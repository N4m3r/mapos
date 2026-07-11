<?php
/**
 * Cabecalho compartilhado da Area do Tecnico (mobile-first).
 * Variaveis esperadas (passe ao incluir):
 *   $titulo         (string)  - titulo da aba e do cabecalho
 *   $header_icone   (string)  - classe boxicons opcional (ex.: 'bx-home-alt')
 *   $header_sub     (string)  - subtitulo opcional
 *   $voltar_url     (string)  - se definido, mostra botao "Voltar"
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
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Área do Técnico">
    <meta name="csrf-token-name" content="<?= config_item('csrf_token_name') ?>">
    <meta name="csrf-cookie-name" content="<?= config_item('csrf_cookie_name') ?>">
    <title><?= isset($titulo) ? $titulo : 'Área do Técnico' ?></title>
    <link rel="shortcut icon" type="image/png" href="<?= base_url('assets/img/favicon.png') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <link href="https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/tecnico.css') ?>">
</head>
<body class="tec-body">
    <header class="tec-header">
        <div class="tec-header-row">
            <div>
                <h1><i class='bx <?= $header_icone ?>'></i> <?= isset($titulo) ? $titulo : 'Área do Técnico' ?></h1>
                <?php if ($header_sub): ?><p><?= $header_sub ?></p><?php endif; ?>
            </div>
            <?php if ($voltar_url): ?>
                <a href="<?= $voltar_url ?>" class="tec-back"><i class='bx bx-arrow-back'></i> Voltar</a>
            <?php endif; ?>
        </div>
    </header>
