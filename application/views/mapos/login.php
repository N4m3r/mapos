<!DOCTYPE html>
<html lang="pt-br">

<head>
  <title><?= htmlspecialchars($this->config->item('app_name'), ENT_QUOTES, 'UTF-8') ?></title>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="theme-color" content="#05080f" />
  <link rel="shortcut icon" type="image/png" href="<?= base_url(); ?>assets/img/favicon.png" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="<?= base_url('assets/boxicons/css/boxicons.min.css') ?>" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/css/login-cftv.css') ?>?v=<?= asset_versao('assets/css/login-cftv.css') ?>" />
  <script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/acoes.js') ?>?v=<?= asset_versao('assets/js/acoes.js') ?>"></script>
</head>

<?php
$hora = (int) date('H');
$saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');
$logoEmitente = emitente_logo_url();
$logo = $logoEmitente !== null ? $logoEmitente : base_url('assets/img/logo-mapos-branco.png');
$versao = htmlspecialchars($this->config->item('app_version'), ENT_QUOTES, 'UTF-8');
?>

<body class="cftv">
  <div class="cftv-wall" data-cftv-wall aria-hidden="true"></div>
  <div class="cftv-veu" aria-hidden="true"></div>

  <main class="cftv-main">
    <section class="cftv-hero">
      <span class="cftv-badge">AO VIVO &middot; <span data-cftv-total>12</span> CÂMERAS</span>
      <?php $this->load->view('tema/login_cftv_domo'); ?>
      <h1><?= $saudacao ?>!<br><span class="grad">Central de monitoramento</span></h1>
      <p>Ordens de serviço, equipes em campo e sistemas de CFTV em um só lugar. Acesse sua conta para continuar.</p>
      <ul class="cftv-chips">
        <li><i class="bx bx-video-recording"></i> Gravação 24h</li>
        <li><i class="bx bx-shield-quarter"></i> Acesso protegido</li>
        <li><i class="bx bx-wrench"></i> Equipes em campo</li>
      </ul>
    </section>

    <div class="cftv-card">
      <span class="cftv-canto c1"></span><span class="cftv-canto c2"></span>
      <span class="cftv-canto c3"></span><span class="cftv-canto c4"></span>

      <div class="cftv-logo">
        <img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" data-fallback="<?= base_url() ?>assets/img/logo-two.png" alt="Logo">
      </div>
      <div class="cftv-titulo">
        <h2>Acesso ao sistema</h2>
        <p>Entre com seu e-mail e senha</p>
      </div>

      <?php if ($this->session->flashdata('error') != null) { ?>
        <div class="cftv-alerta" role="alert">
          <i class="bx bx-error-circle"></i>
          <span><?= $this->session->flashdata('error'); ?></span>
        </div>
      <?php } ?>

      <div id="erro-login" class="cftv-alerta" role="alert" hidden>
        <i class="bx bx-error-circle"></i>
        <span id="erro-login-msg"></span>
      </div>

      <form id="formLogin" method="post" action="<?= site_url('login/verificarLogin') ?>" novalidate>
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

        <div class="cftv-campo">
          <i class="bx bx-envelope"></i>
          <input id="email" name="email" type="email" inputmode="email" autocomplete="username" placeholder="E-mail" aria-label="E-mail" autofocus>
        </div>

        <div class="cftv-campo">
          <i class="bx bx-lock-alt"></i>
          <input id="senha" name="senha" type="password" autocomplete="current-password" placeholder="Senha" aria-label="Senha">
          <button type="button" class="cftv-olho" id="togglePass" aria-label="Mostrar senha"><i class="bx bx-show"></i></button>
        </div>

        <button id="btn-acessar" class="cftv-btn" type="submit">
          <span class="normal">Acessar</span><i class="bx bx-right-arrow-alt normal"></i>
          <span class="carregando">Autenticando...</span>
        </button>
      </form>

      <div class="cftv-rodape">
        <span>Versão <?= $versao ?></span>
        <a href="https://github.com/RamonSilva20/mapos" target="_blank" rel="noopener"><?= date('Y'); ?> &copy; Ramon Silva</a>
      </div>
    </div>
  </main>

  <script nonce="<?= csp_nonce() ?>">
    // Aparelho compartilhado: telas da Area do Tecnico guardadas offline nao ficam para o proximo usuario
    if ('caches' in window) {
      caches.keys().then(function (nomes) {
        nomes.filter(function (n) { return n.indexOf('mapos-tecnico-paginas-') === 0; }).forEach(function (n) { caches.delete(n); });
      }).catch(function () {});
    }
  </script>
  <script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/login-cftv.js') ?>?v=<?= asset_versao('assets/js/login-cftv.js') ?>"></script>
  <script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/jquery-3.7.1.min.js"></script>
  <script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/jquery-migrate-3.4.1.min.js"></script>
  <script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/validate.js"></script>
  <script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function() {
      $('#email').focus();

      // Mostrar / ocultar senha
      $('#togglePass').on('click', function() {
        var $s = $('#senha'), mostrar = $s.attr('type') === 'password';
        $s.attr('type', mostrar ? 'text' : 'password');
        $(this).attr('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha')
          .find('i').attr('class', mostrar ? 'bx bx-hide' : 'bx bx-show');
      });

      function mostrarErro(msg) {
        $('#erro-login-msg').text(msg || 'Os dados de acesso estão incorretos, por favor tente novamente!');
        $('#erro-login').prop('hidden', false);
        $('.cftv-campo').addClass('erro');
        setTimeout(function() { $('.cftv-campo').removeClass('erro'); }, 600);
      }

      $("#formLogin").validate({
        rules: {
          email: { required: true, email: true },
          senha: { required: true }
        },
        messages: {
          email: { required: 'Informe seu e-mail', email: 'Insira um e-mail válido' },
          senha: { required: 'Informe sua senha' }
        },
        submitHandler: function(form) {
          var dados = $(form).serialize();
          $('#erro-login').prop('hidden', true);
          $('#btn-acessar').addClass('is-loading').prop('disabled', true);

          $.ajax({
            type: "POST",
            url: "<?= site_url('login/verificarLogin?ajax=true'); ?>",
            data: dados,
            dataType: 'json',
            success: function(data) {
              if (data.result == true) {
                window.location.href = data.redirect || "<?= site_url('mapos'); ?>";
              } else {
                $('#btn-acessar').removeClass('is-loading').prop('disabled', false);
                mostrarErro(data.message);
                if (data.MAPOS_TOKEN) {
                  $("input[name='<?= $this->security->get_csrf_token_name(); ?>']").val(data.MAPOS_TOKEN);
                }
              }
            },
            error: function() {
              $('#btn-acessar').removeClass('is-loading').prop('disabled', false);
              mostrarErro('Não foi possível conectar. Tente novamente.');
            }
          });

          return false;
        },
        errorClass: "help-inline",
        errorElement: "span",
        errorPlacement: function(error, element) { error.appendTo(element.closest('.cftv-campo')); },
        highlight: function(element) { $(element).closest('.cftv-campo').addClass('erro'); },
        unhighlight: function(element) { $(element).closest('.cftv-campo').removeClass('erro'); }
      });
    });
  </script>
</body>

</html>
