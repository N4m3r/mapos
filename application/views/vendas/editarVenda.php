<link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery.validate.js"></script>
<script nonce="<?= csp_nonce() ?>" src="<?php echo base_url() ?>assets/js/sweetalert2.all.min.js"></script>
<link rel="stylesheet" href="<?php echo base_url() ?>assets/trumbowyg/ui/trumbowyg.css">
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/trumbowyg.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/langs/pt_br.js"></script>

<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/custom.css" />

<div class="row" style="margin-top:0">
    <div class="col-md-12">
        <div class="widget-box">
            <div class="widget-title" style="margin: -20px 0 0">
                <span class="icon">
                    <i class="fas fa-cash-register"></i>
                </span>
                <h5>Editar Venda</h5>
            </div>
            <div class="widget-content nopadding tab-content"><div class="row">
                <div class="col-md-12" id="divProdutosServicos" style=" margin-left: 0">
                    <ul class="nav nav-tabs">
                        <li class="nav-item" id="tabDetalhes"><a class="nav-link active" href="#tab1" data-bs-toggle="tab">Detalhes da Venda</a></li>
                        <li class="nav-item" id="tabProdutos"><a class="nav-link" href="#tab2" data-bs-toggle="tab">Produtos</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab1"><div class="row">
                            <div class="col-md-12" id="divEditarVenda">
                                <form class="row" action="<?php echo current_url(); ?>" method="post" id="formVendas">
                                    <?php echo form_hidden('idVendas', $result->idVendas) ?>
                                    <div class="col-md-12 row" style="padding: 1%; margin-left: 0">
                                        <h3>Venda:
                                            <?php echo $result->idVendas ?>
                                        </h3>
                                        <div class="col-md-2" style="margin-left: 0">
                                            <label for="dataFinal">Data Final</label>
                                            <input id="dataVenda" class="datepicker w-100 form-control" type="text" name="dataVenda" value="<?php echo date('d/m/Y', strtotime($result->dataVenda)); ?>" />
                                        </div>
                                        <div class="col-md-3">
                                            <label for="cliente">Cliente<span class="required">*</span></label>
                                            <input id="cliente" class="w-100 form-control" type="text" name="cliente" value="<?php echo html_escape($result->nomeCliente); ?>" />
                                            <input id="clientes_id" class="w-100" type="hidden" name="clientes_id" value="<?php echo $result->clientes_id ?>" />
                                            <input id="valorTotal" type="hidden" name="valorTotal" value="" />
                                        </div>
                                        <div class="col-md-3">
                                            <label for="tecnico">Vendedor<span class="required">*</span></label>
                                            <input id="tecnico" class="w-100 form-control" type="text" name="tecnico" value="<?php echo html_escape($result->nome); ?>" />
                                            <input id="usuarios_id" class="w-100" type="hidden" name="usuarios_id" value="<?php echo $result->usuarios_id ?>" />
                                        </div>
                                        <div class="col-md-2">
                                            <label for="status">Status<span class="required">*</span></label>
                                            <select class="w-100 form-select" name="status" id="status" value="">
                                                <option <?= $result->status == 'Orçamento' ? 'selected' : '' ?> value="Orçamento">Orçamento</option>
                                                <option <?= $result->status == 'Aberto' ? 'selected' : '' ?> value="Aberto">Aberto</option>
                                                <option <?= $result->status == 'Faturado' ? 'selected' : '' ?> value="Faturado">Faturado</option>
                                                <option <?= $result->status == 'Negociação' ? 'selected' : '' ?> value="Negociação">Negociação</option>
                                                <option <?= $result->status == 'Em Andamento' ? 'selected' : '' ?> value="Em Andamento">Em Andamento</option>
                                                <option <?= $result->status == 'Finalizado' ? 'selected' : '' ?> value="Finalizado">Finalizado</option>
                                                <option <?= $result->status == 'Cancelado' ? 'selected' : '' ?> value="Cancelado">Cancelado</option>
                                                <option <?= $result->status == 'Aguardando Peças' ? 'selected' : '' ?>value="Aguardando Peças">Aguardando Peças</option>
                                                <option <?= $result->status == 'Aprovado' ? 'selected' : '' ?> value="Aprovado">Aprovado</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="garantia">Garantia (dias)</label>
                                            <input id="garantia" type="number" placeholder="Em dias" min="0" max="9999"
                                                class="w-100 form-control" name="garantia"
                                                value="<?php echo $result->garantia ?>" />
                                            <?php echo form_error('garantia'); ?>
                                        </div>
                                    </div>

                                    <div class="col-md-6" style="padding: 1%; margin-left: 0">
                                        <label for="observacoes">
                                            <h4>Observações</h4>
                                        </label>
                                        <textarea class="editor form-control" name="observacoes" id="observacoes" cols="30" rows="5"><?php echo html_escape($result->observacoes) ?></textarea>
                                    </div>

                                    <div class="col-md-6" style="padding: 1%; margin-left: 0">
                                        <label for="observacoes_cliente">
                                            <h4>Observações para o Cliente</h4>
                                        </label>
                                        <textarea class="editor form-control" name="observacoes_cliente" id="observacoes_cliente" cols="30" rows="5"><?php echo html_escape($result->observacoes_cliente) ?></textarea>
                                    </div>

                                    <div class="col-md-12 row" style="padding: 1%; margin-left: 0">
                                        <div class="col-md-12" style="display:flex; justify-content: center;">
                                            <?php if ($result->faturado == 0) { ?>
                                                <a href="#modal-faturar" id="btn-faturar" role="button" data-bs-toggle="modal" class="button btn btn-danger">
                                                    <span class="button__icon"><i class='bx bx-dollar'></i></span> 
                                                    <span class="button__text2">Faturar</span>
                                                </a>
                                            <?php } ?>
                                            <button class="button btn btn-primary" id="btnContinuar">
                                                <span class="button__icon"><i class="bx bx-sync"></i></span>
                                                <span class="button__text2">Atualizar</span>
                                            </button>
                                            <a href="<?php echo base_url() ?>index.php/vendas/visualizar/<?php echo $result->idVendas; ?>" class="button btn btn-primary">
                                                <span class="button__icon"><i class="bx bx-show"></i></span>
                                                <span class="button__text2">Visualizar</span>
                                            </a>
                                            <a href="<?php echo base_url() ?>index.php/vendas" class="button btn btn-warning">
                                                <span class="button__icon"><i class="bx bx-undo"></i></span>
                                                <span class="button__text2">Voltar</span>
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div></div>
                        <div class="tab-pane" id="tab2"><div class="row">
                            <div class="col-md-12 card card-body" style="padding: 1%; margin-left: 0"><div class="row">
                                <div class="col-md-11">
                                    <form class="row" id="formProdutos" action="<?php echo base_url(); ?>index.php/vendas/adicionarProduto" method="post">
                                        <div class="col-md-6">
                                            <input type="hidden" name="idProduto" id="idProduto" />
                                            <input type="hidden" name="idVendasProduto" id="idVendasProduto" value="<?php echo $result->idVendas ?>" />
                                            <input type="hidden" name="estoque" id="estoque" value="" />
                                            <label for="">Produto</label>
                                            <input type="text" class="w-100 form-control" name="produto" id="produto" placeholder="Digite o nome do produto" />
                                        </div>
                                        <div class="col-md-2">
                                            <label for="">Preço</label>
                                            <input type="text" placeholder="Preço" id="preco" name="preco" class="money w-100 form-control" />
                                        </div>
                                        <div class="col-md-2">
                                            <label for="">Quantidade</label>
                                            <input type="text" placeholder="Quantidade" id="quantidade" name="quantidade" class="w-100 form-control" />
                                        </div>
                                        <div class="col-md-2">
                                            <label for="">&nbsp</label>
                                            <button class="button btn btn-success" id="btnAdicionarProduto">
                                                <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Adicionar</span></button>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-md-11">
                                    <form class="row" id="formDesconto" action="<?php echo base_url(); ?>index.php/vendas/adicionarDesconto" method="POST">
                                        <div class="col-md-1">
                                            <input type="hidden" name="idVendas" id="idVendas" value="<?php echo $result->idVendas; ?>" />
                                            <label for="">Desconto</label>
                                            <input class="form-control" style="width: 4em;" id="desconto" name="desconto" type="text" placeholder="0.00" maxlength="6" size="2" /><br />
                                            <strong><span style="color: red" id="errorAlert"></span></strong>
                                        </div>
                                        <div class="col-md-1">
                                        <label for="">Tipo Desc.</label>
                                        <select class="form-select" style="width: 4em;" name="tipoDesconto" id="tipoDesconto">
                                            <option value="real">R$</option>
                                            <option value="porcento" <?=$result->tipo_desconto == "porcento" ? "selected" : "" ?>>%</option>
                                        </select>
                                        <strong><span style="color: red" id="errorAlert"></span></strong>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="">Total com Desconto</label>
                                            <input class="money w-100 form-control" id="resultado" type="text" data-affixes-stay="true" data-thousands="" data-decimal="." name="resultado" value="" readonly />
                                        </div>
                                        <div class="col-md-2">
                                            <label for="">&nbsp;</label>
                                            <button class="button btn btn-success" id="btnAdicionarDesconto">
                                                <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Aplicar</span></button>
                                        </div>
                                    </form>
                                </div>
                            </div></div>
                            <div class="col-md-12" id="divProdutos" style="margin-left: 0">
                                <table class="table table-bordered" id="tblProdutos">
                                    <thead>
                                        <tr>
                                            <th>Produto</th>
                                            <th width="8%">Quantidade</th>
                                            <th width="10%">Preço</th>
                                            <th width="6%">Ações</th>
                                            <th width="10%">Sub-total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $total = 0;
