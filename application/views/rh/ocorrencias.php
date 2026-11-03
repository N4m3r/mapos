<?php
$tipos = ['correcao_ponto'=>'Correção de ponto','justificativa_falta'=>'Justificativa de falta','abono'=>'Abono'];
$tiposBatida = ['entrada'=>'Entrada','saida'=>'Saída','inicio_intervalo'=>'Início do intervalo','fim_intervalo'=>'Fim do intervalo'];
$rotStatus = ['pendente'=>'Pendente','aprovado'=>'Aprovada','recusado'=>'Recusada'];
$corStatus = ['pendente'=>'alerta','aprovado'=>'sucesso','recusado'=>'perigo'];
$podeAprovar = $this->permission->checkPermission($this->session->userdata('permissao'), 'aprovarRh');
?>
<div class="new122">
    <?php $this->load->view('rh/_subnav', ['ativo' => 'ocorrencias']); ?>
    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="bx bx-error-circle"></i> Ocorrências</h1>
            <p class="ui-subtitulo">Correções e justificativas de ponto. Por padrão aparecem só as pendentes de análise.</p>
        </div>
    </div>
    <form method="get" action="<?= site_url('rh/ocorrencias') ?>" class="ui-filtros">
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
            <thead><tr><th>Colaborador</th><th>Tipo</th><th>Referência</th><th>Descrição</th><th>Anexo</th><th>Status</th><th>Ações</th></tr></thead>
            <tbody>
            <?php if (empty($ocorrencias)): ?>
                <tr><td colspan="7"><div class="ui-vazio">
                    <i class='bx bx-check-circle'></i>
                    <strong><?= $status === 'pendente' ? 'Nenhuma ocorrência pendente' : 'Nenhuma ocorrência encontrada' ?></strong>
                    <?php if ($status !== ''): ?>
                        <a href="<?= site_url('rh/ocorrencias?status=') ?>" class="button btn btn-mini btn-warning"><span class="button__text2">Ver todas as ocorrências</span></a>
                    <?php endif; ?>
                </div></td></tr>
            <?php else: foreach ($ocorrencias as $o):
                $dadosModal = [
                    'id' => (int) $o->id,
                    'colaborador' => $o->nome_colaborador ?: '#' . $o->colaborador_id,
                    'tipo' => $tipos[$o->tipo] ?? $o->tipo,
                    'quando' => $o->data_referencia ? date('d/m/Y', strtotime($o->data_referencia)) : '-',
                    'descricao' => (string) $o->descricao,
                    'correcao' => (! empty($o->correcao_tipo) && ! empty($o->correcao_data_hora))
                        ? ($tiposBatida[$o->correcao_tipo] ?? $o->correcao_tipo) . ' em ' . date('d/m/Y H:i', strtotime($o->correcao_data_hora)) : '',
                    'anexo' => ! empty($o->anexo_base64) ? site_url('rh/anexo/ocorrencia/' . (int) $o->id) : '',
                ]; ?>
                <tr>
                    <td><?= html_escape($o->nome_colaborador ?: '#'.$o->colaborador_id) ?></td>
                    <td><?= html_escape($tipos[$o->tipo] ?? $o->tipo) ?></td>
                    <td><?= $o->data_referencia ? date('d/m/Y', strtotime($o->data_referencia)) : '-' ?></td>
                    <td><?= html_escape($o->descricao) ?></td>
                    <td><?= ! empty($o->anexo_base64) ? '<a href="'.site_url('rh/anexo/ocorrencia/'.(int) $o->id).'" target="_blank" rel="noopener">ver anexo</a>' : '-' ?></td>
                    <td><span class="ui-selo <?= $corStatus[$o->status] ?? 'neutro' ?>"><?= html_escape($rotStatus[$o->status] ?? ucfirst($o->status)) ?></span></td>
                    <td><?php if ($o->status==='pendente' && $podeAprovar): ?>
                        <a href="#modal-analise" role="button" data-toggle="modal" class="btn btn-mini btn-success" data-chamar="analisar" data-args="<?= html_escape(json_encode([$dadosModal])) ?>">Analisar</a>
                    <?php else: echo $o->resposta ? html_escape($o->resposta) : '-'; endif; ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div></div>
</div>

<?php if ($podeAprovar): ?>
<div id="modal-analise" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('rh/analisarOcorrencia') ?>" method="post" id="form-analise">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Fechar">×</button><h5>Analisar ocorrência</h5></div>
        <div class="modal-body">
            <input type="hidden" name="id" id="an-id">
            <div class="rh-resumo-solicitacao">
                <div><span>Colaborador</span><strong id="an-colab"></strong></div>
                <div><span>Tipo</span><strong id="an-tipo"></strong></div>
                <div><span>Data de referência</span><strong id="an-quando"></strong></div>
                <div id="an-correcao-box"><span>Correção pedida</span><strong id="an-correcao"></strong></div>
                <div><span>Descrição</span><strong id="an-descricao" class="rh-texto-livre"></strong></div>
                <div id="an-anexo-box"><span>Anexo</span><a id="an-anexo" href="#" target="_blank" rel="noopener">Abrir anexo</a></div>
            </div>
            <label for="an-resposta">Resposta ao colaborador <small>(obrigatória para recusar)</small></label>
            <textarea name="resposta" id="an-resposta" rows="2" class="span12"></textarea>
            <small id="an-erro" class="rh-erro-campo" hidden>Informe a justificativa para recusar.</small>
            <input type="hidden" name="status" id="an-status">
        </div>
        <div class="modal-footer" style="display:flex;justify-content:center;gap:8px">
            <button class="button btn btn-danger" data-definir="#an-status" data-valor="recusado"><span class="button__text2">Recusar</span></button>
            <button class="button btn btn-success" data-definir="#an-status" data-valor="aprovado"><span class="button__text2">Aprovar</span></button>
        </div>
    </form>
</div>
<script nonce="<?= csp_nonce() ?>">
function analisar(o){
    $('#an-id').val(o.id); $('#an-resposta').val(''); $('#an-erro').prop('hidden', true);
    $('#an-colab').text(o.colaborador); $('#an-tipo').text(o.tipo); $('#an-quando').text(o.quando);
    $('#an-descricao').text(o.descricao || '-');
    $('#an-correcao').text(o.correcao); $('#an-correcao-box').toggle(!!o.correcao);
    $('#an-anexo').attr('href', o.anexo || '#'); $('#an-anexo-box').toggle(!!o.anexo);
}
$('#form-analise').on('submit', function(e){
    if ($('#an-status').val() === 'recusado' && $.trim($('#an-resposta').val()) === '') {
        e.preventDefault(); e.stopImmediatePropagation();
        $('#an-erro').prop('hidden', false); $('#an-resposta').focus();
    }
});
</script>
<?php endif; ?>
