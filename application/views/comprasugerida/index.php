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
        <div class="ui-cabecalho">
            <div>
                <h1><i class="bx bx-cart"></i> Compra sugerida</h1>
                <p>Produtos que devem ser repostos, com base no consumo recente e no estoque mínimo.</p>
            </div>
            <?php if ($disponivel) : ?>
            <div class="ui-acoes">
                <a class="button btn btn-success" href="<?= site_url('comprasugerida/csv?' . $qs) ?>" title="Baixar a lista em CSV"><span class="button__icon"><i class="bx bx-download"></i></span><span class="button__text">Baixar CSV</span></a>
            </div>
            <?php endif; ?>
        </div>

        <?php if (! $disponivel) : ?>
            <div class="alert alert-danger">A tabela de movimentação de estoque ainda não existe. Execute as migrações do sistema.</div>
        <?php else : ?>
        <form action="<?= site_url('comprasugerida') ?>" method="get" class="ui-filtros">
            <div class="ui-campo">
                <label for="janela">Janela de consumo (dias)</label>
                <input type="number" id="janela" min="1" max="730" name="janela" value="<?= (int) $filtro['janela'] ?>" />
            </div>
            <div class="ui-campo">
                <label for="prazo">Prazo de reposição (dias)</label>
                <input type="number" id="prazo" min="1" max="365" name="prazo" value="<?= (int) $filtro['prazo'] ?>" />
            </div>
            <div class="ui-campo">
                <label for="cobertura">Cobertura desejada (dias)</label>
                <input type="number" id="cobertura" min="1" max="365" name="cobertura" value="<?= (int) $filtro['cobertura'] ?>" />
            </div>
            <div class="ui-campo">
                <label class="checkbox"><input type="checkbox" name="somente" value="1" <?= $filtro['somente'] ? 'checked' : '' ?> /> Só o que precisa comprar</label>
            </div>
            <div class="ui-campo ui-campo-acao">
                <button class="button btn btn-inverse"><span class="button__icon"><i class="bx bx-filter-alt"></i></span><span class="button__text">Calcular</span></button>
            </div>
        </form>

        <div class="ui-kpis">
            <div class="ui-kpi info"><div class="ui-kpi-rotulo">Produtos listados</div><div class="ui-kpi-valor"><?= (int) count($itens) ?></div></div>
            <div class="ui-kpi alerta"><div class="ui-kpi-rotulo">Total estimado</div><div class="ui-kpi-valor">R$ <?= html_escape(number_format($total, 2, ',', '.')) ?></div></div>
        </div>

        <div class="ui-dica">
            <i class="bx bx-info-circle"></i>
            <div>Ponto de pedido = consumo diário × prazo + estoque mínimo. Quantidade sugerida = consumo diário × (prazo + cobertura) + mínimo − estoque. Ajustes e inventários não contam como consumo. Linhas em vermelho estão abaixo do mínimo.</div>
        </div>

        <div class="widget-box">
            <div class="widget-content nopadding ui-tabela-rolagem">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Produto</th><th>Estoque</th><th>Mínimo</th><th>Saídas</th><th>Consumo/dia</th>
                            <th>Ponto de pedido</th><th>Cobertura (dias)</th><th>Qtd sugerida</th><th>Custo estimado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (! $itens) : ?>
                            <tr><td colspan="9"><div class="ui-vazio"><i class="bx bx-package"></i><strong>Nenhum produto a comprar</strong>Não há reposição necessária com estes parâmetros.</div></td></tr>
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
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
