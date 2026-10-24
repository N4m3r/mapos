<?php
/**
 * Filtro reutilizável do portal do cliente: CNPJ/cliente vinculado + período.
 * Espera em escopo: $filtros (array), $clientesFiltro (lista) e $acaoFiltro
 * (rota destino, ex.: 'mine/os'). O seletor de CNPJ só aparece quando o login
 * enxerga mais de um cliente.
 */
$filtros = isset($filtros) ? $filtros : ['cliente_id' => null, 'data_inicio' => null, 'data_fim' => null];
$clientesFiltro = isset($clientesFiltro) ? $clientesFiltro : [];
$acaoFiltro = isset($acaoFiltro) ? $acaoFiltro : 'mine/os';
$temFiltro = ! empty($filtros['cliente_id']) || ! empty($filtros['data_inicio']) || ! empty($filtros['data_fim']);
$urlAcao = base_url() . 'index.php/' . $acaoFiltro;
?>
<div class="span12" style="margin-left: 0">
    <div class="widget-box">
        <div class="widget-content" style="padding: 15px;">
            <form method="get" action="<?php echo $urlAcao; ?>" class="form-inline os-filtros" style="margin: 0; display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;">
                <?php if (count($clientesFiltro) > 1) { ?>
                    <div>
                        <label style="display:block; font-weight:600;">CNPJ / Cliente</label>
                        <select name="cliente_id">
                            <option value="">Todos</option>
                            <?php foreach ($clientesFiltro as $c) {
                                $sel = (! empty($filtros['cliente_id']) && (int) $filtros['cliente_id'] === (int) $c->idClientes) ? 'selected' : '';
                                $label = $c->nomeCliente . ($c->documento ? ' (' . $c->documento . ')' : '');
                                echo '<option value="' . (int) $c->idClientes . '" ' . $sel . '>' . htmlspecialchars($label) . '</option>';
                            } ?>
                        </select>
                    </div>
                <?php } ?>
                <div>
                    <label style="display:block; font-weight:600;">De</label>
                    <input type="date" name="data_inicio" value="<?php echo htmlspecialchars($filtros['data_inicio'] ?? ''); ?>">
                </div>
                <div>
                    <label style="display:block; font-weight:600;">Até</label>
                    <input type="date" name="data_fim" value="<?php echo htmlspecialchars($filtros['data_fim'] ?? ''); ?>">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-search"></i> Filtrar</button>
                    <?php if ($temFiltro) { ?>
                        <a href="<?php echo $urlAcao; ?>" class="btn">Limpar</a>
                    <?php } ?>
                </div>
            </form>
        </div>
    </div>
</div>
