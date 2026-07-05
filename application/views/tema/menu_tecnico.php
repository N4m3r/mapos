<?php
/**
 * Menu exclusivo para técnicos
 * Este menu é carregado automaticamente quando o usuário tem permissão de técnico
 */
?>
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

    <!-- Start Pesquisar-->
    <li class="search-box">
        <form style="display: flex" action="<?= site_url('mapos/pesquisar') ?>">
            <button style="background:transparent;border:transparent" type="submit" class="tip-bottom" title="">
                <i class='bx bx-search iconX'></i></button>
            <input style="background:transparent;color:#fff;border:transparent" type="search" name="termo" placeholder="Pesquise aqui...">
            <span class="title-tooltip">Pesquisar</span>
        </form>
    </li>
    <!-- End Pesquisar-->

    <div class="menu-bar">
        <div class="menu">
            <ul class="menu-links" style="position: relative;">

                <!-- Home / Dashboard -->
                <li class="<?php if (isset($menuPainel) || isset($menuHome)) { echo 'active'; }; ?>">
                    <a class="tip-bottom" title="" href="<?= site_url('tecnico') ?>">
                        <i class='bx bx-home-alt iconX'></i>
                        <span class="title nav-title">Home</span>
                        <span class="title-tooltip">Início</span>
                    </a>
                </li>

                <!-- Minhas Ordens de Serviço -->
                <li class="<?php if (isset($menuMinhasOs) || isset($menuOs)) { echo 'active'; }; ?>">
                    <a class="tip-bottom" title="" href="<?= site_url('tecnico/os') ?>">
                        <i class='bx bx-file iconX'></i>
                        <span class="title">Ordens de Serviço</span>
                        <span class="title-tooltip">Minhas OS</span>
                    </a>
                </li>

                <!-- Produtos (Visualização) -->
                <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vProduto')) { ?>
                <li class="<?php if (isset($menuProdutos)) { echo 'active'; }; ?>">
                    <a class="tip-bottom" title="" href="<?= site_url('produtos') ?>">
                        <i class='bx bx-basket iconX'></i>
                        <span class="title">Produtos</span>
                        <span class="title-tooltip">Produtos</span>
                    </a>
                </li>
                <?php } ?>

                <!-- Serviços (Visualização) -->
                <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vServico')) { ?>
                <li class="<?php if (isset($menuServicos)) { echo 'active'; }; ?>">
                    <a class="tip-bottom" title="" href="<?= site_url('servicos') ?>">
                        <i class='bx bx-wrench iconX'></i>
                        <span class="title">Serviços</span>
                        <span class="title-tooltip">Serviços</span>
                    </a>
                </li>
                <?php } ?>

                <!-- Clientes (Visualização) -->
                <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vCliente')) { ?>
                <li class="<?php if (isset($menuClientes)) { echo 'active'; }; ?>">
                    <a class="tip-bottom" title="" href="<?= site_url('clientes') ?>">
                        <i class='bx bx-user iconX'></i>
                        <span class="title">Clientes</span>
                        <span class="title-tooltip">Clientes</span>
                    </a>
                </li>
                <?php } ?>

                <!-- Separador -->
                <li style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
                </li>

                <!-- Voltar ao Sistema Principal (se tiver permissão) -->
                <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vOs') &&
                          !$this->permission->checkPermission($this->session->userdata('permissao'), 'aTecnico')) { ?>
                <li>
                    <a class="tip-bottom" title="" href="<?= base_url() ?>">
                        <i class='bx bx-arrow-back iconX'></i>
                        <span class="title">Voltar ao Painel</span>
                        <span class="title-tooltip">Principal</span>
                    </a>
                </li>
                <?php } ?>

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
