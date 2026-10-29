<?php
// Tabela de movimentacoes de estoque. Espera $movimentacoes e, opcional, $mostrarProduto (bool).
$tiposMov = [
    'os' => 'OS',
    'venda' => 'Venda',
    'tecnico' => 'Área do técnico',
    'projeto' => 'Projeto',
    'api' => 'API',
    'ajuste' => 'Ajuste manual',
    'cadastro' => 'Cadastro',
    'inventario' => 'Inventário',
];
$mostrarProduto = ! empty($mostrarProduto);
$numero = static function ($valor) {
    return rtrim(rtrim(number_format((float) $valor, 3, ',', '.'), '0'), ',');
};
?>
<div class="ui-tabela-rolagem"><table class="table table-bordered table-condensed">
    <thead>
        <tr>
            <th style="width:130px">Data</th>
            <?php if ($mostrarProduto) { ?><th>Produto</th><?php } ?>
            <th style="width:120px">Origem</th>
            <th style="width:90px; text-align:right">Quantidade</th>
            <th style="width:110px; text-align:center">Estoque</th>
            <th>Observação</th>
            <th style="width:130px">Usuário</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($movimentacoes)) { ?>
            <tr><td colspan="<?= $mostrarProduto ? 7 : 6 ?>"><div class="ui-vazio"><i class="bx bx-transfer"></i><strong>Nenhuma movimentação registrada</strong>Entradas, saídas e ajustes de estoque aparecem aqui.</div></td></tr>
        <?php }
        foreach ($movimentacoes as $m) {
            $q = (float) $m->quantidade;
            $origem = $tiposMov[$m->tipo] ?? $m->tipo;
            $link = null;
            if ($m->referencia_id && in_array($m->tipo, ['os', 'tecnico', 'api'], true)) {
                $link = site_url('os/visualizar/' . (int) $m->referencia_id);
                $origem .= ' #' . (int) $m->referencia_id;
            } elseif ($m->referencia_id && $m->tipo === 'venda') {
                $link = site_url('vendas/visualizar/' . (int) $m->referencia_id);
                $origem .= ' #' . (int) $m->referencia_id;
            } elseif ($m->referencia_id && $m->tipo === 'inventario') {
                $link = site_url('inventarios/contar/' . (int) $m->referencia_id);
                $origem .= ' #' . (int) $m->referencia_id;
            } ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($m->criado_em)) ?></td>
                <?php if ($mostrarProduto) { ?>
                    <td><a href="<?= site_url('produtos/visualizar/' . (int) $m->produtos_id) ?>"><?= html_escape($m->produto ?: '#' . $m->produtos_id) ?></a></td>
                <?php } ?>
                <td><?= $link ? '<a href="' . $link . '">' . html_escape($origem) . '</a>' : html_escape($origem) ?></td>
                <td style="text-align:right; font-weight:600; color:<?= $q < 0 ? '#c0392b' : '#27ae60' ?>"><?= ($q > 0 ? '+' : '') . $numero($q) ?></td>
                <td style="text-align:center; color:#666"><?= $m->estoque_anterior === null ? '-' : $numero($m->estoque_anterior) . ' → ' . $numero($m->estoque_novo) ?></td>
                <td><?= html_escape($m->observacao ?: '') ?></td>
                <td><?= html_escape($m->usuario ?: '-') ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table></div>
