<?php
$tipos = ['ferias'=>'Férias','folga'=>'Folga','atestado'=>'Atestado','licenca'=>'Licença'];
$podeAprovar = $this->permission->checkPermission($this->session->userdata('permissao'), 'aprovarRh');
?>
<div class="new122">
    <?php $this->load->view('rh/_subnav', ['ativo' => 'ausencias']); ?>
    <div class="widget-title" style="margin:0 0 10px">
        <span class="icon"><i class="fas fa-calendar-alt"></i></span><h5>Folgas / Férias / Atestados</h5>
    </div>
    <div class="span12" style="margin-left:0">
        <form method="get" action="<?= site_url('rh/ausencias') ?>">
            <select name="status" onchange="this.form.submit()">
                <option value="">Todas</option>
                <option value="pendente" <?= $status==='pendente'?'selected':'' ?>>Pendentes</option>
                <option value="aprovado" <?= $status==='aprovado'?'selected':'' ?>>Aprovadas</option>
                <option value="recusado" <?= $status==='recusado'?'selected':'' ?>>Recusadas</option>
            </select>
        </form>
    </div>
    <div class="widget-box"><div class="widget-content nopadding">
        <table class="table table-bordered">
            <thead><tr><th>Colaborador</th><th>Tipo</th><th>Período</th><th>Dias</th><th>Motivo</th><th>Anexo</th><th>Status</th><th>Ações</th></tr></thead>
            <tbody>
            <?php if (empty($ausencias)): ?>
                <tr><td colspan="8">Nenhuma solicitação.</td></tr>
            <?php else: foreach ($ausencias as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a->nome_colaborador ?: '#'.$a->colaborador_id) ?></td>
                    <td><?= $tipos[$a->tipo] ?? $a->tipo ?></td>
                    <td><?= date('d/m/Y', strtotime($a->data_inicio)) ?> a <?= date('d/m/Y', strtotime($a->data_fim)) ?></td>
                    <td><?= (int) $a->dias ?></td>
                    <td><?= htmlspecialchars($a->motivo) ?></td>
                    <td><?= ! empty($a->anexo_base64) ? '<a href="'.site_url('rh/anexo/ausencia/'.$a->id).'" target="_blank">ver</a>' : '-' ?></td>
                    <td><span class="rh-badge <?= $a->status ?>"><?= ucfirst($a->status) ?></span></td>
                    <td><?php if ($a->status==='pendente' && $podeAprovar): ?>
                        <a href="#modal-analise-a" role="button" data-toggle="modal" class="btn btn-mini" onclick='analisarA(<?= $a->id ?>)'>Analisar</a>
                    <?php else: echo $a->resposta ? htmlspecialchars($a->resposta) : '-'; endif; ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div></div>
</div>

<?php if ($podeAprovar): ?>
<div id="modal-analise-a" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('rh/analisarAusencia') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Analisar solicitação</h5></div>
        <div class="modal-body">
            <input type="hidden" name="id" id="ana-id">
            <label>Resposta ao colaborador</label>
            <textarea name="resposta" rows="2" class="span12"></textarea>
            <input type="hidden" name="status" id="ana-status">
        </div>
        <div class="modal-footer" style="display:flex;justify-content:center;gap:8px">
            <button class="button btn btn-danger" onclick="$('#ana-status').val('recusado')"><span class="button__text2">Recusar</span></button>
            <button class="button btn btn-success" onclick="$('#ana-status').val('aprovado')"><span class="button__text2">Aprovar</span></button>
        </div>
    </form>
</div>
<script>function analisarA(id){ $('#ana-id').val(id); }</script>
<?php endif; ?>
