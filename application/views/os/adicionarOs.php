<link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery.validate.js"></script>

<link rel="stylesheet" href="<?php echo base_url() ?>assets/trumbowyg/ui/trumbowyg.css">
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/trumbowyg.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/langs/pt_br.js"></script>

<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/custom.css" />

<div class="ui-cabecalho">
    <div>
        <h1><i class="fas fa-diagnoses"></i> Nova Ordem de Serviço</h1>
        <p>Informe cliente, responsável e prazos. Os campos com * são obrigatórios; produtos e serviços são adicionados no próximo passo.</p>
    </div>
</div>
<link rel="stylesheet" href="<?= base_url('assets/css/os-form.css') ?>?v=<?= asset_versao('assets/css/os-form.css') ?>" />
<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-content os-nova" id="divCadastrarOs">
                <?php if ($custom_error == true) { ?>
                    <div class="alert alert-danger" id="divInfo">Dados incompletos: verifique os campos com asterisco e se selecionou cliente, responsável e termo de garantia na lista de sugestões.</div>
                <?php } ?>
                <form action="<?php echo current_url(); ?>" method="post" id="formOs">
                    <div class="os-card">
                        <h3 class="os-card-titulo"><i class="bx bx-user"></i> Cliente e responsável</h3>
                        <div class="os-grade">
                            <div class="os-campo os-col-2">
                                <label for="cliente">Cliente<span class="required">*</span></label>
                                <input id="cliente" type="text" name="cliente" value="" placeholder="Digite o nome para buscar" autocomplete="off" />
                                <input id="clientes_id" type="hidden" name="clientes_id" value="" />
                            </div>
                            <div class="os-campo os-col-2">
                                <label for="tecnico">Técnico / Responsável<span class="required">*</span></label>
                                <input id="tecnico" type="text" name="tecnico" value="<?= html_escape($this->session->userdata('nome_admin')); ?>" autocomplete="off" />
                                <input id="usuarios_id" type="hidden" name="usuarios_id" value="<?= $this->session->userdata('id_admin'); ?>" />
                            </div>
                        </div>
                    </div>

                    <div class="os-card">
                        <h3 class="os-card-titulo"><i class="bx bx-calendar"></i> Situação e prazos</h3>
                        <div class="os-grade">
                            <div class="os-campo">
                                <label for="status">Status<span class="required">*</span></label>
                                <select name="status" id="status">
                                    <option value="Aberto">Aberto</option>
                                    <option value="Orçamento">Orçamento</option>
                                    <option value="Negociação">Negociação</option>
                                    <option value="Aprovado">Aprovado</option>
                                    <option value="Aguardando Peças">Aguardando Peças</option>
                                    <option value="Em Andamento">Em Andamento</option>
                                    <option value="Finalizado">Finalizado</option>
                                    <option value="Faturado">Faturado</option>
                                    <option value="Cancelado">Cancelado</option>
                                </select>
                            </div>
                            <div class="os-campo">
                                <label for="tipo_servico">Tipo de serviço</label>
                                <select name="tipo_servico" id="tipo_servico">
                                    <option value="">Não informado</option>
                                    <?php $this->load->helper('tipo_servico'); foreach (tipos_servico() as $k => $t) { ?>
                                        <option value="<?= $k ?>"><?= html_escape($t['rotulo']) ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="os-campo">
                                <label for="chamado_id">Nº do chamado</label>
                                <input id="chamado_id" type="text" name="chamado_id" maxlength="60" value="<?= html_escape(set_value('chamado_id')) ?>" placeholder="Protocolo informado pelo cliente" />
                            </div>
                            <div class="os-campo">
                                <label for="dataInicial">Data inicial<span class="required">*</span></label>
                                <input id="dataInicial" autocomplete="off" class="datepicker" type="text" name="dataInicial" value="<?php echo date('d/m/Y'); ?>" />
                            </div>
                            <div class="os-campo">
                                <label for="dataFinal">Data final<span class="required">*</span></label>
                                <input id="dataFinal" autocomplete="off" class="datepicker" type="text" name="dataFinal" value="" placeholder="dd/mm/aaaa" />
                            </div>
                            <div class="os-campo">
                                <label for="garantia">Garantia (dias)</label>
                                <input id="garantia" type="number" placeholder="0 = sem garantia" min="0" max="9999" name="garantia" value="" inputmode="numeric" />
                                <?php echo form_error('garantia'); ?>
                            </div>
                            <div class="os-campo os-col-2">
                                <label for="termoGarantia">Termo de garantia</label>
                                <input id="termoGarantia" type="text" name="termoGarantia" value="" placeholder="Digite para buscar um termo cadastrado" autocomplete="off" />
                                <input id="garantias_id" type="hidden" name="garantias_id" value="" />
                            </div>
                        </div>
                    </div>

                    <div class="os-card">
                        <h3 class="os-card-titulo"><i class="bx bx-detail"></i> Descrição e diagnóstico</h3>
                        <div class="os-editores">
                            <div class="os-campo">
                                <label for="descricaoProduto">Descrição do produto/serviço</label>
                                <textarea class="editor" name="descricaoProduto" id="descricaoProduto" rows="5"></textarea>
                            </div>
                            <div class="os-campo">
                                <label for="defeito">Defeito relatado</label>
                                <textarea class="editor" name="defeito" id="defeito" rows="5"></textarea>
                            </div>
                            <div class="os-campo">
                                <label for="observacoes">Observações</label>
                                <textarea class="editor" name="observacoes" id="observacoes" rows="5"></textarea>
                            </div>
                            <div class="os-campo">
                                <label for="laudoTecnico">Laudo técnico</label>
                                <textarea class="editor" name="laudoTecnico" id="laudoTecnico" rows="5"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="os-acoes">
                        <a href="<?php echo base_url() ?>index.php/os" class="btn btn-warning"><i class="bx bx-undo"></i> Voltar</a>
                        <button type="submit" class="btn btn-success" id="btnContinuar"><i class="bx bx-chevrons-right"></i> Continuar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function() {
        $("#cliente").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteCliente",
            minLength: 1,
            select: function(event, ui) {
                $("#clientes_id").val(ui.item.id);
            }
        });
        $("#tecnico").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteUsuario",
            minLength: 1,
            select: function(event, ui) {
                $("#usuarios_id").val(ui.item.id);
            }
        });
        $("#termoGarantia").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteTermoGarantia",
            minLength: 1,
            select: function(event, ui) {
                $("#garantias_id").val(ui.item.id);
            }
        });

        $("#formOs").validate({
            rules: {
                cliente: {
                    required: true
                },
                tecnico: {
                    required: true
                },
                dataInicial: {
                    required: true
                },
                dataFinal: {
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
                dataInicial: {
                    required: 'Campo Requerido.'
                },
                dataFinal: {
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
        $(".datepicker").datepicker({
            dateFormat: 'dd/mm/yy'
        });
        $('.editor').trumbowyg({
            lang: 'pt_br',
            semantic: { 'strikethrough': 's', }
        });

        // Rascunho automatico (depois do validate, para respeitar envio barrado)
        if (window.Rascunho) {
            Rascunho.formulario('#formOs', 'os_nova_<?= (int) $this->session->userdata('id_admin') ?>', { editores: '.editor' });
        }
    });
</script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/rascunho-form.js') ?>?v=<?= asset_versao('assets/js/rascunho-form.js') ?>"></script>
