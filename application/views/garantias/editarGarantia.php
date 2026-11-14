<link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery.validate.js"></script>

<link rel="stylesheet" href="<?php echo base_url() ?>assets/trumbowyg/ui/trumbowyg.css">
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/trumbowyg.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/langs/pt_br.js"></script>

<div class="row" style="margin-top:0">
    <div class="col-md-12">
        <div class="widget-box">
            <div class="widget-content row" style="padding: 16px">
                <div class="ui-cabecalho">
                    <div>
                        <h1 class="ui-titulo"><i class="fas fa-book"></i> Editar termo de garantia</h1>
                        <p class="ui-subtitulo">Altere a referência ou o texto do termo.</p>
                    </div>
                </div>

                <?php if ($custom_error) { ?>
                    <div class="col-md-12 alert alert-danger" id="divInfo" style="padding: 1%;">Dados incompletos, verifique os campos com asterisco ou se selecionou corretamente cliente e responsável.</div>
                <?php  } ?>

                <form action="<?php echo current_url(); ?>" method="post" id="formGarantia">

                    <div class="ui-grade">
                        <div>
                            <label for="dataGarantia">Data</label>
                            <?php echo form_hidden('idGarantias', $result->idGarantias) ?>
                            <input id="dataGarantia" class="datepicker w-100 form-control" type="text" name="dataGarantia" value="<?php echo date('d/m/Y', strtotime($result->dataGarantia)); ?>" disabled />
                        </div>
                        <div>
                            <label for="usuarios_id">Responsável</label>
                            <input id="usuarios_id" class="w-100 form-control" type="text" name="usuarios_id" value="<?php echo html_escape($result->nome); ?>" disabled />

                        </div>
                        <div>
                            <label for="refGarantia">Ref. Garantia</label>
                            <input id="refGarantia" class="w-100 form-control" type="text" name="refGarantia" value="<?php echo html_escape($result->refGarantia); ?>" />
                        </div>
                        <div style="grid-column: 1 / -1">
                            <label for="textoGarantia">
                                <h4 class="text-center">Termo de Garantia</h4>
                            </label>
                            <textarea required class="editor w-100 form-control" name="textoGarantia" id="textoGarantia" cols="30" rows="5"><?php echo html_escape($result->textoGarantia) ?></textarea>
                        </div>
                    </div>

                    <div class="ui-rodape-form">
                        <button type="submit" class="button btn btn-primary"><span class="button__icon"><i class="bx bx-sync"></i></span><span class="button__text2">Atualizar</span></button>
                        <a href="<?php echo base_url() ?>index.php/garantias" id="" class="button btn btn-sm btn-warning"><span class="button__icon"><i class="bx bx-undo"></i></span> <span class="button__text2">Voltar</span></a>
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
    });
</script>
