<?php
// DANFE (NF-e / produtos) — layout completo, a partir do XML autorizado.
$fmt = fn ($v) => number_format((float) $v, 2, ',', '.');
$dt = function ($iso) {
    $t = strtotime((string) $iso);
    return $t ? date('d/m/Y', $t) : '';
};
$hr = function ($iso) {
    $t = strtotime((string) $iso);
    return $t ? date('H:i:s', $t) : '';
};
$homolog = (string) ($d['ambiente'] ?? '') === '2';
$e = $d['emitEnd'];
$dst = $d['destEnd'];
$modFrete = match ((string) $d['modFrete']) {
    '0' => '0 - Por conta do emitente',
    '1' => '1 - Por conta do destinatário',
    '2' => '2 - Por conta de terceiros',
    '9' => '9 - Sem frete',
    default => (string) $d['modFrete'],
};
$chaveFmt = trim(chunk_split($d['chave'], 4, ' '));
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <title>DANFE - NF-e <?= html_escape($d['numero']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 9px; color: #000; margin: 0; padding: 0; }
        /* Folha A4 na tela (visualização) */
        .folha-a4 { width: 210mm; min-height: 297mm; margin: 12px auto; padding: 8mm; background: #fff; box-shadow: 0 0 8px rgba(0,0,0,.35); }
        @media screen { body { background: #d9d9d9; } }
        @media print {
            body { background: #fff; }
            .folha-a4 { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            @page { size: A4 portrait; margin: 8mm; }
        }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #000; padding: 2px 3px; vertical-align: top; }
        .no-border, .no-border td { border: none; }
        .rot { font-size: 6.5px; text-transform: uppercase; display: block; color: #000; }
        .b { font-weight: bold; }
        .c { text-align: center; }
        .r { text-align: right; }
        .lg { font-size: 15px; font-weight: bold; }
        .danfe-box { text-align: center; }
        .sec { background: #ddd; font-weight: bold; text-transform: uppercase; font-size: 8px; padding: 1px 3px; border: 1px solid #000; margin-top: 3px; }
        .chave { font-family: 'Courier New', monospace; font-size: 11px; letter-spacing: 1px; text-align: center; font-weight: bold; }
        .canhoto { font-size: 8px; }
        .tarja { background: #fff3cd; border: 1px solid #ffe08a; text-align: center; font-weight: bold; padding: 3px; margin: 3px 0; }
        @media print { body { padding: 0; } .noprint { display: none; } }
    </style>
</head>

<body>
    <div class="folha-a4">
    <!-- Canhoto -->
    <table>
        <tr>
            <td style="width:78%" class="canhoto">
                RECEBEMOS DE <b><?= html_escape($d['emitNome']) ?></b> OS PRODUTOS/SERVIÇOS CONSTANTES DA NOTA FISCAL ELETRÔNICA INDICADA AO LADO
                <table style="margin-top:2px">
                    <tr>
                        <td style="width:60%"><span class="rot">Data de Recebimento</span>&nbsp;</td>
                        <td><span class="rot">Identificação e Assinatura do Recebedor</span>&nbsp;</td>
                    </tr>
                </table>
            </td>
            <td class="c"><span class="rot">NF-e</span><br><span class="b">Nº <?= html_escape($d['numero']) ?></span><br>Série <?= html_escape($d['serie']) ?></td>
        </tr>
    </table>

    <?php if ($homolog) : ?>
        <div class="tarja">NF-e EMITIDA EM AMBIENTE DE HOMOLOGAÇÃO — SEM VALOR FISCAL</div>
    <?php endif; ?>

    <!-- Cabeçalho: emitente | DANFE | chave -->
    <table style="margin-top:3px">
        <tr>
            <td style="width:42%">
                <?php if (!empty($emitente->url_logo)) : ?>
                    <img src="<?= $emitente->url_logo ?>" style="max-height:40px;max-width:120px;float:left;margin-right:6px">
                <?php endif; ?>
                <span class="lg"><?= html_escape($d['emitNome']) ?></span><br>
                <?= html_escape(trim($e['lgr'] . ', ' . $e['nro'] . ($e['cpl'] ? ' ' . $e['cpl'] : ''))) ?><br>
                <?= html_escape($e['bairro']) ?> - <?= html_escape($e['mun']) ?>/<?= html_escape($e['uf']) ?><br>
                CEP <?= html_escape($e['cep']) ?> - Fone <?= html_escape($e['fone']) ?>
            </td>
            <td style="width:16%" class="danfe-box">
                <span class="b" style="font-size:13px">DANFE</span>
                <span class="rot">Documento Auxiliar da Nota Fiscal Eletrônica</span>
                <div style="margin-top:4px;text-align:left">
                    <?= (string) $d['tpNF'] === '0' ? '<b>0</b>' : '0' ?> - Entrada<br>
                    <?= (string) $d['tpNF'] === '1' ? '<b>1</b>' : '1' ?> - Saída
                </div>
                <div style="margin-top:4px"><span class="b">Nº <?= html_escape($d['numero']) ?></span><br>Série <?= html_escape($d['serie']) ?><br>Fl. 1/1</div>
            </td>
            <td>
                <span class="rot">Controle do Fisco</span>
                <?php if (!empty($d['barcodeSvg'])) : ?>
                    <div style="height:42px;margin:2px 0"><?= $d['barcodeSvg'] ?></div>
                <?php endif; ?>
                <span class="rot">Chave de Acesso</span>
                <div class="chave"><?= html_escape($chaveFmt) ?></div>
                <div class="c" style="margin-top:3px;font-size:8px">Consulte pela chave de acesso em<br>www.nfe.fazenda.gov.br/portal ou no site da SEFAZ</div>
            </td>
        </tr>
    </table>

    <table style="border-top:0">
        <tr>
            <td style="width:58%"><span class="rot">Natureza da Operação</span><?= html_escape($d['natOp']) ?></td>
            <td><span class="rot">Protocolo de Autorização de Uso</span><?= html_escape($d['protocolo']) ?> - <?= html_escape($dt($d['dhProt']) . ' ' . $hr($d['dhProt'])) ?></td>
        </tr>
        <tr>
            <td><span class="rot">Inscrição Estadual</span><?= html_escape($d['emitIE']) ?></td>
            <td><span class="rot">CNPJ</span><?= html_escape($d['emitCnpj']) ?></td>
        </tr>
    </table>

    <!-- Destinatário -->
    <div class="sec">Destinatário / Remetente</div>
    <table style="border-top:0">
        <tr>
            <td style="width:55%"><span class="rot">Nome / Razão Social</span><?= html_escape($d['destNome']) ?></td>
            <td style="width:25%"><span class="rot">CNPJ / CPF</span><?= html_escape($d['destDoc']) ?></td>
            <td><span class="rot">Data de Emissão</span><?= html_escape($dt($d['dhEmi'])) ?></td>
        </tr>
        <tr>
            <td><span class="rot">Endereço</span><?= html_escape(trim($dst['lgr'] . ', ' . $dst['nro'])) ?></td>
            <td><span class="rot">Bairro / Município</span><?= html_escape(trim($dst['bairro'] . ' - ' . $dst['mun'])) ?></td>
            <td><span class="rot">Data de Saída/Entrada</span><?= html_escape($dt($d['dhSaiEnt'])) ?></td>
        </tr>
        <tr>
            <td><span class="rot">CEP</span><?= html_escape($dst['cep']) ?></td>
            <td><span class="rot">UF / Inscrição Estadual</span><?= html_escape($dst['uf']) ?> - <?= html_escape($d['destIE'] ?: 'ISENTO') ?></td>
            <td><span class="rot">Hora de Saída</span><?= html_escape($hr($d['dhSaiEnt'])) ?></td>
        </tr>
    </table>

    <!-- Cálculo do imposto -->
    <div class="sec">Cálculo do Imposto</div>
    <table style="border-top:0">
        <tr>
            <td class="c"><span class="rot">BC do ICMS</span><?= $fmt($d['vBC']) ?></td>
            <td class="c"><span class="rot">Valor do ICMS</span><?= $fmt($d['vICMS']) ?></td>
            <td class="c"><span class="rot">BC ICMS ST</span><?= $fmt($d['vBCST']) ?></td>
            <td class="c"><span class="rot">Valor ICMS ST</span><?= $fmt($d['vST']) ?></td>
            <td class="c"><span class="rot">V. Total Produtos</span><b><?= $fmt($d['vProd']) ?></b></td>
        </tr>
        <tr>
            <td class="c"><span class="rot">V. Frete</span><?= $fmt($d['vFrete']) ?></td>
            <td class="c"><span class="rot">V. Seguro</span><?= $fmt($d['vSeg']) ?></td>
            <td class="c"><span class="rot">Desconto</span><?= $fmt($d['vDesc']) ?></td>
            <td class="c"><span class="rot">Outras Desp. / IPI</span><?= $fmt($d['vOutro']) ?> / <?= $fmt($d['vIPI']) ?></td>
            <td class="c"><span class="rot">V. Total da Nota</span><b><?= $fmt($d['vNF']) ?></b></td>
        </tr>
    </table>

    <!-- Transportador -->
    <div class="sec">Transportador / Volumes Transportados</div>
    <table style="border-top:0">
        <tr>
            <td style="width:32%"><span class="rot">Razão Social</span><?= html_escape($d['transpNome']) ?>&nbsp;</td>
            <td style="width:20%"><span class="rot">Frete por Conta</span><?= html_escape($modFrete) ?></td>
            <td><span class="rot">Código ANTT</span><?= html_escape($d['veicAntt']) ?>&nbsp;</td>
            <td><span class="rot">Placa do Veículo</span><?= html_escape($d['veicPlaca']) ?>&nbsp;</td>
            <td style="width:7%"><span class="rot">UF</span><?= html_escape($d['veicUF']) ?>&nbsp;</td>
            <td><span class="rot">CNPJ / CPF</span><?= html_escape($d['transpDoc']) ?>&nbsp;</td>
        </tr>
        <tr>
            <td colspan="3"><span class="rot">Endereço</span><?= html_escape($d['transpEnd']) ?>&nbsp;</td>
            <td><span class="rot">Município</span><?= html_escape($d['transpMun']) ?>&nbsp;</td>
            <td colspan="2"><span class="rot">UF / Inscrição Estadual</span><?= html_escape(trim($d['transpUF'] . ' ' . $d['transpIE'])) ?>&nbsp;</td>
        </tr>
        <tr>
            <td><span class="rot">Quantidade</span><?= html_escape($d['volQtd']) ?>&nbsp;</td>
            <td><span class="rot">Espécie</span><?= html_escape($d['volEsp']) ?>&nbsp;</td>
            <td><span class="rot">Marca</span><?= html_escape($d['volMarca']) ?>&nbsp;</td>
            <td><span class="rot">Numeração</span><?= html_escape($d['volNum']) ?>&nbsp;</td>
            <td><span class="rot">Peso Bruto</span><?= html_escape($d['volPesoB']) ?>&nbsp;</td>
            <td><span class="rot">Peso Líquido</span><?= html_escape($d['volPesoL']) ?>&nbsp;</td>
        </tr>
    </table>

    <!-- Produtos -->
    <div class="sec">Dados dos Produtos / Serviços</div>
    <table style="border-top:0">
        <thead>
            <tr style="background:#eee">
                <th>Código</th>
                <th>Descrição</th>
                <th class="c">NCM/SH</th>
                <th class="c">CST</th>
                <th class="c">CFOP</th>
                <th class="c">Un</th>
                <th class="r">Qtd</th>
                <th class="r">V. Unit</th>
                <th class="r">V. Total</th>
                <th class="r">BC ICMS</th>
                <th class="r">V. ICMS</th>
                <th class="r">Alíq</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($d['itens'] as $it) : ?>
                <tr>
                    <td><?= html_escape($it['codigo']) ?></td>
                    <td><?= html_escape($it['descricao']) ?></td>
                    <td class="c"><?= html_escape($it['ncm']) ?></td>
                    <td class="c"><?= html_escape($it['cst']) ?></td>
                    <td class="c"><?= html_escape($it['cfop']) ?></td>
                    <td class="c"><?= html_escape($it['unidade']) ?></td>
                    <td class="r"><?= $fmt($it['quantidade']) ?></td>
                    <td class="r"><?= $fmt($it['vUnit']) ?></td>
                    <td class="r"><?= $fmt($it['vTotal']) ?></td>
                    <td class="r"><?= $fmt($it['vBcIcms'] ?: 0) ?></td>
                    <td class="r"><?= $fmt($it['vIcms'] ?: 0) ?></td>
                    <td class="r"><?= $it['pIcms'] !== '' ? $fmt($it['pIcms']) . '%' : '-' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Cálculo do ISSQN -->
    <div class="sec">Cálculo do ISSQN</div>
    <table style="border-top:0">
        <tr>
            <td style="width:25%"><span class="rot">Inscrição Municipal</span><?= html_escape($d['issInscMun'] ?: (isset($emitente->im) ? $emitente->im : '')) ?>&nbsp;</td>
            <td class="r" style="width:25%"><span class="rot">Valor Total dos Serviços</span><?= $fmt($d['issVServ'] ?: 0) ?></td>
            <td class="r"><span class="rot">Base de Cálculo do ISSQN</span><?= $fmt($d['issVBC'] ?: 0) ?></td>
            <td class="r"><span class="rot">Valor do ISSQN</span><?= $fmt($d['issVISS'] ?: 0) ?></td>
        </tr>
    </table>

    <!-- Dados adicionais -->
    <div class="sec">Dados Adicionais</div>
    <table style="border-top:0">
        <tr>
            <td style="width:65%;height:60px"><span class="rot">Informações Complementares</span><?= nl2br(html_escape($d['infCpl'])) ?></td>
            <td><span class="rot">Reservado ao Fisco</span>&nbsp;</td>
        </tr>
    </table>

    <div class="c" style="font-size:7px;margin-top:3px">
        <?= html_escape($d['xMotivo']) ?> — Documento impresso pelo MapOS. Consulte a autenticidade no portal da NF-e.
    </div>
    </div><!-- /folha-a4 -->

    <script>window.onload = function () { window.print(); };</script>
</body>

</html>
