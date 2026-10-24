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
            <div class="widget-title">
                <span class="icon"><i class="fas fa-file-signature"></i></span>
                <h5><?= $o ? 'Editar Contrato' : 'Novo Contrato' ?></h5>
            </div>
            <div class="widget-content">
                <form action="<?= current_url() ?>" method="post" id="formContrato">
                    <div class="span12">
                        <div class="span3">
                            <label>Código</label>
                            <input type="text" class="span12" name="codigo" placeholder="Ex.: CT-0001" value="<?= $val('codigo') ?>">
                        </div>
                        <div class="span6">
                            <label>Descrição <span class="required">*</span></label>
                            <input type="text" class="span12" name="descricao" required placeholder="Manutenção CFTV + Rede — Loja Centro" value="<?= $val('descricao') ?>">
                        </div>
                        <div class="span3">
                            <label>Status</label>
                            <select name="status" class="span12">
                                <?php foreach (['ativo' => 'Ativo', 'suspenso' => 'Suspenso', 'encerrado' => 'Encerrado'] as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= $statusAtual === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="span5">
                            <label>Cliente</label>
                            <select name="clientes_id" class="span12">
                                <option value="">— Selecione —</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= $c->idClientes ?>" <?= $o && $o->clientes_id == $c->idClientes ? 'selected' : '' ?>><?= html_escape($c->nomeCliente) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="span3">
                            <label>Periodicidade</label>
                            <select name="tipo" class="span12">
                                <?php foreach (['mensal' => 'Mensal', 'trimestral' => 'Trimestral', 'semestral' => 'Semestral', 'anual' => 'Anual'] as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= $tipoAtual === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="span2">
                            <label>Valor (R$)</label>
                            <input type="number" step="0.01" min="0" class="span12" name="valor" value="<?= $val('valor', '0') ?>">
                        </div>
                        <div class="span2">
                            <label>Dia venc.</label>
                            <input type="number" min="1" max="28" class="span12" name="dia_vencimento" value="<?= $val('dia_vencimento', '10') ?>">
                        </div>
                    </div>

                    <div class="span12">
                        <div class="span3"><label>Início da vigência</label><input type="text" class="span12 datepicker" name="data_inicio" value="<?= $dt('data_inicio') ?>"></div>
                        <div class="span3"><label>Fim da vigência</label><input type="text" class="span12 datepicker" name="data_fim" value="<?= $dt('data_fim') ?>"></div>
                        <div class="span6"><label>Observações</label><input type="text" class="span12" name="observacoes" value="<?= $val('observacoes') ?>"></div>
                    </div>

                    <!-- Matriz de SLA por prioridade -->
                    <div class="span12" style="margin-top:8px">
                        <h5 style="border-bottom:1px solid #eee;padding-bottom:6px">
                            <i class='bx bx-time-five'></i> SLA por prioridade
                            <small style="font-weight:normal;color:#888">— prazos em horas corridas a partir da abertura da OS</small>
                        </h5>
                        <table class="table table-bordered" style="max-width:620px">
                            <thead><tr><th>Prioridade</th><th>Resposta (h)</th><th>Solução (h)</th></tr></thead>
                            <tbody>
                                <?php foreach ($slas as $prioridade => $horas): ?>
                                    <tr>
                                        <td><strong><?= html_escape($prioridade) ?></strong></td>
                                        <td><input type="number" min="0" style="width:90px" name="sla_resposta[<?= html_escape($prioridade) ?>]" value="<?= (int) $horas['resposta_horas'] ?>"></td>
                                        <td><input type="number" min="0" style="width:90px" name="sla_solucao[<?= html_escape($prioridade) ?>]" value="<?= (int) $horas['solucao_horas'] ?>"></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="span12" style="padding:1%; margin-left:0">
                        <div class="span6 offset3" style="display:flex;justify-content:center">
                            <button class="button btn btn-success"><span class="button__icon"><i class='bx bx-save'></i></span><span class="button__text2">Salvar</span></button>
                            <a href="<?= site_url('contratos/gerenciar') ?>" class="button btn btn-mini btn-warning"><span class="button__icon"><i class="bx bx-undo"></i></span><span class="button__text2">Voltar</span></a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="<?= base_url() ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script src="<?= base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>
<script>
    $(function () { $('.datepicker').datepicker({ dateFormat: 'dd/mm/yy' }); });
</script>
