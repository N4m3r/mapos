<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Cabeçalho da empresa nos documentos impressos.
 *
 * O mesmo bloco (razão social, CNPJ, endereço e contato) estava copiado em
 * cinco impressos, cada um com uma variação: um pulava o CNPJ mascarado, outro
 * não mostrava o responsável. Ao acrescentar nome fantasia, IE e IM, todos
 * teriam de ser alterados em conjunto — e voltariam a divergir.
 *
 * Uso: $this->load->view('comum/_emitente_cabecalho', [
 *     'emitente' => $emitente,
 *     'quem' => $result->nome,          // opcional: "Responsável: Fulano"
 *     'rotulo_quem' => 'Responsável',   // opcional: "Vendedor", "Técnico"
 * ]);
 *
 * Sem `quem`, a última linha só traz telefone e e-mail.
 */
$emitente = $emitente ?? null;
$quem = trim((string) ($quem ?? ''));
$rotuloQuem = (string) ($rotulo_quem ?? 'Responsável');

if (empty($emitente)) {
    ?>
    <div class="alert alert-danger" role="alert">
        Você precisa configurar os dados do emitente. >>>
        <a href="<?= base_url() ?>index.php/mapos/emitente">Configurar</a>
    </div>
    <?php
    return;
}

// A partial roda dentro dos impressos, cujos controllers nao carregam este
// helper: ele se resolve aqui em vez de depender do autoload.
$this->load->helper('emitente');

$linhas = emitente_cabecalho($emitente);
$nome = emitente_nome_exibicao($emitente);
?>
<div class="imgLogo align-middle">
    <?php if (! empty($emitente->url_logo)) { ?>
        <img src="<?= html_escape($emitente->url_logo) ?>" class="img-fluid" style="width:140px" alt="">
    <?php } ?>
</div>
<div class="emitente">
    <span style="font-size: 16px;"><b><?= html_escape($nome) ?></b></span><br>
    <?php if (! empty($emitente->nome_fantasia) && trim($emitente->nome_fantasia) !== trim((string) $emitente->nome)) { ?>
        <span style="opacity:.75"><?= html_escape($emitente->nome) ?></span><br>
    <?php } ?>
    <?php foreach ($linhas as $indice => $linha) { ?>
        <span class="align-middle"><?= html_escape($linha) ?></span><?= $indice < count($linhas) - 1 ? '<br>' : '' ?>
    <?php } ?>
</div>
<div class="contatoEmitente">
    <?php if (! empty($emitente->telefone)) { ?>
        <span style="font-weight: bold;">Tel: <?= html_escape($emitente->telefone) ?></span><br>
    <?php } ?>
    <?php if (! empty($emitente->email)) { ?>
        <span style="font-weight: bold;"><?= html_escape($emitente->email) ?></span><br>
    <?php } ?>
    <?php if ($quem !== '') { ?>
        <span style="word-break: break-word;"><?= html_escape($rotuloQuem) ?>: <b><?= html_escape($quem) ?></b></span>
    <?php } ?>
</div>
