<?php
/**
 * Menu lateral unico do layout administrativo.
 *
 * Renderiza o catalogo de application/helpers/menu_navegacao_helper.php: todos
 * os itens ficam aqui, agrupados por area, e ja vem filtrados pela permissao do
 * usuario. O topo (tema/topo.php) nao repete navegacao — so perfil e busca.
 *
 * O modo recolhido (80px) depende de `#sidebar.open` e das spans .title /
 * .title-tooltip; o JavaScript da paleta (Ctrl+K) le os links daqui. Uma tela
 * marca um item so (menu_navegacao_id_ativo), pela rota mais especifica.
 */
$this->load->helper('menu_navegacao');

$permissao = $this->session->userdata('permissao');
$pode = function ($atividade) use ($permissao) {
    return $this->permission->checkPermission($permissao, $atividade);
};

$grupos = menu_navegacao_para($pode);
$uri = $this->uri->uri_string();
$logoEmitente = emitente_logo_url();
$logoEmitenteEsc = $logoEmitente !== null ? htmlspecialchars($logoEmitente, ENT_QUOTES, 'UTF-8') : null;
?>
<!--sidebar-menu-->
<nav id="sidebar">
    <?php if ($logoEmitenteEsc !== null) { ?>
    <!-- Logo do emitente: uma imagem so, que acompanha a largura do menu -->
    <div id="newlog" class="com-logo-emitente">
        <div class="logo-emitente-box">
            <img src="<?= $logoEmitenteEsc ?>" class="logo-emitente" alt="Logo">
        </div>
    </div>
    <?php } else { ?>
    <div id="newlog">
        <div class="icon2">
            <img src="<?php echo base_url() ?>assets/img/logo-two.png" alt="">
        </div>
        <div class="title1">
            <?= $configuration['app_theme'] == 'white' || $configuration['app_theme'] == 'whitegreen' ? '<img src="' . base_url() . 'assets/img/logo-mapos.png">' : '<img src="' . base_url() . 'assets/img/logo-mapos-branco.png">'; ?>
        </div>
    </div>
    <?php } ?>
    <a href="#" class="d-md-none">
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
                <input class="form-control" style="background:transparent;<?= $configuration['app_theme'] == 'white' ? 'color:#313030;' : 'color:#fff;' ?>border:transparent" type="search" name="termo" placeholder="Pesquise aqui...">
            <span class="title-tooltip">Pesquisar</span>
        </form>
    </li>
    <!-- End Pesquisar-->

    <div class="menu-bar">
        <div class="menu">

            <ul class="menu-links" style="position: relative;">
                <?php $ativoId = menu_navegacao_id_ativo($uri, $grupos); ?>
                <?php foreach ($grupos as $chave => $grupo) { ?>
                    <?php
                    // A categoria que contem a tela atual nasce aberta e destacada,
                    // para nao esconder o item marcado atras de um clique.
                    $categoria_atual = false;
                    foreach ($grupo['itens'] as $item_grupo) {
                        if ($item_grupo['id'] === $ativoId) {
                            $categoria_atual = true;
                            break;
                        }
                    }
                    ?>
                    <li class="nv-cat<?= $categoria_atual ? ' nv-cat-atual' : '' ?>" data-cat="<?= html_escape($chave) ?>">
                        <button type="button" class="nv-cat-btn" aria-expanded="<?= $categoria_atual ? 'true' : 'false' ?>" aria-controls="nv-sub-<?= html_escape($chave) ?>">
                            <i class='bx <?= html_escape($grupo['icone']) ?> iconX' aria-hidden="true"></i>
                            <span class="title"><?= html_escape($grupo['titulo']) ?></span>
                            <i class='bx bx-chevron-down nv-cat-seta' aria-hidden="true"></i>
                        </button>
                        <ul class="nv-sub" id="nv-sub-<?= html_escape($chave) ?>"<?= $categoria_atual ? '' : ' hidden' ?>>
                            <?php foreach ($grupo['itens'] as $item) { ?>
                                <?php
                                $ativo = $item['id'] === $ativoId;
                                $destino = $item['rota'] === '' ? base_url() : site_url($item['rota']);
                                ?>
                                <li class="<?= $ativo ? 'active' : '' ?>">
                                    <a class="tip-bottom" title="<?= html_escape($item['dica']) ?>"
                                       href="<?= $destino ?>"<?= $ativo ? ' aria-current="page"' : '' ?>>
                                        <i class='bx <?= html_escape($item['icone']) ?> iconX' aria-hidden="true"></i>
                                        <span class="title"><?= html_escape($item['rotulo']) ?></span>
                                        <span class="title-tooltip"><?= html_escape($item['dica']) ?></span>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
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
