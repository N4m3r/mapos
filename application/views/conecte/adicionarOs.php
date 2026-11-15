<link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery.validate.js"></script>

<link rel="stylesheet" href="<?php echo base_url() ?>assets/trumbowyg/ui/trumbowyg.css">
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/trumbowyg.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/langs/pt_br.js"></script>

<style>
    .ui-datepicker {
        z-index: 9999 !important;
    }

    .trumbowyg-box {
        margin-top: 0;
        margin-bottom: 0;
    }
</style>

<div class="row" style="margin-top:0">
    <div class="col-md-12">
        <div class="widget-box">
            <div class="widget-title" style="margin: -20px 0 0">
                <span class="icon">
                    <i class="fas fa-diagnoses"></i>
                </span>
                <h5>Cadastro de OS</h5>
            </div>
            <div class="widget-content nopadding tab-content"><div class="row">
                <div class="col-md-12" id="divProdutosServicos" style=" margin-left: 0">
                    <ul class="nav nav-tabs">
                        <li class="nav-item" id="tabDetalhes"><a class="nav-link active" href="#tab1" data-bs-toggle="tab">Detalhes da OS</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab1"><div class="row">
                            <div class="col-md-12" id="divCadastrarOs">
                                <form class="row" action="<?php echo current_url(); ?>" method="post" id="formOs">
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                                    <div class="col-md-12 row" style="padding: 1%;">
                                        <div class="col-md-12 alert alert-info">
                                            <h5 class="text-center">Preencha os campos abaixo detalhando o que você precisa. Campos com asterisco são obrigatórios.</h5>
                                        </div>
                                    </div>
                                    <div class="col-md-12 row" style="padding: 1%; margin-left: 0">

                                        <div class="col-md-12">
                                            <label for="descricaoProduto">Descrição Produto/Serviço*</label>
                                            <textarea class="editor w-100 form-control" name="descricaoProduto" id="descricaoProduto" cols="30" rows="5"></textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-12 row" style="padding: 1%; margin-left: 0">

                                        <div class="col-md-12">
                                            <label for="defeito">Defeito</label>
                                            <textarea class="editor w-100 form-control" name="defeito" id="defeito" cols="30" rows="5"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-12 row" style="padding: 1%; margin-left: 0">
                                        <div class="col-md-12">
                                            <label for="observacoes">Observações</label>
                                            <textarea class="editor w-100 form-control" name="observacoes" id="observacoes" cols="30" rows="5"></textarea>
                                        </div>
                                        
                                    </div>
                                    <div class="col-md-12 row" style="padding: 1%; margin-left: 0">
                                        <div class="col-md-6 offset-md-3" style="display:flex;justify-content: center">
                                            <button class="button btn btn-success" id="btnContinuar">
                                              <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Cadastrar</span></button>
                                            <a href="<?php echo base_url() ?>index.php/mine/os" class="button btn btn-sm btn-warning">
                                              <span class="button__icon"><i class="bx bx-undo"></i></span> <span class="button__text2">Voltar</span></a>
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


        $("#formOs").validate({
            rules: {
                descricaoProduto: {
                    required: true
                }
            },
            messages: {
                descricaoProduto: {
                    required: 'O campo descrição da OS é obrigatório.'
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
    });
</script>
