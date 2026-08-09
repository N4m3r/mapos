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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>RDO Nº <?= (int) $rdo->numero ?> — <?= html_escape($obra->nome ?? '') ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; color: #222; margin: 0; background: #eef1f6; font-size: 14px; }
        .wrap { max-width: 720px; margin: 0 auto; padding: 16px; }
        .card { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.06); padding: 18px; }
        .rdo-head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 14px; gap: 12px; flex-wrap: wrap; }
        .rdo-head .emit { font-size: 12px; line-height: 1.5; }
        .rdo-head .emit b { font-size: 15px; }
        .rdo-head img { max-width: 130px; max-height: 70px; }
        .rdo-title { text-align: right; }
        .rdo-title h1 { font-size: 16px; margin: 0; }
        .rdo-title .num { font-size: 20px; font-weight: bold; color: #111; }
        h2 { font-size: 12px; text-transform: uppercase; letter-spacing: .5px; background: #f0f0f0; padding: 6px 8px; margin: 16px 0 8px; border-left: 4px solid #333; }
        .grid { display: flex; flex-wrap: wrap; gap: 8px 24px; }
        .grid div { min-width: 140px; }
        .grid .lbl { color: #666; font-size: 11px; text-transform: uppercase; }
        .box { border: 1px solid #ddd; border-radius: 6px; padding: 10px; white-space: pre-wrap; min-height: 40px; }
        .fotos { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px; }
        .fotos img { width: 100%; height: 130px; border: 1px solid #ccc; border-radius: 6px; object-fit: cover; }
        .assinatura { margin-top: 24px; text-align: center; }
        .assinatura img { max-height: 90px; display: block; margin: 0 auto 4px; }
        .assinatura .linha { border-top: 1px solid #333; width: 260px; max-width: 90%; margin: 0 auto; padding-top: 4px; }
        .rodape { margin-top: 20px; font-size: 11px; color: #888; text-align: center; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
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
            <?php if ($end): ?><div style="min-width:100%"><span class="lbl">Local</span><br><?= html_escape($end) ?></div><?php endif; ?>
        </div>

        <h2>Condições do dia</h2>
        <div class="grid">
            <div><span class="lbl">Data</span><br><?= $dataBr($rdo->data) ?></div>
            <div><span class="lbl">Condição</span><br><?= html_escape($condicoes[$rdo->condicao] ?? ucfirst((string) $rdo->condicao)) ?></div>
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
                    <img src="<?= site_url('rdo/foto/' . html_escape($token) . '/' . (int) $f->idFoto) ?>" alt="Foto RDO" loading="lazy">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php $temAssinatura = ! empty($rdo->assinatura) && strpos((string) $rdo->assinatura, 'base64') !== false; ?>
        <?php if ($temAssinatura): ?>
            <div class="assinatura">
                <img src="<?= $rdo->assinatura ?>" alt="Assinatura do cliente">
                <div class="linha">Assinatura do cliente</div>
            </div>
        <?php endif; ?>

        <div class="rodape">Documento gerado pelo sistema em <?= date('d/m/Y H:i') ?>.</div>
    </div>
</div>
</body>
</html>
