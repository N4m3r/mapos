<?php
$tipos = ['correcao_ponto'=>'Correção de ponto','justificativa_falta'=>'Justificativa de falta','abono'=>'Abono'];
$podeAprovar = $this->permission->checkPermission($this->session->userdata('permissao'), 'aprovarRh');
?>
<div class="new122">
    <div class="widget-title" style="margin:-20px 0 10px">
        <span class="icon"><i class="fas fa-exclamation-circle"></i></span><h5>Ocorrências (correções / justificativas)</h5>
    </div>
    <div class="span12" style="margin-left:0">
        <form method="get" action="<?= site_url('rh/ocorrencias') ?>">
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
            <thead><tr><th>Colaborador</th><th>Tipo</th><th>Referência</th><th>Descrição</th><th>Anexo</th><th>Status</th><th>Ações</th></tr></thead>
            <tbody>
            <?php if (empty($ocorrencias)): ?>
                <tr><td colspan="7">Nenhuma ocorrência.</td></tr>
            <?php else: foreach ($ocorrencias as $o): ?>
                <tr>
                    <td><?= htmlspecialchars($o->nome_colaborador ?: '#'.$o->colaborador_id) ?></td>
                    <td><?= $tipos[$o->tipo] ?? $o->tipo ?></td>
                    <td><?= $o->data_referencia ? date('d/m/Y', strtotime($o->data_referencia)) : '-' ?></td>
                    <td><?= htmlspecialchars($o->descricao) ?></td>
                    <td><?= ! empty($o->anexo_base64) ? '<a href="'.site_url('rh/anexo/ocorrencia/'.$o->id).'" target="_blank">ver</a>' : '-' ?></td>
                    <td><span class="rh-badge <?= $o->status ?>"><?= ucfirst($o->status) ?></span></td>
                    <td><?php if ($o->status==='pendente' && $podeAprovar): ?>
                        <a href="#modal-analise" role="button" data-toggle="modal" class="btn btn-mini" onclick='analisar(<?= $o->id ?>)'>Analisar</a>
                    <?php else: echo $o->resposta ? htmlspecialchars($o->resposta) : '-'; endif; ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div></div>
</div>

<?php if ($podeAprovar): ?>
<div id="modal-analise" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('rh/analisarOcorrencia') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Analisar ocorrência</h5></div>
        <div class="modal-body">
            <input type="hidden" name="id" id="an-id">
            <label>Resposta ao colaborador</label>
            <textarea name="resposta" rows="2" class="span12"></textarea>
            <input type="hidden" name="status" id="an-status">
        </div>
        <div class="modal-footer" style="display:flex;justify-content:center;gap:8px">
            <button class="button btn btn-danger" onclick="$('#an-status').val('recusado')"><span class="button__text2">Recusar</span></button>
            <button class="button btn btn-success" onclick="$('#an-status').val('aprovado')"><span class="button__text2">Aprovar</span></button>
        </div>
    </form>
</div>
<script>function analisar(id){ $('#an-id').val(id); }</script>
<?php endif; ?>
