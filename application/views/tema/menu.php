<?php
/**
 * Menu lateral unico do layout administrativo.
 *
 * Renderiza o catalogo de application/helpers/menu_navegacao_helper.php: todos
 * os itens ficam aqui, agrupados por area, e ja vem filtrados pela permissao do
 * usuario. O topo (tema/topo.php) nao repete navegacao — so perfil e busca.
 *
 * O modo recolhido (80px) depende de `#sidebar.open` e das spans .title /
 * .title-tooltip; o JavaScript da paleta (Ctrl+K) le os links daqui.
 */
$this->load->helper('menu_navegacao');

$permissao = $this->session->userdata('permissao');
$pode = function ($atividade) use ($permissao) {
    return $this->permission->checkPermission($permissao, $atividade);
};

$grupos = menu_navegacao_para($pode);
$uri = $this->uri->uri_string();
?>
<!--sidebar-menu-->
<nav id="sidebar">
    <div id="newlog">
        <div class="icon2">
            <img src="<?php echo base_url() ?>assets/img/logo-two.png" alt="">
        </div>
        <div class="title1">
            <?= $configuration['app_theme'] == 'white' || $configuration['app_theme'] == 'whitegreen' ? '<img src="' . base_url() . 'assets/img/logo-mapos.png">' : '<img src="' . base_url() . 'assets/img/logo-mapos-branco.png">'; ?>
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
                <input style="background:transparent;<?= $configuration['app_theme'] == 'white' ? 'color:#313030;' : 'color:#fff;' ?>border:transparent" type="search" name="termo" placeholder="Pesquise aqui...">
            <span class="title-tooltip">Pesquisar</span>
        </form>
    </li>
    <!-- End Pesquisar-->

    <div class="menu-bar">
        <div class="menu">

            <ul class="menu-links" style="position: relative;">
                <?php foreach ($grupos as $grupo) { ?>
                    <li class="nv-grupo" aria-hidden="true"><?= html_escape($grupo['titulo']) ?></li>
                    <?php foreach ($grupo['itens'] as $item) { ?>
                        <?php
                        $ativo = menu_navegacao_ativo($uri, $item['rota'], $item['aliases'] ?? []);
                        $destino = $item['rota'] === '' ? base_url() : site_url($item['rota']);
                        ?>
                        <li class="<?= $ativo ? 'active' : '' ?>">
                            <a class="tip-bottom" title="<?= html_escape($item['dica']) ?>"
                               href="<?= $destino ?>"<?= $ativo ? ' aria-current="page"' : '' ?>>
                                <i class='bx <?= html_escape($item['icone']) ?> iconX'></i>
                                <span class="title"><?= html_escape($item['rotulo']) ?></span>
                                <span class="title-tooltip"><?= html_escape($item['dica']) ?></span>
                            </a>
                        </li>
                    <?php } ?>
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
