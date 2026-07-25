<?php
$ativo = isset($obra_menu) ? $obra_menu : '';
$permE = $this->permission->checkPermission($this->session->userdata('permissao'), 'vObras');
?>
<ul class="nav nav-tabs obra-tabs obra-mainnav">
    <li class="<?= $ativo === 'painel' ? 'active' : '' ?>"><a href="<?= site_url('obras/painel') ?>"><i class='bx bx-grid-alt'></i> Painel</a></li>
    <li class="<?= $ativo === 'obras' ? 'active' : '' ?>"><a href="<?= site_url('obras/gerenciar') ?>"><i class='bx bx-network-chart'></i> Projetos</a></li>
    <?php if ($permE): ?>
        <li class="<?= $ativo === 'equipes' ? 'active' : '' ?>"><a href="<?= site_url('obraequipe') ?>"><i class='bx bx-group'></i> Equipes</a></li>
    <?php endif; ?>
</ul>
