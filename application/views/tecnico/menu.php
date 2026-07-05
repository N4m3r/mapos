<!--sidebar-menu tecnico-->
<nav id="sidebar" style="background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);">
    <div id="newlog">
        <div class="icon2">
            <img src="<?php echo base_url() ?>assets/img/logo-two.png">
        </div>
        <div class="title1">
            <img src="<?php echo base_url() ?>assets/img/logo-mapos-branco.png">
        </div>
    </div>
    <a href="#" class="visible-phone">
        <div class="mode">
            <div class="moon-menu">
                <i class='bx bx-chevron-right iconX open-2'></i>
                <i class='bx bx-chevron-left iconX close-2'></i>
            </div>
        </div>
    </a>

    <div class="menu-bar">
        <div class="menu">
            <ul class="menu-links" style="position: relative;">

                <!-- Home -->
                <li class="<?php if (isset($menuHome)) { echo 'active'; }; ?>">
                    <a class="tip-bottom" title="" href="<?= site_url('tecnico') ?>">
                        <i class='bx bx-home-alt iconX'></i>
                        <span class="title nav-title">Home</span>
                        <span class="title-tooltip">Início</span>
                    </a>
                </li>

                <!-- Minhas OS -->
                <li class="<?php if (isset($menuMinhasOs)) { echo 'active'; }; ?>">
                    <a class="tip-bottom" title="" href="<?= site_url('tecnico/os') ?>">
                        <i class='bx bx-file iconX'></i>
                        <span class="title">Minhas OS</span>
                        <span class="title-tooltip">Ordens</span>
                    </a>
                </li>

                <!-- Produtos -->
                <li class="<?php if (isset($menuProdutos)) { echo 'active'; }; ?>">
                    <a class="tip-bottom" title="" href="<?= site_url('tecnico/produtos') ?>">
                        <i class='bx bx-basket iconX'></i>
                        <span class="title">Produtos</span>
                        <span class="title-tooltip">Produtos</span>
                    </a>
                </li>

                <!-- Serviços -->
                <li class="<?php if (isset($menuServicos)) { echo 'active'; }; ?>">
                    <a class="tip-bottom" title="" href="<?= site_url('tecnico/servicos') ?>">
                        <i class='bx bx-wrench iconX'></i>
                        <span class="title">Serviços</span>
                        <span class="title-tooltip">Serviços</span>
                    </a>
                </li>

                <!-- Voltar ao Sistema Principal -->
                <li style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
                    <a class="tip-bottom" title="" href="<?= base_url() ?>">
                        <i class='bx bx-arrow-back iconX'></i>
                        <span class="title">Voltar ao Sistema</span>
                        <span class="title-tooltip">Principal</span>
                    </a>
                </li>

            </ul>
        </div>

        <div class="botton-content">
            <li class="">
                <a class="tip-bottom" title="" href="<?= site_url('login/sair'); ?>">
                    <i class='bx bx-log-out-circle iconX'></i>
                    <span class="title">Sair</span>
                    <span class="title-tooltip">Sair</span>
                </a>
            </li>
        </div>
    </div>
</nav>
<!--End sidebar-menu-->
