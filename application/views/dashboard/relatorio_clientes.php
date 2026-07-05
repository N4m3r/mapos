<div class="widget-box">
    <div class="widget-title">
        <span class="icon"><i class="bx bx-user-check"></i></span>
        <h5>Relatório de Clientes</h5>
        <div class="buttons" style="margin-top: 5px; margin-right: 10px;">
            <a href="<?php echo site_url('dashboard'); ?>" class="btn btn-mini" style="color:#fff!important">
                <i class="bx bx-arrow-back"></i> Voltar ao Dashboard
            </a>
        </div>
    </div>

    <div class="widget-content">
        <!-- Filtros -->
        <form method="get" action="<?php echo site_url('dashboard/relatorio_clientes'); ?>" class="form-inline" style="margin-bottom: 20px;">
            <div class="row-fluid">
                <div class="span4">
                    <label>Data Início:</label>
                    <input type="date" name="data_inicio" class="span12" value="<?php echo $data_inicio; ?>" />
                </div>
                <div class="span4">
                    <label>Data Fim:</label>
                    <input type="date" name="data_fim" class="span12" value="<?php echo $data_fim; ?>" />
                </div>
                <div class="span4">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary span12">
                        <i class="bx bx-search"></i> Filtrar
                    </button>
                </div>
            </div>
        </form>

        <!-- Botão Exportar -->
        <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vExportarDados')): ?>
            <div class="row-fluid" style="margin-bottom: 15px;">
                <a href="<?php echo site_url('dashboard/exportar?tipo=clientes&data_inicio=' . $data_inicio . '&data_fim=' . $data_fim); ?>"
                   class="btn btn-success">
                    <i class="bx bx-download"></i> Exportar CSV
                </a>
            </div>
        <?php endif; ?>

        <div class="row-fluid">
            <!-- Lista de Clientes -->
            <div class="span12">
                <h5>Clientes - Análise de Atendimentos</h5>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Total de OS</th>
                            <th>Valor Total</th>
                            <th>Ticket Médio</th>
                            <th>Última OS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($clientes)): ?>
                            <?php foreach ($clientes as $c): ?>
                                <tr>
                                    <td><?php echo $c->nomeCliente; ?></td>
                                    <td><?php echo $c->total_os; ?></td>
                                    <td>R$ <?php echo number_format($c->valor_total, 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($c->ticket_medio, 2, ',', '.'); ?></td>
                                    <td><?php echo $c->ultima_os ? date('d/m/Y', strtotime($c->ultima_os)) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Nenhum cliente com atendimentos no período.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Clientes -->
        <div class="row-fluid" style="margin-top: 30px;">
            <div class="span12">
                <h5><i class="bx bx-trophy" style="color: #f9ca24;"></i> Top 10 Clientes</h5>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr style="background: linear-gradient(135deg, #f9ca24 0%, #f0932b 100%); color: white;">
                            <th># Posição</th>
                            <th>Cliente</th>
                            <th>Total de OS</th>
                            <th>Valor Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($top_clientes)): ?>
                            <?php $pos = 1; foreach ($top_clientes as $c): ?>
                                <tr>
                                    <td><strong># <?php echo $pos++; ?></strong></td>
                                    <td><?php echo $c->nomeCliente; ?></td>
                                    <td><?php echo $c->total_os; ?></td>
                                    <td><strong>R$ <?php echo number_format($c->valor_total, 2, ',', '.'); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">Nenhum dado disponível.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
