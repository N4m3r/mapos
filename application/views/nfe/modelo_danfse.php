<?php
// Prévia ilustrativa do DANFSe (NFS-e Padrão Nacional / serviços). SEM VALOR FISCAL.
$numeros = fn ($v) => preg_replace('/\D/', '', (string) $v);
$total = 0.0;
$cTribNac = '';
$descricoes = [];
foreach ($servicos as $s) {
    $preco = (float) ($s->preco ?: $s->precoVenda);
    $qtd = (float) ($s->quantidade ?: 1);
    $total += $preco * $qtd;
    $codigo = $numeros($s->codigo_servico_municipio ?? '');
    if ($cTribNac === '' && strlen($codigo) === 6) {
        $cTribNac = $codigo;
    }
    $descricoes[] = trim($s->nome . (empty($s->descricao) ? '' : ' - ' . $s->descricao)) . ' (' . number_format($qtd, 0) . 'x)';
}
$homolog = (int) $config->ambiente === 2;
$aliquota = (float) ($config->aliquota_iss ?? 0);
$vIss = round($total * $aliquota / 100, 2);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Prévia DANFSe - OS <?= $os->idOs ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #000; background: #f0f0f0; margin: 0; padding: 20px; }
        .folha { width: 720px; margin: 0 auto; background: #fff; padding: 18px; border: 1px solid #999; position: relative; }
        .marca { position: fixed; top: 45%; left: 0; width: 100%; text-align: center; font-size: 48px; color: rgba(0,80,160,.12); font-weight: bold; transform: rotate(-20deg); pointer-events: none; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 8px; }
        td, th { border: 1px solid #333; padding: 4px 6px; vertical-align: top; }
        .rot { font-size: 8px; color: #444; text-transform: uppercase; display: block; }
        .cabecalho { text-align: center; border-bottom: 2px solid #05a; padding-bottom: 8px; margin-bottom: 10px; }
        .cabecalho h2 { margin: 0; color: #05a; font-size: 18px; }
        .dir { text-align: right; }
        .cen { text-align: center; }
        .aviso { background: #d9edf7; border: 1px solid #9acfea; padding: 6px; margin-bottom: 10px; text-align: center; font-weight: bold; }
        .toolbar { width: 720px; margin: 0 auto 10px; text-align: right; }
        .btn { background: #05a; color: #fff; border: 0; padding: 8px 14px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        @media print { body { background: #fff; padding: 0; } .toolbar { display: none; } .folha { border: 0; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn" onclick="window.print()">🖨️ Imprimir / Salvar PDF</button>
        <button class="btn" style="background:#888" onclick="window.close()">Fechar</button>
    </div>

    <div class="folha">
        <div class="marca">PRÉVIA — SEM VALOR FISCAL</div>

        <div class="aviso">
            PRÉVIA ILUSTRATIVA — este documento NÃO é uma NFS-e. Serve apenas para conferir o layout antes de emitir.
            <?= $homolog ? ' (Ambiente configurado: HOMOLOGAÇÃO)' : '' ?>
        </div>

        <div class="cabecalho">
            <h2>NFS-e — Nota Fiscal de Serviços Eletrônica</h2>
            <span class="rot">Padrão Nacional • DPS Nº (prévia) <?= html_escape($config->proximo_numero_dps ?? 1) ?> / Série <?= html_escape($config->serie_dps ?? '1') ?></span>
        </div>

        <table>
            <tr><td colspan="2" style="background:#eef"><b>PRESTADOR DE SERVIÇOS</b></td></tr>
            <tr>
                <td style="width:60%"><span class="rot">Nome / Razão Social</span> <?= html_escape($emitente->nome ?? '—') ?></td>
                <td><span class="rot">CNPJ</span> <?= html_escape($emitente->cnpj ?? '—') ?></td>
            </tr>
            <tr>
                <td><span class="rot">Endereço</span> <?= html_escape(trim(($emitente->rua ?? '') . ', ' . ($emitente->numero ?? '') . ' - ' . ($emitente->bairro ?? '') . ' - ' . ($emitente->cidade ?? '') . '/' . ($emitente->uf ?? ''))) ?></td>
                <td><span class="rot">Inscrição Municipal</span> <?= html_escape($config->inscricao_municipal ?: '—') ?></td>
            </tr>
        </table>

        <table>
            <tr><td colspan="2" style="background:#eef"><b>TOMADOR DE SERVIÇOS</b></td></tr>
            <tr>
                <td style="width:60%"><span class="rot">Nome / Razão Social</span> <?= html_escape($os->nomeCliente) ?></td>
                <td><span class="rot">CNPJ / CPF</span> <?= html_escape($os->documento) ?></td>
            </tr>
            <tr>
                <td colspan="2"><span class="rot">Endereço</span> <?= html_escape(trim(($os->rua ?? '') . ', ' . ($os->numero ?? '') . ' - ' . ($os->bairro ?? '') . ' - ' . ($os->cidade ?? '') . '/' . ($os->estado ?? ''))) ?></td>
            </tr>
        </table>

        <table>
            <tr><td colspan="2" style="background:#eef"><b>DISCRIMINAÇÃO DOS SERVIÇOS</b></td></tr>
            <tr>
                <td colspan="2" style="height:70px">
                    OS nr. <?= $os->idOs ?>:<br>
                    <?php if (empty($servicos)) { ?>
                        <span class="cen">Esta OS não possui serviços lançados.</span>
                    <?php } else {
                        echo html_escape(implode('; ', $descricoes));
                    } ?>
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td style="width:34%"><span class="rot">Cód. Tributação Nacional</span> <?= $cTribNac !== '' ? html_escape($cTribNac) : '<span style="color:#c00">não informado</span>' ?></td>
                <td style="width:22%"><span class="rot">Alíquota ISS</span> <?= number_format($aliquota, 2, ',', '.') ?>%</td>
                <td style="width:22%"><span class="rot">Valor do ISS</span> R$ <?= number_format($vIss, 2, ',', '.') ?></td>
                <td class="dir"><span class="rot">Valor do Serviço</span> <b>R$ <?= number_format($total, 2, ',', '.') ?></b></td>
            </tr>
        </table>

        <div style="font-size:9px;color:#555;margin-top:6px">
            Prestador optante pelo Simples Nacional — o ISS é apurado no DAS. Valor do ISS acima é apenas informativo.
        </div>
    </div>
</body>
</html>
