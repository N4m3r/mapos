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
<link rel="stylesheet" href="<?= base_url('assets/css/projeto-assistente.css') ?>?v=<?= asset_versao('assets/css/projeto-assistente.css') ?>">
<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-content pa">
                <div class="ui-cabecalho">
                    <div>
                        <h1 class="ui-titulo"><i class="bx bx-building-house"></i> Editar projeto</h1>
                        <p class="ui-subtitulo">Ajuste o que mudou. Os dados menos usados ficam em "Mais detalhes".</p>
                    </div>
                </div>
                <form action="<?= current_url() ?>" method="post" id="formObra">
                    <div class="ui-secao">
                        <h4>Projeto e cliente</h4>
                        <div class="ui-grade">
                            <div class="control-group" style="grid-column:span 2">
                                <label for="nome">Nome do projeto <span class="required">*</span></label>
                                <input type="text" id="nome" name="nome" required value="<?= $val('nome') ?>">
                            </div>
                            <div class="control-group">
                                <label for="tipo_obra">Tipo</label>
                                <input type="text" id="tipo_obra" name="tipo_obra" list="tipos_projeto" placeholder="Rede estruturada, CFTV IP..." value="<?= $val('tipo_obra') ?>">
                                <datalist id="tipos_projeto">
                                    <option value="Rede estruturada">
                                    <option value="CFTV IP">
                                    <option value="Rede estruturada + CFTV IP">
                                    <option value="Cabeamento óptico / fibra">
                                    <option value="Controle de acesso">
                                    <option value="Alarme">
                                    <option value="Interfonia / vídeo porteiro">
                                    <option value="Controle de acesso + CFTV IP">
                                    <option value="Infraestrutura (eletrocalha/eletroduto)">
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
                            <div class="control-group" style="grid-column:span 2">
                                <label for="cliente_busca">Cliente</label>
                                <div style="position:relative">
                                    <input type="text" id="cliente_busca" autocomplete="off" placeholder="Buscar por nome ou CNPJ/CPF..." value="<?= html_escape($clienteAtualNome) ?>">
                                    <input type="hidden" name="clientes_id" id="clientes_id" value="<?= $o ? (int) $o->clientes_id : '' ?>">
                                    <a href="#" id="cliente_limpar" title="Limpar seleção de cliente" aria-label="Limpar seleção de cliente" style="position:absolute;right:8px;top:6px;color:#999;<?= $clienteAtualNome ? '' : 'display:none' ?>"><i class="bx bx-x"></i></a>
                                </div>
                                <small class="help-block">Digite o nome ou o documento do cliente.</small>
                            </div>
                            <div class="control-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <?php foreach (['planejamento' => 'Planejamento', 'em_execucao' => 'Em execução', 'paralisada' => 'Paralisada', 'concluida' => 'Concluída', 'entregue' => 'Entregue', 'cancelada' => 'Cancelada'] as $k => $v): ?>
                                        <option value="<?= $k ?>" <?= $statusAtual === $k ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="ui-secao">
                        <h4>Prazos</h4>
                        <div class="ui-grade">
                            <div class="control-group"><label for="data_inicio_prevista">Início previsto</label><input type="text" id="data_inicio_prevista" class="datepicker" name="data_inicio_prevista" value="<?= $dt('data_inicio_prevista') ?>"></div>
                            <div class="control-group"><label for="data_fim_prevista">Fim previsto</label><input type="text" id="data_fim_prevista" class="datepicker" name="data_fim_prevista" value="<?= $dt('data_fim_prevista') ?>"></div>
                            <div class="control-group"><label for="data_inicio_real">Início real</label><input type="text" id="data_inicio_real" class="datepicker" name="data_inicio_real" value="<?= $dt('data_inicio_real') ?>"></div>
                            <div class="control-group"><label for="data_fim_real">Fim real</label><input type="text" id="data_fim_real" class="datepicker" name="data_fim_real" value="<?= $dt('data_fim_real') ?>"></div>
                        </div>
                    </div>

                    <div class="ui-secao">
                        <details class="pe-detalhes">
                            <summary>Mais detalhes</summary>
                            <div class="ui-grade">
                            <div class="control-group">
                                <label for="codigo">Código</label>
                                <input type="text" id="codigo" name="codigo" value="<?= $val('codigo') ?>">
                            </div>
                            <div class="control-group">
                                <label for="responsavel_id">Responsável técnico</label>
                                <select id="responsavel_id" name="responsavel_id">
                                    <option value="">— Selecione —</option>
                                    <?php foreach ($usuarios as $u): ?>
                                        <option value="<?= $u->idUsuarios ?>" <?= $o && $o->responsavel_id == $u->idUsuarios ? 'selected' : '' ?>><?= html_escape($u->nome) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="control-group">
                                <label for="contrato_numero">Nº do contrato</label>
                                <input type="text" id="contrato_numero" name="contrato_numero" value="<?= $val('contrato_numero') ?>">
                            </div>
                            <div class="control-group">
                                <label for="valor_contrato">Valor do contrato (R$)</label>
                                <input type="number" id="valor_contrato" step="0.01" name="valor_contrato" value="<?= $val('valor_contrato', '0') ?>">
                            </div>
                            <div class="control-group">
                                <label for="bdi_percentual">BDI (%)</label>
                                <input type="number" id="bdi_percentual" step="0.01" name="bdi_percentual" value="<?= $val('bdi_percentual', '0') ?>"><small class="help-block">Margem aplicada sobre o custo.</small>
                            </div>
                            </div>
                            <h4 style="margin-top:14px">Endereço do projeto</h4>
                            <div class="ui-grade">
                            <div class="control-group"><label for="cep">CEP</label><input type="text" id="cep" name="cep" value="<?= $val('cep') ?>"></div>
                            <div class="control-group" style="grid-column:span 2"><label for="logradouro">Logradouro</label><input type="text" id="logradouro" name="logradouro" value="<?= $val('logradouro') ?>"></div>
                            <div class="control-group"><label for="numero">Número</label><input type="text" id="numero" name="numero" value="<?= $val('numero') ?>"></div>
                            <div class="control-group"><label for="complemento">Complemento</label><input type="text" id="complemento" name="complemento" value="<?= $val('complemento') ?>"></div>
                            <div class="control-group"><label for="bairro">Bairro</label><input type="text" id="bairro" name="bairro" value="<?= $val('bairro') ?>"></div>
                            <div class="control-group" style="grid-column:span 2"><label for="cidade">Cidade</label><input type="text" id="cidade" name="cidade" value="<?= $val('cidade') ?>"></div>
                            <div class="control-group"><label for="uf">UF</label><input type="text" id="uf" name="uf" maxlength="2" value="<?= $val('uf') ?>"></div>
                            </div>
                            <div class="control-group">
                                <label for="observacoes">Observações</label>
                                <textarea id="observacoes" name="observacoes" rows="3"><?= $val('observacoes') ?></textarea>
                            </div>
                        </details>
                    </div>

                    <div class="ui-rodape-form">
                        <a href="<?= site_url('obras') ?>" class="button btn btn-mini btn-warning"><span class="button__icon"><i class="bx bx-undo"></i></span><span class="button__text2">Voltar</span></a>
                        <button class="button btn btn-primary"><span class="button__icon"><i class='bx bx-save'></i></span><span class="button__text2">Salvar alterações</span></button>
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