foreach ($produtos as $p) {
    $preco = $p->preco ?: $p->precoVenda;
    $total = $total + $p->subTotal;
    echo '<tr>';
    echo '<td>' . $p->descricao . '</td>';
    echo '<td><div align="center">' . $p->quantidade . '</div></td>';
    echo '<td><div align="center">R$: ' . $preco . '</div></td>';
    echo '<td><div align="center"><a href="" idAcao="' . $p->idItens . '" prodAcao="' . $p->idProdutos . '" quantAcao="' . $p->quantidade . '" title="Excluir Produto" class="btn-nwe4"><i class="bx bx-trash-alt"></i></a></div></td>';
    echo '<td><div align="center">R$: ' . number_format($p->subTotal, 2, '.', '') . '</div></td>';
    echo '</tr>';
} ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" style="text-align: right"><strong>Total:</strong></td>
                                            <td>
                                                <div align="center"><strong>R$: <?php echo number_format($total, 2, '.', ''); ?></strong></div> <input type="hidden" id="total-venda" value="<?php echo number_format($total, 2, '.', ''); ?>">
                                            </td>
                                        </tr>
                                        <?php if ($result->valor_desconto != 0 && $result->desconto != 0) {
                                            ?>
                                            <tr>
                                                <td colspan="4" style="text-align: right"><strong>Desconto:</strong></td>
                                                <td>
                                                    <div align="center"><strong><?php echo $result->tipo_desconto == "real" ? "R$ " : ""; ?> <?php echo number_format($result->desconto, 2, '.', ''); ?> <?php echo $result->tipo_desconto == "porcento" ? " %" : ""; ?></strong></div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" style="text-align: right"><strong>Total Com Desconto:</strong></td>
                                                <td>
                                                    <div align="center"><strong>R$: <?php echo number_format($result->valor_desconto, 2, '.', ''); ?></strong></div><input type="hidden" id="total-desconto" value="<?php echo number_format($result->valor_desconto, 2, '.', ''); ?>">
                                                </td>
                                            </tr>
                                        <?php
                                        } ?>
                                    </tfoot>
                                </table>
                            </div>
                        </div></div>
                    </div>
                </div>
                &nbsp
            </div></div>
        </div>
    </div>
