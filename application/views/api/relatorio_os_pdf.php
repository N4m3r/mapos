<?php
/**
 * OS em PDF para o app (mPDF). Layout enxuto, sem Bootstrap: o mPDF so entende
 * CSS simples, e a impressao da web (os/imprimirOs) depende do navegador.
 *
 * Variaveis: $result (Os_model::getById), $produtos, $servicos, $emitente.
 */
$e = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$dinheiro = static function ($v) {
    return 'R$ ' . number_format((float) $v, 2, ',', '.');
};
$data = static function ($v) {
    $t = $v ? strtotime((string) $v) : false;

    return $t ? date('d/m/Y', $t) : '';
};
$totalProdutos = 0.0;
foreach ($produtos as $p) {
    $totalProdutos += (float) ($p->subTotal ?? ((float) ($p->preco ?? 0) * (float) ($p->quantidade ?? 0)));
}
$totalServicos = 0.0;
foreach ($servicos as $s) {
    $totalServicos += (float) ($s->subTotal ?? ((float) ($s->preco ?? 0) * (float) ($s->quantidade ?? 1)));
}
$total = $totalProdutos + $totalServicos;
$comDesconto = (float) ($result->valor_desconto ?? 0);
$logo = trim((string) ($emitente->url_logo ?? ''));
$logoArquivo = $logo !== '' ? convertUrlToUploadsPath($logo) : null;
$textos = [
    'Descrição' => $result->descricaoProduto ?? null,
    'Defeito apresentado' => $result->defeito ?? null,
    'Observações' => $result->observacoes ?? null,
    'Laudo técnico' => $result->laudoTecnico ?? null,
];
?>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: sans-serif; font-size: 10pt; color: #222; }
    .topo td { vertical-align: top; }
    .empresa { font-size: 9pt; color: #444; }
    h1 { font-size: 15pt; margin: 0; }
    h2 { font-size: 11pt; margin: 14px 0 4px; border-bottom: 1px solid #999; padding-bottom: 2px; }
    table.grade { width: 100%; border-collapse: collapse; }
    table.grade th { background: #eee; text-align: left; font-size: 9pt; padding: 4px; border: 1px solid #ccc; }
    table.grade td { padding: 4px; border: 1px solid #ccc; font-size: 9pt; }
    .num { text-align: right; }
    .rotulo { color: #666; font-size: 8pt; }
    .total { text-align: right; font-size: 11pt; margin-top: 6px; }
    .texto { margin: 2px 0 8px; }
</style>
</head>
<body>
<table width="100%" class="topo">
    <tr>
        <?php if ($logoArquivo && is_file($logoArquivo)) { ?>
            <td width="110"><img src="<?= $e($logoArquivo) ?>" style="max-width: 100px; max-height: 70px;"></td>
        <?php } ?>
        <td class="empresa">
            <b><?= $e($emitente->nome ?? '') ?></b><br>
            <?= ! empty($emitente->cnpj) ? 'CNPJ ' . $e($emitente->cnpj) . '<br>' : '' ?>
            <?= $e(trim(($emitente->rua ?? '') . ', ' . ($emitente->numero ?? '') . ' - ' . ($emitente->bairro ?? '') . ' - ' . ($emitente->cidade ?? '') . '/' . ($emitente->uf ?? ''), ' ,-/')) ?><br>
            <?= $e(trim(($emitente->telefone ?? '') . '  ' . ($emitente->email ?? ''))) ?>
        </td>
        <td width="170" style="text-align: right;">
            <h1>OS <?= $e(str_pad((string) $result->idOs, 4, '0', STR_PAD_LEFT)) ?></h1>
            <span class="rotulo">Status</span> <?= $e($result->status ?? '') ?><br>
            <?php if (! empty($result->chamado_id)) { ?><span class="rotulo">Chamado</span> <?= $e($result->chamado_id) ?><br><?php } ?>
            <span class="rotulo">Entrada</span> <?= $e($data($result->dataInicial ?? null)) ?>
            <?php if (! empty($result->dataFinal)) { ?><br><span class="rotulo">Previsão</span> <?= $e($data($result->dataFinal)) ?><?php } ?>
        </td>
    </tr>
</table>

<h2>Cliente</h2>
<table width="100%">
    <tr>
        <td><span class="rotulo">Nome</span><br><?= $e($result->nomeCliente ?? '') ?></td>
        <td><span class="rotulo">Documento</span><br><?= $e($result->documento ?? '') ?></td>
        <td><span class="rotulo">Contato</span><br><?= $e(trim(($result->celular_cliente ?? '') . ' ' . ($result->telefone_cliente ?? ''))) ?></td>
    </tr>
    <tr>
        <td colspan="2"><span class="rotulo">Endereço</span><br><?= $e(trim(($result->rua ?? '') . ', ' . ($result->numero ?? '') . ' - ' . ($result->bairro ?? '') . ' - ' . ($result->cidade ?? '') . '/' . ($result->estado ?? ''), ' ,-/')) ?></td>
        <td><span class="rotulo">Técnico</span><br><?= $e($result->nome ?? '') ?></td>
    </tr>
</table>

<?php foreach ($textos as $rotulo => $texto) {
    if (trim(strip_tags((string) $texto)) === '') {
        continue;
    } ?>
    <h2><?= $e($rotulo) ?></h2>
    <div class="texto"><?= nl2br($e(strip_tags((string) $texto))) ?></div>
<?php } ?>

<?php if ($servicos) { ?>
    <h2>Serviços</h2>
    <table class="grade">
        <tr><th>Serviço</th><th class="num">Qtd</th><th class="num">Preço</th><th class="num">Subtotal</th></tr>
        <?php foreach ($servicos as $s) {
            $qtd = (float) ($s->quantidade ?? 1);
            $preco = (float) ($s->preco ?? ($s->precoVenda ?? 0)); ?>
            <tr>
                <td><?= $e($s->nome ?? '') ?></td>
                <td class="num"><?= $e(rtrim(rtrim(number_format($qtd, 2, ',', '.'), '0'), ',')) ?></td>
                <td class="num"><?= $dinheiro($preco) ?></td>
                <td class="num"><?= $dinheiro($s->subTotal ?? $preco * $qtd) ?></td>
            </tr>
        <?php } ?>
    </table>
<?php } ?>

<?php if ($produtos) { ?>
    <h2>Produtos</h2>
    <table class="grade">
        <tr><th>Produto</th><th class="num">Qtd</th><th class="num">Preço</th><th class="num">Subtotal</th></tr>
        <?php foreach ($produtos as $p) {
            $qtd = (float) ($p->quantidade ?? 0);
            $preco = (float) ($p->preco ?? ($p->precoVenda ?? 0)); ?>
            <tr>
                <td><?= $e($p->descricao ?? '') ?></td>
                <td class="num"><?= $e(rtrim(rtrim(number_format($qtd, 2, ',', '.'), '0'), ',')) ?></td>
                <td class="num"><?= $dinheiro($preco) ?></td>
                <td class="num"><?= $dinheiro($p->subTotal ?? $preco * $qtd) ?></td>
            </tr>
        <?php } ?>
    </table>
<?php } ?>

<?php if ($servicos || $produtos) { ?>
    <div class="total">
        <?php if ($comDesconto > 0 && $comDesconto < $total) { ?>
            Subtotal <?= $dinheiro($total) ?><br>
            Desconto <?= $dinheiro($total - $comDesconto) ?><br>
            <b>Total <?= $dinheiro($comDesconto) ?></b>
        <?php } else { ?>
            <b>Total <?= $dinheiro($total) ?></b>
        <?php } ?>
    </div>
<?php } ?>

<?php if (! empty($result->garantia) || ! empty($result->textoGarantia)) { ?>
    <h2>Garantia</h2>
    <div class="texto">
        <?= ! empty($result->garantia) ? $e($result->garantia) . ' dia(s). ' : '' ?>
        <?= nl2br($e(strip_tags((string) ($result->textoGarantia ?? '')))) ?>
    </div>
<?php } ?>

<p class="rotulo" style="margin-top: 24px;">Gerado em <?= date('d/m/Y H:i') ?></p>
</body>
</html>
