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
<style>
.os-nova { padding: 18px 20px 0; }
.os-nova .os-card { border: 1px solid var(--ui-borda); border-radius: var(--ui-raio); padding: 16px 18px 18px; margin-bottom: 18px; background: var(--ui-suave); }
.os-nova .os-card-titulo { display: flex; align-items: center; gap: 8px; font-size: 15px; line-height: 1.3; font-weight: 600; margin: 0 0 14px; }
.os-nova .os-card-titulo i { font-size: 18px; color: var(--ui-primaria); }
.os-nova .os-grade { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px 18px; }
.os-nova .os-col-2 { grid-column: span 2; }
.os-nova .os-campo label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 5px; }
.os-nova .os-campo input[type=text], .os-nova .os-campo input[type=number], .os-nova .os-campo select { width: 100%; height: 38px; box-sizing: border-box; margin: 0; }
.os-nova .os-campo .help-inline { display: block; padding: 4px 0 0; }
.os-nova .required { color: var(--ui-perigo); margin-left: 2px; }
.os-nova .os-editores { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
.os-nova .os-editores .os-campo > label { font-size: 14px; }
.os-nova .trumbowyg-box { margin: 0; width: 100%; min-height: 0; border-radius: var(--ui-raio-p); overflow: hidden; }
.os-nova .trumbowyg-editor, .os-nova .trumbowyg-textarea { min-height: 150px; height: 150px; }
.os-nova .trumbowyg-box.trumbowyg-fullscreen .trumbowyg-editor { height: auto; }
.os-nova .os-acoes { position: sticky; bottom: 0; z-index: 20; display: flex; justify-content: flex-end; gap: 10px; margin: 0 -20px; padding: 12px 20px; border-top: 1px solid var(--ui-borda); background: inherit; }
.os-nova .os-acoes .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-width: 150px; height: 42px; padding: 0 18px; line-height: 1; box-sizing: border-box; font-size: 14px; font-weight: 600; margin: 0; }
.os-nova .os-acoes .btn i { font-size: 18px; }
.os-nova .alert { margin: 0 0 16px; }
@media (max-width: 979px) {
    .os-nova .os-grade { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .os-nova .os-editores { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 600px) {
    .os-nova { padding: 12px 12px 0; }
    .os-nova .os-card { padding: 14px 12px; }
    .os-nova .os-grade { grid-template-columns: minmax(0, 1fr); }
    .os-nova .os-col-2 { grid-column: auto; }
    .os-nova .os-campo input[type=text], .os-nova .os-campo input[type=number], .os-nova .os-campo select { height: 44px; font-size: 16px; }
    .os-nova .trumbowyg-editor, .os-nova .trumbowyg-textarea { min-height: 120px; height: 120px; }
    .os-nova .trumbowyg-button-pane { display: flex; flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; }
    .os-nova .trumbowyg-button-pane .trumbowyg-button-group { flex: 0 0 auto; }
    .os-nova .trumbowyg-button-pane::after { display: none; }
    .os-nova .os-acoes { margin: 0 -12px; padding: 10px 12px calc(10px + env(safe-area-inset-bottom)); }
    .os-nova .os-acoes .btn { flex: 1; min-width: 0; height: 48px; }
}
</style>
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
