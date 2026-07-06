<?php
// DANFE (NF-e / produtos) no mesmo estilo da impressão da OS.
$fmt = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$dt = function ($iso) {
    $t = strtotime((string) $iso);
    return $t ? date('d/m/Y H:i', $t) : $iso;
};
$homolog = (string) ($d['ambiente'] ?? '') === '2';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>DANFE - NF-e <?= html_escape($d['numero']) ?> - <?= html_escape($d['destNome']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="<?= base_url() ?>assets/css/bootstrap5.3.2.min.css" />
    <link rel="stylesheet" href="<?= base_url() ?>assets/font-awesome/css/font-awesome.css" />
    <link rel="stylesheet" href="<?= base_url() ?>assets/css/imprimir.css">
    <style>
        .via { display: none; }
        .danfse-chave { font-family: monospace; letter-spacing: 1px; word-break: break-all; text-align: center; }
        .tarja-homolog { background: #fff3cd; border: 1px solid #ffe08a; padding: 6px; text-align: center; font-weight: bold; margin: 6px 0; }
    </style>
</head>

<body>
    <div class="main-page">
        <div class="sub-page">
            <header>
                <?php if ($emitente == null) : ?>
                    <div class="alert alert-danger" role="alert">Configure os dados do emitente.</div>
                <?php else : ?>
                    <div class="imgLogo" class="align-middle">
                        <?php if (!empty($emitente->url_logo)) : ?>
                            <img src="<?= $emitente->url_logo ?>" class="img-fluid" style="width:140px;">
                        <?php endif; ?>
                    </div>
                    <div class="emitente">
                        <span style="font-size: 16px;"><b><?= html_escape($emitente->nome) ?></b></span></br>
                        <span class="align-middle">CNPJ: <?= html_escape($d['emitCnpj'] ?: $emitente->cnpj) ?> — IE: <?= html_escape($d['emitIE']) ?></span></br>
                        <span class="align-middle"><?= html_escape($d['emitEnd']) ?></span>
                    </div>
                    <div class="contatoEmitente">
                        <span style="font-weight: bold;">Tel: <?= html_escape($emitente->telefone) ?></span></br>
                        <span style="font-weight: bold;"><?= html_escape($emitente->email) ?></span>
                    </div>
                <?php endif; ?>
            </header>

            <section>
                <div class="title">
                    DANFE — NF-e Nº <?= html_escape($d['numero']) ?> / Série <?= html_escape($d['serie']) ?>
                    <span class="emissao">Emissão: <?= html_escape($dt($d['dhEmi'])) ?></span>
                </div>

                <?php if ($homolog) : ?>
                    <div class="tarja-homolog">AMBIENTE DE HOMOLOGAÇÃO — SEM VALOR FISCAL</div>
                <?php endif; ?>

                <div class="subtitle">DADOS DA NF-e</div>
                <div class="tabela">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td style="width:60%"><b>Natureza da Operação:</b> <?= html_escape($d['natOp']) ?></td>
                                <td><b>Protocolo de Autorização:</b> <?= html_escape($d['protocolo']) ?> <?= $d['dhProt'] ? '(' . html_escape($dt($d['dhProt'])) . ')' : '' ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="subtitle">CHAVE DE ACESSO</div>
                <div class="tabela">
                    <table class="table table-bordered">
                        <tbody>
                            <tr><td class="danfse-chave"><?= html_escape($d['chave']) ?></td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="subtitle">DESTINATÁRIO</div>
                <div class="tabela">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td style="width:60%"><b>Nome/Razão Social:</b> <?= html_escape($d['destNome']) ?></td>
                                <td><b>CNPJ/CPF:</b> <?= html_escape($d['destDoc']) ?></td>
                            </tr>
                            <?php if (!empty(trim($d['destEnd'], ', -/'))) : ?>
                                <tr>
                                    <td><b>Endereço:</b> <?= html_escape($d['destEnd']) ?></td>
                                    <td><b>IE:</b> <?= html_escape($d['destIE'] ?: 'ISENTO') ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="subtitle">PRODUTOS / SERVIÇOS</div>
                <div class="tabela">
                    <table class="table table-bordered">
                        <thead>
                            <tr class="table-secondary">
                                <th>CÓD.</th>
                                <th>DESCRIÇÃO</th>
                                <th class="text-center">NCM</th>
                                <th class="text-center">CFOP</th>
                                <th class="text-center">UN</th>
                                <th class="text-end">QTD</th>
                                <th class="text-end">V. UNIT.</th>
                                <th class="text-end">V. TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($d['itens'] as $it) : ?>
                                <tr>
                                    <td><?= html_escape($it['codigo']) ?></td>
                                    <td><?= html_escape($it['descricao']) ?></td>
                                    <td class="text-center"><?= html_escape($it['ncm']) ?></td>
                                    <td class="text-center"><?= html_escape($it['cfop']) ?></td>
                                    <td class="text-center"><?= html_escape($it['unidade']) ?></td>
                                    <td class="text-end"><?= number_format((float) $it['quantidade'], 2, ',', '.') ?></td>
                                    <td class="text-end"><?= $fmt($it['vUnit']) ?></td>
                                    <td class="text-end"><?= $fmt($it['vTotal']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="subtitle">TOTAIS</div>
                <div class="tabela">
                    <table class="table table-bordered">
                        <thead>
                            <tr class="table-secondary">
                                <th class="text-center">VALOR DOS PRODUTOS</th>
                                <th class="text-center">DESCONTO</th>
                                <th class="text-center">VALOR TOTAL DA NOTA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center"><?= $fmt($d['vProd']) ?></td>
                                <td class="text-center"><?= $fmt($d['vDesc'] ?: 0) ?></td>
                                <td class="text-center"><b><?= $fmt($d['vNF']) ?></b></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($d['infCpl'])) : ?>
                    <div class="subtitle">DADOS ADICIONAIS</div>
                    <div class="tabela">
                        <table class="table table-bordered">
                            <tbody>
                                <tr><td style="font-size:11px"><?= nl2br(html_escape($d['infCpl'])) ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <script>
        window.onload = function () { window.print(); };
    </script>
</body>

</html>
