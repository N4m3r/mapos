<!DOCTYPE html>
<html lang="pt-br">

<head>
  <title><?= html_escape($this->config->item('app_name')) ?> - Verificação em duas etapas</title>
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
    input[type=text] {
      width: 100%; font-size: 26px; letter-spacing: 8px; text-align: center; padding: 12px; border: 1px solid #e6e8f0; border-radius: 10px;
    }
    input[type=text]:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,.2); }
    button {
      width: 100%; margin-top: 16px; padding: 13px; border: 0; border-radius: 10px; font-size: 15px; font-weight: 600; color: #fff; cursor: pointer;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .erro { background: #fdecee; color: #e14b5a; border-radius: 8px; padding: 10px 12px; font-size: 13px; margin-bottom: 16px; }
    .dica { font-size: 12px; margin: 18px 0 0; }
    a { color: #5b4bd6; }
  </style>
</head>

<body>
  <div class="caixa">
    <h1><i class="fa fa-shield"></i> Verificação em duas etapas</h1>
    <p>Digite o código de 6 dígitos que aparece no seu aplicativo autenticador.</p>

    <?php if ($this->session->flashdata('error')) { ?>
      <div class="erro"><?= html_escape($this->session->flashdata('error')) ?></div>
    <?php } ?>

    <?= form_open('login/verificarDoisFatores') ?>
      <input type="text" name="codigo" inputmode="numeric" autocomplete="one-time-code" maxlength="9" placeholder="000000" autofocus required>
      <button type="submit"><i class="fa fa-sign-in"></i> Confirmar</button>
    <?= form_close() ?>

    <p class="dica">Sem acesso ao aplicativo? Digite um dos seus <strong>códigos de recuperação</strong> (formato xxxx-xxxx) no mesmo campo.
      <br><a href="<?= site_url('login') ?>">Voltar ao login</a></p>
  </div>
</body>

</html>
