<?php
$moeda = static fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$tipos = ['venda' => 'Venda', 'troco' => 'Troco', 'sangria' => 'Sangria', 'suprimento' => 'Suprimento'];
$resumo = $caixa->resumo_fechamento ? json_decode($caixa->resumo_fechamento, true) : null;
?>
<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="bx bx-wallet"></i></span>
        <h5>Caixa #<?= (int) $caixa->id ?> - <?= html_escape($caixa->operador ?: '') ?></h5>
    </div>
    <div class="widget-content">
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:12px">
            <a href="<?= site_url('caixa') ?>" class="btn btn-mini"><i class="bx bx-arrow-back"></i> Caixas</a>
            <span class="badge <?= $caixa->status === 'aberto' ? 'badge-warning' : 'badge-success' ?>"><?= ucfirst(html_escape($caixa->status)) ?></span>
            <span>Aberto em <?= date('d/m/Y H:i', strtotime($caixa->aberto_em)) ?><?= $caixa->fechado_em ? ' · fechado em ' . date('d/m/Y H:i', strtotime($caixa->fechado_em)) : '' ?></span>
            <?php if ($meu) { ?><a href="<?= site_url('pdv') ?>" class="button btn btn-mini btn-success" style="margin-left:auto"><span class="button__icon"><i class="bx bx-store"></i></span><span class="button__text2">Voltar ao PDV</span></a><?php } ?>
        </div>

        <div class="row-fluid">
            <div class="span5">
                <table class="table table-bordered table-condensed">
                    <tr><td>Abertura (fundo de troco)</td><td style="text-align:right"><?= $moeda($caixa->valor_abertura) ?></td></tr>
                    <?php foreach ($formas as $chave => $nome) { ?>
                        <tr><td>Vendas em <?= html_escape(mb_strtolower($nome)) ?></td><td style="text-align:right"><?= $moeda($totais['por_forma'][$chave]) ?></td></tr>
                    <?php } ?>
                    <tr><td>Suprimentos</td><td style="text-align:right"><?= $moeda($totais['suprimentos']) ?></td></tr>
                    <tr><td>Sangrias</td><td style="text-align:right">-<?= $moeda($totais['sangrias']) ?></td></tr>
                    <tr style="font-weight:700"><td>Total vendido</td><td style="text-align:right"><?= $moeda($totais['vendas']) ?></td></tr>
                    <tr style="font-weight:700"><td>Dinheiro esperado na gaveta</td><td style="text-align:right"><?= $moeda($totais['esperado_dinheiro']) ?></td></tr>
                    <?php if ($caixa->status === 'fechado') { ?>
                        <tr><td>Dinheiro contado</td><td style="text-align:right"><?= $moeda($caixa->contado_dinheiro) ?></td></tr>
                        <?php $dif = (float) $caixa->contado_dinheiro - (float) $caixa->esperado_dinheiro; ?>
                        <tr style="font-weight:700; color:<?= abs($dif) < 0.01 ? '#27ae60' : '#c0392b' ?>"><td>Diferença</td><td style="text-align:right"><?= $moeda($dif) ?></td></tr>
                    <?php } ?>
                </table>
                <?php if ($caixa->observacao) { ?><p><strong>Observações:</strong> <?= nl2br(html_escape($caixa->observacao)) ?></p><?php } ?>

                <?php if ($meu) { ?>
                    <h5>Sangria / suprimento</h5>
                    <form action="<?= site_url('caixa/movimentar') ?>" method="post" style="display:flex; gap:6px; flex-wrap:wrap; align-items:center">
                        <input type="hidden" name="caixa_id" value="<?= (int) $caixa->id ?>">
                        <select name="tipo" style="width:130px; margin:0"><option value="sangria">Sangria (retirada)</option><option value="suprimento">Suprimento (reforço)</option></select>
                        <input type="text" name="valor" inputmode="decimal" placeholder="Valor" required style="width:90px; margin:0; text-align:right">
                        <input type="text" name="descricao" placeholder="Motivo" style="width:160px; margin:0">
                        <button type="submit" class="btn btn-small">Registrar</button>
                    </form>

                    <h5 style="margin-top:18px">Fechar caixa</h5>
                    <form action="<?= site_url('caixa/fechar') ?>" method="post" data-confirmar="Fechar o caixa? Depois disso não é possível vender com ele.">
                        <input type="hidden" name="caixa_id" value="<?= (int) $caixa->id ?>">
                        <label>Dinheiro contado na gaveta (R$)</label>
                        <input type="text" name="contado_dinheiro" inputmode="decimal" required style="width:140px; text-align:right">
                        <label>Observação</label>
                        <input type="text" name="observacao" maxlength="250" style="width:100%">
                        <div><button type="submit" class="button btn btn-danger"><span class="button__icon"><i class="bx bx-lock"></i></span><span class="button__text2">Fechar caixa</span></button></div>
                    </form>
                <?php } ?>
            </div>
            <div class="span7">
                <h5 style="margin-top:0">Movimentos</h5>
                <table class="table table-bordered table-condensed">
                    <thead><tr><th style="width:110px">Hora</th><th style="width:90px">Tipo</th><th>Forma</th><th>Descrição</th><th style="width:100px; text-align:right">Valor</th></tr></thead>
                    <tbody>
                        <?php if (empty($movimentos)) { ?><tr><td colspan="5">Nenhum movimento.</td></tr><?php }
                        foreach ($movimentos as $m) { ?>
                            <tr>
                                <td><?= date('d/m H:i', strtotime($m->criado_em)) ?></td>
                                <td><?= html_escape($tipos[$m->tipo] ?? $m->tipo) ?></td>
                                <td><?= html_escape($formas[$m->forma] ?? $m->forma) ?></td>
                                <td><?= $m->vendas_id ? '<a href="' . site_url('vendas/visualizar/' . (int) $m->vendas_id) . '">' . html_escape($m->descricao) . '</a>' : html_escape($m->descricao) ?></td>
                                <td style="text-align:right; color:<?= $m->valor < 0 ? '#c0392b' : '#27ae60' ?>"><?= $moeda($m->valor) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
