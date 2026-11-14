<div class="ui-cabecalho">
    <div>
        <h1><i class="fas fa-cash-register"></i> Relatórios de vendas</h1>
        <p>Vendas por período, cliente, vendedor e situação.</p>
    </div>
</div>
<div class="ui-dica"><i class="bx bx-info-circle"></i><div>Os relatórios abrem em uma nova aba. Use os rápidos para o caso comum ou os customizáveis para filtrar; escolha PDF para imprimir ou XLS para planilha.</div></div>

<div class="row" style="margin-top: 0">
    <div class="col-md-4">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon">
                    <i class="fas fa-cash-register"></i>
                </span>
                <h5>Relatórios Rápidos</h5>
            </div>
            <div class="widget-content">
                <ul style="flex-direction: row;" class="site-stats">
                    <li><a target="_blank" href="<?php echo base_url() ?>index.php/relatorios/vendasRapid"><i class="fas fa-cash-register"></i> <small>Todas as Vendas - pdf</small></a></li>
                    <li><a target="_blank" href="<?php echo base_url() ?>index.php/relatorios/vendasRapid?format=xls"><i class="fas fa-cash-register"></i> <small>Todas as Vendas - xls</small></a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon">
                    <i class="fas fa-cash-register"></i>
                </span>
                <h5>Relatórios Customizáveis</h5>
            </div>
            <div class="widget-content row">
                <div class="col-md-12 card card-body">
                    <form class="row" target="_blank" action="<?php echo base_url() ?>index.php/relatorios/vendasCustom" method="get">
                        <div class="col-md-12 card card-body"><div class="row">
                            <div class="col-md-6">
                                <label for="">Data de:</label>
                                <input type="date" name="dataInicial" class="w-100 form-control" />
                            </div>
                            <div class="col-md-6">
                                <label for="">até:</label>
                                <input type="date" name="dataFinal" class="w-100 form-control" />
                            </div>
                        </div></div>
                        <div class="col-md-12 card card-body" style="margin-left: 0"><div class="row">
                            <div class="col-md-6">
                                <label for="">Cliente:</label>
                                <input type="text" id="cliente" class="w-100 form-control" />
                                <input type="hidden" name="cliente" id="clienteHide" />
                            </div>
                            <div class="col-md-6">
                                <label for="">Vendedor:</label>
                                <input type="text" id="tecnico" class="w-100 form-control" />
                                <input type="hidden" name="responsavel" id="responsavelHide" />
                            </div>
                        </div></div>

                        <div class="col-md-12 card card-body" style="margin-left: 0">
                            <label for="">Tipo de impressão:</label>
                            <select name="format" class="w-100 form-select">
                                <option value="">PDF</option>
                                <option value="xls">XLS</option>
                            </select>
                        </div>
                        <div class="col-md-12" style="display:flex;justify-content: center">
                            <button type="reset" class="button btn btn-warning">
                                <span class="button__icon"><i class="bx bx-brush-alt"></i></span>
                                <span class="button__text">Limpar</span>
                            </button>
                            <button class="button btn btn-dark">
                                <span class="button__icon"><i class="bx bx-printer"></i></span>
                                <span class="button__text">Imprimir</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>
<script nonce="<?= csp_nonce() ?>" src="<?php echo base_url(); ?>assets/js/maskmoney.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function() {
        $(".money").maskMoney();
        $("#cliente").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteCliente",
            minLength: 2,
            select: function(event, ui) {
                $("#clienteHide").val(ui.item.id);
            }
        });
        $("#tecnico").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteUsuario",
            minLength: 2,
            select: function(event, ui) {
                $("#responsavelHide").val(ui.item.id);
            }
        });
    });
</script>
