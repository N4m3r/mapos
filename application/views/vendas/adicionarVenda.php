<link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery.validate.js"></script>
<link rel="stylesheet" href="<?php echo base_url() ?>assets/trumbowyg/ui/trumbowyg.css">
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/trumbowyg.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/langs/pt_br.js"></script>

<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/custom.css" />

<div class="ui-cabecalho">
    <div>
        <h1><i class="fas fa-cash-register"></i> Nova Venda</h1>
        <p>Escolha o cliente e o vendedor. Depois de continuar, você adiciona os produtos da venda.</p>
    </div>
</div>
<div class="row" style="margin-top:0">
    <div class="col-md-12">
        <div class="widget-box">
            <div class="widget-content nopadding tab-content"><div class="row">
                <div class="col-md-12" id="divProdutosServicos" style=" margin-left: 0">
                    <ul class="nav nav-tabs">
                        <li class="nav-item" id="tabDetalhes"><a class="nav-link active" href="#tab1" data-bs-toggle="tab">Detalhes da venda</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab1"><div class="row">
                            <div class="col-md-12 row" id="divCadastrarOs">
                                <?php if ($custom_error == true) { ?>
                                    <div class="col-md-12 alert alert-danger" id="divInfo" style="padding: 1%;">Dados incompletos, verifique os campos com asterisco ou se selecionou corretamente cliente e responsável.</div>
                                <?php } ?>
                                <form class="row" action="<?php echo current_url(); ?>" method="post" id="formVendas">
                                    <div class="col-md-12" style="padding: 1%">
                                        <div class="col-md-2">
                                            <label for="dataInicial">Data da Venda<span class="required">*</span></label>
                                            <input id="dataVenda" class="datepicker w-100 form-control" type="text" name="dataVenda" value="<?php echo date('d/m/Y'); ?>" />
                                        </div>
                                        <div class="col-md-3">
                                            <label for="cliente">Cliente<span class="required">*</span></label>
                                            <input id="cliente" class="w-100 form-control" type="text" name="cliente" value="" />
                                            <input id="clientes_id" class="w-100" type="hidden" name="clientes_id" value="" />
                                            <div class="addclient"><?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'aCliente')) { ?>
                                              <a href="<?php echo base_url(); ?>index.php/clientes/adicionar" class="btn btn-success"><i class="fas fa-plus"></i> Adicionar Cliente</a><?php } ?></div>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="tecnico">Vendedor<span class="required">*</span></label>
                                            <input id="tecnico" class="w-100 form-control" type="text" name="tecnico" value="<?= $this->session->userdata('nome_admin'); ?>" />
                                            <input id="usuarios_id" class="w-100" type="hidden" name="usuarios_id" value="<?= $this->session->userdata('id_admin'); ?>" />
                                        </div>
                                        <div class="col-md-2">
                                            <label for="status">Status<span class="required">*</span></label>
                                            <select class="w-100 form-select" name="status" id="status" value="">
                                                <option value="Orçamento">Orçamento</option>
                                                <option value="Aberto">Aberto</option>
                                                <option value="Faturado">Faturado</option>
                                                <option value="Em Andamento">Em Andamento</option>
                                                <option value="Finalizado">Finalizado</option>
                                                <option value="Cancelado">Cancelado</option>
                                                <option value="Aguardando Peças">Aguardando Peças</option>
                                                <option value="Aprovado">Aprovado</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="garantia">Garantia (dias)</label>
                                            <input id="garantia" type="number" placeholder="Em Dias" min="0" max="9999" class="w-100 form-control" name="garantia" value="" />
                                            <?php echo form_error('garantia'); ?>
                                        </div>

                                    <div class="col-md-6" style="padding: 1%; margin-left: 0">
                                        <label for="observacoes">
                                            <h4>Observações</h4>
                                        </label>
                                        <textarea class="editor form-control" name="observacoes" id="observacoes" cols="30" rows="5"></textarea>
                                    </div>

                                    <div class="col-md-6" style="padding: 1%; margin-left: 0">
                                        <label for="observacoes_cliente">
                                            <h4>Observações para o Cliente</h4>
                                        </label>
                                        <textarea class="editor form-control" name="observacoes_cliente" id="observacoes_cliente" cols="30" rows="5"></textarea>
                                    </div>

                                    <div class="col-md-12 row" style="padding: 1%; margin-left: 0">
                                        <div class="col-md-6 offset-md-3" style="display:flex;justify-content: center">
                                          <button class="button btn btn-success" id="btnContinuar"><span class="button__icon"><i class='bx bx-chevrons-right'></i></span><span class="button__text2">Continuar</span></button>
                                            <a href="<?php echo base_url() ?>index.php/vendas" class="button btn btn-sm btn-warning"><span class="button__icon"><i class="bx bx-undo"></i></span> <span class="button__text2">Voltar</span></a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div></div>
                    </div>
                </div>
            </div></div>
        </div>
    </div>
</div>
<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function() {
        $('.addclient').hide();
        $("#cliente").autocomplete({
            source: "<?php echo base_url(); ?>index.php/vendas/autoCompleteCliente",
            minLength: 1,
            close: function(ui) { if(ui.label == 'Adicionar cliente...')ui.target.value = '';},
            select: function(event, ui) {
                if(ui.item.label == 'Adicionar cliente...')
                    $('.addclient').show();
                else
                    {
                        $("#clientes_id").val(ui.item.id);
                        $('.addclient').hide();
                    }
            }
        });
        $("#tecnico").autocomplete({
            source: "<?php echo base_url(); ?>index.php/vendas/autoCompleteUsuario",
            minLength: 1,
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
        $(".datepicker").datepicker({
            dateFormat: 'dd/mm/yy'
        });
        $('.editor').trumbowyg({
            lang: 'pt_br',
            semantic: { 'strikethrough': 's', }
        });
        $('.addclient').hide();
    });
</script>
