<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Etiquetas - <?= html_escape($obra->nome) ?></title>
<style>
    @page { size: A4; margin: 8mm; }
    body { font-family: Arial, sans-serif; margin: 0; color: #000; }
    .barra { padding: 10px; background: #eee; }
    .grade { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0; }
    .et { box-sizing: border-box; height: 45mm; border: 1px dashed #888; padding: 3mm; display: flex; gap: 3mm; align-items: center; page-break-inside: avoid; break-inside: avoid; overflow: hidden; }
    .et img { width: 36mm; height: 36mm; flex: none; }
    .et .id { font-size: 22pt; font-weight: bold; line-height: 1.05; word-break: break-all; }
    .et .t { font-size: 10pt; margin-top: 1mm; }
    .et .m { font-size: 9pt; color: #333; }
    @media print { .barra { display: none; } }
</style>
</head>
<body>
<div class="barra">
    <button type="button" id="btn-imprimir">Imprimir</button>
    <?= count($itens) ?> etiqueta(s) - <?= html_escape($obra->nome) ?>
</div>
<div class="grade">
<?php foreach ($itens as $it): $p = $it['ponto']; ?>
    <div class="et">
        <img src="<?= html_escape($it['qr']) ?>" alt="QR <?= html_escape($p->identificacao) ?>">
        <div>
            <div class="id"><?= html_escape($p->identificacao) ?></div>
            <div class="t"><?= html_escape($tipos[$p->tipo]['rotulo'] ?? $p->tipo) ?></div>
            <?php if ((string) $p->local !== ''): ?><div class="m">Local: <?= html_escape($p->local) ?></div><?php endif; ?>
            <?php if ((string) $p->ip !== ''): ?><div class="m">IP: <?= html_escape($p->ip) ?></div><?php endif; ?>
            <?php if ((string) $p->canal !== ''): ?><div class="m">Canal: <?= html_escape($p->canal) ?></div><?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
<script nonce="<?= csp_nonce() ?>">
    document.getElementById('btn-imprimir').addEventListener('click', function () { window.print(); });
</script>
</body>
</html>
