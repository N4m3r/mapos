<?php
$o = isset($result) ? $result : null;
$val = function ($campo, $default = '') use ($o) {
    return html_escape($o && isset($o->$campo) && $o->$campo !== null ? $o->$campo : $default);
};
$dt = function ($campo) use ($o) {
    if ($o && ! empty($o->$campo) && $o->$campo !== '0000-00-00') {
        return date('d/m/Y', strtotime($o->$campo));
    }
    return '';
};
$statusAtual = $o ? $o->status : 'ativo';
$tipoAtual = $o ? $o->tipo : 'mensal';
$slas = isset($slas) ? $slas : [];
?>
<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-content">
                <div class="ui-cabecalho">
                    <div>
                        <h1 class="ui-titulo"><i class="bx bx-file"></i> <?= $o ? 'Editar Contrato' : 'Novo Contrato' ?></h1>
                        <p class="ui-subtitulo">Preencha a identificação, o valor, a vigência e o SLA por prioridade.</p>
                    </div>
                </div>
                <form action="<?= current_url() ?>" method="post" id="formContrato">
                    <div class="ui-secao">
                        <h4>Identificação</h4>
                        <div class="ui-grade">
                            <div class="control-group">
                                <label for="codigo">Código</label>
                                <input type="text" id="codigo" name="codigo" placeholder="Ex.: CT-0001" value="<?= $val('codigo') ?>">
                            </div>
                            <div class="control-group" style="grid-column:span 2">
                                <label for="descricao">Descrição <span class="required">*</span></label>
                                <input type="text" id="descricao" name="descricao" required placeholder="Manutenção CFTV + Rede — Loja Centro" value="<?= $val('descricao') ?>">
                            </div>
                            <div class="control-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <?php foreach (['ativo' => 'Ativo', 'suspenso' => 'Suspenso', 'encerrado' => 'Encerrado'] as $k => $v): ?>
                                        <option value="<?= $k ?>" <?= $statusAtual === $k ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="control-group">
                                <label for="clientes_id">Cliente</label>
                                <select id="clientes_id" name="clientes_id">
                                    <option value="">— Selecione —</option>
                                    <?php foreach ($clientes as $c): ?>
                                        <option value="<?= $c->idClientes ?>" <?= $o && $o->clientes_id == $c->idClientes ? 'selected' : '' ?>><?= html_escape($c->nomeCliente) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="control-group">
                                <label for="tipo">Periodicidade</label>
                                <select id="tipo" name="tipo">
                                    <?php foreach (['mensal' => 'Mensal', 'trimestral' => 'Trimestral', 'semestral' => 'Semestral', 'anual' => 'Anual'] as $k => $v): ?>
                                        <option value="<?= $k ?>" <?= $tipoAtual === $k ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="ui-secao">
                        <h4>Valor e vigência</h4>
                        <div class="ui-grade">
                            <div class="control-group">
                                <label for="valor">Valor (R$)</label>
                                <input type="number" id="valor" step="0.01" min="0" name="valor" value="<?= $val('valor', '0') ?>">
                            </div>
                            <div class="control-group">
                                <label for="dia_vencimento">Dia de vencimento</label>
                                <input type="number" id="dia_vencimento" min="1" max="28" name="dia_vencimento" value="<?= $val('dia_vencimento', '10') ?>">
                            </div>
                            <div class="control-group">
                                <label for="data_inicio">Início da vigência</label>
                                <input type="text" id="data_inicio" class="datepicker" name="data_inicio" value="<?= $dt('data_inicio') ?>">
                            </div>
                            <div class="control-group">
                                <label for="data_fim">Fim da vigência</label>
                                <input type="text" id="data_fim" class="datepicker" name="data_fim" value="<?= $dt('data_fim') ?>">
                            </div>
                            <div class="control-group" style="grid-column:1 / -1">
                                <label for="observacoes">Observações</label>
                                <input type="text" id="observacoes" name="observacoes" value="<?= $val('observacoes') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Matriz de SLA por prioridade -->
                    <div class="ui-secao">
                        <h4><i class='bx bx-time-five'></i> SLA por prioridade <small style="font-weight:normal;color:var(--ui-texto-2)">— prazos em horas corridas a partir da abertura da OS</small></h4>
                        <div class="ui-tabela-rolagem">
                            <table class="table table-bordered" style="max-width:620px">
                                <thead><tr><th>Prioridade</th><th>Resposta (h)</th><th>Solução (h)</th></tr></thead>
                                <tbody>
                                    <?php foreach ($slas as $prioridade => $horas): ?>
                                        <tr>
                                            <td><strong><?= html_escape($prioridade) ?></strong></td>
                                            <td><input type="number" min="0" style="width:90px" aria-label="Resposta em horas: <?= html_escape($prioridade) ?>" name="sla_resposta[<?= html_escape($prioridade) ?>]" value="<?= (int) $horas['resposta_horas'] ?>"></td>
                                            <td><input type="number" min="0" style="width:90px" aria-label="Solução em horas: <?= html_escape($prioridade) ?>" name="sla_solucao[<?= html_escape($prioridade) ?>]" value="<?= (int) $horas['solucao_horas'] ?>"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="ui-rodape-form">
                        <a href="<?= site_url('contratos/gerenciar') ?>" class="button btn btn-mini btn-warning"><span class="button__icon"><i class="bx bx-undo"></i></span><span class="button__text2">Voltar</span></a>
                        <button class="button btn btn-success"><span class="button__icon"><i class='bx bx-save'></i></span><span class="button__text2">Salvar</span></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="<?= base_url() ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>
<script nonce="<?= csp_nonce() ?>">
    $(function () { $('.datepicker').datepicker({ dateFormat: 'dd/mm/yy' }); });
</script>
