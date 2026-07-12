<?php
$this->load->view('rh/_subnav', ['ativo' => 'colaboradores']);
$lblTipo = ['entrada'=>'Entrada','saida'=>'Saída','inicio_intervalo'=>'Início intervalo','fim_intervalo'=>'Fim intervalo'];
$diasSemana = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
?>
<div class="new122">
    <div class="widget-title" style="margin:0 0 10px">
        <span class="icon"><i class="fas fa-edit"></i></span>
        <h5>Ajustar ponto — <?= htmlspecialchars($colaborador->nome) ?></h5>
    </div>

    <div class="span12" style="margin-left:0;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <input type="month" value="<?= $competencia ?>" onchange="window.location='<?= site_url('rh/ajustarPonto/'.$colaborador->id) ?>/'+this.value">
        <a href="#modal-batida" data-toggle="modal" role="button" class="button btn btn-mini btn-success" onclick="novaBatida()">
            <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2"> Batida manual</span>
        </a>
    </div>

    <div class="widget-box"><div class="widget-content nopadding">
        <table class="table table-bordered">
            <thead><tr><th>Data</th><th>Hora</th><th>Tipo</th><th>Origem</th><th>Situação</th><th>Local</th><th>Ações</th></tr></thead>
            <tbody>
            <?php if (empty($registros)): ?>
                <tr><td colspan="7">Nenhuma batida nesta competência.</td></tr>
            <?php else: foreach ($registros as $r):
                $ts = strtotime($r->data_hora);
                $dtLocal = date('Y-m-d\TH:i', $ts); ?>
                <tr class="<?= $r->status === 'rejeitado' ? 'text-error' : '' ?>">
                    <td><?= date('d/m', $ts) ?> <small><?= $diasSemana[(int)date('w',$ts)] ?></small></td>
                    <td><strong><?= date('H:i', $ts) ?></strong></td>
                    <td><?= $lblTipo[$r->tipo] ?? $r->tipo ?></td>
                    <td><?= $r->origem === 'manual' ? '<span style="color:#d97706">Manual</span>' : ($r->origem === 'whatsapp' ? 'WhatsApp' : 'App') ?></td>
                    <td><?= $r->status === 'ajustado' ? '<span style="color:#d97706">Ajustado</span>' : ($r->status === 'rejeitado' ? '<span style="color:#dc2626">Rejeitado</span>' : 'Válido') ?></td>
                    <td><?php if ($r->dentro_geofence === '1') echo '<span style="color:#16a34a">Na área</span>';
                        elseif ($r->dentro_geofence === '0') echo '<span style="color:#dc2626">Fora ('.(int)$r->distancia_metros.'m)</span>';
                        else echo '—'; ?>
                        <?php if (! empty($r->latitude) && ! empty($r->longitude)): ?>
                            <a href="https://www.google.com/maps?q=<?= $r->latitude ?>,<?= $r->longitude ?>" target="_blank" rel="noopener" title="Ver no mapa"><i class='bx bx-map-pin'></i> mapa</a>
                        <?php endif; ?>
                        <?php if (! empty($r->os_id)): ?><br><small><i class='bx bx-wrench'></i> OS #<?= sprintf('%04d', $r->os_id) ?></small><?php endif; ?>
                        <?php if ($r->face_score !== null): ?><br><small>facial <?= number_format($r->face_score,2) ?></small><?php endif; ?>
                    </td>
                    <td style="white-space:nowrap">
                        <a href="#modal-batida" data-toggle="modal" role="button" class="btn-nwe3" title="Editar"
                           onclick='editarBatida(<?= json_encode(["id"=>$r->id,"dt"=>$dtLocal,"tipo"=>$r->tipo,"status"=>$r->status]) ?>)'><i class="bx bx-edit bx-xs"></i></a>
                        <a href="#modal-excluir-b" data-toggle="modal" role="button" reg="<?= $r->id ?>" class="btn-nwe4" title="Excluir"><i class="bx bx-trash-alt bx-xs"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div></div>
    <a href="<?= site_url('rh/ficha/'.$colaborador->id) ?>" class="button btn btn-warning"><span class="button__text2">Voltar à ficha</span></a>
    <a href="<?= site_url('rh/espelho/'.$colaborador->id.'/'.$competencia) ?>" class="button btn btn-inverse"><span class="button__text2">Ver espelho</span></a>
</div>

<!-- Modal criar/editar batida -->
<div id="modal-batida" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form id="form-batida" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5 id="b-titulo">Batida</h5></div>
        <div class="modal-body">
            <input type="hidden" name="id" id="b-id">
            <input type="hidden" name="colaborador_id" value="<?= $colaborador->id ?>">
            <input type="hidden" name="competencia" value="<?= $competencia ?>">
            <label>Data e hora</label><input type="datetime-local" name="data_hora" id="b-dt" class="span12" required>
            <label>Tipo</label>
            <select name="tipo" id="b-tipo" class="span12">
                <option value="entrada">Entrada</option><option value="inicio_intervalo">Início do intervalo</option>
                <option value="fim_intervalo">Fim do intervalo</option><option value="saida">Saída</option>
            </select>
            <div id="b-status-wrap" style="display:none">
                <label>Situação</label>
                <select name="status" id="b-status" class="span12">
                    <option value="ajustado">Ajustado</option><option value="valido">Válido</option><option value="rejeitado">Rejeitado (desconsiderar)</option>
                </select>
            </div>
        </div>
        <div class="modal-footer"><button class="button btn btn-success"><span class="button__text2">Salvar</span></button></div>
    </form>
</div>

<div id="modal-excluir-b" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('rh/excluirBatida') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Excluir batida</h5></div>
        <div class="modal-body">
            <input type="hidden" id="b-del-id" name="id">
            <input type="hidden" name="colaborador_id" value="<?= $colaborador->id ?>">
            <input type="hidden" name="competencia" value="<?= $competencia ?>">
            <h5 style="text-align:center">Confirma a exclusão desta batida?</h5>
        </div>
        <div class="modal-footer"><button class="button btn btn-danger"><span class="button__text2">Excluir</span></button></div>
    </form>
</div>
<script>
var URL_ADD = '<?= site_url('rh/registrarPontoManual') ?>';
var URL_EDIT = '<?= site_url('rh/editarBatida') ?>';
function novaBatida(){
    document.getElementById('b-titulo').textContent = 'Nova batida manual';
    document.getElementById('form-batida').action = URL_ADD;
    document.getElementById('b-id').value = '';
    document.getElementById('b-dt').value = '';
    document.getElementById('b-tipo').value = 'entrada';
    document.getElementById('b-status-wrap').style.display = 'none';
}
function editarBatida(b){
    document.getElementById('b-titulo').textContent = 'Editar batida';
    document.getElementById('form-batida').action = URL_EDIT;
    document.getElementById('b-id').value = b.id;
    document.getElementById('b-dt').value = b.dt;
    document.getElementById('b-tipo').value = b.tipo;
    document.getElementById('b-status').value = (b.status === 'valido' || b.status === 'rejeitado') ? b.status : 'ajustado';
    document.getElementById('b-status-wrap').style.display = 'block';
}
$(document).on('click','a[reg]',function(){ $('#b-del-id').val($(this).attr('reg')); });
</script>
