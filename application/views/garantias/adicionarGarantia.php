<link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery.validate.js"></script>

<link rel="stylesheet" href="<?php echo base_url() ?>assets/trumbowyg/ui/trumbowyg.css">
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/trumbowyg.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/langs/pt_br.js"></script>

<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-content" style="padding: 16px">
                <div class="ui-cabecalho">
                    <div>
                        <h1 class="ui-titulo"><i class="fas fa-book"></i> Novo termo de garantia</h1>
                        <p class="ui-subtitulo">Informe a referência do produto ou serviço e o texto do termo.</p>
                    </div>
                </div>

                <?php if ($custom_error == true) { ?>
                    <div class="span12 alert alert-danger" id="divInfo" style="padding: 1%;">Dados incompletos, verifique os campos com asterisco.</div>
                <?php
                } ?>
                <form action="<?php echo current_url(); ?>" method="post" id="formGarantia">
                    <div class="ui-grade">
                        <div>
                            <label for="dataGarantia">Data<span class="required">*</span></label>
                            <input id="dataGarantia" class="span12 datepicker" type="text" name="dataGarantia" value="<?php echo date('d/m/Y'); ?>" disabled />
                        </div>
                        <div>
                            <label for="usuarios_id">Responsável<span class="required">*</span></label>
                            <input id="usuarios_id" class="span12" type="text" name="usuarios_id" value="<?php echo $this->session->userdata('nome_admin') ?>" disabled />
                        </div>
                        <div>
                            <label for="refGarantia">Ref Garantia<span class="required">*</span></label>
                            <input type="text" class="span12" name="refGarantia" required placeholder="Informe uma referência: Exemplos: TV, Notebook, Celular">
                        </div>
                        <div style="grid-column: 1 / -1">
                            <label for="textoGarantia">
                                <h4 class="text-center">Termo de Garantia<span class="required">*</span></h4>
                            </label>
                            <textarea required class="span12 editor" name="textoGarantia" id="textoGarantia" cols="30" rows="5"></textarea></textarea>
                        </div>
                    </div>
                    <div class="ui-rodape-form">
                        <button class="button btn btn-success" id="btnContinuar">
                          <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Adicionar</span></button>
                        <a href="<?php echo base_url() ?>index.php/garantias" class="button btn btn-mini btn-warning">
                          <span class="button__icon"><i class="bx bx-undo"></i></span> <span class="button__text2">Voltar</span></a>
                    </div>
                </form>
                .
            </div>
        </div>
    </div>
</div>
<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function() {
        $("#cliente").autocomplete({
            source: "<?php echo base_url(); ?>index.php/garantias/autoCompleteCliente",
            minLength: 1,
            select: function(event, ui) {
                $("#clientes_id").val(ui.item.id);
            }
        });
        $("#tecnico").autocomplete({
            source: "<?php echo base_url(); ?>index.php/garantias/autoCompleteUsuario",
            minLength: 1,
            select: function(event, ui) {
                $("#usuarios_id").val(ui.item.id);
            }
        });
        $("#formGarantia").validate({
            rules: {
                dataGarantia: {
                    required: true
                },
                usuarios_id: {
                    required: true
                },
                refGarantia: {
                    required: true
                },
                textoGarantia: {
                    required: true
                }

            },
            messages: {
                dataGarantia: {
                    required: 'Campo Requerido.'
                },
                usuarios_id: {
                    required: 'Campo Requerido.'
                },
                refGarantia: {
                    required: 'Campo Requerido.'
                },
                textoGarantia: {
                    required: 'Preencha com o termo de garantia'
                }

            },
            errorClass: "help-inline",
            errorElement: "span",
            highlight: function(element, errorClass, validClass) {
                $(element).parents('.control-group').addClass('error');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).parents('.control-group').removeClass('error');
                $(element).parents('.control-group').addClass('success');
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
