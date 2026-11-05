<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-content">
                <div class="ui-cabecalho">
                    <div>
                        <h1 class="ui-titulo"><i class="fas fa-wrench"></i> Editar serviço</h1>
                        <p class="ui-subtitulo">Altere o nome, o preço ou os dados fiscais do serviço. Campos com * são obrigatórios.</p>
                    </div>
                </div>

                <?php if (! empty($custom_error)) { ?>
                    <div class="alert alert-danger"><?php echo $custom_error; ?></div>
                <?php } ?>

                <form action="<?php echo current_url(); ?>" id="formServico" method="post">
                    <?php echo form_hidden('idServicos', $result->idServicos) ?>

                    <div class="ui-secao">
                        <h4 class="ui-secao-titulo">Dados do serviço</h4>
                        <div class="ui-grade">
                            <div class="control-group">
                                <label for="nome" class="control-label">Nome<span class="required">*</span></label>
                                <div class="controls">
                                    <input id="nome" type="text" name="nome" value="<?php echo html_escape($result->nome); ?>" />
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="preco" class="control-label">Preço<span class="required">*</span></label>
                                <div class="controls">
                                    <input id="preco" class="money" data-affixes-stay="true" data-thousands="" data-decimal="." type="text" name="preco" value="<?php echo $result->preco ?>" />
                                </div>
                            </div>
                            <div class="control-group" style="grid-column: 1 / -1">
                                <label for="descricao" class="control-label">Descrição</label>
                                <div class="controls">
                                    <input id="descricao" type="text" name="descricao" value="<?php echo html_escape($result->descricao); ?>" placeholder="O que este serviço inclui" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ui-secao">
                        <h4 class="ui-secao-titulo">Dados fiscais (NFS-e)</h4>
                        <div class="ui-grade">
                            <div class="control-group">
                                <label for="codigo_servico_municipio" class="control-label">Código de Tributação Nacional <small>(NFS-e)</small></label>
                                <div class="controls">
                                    <input id="codigo_servico_municipio" type="text" name="codigo_servico_municipio" maxlength="6" list="listaCtribnac" autocomplete="off" value="<?php echo $result->codigo_servico_municipio ?? '' ?>" placeholder="Digite para ver sugestões (ex.: 010701)" />
                                    <?php $this->load->view('nfe/_datalist_ctribnac'); ?>
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="codigo_tributacao_municipal" class="control-label">Código de Tributação Municipal <small>(NFS-e)</small></label>
                                <div class="controls">
                                    <input id="codigo_tributacao_municipal" type="text" name="codigo_tributacao_municipal" maxlength="3" value="<?php echo $result->codigo_tributacao_municipal ?? '' ?>" placeholder="3 dígitos, quando o município exigir" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php $this->load->view('tributacao/_classificacao_item', ['tipo' => 'servico', 'item' => $result]); ?>

                    <div class="ui-rodape-form">
                        <button type="submit" class="button btn btn-mini btn-primary">
                            <span class="button__icon"><i class="bx bx-sync"></i></span><span class="button__text2">Atualizar</span></button>
                        <a href="<?php echo base_url() ?>index.php/servicos" id="btnAdicionar" class="button btn btn-mini btn-warning">
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
    });
</script>
