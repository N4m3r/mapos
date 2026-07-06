<?php
// DANFSe no mesmo estilo da impressão da OS (imprimir.css + bootstrap).
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
    <title>DANFSe - NFS-e <?= html_escape($d['numero']) ?> - <?= html_escape($d['tomaNome']) ?></title>
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
                        <?php if ($emitente->cnpj != "00.000.000/0000-00") : ?>
                            <span class="align-middle">CNPJ: <?= html_escape($emitente->cnpj) ?></span></br>
                        <?php endif; ?>
                        <span class="align-middle">
                            <?= html_escape($emitente->rua . ', ' . $emitente->numero . ', ' . $emitente->bairro) ?><br>
                            <?= html_escape($emitente->cidade . ' - ' . $emitente->uf . ' - ' . $emitente->cep) ?>
                        </span>
                    </div>
                    <div class="contatoEmitente">
                        <span style="font-weight: bold;">Tel: <?= html_escape($emitente->telefone) ?></span></br>
                        <span style="font-weight: bold;"><?= html_escape($emitente->email) ?></span></br>
                        <?php if (!empty(trim($d['prestIM']))) : ?>
                            <span>Insc. Municipal: <b><?= html_escape(trim($d['prestIM'])) ?></b></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </header>

            <section>
                <div class="title">
                    NFS-e Nº <?= html_escape($d['numero']) ?>
                    <span class="emissao">Emissão: <?= html_escape($dt($d['dhProc'])) ?></span>
                </div>

                <?php if ($homolog) : ?>
                    <div class="tarja-homolog">AMBIENTE DE HOMOLOGAÇÃO — SEM VALOR FISCAL</div>
                <?php endif; ?>

                <div class="subtitle">CHAVE DE ACESSO DA NFS-e</div>
                <div class="tabela">
                    <table class="table table-bordered">
                        <tbody>
                            <tr><td class="danfse-chave"><?= html_escape($d['chave']) ?></td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="subtitle">DADOS DO TOMADOR</div>
                <div class="tabela">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td style="width:70%"><b>Nome/Razão Social:</b> <?= html_escape($d['tomaNome']) ?></td>
                                <td><b>CNPJ/CPF:</b> <?= html_escape($d['tomaDoc']) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="subtitle">SERVIÇO PRESTADO</div>
                <div class="tabela">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td style="width:50%"><b>Cód. Tributação Nacional:</b> <?= html_escape($d['cTribNac']) ?></td>
                                <td><b>Cód. Tributação Municipal:</b> <?= html_escape($d['cTribMun']) ?></td>
                            </tr>
                            <tr>
                                <td colspan="2"><b>Discriminação:</b><br><?= nl2br(html_escape($d['servDesc'])) ?></td>
                            </tr>
                            <?php if (!empty($d['xTribNac'])) : ?>
                                <tr><td colspan="2"><b>Item da lista:</b> <?= html_escape($d['xTribNac']) ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="subtitle">VALORES</div>
                <div class="tabela">
                    <table class="table table-bordered">
                        <thead>
                            <tr class="table-secondary">
                                <th class="text-center">VALOR DO SERVIÇO</th>
                                <th class="text-center">ALÍQUOTA ISS</th>
                                <th class="text-center">VALOR DO ISS</th>
                                <th class="text-center">VALOR LÍQUIDO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center"><?= $fmt($d['vServ']) ?></td>
                                <td class="text-center"><?= $d['pAliq'] !== '' ? number_format((float) $d['pAliq'], 2, ',', '.') . '%' : '-' ?></td>
                                <td class="text-center"><?= $d['vISS'] !== '' ? $fmt($d['vISS']) : 'R$ 0,00' ?></td>
                                <td class="text-center"><b><?= $fmt($d['vLiq'] !== '' ? $d['vLiq'] : $d['vServ']) ?></b></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div style="font-size:11px;color:#555;margin-top:8px">
                    Documento gerado pelo MapOS a partir do XML autorizado pelo Sistema Nacional NFS-e.
                    Consulte a autenticidade pela chave de acesso em nfse.gov.br.
                </div>
            </section>
        </div>
    </div>

    <script>
        window.onload = function () { window.print(); };
    </script>
</body>

</html>
