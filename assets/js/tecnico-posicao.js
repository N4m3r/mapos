/**
 * Posição do técnico para o mapa em tempo real (admin).
 * Roda nas páginas da Área do Técnico. Só envia se a permissão de GPS JÁ foi
 * concedida (nunca dispara o pedido). Throttle de 20s, pausa com a aba oculta
 * e reenvia ao voltar. Na tela de OS com atendimento, tecnico-localizacao.js
 * já faz o rastreio, então este script não atua.
 */
(function () {
    'use strict';
    if (!navigator.geolocation || (window.checkinConfig && window.checkinConfig.osId)) { return; }

    var INTERVALO = 20000, ultimo = 0, watchId = null, pos = null, ocupado = false;

    function meta(n) { var e = document.querySelector('meta[name="' + n + '"]'); return e ? e.content : ''; }
    function cookie(n) {
        var m = document.cookie.match(new RegExp('(?:^|; )' + n.replace(/[.$?*|{}()\[\]\\\/+^]/g, '\\$&') + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : '';
    }

    function enviar(bateria) {
        if (!pos || ocupado) { return; }
        ocupado = true; ultimo = Date.now();
        var f = new URLSearchParams();
        f.set('latitude', pos.latitude); f.set('longitude', pos.longitude);
        if (pos.accuracy != null) { f.set('precisao', pos.accuracy); }
        if (pos.speed != null) { f.set('velocidade', pos.speed); }
        if (bateria != null) { f.set('bateria', bateria); }
        f.set(meta('csrf-token-name'), cookie(meta('csrf-cookie-name')));
        fetch(meta('base-url') + 'index.php/tecnico/registrar_posicao', {
            method: 'POST', credentials: 'same-origin', body: f,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).catch(function () {}).then(function () { ocupado = false; });
    }

    function disparar() {
        if (navigator.getBattery) {
            navigator.getBattery().then(function (b) { enviar(Math.round(b.level * 100)); }, function () { enviar(null); });
        } else { enviar(null); }
    }

    function onPos(p) {
        pos = p.coords;
        if (Date.now() - ultimo >= INTERVALO) { disparar(); }
    }

    function iniciar() {
        if (watchId !== null || document.hidden) { return; }
        watchId = navigator.geolocation.watchPosition(onPos, function () {}, {
            enableHighAccuracy: false, maximumAge: 15000, timeout: 30000
        });
    }
    function parar() {
        if (watchId !== null) { navigator.geolocation.clearWatch(watchId); watchId = null; }
    }

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) { parar(); } else { iniciar(); if (pos && Date.now() - ultimo >= 5000) { disparar(); } }
    });

    if (navigator.permissions && navigator.permissions.query) {
        navigator.permissions.query({ name: 'geolocation' }).then(function (s) {
            if (s.state === 'granted') { iniciar(); }
            s.onchange = function () { if (s.state === 'granted') { iniciar(); } else { parar(); } };
        }).catch(function () {});
    }
})();
