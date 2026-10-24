<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Comprovante - venda #<?= (int) $venda->idVendas ?></title>
    <style>
        @page { size: 80mm auto; margin: 3mm; }
        body { font-family: 'Courier New', monospace; font-size: 12px; width: 74mm; margin: 0 auto; color: #000; }
        .c { text-align: center; }
        .r { text-align: right; }
        hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 1px 0; }
        .total td { font-size: 14px; font-weight: bold; }
        @media screen { body { margin-top: 10px; } .acoes { text-align: center; margin: 12px 0; } }
        @media print { .acoes { display: none; } }
    </style>
</head>
<body>
    <div class="c">
        <strong><?= html_escape($emitente->nome ?? '') ?></strong><br>
        <?= html_escape($emitente->cnpj ?? '') ?><br>
        <?= html_escape(trim(($emitente->rua ?? '') . ', ' . ($emitente->numero ?? '') . ' - ' . ($emitente->cidade ?? ''), ' ,-')) ?><br>
        <?= html_escape($emitente->telefone ?? '') ?>
    </div>
    <hr>
    <div class="c"><strong>COMPROVANTE DE VENDA #<?= (int) $venda->idVendas ?></strong><br>NÃO É DOCUMENTO FISCAL</div>
    <div class="c"><?= date('d/m/Y H:i') ?></div>
    <hr>
    <table>
        <?php foreach ($itens as $i) { ?>
            <tr><td colspan="2"><?= html_escape($i->descricao) ?></td></tr>
            <tr><td><?= (int) $i->quantidade ?> x <?= number_format((float) $i->preco, 2, ',', '.') ?></td><td class="r"><?= number_format((float) $i->subTotal, 2, ',', '.') ?></td></tr>
        <?php } ?>
    </table>
    <hr>
    <table>
        <tr><td>Subtotal</td><td class="r"><?= number_format((float) $venda->valorTotal, 2, ',', '.') ?></td></tr>
        <?php if ((float) $venda->desconto > 0) { ?><tr><td>Desconto</td><td class="r">-<?= number_format((float) $venda->desconto, 2, ',', '.') ?></td></tr><?php } ?>
        <tr class="total"><td>TOTAL</td><td class="r">R$ <?= number_format((float) $venda->valorTotal - (float) $venda->desconto, 2, ',', '.') ?></td></tr>
        <?php foreach ($pagamentos['pagamentos'] as $p) { ?>
            <tr><td><?= html_escape($formas[$p['forma']] ?? $p['forma']) ?></td><td class="r"><?= number_format($p['valor'], 2, ',', '.') ?></td></tr>
        <?php } ?>
        <?php if ($pagamentos['troco'] > 0) { ?><tr><td>Troco</td><td class="r"><?= number_format($pagamentos['troco'], 2, ',', '.') ?></td></tr><?php } ?>
    </table>
    <hr>
    <div class="c">
        Cliente: <?= html_escape($venda->nomeCliente) ?><br>
        <?php if (! empty($venda->observacoes)) { ?><?= html_escape($venda->observacoes) ?><br><?php } ?>
        Obrigado pela preferência!
    </div>
    <div class="acoes"><button onclick="window.print()">Imprimir</button></div>
    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
</body>
</html>
