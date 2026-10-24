<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="bx bx-transfer"></i></span>
        <h5>Movimentações de estoque</h5>
    </div>
    <div class="widget-content nopadding">
        <form method="get" action="<?= site_url('produtos/movimentacoes') ?>" style="padding:12px 14px 0; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end">
            <?php if ($produto) { ?>
                <input type="hidden" name="produto" value="<?= (int) $produto->idProdutos ?>">
                <div><label>Produto</label><strong><?= html_escape($produto->descricao) ?></strong>
                    <a href="<?= site_url('produtos/movimentacoes') ?>" title="Todos os produtos">(ver todos)</a></div>
            <?php } ?>
            <div>
                <label>Origem</label>
                <select name="tipo" style="width:150px">
                    <option value="">Todas</option>
                    <?php foreach (['os' => 'OS', 'venda' => 'Venda', 'tecnico' => 'Área do técnico', 'api' => 'API', 'ajuste' => 'Ajuste manual', 'cadastro' => 'Cadastro', 'inventario' => 'Inventário'] as $valor => $nome) { ?>
                        <option value="<?= $valor ?>" <?= $filtros['tipo'] === $valor ? 'selected' : '' ?>><?= $nome ?></option>
                    <?php } ?>
                </select>
            </div>
            <div><label>De</label><input type="date" name="de" value="<?= html_escape($filtros['de']) ?>" style="width:140px"></div>
            <div><label>Até</label><input type="date" name="ate" value="<?= html_escape($filtros['ate']) ?>" style="width:140px"></div>
            <div><button class="button btn btn-mini btn-warning" type="submit"><span class="button__icon"><i class="bx bx-search-alt"></i></span><span class="button__text2">Filtrar</span></button></div>
            <div style="margin-left:auto">
                <a href="<?= site_url('inventarios') ?>" class="button btn btn-mini btn-info"><span class="button__icon"><i class="bx bx-list-check"></i></span><span class="button__text2">Inventários</span></a>
            </div>
        </form>

        <div style="padding:0 14px">
            <p style="color:#777; margin:10px 0">Total: <?= (int) $total ?> movimentação(ões)</p>
            <?php $this->load->view('produtos/_movimentacoes_tabela', ['movimentacoes' => $movimentacoes, 'mostrarProduto' => ! $produto]); ?>

            <?php if ($paginas > 1) { ?>
                <div class="pagination" style="margin:0 0 14px"><ul>
                    <?php for ($p = 1; $p <= $paginas; $p++) {
                        $q = array_filter(array_merge($filtros, ['produto' => $produto ? $produto->idProdutos : null, 'pagina' => $p])); ?>
                        <li class="<?= $p === $pagina ? 'active' : '' ?>"><a href="<?= site_url('produtos/movimentacoes') . '?' . http_build_query($q) ?>"><?= $p ?></a></li>
                    <?php } ?>
                </ul></div>
            <?php } ?>
        </div>
    </div>
</div>
