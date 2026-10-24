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

                        <?php
                        $clienteAtualNome = '';
                        if ($o && $o->clientes_id) {
                            foreach ($clientes as $c) {
                                if ($c->idClientes == $o->clientes_id) {
                                    $clienteAtualNome = $c->nomeCliente . ($c->documento ? ' — ' . $c->documento : '');
                                    break;
                                }
                            }
                        }
                        ?>
                        <div class="span5">
                            <label>Cliente</label>
                            <div style="position:relative">
                                <input type="text" id="cliente_busca" class="span12" autocomplete="off" placeholder="Buscar por nome ou CNPJ/CPF..." value="<?= html_escape($clienteAtualNome) ?>">
                                <input type="hidden" name="clientes_id" id="clientes_id" value="<?= $o ? (int) $o->clientes_id : '' ?>">
                                <a href="#" id="cliente_limpar" title="Limpar" style="position:absolute;right:8px;top:6px;color:#999;<?= $clienteAtualNome ? '' : 'display:none' ?>"><i class="bx bx-x"></i></a>
                            </div>
                            <small style="color:#888">Digite o nome ou o documento do cliente.</small>
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

    // Busca de cliente (nome + CNPJ/CPF) com autocomplete local
    var CLIENTES = <?= json_encode(array_map(function ($c) {
        $doc = $c->documento ? ' — ' . $c->documento : '';
        return ['id' => (int) $c->idClientes, 'label' => $c->nomeCliente . $doc, 'nome' => $c->nomeCliente, 'doc' => (string) $c->documento];
    }, $clientes), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;

    $(function () {
        var $busca = $('#cliente_busca');
        var $id = $('#clientes_id');
        var $limpar = $('#cliente_limpar');

        function limpar() {
            $id.val('');
            $busca.val('');
            $limpar.hide();
        }

        $busca.autocomplete({
            minLength: 0,
            delay: 100,
            source: function (request, response) {
                var t = (request.term || '').toLowerCase().replace(/[.\-\/\s]/g, '');
                var termoNome = (request.term || '').toLowerCase();
                var res = CLIENTES.filter(function (c) {
                    var docLimpo = (c.doc || '').toLowerCase().replace(/[.\-\/\s]/g, '');
                    return c.nome.toLowerCase().indexOf(termoNome) !== -1 || (t !== '' && docLimpo.indexOf(t) !== -1);
                }).slice(0, 20);
                response(res);
            },
            focus: function (event, ui) {
                $busca.val(ui.item.label);
                return false;
            },
            select: function (event, ui) {
                $busca.val(ui.item.label);
                $id.val(ui.item.id);
                $limpar.show();
                return false;
            }
        }).on('focus', function () { $(this).autocomplete('search', $(this).val()); });

        // Se o usuário apagar/alterar o texto sem escolher, invalida a seleção
        $busca.on('input', function () {
            if ($.trim($(this).val()) === '') { limpar(); }
            else { $id.val(''); $limpar.show(); }
        });

        $limpar.on('click', function (e) { e.preventDefault(); limpar(); $busca.focus(); });

        // Garante que só envia com um cliente válido selecionado (ou vazio)
        $('#formObra').on('submit', function () {
            if ($.trim($busca.val()) === '') { $id.val(''); }
        });
    });
</script>
