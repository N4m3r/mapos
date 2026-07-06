<?php
// DANFSe gerado localmente a partir do XML autorizado (mpdf).
// $d = dados extraídos do XML; $emitente = linha do emitente; $logo = caminho local da logo (ou '').
$fmt = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$dt = function ($iso) {
    $t = strtotime($iso);
    return $t ? date('d/m/Y H:i', $t) : $iso;
};
$homolog = (string) ($d['ambiente'] ?? '') === '2';
?>
<style>
    body { font-family: Arial, sans-serif; font-size: 10px; color: #000; }
    table { width: 100%; border-collapse: collapse; }
    .cab td { border: none; vertical-align: middle; }
    .box { border: 1px solid #333; }
    .box td { border: 1px solid #333; padding: 4px 6px; vertical-align: top; }
    .sec { background: #e8eef5; font-weight: bold; text-transform: uppercase; font-size: 9px; }
    .rot { color: #555; font-size: 8px; text-transform: uppercase; }
    .center { text-align: center; }
    .right { text-align: right; }
    .titulo { color: #0b5394; font-size: 16px; font-weight: bold; }
    .aviso { background: #fff3cd; border: 1px solid #ffe08a; padding: 5px; text-align: center; font-weight: bold; font-size: 9px; }
    h1,h2,h3,p { margin: 0; }
</style>

<table class="cab">
    <tr>
        <td style="width:20%">
            <?php if (!empty($logo)) { ?><img src="<?= $logo ?>" style="max-height:55px;max-width:120px"><?php } ?>
        </td>
        <td class="center">
            <div class="titulo">NFS-e</div>
            <div class="rot">Nota Fiscal de Serviços Eletrônica — Padrão Nacional</div>
        </td>
        <td style="width:28%" class="right">
            <span class="rot">Número</span><br><strong><?= html_escape($d['numero']) ?></strong><br>
            <span class="rot">Competência</span> <?= html_escape($d['competencia']) ?><br>
            <span class="rot">Emissão</span> <?= html_escape($dt($d['dhProc'])) ?>
        </td>
    </tr>
</table>

<?php if ($homolog) { ?>
    <div class="aviso" style="margin:6px 0">AMBIENTE DE HOMOLOGAÇÃO — SEM VALOR FISCAL</div>
<?php } ?>

<table class="box" style="margin-top:6px">
    <tr><td class="sec">Chave de Acesso da NFS-e</td></tr>
    <tr><td class="center" style="font-family:monospace;letter-spacing:1px"><?= html_escape($d['chave']) ?></td></tr>
</table>

<table class="box" style="margin-top:6px">
    <tr><td class="sec" colspan="2">Prestador de Serviços</td></tr>
    <tr>
        <td style="width:65%"><span class="rot">Nome / Razão Social</span><br><?= html_escape($d['prestNome'] ?: ($emitente->nome ?? '')) ?></td>
        <td><span class="rot">CNPJ</span><br><?= html_escape($d['prestCnpj'] ?: ($emitente->cnpj ?? '')) ?><br>
            <span class="rot">Insc. Municipal</span> <?= html_escape(trim($d['prestIM'])) ?></td>
    </tr>
    <tr><td colspan="2"><span class="rot">Endereço</span> <?= html_escape($d['prestEnd']) ?> — <?= html_escape($d['prestMun']) ?></td></tr>
</table>

<table class="box" style="margin-top:6px">
    <tr><td class="sec" colspan="2">Tomador de Serviços</td></tr>
    <tr>
        <td style="width:65%"><span class="rot">Nome / Razão Social</span><br><?= html_escape($d['tomaNome']) ?></td>
        <td><span class="rot">CNPJ / CPF</span><br><?= html_escape($d['tomaDoc']) ?></td>
    </tr>
</table>

<table class="box" style="margin-top:6px">
    <tr><td class="sec" colspan="2">Serviço Prestado</td></tr>
    <tr>
        <td style="width:50%"><span class="rot">Cód. Tributação Nacional</span> <?= html_escape($d['cTribNac']) ?></td>
        <td><span class="rot">Cód. Tributação Municipal</span> <?= html_escape($d['cTribMun']) ?></td>
    </tr>
    <tr><td colspan="2"><span class="rot">Discriminação</span><br><?= nl2br(html_escape($d['servDesc'])) ?></td></tr>
    <?php if (!empty($d['xTribNac'])) { ?>
        <tr><td colspan="2"><span class="rot">Item da Lista</span> <?= html_escape($d['xTribNac']) ?></td></tr>
    <?php } ?>
</table>

<table class="box" style="margin-top:6px">
    <tr><td class="sec" colspan="4">Valores</td></tr>
    <tr>
        <td class="center"><span class="rot">Valor do Serviço</span><br><strong><?= $fmt($d['vServ']) ?></strong></td>
        <td class="center"><span class="rot">Alíquota ISS</span><br><?= $d['pAliq'] !== '' ? number_format((float) $d['pAliq'], 2, ',', '.') . '%' : '-' ?></td>
        <td class="center"><span class="rot">Valor do ISS</span><br><?= $d['vISS'] !== '' ? $fmt($d['vISS']) : 'R$ 0,00' ?></td>
        <td class="center" style="background:#e8f5e9"><span class="rot">Valor Líquido</span><br><strong><?= $fmt($d['vLiq'] !== '' ? $d['vLiq'] : $d['vServ']) ?></strong></td>
    </tr>
</table>

<p style="margin-top:8px;font-size:8px;color:#555">
    Documento gerado pelo MapOS a partir do XML autorizado pelo Sistema Nacional NFS-e.
    Consulte a autenticidade pela chave de acesso no portal nacional (nfse.gov.br).
</p>
