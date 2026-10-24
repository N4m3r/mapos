<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="bx bx-wallet"></i></span>
        <h5>Caixas do PDV</h5>
    </div>
    <div class="widget-content nopadding">
        <div style="padding:12px 14px 0; display:flex; gap:10px; flex-wrap:wrap">
            <a href="<?= site_url('pdv') ?>" class="button btn btn-mini btn-success"><span class="button__icon"><i class="bx bx-store"></i></span><span class="button__text2"><?= $meuCaixa ? 'Ir para o PDV' : 'Abrir caixa / PDV' ?></span></a>
            <?php if ($meuCaixa) { ?>
                <a href="<?= site_url('caixa/ver/' . (int) $meuCaixa->id) ?>" class="button btn btn-mini btn-info"><span class="button__icon"><i class="bx bx-wallet"></i></span><span class="button__text2">Meu caixa aberto (#<?= (int) $meuCaixa->id ?>)</span></a>
            <?php } ?>
        </div>
        <table class="table table-bordered" style="margin-top:12px">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    <th>Operador</th>
                    <th style="width:140px">Aberto em</th>
                    <th style="width:140px">Fechado em</th>
                    <th style="width:70px; text-align:center">Vendas</th>
                    <th style="width:120px; text-align:right">Vendido</th>
                    <th style="width:130px; text-align:right">Diferença (dinheiro)</th>
                    <th style="width:90px; text-align:center">Situação</th>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($caixas)) { ?>
                    <tr><td colspan="9">Nenhum caixa ainda.</td></tr>
                <?php }
                foreach ($caixas as $c) {
                    $diferenca = $c->contado_dinheiro !== null ? (float) $c->contado_dinheiro - (float) $c->esperado_dinheiro : null; ?>
                    <tr>
                        <td><?= (int) $c->id ?></td>
                        <td><?= html_escape($c->operador ?: '-') ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($c->aberto_em)) ?></td>
                        <td><?= $c->fechado_em ? date('d/m/Y H:i', strtotime($c->fechado_em)) : '-' ?></td>
                        <td style="text-align:center"><?= (int) $c->vendas ?></td>
                        <td style="text-align:right">R$ <?= number_format((float) $c->total_vendas, 2, ',', '.') ?></td>
                        <td style="text-align:right; color:<?= $diferenca === null ? '#999' : (abs($diferenca) < 0.01 ? '#27ae60' : '#c0392b') ?>">
                            <?= $diferenca === null ? '-' : 'R$ ' . number_format($diferenca, 2, ',', '.') ?></td>
                        <td style="text-align:center"><span class="badge <?= $c->status === 'aberto' ? 'badge-warning' : 'badge-success' ?>"><?= ucfirst(html_escape($c->status)) ?></span></td>
                        <td style="text-align:center"><a href="<?= site_url('caixa/ver/' . (int) $c->id) ?>" class="btn btn-mini btn-info"><i class="bx bx-show"></i></a></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
