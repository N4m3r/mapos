<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<link rel="stylesheet" href="<?= base_url('assets/plugins/leaflet/leaflet.css') ?>">
<style>
    #mapa-tecnicos, #mapa-trajeto { width: 100%; height: 66vh; min-height: 400px; border-radius: 6px; z-index: 1; }

    /* Abas */
    .loc-tabs { display: flex; gap: 4px; margin-bottom: 16px; border-bottom: 1px solid #ddd; }
    .loc-tabs button {
        border: none; background: transparent; padding: 10px 18px; cursor: pointer;
        font-size: 14px; color: #666; border-bottom: 3px solid transparent; margin-bottom: -1px;
    }
    .loc-tabs button.active { color: #2D335B; border-bottom-color: #2D335B; font-weight: 600; }
    .loc-pane { display: none; }
    .loc-pane.active { display: block; }

    /* Toolbar tempo real */
    .loc-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-bottom: 10px; }
    .loc-toolbar .loc-status { font-size: 13px; color: #555; }
    .loc-toolbar .loc-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; background: #2D335B; color: #fff; font-size: 12px; }
    .loc-legenda { font-size: 12px; color: #555; margin-bottom: 10px; display: flex; flex-wrap: wrap; gap: 14px; align-items: center; }
    .loc-legenda .pt { display: inline-block; width: 11px; height: 11px; border-radius: 50%; margin-right: 5px; vertical-align: middle; border: 1px solid rgba(0,0,0,.2); }
    .loc-popup-nome { font-weight: 700; font-size: 14px; margin-bottom: 4px; }
    .loc-popup-linha { font-size: 12px; color: #333; }
    .loc-popup-linha b { color: #2D335B; }
    .loc-vazio { padding: 30px; text-align: center; color: #777; }

    /* Toolbar percurso */
    .traj-toolbar { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px; margin-bottom: 10px; }
    .traj-toolbar .campo { display: flex; flex-direction: column; gap: 4px; }
    .traj-toolbar .campo label { font-size: 12px; color: #555; margin: 0; }
    .traj-toolbar select, .traj-toolbar input[type="date"] { height: 32px; }
    .traj-toolbar2 { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; margin-bottom: 12px; font-size: 12px; color: #555; }
    .traj-toolbar2 label { margin: 0; }
    .traj-resumo { font-size: 13px; color: #333; margin-bottom: 10px; }
    .traj-resumo b { color: #2D335B; }
    .traj-legenda { font-size: 12px; margin-top: 8px; }
    .traj-legenda span.cor { display: inline-block; width: 12px; height: 12px; border-radius: 2px; margin-right: 4px; vertical-align: middle; }
    .traj-vazio { padding: 24px; text-align: center; color: #777; }

    /* KPIs */
    .traj-kpis { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 12px; }
    .kpi { flex: 1 1 120px; min-width: 110px; background: #f6f6fa; border: 1px solid #e6e6ef; border-radius: 8px; padding: 8px 12px; }
    .kpi .kpi-num { display: block; font-size: 20px; font-weight: 700; color: #2D335B; line-height: 1.1; }
    .kpi .kpi-lbl { font-size: 11px; color: #777; text-transform: uppercase; letter-spacing: .3px; }
    .kpi.alerta .kpi-num { color: #c0392b; }

    /* Replay */
    .traj-replay { display: none; align-items: center; gap: 10px; margin-top: 8px; font-size: 12px; color: #555; }
    .traj-replay input[type="range"] { flex: 1 1 auto; }
    .traj-replay .rp-hora { min-width: 46px; font-weight: 600; color: #2D335B; }

    /* Layout percurso: mapa + painel de OS do dia lado a lado */
    .traj-grid { display: flex; gap: 16px; align-items: stretch; }
    .traj-grid .traj-mapa-col { flex: 1 1 auto; min-width: 0; }
    .traj-grid .traj-os-col { flex: 0 0 300px; max-width: 300px; }
    @media (max-width: 900px) { .traj-grid { flex-direction: column; } .traj-grid .traj-os-col { flex-basis: auto; max-width: none; } }

    .os-dia-titulo { font-size: 13px; font-weight: 600; color: #2D335B; margin: 0 0 8px; display: flex; align-items: center; gap: 6px; }
    .os-dia-lista { max-height: 66vh; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; }
    .os-card { border: 1px solid #e2e2e2; border-left: 4px solid #8e44ad; border-radius: 6px; padding: 8px 10px; cursor: pointer; transition: background .15s; }
    .os-card:hover { background: #f6f4fb; }
    .os-card.ordem { counter-increment: none; }
    .os-card .os-card-head { display: flex; justify-content: space-between; align-items: center; gap: 6px; }
    .os-card .os-num { font-weight: 700; color: #2D335B; font-size: 13px; }
    .os-card .os-status { font-size: 11px; padding: 1px 8px; border-radius: 10px; background: #eee; color: #555; white-space: nowrap; }
    .os-card .os-hora { font-size: 12px; color: #8e44ad; font-weight: 600; }
    .os-card .os-cliente { font-size: 13px; color: #333; margin-top: 2px; }
    .os-card .os-end { font-size: 11px; color: #888; margin-top: 2px; }
    .os-card .os-tag { display: inline-block; font-size: 10px; margin-top: 4px; padding: 1px 6px; border-radius: 8px; }
    .os-card .tag-visita { background: #e8f6ee; color: #1e8449; }
    .os-card .tag-sem { background: #fdecea; color: #c0392b; }
    .os-card .tag-longe { background: #fef5e7; color: #b9770e; }
    .os-card .tag-semcoord { background: #f0f0f0; color: #999; }
    .os-card .os-seq { display: inline-block; width: 18px; height: 18px; line-height: 18px; text-align: center; border-radius: 50%; background: #8e44ad; color: #fff; font-size: 11px; font-weight: 700; margin-right: 4px; }
    .os-dia-vazio { font-size: 12px; color: #999; padding: 12px 0; }

    /* Pino de OS no mapa (número da OS) */
    .os-pin-badge { background: #8e44ad; color: #fff; font-size: 11px; font-weight: 700; border: 2px solid #fff;
        border-radius: 50% 50% 50% 0; width: 26px; height: 26px; line-height: 22px; text-align: center;
        transform: rotate(-45deg); box-shadow: 0 1px 4px rgba(0,0,0,.4); }
    .os-pin-badge span { display: inline-block; transform: rotate(45deg); }

    /* Marcador de status do técnico (tempo real) */
    .tec-dot { width: 18px; height: 18px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 1px 4px rgba(0,0,0,.4); }
</style>

<div class="widget-box">
    <div class="widget-title">
        <span class="icon"><i class="bx bx-map-alt"></i></span>
        <h5>Gestão do Técnico no Mapa</h5>
    </div>
    <div class="widget-content">

        <div class="loc-tabs">
            <button type="button" class="active" data-tab="tempo-real"><i class="bx bx-broadcast"></i> Tempo Real</button>
            <button type="button" data-tab="percurso"><i class="bx bx-trip"></i> Percurso (histórico)</button>
        </div>

        <!-- ========================= ABA: TEMPO REAL ========================= -->
        <div class="loc-pane active" id="pane-tempo-real">
            <div class="loc-toolbar">
                <span class="loc-badge"><span id="loc-total">0</span> técnico(s) em campo</span>
                <select id="loc-tecnico" style="height:32px;max-width:220px;">
                    <option value="">Todos os técnicos</option>
                    <?php foreach ($tecnicos as $t): ?>
                        <option value="<?= (int) $t->usuarios_id ?>"><?= htmlspecialchars($t->nome, ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="loc-status">Atualizado: <span id="loc-atualizado">—</span></span>
                <label style="margin:0; font-size:13px;">
                    <input type="checkbox" id="loc-auto" checked> Atualização automática
                </label>
                <button class="btn btn-small" id="loc-refresh"><i class="bx bx-refresh"></i> Atualizar agora</button>
                <button class="btn btn-small" id="loc-fit"><i class="bx bx-expand"></i> Enquadrar</button>
            </div>

            <div class="loc-legenda">
                <span><span class="pt" style="background:#27ae60"></span>Em atendimento</span>
                <span><span class="pt" style="background:#2980b9"></span>Em deslocamento</span>
                <span><span class="pt" style="background:#e67e22"></span>Sinal antigo (5–15 min)</span>
                <span><span class="pt" style="background:#c0392b"></span>Sem sinal (&gt;15 min)</span>
                <span id="loc-inativos" style="color:#c0392b;font-weight:600;"></span>
            </div>

            <div id="mapa-tecnicos"></div>
            <div id="loc-vazio" class="loc-vazio" style="display:none;">
                Nenhum técnico com atendimento ativo no momento.
            </div>
        </div>

        <!-- ========================= ABA: PERCURSO ========================= -->
        <div class="loc-pane" id="pane-percurso">
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

            <div class="traj-toolbar2">
                <label><input type="checkbox" id="traj-rota-plan"> Rota planejada</label>
                <label><input type="checkbox" id="traj-vias"> Seguir vias (OSRM)</label>
                <label><input type="checkbox" id="traj-heat"> Heatmap</label>
                <button class="btn btn-mini" id="traj-otimizar" disabled><i class="bx bx-shuffle"></i> Sugerir roteiro</button>
                <button class="btn btn-mini" id="traj-replay-btn" disabled><i class="bx bx-play"></i> Replay</button>
            </div>

            <div class="traj-kpis" id="traj-kpis" style="display:none;">
                <div class="kpi"><span class="kpi-num" id="kpi-osdia">0</span><span class="kpi-lbl">OS do dia</span></div>
                <div class="kpi"><span class="kpi-num" id="kpi-visitadas">0</span><span class="kpi-lbl">Visitadas</span></div>
                <div class="kpi alerta"><span class="kpi-num" id="kpi-semvisita">0</span><span class="kpi-lbl">Sem visita</span></div>
                <div class="kpi"><span class="kpi-num" id="kpi-posicoes">0</span><span class="kpi-lbl">Posições</span></div>
                <div class="kpi"><span class="kpi-num" id="kpi-distancia">0</span><span class="kpi-lbl">Distância</span></div>
                <div class="kpi"><span class="kpi-num" id="kpi-tempo">—</span><span class="kpi-lbl">Tempo em campo</span></div>
            </div>

            <div class="traj-resumo" id="traj-resumo" style="display:none;"></div>

            <div class="traj-grid">
                <div class="traj-mapa-col">
                    <div id="mapa-trajeto"></div>
                    <div class="traj-replay" id="traj-replay">
                        <button class="btn btn-mini" id="rp-play"><i class="bx bx-play"></i></button>
                        <input type="range" id="rp-range" min="0" max="0" value="0">
                        <span class="rp-hora" id="rp-hora">—</span>
                    </div>
                    <div id="traj-legenda" class="traj-legenda"></div>
                    <div id="traj-vazio" class="traj-vazio" style="display:none;">
                        Nenhum registro de localização para este técnico no período.
                    </div>
                </div>
                <div class="traj-os-col">
                    <div class="os-dia-titulo"><i class="bx bx-clipboard"></i> OS do dia <span id="os-dia-contador"></span></div>
                    <div class="os-dia-lista" id="os-dia-lista"></div>
                    <div class="os-dia-vazio" id="os-dia-vazio">Selecione um técnico e clique em “Ver percurso”.</div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="<?= base_url('assets/plugins/leaflet/leaflet.js') ?>"></script>
<script>
(function () {
    'use strict';

    var BASE = '<?= base_url() ?>';

    /* ===================== HELPERS ===================== */
    function escapar(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function pad(n) { return ('000' + n).slice(-4); }
    function fmtDist(m) { return (m >= 1000) ? (m / 1000).toFixed(2) + ' km' : Math.round(m) + ' m'; }
    function fmtHora(dt) {
        if (!dt) { return ''; }
        var p = dt.split(' ');
        return p.length > 1 ? p[1].substring(0, 5) : dt;
    }
    function haversine(lat1, lon1, lat2, lon2) {
        var R = 6371000, tr = Math.PI / 180;
        var dLat = (lat2 - lat1) * tr, dLon = (lon2 - lon1) * tr;
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * tr) * Math.cos(lat2 * tr) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    /* ===================== ABA TEMPO REAL ===================== */
    var TempoReal = (function () {
        var ENDPOINT = BASE + 'index.php/localizacao/tecnicos_ativos?minutos=30';
        var REFRESH_MS = 15000;

        var mapa = null;
        var marcadores = {}; // usuarios_id -> L.marker
        var primeiraCarga = true;
        var timer = null;
        var ultimaLista = [];
        var filtroTecnico = '';

        // Cor do técnico conforme frescor do sinal e status.
        function corTecnico(t) {
            if (t.ha_minutos != null && t.ha_minutos > 15) { return '#c0392b'; }
            if (t.ha_minutos != null && t.ha_minutos > 5) { return '#e67e22'; }
            return t.status === 'atendimento' ? '#27ae60' : '#2980b9';
        }

        function iconeTecnico(t) {
            return L.divIcon({
                className: 'tec-icon',
                html: '<div class="tec-dot" style="background:' + corTecnico(t) + '"></div>',
                iconSize: [18, 18],
                iconAnchor: [9, 9],
                popupAnchor: [0, -10]
            });
        }

        function popupHtml(t) {
            var html = '<div class="loc-popup-nome">' + escapar(t.nome || 'Técnico') + '</div>';
            var st = (t.ha_minutos != null && t.ha_minutos > 15) ? 'Sem sinal'
                   : (t.status === 'atendimento' ? 'Em atendimento' : 'Em deslocamento');
            html += '<div class="loc-popup-linha"><b>Status:</b> ' + st + '</div>';
            if (t.os_id) {
                html += '<div class="loc-popup-linha"><b>OS:</b> #' + pad(t.os_id) +
                        (t.os_status ? ' (' + escapar(t.os_status) + ')' : '') + '</div>';
            }
            if (t.cliente) {
                html += '<div class="loc-popup-linha"><b>Cliente:</b> ' + escapar(t.cliente) + '</div>';
            }
            if (t.velocidade != null) {
                html += '<div class="loc-popup-linha"><b>Velocidade:</b> ' + Math.round(t.velocidade * 3.6) + ' km/h</div>';
            }
            if (t.bateria != null) {
                html += '<div class="loc-popup-linha"><b>Bateria:</b> ' + t.bateria + '%</div>';
            }
            if (t.precisao != null) {
                html += '<div class="loc-popup-linha"><b>Precisão:</b> ~' + Math.round(t.precisao) + ' m</div>';
            }
            var quando = (t.ha_minutos != null)
                ? (t.ha_minutos <= 0 ? 'agora mesmo' : 'há ' + t.ha_minutos + ' min')
                : (t.data_hora || '');
            html += '<div class="loc-popup-linha" style="margin-top:4px;color:#888;">Última posição: ' + quando + '</div>';
            html += '<div style="margin-top:6px; display:flex; gap:6px; flex-wrap:wrap;">';
            if (t.os_id) {
                html += '<a class="btn btn-mini" href="' + BASE +
                        'index.php/os/visualizar/' + t.os_id + '" target="_blank">Abrir OS</a>';
            }
            html += '<a class="btn btn-mini" href="#" onclick="return locVerPercursoHoje(' + t.usuarios_id + ');">Percurso de hoje</a>';
            html += '</div>';
            return html;
        }

        function atualizarMapa(tecnicos) {
            if (filtroTecnico) {
                tecnicos = tecnicos.filter(function (t) { return String(t.usuarios_id) === filtroTecnico; });
            }

            var vistos = {};
            var bounds = [];
            var inativos = 0;

            tecnicos.forEach(function (t) {
                var id = t.usuarios_id;
                var latlng = [t.latitude, t.longitude];
                vistos[id] = true;
                bounds.push(latlng);
                if (t.ha_minutos != null && t.ha_minutos > 15) { inativos++; }

                if (marcadores[id]) {
                    marcadores[id].setLatLng(latlng);
                    marcadores[id].setIcon(iconeTecnico(t));
                    marcadores[id].setPopupContent(popupHtml(t));
                } else {
                    marcadores[id] = L.marker(latlng, { icon: iconeTecnico(t) }).addTo(mapa).bindPopup(popupHtml(t));
                    marcadores[id].bindTooltip(t.nome || 'Técnico', { permanent: false, direction: 'top' });
                }
            });

            Object.keys(marcadores).forEach(function (id) {
                if (!vistos[id]) {
                    mapa.removeLayer(marcadores[id]);
                    delete marcadores[id];
                }
            });

            document.getElementById('loc-total').textContent = tecnicos.length;
            document.getElementById('loc-vazio').style.display = tecnicos.length ? 'none' : 'block';
            document.getElementById('loc-inativos').textContent = inativos ? ('⚠ ' + inativos + ' sem sinal recente') : '';

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
                        ultimaLista = r.tecnicos || [];
                        atualizarMapa(ultimaLista);
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

        function init() {
            if (mapa) { return; }
            mapa = L.map('mapa-tecnicos').setView([-14.235, -51.925], 4);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19, attribution: '&copy; OpenStreetMap'
            }).addTo(mapa);

            document.getElementById('loc-refresh').addEventListener('click', carregar);
            document.getElementById('loc-fit').addEventListener('click', enquadrar);
            document.getElementById('loc-auto').addEventListener('change', agendar);
            document.getElementById('loc-tecnico').addEventListener('change', function () {
                filtroTecnico = this.value;
                primeiraCarga = true;
                atualizarMapa(ultimaLista);
            });

            carregar();
            agendar();
        }

        return {
            init: init,
            refresh: function () { if (mapa) { mapa.invalidateSize(); } }
        };
    })();

    /* ===================== ABA PERCURSO ===================== */
    var Percurso = (function () {
        var ENDPOINT = BASE + 'index.php/localizacao/trajeto_dados';
        var ENDPOINT_OS = BASE + 'index.php/localizacao/os_do_dia';
        var EXPORT = BASE + 'index.php/localizacao/exportar_trajeto';
        var OSRM = 'https://router.project-osrm.org/route/v1/driving/';
        var CORES = ['#2D335B', '#e74c3c', '#27ae60', '#e67e22', '#8e44ad', '#16a085', '#c0392b', '#2980b9'];
        var LONGE_M = 100; // limiar para "atendida longe do local"

        var mapa = null;
        var camada = null;     // percurso GPS
        var camadaOs = null;   // pinos das OS + rota planejada
        var camadaHeat = null; // heatmap
        var ultimoFiltro = null;
        var ultimoTrajeto = null; // { segmentos, total_pontos, distancia_total }
        var ultimaOsDia = null;   // array de OS
        var ordemOtimizada = null; // array de idOs (roteiro sugerido) ou null
        var pinosOs = {};

        // Replay
        var replayPts = [];    // pontos concatenados ordenados por tempo
        var replayMarker = null;
        var replayTimer = null;
        var replayIdx = 0;

        function osIcon(numero, seq) {
            var label = seq != null ? seq : numero;
            return L.divIcon({
                className: 'os-pin',
                html: '<div class="os-pin-badge"><span>' + label + '</span></div>',
                iconSize: [26, 26], iconAnchor: [13, 26], popupAnchor: [0, -24]
            });
        }

        /* -------- desenho do percurso GPS -------- */
        function desenharTrajeto() {
            camada.clearLayers();
            if (!ultimoTrajeto) { return; }
            var segmentos = ultimoTrajeto.segmentos || [];
            var legendaHtml = '';

            segmentos.forEach(function (seg, idx) {
                var cor = CORES[idx % CORES.length];
                var latlngs = seg.pontos.map(function (p) { return [p.latitude, p.longitude]; });

                if (latlngs.length >= 2) {
                    L.polyline(latlngs, { color: cor, weight: 4, opacity: 0.8 }).addTo(camada);
                }
                seg.pontos.forEach(function (p) {
                    L.circleMarker([p.latitude, p.longitude], {
                        radius: 3, color: cor, fillColor: cor, fillOpacity: 0.9, weight: 1
                    }).addTo(camada).bindTooltip(fmtHora(p.data_hora), { direction: 'top' });
                });
                if (latlngs.length) {
                    var ini = seg.pontos[0], fim = seg.pontos[seg.pontos.length - 1];
                    var infoOs = seg.os_id ? ('OS #' + pad(seg.os_id) + (seg.cliente ? ' — ' + escapar(seg.cliente) : '')) : 'Atendimento';
                    L.marker([ini.latitude, ini.longitude]).addTo(camada)
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
        }

        // Mapa auxiliar: os_id -> primeiro ponto real do atendimento (do trajeto).
        function pontosRealPorOs() {
            var map = {};
            if (!ultimoTrajeto) { return map; }
            (ultimoTrajeto.segmentos || []).forEach(function (seg) {
                if (seg.os_id && seg.pontos && seg.pontos.length) {
                    map[seg.os_id] = seg.pontos[0];
                }
            });
            return map;
        }

        /* -------- OS do dia: pinos + cards -------- */
        function desenharOsDoDia() {
            camadaOs.clearLayers();
            pinosOs = {};

            var contador = document.getElementById('os-dia-contador');
            var elLista = document.getElementById('os-dia-lista');
            var elVazio = document.getElementById('os-dia-vazio');
            var lista = ultimaOsDia || [];

            contador.textContent = lista.length ? '(' + lista.length + ')' : '';

            if (!lista.length) {
                elLista.innerHTML = '';
                elVazio.textContent = 'Nenhuma OS atribuída a este técnico no período.';
                elVazio.style.display = 'block';
                document.getElementById('traj-otimizar').disabled = true;
                return;
            }
            elVazio.style.display = 'none';

            // Ordena conforme roteiro otimizado, se houver.
            if (ordemOtimizada) {
                var pos = {};
                ordemOtimizada.forEach(function (id, i) { pos[id] = i; });
                lista = lista.slice().sort(function (a, b) {
                    var pa = pos[a.idOs] != null ? pos[a.idOs] : 999;
                    var pb = pos[b.idOs] != null ? pos[b.idOs] : 999;
                    return pa - pb;
                });
            }

            var realPorOs = pontosRealPorOs();
            var comCoord = [];
            var cards = '';

            lista.forEach(function (os, i) {
                var temCoord = (os.latitude != null && os.longitude != null);
                var visitou = !!realPorOs[os.idOs];
                var seq = ordemOtimizada ? (i + 1) : null;

                // Distância planejado × realizado.
                var distReal = null;
                if (temCoord && visitou) {
                    var pr = realPorOs[os.idOs];
                    distReal = haversine(os.latitude, os.longitude, pr.latitude, pr.longitude);
                }

                if (temCoord) {
                    var m = L.marker([os.latitude, os.longitude], { icon: osIcon(os.idOs, seq) })
                        .addTo(camadaOs)
                        .bindPopup('<b>OS #' + pad(os.idOs) + '</b>' +
                            (os.status ? ' <span style="color:#888">(' + escapar(os.status) + ')</span>' : '') +
                            (os.cliente ? '<br>' + escapar(os.cliente) : '') +
                            (os.endereco ? '<br><span style="color:#888">' + escapar(os.endereco) + '</span>' : '') +
                            (distReal != null ? '<br><span style="color:#b9770e">Atendida a ' + fmtDist(distReal) + ' do local</span>' : '') +
                            '<br><a class="btn btn-mini" style="margin-top:6px" href="' + BASE +
                            'index.php/os/visualizar/' + os.idOs + '" target="_blank">Abrir OS</a>');
                    pinosOs[os.idOs] = m;
                    comCoord.push({ os: os, latlng: [os.latitude, os.longitude] });
                }

                var tag = '';
                if (!visitou) {
                    tag = '<span class="os-tag tag-sem">sem visita</span>';
                } else if (distReal != null && distReal > LONGE_M) {
                    tag = '<span class="os-tag tag-longe">atendida a ' + fmtDist(distReal) + '</span>';
                } else if (visitou) {
                    tag = '<span class="os-tag tag-visita">visitada</span>';
                }
                if (!temCoord) {
                    tag += ' <span class="os-tag tag-semcoord">sem local</span>';
                }

                cards += '<div class="os-card" data-os="' + os.idOs + '" style="border-left-color:' +
                    (visitou ? '#27ae60' : (temCoord ? '#8e44ad' : '#bbb')) + '">' +
                    '<div class="os-card-head">' +
                        '<span class="os-num">' + (seq != null ? '<span class="os-seq">' + seq + '</span>' : '') + '#' + pad(os.idOs) + '</span>' +
                        '<span class="os-hora">' + escapar(fmtAgenda(os)) + '</span>' +
                    '</div>' +
                    (os.status ? '<div><span class="os-status">' + escapar(os.status) + '</span></div>' : '') +
                    (os.cliente ? '<div class="os-cliente">' + escapar(os.cliente) + '</div>' : '') +
                    (os.endereco ? '<div class="os-end">' + escapar(os.endereco) + '</div>' : '') +
                    (tag ? '<div>' + tag + '</div>' : '') +
                    '</div>';
            });
            elLista.innerHTML = cards;

            Array.prototype.forEach.call(elLista.querySelectorAll('.os-card'), function (card) {
                card.addEventListener('click', function () {
                    var m = pinosOs[card.getAttribute('data-os')];
                    if (m) { mapa.setView(m.getLatLng(), Math.max(mapa.getZoom(), 15)); m.openPopup(); }
                });
            });

            document.getElementById('traj-otimizar').disabled = comCoord.length < 2;

            desenharRotaPlanejada(comCoord);
        }

        function fmtAgenda(os) {
            var dt = os.agendamento || os.data_inicial;
            if (!dt) { return ''; }
            var p = dt.split(' ');
            if (p.length > 1 && p[1].substring(0, 5) !== '00:00') { return p[1].substring(0, 5); }
            var d = p[0].split('-');
            return d.length === 3 ? d[2] + '/' + d[1] : dt;
        }

        function desenharRotaPlanejada(comCoord) {
            if (!document.getElementById('traj-rota-plan').checked || comCoord.length < 2) { return; }
            var latlngs = comCoord.map(function (c) { return c.latlng; });

            if (document.getElementById('traj-vias').checked) {
                snapVias(latlngs, function (geometria) {
                    L.polyline(geometria || latlngs, { color: '#8e44ad', weight: 3, opacity: 0.75, dashArray: geometria ? null : '8,8' }).addTo(camadaOs);
                });
            } else {
                L.polyline(latlngs, { color: '#8e44ad', weight: 3, opacity: 0.7, dashArray: '8,8' }).addTo(camadaOs);
            }
        }

        // OSRM (best-effort): rota por vias entre os waypoints. Falha → callback(null).
        function snapVias(latlngs, cb) {
            var coords = latlngs.slice(0, 25).map(function (p) { return p[1] + ',' + p[0]; }).join(';');
            $.ajax({ url: OSRM + coords, method: 'GET', dataType: 'json', data: { overview: 'full', geometries: 'geojson' }, timeout: 8000 })
                .done(function (r) {
                    if (r && r.routes && r.routes[0] && r.routes[0].geometry) {
                        cb(r.routes[0].geometry.coordinates.map(function (c) { return [c[1], c[0]]; }));
                    } else { cb(null); }
                })
                .fail(function () { cb(null); });
        }

        /* -------- Heatmap leve (sem plugin): densidade por sobreposição -------- */
        function desenharHeat() {
            camadaHeat.clearLayers();
            if (!document.getElementById('traj-heat').checked || !ultimoTrajeto) { return; }
            (ultimoTrajeto.segmentos || []).forEach(function (seg) {
                seg.pontos.forEach(function (p) {
                    L.circleMarker([p.latitude, p.longitude], {
                        radius: 16, stroke: false, fillColor: '#e74c3c', fillOpacity: 0.12
                    }).addTo(camadaHeat);
                });
            });
        }

        /* -------- Roteiro otimizado (vizinho mais próximo) -------- */
        function otimizarRoteiro() {
            var lista = (ultimaOsDia || []).filter(function (o) { return o.latitude != null && o.longitude != null; });
            if (lista.length < 2) { return; }

            // Ponto de partida: primeiro ping do técnico, se houver; senão a 1ª OS.
            var partida = null;
            if (ultimoTrajeto && ultimoTrajeto.segmentos && ultimoTrajeto.segmentos.length) {
                var pts = ultimoTrajeto.segmentos[0].pontos;
                if (pts && pts.length) { partida = { latitude: pts[0].latitude, longitude: pts[0].longitude }; }
            }
            var restantes = lista.slice();
            var ordem = [];
            var atual = partida || restantes[0];
            if (!partida) { ordem.push(restantes[0].idOs); restantes.splice(0, 1); atual = lista[0]; }

            while (restantes.length) {
                var melhor = 0, melhorD = Infinity;
                for (var i = 0; i < restantes.length; i++) {
                    var d = haversine(atual.latitude, atual.longitude, restantes[i].latitude, restantes[i].longitude);
                    if (d < melhorD) { melhorD = d; melhor = i; }
                }
                atual = restantes[melhor];
                ordem.push(atual.idOs);
                restantes.splice(melhor, 1);
            }
            ordemOtimizada = ordem;
            // Força a rota planejada ligada para visualizar o roteiro.
            document.getElementById('traj-rota-plan').checked = true;
            desenharOsDoDia();
            ajustarBounds();
        }

        /* -------- Replay -------- */
        function prepararReplay() {
            pararReplay();
            replayPts = [];
            if (ultimoTrajeto) {
                (ultimoTrajeto.segmentos || []).forEach(function (seg) {
                    seg.pontos.forEach(function (p) { replayPts.push(p); });
                });
            }
            replayPts.sort(function (a, b) { return (a.data_hora || '') < (b.data_hora || '') ? -1 : 1; });

            var barra = document.getElementById('traj-replay');
            var range = document.getElementById('rp-range');
            var btn = document.getElementById('traj-replay-btn');
            if (replayPts.length >= 2) {
                barra.style.display = 'flex';
                range.max = replayPts.length - 1;
                range.value = 0;
                btn.disabled = false;
                posicionarReplay(0);
            } else {
                barra.style.display = 'none';
                btn.disabled = true;
            }
        }

        function posicionarReplay(i) {
            replayIdx = i;
            var p = replayPts[i];
            if (!p) { return; }
            var ll = [p.latitude, p.longitude];
            if (!replayMarker) {
                replayMarker = L.circleMarker(ll, { radius: 8, color: '#000', fillColor: '#f1c40f', fillOpacity: 1, weight: 2 }).addTo(camada);
            } else {
                if (!camada.hasLayer(replayMarker)) { replayMarker.addTo(camada); }
                replayMarker.setLatLng(ll);
            }
            document.getElementById('rp-range').value = i;
            document.getElementById('rp-hora').textContent = fmtHora(p.data_hora) || '—';
        }

        function tocarReplay() {
            var icon = document.querySelector('#rp-play i');
            if (replayTimer) { pararReplay(); return; }
            if (replayIdx >= replayPts.length - 1) { replayIdx = 0; }
            if (icon) { icon.className = 'bx bx-pause'; }
            replayTimer = setInterval(function () {
                if (replayIdx >= replayPts.length - 1) { pararReplay(); return; }
                posicionarReplay(replayIdx + 1);
            }, 250);
        }

        function pararReplay() {
            if (replayTimer) { clearInterval(replayTimer); replayTimer = null; }
            var icon = document.querySelector('#rp-play i');
            if (icon) { icon.className = 'bx bx-play'; }
        }

        /* -------- KPIs -------- */
        function atualizarKpis() {
            var box = document.getElementById('traj-kpis');
            var osDia = ultimaOsDia || [];
            var real = pontosRealPorOs();
            var visitadas = osDia.filter(function (o) { return real[o.idOs]; }).length;
            var semVisita = osDia.length - visitadas;

            var posicoes = ultimoTrajeto ? (ultimoTrajeto.total_pontos || 0) : 0;
            var distancia = ultimoTrajeto ? (ultimoTrajeto.distancia_total || 0) : 0;

            // Tempo em campo: primeiro → último ping de todos os segmentos.
            var ini = null, fim = null;
            if (ultimoTrajeto) {
                (ultimoTrajeto.segmentos || []).forEach(function (s) {
                    if (s.inicio && (!ini || s.inicio < ini)) { ini = s.inicio; }
                    if (s.fim && (!fim || s.fim > fim)) { fim = s.fim; }
                });
            }
            var tempo = '—';
            if (ini && fim) {
                var ms = (new Date(fim.replace(' ', 'T')) - new Date(ini.replace(' ', 'T')));
                if (ms > 0) {
                    var min = Math.round(ms / 60000);
                    tempo = min >= 60 ? (Math.floor(min / 60) + 'h' + ('0' + (min % 60)).slice(-2)) : (min + ' min');
                }
            }

            document.getElementById('kpi-osdia').textContent = osDia.length;
            document.getElementById('kpi-visitadas').textContent = visitadas;
            document.getElementById('kpi-semvisita').textContent = semVisita;
            document.getElementById('kpi-posicoes').textContent = posicoes;
            document.getElementById('kpi-distancia').textContent = fmtDist(distancia);
            document.getElementById('kpi-tempo').textContent = tempo;

            box.style.display = (osDia.length || posicoes) ? 'flex' : 'none';
        }

        /* -------- render coordenado -------- */
        function ajustarBounds() {
            var pts = [];
            if (ultimoTrajeto) {
                (ultimoTrajeto.segmentos || []).forEach(function (s) {
                    s.pontos.forEach(function (p) { pts.push([p.latitude, p.longitude]); });
                });
            }
            Object.keys(pinosOs).forEach(function (id) { pts.push(pinosOs[id].getLatLng()); });
            if (pts.length) { mapa.fitBounds(L.latLngBounds(pts).pad(0.2)); }
        }

        function render() {
            desenharTrajeto();
            desenharOsDoDia();
            desenharHeat();
            atualizarKpis();
            prepararReplay();

            var resumo = document.getElementById('traj-resumo');
            var vazio = document.getElementById('traj-vazio');
            var temPontos = ultimoTrajeto && (ultimoTrajeto.total_pontos || 0) > 0;
            if (temPontos) {
                resumo.style.display = 'block';
                resumo.innerHTML = '<b>' + (ultimoTrajeto.segmentos || []).length + '</b> atendimento(s) &middot; <b>' +
                    ultimoTrajeto.total_pontos + '</b> posições &middot; distância total <b>' + fmtDist(ultimoTrajeto.distancia_total) + '</b>';
                vazio.style.display = 'none';
            } else {
                resumo.style.display = 'none';
                vazio.style.display = (ultimaOsDia && ultimaOsDia.length) ? 'none' : 'block';
                if (ultimoTrajeto) { vazio.style.display = (ultimaOsDia && ultimaOsDia.length) ? 'none' : 'block'; }
            }
            ajustarBounds();
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
            if (!f.usuario_id || !f.data) { alert('Selecione o técnico e a data inicial.'); return; }

            ultimoTrajeto = null;
            ultimaOsDia = null;
            ordemOtimizada = null;

            $.ajax({ url: ENDPOINT_OS, method: 'GET', dataType: 'json', data: f })
                .done(function (r) { ultimaOsDia = (r && r.success && r.os) ? r.os : []; render(); })
                .fail(function () { ultimaOsDia = []; render(); });

            $.ajax({ url: ENDPOINT, method: 'GET', dataType: 'json', data: f })
                .done(function (r) {
                    if (r && r.success) {
                        ultimoTrajeto = r;
                        var temPontos = (r.total_pontos || 0) > 0;
                        ultimoFiltro = temPontos ? f : null;
                        habilitarExport(temPontos);
                        render();
                    } else {
                        ultimoTrajeto = { segmentos: [], total_pontos: 0, distancia_total: 0 };
                        habilitarExport(false);
                        render();
                    }
                })
                .fail(function () {
                    ultimoTrajeto = { segmentos: [], total_pontos: 0, distancia_total: 0 };
                    habilitarExport(false);
                    render();
                    alert('Erro de comunicação ao buscar percurso.');
                });
        }

        function carregarPara(usuarioId, data, dataFim) {
            init();
            document.getElementById('traj-tecnico').value = String(usuarioId);
            if (data) { document.getElementById('traj-data').value = data; }
            document.getElementById('traj-data-fim').value = dataFim || data || document.getElementById('traj-data').value;
            document.getElementById('traj-os').value = '';
            buscar();
        }

        function exportar(formato) {
            if (!ultimoFiltro) { return; }
            var qs = $.param($.extend({}, ultimoFiltro, { formato: formato }));
            window.location = EXPORT + '?' + qs;
        }

        function init() {
            if (mapa) { return; }
            mapa = L.map('mapa-trajeto').setView([-14.235, -51.925], 4);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19, attribution: '&copy; OpenStreetMap'
            }).addTo(mapa);
            camada = L.layerGroup().addTo(mapa);
            camadaOs = L.layerGroup().addTo(mapa);
            camadaHeat = L.layerGroup().addTo(mapa);

            document.getElementById('traj-buscar').addEventListener('click', buscar);
            document.getElementById('traj-gpx').addEventListener('click', function () { exportar('gpx'); });
            document.getElementById('traj-kml').addEventListener('click', function () { exportar('kml'); });
            document.getElementById('traj-rota-plan').addEventListener('change', function () { desenharOsDoDia(); });
            document.getElementById('traj-vias').addEventListener('change', function () { desenharOsDoDia(); });
            document.getElementById('traj-heat').addEventListener('change', desenharHeat);
            document.getElementById('traj-otimizar').addEventListener('click', otimizarRoteiro);
            document.getElementById('traj-replay-btn').addEventListener('click', function () {
                document.getElementById('traj-replay').scrollIntoView({ block: 'nearest' });
                tocarReplay();
            });
            document.getElementById('rp-play').addEventListener('click', tocarReplay);
            document.getElementById('rp-range').addEventListener('input', function () {
                pararReplay();
                posicionarReplay(parseInt(this.value, 10) || 0);
            });
        }

        return {
            init: init,
            carregarPara: carregarPara,
            refresh: function () { if (mapa) { mapa.invalidateSize(); } }
        };
    })();

    /* ===================== TROCA DE ABAS ===================== */
    var botoes = document.querySelectorAll('.loc-tabs button');
    var panes = {
        'tempo-real': document.getElementById('pane-tempo-real'),
        'percurso': document.getElementById('pane-percurso')
    };

    function abrir(tab) {
        botoes.forEach(function (b) { b.classList.toggle('active', b.getAttribute('data-tab') === tab); });
        Object.keys(panes).forEach(function (k) { panes[k].classList.toggle('active', k === tab); });
        if (tab === 'tempo-real') {
            TempoReal.init();
            setTimeout(TempoReal.refresh, 60);
        } else {
            Percurso.init();
            setTimeout(Percurso.refresh, 60);
        }
    }

    botoes.forEach(function (b) {
        b.addEventListener('click', function () { abrir(b.getAttribute('data-tab')); });
    });

    window.locVerPercursoHoje = function (usuarioId) {
        var hoje = new Date();
        var d = hoje.getFullYear() + '-' + ('0' + (hoje.getMonth() + 1)).slice(-2) + '-' + ('0' + hoje.getDate()).slice(-2);
        abrir('percurso');
        setTimeout(function () { Percurso.carregarPara(usuarioId, d, d); }, 80);
        return false;
    };

    TempoReal.init();
    setTimeout(TempoReal.refresh, 200);
})();
</script>
