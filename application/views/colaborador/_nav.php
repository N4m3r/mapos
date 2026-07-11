<?php
/**
 * Bottom-nav da Área do Colaborador.
 * Variáveis: $nav_ativo ('home'|'ponto'|'espelho'|'solicitacoes'), $pode_bater_ponto
 */
$nav_ativo = isset($nav_ativo) ? $nav_ativo : '';
$pode_bater_ponto = isset($pode_bater_ponto) ? $pode_bater_ponto : true;
?>
<nav class="rh-bottomnav">
    <a href="<?= site_url('colaborador') ?>" class="<?= $nav_ativo === 'home' ? 'active' : '' ?>">
        <i class='bx bxs-dashboard'></i><span>Início</span>
    </a>
    <?php if ($pode_bater_ponto): ?>
    <a href="<?= site_url('ponto') ?>" class="<?= $nav_ativo === 'ponto' ? 'active' : '' ?>">
        <i class='bx bx-fingerprint'></i><span>Ponto</span>
    </a>
    <?php endif; ?>
    <a href="<?= site_url('colaborador/espelho') ?>" class="<?= $nav_ativo === 'espelho' ? 'active' : '' ?>">
        <i class='bx bx-calendar-check'></i><span>Espelho</span>
    </a>
    <a href="<?= site_url('colaborador/ausencias') ?>" class="<?= $nav_ativo === 'solicitacoes' ? 'active' : '' ?>">
        <i class='bx bx-envelope'></i><span>Solicitar</span>
    </a>
    <a href="<?= site_url('login/sair') ?>">
        <i class='bx bx-log-out-circle'></i><span>Sair</span>
    </a>
</nav>
<script src="<?= base_url('assets/js/jquery-1.12.4.min.js') ?>"></script>
