<?php
// Compra sugerida / ponto de pedido - ADR-008
$total = 0;
foreach ($itens as $i) {
    $total += $i['custo'];
}
$qs = http_build_query(['janela' => $filtro['janela'], 'prazo' => $filtro['prazo'], 'cobertura' => $filtro['cobertura'], 'somente' => $filtro['somente'] ? 1 : 0]);
?>

<div class="row-fluid" style="margin-top: 0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon"><i class="bx bx-cart"></i></span>
                <h5>Compra sugerida</h5>
            </div>
            <div class="widget-content">
                <?php if (! $disponivel) : ?>
                    <div class="alert alert-danger">A tabela de movimentação de estoque ainda não existe. Execute as migrações do sistema.</div>
                <?php else : ?>
                <form action="<?= site_url('comprasugerida') ?>" method="get" class="span12 well" style="margin-left:0">
                    <div class="span2">
                        <label>Janela de consumo (dias)</label>
                        <input type="number" min="1" max="730" name="janela" class="span12" value="<?= (int) $filtro['janela'] ?>" />
                    </div>
                    <div class="span2">
                        <label>Prazo de reposição (dias)</label>
                        <input type="number" min="1" max="365" name="prazo" class="span12" value="<?= (int) $filtro['prazo'] ?>" />
                    </div>
                    <div class="span2">
                        <label>Cobertura desejada (dias)</label>
                        <input type="number" min="1" max="365" name="cobertura" class="span12" value="<?= (int) $filtro['cobertura'] ?>" />
                    </div>
                    <div class="span3">
                        <label>&nbsp;</label>
                        <label class="checkbox"><input type="checkbox" name="somente" value="1" <?= $filtro['somente'] ? 'checked' : '' ?> /> Só o que precisa comprar</label>
                    </div>
                    <div class="span3">
                        <label>&nbsp;</label>
                        <button class="button btn btn-inverse"><span class="button__icon"><i class="bx bx-filter-alt"></i></span><span class="button__text">Calcular</span></button>
                        <a class="button btn btn-mini btn-success" href="<?= site_url('comprasugerida/csv?' . $qs) ?>"><span class="button__icon"><i class="bx bx-download"></i></span><span class="button__text">CSV</span></a>
                    </div>
                </form>

                <p class="muted span12" style="margin-left:0">
                    Ponto de pedido = consumo diário × prazo + estoque mínimo. Quantidade sugerida = consumo diário × (prazo + cobertura) + mínimo − estoque.
                    Ajustes e inventários não contam como consumo.
                </p>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Produto</th><th>Estoque</th><th>Mínimo</th><th>Saídas</th><th>Consumo/dia</th>
                            <th>Ponto de pedido</th><th>Cobertura (dias)</th><th>Qtd sugerida</th><th>Custo estimado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (! $itens) : ?>
                            <tr><td colspan="9">Nenhum produto a comprar com estes parâmetros.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($itens as $i) : ?>
                            <tr<?= $i['abaixo'] ? ' style="background:#fdecea"' : '' ?>>
                                <td><?= html_escape($i['descricao']) ?> <?= $i['unidade'] ? '<small class="muted">(' . html_escape($i['unidade']) . ')</small>' : '' ?></td>
                                <td><?= html_escape(number_format($i['estoque'], 3, ',', '.')) ?></td>
                                <td><?= html_escape(number_format($i['estoqueMinimo'], 3, ',', '.')) ?></td>
                                <td><?= html_escape(number_format($i['saidas'], 3, ',', '.')) ?></td>
                                <td><?= html_escape(number_format($i['consumo_diario'], 3, ',', '.')) ?></td>
                                <td><?= html_escape(number_format($i['ponto_pedido'], 3, ',', '.')) ?></td>
                                <td><?= $i['dias_cobertura'] === null ? '—' : html_escape(number_format($i['dias_cobertura'], 1, ',', '.')) ?></td>
                                <td><strong><?= (int) $i['sugerida'] ?></strong></td>
                                <td>R$ <?= html_escape(number_format($i['custo'], 2, ',', '.')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="8" style="text-align:right"><strong>Total estimado</strong></td><td><strong>R$ <?= html_escape(number_format($total, 2, ',', '.')) ?></strong></td></tr>
                    </tfoot>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
