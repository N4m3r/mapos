<!DOCTYPE html>
<html lang="pt-br">

<head>
  <title><?= $this->config->item('app_name') ?> </title>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
  <meta name="theme-color" content="#5b4bd6" />
  <link href="<?= base_url(); ?>assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
  <link rel="shortcut icon" type="image/png" href="<?= base_url(); ?>assets/img/favicon.png" />
  <style>
    :root {
      --grad-1: #667eea;
      --grad-2: #764ba2;
      --ink: #2d2f45;
      --muted: #8a8fa3;
      --line: #e6e8f0;
      --danger: #e14b5a;
    }

    * { box-sizing: border-box; }

    html, body { height: 100%; }

    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, var(--grad-1) 0%, var(--grad-2) 100%);
      color: var(--ink);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      min-height: 100vh;
    }

    .login-wrap {
      width: 100%;
      max-width: 920px;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 24px 60px rgba(20, 20, 60, 0.28);
      overflow: hidden;
      display: grid;
      grid-template-columns: 1.05fr 1fr;
    }

    /* Lado ilustrativo (esconde no mobile) */
    .login-aside {
      background: linear-gradient(135deg, var(--grad-1) 0%, var(--grad-2) 100%);
      color: #fff;
      padding: 44px 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 18px;
    }

    .login-aside h1 { margin: 0; font-size: 26px; line-height: 1.25; font-weight: 700; }
    .login-aside p { margin: 0; font-size: 15px; opacity: 0.92; line-height: 1.5; }
    .login-aside img.illus { width: 100%; max-width: 320px; margin-top: 10px; align-self: center; }

    /* Lado do formulário */
    .login-form {
      padding: 40px 34px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .brand {
      text-align: center;
      margin-bottom: 22px;
    }

    .brand img { max-width: 190px; height: auto; }
    .brand .versao { display: block; margin-top: 6px; color: var(--muted); font-size: 12px; }

    .field { position: relative; margin-bottom: 16px; }

    .field .fa {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      font-size: 16px;
      pointer-events: none;
    }

    .field input {
      width: 100%;
      height: 52px;
      border: 1.5px solid var(--line);
      border-radius: 12px;
      padding: 0 46px;
      font-size: 16px;
      color: var(--ink);
      background: #fafbfe;
      transition: border-color .15s, box-shadow .15s, background .15s;
    }

    .field input:focus {
      outline: none;
      border-color: var(--grad-1);
      background: #fff;
      box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.14);
    }

    .field .toggle-pass {
      position: absolute;
      right: 8px;
      top: 50%;
      transform: translateY(-50%);
      border: none;
      background: transparent;
      color: var(--muted);
      width: 40px;
      height: 40px;
      cursor: pointer;
      font-size: 16px;
      border-radius: 8px;
    }

    .field .toggle-pass:hover { color: var(--grad-2); }

    .btn-entrar {
      width: 100%;
      height: 52px;
      border: none;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--grad-1) 0%, var(--grad-2) 100%);
      color: #fff;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: transform .12s, box-shadow .12s, opacity .12s;
      margin-top: 4px;
    }

    .btn-entrar:hover { transform: translateY(-1px); box-shadow: 0 10px 22px rgba(118, 75, 162, 0.34); }
    .btn-entrar:disabled { opacity: .7; cursor: default; transform: none; box-shadow: none; }

    .alert-erro {
      background: #fdecee;
      border: 1px solid #f6c4ca;
      color: var(--danger);
      border-radius: 10px;
      padding: 11px 14px;
      font-size: 14px;
      margin-bottom: 16px;
      display: flex;
      align-items: flex-start;
      gap: 8px;
    }

    .help-inline { color: var(--danger); font-size: 12.5px; display: block; margin: 6px 2px 0; }

    .rodape { text-align: center; margin-top: 22px; color: var(--muted); font-size: 12.5px; }
    .rodape a { color: var(--muted); text-decoration: none; }
    .rodape a:hover { color: var(--grad-2); }

    .spin { display: none; }
    .btn-entrar.loading .spin { display: inline-block; }
    .btn-entrar.loading .label { display: none; }
    @keyframes rot { to { transform: rotate(360deg); } }
    .spin .fa { animation: rot .8s linear infinite; }

    @media (max-width: 760px) {
      body { padding: 16px; align-items: flex-start; }
      .login-wrap { grid-template-columns: 1fr; max-width: 440px; border-radius: 18px; margin-top: 12px; }
      .login-aside { display: none; }
      .login-form { padding: 30px 22px 26px; }
    }
  </style>
</head>

