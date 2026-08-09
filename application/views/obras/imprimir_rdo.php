<?php
$dataBr = function ($d) {
    return (! empty($d) && $d !== '0000-00-00') ? date('d/m/Y', strtotime($d)) : '—';
};
$end = trim(($obra->logradouro ?? '') . ', ' . ($obra->numero ?? '') . ' - ' . ($obra->bairro ?? '') . ' - ' . ($obra->cidade ?? '') . '/' . ($obra->uf ?? ''), ' ,-/');
$condicoes = ['praticavel' => 'Praticável', 'parcial' => 'Parcialmente praticável', 'impraticavel' => 'Impraticável'];
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>RDO Nº <?= (int) $rdo->numero ?> — <?= html_escape($obra->nome ?? '') ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #222; margin: 24px; font-size: 13px; }
        .rdo-head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 14px; }
        .rdo-head .emit { font-size: 12px; line-height: 1.5; }
        .rdo-head .emit b { font-size: 15px; }
        .rdo-head img { max-width: 140px; max-height: 80px; }
        .rdo-title { text-align: right; }
        .rdo-title h1 { font-size: 18px; margin: 0; }
        .rdo-title .num { font-size: 22px; font-weight: bold; color: #111; }
        h2 { font-size: 13px; text-transform: uppercase; letter-spacing: .5px; background: #f0f0f0; padding: 6px 8px; margin: 16px 0 8px; border-left: 4px solid #333; }
        .grid { display: flex; flex-wrap: wrap; gap: 6px 24px; }
        .grid div { min-width: 150px; }
        .grid .lbl { color: #666; font-size: 11px; text-transform: uppercase; }
        .box { border: 1px solid #ccc; border-radius: 4px; padding: 10px; white-space: pre-wrap; min-height: 40px; }
        .fotos { display: flex; flex-wrap: wrap; gap: 8px; }
        .fotos img { width: 32%; border: 1px solid #ccc; border-radius: 4px; object-fit: cover; }
        .assinatura { margin-top: 28px; text-align: center; }
        .assinatura img { max-height: 90px; display: block; margin: 0 auto 4px; }
        .assinatura .linha { border-top: 1px solid #333; width: 280px; margin: 0 auto; padding-top: 4px; }
        .rodape { margin-top: 24px; font-size: 11px; color: #888; text-align: center; }
        .btn-print { position: fixed; top: 12px; right: 12px; padding: 8px 16px; background: #2b7; color: #fff; border: 0; border-radius: 4px; cursor: pointer; font-size: 14px; }
        @media print { .btn-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <button class="btn-print" onclick="window.print()">🖨 Imprimir</button>

    <div class="rdo-head">
        <div class="emit">
            <?php if (! empty($emitente->url_logo)): ?><img src="<?= $emitente->url_logo ?>"><br><?php endif; ?>
            <b><?= html_escape($emitente->nome ?? '') ?></b><br>
            <?php if (! empty($emitente->cnpj) && $emitente->cnpj !== '00.000.000/0000-00'): ?>CNPJ: <?= html_escape($emitente->cnpj) ?><br><?php endif; ?>
            <?php if (! empty($emitente->telefone)): ?>Tel: <?= html_escape($emitente->telefone) ?><br><?php endif; ?>
        </div>
        <div class="rdo-title">
            <h1>Relatório Diário de Obra</h1>
            <div class="num">RDO Nº <?= (int) $rdo->numero ?></div>
            <div><?= $dataBr($rdo->data) ?></div>
        </div>
    </div>

    <h2>Projeto</h2>
    <div class="grid">
        <div><span class="lbl">Projeto</span><br><?= html_escape($obra->nome ?? '—') ?><?= ! empty($obra->codigo) ? ' (#' . html_escape($obra->codigo) . ')' : '' ?></div>
        <div><span class="lbl">Cliente</span><br><?= html_escape($obra->nomeCliente ?? '—') ?></div>
        <div style="min-width:100%"><span class="lbl">Local</span><br><?= html_escape($end ?: '—') ?></div>
    </div>

    <h2>Condições do dia</h2>
    <div class="grid">
        <div><span class="lbl">Data</span><br><?= $dataBr($rdo->data) ?></div>
        <div><span class="lbl">Clima (M/T/N)</span><br><?= html_escape(($rdo->clima_manha ?: '-') . ' / ' . ($rdo->clima_tarde ?: '-') . ' / ' . ($rdo->clima_noite ?: '-')) ?></div>
        <div><span class="lbl">Condição</span><br><?= html_escape($condicoes[$rdo->condicao] ?? ucfirst((string) $rdo->condicao)) ?></div>
        <div><span class="lbl">Efetivo</span><br><?= (int) $rdo->efetivo_total ?> pessoa(s)</div>
        <div><span class="lbl">Responsável</span><br><?= html_escape($responsavel ?: '—') ?></div>
    </div>

    <h2>O que foi feito (atividades)</h2>
    <div class="box"><?= $rdo->atividades ? html_escape($rdo->atividades) : '—' ?></div>

    <?php if (! empty($rdo->ocorrencias)): ?>
        <h2>Ocorrências</h2>
        <div class="box"><?= html_escape($rdo->ocorrencias) ?></div>
    <?php endif; ?>

    <?php if (! empty($rdo->observacoes)): ?>
        <h2>Observações</h2>
        <div class="box"><?= html_escape($rdo->observacoes) ?></div>
    <?php endif; ?>

    <?php if (! empty($fotos)): ?>
        <h2>Fotos</h2>
        <div class="fotos">
            <?php foreach ($fotos as $f): ?>
                <img src="<?= site_url('obras/fotoRdo/' . (int) $f->idFoto) ?>" alt="Foto RDO">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="assinatura">
        <?php if (! empty($rdo->assinatura) && strpos((string) $rdo->assinatura, 'base64') !== false): ?>
            <img src="<?= $rdo->assinatura ?>" alt="Assinatura">
        <?php endif; ?>
        <div class="linha"><?= html_escape($responsavel ?: 'Responsável') ?></div>
    </div>

    <div class="rodape">Emitido em <?= date('d/m/Y H:i') ?></div>

    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
</body>
</html>
