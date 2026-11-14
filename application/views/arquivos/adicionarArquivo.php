<link rel="stylesheet" href="<?= base_url(); ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?= base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>

<div class="row" style="margin-top:0">
    <div class="col-md-12">
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
                            <div class="mb-3">
                                <label for="arquivo" class="form-label"><span class="required">Arquivo*</span></label>
                                <div class="">
                                    <input class="form-control" id="arquivo" type="file" name="userfile" /> <span class="form-text">Formatos aceitos: .txt .pdf .gif .png .jpg .jpeg</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome do Arquivo*</label>
                                <div class="">
                                    <input class="form-control" id="nome" type="text" name="nome" />
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="data" class="form-label">Data</label>
                                <div class="">
                                    <input id="data" type="text" class="datepicker form-control" name="data" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ui-secao">
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <div class="">
                                <textarea class="form-control" rows="3" cols="30" name="descricao" id="descricao"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="ui-rodape-form">
                        <button type="submit" class="button btn btn-sm btn-success">
                            <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Adicionar</span></button>
                        <a href="<?= base_url() ?>index.php/arquivos" class="button btn btn-sm btn-warning">
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
