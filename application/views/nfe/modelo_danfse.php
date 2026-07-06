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
$emitEndereco = trim(($emitente->rua ?? '') . ', ' . ($emitente->numero ?? '') . ' - ' . ($emitente->bairro ?? '') . ' - ' . ($emitente->cidade ?? '') . '/' . ($emitente->uf ?? '') . ' - CEP ' . ($emitente->cep ?? ''));
$tomaEndereco = trim(($os->rua ?? '') . ', ' . ($os->numero ?? '') . ' - ' . ($os->bairro ?? '') . ' - ' . ($os->cidade ?? '') . '/' . ($os->estado ?? ''));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Prévia DANFSe - OS <?= $os->idOs ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; background: #eceff1; margin: 0; padding: 20px; }
        .folha { width: 740px; margin: 0 auto; background: #fff; border: 1px solid #607d8b; position: relative; }
        .marca { position: fixed; top: 46%; left: 0; width: 100%; text-align: center; font-size: 46px; color: rgba(0,80,160,.10); font-weight: bold; transform: rotate(-20deg); pointer-events: none; }
        .faixa { background: #0b5394; color: #fff; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; }
        .faixa h2 { margin: 0; font-size: 15px; }
        .faixa small { display: block; opacity: .85; font-size: 9px; }
        .sec { padding: 0 14px; }
        .bloco { border: 1px solid #90a4ae; margin-top: 8px; }
        .bloco > .cab { background: #eceff1; border-bottom: 1px solid #90a4ae; padding: 3px 8px; font-weight: bold; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; }
        .bloco > .corpo { padding: 6px 8px; }
        .grid { display: flex; flex-wrap: wrap; }
        .grid .cel { padding: 4px 8px; border-right: 1px solid #cfd8dc; flex: 1; }
        .grid .cel:last-child { border-right: 0; }
        .rot { font-size: 8px; color: #607d8b; text-transform: uppercase; display: block; }
        .valores { display: flex; text-align: center; }
        .valores .v { flex: 1; padding: 6px 4px; border-right: 1px solid #cfd8dc; }
        .valores .v:last-child { border-right: 0; }
        .valores .v b { display: block; font-size: 13px; margin-top: 2px; }
        .destaque { background: #e8f5e9; }
        .chave { font-family: 'Courier New', monospace; letter-spacing: 2px; word-break: break-all; }
        .aviso { background: #d9edf7; border: 1px solid #9acfea; padding: 6px; margin: 10px 14px 0; text-align: center; font-weight: bold; }
        .rodape { font-size: 9px; color: #607d8b; padding: 8px 14px 14px; }
        .toolbar { width: 740px; margin: 0 auto 10px; text-align: right; }
        .btn { background: #0b5394; color: #fff; border: 0; padding: 8px 14px; border-radius: 4px; cursor: pointer; font-size: 12px; }
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

        <div class="faixa">
            <div>
                <h2>NFS-e</h2>
                <small>Nota Fiscal de Serviços Eletrônica — Padrão Nacional</small>
            </div>
            <div style="text-align:right">
                <small>Número (prévia)</small><b><?= str_pad((string) ($config->proximo_numero_dps ?? 1), 15, '0', STR_PAD_LEFT) ?></b>
                <small style="margin-top:4px">Competência</small><?= date('m/Y') ?>
                <small style="margin-top:4px">Emissão</small><?= date('d/m/Y H:i') ?>
            </div>
        </div>

        <div class="aviso">
            PRÉVIA ILUSTRATIVA — este documento NÃO é uma NFS-e. Serve apenas para conferir o layout antes de emitir.
            <?= $homolog ? ' (Ambiente: HOMOLOGAÇÃO)' : '' ?>
        </div>

        <div class="sec">
            <div class="bloco">
                <div class="cab">Chave de Acesso da NFS-e</div>
                <div class="corpo cen"><span class="chave">•••• • •••• • •••• (gerada pelo Sefin Nacional na emissão) •••• • ••••</span></div>
            </div>

            <div class="bloco">
                <div class="cab">Prestador de Serviços</div>
                <div style="display:flex;align-items:stretch">
                    <?php if (!empty($emitente->url_logo)) { ?>
                        <div style="width:170px;padding:8px;display:flex;align-items:center;justify-content:center;border-right:1px solid #cfd8dc">
                            <img src="<?= $emitente->url_logo ?>" alt="Logo" style="max-height:70px;max-width:150px">
                        </div>
                    <?php } ?>
                    <div style="flex:1">
                        <div class="corpo grid">
                            <div class="cel" style="flex:2"><span class="rot">Nome / Razão Social</span><?= html_escape($emitente->nome ?? '—') ?></div>
                            <div class="cel"><span class="rot">CNPJ</span><?= html_escape($emitente->cnpj ?? '—') ?></div>
                            <div class="cel"><span class="rot">Inscrição Municipal</span><?= html_escape($config->inscricao_municipal ?: '—') ?></div>
                        </div>
                        <div class="corpo" style="border-top:1px solid #eceff1"><span class="rot">Endereço</span><?= html_escape($emitEndereco) ?></div>
                    </div>
                </div>
            </div>

            <div class="bloco">
                <div class="cab">Tomador de Serviços</div>
                <div class="corpo grid">
                    <div class="cel" style="flex:2"><span class="rot">Nome / Razão Social</span><?= html_escape($os->nomeCliente) ?></div>
                    <div class="cel"><span class="rot">CNPJ / CPF</span><?= html_escape($os->documento) ?></div>
                </div>
                <div class="corpo" style="border-top:1px solid #eceff1"><span class="rot">Endereço</span><?= html_escape($tomaEndereco ?: '—') ?></div>
            </div>

            <div class="bloco">
                <div class="cab">Serviço Prestado</div>
                <div class="corpo grid">
                    <div class="cel"><span class="rot">Cód. Tributação Nacional</span><?= $cTribNac !== '' ? html_escape($cTribNac) : '<span style="color:#c00">não informado</span>' ?></div>
                    <div class="cel"><span class="rot">Município da Prestação</span><?= html_escape($config->codigo_municipio ?: '—') ?> (cód. IBGE)</div>
                </div>
                <div class="corpo" style="border-top:1px solid #eceff1;min-height:56px">
                    <span class="rot">Discriminação</span>
                    OS nr. <?= $os->idOs ?>:
                    <?= empty($servicos) ? '<span style="color:#c00">Esta OS não possui serviços lançados.</span>' : html_escape(implode('; ', $descricoes)) ?>
                </div>
            </div>

            <div class="bloco">
                <div class="cab">Valores</div>
                <div class="valores">
                    <div class="v"><span class="rot">Valor do Serviço</span><b>R$ <?= number_format($total, 2, ',', '.') ?></b></div>
                    <div class="v"><span class="rot">Deduções</span><b>R$ 0,00</b></div>
                    <div class="v"><span class="rot">Base de Cálculo</span><b>R$ <?= number_format($total, 2, ',', '.') ?></b></div>
                    <div class="v"><span class="rot">Alíquota ISS</span><b><?= number_format($aliquota, 2, ',', '.') ?>%</b></div>
                    <div class="v"><span class="rot">Valor do ISS</span><b>R$ <?= number_format($vIss, 2, ',', '.') ?></b></div>
                    <div class="v destaque"><span class="rot">Valor Líquido</span><b>R$ <?= number_format($total, 2, ',', '.') ?></b></div>
                </div>
            </div>
        </div>

        <div class="rodape">
            Regime: Simples Nacional — o ISS é recolhido no DAS; o valor do ISS acima é apenas informativo.
            Documento emitido pelo MapOS. Chave, código de verificação e QR Code são gerados pelo Sefin Nacional no momento da emissão.
        </div>
    </div>
</body>
</html>
