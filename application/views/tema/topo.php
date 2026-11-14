<?php
// Tema da tela: o do sistema ou o do botao claro/escuro do usuario (MY_Controller).
$temaAtual = $configuration['app_theme'] ?: 'white';
$temaPar = tema_par_claro_escuro($configuration['app_theme_sistema'] ?? $temaAtual);
$modoEscuro = tema_e_escuro($temaAtual);
$urlCssTema = function ($tema) {
    $arquivo = tema_arquivo_css($tema);
    return $arquivo ? base_url($arquivo) . '?v=' . asset_versao($arquivo) : '';
};
?>
<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="<?= $modoEscuro ? 'dark' : 'light' ?>">

<head>
  <title><?= $configuration['app_name'] ?: 'Map-OS' ?></title>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token-name" content="<?= config_item("csrf_token_name") ?>">
  <meta name="csrf-cookie-name" content="<?= config_item("csrf_cookie_name") ?>">
  <link rel="shortcut icon" type="image/png" href="<?= base_url(); ?>assets/img/favicon.png" />
  <link rel="stylesheet" href="<?= base_url(); ?>assets/css/bootstrap.min.css?v=<?= asset_versao('assets/css/bootstrap.min.css') ?>" />
  <link rel="stylesheet" href="<?= base_url('assets/css/matrix-style.css') ?>?v=<?= asset_versao('assets/css/matrix-style.css') ?>" />
  <link rel="stylesheet" href="<?= base_url(); ?>assets/css/matrix-media.css?v=<?= asset_versao('assets/css/matrix-media.css') ?>" />
  <?php if ($this->uri->segment(1) === 'rh'): ?>
  <link rel="stylesheet" href="<?= base_url('assets/css/rh.css') ?>?v=<?= asset_versao('assets/css/rh.css', '3') ?>" />
  <?php endif; ?>
  <link href="<?= base_url(); ?>assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
  <link rel="stylesheet" href="<?= base_url(); ?>assets/css/fullcalendar.css" />
  <link id="tema-css" rel="stylesheet"<?= $urlCssTema($temaAtual) ? ' href="' . $urlCssTema($temaAtual) . '"' : '' ?> />
  <link rel="stylesheet" href="<?= base_url('assets/css/tema-escuro-ajustes.css') ?>?v=<?= asset_versao('assets/css/tema-escuro-ajustes.css') ?>">
  <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,700,800' rel='stylesheet' type='text/css'>
  <link href='https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@300;400;500;700&display=swap' rel='stylesheet' type='text/css'>
  <link href='<?= base_url('assets/boxicons/css/boxicons.min.css') ?>' rel='stylesheet'>
  <link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap' rel='stylesheet'>
  <!-- Camada visual moderna (ADR-009): depois do tema, que continua dando as cores -->
  <link rel="stylesheet" href="<?= base_url('assets/css/moderno.css') ?>?v=<?= asset_versao('assets/css/moderno.css', '1') ?>" />
  <link rel="stylesheet" href="<?= base_url('assets/css/menu-navegacao.css') ?>?v=<?= asset_versao('assets/css/menu-navegacao.css', '1') ?>" />
  <!-- jQuery 3.7 + Migrate (padrao); APP_JQUERY3=false no .env volta ao 1.12 -->
  <?= jquery_tags() ?>
  <script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?= base_url(); ?>assets/js/shortcut.js"></script>
  <script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?= base_url(); ?>assets/js/funcoesGlobal.js"></script>
  <script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?= base_url(); ?>assets/js/datatables.min.js"></script>
  <script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?= base_url(); ?>assets/js/sweetalert.min.js"></script>
  <script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?= base_url(); ?>assets/js/csrf.js?v=<?= asset_versao('assets/js/csrf.js', '4') ?>"></script>
  <script nonce="<?= csp_nonce() ?>" type="text/javascript">
    shortcut.add("escape", function() {
      location.href = '<?= base_url(); ?>';
    });
    shortcut.add("F1", function() {
      location.href = '<?= site_url('clientes'); ?>';
    });
    shortcut.add("F2", function() {
      location.href = '<?= site_url('produtos'); ?>';
    });
    shortcut.add("F3", function() {
      location.href = '<?= site_url('servicos'); ?>';
    });
    shortcut.add("F4", function() {
      location.href = '<?= site_url('os'); ?>';
    });
    //shortcut.add("F5", function() {});
    shortcut.add("F6", function() {
      location.href = '<?= site_url('vendas/adicionar'); ?>';
    });
    shortcut.add("F7", function() {
      location.href = '<?= site_url('financeiro/lancamentos'); ?>';
    });
    shortcut.add("F8", function() {});
    shortcut.add("F9", function() {});
    shortcut.add("F10", function() {});
    //shortcut.add("F11", function() {});
    shortcut.add("F12", function() {});
    window.BaseUrl = "<?= base_url() ?>";
  </script>
    <script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/acoes.js') ?>?v=<?= asset_versao('assets/js/acoes.js') ?>"></script>
    <script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/modo-tema.js') ?>?v=<?= asset_versao('assets/js/modo-tema.js') ?>" defer></script>
