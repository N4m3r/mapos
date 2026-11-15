<link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>

<div class="row" style="margin-top:0">
    <div class="col-md-12">
        <div class="widget-box">
            <div class="widget-content nopadding tab-content" style="padding: 16px">
                <div class="ui-cabecalho">
                    <div>
                        <h1 class="ui-titulo"><i class="fas fa-hdd"></i> Editar arquivo</h1>
                        <p class="ui-subtitulo">Altere o nome, a descrição ou a data do documento.</p>
                    </div>
                </div>
                <?php echo $custom_error; ?>
                <form action="<?php echo current_url(); ?>" id="formArquivo" method="post">


                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome do Arquivo*</label>
                        <div class="">
                            <input class="form-control" id="nome" type="text" name="nome" value="<?php echo html_escape($result->documento); ?> " />

                            <input id="idDocumentos" type="hidden" name="idDocumentos" value="<?php echo $result->idDocumentos; ?> " />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <div class="">
                            <textarea class="form-control" rows="3" cols="30" name="descricao" id="descricao"><?php echo html_escape($result->descricao); ?></textarea>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Data</label>
                        <div class="">
                            <input id="data" type="text" class="datepicker form-control" name="data" value="<?php echo date('d/m/Y', strtotime($result->cadastro)); ?>" />
                        </div>
                    </div>

                    <div class="ui-rodape-form">
                        <button type="submit" class="button btn btn-primary"><span class="button__icon"><i class="bx bx-sync"></i></span><span class="button__text2">Atualizar</span></button>
                        <a href="<?php echo base_url() ?>index.php/arquivos" class="button btn btn-sm btn-warning"><span class="button__icon"><i class="bx bx-undo"></i></span> <span class="button__text2">Voltar</span></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>" src="<?php echo base_url() ?>assets/js/jquery.validate.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function() {

        $('#formArquivo').validate({
            rules: {
                nome: {
                    required: true
                }
            },
            messages: {
                nome: {
                    required: 'Campo Requerido.'
                }
            },

            errorClass: "invalid-feedback",
            errorElement: "span",
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalid');
                $(element).removeClass('is-valid');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalid');
                $(element).addClass('is-valid');
            }
        });


        $(".datepicker").datepicker({
            dateFormat: 'dd/mm/yy'
        });
    });
</script>
