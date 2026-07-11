<div class="new122">
    <div class="widget-title" style="margin:-20px 0 0">
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
                    <td><?= $u->latitude ? $u->latitude.', '.$u->longitude : '<span style="color:#dc2626">sem geofence</span>' ?></td>
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

<div id="modal-unidade" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('rh/salvarUnidade') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5 id="u-titulo">Unidade</h5></div>
        <div class="modal-body">
            <input type="hidden" name="id" id="u-id">
            <label>Nome</label><input type="text" name="nome" id="u-nome" class="span12" required>
            <label>Endereço</label><input type="text" name="endereco" id="u-endereco" class="span12">
            <div style="display:flex;gap:8px">
                <div style="flex:1"><label>Latitude</label><input type="text" name="latitude" id="u-lat" class="span12"></div>
                <div style="flex:1"><label>Longitude</label><input type="text" name="longitude" id="u-lng" class="span12"></div>
                <div style="width:90px"><label>Raio (m)</label><input type="number" name="raio_metros" id="u-raio" class="span12" value="150"></div>
            </div>
            <button type="button" class="btn btn-mini" onclick="minhaLoc()" style="margin-top:8px"><i class='bx bx-map'></i> Usar minha localização atual</button>
            <div style="margin-top:8px"><label>Situação</label>
                <select name="situacao" id="u-situacao" class="span12"><option value="1">Ativa</option><option value="0">Inativa</option></select>
            </div>
        </div>
        <div class="modal-footer"><button class="button btn btn-success"><span class="button__text2">Salvar</span></button></div>
    </form>
</div>

<div id="modal-excluir-u" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('rh/excluirUnidade') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Excluir Unidade</h5></div>
        <div class="modal-body"><input type="hidden" id="u-del-id" name="id"><h5 style="text-align:center">Confirma exclusão?</h5></div>
        <div class="modal-footer"><button class="button btn btn-danger"><span class="button__text2">Excluir</span></button></div>
    </form>
</div>
<script>
function novaUnidade(){ $('#u-titulo').text('Nova unidade'); $('#u-id').val(''); $('#u-nome').val(''); $('#u-endereco').val(''); $('#u-lat').val(''); $('#u-lng').val(''); $('#u-raio').val(150); $('#u-situacao').val('1'); }
function editarUnidade(u){ $('#u-titulo').text('Editar unidade'); $('#u-id').val(u.id); $('#u-nome').val(u.nome); $('#u-endereco').val(u.endereco||''); $('#u-lat').val(u.latitude||''); $('#u-lng').val(u.longitude||''); $('#u-raio').val(u.raio_metros); $('#u-situacao').val(u.situacao); }
function minhaLoc(){ if(!navigator.geolocation){ alert('GPS indisponível'); return; } navigator.geolocation.getCurrentPosition(function(p){ $('#u-lat').val(p.coords.latitude.toFixed(7)); $('#u-lng').val(p.coords.longitude.toFixed(7)); }, function(){ alert('Não foi possível obter a localização'); }, {enableHighAccuracy:true}); }
$(document).on('click','a[reg]',function(){ $('#u-del-id').val($(this).attr('reg')); });
</script>
