<?php
// Relatorio de cobranca do mes por cliente - ADR-011
$moeda = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$fmtData = fn ($d) => $d ? date('d/m/Y', strtotime($d)) : '—';
$totalQtd = fn ($k) => (int) ($resumo['totais'][$k]['qtd'] ?? 0);
$totalValor = fn ($k) => (float) ($resumo['totais'][$k]['valor'] ?? 0);
$cobrarQtd = $totalQtd('nao_cobrado') + $totalQtd('pendente');
$cobrarValor = $totalValor('nao_cobrado') + $totalValor('pendente');

$mesTitulo = date('m/Y', strtotime($competencia . '-01'));
$clienteNome = 'Todos';
foreach ($clientes as $c) {
    if ($filtro['clientes_id'] !== null && (int) $filtro['clientes_id'] === (int) $c->idClientes) {
        $clienteNome = $c->nomeCliente;
    }
}
$origemTexto = $filtro['origem'] !== '' ? $origens[$filtro['origem']] : 'Todas';
$situacaoTexto = $filtro['situacao']
    ? implode(', ', array_map(fn ($k) => $situacoes[$k]['rotulo'], $filtro['situacao']))
    : 'Todas';
?>

<style>
    .so-impressao { display: none; }
    .cobranca-chips-linha { flex: 1 1 100%; order: -1; display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .cobranca-chips-linha > span { font-size: 12px; font-weight: 600; color: var(--ui-texto-2); margin-right: 4px; }
    .cobranca-chip { position: relative; cursor: pointer; margin: 0; }
    .cobranca-chip input { position: absolute; opacity: 0; pointer-events: none; }
    .cobranca-chip .ui-selo { font-size: 13px; padding: 6px 12px; border: 1px solid var(--ui-borda); opacity: .8; }
    .cobranca-chip:hover .ui-selo { opacity: 1; }
    .cobranca-chip input:checked + .ui-selo { opacity: 1; box-shadow: inset 0 0 0 2px currentColor; }
    .cobranca-chip input:focus-visible + .ui-selo { outline: 2px solid var(--ui-info); outline-offset: 2px; }
    .cobranca-kpi-destaque { border-width: 2px; border-color: var(--ui-info); }
    .cobranca-bloco { margin-bottom: 18px; border: 1px solid var(--ui-borda); border-radius: var(--ui-raio); overflow: hidden; }
    .cobranca-bloco-titulo { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; align-items: baseline; padding: 10px 14px; border-bottom: 1px solid var(--ui-borda); }
    .cobranca-bloco-titulo h5 { margin: 0; font-size: 15px; }
    .cobranca-bloco-titulo strong { white-space: nowrap; }
    .cobranca-direita { text-align: right; }
    .cobranca-total-linha th { font-size: 14px; }

    @media print {
        #sidebar, #header, #user-nav, #userr, #footer, #breadcrumb, .breadcrumb, .ui-filtros, .ui-acoes, .ui-dica,
        button, .btn { display: none !important; }
        a[href]:after { content: none !important; }
        html body #content, html body .container-fluid, .new122 { margin: 0 !important; padding: 0 !important; left: 0 !important; position: static !important; width: auto !important; max-width: none !important; }
        html body { padding: 0 !important; margin: 0 !important; }
        .ui-cabecalho { display: none !important; }
        @page { size: A4 landscape; margin: 10mm; }
        .so-impressao { display: block !important; margin-bottom: 12px; }
        .so-impressao h2 { margin: 0 0 4px; font-size: 18px; color: #000 !important; }
        .so-impressao p { margin: 0; font-size: 12px; color: #000 !important; }
        body, #content, #content-header, .container-fluid, .row-fluid, .span12 {
            background: #fff !important; color: #000 !important; margin: 0 !important; padding: 0 !important;
            width: 100% !important; min-height: 0 !important;
        }
        #content { margin-left: 0 !important; }
        * { box-shadow: none !important; text-shadow: none !important; transition: none !important; }
        a { color: #000 !important; text-decoration: none; }
        .ui-cabecalho h1, .ui-cabecalho p { color: #000 !important; }
        .ui-kpi, .ui-kpi * { color: #000 !important; background: #fff !important; }
        .ui-kpi { border: 1px solid #000 !important; }
        .ui-kpis { display: flex; flex-wrap: wrap; gap: 6px; }
        .ui-kpi { flex: 1 1 110px; }
        .cobranca-bloco { border: none !important; margin-bottom: 14px; page-break-inside: avoid; break-inside: avoid; }
        .cobranca-bloco-titulo { border-bottom: 1px solid #000 !important; padding-left: 0; padding-right: 0; }
        .cobranca-bloco-titulo * { color: #000 !important; }
        .ui-tabela-rolagem { overflow: visible !important; max-height: none !important; }
        table, th, td { border: 1px solid #000 !important; color: #000 !important; background: #fff !important; }
        table { border-collapse: collapse !important; width: 100% !important; font-size: 11px; }
        .ui-selo { background: #fff !important; color: #000 !important; border: 1px solid #000 !important; }
        .ui-vazio { color: #000 !important; }
    }
</style>

<div class="row-fluid" style="margin-top: 0">
    <div class="span12">
        <div class="so-impressao">
            <h2>Cobrança do mês <?= html_escape($mesTitulo) ?></h2>
            <p>
                Cliente: <?= html_escape($clienteNome) ?> |
                Origem: <?= html_escape($origemTexto) ?> |
                Situação: <?= html_escape($situacaoTexto) ?>
            </p>
        </div>

        <div class="ui-cabecalho">
            <div>
                <h1><i class="bx bx-receipt"></i> Cobrança do mês</h1>
                <p>O que foi feito, o que está pendente e o que ainda não foi cobrado, por cliente, no mês escolhido.</p>
            </div>
            <div class="ui-acoes">
                <button type="button" class="button btn btn-mini btn-inverse" data-acao="imprimir"><span class="button__icon"><i class="bx bx-printer"></i></span><span class="button__text">Imprimir</span></button>
            </div>
        </div>

        <form action="<?= site_url('relatoriocobranca') ?>" method="get" class="ui-filtros">
            <div class="ui-campo">
                <label for="competencia">Mês</label>
                <input type="month" id="competencia" name="competencia" value="<?= html_escape($competencia) ?>" />
            </div>
            <div class="ui-campo">
                <label for="clientes_id">Cliente</label>
                <select id="clientes_id" name="clientes_id">
                    <option value="">Todos</option>
                    <?php foreach ($clientes as $c) : ?>
                        <option value="<?= (int) $c->idClientes ?>" <?= $filtro['clientes_id'] !== null && (int) $filtro['clientes_id'] === (int) $c->idClientes ? 'selected' : '' ?>><?= html_escape($c->nomeCliente) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ui-campo">
                <label for="origem">Origem</label>
                <select id="origem" name="origem">
                    <option value="">Todas</option>
                    <?php foreach ($origens as $chave => $rotulo) : ?>
                        <option value="<?= html_escape($chave) ?>" <?= $filtro['origem'] === $chave ? 'selected' : '' ?>><?= html_escape($rotulo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ui-campo ui-campo-acao">
                <button class="button btn btn-inverse"><span class="button__icon"><i class="bx bx-filter-alt"></i></span><span class="button__text">Filtrar</span></button>
                <a class="btn" href="<?= site_url('relatoriocobranca') ?>" title="Limpar filtros"><i class="bx bx-x"></i> Limpar</a>
            </div>
            <div class="cobranca-chips-linha">
                <span>Situação (marque para filtrar a tabela):</span>
                    <?php foreach ($situacoes as $chave => $s) : ?>
                        <label class="cobranca-chip">
                            <input type="checkbox" name="situacao[]" value="<?= html_escape($chave) ?>" <?= in_array($chave, $filtro['situacao'], true) ? 'checked' : '' ?> />
                            <span class="ui-selo <?= html_escape($s['classe']) ?>"><?= html_escape($s['rotulo']) ?> (<?= $totalQtd($chave) ?>)</span>
                        </label>
                    <?php endforeach; ?>
            </div>
        </form>

        <div class="ui-kpis">
            <?php foreach ($situacoes as $chave => $s) : ?>
                <div class="ui-kpi <?= html_escape($s['classe']) ?>">
                    <div class="ui-kpi-rotulo"><?= html_escape($s['rotulo']) ?></div>
                    <div class="ui-kpi-valor"><?= $moeda($totalValor($chave)) ?></div>
                    <div class="ui-kpi-extra"><?= $totalQtd($chave) ?> item(ns)</div>
                </div>
            <?php endforeach; ?>
            <div class="ui-kpi cobranca-kpi-destaque">
                <div class="ui-kpi-rotulo">Total a cobrar</div>
                <div class="ui-kpi-valor"><?= $moeda($cobrarValor) ?></div>
                <div class="ui-kpi-extra"><?= $cobrarQtd ?> item(ns) não cobrado ou pendente</div>
            </div>
        </div>

        <?php if (! $resumo['clientes']) : ?>
            <div class="widget-box">
                <div class="ui-vazio"><i class="bx bx-calendar-x"></i><strong>Nenhum item neste mês</strong>Escolha outro mês ou ajuste os filtros.</div>
            </div>
        <?php endif; ?>

        <?php
        // Clientes cadastrados primeiro; lancamentos sem cliente no fim.
        $blocos = $resumo['clientes'];
        usort($blocos, fn ($a, $b) => (empty($a['clientes_id']) <=> empty($b['clientes_id'])) ?: strcasecmp($a['cliente'], $b['cliente']));
        foreach ($blocos as $cli) : ?>
            <div class="cobranca-bloco">
                <div class="cobranca-bloco-titulo">
                    <h5><?= html_escape($cli['cliente']) ?></h5>
                    <strong>Total a cobrar: <?= $moeda($cli['total_a_cobrar']) ?></strong>
                </div>
                <div class="ui-tabela-rolagem">
                    <table class="table table-bordered">
                        <thead>
                            <tr><th>Data</th><th>Origem</th><th>Descrição</th><th>Situação</th><th>Detalhe</th><th>Vencimento</th><th>Valor</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cli['itens'] as $item) :
                                $sit = $situacoes[$item['situacao']] ?? ['rotulo' => $item['situacao'], 'classe' => 'neutro'];
                                ?>
                                <tr>
                                    <td><?= $fmtData($item['data_ref']) ?></td>
                                    <td><?= html_escape($origens[$item['origem']] ?? $item['origem']) ?></td>
                                    <td><a href="<?= site_url($item['link']) ?>"><?= html_escape($item['descricao']) ?></a></td>
                                    <td><span class="ui-selo <?= html_escape($sit['classe']) ?>"><?= html_escape($sit['rotulo']) ?></span></td>
                                    <td><?= html_escape($item['detalhe']) ?></td>
                                    <td><?= $fmtData($item['vencimento'] ?? null) ?></td>
                                    <td><?= $moeda($item['valor']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <?php foreach ($situacoes as $chave => $s) : ?>
                                <?php if (! empty($cli['totais'][$chave]) && (float) $cli['totais'][$chave] != 0) : ?>
                                    <tr>
                                        <th colspan="6" class="cobranca-direita">Subtotal: <?= html_escape($s['rotulo']) ?></th>
                                        <th><?= $moeda($cli['totais'][$chave]) ?></th>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <tr class="cobranca-total-linha">
                                <th colspan="6" class="cobranca-direita">Total a cobrar</th>
                                <th><?= $moeda($cli['total_a_cobrar']) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
