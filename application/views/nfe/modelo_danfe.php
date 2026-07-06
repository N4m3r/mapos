<?php
// Prévia ilustrativa do DANFE (NF-e / produtos). SEM VALOR FISCAL.
$numeros = fn ($v) => preg_replace('/\D/', '', (string) $v);
$totalProdutos = 0.0;
foreach ($produtos as $p) {
    $totalProdutos += (float) $p->quantidade * (float) $p->preco;
}
$homolog = (int) $config->ambiente === 2;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Prévia DANFE - OS <?= $os->idOs ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #000; background: #f0f0f0; margin: 0; padding: 20px; }
        .folha { width: 800px; margin: 0 auto; background: #fff; padding: 14px; border: 1px solid #999; position: relative; }
        .marca { position: fixed; top: 45%; left: 0; width: 100%; text-align: center; font-size: 52px; color: rgba(200,0,0,.12); font-weight: bold; transform: rotate(-20deg); pointer-events: none; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #333; padding: 3px 5px; vertical-align: top; }
        .sem-borda td { border: none; padding: 1px 3px; }
        .rot { font-size: 8px; color: #444; text-transform: uppercase; display: block; }
        .box-danfe { text-align: center; }
        .box-danfe b { font-size: 18px; display: block; }
        .titulo { font-size: 14px; font-weight: bold; }
        .dir { text-align: right; }
        .cen { text-align: center; }
        .aviso { background: #fff3cd; border: 1px solid #ffe08a; padding: 6px; margin-bottom: 10px; text-align: center; font-weight: bold; }
        .toolbar { width: 800px; margin: 0 auto 10px; text-align: right; }
        .btn { background: #256; color: #fff; border: 0; padding: 8px 14px; border-radius: 4px; cursor: pointer; font-size: 12px; }
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
            PRÉVIA ILUSTRATIVA — este documento NÃO é uma NF-e. Serve apenas para conferir o layout antes de emitir.
            <?= $homolog ? ' (Ambiente configurado: HOMOLOGAÇÃO)' : '' ?>
            <?php if (!empty($previaAviso)) { ?>
                <div style="font-weight:normal;font-size:10px;margin-top:4px;color:#666"><?= html_escape($previaAviso) ?></div>
            <?php } ?>
        </div>

        <!-- Cabeçalho: emitente + bloco DANFE -->
        <table>
            <tr>
                <td style="width:55%">
                    <span class="rot">Emitente</span>
                    <?php if (!empty($emitente->url_logo)) { ?>
                        <img src="<?= $emitente->url_logo ?>" alt="Logo" style="max-height:45px;max-width:150px;float:left;margin:0 8px 4px 0">
                    <?php } ?>
                    <span class="titulo"><?= html_escape($emitente->nome ?? '—') ?></span><br>
                    <?= html_escape(trim(($emitente->rua ?? '') . ', ' . ($emitente->numero ?? '') . ' - ' . ($emitente->bairro ?? ''))) ?><br>
                    <?= html_escape(trim(($emitente->cidade ?? '') . '/' . ($emitente->uf ?? '') . ' - CEP ' . ($emitente->cep ?? ''))) ?><br>
                    Fone: <?= html_escape($emitente->telefone ?? '—') ?>
                </td>
                <td class="box-danfe" style="width:20%">
                    <b>DANFE</b>
                    <span class="rot">Documento Auxiliar da Nota Fiscal Eletrônica</span>
                    <div style="margin-top:6px">0 - Entrada<br><b>1 - Saída</b></div>
                </td>
                <td style="width:25%">
                    <span class="rot">Nº (prévia)</span>
                    <b><?= str_pad((string) ($config->proximo_numero_nfe ?? 1), 9, '0', STR_PAD_LEFT) ?></b><br>
                    <span class="rot">Série</span> <?= html_escape($config->serie_nfe ?? '1') ?><br>
                    <span class="rot">Modelo</span> 55
                </td>
            </tr>
        </table>

        <table style="border-top:0">
            <tr>
                <td colspan="3"><span class="rot">Chave de acesso</span> <span class="cen" style="letter-spacing:2px">•••• (gerada na emissão) ••••</span></td>
            </tr>
            <tr>
                <td style="width:55%"><span class="rot">Natureza da operação</span> VENDA DE MERCADORIA</td>
                <td style="width:20%"><span class="rot">CNPJ</span> <?= html_escape($emitente->cnpj ?? '—') ?></td>
                <td style="width:25%"><span class="rot">Inscrição Estadual</span> <?= html_escape($emitente->ie ?? '—') ?></td>
            </tr>
        </table>

        <!-- Destinatário -->
        <div class="rot" style="margin-top:8px;font-weight:bold">Destinatário / Remetente</div>
        <table>
            <tr>
                <td style="width:55%"><span class="rot">Nome / Razão Social</span> <?= html_escape($os->nomeCliente) ?></td>
                <td style="width:25%"><span class="rot">CNPJ / CPF</span> <?= html_escape($os->documento) ?></td>
                <td style="width:20%"><span class="rot">Inscrição Estadual</span> <?= html_escape($os->ie ?? 'ISENTO') ?></td>
            </tr>
            <tr>
                <td><span class="rot">Endereço</span> <?= html_escape(trim(($os->rua ?? '') . ', ' . ($os->numero ?? '') . ' - ' . ($os->bairro ?? ''))) ?></td>
                <td><span class="rot">Município / UF</span> <?= html_escape(trim(($os->cidade ?? '') . '/' . ($os->estado ?? ''))) ?></td>
                <td><span class="rot">CEP</span> <?= html_escape($os->cep ?? '—') ?></td>
            </tr>
        </table>

        <!-- Produtos -->
        <div class="rot" style="margin-top:8px;font-weight:bold">Dados dos Produtos / Serviços</div>
        <table>
            <thead>
                <tr style="background:#eee">
                    <th>Cód.</th><th>Descrição</th><th>NCM</th><th>CFOP</th><th>Un</th>
                    <th class="dir">Qtd</th><th class="dir">V. Unit.</th><th class="dir">V. Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($produtos)) { ?>
                    <tr><td colspan="8" class="cen">Esta OS não possui produtos. (Para serviços, veja a prévia do DANFSe.)</td></tr>
                <?php } else {
                    foreach ($produtos as $p) {
                        $sub = (float) $p->quantidade * (float) $p->preco;
                        $ncm = $numeros($p->ncm ?? '');
                        $cfop = $numeros($p->cfop ?? '') ?: ($config->cfop_padrao ?? '5102'); ?>
                        <tr>
                            <td><?= html_escape($p->produtos_id) ?></td>
                            <td><?= html_escape($p->descricao) ?></td>
                            <td class="cen"><?= $ncm !== '' ? html_escape($ncm) : '<span style="color:#c00">falta NCM</span>' ?></td>
                            <td class="cen"><?= html_escape($cfop) ?></td>
                            <td class="cen"><?= html_escape($p->unidade ?: 'UN') ?></td>
                            <td class="dir"><?= number_format((float) $p->quantidade, 2, ',', '.') ?></td>
                            <td class="dir"><?= number_format((float) $p->preco, 2, ',', '.') ?></td>
                            <td class="dir"><?= number_format($sub, 2, ',', '.') ?></td>
                        </tr>
                    <?php }
                } ?>
            </tbody>
        </table>

        <!-- Totais -->
        <div class="rot" style="margin-top:8px;font-weight:bold">Cálculo do Imposto</div>
        <table>
            <tr>
                <td>ICMS (Simples Nacional - CSOSN <?= html_escape($config->csosn_padrao ?? '102') ?>)<br><b>R$ 0,00</b></td>
                <td>PIS / COFINS<br><b>Sem destaque</b></td>
                <td class="dir">Valor Total dos Produtos<br><b>R$ <?= number_format($totalProdutos, 2, ',', '.') ?></b></td>
                <td class="dir">Valor Total da Nota<br><b>R$ <?= number_format($totalProdutos, 2, ',', '.') ?></b></td>
            </tr>
        </table>

        <!-- Dados adicionais -->
        <div class="rot" style="margin-top:8px;font-weight:bold">Dados Adicionais</div>
        <table>
            <tr><td style="height:50px">
                DOCUMENTO EMITIDO POR ME OU EPP OPTANTE PELO SIMPLES NACIONAL. NÃO GERA DIREITO A CRÉDITO FISCAL DE IPI.
                Ordem de Serviço nr. <?= $os->idOs ?>.
            </td></tr>
        </table>
    </div>
</body>
</html>
