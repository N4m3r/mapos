<link rel="stylesheet" href="<?= base_url(); ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?= base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>

<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-content nopadding tab-content" style="padding: 16px">
                <div class="ui-cabecalho">
                    <div>
                        <h1 class="ui-titulo"><i class="fas fa-hdd"></i> Novo arquivo</h1>
                        <p class="ui-subtitulo">Envie um documento e dê um nome para encontrá-lo depois.</p>
                    </div>
                </div>
                <?= $custom_error ?>

                <form action="<?= current_url() ?>" id="formArquivo" enctype="multipart/form-data" method="post">
                    <div class="ui-secao">
                        <div class="ui-grade">
                            <div class="control-group">
                                <label for="arquivo" class="control-label"><span class="required">Arquivo*</span></label>
                                <div class="controls">
                                    <input id="arquivo" type="file" name="userfile" /> <span class="help-block">Formatos aceitos: .txt .pdf .gif .png .jpg .jpeg</span>
                                </div>
                            </div>

                            <div class="control-group">
                                <label for="nome" class="control-label">Nome do Arquivo*</label>
                                <div class="controls">
                                    <input id="nome" type="text" name="nome" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label for="data" class="control-label">Data</label>
                                <div class="controls">
                                    <input id="data" type="text" class="datepicker" name="data" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ui-secao">
                        <div class="control-group">
                            <label for="descricao" class="control-label">Descrição</label>
                            <div class="controls">
                                <textarea rows="3" cols="30" name="descricao" id="descricao"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="ui-rodape-form">
                        <button type="submit" class="button btn btn-mini btn-success">
                            <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Adicionar</span></button>
                        <a href="<?= base_url() ?>index.php/arquivos" class="button btn btn-mini btn-warning">
                            <span class="button__icon"><i class="bx bx-undo"></i></span><span class="button__text2">Voltar</span></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/jquery.validate.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function() {
        $('#formArquivo').validate({
            rules: {
                nome: {
                    required: true
                },
                userfile: {
                    required: true
                }
            },
            messages: {
                nome: {
                    required: 'Campo Requerido.'
                },
                userfile: {
                    required: 'Campo Requerido.'
                }
            },

            errorClass: "help-inline",
            errorElement: "span",
            highlight: function(element, errorClass, validClass) {
                $(element).parents('.control-group').addClass('error');
                $(element).parents('.control-group').removeClass('success');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).parents('.control-group').removeClass('error');
                $(element).parents('.control-group').addClass('success');
            }
        });
        $(".datepicker").datepicker({
            dateFormat: 'dd/mm/yy'
        });
    });
</script>
