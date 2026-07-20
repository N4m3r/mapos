<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<link rel="stylesheet" href="<?= base_url('assets/plugins/leaflet/leaflet.css') ?>">
<style>
    #mapa-trajeto { width: 100%; height: 66vh; min-height: 400px; border-radius: 6px; z-index: 1; }
    .traj-toolbar { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px; margin-bottom: 12px; }
    .traj-toolbar .campo { display: flex; flex-direction: column; gap: 4px; }
    .traj-toolbar .campo label { font-size: 12px; color: #555; margin: 0; }
    .traj-toolbar select, .traj-toolbar input[type="date"] { height: 32px; }
    .traj-resumo { font-size: 13px; color: #333; margin-bottom: 10px; }
    .traj-resumo b { color: #2D335B; }
    .traj-legenda { font-size: 12px; margin-top: 8px; }
    .traj-legenda span.cor { display: inline-block; width: 12px; height: 12px; border-radius: 2px; margin-right: 4px; vertical-align: middle; }
    .traj-vazio { padding: 24px; text-align: center; color: #777; }
</style>

<div class="widget-box">
    <div class="widget-title">
        <span class="icon"><i class="bx bx-trip"></i></span>
        <h5>Percurso do Técnico</h5>
    </div>
    <div class="widget-content">
        <div class="traj-toolbar">
            <div class="campo">
                <label for="traj-tecnico">Técnico</label>
                <select id="traj-tecnico">
                    <option value="">— selecione —</option>
                    <?php foreach ($tecnicos as $t): ?>
                        <option value="<?= (int) $t->usuarios_id ?>"><?= htmlspecialchars($t->nome, ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="traj-data">Data inicial</label>
                <input type="date" id="traj-data" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="campo">
                <label for="traj-data-fim">Data final</label>
                <input type="date" id="traj-data-fim" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="campo">
                <label for="traj-os">OS (opcional)</label>
                <input type="number" id="traj-os" placeholder="nº da OS" min="1" style="width:110px;">
            </div>
            <button class="btn btn-primary" id="traj-buscar"><i class="bx bx-search"></i> Ver percurso</button>
            <button class="btn btn-small" id="traj-gpx" disabled><i class="bx bx-download"></i> GPX</button>
            <button class="btn btn-small" id="traj-kml" disabled><i class="bx bx-download"></i> KML</button>
        </div>

        <div class="traj-resumo" id="traj-resumo" style="display:none;"></div>

        <div id="mapa-trajeto"></div>

        <div id="traj-legenda" class="traj-legenda"></div>
        <div id="traj-vazio" class="traj-vazio" style="display:none;">
            Nenhum registro de localização para este técnico no período.
        </div>
    </div>
</div>

<script src="<?= base_url('assets/plugins/leaflet/leaflet.js') ?>"></script>
<script>
(function () {
    'use strict';

    var BASE = '<?= base_url() ?>';
    var ENDPOINT = BASE + 'index.php/localizacao/trajeto_dados';
    var EXPORT = BASE + 'index.php/localizacao/exportar_trajeto';
    var CORES = ['#2D335B', '#e74c3c', '#27ae60', '#e67e22', '#8e44ad', '#16a085', '#c0392b', '#2980b9'];

    var ultimoFiltro = null; // guarda os filtros da última busca com resultado

    var mapa = L.map('mapa-trajeto').setView([-14.235, -51.925], 4);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(mapa);

    var camada = L.layerGroup().addTo(mapa); // tudo que é redesenhado a cada busca

    function limpar() {
        camada.clearLayers();
    }

    function fmtHora(dt) {
        if (!dt) { return ''; }
        var p = dt.split(' ');
        return p.length > 1 ? p[1].substring(0, 5) : dt;
    }

    function fmtDist(m) {
        return (m >= 1000) ? (m / 1000).toFixed(2) + ' km' : Math.round(m) + ' m';
    }

    function pad(n) { return ('000' + n).slice(-4); }

    function escapar(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function desenhar(dados) {
        limpar();
        var segmentos = dados.segmentos || [];
        var todosPontos = [];
        var legendaHtml = '';

        segmentos.forEach(function (seg, idx) {
            var cor = CORES[idx % CORES.length];
            var latlngs = seg.pontos.map(function (p) { return [p.latitude, p.longitude]; });
            todosPontos = todosPontos.concat(latlngs);

            if (latlngs.length >= 2) {
                L.polyline(latlngs, { color: cor, weight: 4, opacity: 0.8 }).addTo(camada);
            }

            // Pontos intermediários (círculos pequenos com hora).
            seg.pontos.forEach(function (p) {
                L.circleMarker([p.latitude, p.longitude], {
                    radius: 3, color: cor, fillColor: cor, fillOpacity: 0.9, weight: 1
                }).addTo(camada).bindTooltip(fmtHora(p.data_hora), { direction: 'top' });
            });

            // Início (verde) e fim (vermelho).
            if (latlngs.length) {
                var ini = seg.pontos[0], fim = seg.pontos[seg.pontos.length - 1];
                var infoOs = seg.os_id ? ('OS #' + pad(seg.os_id) + (seg.cliente ? ' — ' + escapar(seg.cliente) : '')) : 'Atendimento';
                L.marker([ini.latitude, ini.longitude])
                    .addTo(camada)
                    .bindPopup('<b>Início</b><br>' + infoOs + '<br>' + escapar(fmtHora(ini.data_hora)) +
                               '<br>Distância: ' + fmtDist(seg.distancia_m));
                if (seg.pontos.length > 1) {
                    L.circleMarker([fim.latitude, fim.longitude], {
                        radius: 6, color: '#c0392b', fillColor: '#e74c3c', fillOpacity: 1, weight: 2
                    }).addTo(camada).bindPopup('<b>Fim</b><br>' + infoOs + '<br>' + escapar(fmtHora(fim.data_hora)));
                }
            }

            legendaHtml += '<div><span class="cor" style="background:' + cor + '"></span>' +
                (seg.os_id ? 'OS #' + pad(seg.os_id) : 'Atendimento') +
                (seg.cliente ? ' — ' + escapar(seg.cliente) : '') +
                ' &middot; ' + escapar(fmtHora(seg.inicio)) + '–' + escapar(fmtHora(seg.fim)) +
                ' &middot; ' + fmtDist(seg.distancia_m) + ' &middot; ' + seg.total_pontos + ' pts</div>';
        });

        document.getElementById('traj-legenda').innerHTML = legendaHtml;

        var resumo = document.getElementById('traj-resumo');
        var vazio = document.getElementById('traj-vazio');
        if (todosPontos.length) {
            resumo.style.display = 'block';
            resumo.innerHTML = '<b>' + segmentos.length + '</b> atendimento(s) &middot; <b>' +
                dados.total_pontos + '</b> posições &middot; distância total <b>' + fmtDist(dados.distancia_total) + '</b>';
            vazio.style.display = 'none';
            mapa.fitBounds(L.latLngBounds(todosPontos).pad(0.2));
        } else {
            resumo.style.display = 'none';
            vazio.style.display = 'block';
        }
    }

    function filtrosAtuais() {
        var usuario = document.getElementById('traj-tecnico').value;
        var data = document.getElementById('traj-data').value;
        var dataFim = document.getElementById('traj-data-fim').value;
        var os = document.getElementById('traj-os').value;
        return { usuario_id: usuario, data: data, data_fim: dataFim || data, os_id: os || '' };
    }

    function habilitarExport(ligado) {
        document.getElementById('traj-gpx').disabled = !ligado;
        document.getElementById('traj-kml').disabled = !ligado;
    }

    function buscar() {
        var f = filtrosAtuais();
        if (!f.usuario_id || !f.data) {
            alert('Selecione o técnico e a data inicial.');
            return;
        }
        $.ajax({ url: ENDPOINT, method: 'GET', dataType: 'json', data: f })
            .done(function (r) {
                if (r && r.success) {
                    desenhar(r);
                    var temPontos = (r.total_pontos || 0) > 0;
                    ultimoFiltro = temPontos ? f : null;
                    habilitarExport(temPontos);
                } else {
                    habilitarExport(false);
                    alert((r && r.message) || 'Falha ao buscar percurso.');
                }
            })
            .fail(function () {
                habilitarExport(false);
                alert('Erro de comunicação ao buscar percurso.');
            });
    }

    function exportar(formato) {
        if (!ultimoFiltro) { return; }
        var qs = $.param($.extend({}, ultimoFiltro, { formato: formato }));
        window.location = EXPORT + '?' + qs;
    }

    document.getElementById('traj-buscar').addEventListener('click', buscar);
    document.getElementById('traj-gpx').addEventListener('click', function () { exportar('gpx'); });
    document.getElementById('traj-kml').addEventListener('click', function () { exportar('kml'); });

    setTimeout(function () { mapa.invalidateSize(); }, 200);
})();
</script>
