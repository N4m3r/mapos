<?php
/** Lista somente leitura das observações do técnico. Espera $observacoes_tecnico (array). */
if (empty($observacoes_tecnico)) {
    return;
}
?>
<div class="obs-tecnico-bloco" style="margin:10px 0;">
    <h5 style="margin:6px 0;">Observações do técnico</h5>
    <?php foreach ($observacoes_tecnico as $ob) : ?>
        <div style="padding:6px 0;border-bottom:1px solid #eee;">
            <small style="color:#666;"><?= html_escape(date('d/m/Y H:i', strtotime($ob->data_hora))) ?> &middot; <?= html_escape($ob->nome_usuario ?: 'Técnico') ?></small><br>
            <?= nl2br(html_escape($ob->texto)) ?>
        </div>
    <?php endforeach; ?>
</div>
