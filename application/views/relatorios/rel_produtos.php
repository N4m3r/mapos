<div class="ui-cabecalho">
    <div>
        <h1><i class="fas fa-shopping-bag"></i> Relatórios de produtos</h1>
        <p>Lista de produtos e posição de estoque.</p>
    </div>
</div>
<div class="ui-dica"><i class="bx bx-info-circle"></i><div>Os relatórios abrem em uma nova aba. Use os rápidos para o caso comum ou os customizáveis para filtrar; escolha PDF para imprimir ou XLS para planilha.</div></div>

<div class="row-fluid" style="margin-top: 0">
    <div class="span4">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon">
                    <i class="fas fa-shopping-bag"></i>
                </span>
                <h5>Relatórios Rápidos</h5>
            </div>
            <div class="widget-content">
                <ul style="flex-direction: row;" class="site-stats">
                    <li><a target="_blank" href="<?php echo base_url() ?>index.php/relatorios/produtosRapid"><i class="fas fa-shopping-bag"></i> <small>Todos os Produtos</small></a></li>
                    <li><a target="_blank" href="<?php echo base_url() ?>index.php/relatorios/produtosRapidMin"><i class="fas fa-shopping-bag"></i> <small>Com Estoque Mínimo</small></a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="span8">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon">
                    <i class="fas fa-shopping-bag"></i>
                </span>
                <h5>Relatórios Customizáveis</h5>
            </div>
            <div class="widget-content">
                <div class="span12 well">
                    <div class="span12 alert alert-info">Deixe em branco caso não deseje utilizar o parâmetro.</div>
                    <form target="_blank" action="<?php echo base_url() ?>index.php/relatorios/produtosCustom" method="get">
                        <div class="span12 well">
                            <div class="span6">
                                <label for="">Preço de Venda de:</label>
                                <input type="text" name="precoInicial" class="span12 money" />
                            </div>
                            <div class="span6">
                                <label for="">até:</label>
                                <input type="text" name="precoFinal" class="span12 money" />
                            </div>
                        </div>
                        <div class="span12 well" style="margin-left: 0">
                            <div class="span6">
                                <label for="">Estoque de:</label>
                                <input type="text" name="estoqueInicial" class="span12" />
                            </div>
                            <div class="span6">
                                <label for="">até:</label>
                                <input type="text" name="estoqueFinal" class="span12" />
                            </div>
                        </div>
                        <div class="span12" style="display: flex; justify-content: center">
                            <button type="reset" class="button btn btn-warning">
                                <span class="button__icon"><i class="bx bx-brush-alt"></i></span>
                                <span class="button__text">Limpar</span>
                            </button>
                            <button class="button btn btn-inverse">
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
<script nonce="<?= csp_nonce() ?>" src="<?php echo base_url(); ?>assets/js/maskmoney.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function() {
        $(".money").maskMoney();
    });
</script>
