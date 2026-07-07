<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Página pública de ACEITE DO SERVIÇO REALIZADO (link temporário ao cliente).
 * Página independente (não usa o layout administrativo). Espelha aprovacao/publico.
 */
function ac_money($v)
{
    return 'R$ ' . number_format((float) $v, 2, ',', '.');
}

function ac_data($d)
{
    return $d ? date('d/m/Y', strtotime($d)) : '--';
}

$appName = $this->config->item('app_name') ?: 'Map-OS';
$emitente = isset($emitente) ? $emitente : null;
$totalFotos = count($fotos_etapa['entrada']) + count($fotos_etapa['durante']) + count($fotos_etapa['saida']);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Aceite do Serviço - <?= html_escape($appName) ?></title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <link rel="stylesheet" href="<?= base_url() ?>assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="<?= base_url() ?>assets/css/bootstrap-responsive.min.css" />
    <link href="<?= base_url() ?>assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link rel="shortcut icon" type="image/png" href="<?= base_url() ?>assets/img/favicon.png" />
    <style>
        body { background:#eef1f6; color:#33373d; padding:0 0 50px; font-family:'Segoe UI',Tahoma,Arial,sans-serif; }
        .ac-wrap { max-width:820px; margin:0 auto; padding:15px; }
        .ac-card { background:#fff; border-radius:10px; box-shadow:0 2px 12px rgba(0,0,0,.08); overflow:hidden; margin-top:20px; }
        .ac-head { background:#1c7a4d; color:#fff; padding:22px 26px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; }
        .ac-head h1 { font-size:20px; margin:0; line-height:1.3; }
        .ac-head small { color:#cdeadd; }
        .ac-head img { max-height:46px; }
        .ac-body { padding:24px 26px; }
        .ac-badge { display:inline-block; padding:5px 14px; border-radius:20px; font-weight:bold; font-size:13px; }
        .ac-badge.pendente { background:#fff3cd; color:#8a6d3b; }
        .ac-badge.aceito { background:#d4edda; color:#256029; }
        .ac-badge.recusado { background:#f8d7da; color:#a2434b; }
        .ac-section-title { font-size:15px; font-weight:bold; color:#1c7a4d; border-bottom:2px solid #eef1f6; padding-bottom:8px; margin:26px 0 14px; }
        .ac-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px 24px; }
        @media (max-width:600px){ .ac-grid{ grid-template-columns:1fr; } }
        .ac-field label { display:block; font-size:11px; text-transform:uppercase; color:#98a0ad; letter-spacing:.3px; margin:0; }
        .ac-field span { font-size:14px; }
        table.ac-items { width:100%; border-collapse:collapse; margin-bottom:6px; }
        table.ac-items th, table.ac-items td { padding:8px 10px; border-bottom:1px solid #eef1f6; font-size:13px; text-align:left; }
        table.ac-items th { background:#f7f8fb; color:#6b7280; text-transform:uppercase; font-size:11px; }
        table.ac-items td.num, table.ac-items th.num { text-align:right; }
        .ac-total { text-align:right; font-size:15px; margin-top:4px; }
        .ac-total strong { font-size:22px; color:#1c7a4d; }
        .ac-fotos { display:flex; flex-wrap:wrap; gap:8px; }
        .ac-fotos a { display:block; width:100px; height:100px; border-radius:8px; overflow:hidden; border:1px solid #e2e6ee; }
        .ac-fotos img { width:100%; height:100%; object-fit:cover; }
        .ac-etapa-tit { font-size:12px; text-transform:uppercase; color:#98a0ad; margin:12px 0 6px; }
        .ac-actions { margin-top:26px; padding:22px; background:#f7f8fb; border-radius:10px; }
        .ac-actions input[type=text], .ac-actions textarea { width:100%; box-sizing:border-box; }
        .ac-canvas-box { background:#fff; border:2px dashed #b9c2d0; border-radius:8px; margin-top:6px; }
        #canvasAssinatura { width:100%; height:auto; touch-action:none; display:block; }
        .ac-btns { margin-top:16px; display:flex; gap:12px; flex-wrap:wrap; }
        .ac-btns .btn { flex:1; min-width:160px; padding:12px; font-size:15px; font-weight:bold; }
        .ac-note { font-size:12px; color:#98a0ad; margin-top:14px; text-align:center; }
        .ac-state { text-align:center; padding:40px 20px; }
        .ac-state i { font-size:54px; }
        .ac-state h2 { margin:14px 0 6px; }
        #obsBox { display:none; margin-top:12px; }
        .ac-assinaturas img { max-height:90px; border:1px solid #e2e6ee; border-radius:6px; background:#fff; margin:4px 8px 4px 0; }
    </style>
</head>

<body>
    <div class="ac-wrap">
        <div class="ac-card">
            <div class="ac-head">
                <div>
                    <h1>Aceite do Serviço Realizado</h1>
                    <small><?= html_escape($emitente->nome ?? $appName) ?></small>
                </div>
                <?php if ($emitente && ! empty($emitente->logo)) { ?>
                    <img src="<?= base_url('assets/img/' . $emitente->logo) ?>" alt="<?= html_escape($emitente->nome) ?>">
                <?php } ?>
            </div>

            <div class="ac-body">

                <?php if ($situacao === 'invalido') { ?>
                    <div class="ac-state">
                        <i class="fa fa-unlink" style="color:#c0392b"></i>
                        <h2>Link inválido</h2>
                        <p class="muted">Este link de aceite não existe ou foi revogado. Entre em contato com a empresa.</p>
                    </div>

                <?php } elseif ($situacao === 'expirado') { ?>
                    <div class="ac-state">
                        <i class="fa fa-clock-o" style="color:#e67e22"></i>
                        <h2>Link expirado</h2>
                        <p class="muted">O prazo para confirmar o aceite da OS nº <?= (int) $os->idOs ?> expirou em <?= ac_data($os->aceite_expira) ?>. Solicite um novo link à empresa.</p>
                    </div>

                <?php } else { ?>

                    <?php if ($erro) { ?>
                        <div class="alert alert-danger"><?= html_escape($erro) ?></div>
                    <?php } ?>

                    <?php if ($situacao === 'aceito') { ?>
                        <div class="alert alert-success" style="text-align:center">
                            <i class="fa fa-check-circle"></i>
                            <strong>Serviço aceito</strong> por <?= html_escape($os->aceite_nome) ?> em <?= $os->aceite_data ? date('d/m/Y H:i', strtotime($os->aceite_data)) : '' ?>.
                        </div>
                    <?php } elseif ($situacao === 'recusado') { ?>
                        <div class="alert alert-error" style="text-align:center">
                            <i class="fa fa-times-circle"></i>
                            <strong>Serviço recusado</strong> por <?= html_escape($os->aceite_nome) ?> em <?= $os->aceite_data ? date('d/m/Y H:i', strtotime($os->aceite_data)) : '' ?>.
                            <?php if (! empty($os->aceite_obs)) { ?><br><em>Motivo: <?= html_escape($os->aceite_obs) ?></em><?php } ?>
                        </div>
                    <?php } ?>

                    <div style="margin-bottom:6px">
                        <span class="ac-badge <?= $situacao ?>">
                            OS Nº <?= (int) $os->idOs ?> &middot;
                            <?= $situacao === 'pendente' ? 'Aguardando seu aceite' : ($situacao === 'aceito' ? 'Aceito' : 'Recusado') ?>
                        </span>
                    </div>

                    <div class="ac-section-title">Dados do atendimento</div>
                    <div class="ac-grid">
                        <div class="ac-field"><label>Cliente</label><span><?= html_escape($os->nomeCliente) ?></span></div>
                        <div class="ac-field"><label>Responsável</label><span><?= html_escape($os->nome_responsavel ?? '--') ?></span></div>
                        <div class="ac-field"><label>Abertura</label><span><?= ac_data($os->dataInicial) ?></span></div>
                        <div class="ac-field"><label>Encerramento</label><span><?= ac_data($os->dataFinal) ?></span></div>
                    </div>

                    <?php if (! empty($os->defeito) || ! empty($os->laudoTecnico)) { ?>
                        <div class="ac-section-title">Serviço executado</div>
                        <?php if (! empty($os->defeito)) { ?>
                            <div class="ac-field" style="margin-bottom:10px"><label>Defeito relatado</label><span><?= nl2br(html_escape($os->defeito)) ?></span></div>
                        <?php } ?>
                        <?php if (! empty($os->laudoTecnico)) { ?>
                            <div class="ac-field"><label>Laudo técnico</label><span><?= nl2br(html_escape($os->laudoTecnico)) ?></span></div>
                        <?php } ?>
                    <?php } ?>

                    <?php if (! empty($servicos)) { ?>
                        <div class="ac-section-title">Serviços</div>
                        <table class="ac-items">
                            <thead>
                                <tr><th>Serviço</th><th class="num">Qtd.</th><th class="num">Subtotal</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($servicos as $s) {
                                    $preco = $s->preco ?: $s->precoVenda;
                                    $qtd = $s->quantidade ?: 1; ?>
                                    <tr>
                                        <td><?= html_escape($s->nome) ?></td>
                                        <td class="num"><?= (int) $qtd ?></td>
                                        <td class="num"><?= ac_money($preco * $qtd) ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    <?php } ?>

                    <?php if (! empty($produtos)) { ?>
                        <div class="ac-section-title">Produtos / Peças</div>
                        <table class="ac-items">
                            <thead>
                                <tr><th>Produto</th><th class="num">Qtd.</th><th class="num">Subtotal</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($produtos as $p) { ?>
                                    <tr>
                                        <td><?= html_escape($p->descricao) ?></td>
                                        <td class="num"><?= (int) $p->quantidade ?></td>
                                        <td class="num"><?= ac_money($p->subTotal) ?></td>
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
                    <div class="ac-total">
                        <?php if ($temDesconto) { ?><div>Subtotal: <?= ac_money($subtotalGeral) ?></div><?php } ?>
                        <div>Total: <strong><?= ac_money($totalFinal) ?></strong></div>
                    </div>

                    <?php if ($totalFotos > 0) { ?>
                        <div class="ac-section-title">Registro fotográfico do atendimento</div>
                        <?php
                        $rotulos = ['entrada' => 'Entrada', 'durante' => 'Durante', 'saida' => 'Saída'];
                        foreach ($rotulos as $etapa => $rot) {
                            if (empty($fotos_etapa[$etapa])) {
                                continue;
                            } ?>
                            <div class="ac-etapa-tit"><?= $rot ?></div>
                            <div class="ac-fotos">
                                <?php foreach ($fotos_etapa[$etapa] as $foto) { ?>
                                    <a href="<?= site_url('aceite/foto/' . $token . '/' . $foto->idFoto) ?>" target="_blank">
                                        <img src="<?= site_url('aceite/foto/' . $token . '/' . $foto->idFoto) ?>" alt="Foto <?= $rot ?>">
                                    </a>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    <?php } ?>

                    <?php if (! empty($assinaturas)) { ?>
                        <div class="ac-section-title">Assinaturas do atendimento</div>
                        <div class="ac-assinaturas">
                            <?php foreach ($assinaturas as $a) { ?>
                                <img src="<?= site_url('aceite/assinatura/' . $token . '/' . $a->idAssinatura) ?>" alt="Assinatura">
                            <?php } ?>
                        </div>
                    <?php } ?>

                    <?php if ($situacao === 'pendente') { ?>
                        <div class="ac-actions">
                            <h4 style="margin-top:0">Confirme o aceite do serviço</h4>
                            <p class="muted">Confirme que o serviço foi realizado a contento. Ao aceitar, assine no quadro abaixo.</p>

                            <form method="post" action="<?= site_url('aceite/confirmar') ?>" id="formAceite">
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                                <input type="hidden" name="token" value="<?= html_escape($token) ?>">
                                <input type="hidden" name="decisao" id="decisao" value="">
                                <input type="hidden" name="assinatura" id="assinatura" value="">

                                <div class="control-group">
                                    <label class="control-label" for="nome"><strong>Seu nome *</strong></label>
                                    <div class="controls">
                                        <input type="text" name="nome" id="nome" maxlength="150" placeholder="Nome de quem confirma o aceite" required>
                                    </div>
                                </div>

                                <div class="control-group" id="assinaturaBox">
                                    <label class="control-label"><strong>Assinatura *</strong></label>
                                    <div class="ac-canvas-box">
                                        <canvas id="canvasAssinatura"></canvas>
                                    </div>
                                    <button type="button" class="btn btn-mini" id="btnLimparAssinatura" style="margin-top:6px"><i class="fa fa-eraser"></i> Limpar</button>
                                </div>

                                <div class="control-group" id="obsBox">
                                    <label class="control-label" for="obs"><strong>Motivo da recusa *</strong></label>
                                    <div class="controls">
                                        <textarea name="obs" id="obs" rows="3" placeholder="Descreva o motivo da recusa"></textarea>
                                    </div>
                                </div>

                                <div class="ac-btns">
                                    <button type="submit" class="btn btn-success" id="btnAceitar"><i class="fa fa-check"></i> Aceitar serviço</button>
                                    <button type="submit" class="btn btn-danger" id="btnRecusar"><i class="fa fa-times"></i> Recusar</button>
                                </div>
                            </form>

                            <p class="ac-note">Válido até <?= ac_data($os->aceite_expira) ?>. Este link é pessoal e deixa de funcionar após a sua decisão.</p>
                        </div>
                    <?php } ?>

                    <?php if ($emitente && ! empty($emitente->telefone)) { ?>
                        <p class="ac-note">Dúvidas? Fale com <?= html_escape($emitente->nome) ?> &middot; <?= html_escape($emitente->telefone) ?></p>
                    <?php } ?>

                <?php } ?>

            </div>
        </div>
    </div>

    <script src="<?= base_url() ?>assets/js/jquery-1.12.4.min.js"></script>
    <?php if ($situacao === 'pendente') { ?>
        <script src="<?= base_url() ?>assets/js/assinatura-canvas.js"></script>
        <script>
            $(function() {
                var $form = $('#formAceite');
                var assinatura = new AssinaturaCanvas('canvasAssinatura', { altura: 180 });

                $('#btnLimparAssinatura').on('click', function() { assinatura.limpar(); });

                $('#btnAceitar').on('click', function(e) {
                    e.preventDefault();
                    if (!$.trim($('#nome').val())) {
                        alert('Por favor, informe seu nome.');
                        $('#nome').focus();
                        return;
                    }
                    if (assinatura.estaVazio()) {
                        alert('Por favor, assine no quadro para confirmar o aceite.');
                        return;
                    }
                    if (!confirm('Confirma o ACEITE do serviço realizado?')) return;
                    $('#decisao').val('aceito');
                    $('#assinatura').val(assinatura.obterImagem());
                    $form.get(0).submit();
                });

                $('#btnRecusar').on('click', function(e) {
                    e.preventDefault();
                    $('#obsBox').show();
                    $('#decisao').val('recusado');
                    if (!$.trim($('#nome').val())) {
                        alert('Por favor, informe seu nome.');
                        $('#nome').focus();
                        return;
                    }
                    if (!$.trim($('#obs').val())) {
                        alert('Descreva o motivo da recusa.');
                        $('#obs').focus();
                        return;
                    }
                    if (confirm('Confirma a RECUSA do serviço?')) {
                        $form.get(0).submit();
                    }
                });
            });
        </script>
    <?php } ?>
</body>

</html>
