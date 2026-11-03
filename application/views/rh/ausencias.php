<?php
$tipos = ['ferias'=>'Férias','folga'=>'Folga','atestado'=>'Atestado','licenca'=>'Licença'];
$rotStatus = ['pendente'=>'Pendente','aprovado'=>'Aprovada','recusado'=>'Recusada'];
$corStatus = ['pendente'=>'alerta','aprovado'=>'sucesso','recusado'=>'perigo'];
$podeAprovar = $this->permission->checkPermission($this->session->userdata('permissao'), 'aprovarRh');
?>
<div class="new122">
    <?php $this->load->view('rh/_subnav', ['ativo' => 'ausencias']); ?>
    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="bx bx-calendar"></i> Folgas, férias e atestados</h1>
            <p class="ui-subtitulo">Afastamentos e ausências dos colaboradores. Por padrão aparecem só as pendentes de análise.</p>
        </div>
    </div>
    <form method="get" action="<?= site_url('rh/ausencias') ?>" class="ui-filtros">
        <div class="ui-campo">
            <label for="filtro-status">Situação</label>
            <select name="status" id="filtro-status" data-ao-mudar="enviar">
                <option value="" <?= $status===''?'selected':'' ?>>Todas</option>
                <option value="pendente" <?= $status==='pendente'?'selected':'' ?>>Pendentes</option>
                <option value="aprovado" <?= $status==='aprovado'?'selected':'' ?>>Aprovadas</option>
                <option value="recusado" <?= $status==='recusado'?'selected':'' ?>>Recusadas</option>
            </select>
        </div>
    </form>
    <div class="widget-box"><div class="widget-content nopadding ui-tabela-rolagem">
        <table class="table table-bordered">
            <thead><tr><th>Colaborador</th><th>Tipo</th><th>Período</th><th>Dias</th><th>Motivo</th><th>Anexo</th><th>Status</th><th>Ações</th></tr></thead>
            <tbody>
            <?php if (empty($ausencias)): ?>
                <tr><td colspan="8"><div class="ui-vazio">
                    <i class='bx bx-calendar-check'></i>
                    <strong><?= $status === 'pendente' ? 'Nenhuma solicitação pendente' : 'Nenhuma solicitação encontrada' ?></strong>
                    <?php if ($status !== ''): ?>
                        <a href="<?= site_url('rh/ausencias?status=') ?>" class="button btn btn-mini btn-warning"><span class="button__text2">Ver todas as solicitações</span></a>
                    <?php endif; ?>
                </div></td></tr>
            <?php else: foreach ($ausencias as $a):
                $periodo = date('d/m/Y', strtotime($a->data_inicio)) . ' a ' . date('d/m/Y', strtotime($a->data_fim));
                $dadosModal = [
                    'id' => (int) $a->id,
                    'colaborador' => $a->nome_colaborador ?: '#' . $a->colaborador_id,
                    'tipo' => $tipos[$a->tipo] ?? $a->tipo,
                    'periodo' => $periodo . ' (' . (int) $a->dias . ' dia' . ((int) $a->dias === 1 ? '' : 's') . ')',
                    'motivo' => (string) $a->motivo,
                    'anexo' => ! empty($a->anexo_base64) ? site_url('rh/anexo/ausencia/' . (int) $a->id) : '',
                ]; ?>
                <tr>
                    <td><?= html_escape($a->nome_colaborador ?: '#'.$a->colaborador_id) ?></td>
                    <td><?= html_escape($tipos[$a->tipo] ?? $a->tipo) ?></td>
                    <td><?= $periodo ?></td>
                    <td><?= (int) $a->dias ?></td>
                    <td><?= html_escape($a->motivo) ?></td>
                    <td><?= ! empty($a->anexo_base64) ? '<a href="'.site_url('rh/anexo/ausencia/'.(int) $a->id).'" target="_blank" rel="noopener">ver anexo</a>' : '-' ?></td>
                    <td><span class="ui-selo <?= $corStatus[$a->status] ?? 'neutro' ?>"><?= html_escape($rotStatus[$a->status] ?? ucfirst($a->status)) ?></span></td>
                    <td><?php if ($a->status==='pendente' && $podeAprovar): ?>
                        <a href="#modal-analise-a" role="button" data-toggle="modal" class="btn btn-mini btn-success" data-chamar="analisarA" data-args="<?= html_escape(json_encode([$dadosModal])) ?>">Analisar</a>
                    <?php else: echo $a->resposta ? html_escape($a->resposta) : '-'; endif; ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div></div>
</div>

<?php if ($podeAprovar): ?>
<div id="modal-analise-a" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('rh/analisarAusencia') ?>" method="post" id="form-analise-a">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Fechar">×</button><h5>Analisar solicitação</h5></div>
        <div class="modal-body">
            <input type="hidden" name="id" id="ana-id">
            <div class="rh-resumo-solicitacao">
                <div><span>Colaborador</span><strong id="ana-colab"></strong></div>
                <div><span>Tipo</span><strong id="ana-tipo"></strong></div>
                <div><span>Período</span><strong id="ana-periodo"></strong></div>
                <div><span>Motivo</span><strong id="ana-motivo" class="rh-texto-livre"></strong></div>
                <div id="ana-anexo-box"><span>Anexo</span><a id="ana-anexo" href="#" target="_blank" rel="noopener">Abrir anexo</a></div>
            </div>
            <label for="ana-resposta">Resposta ao colaborador <small>(obrigatória para recusar)</small></label>
            <textarea name="resposta" id="ana-resposta" rows="2" class="span12"></textarea>
            <small id="ana-erro" class="rh-erro-campo" hidden>Informe a justificativa para recusar.</small>
            <input type="hidden" name="status" id="ana-status">
        </div>
        <div class="modal-footer" style="display:flex;justify-content:center;gap:8px">
            <button class="button btn btn-danger" data-definir="#ana-status" data-valor="recusado"><span class="button__text2">Recusar</span></button>
            <button class="button btn btn-success" data-definir="#ana-status" data-valor="aprovado"><span class="button__text2">Aprovar</span></button>
        </div>
    </form>
</div>
<script nonce="<?= csp_nonce() ?>">
function analisarA(a){
    $('#ana-id').val(a.id); $('#ana-resposta').val(''); $('#ana-erro').prop('hidden', true);
    $('#ana-colab').text(a.colaborador); $('#ana-tipo').text(a.tipo); $('#ana-periodo').text(a.periodo);
    $('#ana-motivo').text(a.motivo || '-');
    $('#ana-anexo').attr('href', a.anexo || '#'); $('#ana-anexo-box').toggle(!!a.anexo);
}
$('#form-analise-a').on('submit', function(e){
    if ($('#ana-status').val() === 'recusado' && $.trim($('#ana-resposta').val()) === '') {
        e.preventDefault(); e.stopImmediatePropagation();
        $('#ana-erro').prop('hidden', false); $('#ana-resposta').focus();
    }
});
</script>
<?php endif; ?>
