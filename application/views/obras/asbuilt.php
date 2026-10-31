<?php
$end = trim(($obra->logradouro ?? '') . ', ' . ($obra->numero ?? '') . ' - ' . ($obra->bairro ?? '') . ' - ' . ($obra->cidade ?? '') . '/' . ($obra->uf ?? ''), ' ,-/');
$fmtM = function ($v) {
    return (float) $v > 0 ? number_format((float) $v, 2, ',', '.') : '—';
};
$porTipo = [];
foreach ($pontos as $pt) {
    $porTipo[$pt->tipo][] = $pt;
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>As-built — <?= html_escape($obra->nome ?? '') ?></title>
    <style>
        * { box-sizing: border-box; }
        @page { size: A4; margin: 12mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #222; margin: 24px; font-size: 12px; }
        .ab-head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 14px; }
        .ab-head .emit { font-size: 12px; line-height: 1.5; }
        .ab-head .emit b { font-size: 15px; }
        .ab-head img { max-width: 140px; max-height: 80px; }
        .ab-title { text-align: right; }
        .ab-title h1 { font-size: 18px; margin: 0; }
        h2 { font-size: 13px; text-transform: uppercase; letter-spacing: .5px; background: #f0f0f0; padding: 6px 8px; margin: 16px 0 8px; border-left: 4px solid #333; }
        .grid { display: flex; flex-wrap: wrap; gap: 6px 24px; }
        .grid div { min-width: 150px; }
        .grid .lbl { color: #666; font-size: 11px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid #bbb; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f6f6f6; font-size: 11px; text-transform: uppercase; }
        td.num, th.num { text-align: right; }
        tr { page-break-inside: avoid; }
        .assinaturas { display: flex; gap: 40px; margin-top: 48px; page-break-inside: avoid; }
        .assinaturas div { flex: 1; border-top: 1px solid #333; text-align: center; padding-top: 4px; }
        .rodape { margin-top: 24px; font-size: 11px; color: #888; text-align: center; }
        .vazio { color: #777; padding: 10px 0; }
        .btn-print { position: fixed; top: 12px; right: 12px; padding: 8px 16px; background: #2b7; color: #fff; border: 0; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .ab-acoes { display: flex; justify-content: flex-end; gap: 8px; margin: -4px 0 10px; }
        .ab-acoes a { font-size: 12px; color: #2b7; text-decoration: none; border: 1px solid #2b7; border-radius: 4px; padding: 4px 10px; }
        .ab-foto img { display: block; max-width: 110px; max-height: 110px; height: auto; width: auto; border: 1px solid #ccc; }
        @media print { .btn-print, .ab-nao-imprimir { display: none !important; } body { margin: 0; } }
    </style>
    <script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/acoes.js') ?>?v=<?= asset_versao('assets/js/acoes.js') ?>"></script>
</head>
<body>
    <button class="btn-print" data-acao="imprimir">Imprimir</button>

    <div class="ab-head">
        <div class="emit">
            <?php if (! empty($emitente->url_logo)): ?><img src="<?= html_escape($emitente->url_logo) ?>" alt=""><br><?php endif; ?>
            <b><?= html_escape($emitente->nome ?? '') ?></b><br>
            <?php if (! empty($emitente->cnpj) && $emitente->cnpj !== '00.000.000/0000-00'): ?>CNPJ: <?= html_escape($emitente->cnpj) ?><br><?php endif; ?>
            <?php if (! empty($emitente->telefone)): ?>Tel: <?= html_escape($emitente->telefone) ?><br><?php endif; ?>
        </div>
        <div class="ab-title">
            <h1>As-built — pontos instalados</h1>
            <div><?= date('d/m/Y') ?></div>
        </div>
    </div>

    <div class="ab-acoes ab-nao-imprimir">
        <?php if (! empty($com_fotos)): ?>
            <a href="<?= site_url('obras/asbuilt/' . (int) $obra->idObra) ?>">Sem fotos</a>
        <?php else: ?>
            <a href="<?= site_url('obras/asbuilt/' . (int) $obra->idObra . '?fotos=1') ?>">Imprimir com fotos</a>
        <?php endif; ?>
    </div>

    <h2>Projeto</h2>
    <div class="grid">
        <div><span class="lbl">Projeto</span><br><?= html_escape($obra->nome ?? '—') ?><?= ! empty($obra->codigo) ? ' (#' . html_escape($obra->codigo) . ')' : '' ?></div>
        <div><span class="lbl">Cliente</span><br><?= html_escape($obra->nomeCliente ?? '—') ?></div>
        <div><span class="lbl">Tipo</span><br><?= html_escape($obra->tipo_obra ?: '—') ?></div>
        <div style="min-width:100%"><span class="lbl">Local</span><br><?= html_escape($end ?: '—') ?></div>
    </div>

    <h2>Resumo</h2>
    <?php if (empty($resumo['por_tipo'])): ?>
        <div class="vazio">Nenhum ponto cadastrado neste projeto.</div>
    <?php else: ?>
        <table>
            <thead><tr><th>Tipo</th><th class="num">Pontos</th><th class="num">Testados</th></tr></thead>
            <tbody>
                <?php foreach ($resumo['por_tipo'] as $r): ?>
                    <tr><td><?= html_escape($r['rotulo']) ?></td><td class="num"><?= (int) $r['total'] ?></td><td class="num"><?= (int) $r['testados'] ?></td></tr>
                <?php endforeach; ?>
                <tr><th>Total</th><th class="num"><?= (int) $resumo['total'] ?></th><th class="num"><?= (int) ($resumo['por_status']['testado'] ?? 0) ?></th></tr>
            </tbody>
        </table>
        <div>Cabo passado: <strong><?= number_format((float) $resumo['cabo_m'], 2, ',', '.') ?> m</strong><?php if ($resumo['progresso'] !== null): ?> &middot; Progresso: <strong><?= number_format((float) $resumo['progresso'], 1, ',', '.') ?>%</strong><?php endif; ?></div>
    <?php endif; ?>

    <?php foreach ($tipos as $tk => $tinfo): if (empty($porTipo[$tk])) continue; ?>
        <h2><?= html_escape($tinfo['rotulo']) ?> (<?= count($porTipo[$tk]) ?>)</h2>
        <table>
            <thead><tr><?php if (! empty($com_fotos)): ?><th>Foto</th><?php endif; ?><th>Identificação</th><th>Local</th><th>IP</th><th>Canal</th><th>Porta</th><th>Modelo</th><th>Série / MAC</th><th class="num">Metros</th><th>Status</th><?php if ($tk === 'camera'): ?><th>Teste de imagem</th><?php endif; ?><th>Atualizado em</th></tr></thead>
            <tbody>
                <?php foreach ($porTipo[$tk] as $pt): ?>
                    <tr>
                        <?php if (! empty($com_fotos)): $fotoLista = array_values($fotos_pontos[$pt->idPonto] ?? []); $primeiraFoto = $fotoLista[0] ?? null; ?>
                            <td class="ab-foto"><?php if ($primeiraFoto): ?><img src="<?= site_url('obras/fotoPonto/' . (int) $primeiraFoto->idFoto) ?>" alt="Foto de <?= html_escape($pt->identificacao) ?>"><?php else: ?>—<?php endif; ?></td>
                        <?php endif; ?>
                        <td><strong><?= html_escape($pt->identificacao) ?></strong></td>
                        <td><?= html_escape($pt->local ?: '—') ?></td>
                        <td><?= html_escape($pt->ip ?: '—') ?></td>
                        <td><?= html_escape($pt->canal ?: '—') ?></td>
                        <td><?= html_escape($pt->porta ?: '—') ?></td>
                        <td><?= html_escape($pt->modelo ?: '—') ?></td>
                        <td><?= html_escape($pt->serie ?: '—') ?></td>
                        <td class="num"><?= $fmtM($pt->metragem_cabo) ?></td>
                        <td><?= html_escape($status_lista[$pt->status]['rotulo'] ?? $pt->status) ?></td>
                        <?php if ($tk === 'camera'): $tst = $testes_pontos[(int) $pt->idPonto] ?? null; ?>
                            <td><?php if ($tst): ?><?= ! empty($tst->aprovado) ? '✓' : '✗' ?> <?= (int) $tst->retencao_dias ?> d · <?= date('d/m/Y', strtotime($tst->data)) ?><?php else: ?>—<?php endif; ?></td>
                        <?php endif; ?>
                        <td><?= ! empty($pt->atualizado_em) ? date('d/m/Y', strtotime($pt->atualizado_em)) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>

    <div class="assinaturas">
        <div>Responsável técnico</div>
        <div>Cliente: <?= html_escape($obra->nomeCliente ?? '') ?></div>
    </div>

    <div class="rodape">Emitido em <?= date('d/m/Y H:i') ?></div>
</body>
</html>