</div>

<!-- Modal Faturar-->
<div id="modal-faturar" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
    <form id="formFaturar" action="<?php echo current_url() ?>" method="post">
        <div class="modal-header">
            <h3 class="modal-title" id="myModalLabel">Faturar Venda</h3>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
        <div class="modal-body"><div class="row">
            <div class="col-md-12 alert alert-info" style="margin-left: 0"> Obrigatório o preenchimento dos campos com asterisco.</div>
            <div class="col-md-12" style="margin-left: 0">
                <label for="descricao">Descrição</label>
                <input class="w-100 form-control" id="descricao" type="text" name="descricao" value="Fatura de Venda Nº: <?php echo $result->idVendas; ?> " />
            </div>
            <div class="col-md-12 row" style="margin-left: 0">
                <div class="col-md-12" style="margin-left: 0">
                    <label for="cliente">Cliente*</label>
                    <input class="w-100 form-control" id="cliente" type="text" name="cliente" value="<?php echo html_escape($result->nomeCliente); ?>" />
                    <input type="hidden" name="clientes_id" id="clientes_id" value="<?php echo $result->clientes_id ?>">
                    <input type="hidden" name="vendas_id" id="vendas_id" value="<?php echo $result->idVendas; ?>">
                </div>
            </div>
            <div class="col-md-12 row" style="margin-left: 0">
                <div class="col-md-5" style="margin-left: 0">
                    <label for="valor">Valor*</label>
                    <input type="hidden" id="tipo" name="tipo" value="receita" />
                    <input class="money w-100 form-control" id="valor" type="text" name="valor" value="<?php echo number_format($total, 2, '.', ''); ?> " />
                </div>
                <div class="col-md-5" style="margin-left: 2">
                    <label for="valor">Valor Com Desconto*</label>
                    <input class="money w-100 form-control" id="faturar-desconto" type="text" name="faturar-desconto" value="<?php echo number_format($result->valor_desconto, 2, '.', ''); ?> " />
                </div>
            </div>
            <div class="col-md-12 row" style="margin-left: 0">
                <div class="col-md-4" style="margin-left: 0">
                    <label for="vencimento">Data Entrada*</label>
                    <input class="datepicker w-100 form-control" autocomplete="off" id="vencimento" type="text" name="vencimento" />
                </div>
            </div>
            <div class="col-md-12 row" style="margin-left: 0">
                <div class="col-md-4" style="margin-left: 0">
                    <label for="recebido">Recebido?</label>
                    &nbsp &nbsp &nbsp &nbsp<input class="form-check-input" id="recebido" type="checkbox" name="recebido" value="1" />
                </div>
                <div id="divRecebimento" class="col-md-8 row" style=" display: none">
                    <div class="col-md-6">
                        <label for="recebimento">Data Recebimento</label>
                        <input class="datepicker w-100 form-control" autocomplete="off" id="recebimento" type="text" name="recebimento" />
                    </div>
                    <div class="col-md-6">
                        <label for="formaPgto">Forma Pgto</label>
                        <select name="formaPgto" id="formaPgto" class="w-100 form-select">
                            <option value="Dinheiro">Dinheiro</option>
                            <option value="Cartão de Crédito">Cartão de Crédito</option>
                            <option value="Débito">Débito</option>
                            <option value="Boleto">Boleto</option>
                            <option value="Depósito">Depósito</option>
                            <option value="Pix">Pix</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                </div>
            </div>
        </div></div>
        <div class="modal-footer" style="display:flex">
            <button class="button btn btn-warning" data-bs-dismiss="modal" aria-hidden="true" id="btn-cancelar-faturar">
                <span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Cancelar</span></button>
            <button class="button btn btn-danger"><span class="button__icon"><i class='bx bx-dollar'></i></span> <span class="button__text2">Faturar</span></button>
        </div>        
    </form>
