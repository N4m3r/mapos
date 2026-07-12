<div class="new122">
    <?php $this->load->view('rh/_subnav', ['ativo' => 'unidades']); ?>
    <div class="widget-title" style="margin:0 0 0">
        <span class="icon"><i class="fas fa-building"></i></span><h5>Unidades (geofence)</h5>
    </div>
    <div class="span12" style="margin-left:0">
        <a href="#modal-unidade" role="button" data-toggle="modal" class="button btn btn-mini btn-success" onclick="novaUnidade()">
            <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2"> Unidade</span>
        </a>
    </div>
    <div class="widget-box"><div class="widget-content nopadding">
        <table class="table table-bordered">
            <thead><tr><th>Nome</th><th>Endereço</th><th>Coordenadas</th><th>Raio</th><th>Situação</th><th>Ações</th></tr></thead>
            <tbody>
            <?php if (empty($unidades)): ?>
                <tr><td colspan="6">Nenhuma unidade cadastrada.</td></tr>
            <?php else: foreach ($unidades as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u->nome) ?></td>
                    <td><?= htmlspecialchars($u->endereco ?: '-') ?></td>
                    <td><?php if ($u->latitude): ?>
                            <a href="https://www.google.com/maps?q=<?= $u->latitude ?>,<?= $u->longitude ?>" target="_blank" rel="noopener">
                                <?= $u->latitude ?>, <?= $u->longitude ?> <i class='bx bx-map'></i></a>
                        <?php else: ?><span style="color:#dc2626">sem geofence</span><?php endif; ?></td>
                    <td><?= (int) $u->raio_metros ?>m</td>
                    <td><?= $u->situacao ? 'Ativa' : 'Inativa' ?></td>
                    <td>
                        <a href="#modal-unidade" role="button" data-toggle="modal" class="btn-nwe3" title="Editar"
                           onclick='editarUnidade(<?= json_encode($u) ?>)'><i class="bx bx-edit bx-xs"></i></a>
                        <a href="#modal-excluir-u" role="button" data-toggle="modal" reg="<?= $u->id ?>" class="btn-nwe4" title="Excluir"><i class="bx bx-trash-alt bx-xs"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div></div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">

<!-- Modal unidade (com mapa) -->
<div id="modal-unidade" class="modal hide fade rh-modal-wide" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('rh/salvarUnidade') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5 id="u-titulo">Unidade</h5></div>
        <div class="modal-body">
            <div class="rh-modal-cols">
                <div class="rh-modal-form">
                    <input type="hidden" name="id" id="u-id">
                    <label>Nome *</label><input type="text" name="nome" id="u-nome" class="span12" required>
                    <label>Endereço</label>
                    <div style="display:flex;gap:6px">
                        <input type="text" name="endereco" id="u-endereco" class="span12" style="flex:1" placeholder="Rua, número, cidade...">
                        <button type="button" class="btn" onclick="buscarEndereco()" title="Buscar no mapa"><i class='bx bx-search'></i></button>
                    </div>
                    <div style="display:flex;gap:8px">
                        <div style="flex:1"><label>Latitude</label><input type="text" name="latitude" id="u-lat" class="span12"></div>
                        <div style="flex:1"><label>Longitude</label><input type="text" name="longitude" id="u-lng" class="span12"></div>
                    </div>
                    <label>Raio do geofence (metros)</label>
                    <input type="number" name="raio_metros" id="u-raio" class="span12" value="150" min="20" step="10">
                    <button type="button" class="btn btn-mini" onclick="minhaLoc()" style="margin-top:6px"><i class='bx bx-current-location'></i> Usar minha localização</button>
                    <div style="margin-top:8px"><label>Situação</label>
                        <select name="situacao" id="u-situacao" class="span12"><option value="1">Ativa</option><option value="0">Inativa</option></select>
                    </div>
                </div>
                <div class="rh-modal-map">
                    <div id="u-map" style="height:320px;border-radius:8px;background:#eef0f4"></div>
                    <small style="color:#6b7280">Clique no mapa para marcar o local; arraste o pino para ajustar. O círculo é o raio.</small>
                </div>
            </div>
        </div>
        <div class="modal-footer"><button class="button btn btn-success"><span class="button__icon"><i class='bx bx-save'></i></span><span class="button__text2"> Salvar</span></button></div>
    </form>
</div>

