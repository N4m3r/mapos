<div class="row" style="margin-top:0">
    <div class="col-md-12">
        <div class="widget-box">
            <div class="widget-content">
                <div class="ui-cabecalho">
                    <div>
                        <h1 class="ui-titulo"><i class="fas fa-wrench"></i> Novo serviço</h1>
                        <p class="ui-subtitulo">Nome, preço e dados fiscais para emissão de NFS-e. Campos com * são obrigatórios.</p>
                    </div>
                </div>

                <?php if (! empty($custom_error)) { ?>
                    <div class="alert alert-danger"><?php echo $custom_error; ?></div>
                <?php } ?>

                <form action="<?php echo current_url(); ?>" id="formServico" method="post">
                    <div class="ui-secao">
                        <h4 class="ui-secao-titulo">Dados do serviço</h4>
                        <div class="ui-grade">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome<span class="required">*</span></label>
                                <div class="">
                                    <input class="form-control" id="nome" type="text" name="nome" value="<?php echo set_value('nome'); ?>" />
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="preco" class="form-label">Preço<span class="required">*</span></label>
                                <div class="">
                                    <input id="preco" class="money form-control" data-affixes-stay="true" data-thousands="" data-decimal="." type="text" name="preco" value="<?php echo set_value('preco'); ?>" />
                                </div>
                            </div>
                            <div class="mb-3" style="grid-column: 1 / -1">
                                <label for="descricao" class="form-label">Descrição</label>
                                <div class="">
                                    <input class="form-control" id="descricao" type="text" name="descricao" value="<?php echo set_value('descricao'); ?>" placeholder="O que este serviço inclui" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ui-secao">
                        <h4 class="ui-secao-titulo">Dados fiscais (NFS-e)</h4>
                        <div class="ui-grade">
                            <div class="mb-3">
                                <label for="codigo_servico_municipio" class="form-label">Código de Tributação Nacional <small>(NFS-e)</small></label>
                                <div class="">
                                    <input class="form-control" id="codigo_servico_municipio" type="text" name="codigo_servico_municipio" maxlength="6" list="listaCtribnac" autocomplete="off" value="<?php echo set_value('codigo_servico_municipio'); ?>" placeholder="Digite para ver sugestões (ex.: 010701)" />
                                    <?php $this->load->view('nfe/_datalist_ctribnac'); ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="codigo_tributacao_municipal" class="form-label">Código de Tributação Municipal <small>(NFS-e)</small></label>
                                <div class="">
                                    <input class="form-control" id="codigo_tributacao_municipal" type="text" name="codigo_tributacao_municipal" maxlength="3" value="<?php echo set_value('codigo_tributacao_municipal'); ?>" placeholder="3 dígitos, quando o município exigir" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php $this->load->view('tributacao/_classificacao_item', ['tipo' => 'servico', 'item' => null]); ?>

                    <div class="ui-rodape-form">
                        <button type="submit" class="button btn btn-sm btn-success">
                            <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Adicionar</span></button>
                        <a href="<?php echo base_url() ?>index.php/servicos" id="btnAdicionar" class="button btn btn-sm btn-warning">
                            <span class="button__icon"><i class="bx bx-undo"></i></span><span class="button__text2">Voltar</span></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script nonce="<?= csp_nonce() ?>" src="<?php echo base_url() ?>assets/js/jquery.validate.js"></script>
<script nonce="<?= csp_nonce() ?>" src="<?php echo base_url(); ?>assets/js/maskmoney.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function() {
        $(".money").maskMoney();
        $('#formServico').validate({
            rules: {
                nome: {
                    required: true
                },
                preco: {
                    required: true
                }
            },
            messages: {
                nome: {
                    required: 'Campo Requerido.'
                },
                preco: {
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
    });
</script>