<body>
  <div class="login-wrap">
    <aside class="login-aside">
      <?php
      function saudacao($nome = '')
      {
          $hora = date('H');
          if ($hora >= 0 && $hora < 12) {
              return 'Bom dia' . (empty($nome) ? '' : ', ' . $nome);
          } elseif ($hora >= 12 && $hora < 18) {
              return 'Boa tarde' . (empty($nome) ? '' : ', ' . $nome);
          }
          return 'Boa noite' . (empty($nome) ? '' : ', ' . $nome);
      }
      ?>
      <h1><?= saudacao(); ?>! 👋</h1>
      <p>Bem-vindo ao sistema de controle de Ordens de Serviço. Acesse sua conta para continuar.</p>
      <img class="illus" src="<?= base_url() ?>assets/img/dashboard-animate.svg" alt="Map-OS - Versão: <?= $this->config->item('app_version'); ?>">
    </aside>

    <main class="login-form">
      <div class="brand">
        <img src="<?= base_url() ?>assets/img/logo-mapos.png" onerror="this.onerror=null;this.src='<?= base_url() ?>assets/img/logo-two.png';" alt="Map-OS">
        <span class="versao">Versão: <?= $this->config->item('app_version'); ?></span>
      </div>

      <?php if ($this->session->flashdata('error') != null) { ?>
        <div class="alert-erro">
          <i class="fa fa-exclamation-circle"></i>
          <span><?= $this->session->flashdata('error'); ?></span>
        </div>
      <?php } ?>

      <div id="erro-login" class="alert-erro" style="display:none;">
        <i class="fa fa-exclamation-circle"></i>
        <span id="erro-login-msg"></span>
      </div>

      <form class="form-vertical" id="formLogin" method="post" action="<?= site_url('login/verificarLogin') ?>">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

        <div class="field">
          <i class="fa fa-user"></i>
          <input id="email" name="email" type="email" inputmode="email" autocomplete="username" placeholder="E-mail" autofocus>
        </div>

        <div class="field">
          <i class="fa fa-lock"></i>
          <input id="senha" name="senha" type="password" autocomplete="current-password" placeholder="Senha">
          <button type="button" class="toggle-pass" id="togglePass" tabindex="-1" aria-label="Mostrar senha">
            <i class="fa fa-eye"></i>
          </button>
        </div>

        <button id="btn-acessar" class="btn-entrar" type="submit">
          <span class="label"><i class="fa fa-sign-in"></i> Acessar</span>
          <span class="spin"><i class="fa fa-spinner"></i> Entrando...</span>
        </button>
      </form>

      <div class="rodape">
        <a href="https://github.com/RamonSilva20/mapos"><?= date('Y'); ?> &copy; Ramon Silva</a>
      </div>
    </main>
  </div>

  <script src="<?= base_url() ?>assets/js/jquery-1.12.4.min.js"></script>
  <script src="<?= base_url() ?>assets/js/validate.js"></script>
  <script type="text/javascript">
    $(document).ready(function() {
      $('#email').focus();

      // Mostrar / ocultar senha
      $('#togglePass').on('click', function() {
        var input = $('#senha');
        var icon = $(this).find('i');
        if (input.attr('type') === 'password') {
          input.attr('type', 'text');
          icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
          input.attr('type', 'password');
          icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
      });

      function mostrarErro(msg) {
        $('#erro-login-msg').text(msg || 'Os dados de acesso estão incorretos, por favor tente novamente!');
        $('#erro-login').show();
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
          $('#erro-login').hide();
          $('#btn-acessar').addClass('loading').prop('disabled', true);

          $.ajax({
            type: "POST",
            url: "<?= site_url('login/verificarLogin?ajax=true'); ?>",
            data: dados,
            dataType: 'json',
            success: function(data) {
              if (data.result == true) {
                window.location.href = data.redirect || "<?= site_url('mapos'); ?>";
              } else {
                $('#btn-acessar').removeClass('loading').prop('disabled', false);
                mostrarErro(data.message);
                if (data.MAPOS_TOKEN) {
                  $("input[name='<?= $this->security->get_csrf_token_name(); ?>']").val(data.MAPOS_TOKEN);
                }
              }
            },
            error: function() {
              $('#btn-acessar').removeClass('loading').prop('disabled', false);
              mostrarErro('Não foi possível conectar. Tente novamente.');
            }
          });

          return false;
        },
        errorClass: "help-inline",
        errorElement: "span",
        highlight: function(element) { $(element).css('border-color', 'var(--danger)'); },
        unhighlight: function(element) { $(element).css('border-color', ''); }
      });
    });
  </script>
</body>

</html>
