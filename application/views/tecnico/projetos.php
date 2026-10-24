<?php
$this->load->view('tecnico/_topo', [
    'titulo'       => 'Meus Projetos',
    'header_icone' => 'bx-network-chart',
    'header_sub'   => 'Projetos em que você atua',
]);
$statusLabels = ['planejamento' => 'Planejamento', 'em_execucao' => 'Em execução', 'paralisada' => 'Paralisada', 'concluida' => 'Concluída', 'entregue' => 'Entregue', 'cancelada' => 'Cancelada'];
?>

<div class="tec-container">

    <?php if ($this->session->flashdata('success')): ?>
        <div class="info-card" style="border-left:4px solid var(--tec-success, #2ecc71); color:var(--tec-success, #2ecc71);"><i class='bx bx-check-circle'></i> <?= html_escape($this->session->flashdata('success')) ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="info-card" style="border-left:4px solid var(--tec-danger, #e74c3c); color:var(--tec-danger, #e74c3c);"><i class='bx bx-error-circle'></i> <?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>

    <h2 class="tec-section-title">
        <i class='bx bx-network-chart'></i> Projetos
        <?php if (!empty($projetos)): ?><span class="count"><?= count($projetos) ?></span><?php endif; ?>
    </h2>

    <?php if (!empty($projetos)): ?>
        <?php foreach ($projetos as $p): ?>
            <a href="<?= site_url('tecnico/projeto/' . $p->idObra) ?>" style="text-decoration:none; color:inherit; display:block;">
                <div class="os-card">
                    <div class="os-head">
                        <span class="os-num"><i class='bx bx-network-chart'></i> <?= html_escape($p->nome) ?></span>
                        <span class="badge-status pendente"><?= html_escape($statusLabels[$p->status] ?? $p->status) ?></span>
                    </div>
                    <?php if (!empty($p->codigo)): ?><div class="os-desc">Código: <?= html_escape($p->codigo) ?></div><?php endif; ?>
                    <div class="os-cliente"><i class='bx bx-user'></i> <?= html_escape($p->nomeCliente ?: '—') ?></div>
                    <div style="margin:8px 0 4px;">
                        <div style="background:#e9ecef;border-radius:6px;height:8px;overflow:hidden;">
                            <div style="background:#2b7;height:8px;width:<?= (float) $p->percentual_concluido ?>%;"></div>
                        </div>
                        <small style="color:#888;"><?= number_format((float) $p->percentual_concluido, 1, ',', '.') ?>% concluído</small>
                    </div>
                    <div class="os-foot">
                        <span class="os-meta"><span><i class='bx bx-map'></i> <?= html_escape($p->cidade ?: '—') ?></span></span>
                        <span class="btn-tec primary">Abrir <i class='bx bx-right-arrow-alt'></i></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bx-folder-open'></i>
            <p>Você ainda não tem projetos vinculados.<br>Peça ao gestor para dar acesso a você em um projeto.</p>
        </div>
    <?php endif; ?>
</div>

<?php $this->load->view('tecnico/_nav', ['nav_ativo' => 'projetos', 'pode_ver_sistema' => isset($pode_ver_sistema) ? $pode_ver_sistema : false]); ?>
</body>
</html>
