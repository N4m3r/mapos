<div class="widget-box">
    <div class="widget-title">
        <span class="icon"><i class="bx bx-time"></i></span>
        <h5>Relatório de Atendimentos</h5>
        <div class="buttons" style="margin-top: 5px; margin-right: 10px;">
            <a href="<?php echo site_url('dashboard'); ?>" class="btn btn-mini" style="color:#fff!important">
                <i class="bx bx-arrow-back"></i> Voltar ao Dashboard
            </a>
        </div>
    </div>

    <div class="widget-content">
        <!-- Filtros -->
        <form method="get" action="<?php echo site_url('dashboard/relatorio_atendimentos'); ?>" class="form-inline" style="margin-bottom: 20px;">
            <div class="row-fluid">
                <div class="span3">
                    <label>Data Início:</label>
                    <input type="date" name="data_inicio" class="span12" value="<?php echo $data_inicio; ?>" />
                </div>
                <div class="span3">
                    <label>Data Fim:</label>
                    <input type="date" name="data_fim" class="span12" value="<?php echo $data_fim; ?>" />
                </div>
                <div class="span3">
                    <label>Técnico:</label>
                    <select name="tecnico_id" class="span12">
                        <option value="">Todos os Técnicos</option>
                        <?php foreach ($tecnicos as $tecnico): ?>
                            <option value="<?php echo $tecnico->idUsuarios; ?>" <?php echo ($tecnico_id == $tecnico->idUsuarios) ? 'selected' : ''; ?>>
                                <?php echo $tecnico->nome; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="span3">
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
                <a href="<?php echo site_url('dashboard/exportar?tipo=atendimentos&data_inicio=' . $data_inicio . '&data_fim=' . $data_fim); ?>"
                   class="btn btn-success">
                    <i class="bx bx-download"></i> Exportar CSV
                </a>
            </div>
        <?php endif; ?>

        <!-- Tabela de Atendimentos -->
        <div class="row-fluid">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>OS</th>
                        <th>Cliente</th>
                        <th>Técnico</th>
                        <th>Status</th>
                        <th>Data Inicial</th>
                        <th>Data Final</th>
                        <th>Valor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($atendimentos)): ?>
                        <?php foreach ($atendimentos as $a): ?>
                            <tr>
                                <td><?php echo $a->idOs; ?></td>
                                <td><?php echo $a->nomeCliente; ?></td>
                                <td><?php echo $a->nome_tecnico ?: 'Não atribuído'; ?></td>
                                <td>
                                    <span class="label" style="background-color: <?php echo $this->mapos_model->getStatusCor($a->status); ?>">
                                        <?php echo $a->status; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($a->dataInicial)); ?></td>
                                <td><?php echo $a->dataFinal ? date('d/m/Y', strtotime($a->dataFinal)) : '-'; ?></td>
                                <td>R$ <?php echo number_format($a->valorTotal, 2, ',', '.'); ?></td>
                                <td>
                                    <a href="<?php echo site_url('os/visualizar/' . $a->idOs); ?>" class="btn btn-mini btn-info" title="Visualizar">
                                        <i class="bx bx-show"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">Nenhum atendimento encontrado no período.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
