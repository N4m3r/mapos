<?php
/**
 * Trilha "RH › Seção › Nome" das telas de detalhe.
 * Variável: $atual (texto da página atual). Seção vem do controller ($rh_secao_*).
 */
?>
<nav class="rh-migalha" aria-label="Você está em">
    <a href="<?= site_url('rh') ?>">RH</a>
    <span aria-hidden="true">›</span>
    <a href="<?= html_escape($rh_secao_url ?? site_url('rh/colaboradores')) ?>"><?= html_escape($rh_secao_rotulo ?? 'Colaboradores') ?></a>
    <span aria-hidden="true">›</span>
    <span aria-current="page"><?= html_escape($atual ?? '') ?></span>
</nav>