</div></div></div>
<script nonce="<?= csp_nonce() ?>" src="<?php echo base_url(); ?>assets/js/maskmoney.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    
    $("#quantidade").keyup(function() {
        this.value = this.value.replace(/[^0-9.]/g, '');
    });

    function calcDesconto(valor, desconto, tipoDesconto) {
        var resultado = 0;
        if (tipoDesconto == 'real') {
            resultado = valor - desconto;
        }
        if (tipoDesconto == 'porcento') {
            resultado = (valor - desconto * valor / 100).toFixed(2);
        }
        return resultado;
    }

    function validarDesconto(resultado, valor) {
        if (resultado == valor) {
            return resultado = "";
        } else {
            return resultado.toFixed(2);
        }
    }
    var valorBackup = $("#total-venda").val();

    $("#desconto").keyup(function() {

        this.value = this.value.replace(/[^0-9.]/g, '');
        if ($("#total-venda").val() == null || $("#total-venda").val() == '') {
            $('#errorAlert').text('Valor não pode ser apagado.').css("display", "inline").fadeOut(5000);
            $('#desconto').val('');
            $('#resultado').val('');
            $("#total-venda").val(valorBackup);
            $("#desconto").focus();

        } else if (Number($("#desconto").val()) >= 0) {
            $('#resultado').val(calcDesconto(Number($("#total-venda").val()), Number($("#desconto").val()), $("#tipoDesconto").val()));
            $('#resultado').val(validarDesconto(Number($('#resultado').val()), Number($("#total-venda").val())));
        } else {
            $('#errorAlert').text('Erro desconhecido.').css("display", "inline").fadeOut(5000);
            $('#desconto').val('');
            $('#resultado').val('');
        }
    });
    $('#tipoDesconto').on('change', function() {
        if (Number($("#desconto").val()) >= 0) {
            $('#resultado').val(calcDesconto(Number($("#total-venda").val()), Number($("#desconto").val()), $("#tipoDesconto").val()));
            $('#resultado').val(validarDesconto(Number($('#resultado').val()), Number($("#total-venda").val())));
        }
    });

    $("#total-venda").focusout(function() {
        $("#total-venda").val(valorBackup);
        if ($("#total-venda").val() == '0.00' && $('#resultado').val() != '') {
            $('#errorAlert').text('Você não pode apagar o valor.').css("display", "inline").fadeOut(6000);
            $('#resultado').val('');
            $("#total-venda").val(valorBackup);
            $('#resultado').val(calcDesconto(Number($("#total-venda").val()), Number($("#desconto").val()), $("#tipoDesconto").val()));
            $('#resultado').val(validarDesconto(Number($('#resultado').val()), Number($("#total-venda").val())));
            $("#desconto").focus();
        } else {
            $('#resultado').val(calcDesconto(Number($("#total-venda").val()), Number($("#desconto").val()), $("#tipoDesconto").val()));
            $('#resultado').val(validarDesconto(Number($('#resultado').val()), Number($("#total-venda").val())));
        }
    });

    $('#resultado').focusout(function() {
        if (Number($('#resultado').val()) > Number($("#total-venda").val())) {
            $('#errorAlert').text('Desconto não pode ser maior que o Valor.').css("display", "inline").fadeOut(6000);
            $('#resultado').val('');
        }
        if ($("#desconto").val() != "" || $("#desconto").val() != null) {
            $('#resultado').val(calcDesconto(Number($("#total-venda").val()), Number($("#desconto").val())));
            $('#resultado').val(validarDesconto(Number($('#resultado').val()), Number($("#total-venda").val())));
        }
    });

    $(document).ready(function() {
        $(".money").maskMoney();
        $('#recebido').click(function(event) {
            var flag = $(this).is(':checked');
            if (flag == true) {
                $('#divRecebimento').show();
            } else {
                $('#divRecebimento').hide();
            }
        });
        $(document).on('click', '#btn-faturar', function(event) {
            event.preventDefault();
            valor = $('#total-venda').val();
            valor_desconto = $('#total-desconto').val();
            valor_desconto != 0.00 || valor_desconto ? $('#valor').attr('readonly', false) : $('#faturar-desconto').attr('readonly', false);
            valor = valor.replace(',', '');
            $('#valor').val(valor);
        });
        $('#formDesconto').submit(function(e) {
            e.preventDefault();
            var form = $(this);
            $("#divProdutos").html("<div class='progress'><div class='progress-bar progress-bar-striped progress-bar-animated bg-info' style='width: 100%'></div></div>");
            $.ajax({
                url: form.attr('action'),
                type: form.attr('method'),
                data: form.serialize(),
                beforeSend: function() {
                    Swal.fire({
                        title: 'Processando',
                        text: 'Registrando desconto...',
                        icon: 'info',
                        showCloseButton: false,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    });
                },
                success: function(response) {
                    if (response.result) {
                        Swal.fire({
                            type: "success",
                            title: "Sucesso",
                            text: response.messages
                        });
                        $("#divProdutos").load("<?php echo current_url(); ?> #divProdutos");
                        $("#desconto").val("");
                        $("#resultado").val("");
                        /*setTimeout(function() {
                            window.location.href = window.BaseUrl + 'index.php/vendas/editar/' + <?php echo $result->idVendas ?>;
                        }, 2000);*/
                    } else {
                        Swal.fire({
                            type: "error",
                            title: "Atenção",
                            text: response.messages
                        });
                        $("#divProdutos").load("<?php echo current_url(); ?> #divProdutos");
                        $("#desconto").val("");
                        $("#resultado").val("");
                    }

                },
                error: function(response) {
                    Swal.fire({
                        type: "error",
                        title: "Atenção",
                        text: response.responseJSON.messages
                    });
                    $("#divProdutos").load("<?php echo current_url(); ?> #divProdutos");
                    $("#desconto").val("");
                    $("#resultado").val("");
                }
            });
        });
        $("#formFaturar").validate({
            rules: {
                descricao: {
                    required: true
                },
                cliente: {
                    required: true
                },
                valor: {
                    required: true
                },
                vencimento: {
                    required: true
                }
            },
            messages: {
                descricao: {
                    required: 'Campo Requerido.'
                },
                cliente: {
                    required: 'Campo Requerido.'
                },
                valor: {
                    required: 'Campo Requerido.'
                },
                vencimento: {
                    required: 'Campo Requerido.'
                }
            },
            submitHandler: function(form) {
                var dados = $(form).serialize();
                var qtdProdutos = $('#tblProdutos >tbody >tr').length;

                $('#btn-cancelar-faturar').trigger('click');

                if (qtdProdutos <= 0) {
                    Swal.fire({
                        type: "error",
                        title: "Atenção",
                        text: "Não é possível faturar uma venda sem produtos"
                    });
                } else if (qtdProdutos > 0) {
                    $.ajax({
                        type: "POST",
                        url: "<?php echo base_url(); ?>index.php/vendas/faturar",
                        data: dados,
                        dataType: 'json',
                        success: function(data) {
                            if (data.result == true) {
                                window.location.reload(true);
                            } else {
                                Swal.fire({
                                    type: "error",
                                    title: "Atenção",
                                    text: "Ocorreu um erro ao tentar faturar venda."
                                });
                                $('#progress-fatura').hide();
                            }
                        }
                    });

                    return false;
                }
            }
        });
        $("#produto").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteProdutoSaida",
            minLength: 2,
            select: function(event, ui) {
                $("#idProduto").val(ui.item.id);
                $("#estoque").val(ui.item.estoque);
                $("#preco").val(ui.item.preco);
                $("#quantidade").focus();
            }
        });
        $("#cliente").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteCliente",
            minLength: 2,
            select: function(event, ui) {
                $("#clientes_id").val(ui.item.id);
            }
        });
        $("#tecnico").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteUsuario",
            minLength: 2,
            select: function(event, ui) {
                $("#usuarios_id").val(ui.item.id);
            }
        });
        $("#formVendas").validate({
            rules: {
                cliente: {
                    required: true
                },
                tecnico: {
                    required: true
                },
                dataVenda: {
                    required: true
                }
            },
            messages: {
                cliente: {
                    required: 'Campo Requerido.'
                },
                tecnico: {
                    required: 'Campo Requerido.'
                },
                dataVenda: {
                    required: 'Campo Requerido.'
                }
            },
            errorClass: "invalid-feedback",
            errorElement: "span",
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalid');
                $(element).addClass('is-valid');
            }
        });
        $("#formProdutos").validate({
            rules: {
                preco: {
                    required: true
                },
                quantidade: {
                    required: true
                }
            },
            messages: {
                preco: {
                    required: 'Insira o preço'
                },
                quantidade: {
                    required: 'Insira a quantidade'
                }
            },
            submitHandler: function(form) {
                var quantidade = parseInt($("#quantidade").val());
                var estoque = parseInt($("#estoque").val());

                <?php if (!$configuration['control_estoque']) {
                    echo 'estoque = 1000000';
                }; ?>

                if (estoque < quantidade) {
                    Swal.fire({
                        type: "warning",
                        title: "Atenção",
                        text: "Você não possui estoque suficiente."
                    });
                } else {
                    var dados = $(form).serialize();
                    $("#divProdutos").html("<div class='progress'><div class='progress-bar progress-bar-striped progress-bar-animated bg-info' style='width: 100%'></div></div>");
                    $.ajax({
                        type: "POST",
                        url: "<?php echo base_url(); ?>index.php/vendas/adicionarProduto",
                        data: dados,
                        dataType: 'json',
                        success: function(data) {
                            if (data.result == true) {
                                $("#divProdutos").load("<?php echo current_url(); ?> #divProdutos");
                                $("#quantidade").val('');
                                $("#preco").val('');
                                $("#produto").val('').focus();
                                $("#resultado").val("");
                                $("#desconto").val("");
                            } else {
                                Swal.fire({
                                    type: "error",
                                    title: "Atenção",
                                    html: "Ocorreu um erro ao tentar adicionar produto. <br /><br />Error: " + data.messages
                                });
                                $("#divProdutos").load("<?php echo current_url(); ?> #divProdutos");
                                $('#formProdutos')[0].reset();
                            }
                        }
                    });
                    return false;
                }
            }
        });
        $(document).on('click', 'a', function(event) {
            var idProduto = $(this).attr('idAcao');
            var quantidade = $(this).attr('quantAcao');
            var produto = $(this).attr('prodAcao');
            if ((idProduto % 1) == 0) {
                $("#divProdutos").html("<div class='progress'><div class='progress-bar progress-bar-striped progress-bar-animated bg-info' style='width: 100%'></div></div>");
                $.ajax({
                    type: "POST",
                    url: "<?php echo base_url(); ?>index.php/vendas/excluirProduto",
                    data: "idProduto=" + idProduto + "&idVendas=" + <?= $result->idVendas ?> + "&quantidade=" + quantidade + "&produto=" + produto,
                    dataType: 'json',
                    success: function(data) {
                        if (data.result == true) {
                            $("#divProdutos").load("<?php echo current_url(); ?> #divProdutos");
                            $("#resultado").val("");
                            $("#desconto").val("");
                        } else {
                            Swal.fire({
                                type: "error",
                                title: "Atenção",
                                html: "Ocorreu um erro ao tentar excluir produto." + data.messages
                            });
                            $("#divProdutos").load("<?php echo current_url(); ?> #divProdutos");
                        }
                    }
                });
                return false;
            }
        });
        $(".datepicker").datepicker({
            dateFormat: 'dd/mm/yy'
        });
        $('.editor').trumbowyg({
            lang: 'pt_br',
            semantic: { 'strikethrough': 's', }
        });
    });
</script>
