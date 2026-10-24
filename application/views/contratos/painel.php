<link rel="stylesheet" href="<?= base_url() ?>assets/css/obras.css">
<?php
$fmt = function ($v, $d = 2) { return number_format((float) $v, $d, ',', '.'); };
$perm = function ($p) { return $this->permission->checkPermission($this->session->userdata('permissao'), $p); };
$tipos = ['mensal' => 'Mensal', 'trimestral' => 'Trimestral', 'semestral' => 'Semestral', 'anual' => 'Anual'];
?>

<div class="new122" style="margin-top:0">
    <div class="widget-title" style="margin:-6px 0 8px">
        <span class="icon"><i class="fas fa-file-signature"></i></span>
        <h5>Painel de Contratos</h5>
    </div>

    <?php $this->load->view('contratos/_nav'); ?>

    <!-- KPIs -->
    <div class="obra-kpis">
        <div class="obra-kpi"><div class="k-icon" style="background:#eef2ff;color:#4f46e5"><i class='bx bx-file'></i></div><div><div class="k-num"><?= (int) $kpis['ativos'] ?></div><div class="k-lbl">Contratos ativos</div></div></div>
        <div class="obra-kpi"><div class="k-icon" style="background:#e7f5ec;color:#2e7d32"><i class='bx bx-dollar-circle'></i></div><div><div class="k-num">R$ <?= $fmt($kpis['mrr']) ?></div><div class="k-lbl">Receita recorrente / mês (MRR)</div></div></div>
        <div class="obra-kpi"><div class="k-icon" style="background:#fdeaea;color:#c62828"><i class='bx bx-time-five'></i></div><div><div class="k-num"><?= (int) $kpis['sla_estourados'] ?></div><div class="k-lbl">SLA estourado (em aberto)</div></div></div>
    </div>

    <div class="widget-box">
        <div class="widget-title"><h5><i class='bx bx-file'></i> Contratos ativos</h5></div>
        <div class="widget-content nopadding">
            <table class="table table-bordered">
                <thead><tr><th>Código</th><th>Descrição</th><th>Cliente</th><th>Tipo</th><th>Valor</th><th>OS</th><th></th></tr></thead>
                <tbody>
                    <?php if (! $recentes) echo '<tr><td colspan="7">Nenhum contrato ativo. Cadastre o primeiro.</td></tr>'; ?>
                    <?php foreach ($recentes as $c): ?>
                        <tr>
                            <td><?= html_escape($c->codigo ?: '#' . $c->idContrato) ?></td>
                            <td><?= html_escape($c->descricao) ?></td>
                            <td><?= html_escape($c->nomeCliente ?: '—') ?></td>
                            <td><?= $tipos[$c->tipo] ?? html_escape($c->tipo) ?></td>
                            <td>R$ <?= $fmt($c->valor) ?></td>
                            <td><?= (int) $c->total_os ?></td>
                            <td><a href="<?= site_url('contratos/visualizar/' . $c->idContrato) ?>" class="btn-nwe" title="Abrir"><i class="bx bx-show bx-xs"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
