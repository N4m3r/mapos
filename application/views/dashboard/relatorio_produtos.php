<div class="widget-box">
    <div class="widget-title">
        <span class="icon"><i class="bx bx-package"></i></span>
        <h5>Relatório de Produtos e Serviços</h5>
        <div class="buttons" style="margin-top: 5px; margin-right: 10px;">
            <a href="<?php echo site_url('dashboard'); ?>" class="btn btn-mini" style="color:#fff!important">
                <i class="bx bx-arrow-back"></i> Voltar ao Dashboard
            </a>
        </div>
    </div>

    <div class="widget-content">
        <!-- Filtros -->
        <form method="get" action="<?php echo site_url('dashboard/relatorio_produtos'); ?>" class="form-inline" style="margin-bottom: 20px;">
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

        <!-- Estoque Crítico -->
        <div class="row-fluid" style="margin-bottom: 30px;">
            <div class="span12">
                <h5><i class="bx bx-error-circle" style="color: #f5576c;"></i> Produtos com Estoque Crítico</h5>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Produto</th>
                            <th>Estoque Atual</th>
                            <th>Estoque Mínimo</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($estoque_critico)): ?>
                            <?php foreach ($estoque_critico as $p): ?>
                                <tr style="background-color: #fff5f5;">
                                    <td><?php echo $p->idProdutos; ?></td>
                                    <td><?php echo $p->descricao; ?></td>
                                    <td><?php echo $p->estoque; ?></td>
                                    <td><?php echo $p->stoqueMinimo; ?></td>
                                    <td><span class="label label-important">Crítico</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Nenhum produto com estoque crítico.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row-fluid">
            <!-- Produtos Mais Vendidos -->
            <div class="span6">
                <h5>Produtos Mais Vendidos</h5>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Qtd Vendida</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($produtos)): ?>
                            <?php foreach ($produtos as $p): ?>
                                <tr>
                                    <td><?php echo $p->descricao; ?></td>
                                    <td><?php echo $p->quantidade; ?></td>
                                    <td>R$ <?php echo number_format($p->total, 2, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">Nenhum produto vendido no período.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Serviços Mais Realizados -->
            <div class="span6">
                <h5>Serviços Mais Realizados</h5>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Serviço</th>
                            <th>Qtd</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($servicos)): ?>
                            <?php foreach ($servicos as $s): ?>
                                <tr>
                                    <td><?php echo $s->nome; ?></td>
                                    <td><?php echo $s->quantidade; ?></td>
                                    <td>R$ <?php echo number_format($s->total, 2, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">Nenhum serviço realizado no período.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
