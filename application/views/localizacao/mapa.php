<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<link rel="stylesheet" href="<?= base_url('assets/plugins/leaflet/leaflet.css') ?>">
<style>
    #mapa-tecnicos { width: 100%; height: 70vh; min-height: 420px; border-radius: 6px; z-index: 1; }
    .loc-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-bottom: 12px; }
    .loc-toolbar .loc-status { font-size: 13px; color: #555; }
    .loc-toolbar .loc-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; background: #2D335B; color: #fff; font-size: 12px; }
    .loc-popup-nome { font-weight: 700; font-size: 14px; margin-bottom: 4px; }
    .loc-popup-linha { font-size: 12px; color: #333; }
    .loc-popup-linha b { color: #2D335B; }
    .loc-vazio { padding: 30px; text-align: center; color: #777; }
</style>

<div class="widget-box">
    <div class="widget-title">
        <span class="icon"><i class="bx bx-map-alt"></i></span>
        <h5>Mapa dos Técnicos — Tempo Real</h5>
    </div>
    <div class="widget-content">
        <div class="loc-toolbar">
            <span class="loc-badge"><span id="loc-total">0</span> técnico(s) em campo</span>
            <span class="loc-status">Atualizado: <span id="loc-atualizado">—</span></span>
            <label style="margin:0; font-size:13px;">
                <input type="checkbox" id="loc-auto" checked> Atualização automática
            </label>
            <button class="btn btn-small" id="loc-refresh"><i class="bx bx-refresh"></i> Atualizar agora</button>
            <button class="btn btn-small" id="loc-fit"><i class="bx bx-expand"></i> Enquadrar</button>
        </div>

        <div id="mapa-tecnicos"></div>
        <div id="loc-vazio" class="loc-vazio" style="display:none;">
            Nenhum técnico com atendimento ativo no momento.
        </div>
    </div>
</div>

<script src="<?= base_url('assets/plugins/leaflet/leaflet.js') ?>"></script>
<script>
(function () {
    'use strict';

    var BASE = '<?= base_url() ?>';
    var ENDPOINT = BASE + 'index.php/localizacao/tecnicos_ativos';
    var REFRESH_MS = 15000;

    // Centro padrão (Brasil) enquanto não há posições.
    var mapa = L.map('mapa-tecnicos').setView([-14.235, -51.925], 4);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(mapa);

    var marcadores = {}; // usuarios_id -> L.marker
    var primeiraCarga = true;
    var timer = null;

    function popupHtml(t) {
        var html = '<div class="loc-popup-nome">' + escapar(t.nome || 'Técnico') + '</div>';
        if (t.os_id) {
            html += '<div class="loc-popup-linha"><b>OS:</b> #' + pad(t.os_id) +
                    (t.os_status ? ' (' + escapar(t.os_status) + ')' : '') + '</div>';
        }
        if (t.cliente) {
            html += '<div class="loc-popup-linha"><b>Cliente:</b> ' + escapar(t.cliente) + '</div>';
        }
        if (t.precisao != null) {
            html += '<div class="loc-popup-linha"><b>Precisão:</b> ~' + Math.round(t.precisao) + ' m</div>';
        }
        var quando = (t.ha_minutos != null)
            ? (t.ha_minutos <= 0 ? 'agora mesmo' : 'há ' + t.ha_minutos + ' min')
            : (t.data_hora || '');
        html += '<div class="loc-popup-linha" style="margin-top:4px;color:#888;">Última posição: ' + quando + '</div>';
        if (t.os_id) {
            html += '<div style="margin-top:6px;"><a class="btn btn-mini" href="' + BASE +
                    'index.php/os/visualizar/' + t.os_id + '" target="_blank">Abrir OS</a></div>';
        }
        return html;
    }

    function atualizarMapa(tecnicos) {
        var vistos = {};
        var bounds = [];

        tecnicos.forEach(function (t) {
            var id = t.usuarios_id;
            var latlng = [t.latitude, t.longitude];
            vistos[id] = true;
            bounds.push(latlng);

            if (marcadores[id]) {
                marcadores[id].setLatLng(latlng);
                marcadores[id].setPopupContent(popupHtml(t));
            } else {
                marcadores[id] = L.marker(latlng).addTo(mapa).bindPopup(popupHtml(t));
                marcadores[id].bindTooltip(t.nome || 'Técnico', { permanent: false, direction: 'top' });
            }
        });

        // Remove marcadores de técnicos que saíram (sem ping recente).
        Object.keys(marcadores).forEach(function (id) {
            if (!vistos[id]) {
                mapa.removeLayer(marcadores[id]);
                delete marcadores[id];
            }
        });

        document.getElementById('loc-total').textContent = tecnicos.length;
        document.getElementById('loc-vazio').style.display = tecnicos.length ? 'none' : 'block';

        if (primeiraCarga && bounds.length) {
            enquadrar();
            primeiraCarga = false;
        }
    }

    function enquadrar() {
        var pts = [];
        Object.keys(marcadores).forEach(function (id) { pts.push(marcadores[id].getLatLng()); });
        if (pts.length === 1) {
            mapa.setView(pts[0], 15);
        } else if (pts.length > 1) {
            mapa.fitBounds(L.latLngBounds(pts).pad(0.2));
        }
    }

    function carregar() {
        $.ajax({ url: ENDPOINT, method: 'GET', dataType: 'json' })
            .done(function (r) {
                if (r && r.success) {
                    atualizarMapa(r.tecnicos || []);
                    document.getElementById('loc-atualizado').textContent = r.servidor || '';
                }
            });
    }

    function agendar() {
        if (timer) { clearInterval(timer); }
        if (document.getElementById('loc-auto').checked) {
            timer = setInterval(carregar, REFRESH_MS);
        }
    }

    function pad(n) { return ('000' + n).slice(-4); }
    function escapar(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    document.getElementById('loc-refresh').addEventListener('click', carregar);
    document.getElementById('loc-fit').addEventListener('click', enquadrar);
    document.getElementById('loc-auto').addEventListener('change', agendar);

    // Corrige renderização do mapa dentro do layout (span12).
    setTimeout(function () { mapa.invalidateSize(); }, 200);

    carregar();
    agendar();
})();
</script>