<div id="modal-excluir-u" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('rh/excluirUnidade') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Excluir Unidade</h5></div>
        <div class="modal-body"><input type="hidden" id="u-del-id" name="id"><h5 style="text-align:center">Confirma exclusão?</h5></div>
        <div class="modal-footer"><button class="button btn btn-danger"><span class="button__text2">Excluir</span></button></div>
    </form>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
var uMap, uMarker, uCircle;

function novaUnidade(){
    $('#u-titulo').text('Nova unidade'); $('#u-id').val(''); $('#u-nome').val(''); $('#u-endereco').val('');
    $('#u-lat').val(''); $('#u-lng').val(''); $('#u-raio').val(150); $('#u-situacao').val('1');
}
function editarUnidade(u){
    $('#u-titulo').text('Editar unidade'); $('#u-id').val(u.id); $('#u-nome').val(u.nome);
    $('#u-endereco').val(u.endereco||''); $('#u-lat').val(u.latitude||''); $('#u-lng').val(u.longitude||'');
    $('#u-raio').val(u.raio_metros); $('#u-situacao').val(u.situacao);
}

function garanteMapa(){
    if (uMap) return;
    uMap = L.map('u-map');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '© OpenStreetMap'
    }).addTo(uMap);
    uMap.on('click', function(e){ setPonto(e.latlng.lat, e.latlng.lng); });
}
function setMarker(lat, lng){
    var raio = parseInt($('#u-raio').val() || '150', 10);
    if (uMarker) uMap.removeLayer(uMarker);
    if (uCircle) uMap.removeLayer(uCircle);
    uMarker = L.marker([lat, lng], {draggable:true}).addTo(uMap);
    uMarker.on('dragend', function(){ var p = uMarker.getLatLng(); setPonto(p.lat, p.lng); });
    uCircle = L.circle([lat, lng], {radius: raio, color:'#667eea', fillColor:'#667eea', fillOpacity:0.12}).addTo(uMap);
}
function setPonto(lat, lng){
    $('#u-lat').val(parseFloat(lat).toFixed(7));
    $('#u-lng').val(parseFloat(lng).toFixed(7));
    setMarker(lat, lng);
}
function minhaLoc(){
    if (!navigator.geolocation){ alert('GPS indisponível'); return; }
    navigator.geolocation.getCurrentPosition(function(p){
        if (uMap){ uMap.setView([p.coords.latitude, p.coords.longitude], 17); }
        setPonto(p.coords.latitude, p.coords.longitude);
    }, function(){ alert('Não foi possível obter a localização'); }, {enableHighAccuracy:true});
}
function buscarEndereco(){
    var q = $('#u-endereco').val(); if (!q){ return; }
    fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q), {headers:{'Accept':'application/json'}})
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d && d.length){ var lat=parseFloat(d[0].lat), lng=parseFloat(d[0].lon); uMap.setView([lat,lng],16); setPonto(lat,lng); }
            else { alert('Endereço não encontrado.'); }
        }).catch(function(){ alert('Falha na busca de endereço.'); });
}

$('#u-raio').on('input', function(){ if (uCircle) uCircle.setRadius(parseInt(this.value || '150', 10)); });

// Inicializa/atualiza o mapa a partir dos campos (Leaflet precisa do container visível)
function abrirMapaUnidade(){
    garanteMapa();
    var lat = parseFloat($('#u-lat').val()), lng = parseFloat($('#u-lng').val());
    if (!isNaN(lat) && !isNaN(lng)){ uMap.setView([lat,lng], 16); setMarker(lat,lng); }
    else { uMap.setView([-14.235, -51.925], 4); if (uMarker){uMap.removeLayer(uMarker);uMarker=null;} if (uCircle){uMap.removeLayer(uCircle);uCircle=null;} }
    setTimeout(function(){ uMap.invalidateSize(); }, 150);
}
// Robusto: tanto no evento 'shown' do modal quanto num fallback pós-clique do gatilho
// (funciona mesmo que o tema não dispare 'shown').
$('#modal-unidade').on('shown', abrirMapaUnidade);
$(document).on('click', 'a[href="#modal-unidade"][data-toggle="modal"]', function(){ setTimeout(abrirMapaUnidade, 350); });

$(document).on('click','a[reg]',function(){ $('#u-del-id').val($(this).attr('reg')); });
</script>
