<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Página pública de aprovação de OS (acessada pelo cliente via link temporário).
 * Página independente (não usa o layout administrativo).
 */
function ap_money($v)
{
    return 'R$ ' . number_format((float) $v, 2, ',', '.');
}

function ap_data($d)
{
    return $d ? date('d/m/Y', strtotime($d)) : '--';
}

$appName = $this->config->item('app_name') ?: 'Map-OS';
$emitente = isset($emitente) ? $emitente : null;
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Aprovação de Ordem de Serviço - <?= html_escape($appName) ?></title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <link rel="stylesheet" href="<?= base_url() ?>assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="<?= base_url() ?>assets/css/bootstrap-responsive.min.css" />
    <link href="<?= base_url() ?>assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link rel="shortcut icon" type="image/png" href="<?= base_url() ?>assets/img/favicon.png" />
    <style>
        body {
            background: #eef1f6;
            color: #33373d;
            padding: 0 0 50px;
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
        }

        .ap-wrap {
            max-width: 820px;
            margin: 0 auto;
            padding: 15px;
        }

        .ap-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .08);
            overflow: hidden;
            margin-top: 20px;
        }

        .ap-head {
            background: #2d335b;
            color: #fff;
            padding: 22px 26px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .ap-head h1 {
            font-size: 20px;
            margin: 0;
            line-height: 1.3;
        }

        .ap-head small {
            color: #c9cde8;
        }

        .ap-head img {
            max-height: 46px;
        }

        .ap-body {
            padding: 24px 26px;
        }

        .ap-badge {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 13px;
        }

        .ap-badge.pendente {
            background: #fff3cd;
            color: #8a6d3b;
        }

        .ap-badge.aprovado {
            background: #d4edda;
            color: #256029;
        }

        .ap-badge.reprovado {
            background: #f8d7da;
            color: #a2434b;
        }

        .ap-section-title {
            font-size: 15px;
            font-weight: bold;
            color: #2d335b;
            border-bottom: 2px solid #eef1f6;
            padding-bottom: 8px;
            margin: 26px 0 14px;
        }

        .ap-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 24px;
        }

        @media (max-width: 600px) {
            .ap-grid {
                grid-template-columns: 1fr;
            }
        }

        .ap-field label {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            color: #98a0ad;
            letter-spacing: .3px;
            margin: 0;
        }

        .ap-field span {
            font-size: 14px;
        }

        table.ap-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        table.ap-items th,
        table.ap-items td {
            padding: 8px 10px;
            border-bottom: 1px solid #eef1f6;
            font-size: 13px;
            text-align: left;
        }

        table.ap-items th {
            background: #f7f8fb;
            color: #6b7280;
            text-transform: uppercase;
            font-size: 11px;
        }

        table.ap-items td.num,
        table.ap-items th.num {
            text-align: right;
        }

        .ap-total {
            text-align: right;
            font-size: 15px;
            margin-top: 4px;
        }

        .ap-total strong {
            font-size: 22px;
            color: #2d335b;
        }

        .ap-actions {
            margin-top: 26px;
            padding: 22px;
            background: #f7f8fb;
            border-radius: 10px;
        }

        .ap-actions .controls input[type=text],
        .ap-actions textarea {
            width: 100%;
            box-sizing: border-box;
        }

        .ap-btns {
            margin-top: 16px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .ap-btns .btn {
            flex: 1;
            min-width: 160px;
            padding: 12px;
            font-size: 15px;
            font-weight: bold;
        }

        .ap-note {
            font-size: 12px;
            color: #98a0ad;
            margin-top: 14px;
            text-align: center;
        }

        .ap-state {
            text-align: center;
            padding: 40px 20px;
        }

        .ap-state i {
            font-size: 54px;
        }

        .ap-state h2 {
            margin: 14px 0 6px;
        }

        #obsBox {
            display: none;
            margin-top: 12px;
        }
    </style>
</head>

