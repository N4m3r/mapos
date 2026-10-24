<link rel="stylesheet" href="<?= base_url() ?>assets/css/obras.css">
<?php
$fmt = function ($v, $d = 2) { return number_format((float) $v, $d, ',', '.'); };
$data_br = function ($x) { return (! empty($x) && $x !== '0000-00-00') ? date('d/m/Y', strtotime($x)) : '—'; };
$perm = function ($p) { return $this->permission->checkPermission($this->session->userdata('permissao'), $p); };
$statusLabels = ['planejamento' => 'Planejamento', 'em_execucao' => 'Em execução', 'paralisada' => 'Paralisada', 'concluida' => 'Concluída', 'entregue' => 'Entregue', 'cancelada' => 'Cancelada'];
?>

<?php $this->load->view('obras/_nav'); ?>

<div class="new122" style="margin-top:0">
    <div class="widget-title" style="margin:-6px 0 8px">
        <span class="icon"><i class="fas fa-network-wired"></i></span>
        <h5>Painel de Projetos</h5>
    </div>
    <?php if ($perm('cObras')): ?>
        <a href="<?= site_url('obras/adicionar') ?>" class="button btn btn-mini btn-success" style="max-width:160px">
            <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Novo Projeto</span></a>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="obra-kpis">
        <div class="obra-kpi"><div class="k-icon" style="background:#eef2ff;color:#4f46e5"><i class='bx bx-building-house'></i></div><div><div class="k-num"><?= (int) $kpis['total'] ?></div><div class="k-lbl">Projetos cadastrados</div></div></div>
        <div class="obra-kpi"><div class="k-icon" style="background:#e7f5ec;color:#2e7d32"><i class='bx bx-loader-circle'></i></div><div><div class="k-num"><?= (int) $kpis['em_execucao'] ?></div><div class="k-lbl">Em execução</div></div></div>
        <div class="obra-kpi"><div class="k-icon" style="background:#fff4e5;color:#b26a00"><i class='bx bx-pause-circle'></i></div><div><div class="k-num"><?= (int) $kpis['paralisadas'] ?></div><div class="k-lbl">Paralisadas</div></div></div>
        <div class="obra-kpi"><div class="k-icon" style="background:#e6f7fb;color:#0277bd"><i class='bx bx-check-circle'></i></div><div><div class="k-num"><?= (int) $kpis['concluidas'] ?></div><div class="k-lbl">Concluídas</div></div></div>
        <div class="obra-kpi"><div class="k-icon" style="background:#f3e8fd;color:#7b1fa2"><i class='bx bx-trending-up'></i></div><div><div class="k-num"><?= $fmt($kpis['progresso_medio'], 1) ?>%</div><div class="k-lbl">Progresso médio</div></div></div>
        <div class="obra-kpi"><div class="k-icon" style="background:#fdeaea;color:#c62828"><i class='bx bx-receipt'></i></div><div><div class="k-num"><?= (int) $kpis['medicoes_abertas'] ?></div><div class="k-lbl">Medições a aprovar</div></div></div>
        <div class="obra-kpi"><div class="k-icon" style="background:#e7f5ec;color:#2e7d32"><i class='bx bx-dollar-circle'></i></div><div><div class="k-num">R$ <?= $fmt($kpis['valor_contratos']) ?></div><div class="k-lbl">Em contratos</div></div></div>
        <div class="obra-kpi"><div class="k-icon" style="background:#eef2ff;color:#4f46e5"><i class='bx bx-box'></i></div><div><div class="k-num"><?= $fmt($kpis['custodia_cliente'], 0) ?></div><div class="k-lbl">Material do cliente (custódia)</div></div></div>
    </div>

    <div class="row-fluid">
        <!-- Obras em execução -->
        <div class="span7" style="margin-left:0">
            <div class="widget-box"><div class="widget-title"><h5><i class='bx bx-loader-circle'></i> Projetos em execução</h5></div>
                <div class="widget-content nopadding">
                    <table class="table table-bordered">
                        <thead><tr><th>Projeto</th><th>Cliente</th><th>Progresso</th><th></th></tr></thead>
                        <tbody>
                            <?php if (! $obras_execucao) echo '<tr><td colspan="4">Nenhum projeto em execução.</td></tr>'; ?>
                            <?php foreach ($obras_execucao as $o): ?>
                                <tr>
                                    <td><?= html_escape($o->nome) ?></td>
                                    <td><?= html_escape($o->nomeCliente ?: '—') ?></td>
                                    <td style="min-width:120px">
                                        <div class="obra-progress mini"><div class="obra-progress-bar" style="width:<?= (float) $o->percentual_concluido ?>%"></div></div>
                                        <small><?= $fmt($o->percentual_concluido, 1) ?>%</small>
                                    </td>
                                    <td><a href="<?= site_url('obras/visualizar/' . $o->idObra) ?>" class="btn-nwe" title="Abrir"><i class="bx bx-show bx-xs"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Medições a aprovar -->
        <div class="span5">
            <div class="widget-box"><div class="widget-title"><h5><i class='bx bx-receipt'></i> Medições aguardando aprovação</h5></div>
                <div class="widget-content nopadding">
                    <table class="table table-bordered">
                        <thead><tr><th>Projeto</th><th>Nº</th><th>Valor</th><th></th></tr></thead>
                        <tbody>
                            <?php if (! $medicoes_abertas) echo '<tr><td colspan="4">Nenhuma medição pendente.</td></tr>'; ?>
                            <?php foreach ($medicoes_abertas as $m): ?>
                                <tr>
                                    <td><?= html_escape($m->obra_nome) ?></td>
                                    <td><?= (int) $m->numero ?></td>
                                    <td>R$ <?= $fmt($m->valor_medido) ?></td>
                                    <td><a href="<?= site_url('obras/visualizar/' . $m->obra_id) ?>#medicao" class="btn-nwe" title="Ver"><i class="bx bx-right-arrow-alt bx-xs"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- RDOs recentes -->
    <div class="widget-box"><div class="widget-title"><h5><i class='bx bx-notepad'></i> Diários de Obra recentes</h5></div>
        <div class="widget-content nopadding">
            <table class="table table-bordered">
                <thead><tr><th>Data</th><th>Projeto</th><th>Nº</th><th>Condição</th><th>Efetivo</th><th>Responsável</th></tr></thead>
                <tbody>
                    <?php if (! $rdos_recentes) echo '<tr><td colspan="6">Nenhum RDO registrado.</td></tr>'; ?>
                    <?php foreach ($rdos_recentes as $r): ?>
                        <tr>
                            <td><?= $data_br($r->data) ?></td>
                            <td><a href="<?= site_url('obras/visualizar/' . $r->obra_id) ?>#rdo"><?= html_escape($r->obra_nome) ?></a></td>
                            <td><?= (int) $r->numero ?></td>
                            <td><?= html_escape($r->condicao) ?></td>
                            <td><?= (int) $r->efetivo_total ?></td>
                            <td><?= html_escape($r->responsavel) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
