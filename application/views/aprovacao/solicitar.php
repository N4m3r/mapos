<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Página pública para o cliente pedir o link de aprovação pelo número do
 * chamado. Substitui a orientação "fale com a empresa" quando a OS foi aberta
 * sem e-mail ou celular utilizável.
 *
 * Sem JavaScript obrigatório: são formulários HTML comuns, com CSRF.
 */
$appName = $this->config->item('app_name') ?: 'Map-OS';
$emitente = isset($emitente) ? $emitente : null;
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Aprovação por chamado - <?= html_escape($appName) ?></title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <link rel="stylesheet" href="<?= base_url() ?>assets/css/bootstrap.min.css?v=<?= asset_versao('assets/css/bootstrap.min.css') ?>" />
    <link rel="stylesheet" href="<?= base_url() ?>assets/font-awesome/css/font-awesome.css" />
    <link rel="shortcut icon" type="image/png" href="<?= base_url() ?>assets/img/favicon.png" />
    <style>
        body { background: #eef1f6; color: #33373d; padding: 0 0 50px; font-family: 'Segoe UI', Tahoma, Arial, sans-serif; }
        .ap-wrap { max-width: 620px; margin: 0 auto; padding: 15px; }
        .ap-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(0, 0, 0, .08); overflow: hidden; margin-top: 20px; }
        .ap-head { background: #2d335b; color: #fff; padding: 22px 26px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .ap-head h1 { font-size: 20px; line-height: 1.3; margin: 0; }
        .ap-head small { opacity: .8; display: block; margin-top: 2px; }
        .ap-head img { max-height: 42px; }
        .ap-body { padding: 22px 26px; }
        .ap-body h2 { font-size: 17px; margin: 0 0 6px; }
        .ap-note { font-size: 12px; color: #7b8088; margin-top: 14px; }
        .ap-field label { font-weight: 600; font-size: 13px; display: block; margin-bottom: 4px; }
        .ap-field input { width: 100%; box-sizing: border-box; height: 40px; }
        .ap-field + .ap-field { margin-top: 14px; }
        .ap-btns { margin-top: 18px; display: flex; gap: 10px; flex-wrap: wrap; }
        .ap-btns .btn { min-height: 40px; }
        .ap-rodape { text-align: center; color: #7b8088; font-size: 13px; margin-top: 14px; }
        @media (max-width: 520px) { .ap-head { flex-direction: column; align-items: flex-start; } }
    </style>
</head>

<body>
    <div class="ap-wrap">
        <div class="ap-card">
            <div class="ap-head">
                <div>
                    <h1>Aprovação de orçamento</h1>
                    <small><?= html_escape($emitente->nome ?? $appName) ?></small>
                </div>
                <?php if ($emitente && ! empty($emitente->logo)) { ?>
                    <img src="<?= base_url('assets/img/' . $emitente->logo) ?>" alt="<?= html_escape($emitente->nome) ?>">
                <?php } ?>
            </div>

            <div class="ap-body">
                <?php if (! empty($erro)) { ?>
                    <div class="alert alert-danger"><?= html_escape($erro) ?></div>
                <?php } ?>
                <?php if (! empty($info)) { ?>
                    <div class="alert alert-info"><?= html_escape($info) ?></div>
                <?php } ?>

                <h2>Receber o link de aprovação</h2>
                <p class="text-muted">
                    Informe o número do chamado que consta na sua Ordem de Serviço e o CPF/CNPJ do titular.
                    Enviamos o link aos contatos já cadastrados — por segurança, ele não aparece nesta tela.
                </p>

                <form method="post" action="<?= site_url('aprovacao/enviarLink') ?>">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>" />

                    <div class="ap-field">
                        <label for="chamado">Número do chamado</label>
                        <input class="form-control" id="chamado" name="chamado" type="text" maxlength="60" required
                               value="<?= html_escape($this->input->post('chamado')) ?>"
                               placeholder="Ex.: CH-2026/0042" autocomplete="off" />
                    </div>

                    <div class="ap-field">
                        <label for="documento">CPF ou CNPJ do titular</label>
                        <input class="form-control" id="documento" name="documento" type="text" maxlength="20" required
                               value="<?= html_escape($this->input->post('documento')) ?>"
                               placeholder="Somente números ou com pontuação" inputmode="numeric" autocomplete="off" />
                    </div>

                    <div class="ap-btns">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane"></i> Enviar o link</button>
                    </div>
                </form>

                <p class="ap-note">
                    Já tem o link? Ele abre direto a página de aprovação, com o prazo e o valor do orçamento.
                </p>

                <?php if ($emitente && ! empty($emitente->telefone)) { ?>
                    <p class="ap-note">Dúvidas? Fale com <?= html_escape($emitente->nome) ?> &middot; <?= html_escape($emitente->telefone) ?></p>
                <?php } ?>
            </div>
        </div>

        <p class="ap-rodape"><?= html_escape($appName) ?></p>
    </div>
</body>

</html>
