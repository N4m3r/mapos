<!DOCTYPE html>
<html lang="pt-br">

<head>
  <title><?= html_escape($this->config->item('app_name')) ?> - Criar sua senha</title>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="theme-color" content="#5b4bd6" />
  <link href="<?= base_url(); ?>assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
  <link rel="shortcut icon" type="image/png" href="<?= base_url(); ?>assets/img/favicon.png" />
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px 16px;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #2d2f45;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .caixa { width: 100%; max-width: 400px; background: #fff; border-radius: 16px; padding: 32px 28px; box-shadow: 0 20px 50px rgba(0,0,0,.25); }
    h1 { font-size: 20px; margin: 0 0 8px; display: flex; align-items: center; gap: 10px; }
    h1 i { color: #5b4bd6; }
    p { color: #8a8fa3; font-size: 14px; line-height: 1.5; margin: 0 0 20px; }
    label { display: block; font-size: 13px; font-weight: 600; margin: 0 0 6px; }
    input[type=password] {
      width: 100%; font-size: 15px; padding: 12px; border: 1px solid #e6e8f0; border-radius: 10px; margin-bottom: 14px;
    }
    input[type=password]:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,.2); }
    button {
      width: 100%; margin-top: 16px; padding: 13px; border: 0; border-radius: 10px; font-size: 15px; font-weight: 600; color: #fff; cursor: pointer;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .erro { background: #fdecee; color: #e14b5a; border-radius: 8px; padding: 10px 12px; font-size: 13px; margin-bottom: 16px; }
    .dica { font-size: 12px; margin: 18px 0 0; }
    .forca { height: 6px; background: #e6e8f0; border-radius: 3px; margin: -8px 0 4px; overflow: hidden; }
    .forca div { height: 100%; width: 0; transition: width .2s; }
    #forcaSenhaTexto { font-size: 12px; color: #8a8fa3; margin: 0 0 14px; }
    a { color: #5b4bd6; }
  </style>
</head>

<body>
  <div class="caixa">
    <h1><i class="fa fa-key"></i> Crie a sua senha</h1>
    <p>Você entrou com uma senha temporária. Escolha agora a senha que vai usar daqui para frente.</p>

    <?php if ($this->session->flashdata('error')) { ?>
      <div class="erro"><?= html_escape($this->session->flashdata('error')) ?></div>
    <?php } ?>

    <?= form_open('login/salvarNovaSenha') ?>
      <label for="novaSenha">Nova senha</label>
      <input type="password" id="novaSenha" name="novaSenha" data-forca-senha autocomplete="new-password" minlength="6" required autofocus>
      <div class="forca" role="presentation"><div id="forcaSenhaBarra"></div></div>
      <div id="forcaSenhaTexto" aria-live="polite">Mínimo de 6 caracteres. Misture letras, números e símbolos.</div>
      <label for="confirmarSenha">Confirmar nova senha</label>
      <input type="password" id="confirmarSenha" name="confirmarSenha" autocomplete="new-password" minlength="6" required>
      <button type="submit"><i class="fa fa-check"></i> Salvar e entrar</button>
    <?= form_close() ?>

    <p class="dica"><a href="<?= site_url('login/sair') ?>">Sair</a></p>
  </div>
  <script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/forca-senha.js') ?>?v=<?= asset_versao('assets/js/forca-senha.js') ?>"></script>
</body>

</html>
