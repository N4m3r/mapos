<?php
/**
 * Cabeçalho compartilhado da Área do Colaborador / tela de ponto (mobile-first).
 * Reaproveita tecnico.css + rh.css. Variáveis opcionais:
 *   $titulo, $header_icone, $header_sub, $voltar_url
 */
$header_icone = isset($header_icone) ? $header_icone : 'bxs-user-circle';
$header_sub   = isset($header_sub) ? $header_sub : null;
$voltar_url   = isset($voltar_url) ? $voltar_url : null;
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#667eea">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="csrf-token-name" content="<?= config_item('csrf_token_name') ?>">
    <meta name="csrf-cookie-name" content="<?= config_item('csrf_cookie_name') ?>">
    <title><?= isset($titulo) ? $titulo : 'Área do Colaborador' ?></title>
    <link rel="shortcut icon" type="image/png" href="<?= base_url('assets/img/favicon.png') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <link href="<?= base_url('assets/boxicons/css/boxicons.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/tecnico.css') ?>?v=<?= asset_versao('assets/css/tecnico.css', '4') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/rh.css') ?>?v=<?= asset_versao('assets/css/rh.css', '3') ?>">
    <script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/acoes.js') ?>?v=<?= asset_versao('assets/js/acoes.js') ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/moderno.css') ?>?v=<?= asset_versao('assets/css/moderno.css', '1') ?>" />
</head>
<body class="tec-body">
    <header class="tec-header">
        <div class="tec-header-row">
            <div class="tec-header-esq">
                <button type="button" id="tec-menu-btn" class="tec-menu-btn" aria-label="Abrir menu" aria-haspopup="dialog" aria-controls="tec-menu" aria-expanded="false"><i class='bx bx-menu' aria-hidden="true"></i></button>
            <div>
                <h1><i class='bx <?= $header_icone ?>'></i> <?= isset($titulo) ? $titulo : 'Área do Colaborador' ?></h1>
                <?php if ($header_sub): ?><p><?= $header_sub ?></p><?php endif; ?>
            </div>
            </div>
            <?php if ($voltar_url): ?>
                <a href="<?= $voltar_url ?>" class="tec-back"><i class='bx bx-arrow-back'></i> Voltar</a>
            <?php endif; ?>
        </div>
    </header>
<?php $this->load->view('tecnico/_menu_lateral'); ?>