<body>
    <div class="ap-wrap">
        <div class="ap-card">
            <div class="ap-head">
                <div>
                    <h1>Aprovação de Ordem de Serviço</h1>
                    <small><?= html_escape($emitente->nome ?? $appName) ?></small>
                </div>
                <?php if ($emitente && ! empty($emitente->logo)) { ?>
                    <img src="<?= base_url('assets/img/' . $emitente->logo) ?>" alt="<?= html_escape($emitente->nome) ?>">
                <?php } ?>
            </div>

            <div class="ap-body">

                <?php if ($situacao === 'invalido') { ?>
                    <div class="ap-state">
                        <i class="fa fa-unlink" style="color:#c0392b"></i>
                        <h2>Link inválido</h2>
                        <p class="muted">Este link de aprovação não existe ou foi revogado. Entre em contato com a empresa para obter um novo link.</p>
                    </div>

                <?php } elseif ($situacao === 'expirado') { ?>
                    <div class="ap-state">
                        <i class="fa fa-clock-o" style="color:#e67e22"></i>
                        <h2>Link expirado</h2>
                        <p class="muted">O prazo para aprovação desta Ordem de Serviço (nº <?= (int) $os->idOs ?>) expirou em <?= ap_data($os->aprovacao_expira) ?>. Solicite um novo link à empresa.</p>
                    </div>

                <?php } else { ?>

                    <?php if ($erro) { ?>
                        <div class="alert alert-danger"><?= html_escape($erro) ?></div>
                    <?php } ?>

                    <?php if (! empty($info)) { ?>
                        <div class="alert alert-info"><?= html_escape($info) ?></div>
                    <?php } ?>

                    <?php if ($situacao === 'aprovado') { ?>
                        <div class="alert alert-success" style="text-align:center">
                            <i class="fa fa-check-circle"></i>
                            <strong>Orçamento aprovado</strong> por <?= html_escape($os->aprovacao_nome) ?> em <?= $os->aprovacao_data ? date('d/m/Y H:i', strtotime($os->aprovacao_data)) : '' ?>.
                        </div>
                    <?php } elseif ($situacao === 'reprovado') { ?>
                        <div class="alert alert-error" style="text-align:center">
                            <i class="fa fa-times-circle"></i>
                            <strong>Orçamento reprovado</strong> por <?= html_escape($os->aprovacao_nome) ?> em <?= $os->aprovacao_data ? date('d/m/Y H:i', strtotime($os->aprovacao_data)) : '' ?>.
                            <?php if (! empty($os->aprovacao_obs)) { ?><br><em>Motivo: <?= html_escape($os->aprovacao_obs) ?></em><?php } ?>
                        </div>
                    <?php } ?>

                    <div style="margin-bottom:6px">
                        <span class="ap-badge <?= $situacao ?>">
                            OS Nº <?= (int) $os->idOs ?> &middot;
                            <?= $situacao === 'pendente' ? 'Aguardando sua aprovação' : ($situacao === 'aprovado' ? 'Aprovada' : 'Reprovada') ?>
                        </span>
                    </div>

                    <div class="ap-section-title">Dados da Ordem de Serviço</div>
                    <div class="ap-grid">
                        <div class="ap-field"><label>Cliente</label><span><?= html_escape($os->nomeCliente) ?></span></div>
                        <div class="ap-field"><label>Data de abertura</label><span><?= ap_data($os->dataInicial) ?></span></div>
                        <div class="ap-field"><label>Responsável</label><span><?= html_escape($os->nome_responsavel ?? '--') ?></span></div>
                        <div class="ap-field"><label>Status atual</label><span><?= html_escape($os->status ?: '--') ?></span></div>
                    </div>

                    <?php if (! empty($os->descricaoProduto) || ! empty($os->defeito)) { ?>
                        <div class="ap-section-title">Descrição / Defeito relatado</div>
                        <?php if (! empty($os->descricaoProduto)) { ?>
                            <div class="ap-field" style="margin-bottom:10px"><label>Equipamento / Produto</label><span><?= nl2br(html_escape(strip_tags($os->descricaoProduto))) ?></span></div>
                        <?php } ?>
                        <?php if (! empty($os->defeito)) { ?>
                            <div class="ap-field"><label>Defeito relatado</label><span><?= nl2br(html_escape(strip_tags($os->defeito))) ?></span></div>
                        <?php } ?>
                    <?php } ?>

                    <?php if (! empty($servicos)) { ?>
                        <div class="ap-section-title">Serviços</div>
                        <table class="ap-items">
                            <thead>
                                <tr>
                                    <th>Serviço</th>
                                    <th class="num">Qtd.</th>
                                    <th class="num">Preço</th>
                                    <th class="num">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($servicos as $s) {
                                    $preco = $s->preco ?: $s->precoVenda;
                                    $qtd = $s->quantidade ?: 1; ?>
                                    <tr>
                                        <td><?= html_escape($s->nome) ?></td>
                                        <td class="num"><?= (int) $qtd ?></td>
                                        <td class="num"><?= ap_money($preco) ?></td>
                                        <td class="num"><?= ap_money($preco * $qtd) ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    <?php } ?>

                    <?php if (! empty($produtos)) { ?>
                        <div class="ap-section-title">Produtos / Peças</div>
                        <table class="ap-items">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th class="num">Qtd.</th>
                                    <th class="num">Preço</th>
                                    <th class="num">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($produtos as $p) { ?>
                                    <tr>
                                        <td><?= html_escape($p->descricao) ?></td>
                                        <td class="num"><?= (int) $p->quantidade ?></td>
                                        <td class="num"><?= ap_money($p->preco) ?></td>
                                        <td class="num"><?= ap_money($p->subTotal) ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    <?php } ?>

                    <?php
                    $subtotalGeral = (float) $totalProdutos + (float) $totalServico;
                    $temDesconto = isset($valorDesconto) && $valorDesconto > 0;
                    $totalFinal = $temDesconto ? $valorDesconto : $subtotalGeral;
                    ?>
                    <div class="ap-total">
                        <?php if ($temDesconto) { ?>
                            <div>Subtotal: <?= ap_money($subtotalGeral) ?></div>
                        <?php } ?>
                        <div>Total: <strong><?= ap_money($totalFinal) ?></strong></div>
                    </div>

                    <?php
                    $precisaVerificar = ! empty($exigeToken) && empty($codigoValidado);
                    ?>

                    <?php if ($situacao === 'pendente' && $precisaVerificar) { ?>
                        <div class="ap-actions">
                            <h4 style="margin-top:0"><i class="fa fa-lock"></i> Verificação de segurança</h4>
                            <p class="muted">Para sua proteção, é preciso confirmar sua identidade com um código antes de aprovar. Enviaremos o código para <strong><?= html_escape($canalMascarado ?: 'seu contato cadastrado') ?></strong><?php if (! empty($qtdDestinos) && $qtdDestinos > 1) { ?> e mais <?= (int) $qtdDestinos - 1 ?> contato(s) cadastrado(s)<?php } ?>.</p>

                            <form method="post" action="<?= site_url('aprovacao/enviarCodigo') ?>" style="display:inline">
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                                <input type="hidden" name="token" value="<?= html_escape($token) ?>">
                                <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane"></i> <?= ! empty($codigoEnviado) ? 'Reenviar código' : 'Enviar código' ?></button>
                            </form>

                            <?php if (! empty($codigoEnviado)) { ?>
                                <form method="post" action="<?= site_url('aprovacao/validarCodigo') ?>" style="margin-top:16px">
                                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                                    <input type="hidden" name="token" value="<?= html_escape($token) ?>">
                                    <div class="control-group">
                                        <label class="control-label" for="codigo"><strong>Código recebido</strong></label>
                                        <div class="controls">
                                            <input type="text" name="codigo" id="codigo" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="000000" style="letter-spacing:6px;font-size:20px;max-width:180px" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Validar código</button>
                                </form>
                            <?php } ?>
                        </div>
                    <?php } ?>

                    <?php if ($situacao === 'pendente' && ! $precisaVerificar) { ?>
                        <div class="ap-actions">
                            <h4 style="margin-top:0">Confirme sua decisão</h4>
                            <p class="muted">Revise o orçamento acima e informe se autoriza a execução do serviço.</p>

                            <form method="post" action="<?= site_url('aprovacao/confirmar') ?>" id="formDecisao">
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                                <input type="hidden" name="token" value="<?= html_escape($token) ?>">
                                <input type="hidden" name="decisao" id="decisao" value="">

                                <div class="control-group">
                                    <label class="control-label" for="nome"><strong>Seu nome *</strong></label>
                                    <div class="controls">
                                        <input type="text" name="nome" id="nome" maxlength="150" placeholder="Nome de quem está aprovando" required>
                                    </div>
                                </div>

                                <div class="control-group" id="obsBox">
                                    <label class="control-label" for="obs"><strong>Motivo da reprovação *</strong></label>
                                    <div class="controls">
                                        <textarea name="obs" id="obs" rows="3" placeholder="Descreva o motivo da reprovação"></textarea>
                                    </div>
                                </div>

                                <div class="ap-btns">
                                    <button type="submit" class="btn btn-success" id="btnAprovar"><i class="fa fa-check"></i> Aprovar orçamento</button>
                                    <button type="submit" class="btn btn-danger" id="btnReprovar"><i class="fa fa-times"></i> Reprovar</button>
                                </div>
                            </form>

                            <p class="ap-note">Válido até <?= ap_data($os->aprovacao_expira) ?>. Este link é pessoal e deixa de funcionar após a sua decisão.</p>
                        </div>
                    <?php } ?>

                    <?php if ($emitente && ! empty($emitente->telefone)) { ?>
                        <p class="ap-note">Dúvidas? Fale com <?= html_escape($emitente->nome) ?> &middot; <?= html_escape($emitente->telefone) ?></p>
                    <?php } ?>

                <?php } ?>

            </div>
        </div>
    </div>

    <script src="<?= base_url() ?>assets/js/jquery-1.12.4.min.js"></script>
    <script>
        $(function() {
            var $form = $('#formDecisao');
            if (!$form.length) return;

            $('#btnAprovar').on('click', function(e) {
                if (!$.trim($('#nome').val())) {
                    e.preventDefault();
                    alert('Por favor, informe seu nome antes de aprovar.');
                    $('#nome').focus();
                    return;
                }
                if (!confirm('Confirma a APROVAÇÃO deste orçamento?')) {
                    e.preventDefault();
                    return;
                }
                $('#decisao').val('aprovado');
            });

            $('#btnReprovar').on('click', function(e) {
                e.preventDefault();
                $('#obsBox').show();
                $('#decisao').val('reprovado');
                if (!$.trim($('#nome').val())) {
                    alert('Por favor, informe seu nome.');
                    $('#nome').focus();
                    return;
                }
                if (!$.trim($('#obs').val())) {
                    alert('Descreva o motivo da reprovação.');
                    $('#obs').focus();
                    return;
                }
                if (confirm('Confirma a REPROVAÇÃO deste orçamento?')) {
                    $form.get(0).submit();
                }
            });
        });
    </script>
</body>

</html>
