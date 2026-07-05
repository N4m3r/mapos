<div class="widget-box">
    <div class="widget-title">
        <span class="icon"><i class="bx bx-dollar-circle"></i></span>
        <h5>Relatório Financeiro</h5>
        <div class="buttons" style="margin-top: 5px; margin-right: 10px;">
            <a href="<?php echo site_url('dashboard'); ?>" class="btn btn-mini" style="color:#fff!important">
                <i class="bx bx-arrow-back"></i> Voltar ao Dashboard
            </a>
        </div>
    </div>

    <div class="widget-content">
        <!-- Filtros -->
        <form method="get" action="<?php echo site_url('dashboard/relatorio_financeiro'); ?>" class="form-inline" style="margin-bottom: 20px;">
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

        <!-- Resumo -->
        <div class="row-fluid" style="margin-bottom: 20px;">
            <div class="span4">
                <div class="widget-box" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
                    <div class="widget-content" style="text-align: center;">
                        <h4>Entradas</h4>
                        <h2>R$ <?php echo isset($financeiro['entradas']) ? number_format($financeiro['entradas'], 2, ',', '.') : '0,00'; ?></h2>
                    </div>
                </div>
            </div>

            <div class="span4">
                <div class="widget-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                    <div class="widget-content" style="text-align: center;">
                        <h4>Saídas</h4>
                        <h2>R$ <?php echo isset($financeiro['saidas']) ? number_format($financeiro['saidas'], 2, ',', '.') : '0,00'; ?></h2>
                    </div>
                </div>
            </div>

            <div class="span4">
                <div class="widget-box" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                    <div class="widget-content" style="text-align: center;">
                        <h4>Saldo</h4>
                        <h2>R$ <?php echo isset($financeiro['saldo']) ? number_format($financeiro['saldo'], 2, ',', '.') : '0,00'; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botão Exportar -->
        <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vExportarDados')): ?>
            <div class="row-fluid" style="margin-bottom: 15px;">
                <a href="<?php echo site_url('dashboard/exportar?tipo=financeiro&data_inicio=' . $data_inicio . '&data_fim=' . $data_fim); ?>"
                   class="btn btn-success">
                    <i class="bx bx-download"></i> Exportar CSV
                </a>
            </div>
        <?php endif; ?>

        <!-- Tabela de Lançamentos -->
        <div class="row-fluid">
            <h5>Lançamentos do Período</h5>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Tipo</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($lancamentos)): ?>
                        <?php foreach ($lancamentos as $l): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($l->data_vencimento)); ?></td>
                                <td><?php echo $l->descricao; ?></td>
                                <td>
                                    <span class="label label-<?php echo $l->tipo == 'receita' ? 'success' : 'important'; ?>">
                                        <?php echo ucfirst($l->tipo); ?>
                                    </span>
                                </td>
                                <td>R$ <?php echo number_format($l->valor, 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">Nenhum lançamento encontrado no período.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
