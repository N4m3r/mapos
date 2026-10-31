<?php
$fmt = fn ($v) => number_format((float) $v, 2, ',', '.');
$d = $apuracao['debitos'];
$c = $apuracao['creditos'];
$p = $apuracao['pendentes'];
$s = $apuracao['saldo'];
$tabela = function (array $linhas) use ($fmt) {
    if (!$linhas) {
        echo '<p style="color:#888;margin:6px 0">Nenhum documento neste mês.</p>';

        return;
    }
    echo '<table class="table table-bordered table-condensed"><thead><tr><th>Documento</th><th>Número</th><th>Data</th><th>IBS estadual</th><th>IBS municipal</th><th>CBS</th><th>Créd. presumido ZFM</th></tr></thead><tbody>';
    foreach ($linhas as $l) {
        echo '<tr><td>' . html_escape($l['documento']) . '</td><td>' . html_escape($l['numero']) . '</td><td>' . html_escape($l['data'])
            . '</td><td>' . $fmt($l['v_ibs_uf'] ?? 0) . '</td><td>' . $fmt($l['v_ibs_mun'] ?? 0) . '</td><td>' . $fmt($l['v_cbs'] ?? 0) . '</td><td>' . $fmt($l['v_zfm'] ?? 0) . '</td></tr>';
    }
    echo '</tbody></table>';
};
$quadro = function ($t) use ($fmt) {
    return '<div>IBS estadual: R$ ' . $fmt($t['ibs_uf']) . '</div><div>IBS municipal: R$ ' . $fmt($t['ibs_mun']) . '</div><div>CBS: R$ ' . $fmt($t['cbs']) . '</div>'
        . ($t['zfm'] > 0 ? '<div>Crédito presumido ZFM: R$ ' . $fmt($t['zfm']) . '</div>' : '')
        . '<div style="margin-top:6px;font-size:18px;font-weight:bold">R$ ' . $fmt($t['total']) . '</div>';
};
?>
<style nonce="<?= csp_nonce() ?>">
    .apu-quadro { border: 1px solid #d0d0d0; border-radius: 8px; padding: 14px; background: #fafafa; }
    .apu-deb { border-color: #e6cccc; background: #fbf4f4; }
    .apu-cred { border-color: #cce0cc; background: #f4fbf4; }
    .apu-pend { color: #b36b00; font-size: 13px; margin-bottom: 12px; }
    .apu-titulo { font-weight: bold; margin-bottom: 6px; }
    body.dark-mode .apu-quadro, .dark .apu-quadro, [data-theme="dark"] .apu-quadro { background: #2a2d33; border-color: #444; color: #e6e6e6; }
    body.dark-mode .apu-pend, .dark .apu-pend, [data-theme="dark"] .apu-pend { color: #f0b04a; }
    @media print {
        #sidebar-menu, .menu-left, #header, header, nav, .apu-noprint { display: none !important; }
        .apu-quadro { background: #fff !important; color: #000 !important; border-color: #888 !important; }
    }
</style>
<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-content" style="padding: 16px">
                <div class="ui-cabecalho">
                    <div>
                        <h1 class="ui-titulo"><i class="fas fa-calculator"></i> Apuração de IBS/CBS — <?= html_escape($competencia) ?></h1>
                        <p class="ui-subtitulo">O imposto das suas vendas e serviços, menos o imposto das compras já pagas.</p>
                    </div>
                </div>

                <div class="apu-noprint" style="display:flex;gap:10px;align-items:flex-end;margin-bottom:14px;flex-wrap:wrap">
                    <form action="<?= site_url('entradas/apuracao') ?>" method="get" style="margin:0">
                        <label style="font-size:12px;color:#666;display:block">Mês</label>
                        <input type="month" name="competencia" value="<?= html_escape($competencia) ?>" data-ao-mudar="enviar" />
                    </form>
                    <a href="<?= site_url('entradas/apuracaoCsv?competencia=' . urlencode($competencia)) ?>" class="button btn btn-success">
                        <span class="button__icon"><i class="bx bx-download"></i></span><span class="button__text2">Baixar CSV</span>
                    </a>
                    <button type="button" class="button btn btn-info" id="apu-imprimir">
                        <span class="button__icon"><i class="bx bx-printer"></i></span><span class="button__text2">Imprimir</span>
                    </button>
                    <a href="<?= site_url('entradas?competencia=' . urlencode($competencia)) ?>" class="button btn btn-warning">
                        <span class="button__icon"><i class="bx bx-arrow-back"></i></span><span class="button__text2">Voltar às entradas</span>
                    </a>
                </div>

                <div class="alert alert-warning">
                    <strong>Apuração assistida:</strong> confira com a apuração oficial da Receita/CG-IBS e com o seu contador. Em 2026 os valores são de teste e não são recolhidos.
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:14px;margin-bottom:10px">
                    <div class="apu-quadro apu-deb">
                        <div class="apu-titulo" style="color:#a33">Débitos das vendas e serviços</div>
                        <?= $quadro($d) ?>
                        <small style="color:#999"><?= (int) $d['qtd'] ?> nota(s) autorizada(s) (NF-e, NFC-e, NFS-e)</small>
                    </div>
                    <div class="apu-quadro apu-cred">
                        <div class="apu-titulo" style="color:#3a3">Créditos das compras pagas</div>
                        <?= $quadro($c) ?>
                        <small style="color:#999"><?= (int) $c['qtd'] ?> nota(s) de compra</small>
                    </div>
                    <div class="apu-quadro" style="border-width:2px">
                        <div class="apu-titulo">Saldo</div>
                        <?php foreach ([['IBS estadual', 'ibs_uf'], ['IBS municipal', 'ibs_mun'], ['CBS', 'cbs']] as [$rot, $k]) { ?>
                            <div><?= $rot ?>:
                                <?php if ($s[$k]['credor'] > 0) { ?><span style="color:#3a3">crédito de R$ <?= $fmt($s[$k]['credor']) ?></span>
                                <?php } else { ?><span style="color:#a33">a recolher R$ <?= $fmt($s[$k]['a_recolher']) ?></span><?php } ?>
                            </div>
                        <?php } ?>
                        <div style="margin-top:6px;font-size:18px;font-weight:bold">
                            <?= $s['total']['credor'] > 0 ? 'Crédito a transportar: R$ ' . $fmt($s['total']['credor']) : 'A recolher: R$ ' . $fmt($s['total']['a_recolher']) ?>
                        </div>
                        <small style="color:#999">Débitos − créditos</small>
                    </div>
                </div>

                <div class="apu-pend">
                    Crédito pendente de pagamento: R$ <?= $fmt($p['total']) ?> (<?= (int) $p['qtd'] ?> nota(s)).
                    Só vira crédito depois de informada a data de pagamento da nota de compra.
                </div>

                <?php foreach ([['Detalhe dos débitos', $d['qtd'], $debito['linhas']], ['Detalhe dos créditos (compras pagas)', $c['qtd'], $linhasCred], ['Detalhe do crédito pendente', $p['qtd'], $linhasPend]] as [$titulo, $qtd, $linhas]) { ?>
                    <details class="apu-detalhe" style="margin-bottom:8px">
                        <summary style="cursor:pointer;font-weight:bold"><?= $titulo ?> (<?= (int) $qtd ?>)</summary>
                        <?php $tabela($linhas); ?>
                    </details>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    document.getElementById('apu-imprimir').addEventListener('click', function () {
        document.querySelectorAll('.apu-detalhe').forEach(function (d) { d.open = true; });
        window.print();
    });
</script>
