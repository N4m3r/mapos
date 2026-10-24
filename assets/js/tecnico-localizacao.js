/**
 * Rastreio de localização do técnico em tempo real.
 *
 * Enquanto houver um atendimento ativo (check-in aberto) na OS aberta na tela,
 * o dispositivo envia periodicamente a posição GPS para o servidor
 * (Tecnico::registrar_localizacao). O GPS só é acionado durante o atendimento —
 * fora dele nenhuma posição é coletada (privacidade e bateria).
 *
 * Depende de: jQuery, csrf.js (token automático) e window.checkinConfig.
 */
(function () {
    'use strict';

    var cfg = window.checkinConfig || {};
    if (!cfg.osId || typeof jQuery === 'undefined') {
        return;
    }

    var baseUrl = cfg.baseUrl || '';
    var osId = cfg.osId;

    var STATUS_INTERVAL = 20000; // reavalia se está em atendimento (sem GPS)
    var PING_INTERVAL = 15000;   // intervalo mínimo entre envios de posição

    var ativo = false;      // rastreio ligado?
    var watchId = null;     // id do watchPosition
    var ultimaPos = null;   // últimas coordenadas conhecidas
    var ultimoPing = 0;     // timestamp do último envio

    function log() {
        if (cfg.debug && window.console) {
            console.log.apply(console, ['[tecnico-localizacao]'].concat([].slice.call(arguments)));
        }
    }

    function nivelBateria(cb) {
        if (navigator.getBattery) {
            navigator.getBattery().then(function (b) {
                cb(Math.round((b.level || 0) * 100));
            }).catch(function () { cb(null); });
        } else {
            cb(null);
        }
    }

    function enviarPing() {
        if (!ultimaPos) {
            return;
        }
        ultimoPing = Date.now();

        nivelBateria(function (bateria) {
            var dados = {
                os_id: osId,
                latitude: ultimaPos.latitude,
                longitude: ultimaPos.longitude,
                precisao: (ultimaPos.accuracy != null) ? ultimaPos.accuracy : '',
                velocidade: (ultimaPos.speed != null) ? ultimaPos.speed : '',
                bateria: (bateria != null) ? bateria : ''
            };

            $.ajax({
                url: baseUrl + 'index.php/tecnico/registrar_localizacao',
                method: 'POST',
                data: dados,
                dataType: 'json'
            }).done(function (r) {
                // Servidor diz que não há mais atendimento ativo → desliga o GPS.
                if (r && r.active === false) {
                    log('servidor sem atendimento ativo, parando rastreio');
                    pararRastreio();
                }
            }).fail(function () {
                log('falha ao enviar ping (mantém rastreio)');
            });
        });
    }

    function onPosicao(pos) {
        ultimaPos = pos.coords;
        if (Date.now() - ultimoPing >= PING_INTERVAL) {
            enviarPing();
        }
    }

    function onErroPosicao(err) {
        log('erro geolocalização', err && err.message);
    }

    function iniciarRastreio() {
        if (ativo || !('geolocation' in navigator)) {
            return;
        }
        ativo = true;
        log('iniciando rastreio');
        watchId = navigator.geolocation.watchPosition(onPosicao, onErroPosicao, {
            enableHighAccuracy: true,
            maximumAge: 10000,
            timeout: 20000
        });
    }

    function pararRastreio() {
        if (!ativo) {
            return;
        }
        ativo = false;
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }
        log('rastreio parado');
    }

    // Verifica no servidor se há atendimento ativo para esta OS (não usa GPS).
    function checarAtendimento() {
        $.ajax({
            url: baseUrl + 'index.php/checkin/status',
            method: 'POST',
            data: { os_id: osId },
            dataType: 'json'
        }).done(function (r) {
            if (r && r.success && r.em_atendimento) {
                iniciarRastreio();
            } else {
                pararRastreio();
            }
        });
    }

    $(function () {
        checarAtendimento();
        setInterval(checarAtendimento, STATUS_INTERVAL);

        // Garante envio periódico mesmo se o watchPosition disparar com folga.
        setInterval(function () {
            if (ativo && ultimaPos && (Date.now() - ultimoPing >= PING_INTERVAL)) {
                enviarPing();
            }
        }, PING_INTERVAL);
    });
})();