</head>

<body data-theme="<?= html_escape($temaAtual) ?>"
  data-tema-claro="<?= html_escape($temaPar['claro']) ?>" data-css-claro="<?= html_escape($urlCssTema($temaPar['claro'])) ?>"
  data-tema-escuro="<?= html_escape($temaPar['escuro']) ?>" data-css-escuro="<?= html_escape($urlCssTema($temaPar['escuro'])) ?>"
  data-modo-cookie="mapos_modo_<?= (int) $this->session->userdata('id_admin') ?>" data-cookie-path="<?= html_escape(config_item('cookie_path') ?: '/') ?>">
  <!--top-Header-menu-->
  <div class="navebarn">
    <div id="user-nav" class="user-nav">
      <ul class="user-nav-lista">
        <li class="modo-tema">
          <a href="#" id="btn-modo-escuro" role="button" aria-pressed="<?= $modoEscuro ? 'true' : 'false' ?>"
            aria-label="<?= $modoEscuro ? 'Ativar modo claro' : 'Ativar modo escuro' ?>" title="<?= $modoEscuro ? 'Ativar modo claro' : 'Ativar modo escuro' ?>"><i class='bx <?= $modoEscuro ? 'bx-sun' : 'bx-moon' ?> iconN' aria-hidden="true"></i></a>
        </li>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" role="button" data-bs-toggle="dropdown" title="Minha conta e sair" aria-label="Menu da conta" aria-expanded="false"><i class='bx bx-user-circle iconN'></i><span class="text"></span></a>
          <ul class="dropdown-menu menu-perfil">
            <li class="menu-perfil-cabecalho">
              <strong><?= html_escape($this->session->userdata('nome_admin')) ?></strong>
              <small><?= html_escape($this->session->userdata('email_admin')) ?></small>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= site_url('mapos/minhaConta'); ?>"><i class='bx bx-user' aria-hidden="true"></i> <span class="text">Minha Conta</span></a></li>
            <li><a class="dropdown-item" href="<?= site_url(); ?>/mine" target="_blank" rel="noopener"><i class='bx bx-link-external' aria-hidden="true"></i> <span class="text">Área do Cliente</span></a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= site_url('login/sair'); ?>"><i class='bx bx-log-out-circle' aria-hidden="true"></i> <span class="text">Sair do Sistema</span></a></li>
          </ul>
        </li>
      </ul>
    </div>

    <!-- New User -->
    <div id="userr" style="padding-right:45px;display:flex;flex-direction:column;align-items:flex-end;justify-content:center;">
      <div class="user-names userT0">
        <?php
        function saudacao()
        {
          $hora = date('H');
          if ($hora >= 00 && $hora < 12) {
            return 'Bom dia, ';
          } elseif ($hora >= 12 && $hora < 18) {
            return 'Boa tarde, ';
          } else {
            return 'Boa noite, ';
          }
        }

        $login = '';
        echo saudacao($login); // Irá retornar conforme o horário
        ?>
      </div>
      <div class="userT"><?= $this->session->userdata('nome_admin') ?></div>

      <section style="display:block;position:absolute;right:10px">
        <div class="profile">
          <div class="profile-img">
            <a href="<?= site_url('mapos/minhaConta'); ?>"><img src="<?= !is_file(FCPATH . "assets/userImage/" . $this->session->userdata('url_image_user_admin')) ?  base_url() . "assets/img/User.png" : base_url() . "assets/userImage/" . $this->session->userdata('url_image_user_admin') ?>" alt=""></a>
          </div>
        </div>
      </section>

    </div>
  </div>
  <!-- End User -->

  <!--start-top-serch-->
  <div style="display: none" id="search">
    <form action="<?= site_url('mapos/pesquisar') ?>">
      <input type="text" name="termo" placeholder="Pesquisar..." />
      <button type="submit" class="tip-bottom" title="Pesquisar"><i class="fas fa-search fa-white"></i></button>
    </form>
  </div>
  <!--close-top-serch-->
