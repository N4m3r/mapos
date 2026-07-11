<?php
/**
 * Barra de navegacao inferior (bottom-nav) compartilhada da Area do Tecnico.
 * Variaveis esperadas (passe ao incluir):
 *   $nav_ativo         (string)  - 'home' | 'os'  (item destacado)
 *   $pode_ver_sistema  (bool)    - se true, mostra atalho para o painel principal
 */
$nav_ativo = isset($nav_ativo) ? $nav_ativo : '';
$pode_ver_sistema = isset($pode_ver_sistema) ? $pode_ver_sistema : false;
?>
<nav class="tec-bottomnav">
    <a href="<?= site_url('tecnico') ?>" class="<?= $nav_ativo === 'home' ? 'active' : '' ?>">
        <i class='bx bxs-dashboard'></i>
        <span>Início</span>
    </a>
    <a href="<?= site_url('tecnico/os') ?>" class="<?= $nav_ativo === 'os' ? 'active' : '' ?>">
        <i class='bx bx-list-ul'></i>
        <span>Minhas OS</span>
    </a>
    <?php if ($pode_ver_sistema): ?>
    <a href="<?= base_url() ?>">
        <i class='bx bx-home'></i>
        <span>Sistema</span>
    </a>
    <?php endif; ?>
    <a href="<?= site_url('login/sair') ?>">
        <i class='bx bx-log-out-circle'></i>
        <span>Sair</span>
    </a>
</nav>
<script src="<?= base_url('assets/js/jquery-1.12.4.min.js') ?>"></script>
<script src="<?= base_url('assets/js/bootstrap.min.js') ?>"></script>
