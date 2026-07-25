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
$statusAtual = $o ? $o->status : 'planejamento';
?>
<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon"><i class="fas fa-network-wired"></i></span>
                <h5><?= $o ? 'Editar Projeto' : 'Novo Projeto' ?></h5>
            </div>
            <div class="widget-content">
                <form action="<?= current_url() ?>" method="post" id="formObra">
                    <div class="span12">
                        <div class="span3">
                            <label>Código</label>
                            <input type="text" class="span12" name="codigo" value="<?= $val('codigo') ?>">
                        </div>
                        <div class="span6">
                            <label>Nome do projeto <span class="required">*</span></label>
                            <input type="text" class="span12" name="nome" required value="<?= $val('nome') ?>">
                        </div>
                        <div class="span3">
                            <label>Tipo</label>
                            <input type="text" class="span12" name="tipo_obra" list="tipos_projeto" placeholder="Rede estruturada, CFTV IP..." value="<?= $val('tipo_obra') ?>">
                            <datalist id="tipos_projeto">
                                <option value="Rede estruturada">
                                <option value="CFTV IP">
                                <option value="Rede estruturada + CFTV IP">
                                <option value="Cabeamento óptico / fibra">
                                <option value="Controle de acesso">
                                <option value="Manutenção">
                            </datalist>
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
                        <div class="span4">
                            <label>Responsável técnico</label>
                            <select name="responsavel_id" class="span12">
                                <option value="">— Selecione —</option>
                                <?php foreach ($usuarios as $u): ?>
                                    <option value="<?= $u->idUsuarios ?>" <?= $o && $o->responsavel_id == $u->idUsuarios ? 'selected' : '' ?>><?= html_escape($u->nome) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="span3">
                            <label>Status</label>
                            <select name="status" class="span12">
                                <?php foreach (['planejamento' => 'Planejamento', 'em_execucao' => 'Em execução', 'paralisada' => 'Paralisada', 'concluida' => 'Concluída', 'entregue' => 'Entregue', 'cancelada' => 'Cancelada'] as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= $statusAtual === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="span4">
                            <label>Nº do contrato</label>
                            <input type="text" class="span12" name="contrato_numero" value="<?= $val('contrato_numero') ?>">
                        </div>
                        <div class="span4">
                            <label>Valor do contrato (R$)</label>
                            <input type="number" step="0.01" class="span12" name="valor_contrato" value="<?= $val('valor_contrato', '0') ?>">
                        </div>
                        <div class="span4">
                            <label>BDI (%)</label>
                            <input type="number" step="0.01" class="span12" name="bdi_percentual" value="<?= $val('bdi_percentual', '0') ?>">
                        </div>
                    </div>

                    <div class="span12">
                        <h5 style="margin-top:10px">Endereço do projeto</h5>
                        <div class="span2"><label>CEP</label><input type="text" class="span12" name="cep" value="<?= $val('cep') ?>"></div>
                        <div class="span6"><label>Logradouro</label><input type="text" class="span12" name="logradouro" value="<?= $val('logradouro') ?>"></div>
                        <div class="span2"><label>Número</label><input type="text" class="span12" name="numero" value="<?= $val('numero') ?>"></div>
                        <div class="span2"><label>Complemento</label><input type="text" class="span12" name="complemento" value="<?= $val('complemento') ?>"></div>
                        <div class="span4"><label>Bairro</label><input type="text" class="span12" name="bairro" value="<?= $val('bairro') ?>"></div>
                        <div class="span5"><label>Cidade</label><input type="text" class="span12" name="cidade" value="<?= $val('cidade') ?>"></div>
                        <div class="span3"><label>UF</label><input type="text" class="span12" name="uf" maxlength="2" value="<?= $val('uf') ?>"></div>
                    </div>

                    <div class="span12">
                        <h5 style="margin-top:10px">Prazos</h5>
                        <div class="span3"><label>Início previsto</label><input type="text" class="span12 datepicker" name="data_inicio_prevista" value="<?= $dt('data_inicio_prevista') ?>"></div>
                        <div class="span3"><label>Fim previsto</label><input type="text" class="span12 datepicker" name="data_fim_prevista" value="<?= $dt('data_fim_prevista') ?>"></div>
                        <div class="span3"><label>Início real</label><input type="text" class="span12 datepicker" name="data_inicio_real" value="<?= $dt('data_inicio_real') ?>"></div>
                        <div class="span3"><label>Fim real</label><input type="text" class="span12 datepicker" name="data_fim_real" value="<?= $dt('data_fim_real') ?>"></div>
                    </div>

                    <div class="span12">
                        <label>Observações</label>
                        <textarea class="span12" name="observacoes" rows="3"><?= $val('observacoes') ?></textarea>
                    </div>

                    <div class="span12" style="padding:1%; margin-left:0">
                        <div class="span6 offset3" style="display:flex;justify-content:center">
                            <button class="button btn btn-success"><span class="button__icon"><i class='bx bx-save'></i></span><span class="button__text2">Salvar</span></button>
                            <a href="<?= site_url('obras') ?>" class="button btn btn-mini btn-warning"><span class="button__icon"><i class="bx bx-undo"></i></span><span class="button__text2">Voltar</span></a>
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
