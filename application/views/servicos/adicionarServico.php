<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-content nopadding tab-content" style="padding: 16px">
                <div class="ui-cabecalho">
                    <div>
                        <h1 class="ui-titulo"><i class="fas fa-wrench"></i> Novo serviço</h1>
                        <p class="ui-subtitulo">Nome, preço e dados fiscais para emissão de NFS-e. Campos com * são obrigatórios.</p>
                    </div>
                </div>
                <?php echo $custom_error; ?>
                <form action="<?php echo current_url(); ?>" id="formServico" method="post">
                    <div class="ui-secao">
                    <h4 class="ui-secao-titulo">Dados do serviço</h4>
                    <div class="ui-grade">
                    <div class="control-group">
                        <label for="nome" class="control-label">Nome<span class="required">*</span></label>
                        <div class="controls">
                            <input id="nome" type="text" name="nome" value="<?php echo set_value('nome'); ?>" />
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="preco" class="control-label"><span class="required">Preço*</span></label>
                        <div class="controls">
                            <input id="preco" class="money" data-affixes-stay="true" data-thousands="" data-decimal="." type="text" name="preco" value="<?php echo set_value('preco'); ?>" />
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="descricao" class="control-label">Descrição</label>
                        <div class="controls">
                            <input id="descricao" type="text" name="descricao" value="<?php echo set_value('descricao'); ?>" />
                        </div>
                    </div>
                    </div>
                    </div>
                    <div class="ui-secao">
                    <h4 class="ui-secao-titulo">Dados fiscais (NFS-e)</h4>
                    <div class="ui-grade">
                    <div class="control-group">
                        <label for="codigo_servico_municipio" class="control-label">Cód. Tributação Nacional <small>(NFS-e)</small></label>
                        <div class="controls">
                            <input id="codigo_servico_municipio" type="text" name="codigo_servico_municipio" maxlength="6" list="listaCtribnac" autocomplete="off" value="<?php echo set_value('codigo_servico_municipio'); ?>" placeholder="Digite p/ sugestões (ex: 010701 = suporte em informática)" />
                            <?php $this->load->view('nfe/_datalist_ctribnac'); ?>
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="codigo_tributacao_municipal" class="control-label">Cód. Tributação Municipal <small>(NFS-e)</small></label>
                        <div class="controls">
                            <input id="codigo_tributacao_municipal" type="text" name="codigo_tributacao_municipal" maxlength="3" value="<?php echo set_value('codigo_tributacao_municipal'); ?>" placeholder="3 dígitos — exigido por alguns municípios (ex.: Manaus)" />
                        </div>
                    </div>
                    </div>
                    </div>
                    <div class="ui-rodape-form">
                        <button type="submit" class="button btn btn-mini btn-success">
                          <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Adicionar</span></button>
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
