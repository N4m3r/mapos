<div class="ui-cabecalho">
    <div>
        <h1><i class="fas fa-users"></i> Relatórios de clientes</h1>
        <p>Lista de clientes e fornecedores, completa ou por data de cadastro.</p>
    </div>
</div>
<div class="ui-dica"><i class="bx bx-info-circle"></i><div>Os relatórios abrem em uma nova aba. Use os rápidos para o caso comum ou os customizáveis para filtrar; escolha PDF para imprimir ou XLS para planilha.</div></div>

<div class="row" style="margin-top: 0">
    <div class="col-md-4">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon">
                    <i class="fas fa-users"></i>
                </span>
                <h5>Relatórios Rápidos</h5>
            </div>
            <div class="widget-content">
                <ul style="flex-direction: row;" class="site-stats">
                    <li><a href="<?php echo base_url() ?>index.php/relatorios/clientesRapid" target="_blank"><i
                                    class="fas fa-users"></i> <small>Todos os Clientes - pdf</small></a></li>
                    <li><a href="<?php echo base_url() ?>index.php/relatorios/clientesRapid?format=xls" target="_blank"><i
                                    class="fas fa-users"></i> <small>Todos os Clientes - xls</small></a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon">
                    <i class="fas fa-users"></i>
                </span>
                <h5>Relatórios Customizáveis</h5>
            </div>
            <div class="widget-content row">
                <div class="col-md-12 card card-body">
                    <form class="row" target="_blank" action="<?php echo base_url() ?>index.php/relatorios/clientesCustom"
                          method="get">
                        <div class="col-md-4">
                            <label for="">Cadastrado de:</label>
                            <input type="date" name="dataInicial" class="w-100 form-control"/>
                        </div>
                        <div class="col-md-4">
                            <label for="">até:</label>
                            <input type="date" name="dataFinal" class="w-100 form-control"/>
                        </div>
                        <div class="col-md-4">
                            <label for="">.</label>
                            <button class="button btn btn-dark"><span class="button__icon"><i class="bx bx-printer"></i></span> <span class="button__text2">Imprimir</span></button>
                        </div>
                        <div class="col-md-12 card card-body" style="margin-left: 0"><div class="row">
                        <div class="col-md-12">
                            <label for="">Tipo de cliente:</label>
                            <select name="tipocliente" class="w-100 form-select">
                                <option value="0">Cliente</option>
                                <option value="1">Fornecedor</option>
                            </select>
                        </div>
                    </div></div>
                    </form>
                </div>
                .
            </div>
        </div>
    </div>
</div>
