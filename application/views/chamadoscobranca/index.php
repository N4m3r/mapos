<?php
/**
 * Painel de cobranca dos chamados (OS).
 *
 * Dados: $itens, $kpis, $emAberto, $pontosStatus, $topClientes, $filtro, $disponivel.
 * Sem handler inline (CSP): impressao por data-acao, filtros e lotes por form.
 */
require_once APPPATH . 'libraries/Grafico_svg.php';

$perm = function ($p) {
    return $this->permission->checkPermission($this->session->userdata('permissao'), $p);
};
$e = function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$brl = fn ($v) => Chamado_cobranca::moeda($v);
$data = fn ($d) => ! empty($d) && strpos((string) $d, '0000') !== 0 ? date('d/m/Y', strtotime($d)) : '—';
$selecionados = Chamado_cobranca::filtro((array) $filtro['status']);
$podeAprovar = $perm('vOs');
$podeCobranca = $perm('eCobranca');
$chips = Chamado_cobranca::STATUS;
?>

<style>
    .cc-chips { display: flex; flex-wrap: wrap; gap: 8px; flex: 1 1 100%; order: -1; align-items: center; }
    .cc-chips > span { font-size: 12px; font-weight: 600; color: var(--ui-texto-2); margin-right: 4px; }
    .cc-chip { position: relative; cursor: pointer; margin: 0; }
    .cc-chip input { position: absolute; opacity: 0; pointer-events: none; }
    .cc-chip .ui-selo { font-size: 13px; padding: 6px 12px; border: 1px solid var(--ui-borda); opacity: .8; }
    .cc-chip:hover .ui-selo { opacity: 1; }
    .cc-chip input:checked + .ui-selo { opacity: 1; box-shadow: inset 0 0 0 2px currentColor; }
    .cc-chip input:focus-visible + .ui-selo { outline: 2px solid var(--ui-info); outline-offset: 2px; }
    .cc-destaque { border-width: 2px; border-color: var(--ui-info); }
    .cc-graficos { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 16px; margin-bottom: 16px; }
    .cc-fig { margin: 0; padding: 10px 12px; border: 1px solid var(--ui-borda); border-radius: var(--ui-raio); background: var(--ui-suave); }
    .cc-fig svg { display: block; width: 100%; height: auto; overflow: visible; }
    .cc-lote { position: sticky; top: 0; z-index: 3; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; padding: 10px 12px; margin: 0 0 10px; border: 1px solid var(--ui-borda); border-radius: var(--ui-raio); background: var(--ui-suave); }
    .cc-lote[hidden] { display: none; }
    .cc-lote strong { margin-right: auto; }
    .cc-col-sel { width: 34px; }
    .cc-num { white-space: nowrap; text-align: right; }
    .cc-so-impressao { display: none; }
    @media (max-width: 900px) { .cc-graficos { grid-template-columns: minmax(0, 1fr); } }

    @media print {
        #sidebar, #header, #user-nav, #userr, #footer, #breadcrumb, .breadcrumb, .ui-filtros, .ui-acoes, .ui-dica,
        button, .btn, .cc-lote, .cc-graficos { display: none !important; }
        a[href]:after { content: none !important; }
        html body #content, html body .container-fluid, .new122 { margin: 0 !important; padding: 0 !important; left: 0 !important; position: static !important; width: auto !important; max-width: none !important; }
        html body { padding: 0 !important; margin: 0 !important; }
        .ui-cabecalho { display: none !important; }
        @page { size: A4 landscape; margin: 10mm; }
        .cc-so-impressao { display: block !important; margin-bottom: 12px; }
        .cc-so-impressao h2 { margin: 0 0 4px; font-size: 18px; color: #000 !important; }
        .cc-so-impressao p { margin: 0; font-size: 12px; color: #000 !important; }
        body, #content, #content-header, .container-fluid, .row, .col-md-12 {
            background: #fff !important; color: #000 !important; margin: 0 !important; padding: 0 !important;
            width: 100% !important; min-height: 0 !important;
        }
        #content { margin-left: 0 !important; }
        * { box-shadow: none !important; text-shadow: none !important; transition: none !important; }
        a { color: #000 !important; text-decoration: none; }
        .ui-kpi, .ui-kpi * { color: #000 !important; background: #fff !important; }
        .ui-kpi { border: 1px solid #000 !important; }
        .ui-kpis { display: flex; flex-wrap: wrap; gap: 6px; }
        .ui-kpi { flex: 1 1 110px; }
        .ui-tabela-rolagem { overflow: visible !important; max-height: none !important; }
        table, th, td { border: 1px solid #000 !important; color: #000 !important; background: #fff !important; }
        table { border-collapse: collapse !important; width: 100% !important; font-size: 11px; }
        .ui-selo { background: #fff !important; color: #000 !important; border: 1px solid #000 !important; }
        .ui-vazio { color: #000 !important; }
        .cc-col-sel { display: none !important; }
    }
</style>

<div class="row" style="margin-top: 0">
    <div class="col-md-12">
        <div class="cc-so-impressao">
            <h2>Cobrança dos chamados</h2>
            <p>
                Situações: <?= $selecionados ? $e(implode(', ', array_map(fn ($s) => $chips[$s]['rotulo'], $selecionados))) : 'Todas' ?> |
                Cliente: <?= $e($filtro['busca'] ?: 'Todos') ?> |
                Chamado: <?= $e($filtro['chamado'] ?: 'Todos') ?> |
                Emitido em <?= date('d/m/Y H:i') ?>
            </p>
        </div>

        <div class="ui-cabecalho">
            <div>
                <h1><i class="bx bx-receipt"></i> Cobrança dos chamados</h1>
                <p>O que aguarda aprovação, o que falta cobrar e o que está em cobrança, numa lista só.</p>
            </div>
            <div class="ui-acoes">
                <button type="button" class="button btn btn-sm btn-dark" data-acao="imprimir"><span class="button__icon"><i class="bx bx-printer"></i></span><span class="button__text">Imprimir</span></button>
                <a class="button btn btn-sm btn-primary" href="<?= site_url('chamadoscobranca/csv') ?>" data-csv="1"><span class="button__icon"><i class="bx bx-spreadsheet"></i></span><span class="button__text">CSV</span></a>
            </div>
        </div>

        <?php if (! $disponivel) { ?>
            <div class="ui-dica alerta"><i class="bx bx-info-circle"></i><div>O campo de chamado ainda não existe neste banco. Rode as migrations (<code>php index.php tools migrate</code>) para preencher e filtrar por ele.</div></div>
        <?php } ?>

        <form method="get" action="<?= site_url('chamadoscobranca') ?>" class="ui-filtros">
            <div class="ui-campo">
                <label for="q">Cliente</label>
                <input class="form-control" type="text" id="q" name="q" value="<?= $e($filtro['busca']) ?>" placeholder="Nome do cliente" />
            </div>
            <div class="ui-campo">
                <label for="chamado">Chamado</label>
                <input class="form-control" type="text" id="chamado" name="chamado" value="<?= $e($filtro['chamado']) ?>" placeholder="Número do chamado" />
            </div>
            <div class="ui-campo ui-campo-acao">
                <button class="button btn btn-dark"><span class="button__icon"><i class="bx bx-filter-alt"></i></span><span class="button__text">Filtrar</span></button>
                <a class="btn btn-outline-secondary" href="<?= site_url('chamadoscobranca') ?>" title="Limpar filtros"><i class="bx bx-x"></i> Limpar</a>
            </div>
            <div class="cc-chips">
                <span>Situação:</span>
                <?php foreach ($chips as $chave => $cfg) { ?>
                    <label class="cc-chip">
                        <input class="form-check-input" type="checkbox" name="status[]" value="<?= $e($chave) ?>"<?= in_array($chave, $selecionados, true) ? ' checked' : '' ?> />
                        <span class="ui-selo <?= $e($cfg['classe']) ?>"><?= $e($cfg['rotulo']) ?> (<?= (int) ($kpis[$chave]['qtd'] ?? 0) ?>)</span>
                    </label>
                <?php } ?>
            </div>
        </form>

        <div class="ui-kpis">
            <?php foreach ($chips as $chave => $cfg) { ?>
                <div class="ui-kpi <?= $e($cfg['classe']) ?>">
                    <div class="ui-kpi-rotulo"><?= $e($cfg['rotulo']) ?></div>
                    <div class="ui-kpi-valor"><?= $brl($kpis[$chave]['valor'] ?? 0) ?></div>
                    <div class="ui-kpi-extra"><?= (int) ($kpis[$chave]['qtd'] ?? 0) ?> item(ns)</div>
                </div>
            <?php } ?>
            <div class="ui-kpi cc-destaque">
                <div class="ui-kpi-rotulo">Em aberto</div>
                <div class="ui-kpi-valor"><?= $brl($emAberto) ?></div>
                <div class="ui-kpi-extra">Aguardando aprovação, a cobrar, a receber e vencidas</div>
            </div>
        </div>

        <div class="cc-graficos">
            <?php if ($pontosStatus) { ?>
                <figure class="cc-fig">
                    <?= Grafico_svg::colunas($pontosStatus, [
                        'titulo' => 'Valor por situação',
                        'descricao' => 'Colunas com o valor em cada situação de cobrança, no recorte filtrado.',
                        'decimais' => 2,
                    ]) ?>
                </figure>
            <?php } ?>
            <?php if ($topClientes) { ?>
                <figure class="cc-fig">
                    <?= Grafico_svg::barrasHorizontais($topClientes, [
                        'titulo' => 'Maiores saldos em aberto',
                        'descricao' => 'Barras horizontais com os clientes de maior valor em aberto.',
                        'decimais' => 2,
                    ]) ?>
                </figure>
            <?php } ?>
        </div>

        <?php if (! $itens) { ?>
            <div class="widget-box">
                <div class="ui-vazio"><i class="bx bx-check-circle"></i><strong>Nada pendente com estes filtros</strong>Ajuste o cliente, o chamado ou as situações.</div>
            </div>
        <?php } else { ?>
            <form method="post" action="<?= site_url('chamadoscobranca/cobrancasAcao') ?>" id="form-lote" class="widget-box" style="padding:0">
                <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>" />

                <div class="cc-lote" id="cc-lote" hidden>
                    <strong><span id="cc-qtd">0</span> selecionado(s)</strong>
                    <?php if ($podeAprovar) { ?>
                        <button class="button btn btn-sm btn-primary" type="submit" formaction="<?= site_url('chamadoscobranca/solicitarAprovacao') ?>"><span class="button__icon"><i class="bx bx-check-shield"></i></span><span class="button__text">Solicitar aprovação</span></button>
                    <?php } ?>
                    <?php if ($podeCobranca) { ?>
                        <button class="button btn btn-sm btn-success" type="submit" name="acao" value="confirmar"><span class="button__icon"><i class="bx bx-check"></i></span><span class="button__text">Confirmar pagamento</span></button>
                        <button class="button btn btn-sm btn-danger" type="submit" name="acao" value="cancelar"><span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text">Cancelar cobrança</span></button>
                    <?php } ?>
                </div>

                <div class="widget-content nopadding ui-tabela-rolagem">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th class="cc-col-sel"><input class="form-check-input" type="checkbox" id="cc-todos" title="Selecionar todas" aria-label="Selecionar todas as linhas"></th>
                                <th>Chamado</th>
                                <th>OS / Cobrança</th>
                                <th>Cliente</th>
                                <th>Data</th>
                                <th class="cc-num">Valor</th>
                                <th>Situação</th>
                                <th>Detalhe</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $i) { ?>
                                <tr>
                                    <td class="cc-col-sel"><?php if (in_array($i['tipo'], ['os', 'cob'], true)) { ?><input class="form-check-input" type="checkbox" name="selecionados[]" value="<?= $e($i['chave']) ?>" aria-label="Selecionar <?= $e($i['tipo'] === 'cob' ? 'cobrança #' . (int) $i['ref_id'] : 'OS #' . (int) $i['ref_id']) ?>"><?php } ?></td>
                                    <td><?= $i['chamado'] ? $e($i['chamado']) : '—' ?></td>
                                    <td><a href="<?= site_url($i['link']) ?>"><?= $i['tipo'] === 'cob' ? 'Cobrança #' . (int) $i['ref_id'] : 'OS #' . (int) $i['ref_id'] ?></a></td>
                                    <td><?= $e($i['cliente']) ?></td>
                                    <td><?= $data($i['data_ref']) ?></td>
                                    <td class="cc-num"><strong><?= $brl($i['valor']) ?></strong></td>
                                    <td><span class="ui-selo <?= $e(Chamado_cobranca::classe($i['status'])) ?>"><?= $e(Chamado_cobranca::rotulo($i['status'])) ?></span></td>
                                    <td><?= $e($i['detalhe']) ?></td>
                                    <td>
                                        <?php if ($i['tipo'] === 'os' && $podeAprovar) { ?>
                                            <button class="button btn btn-sm btn-info" type="submit" formaction="<?= site_url('chamadoscobranca/solicitarAprovacao') ?>" name="selecionados[]" value="<?= $e($i['chave']) ?>"><span class="button__icon"><i class="bx bx-check-shield"></i></span><span class="button__text">Aprovação</span></button>
                                        <?php } elseif ($i['tipo'] === 'cob') { ?>
                                            <a class="btn btn-sm btn-light" href="<?= site_url($i['link']) ?>" title="Ver cobrança"><i class="bx bx-show"></i></a>
                                        <?php } else { ?>
                                            <a class="btn btn-sm btn-light" href="<?= site_url($i['link']) ?>" title="Abrir documento"><i class="bx bx-show"></i></a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </form>
        <?php } ?>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/grafico-svg.js') ?>?v=<?= asset_versao('assets/js/grafico-svg.js') ?>"></script>
<script nonce="<?= csp_nonce() ?>">
(function () {
    'use strict';
    var todos = document.getElementById('cc-todos');
    var barra = document.getElementById('cc-lote');
    var qtd = document.getElementById('cc-qtd');
    if (!todos || !barra) { return; }

    function marcadas() {
        return document.querySelectorAll('input[name="selecionados[]"]:checked');
    }

    function atualizar() {
        var n = marcadas().length;
        qtd.textContent = String(n);
        barra.hidden = n === 0;
    }

    todos.addEventListener('change', function () {
        document.querySelectorAll('input[name="selecionados[]"]').forEach(function (c) {
            c.checked = todos.checked;
        });
        atualizar();
    });

    document.addEventListener('change', function (ev) {
        if (ev.target && ev.target.name === 'selecionados[]') { atualizar(); }
    });

    // O botao de CSV precisa levar os filtros atuais, inclusive os chips marcados.
    var csv = document.querySelector('[data-csv]');
    if (csv) {
        csv.addEventListener('click', function (ev) {
            ev.preventDefault();
            var p = new URLSearchParams(window.location.search);
            window.location = csv.getAttribute('href').split('?')[0] + (p.toString() ? '?' + p.toString() : '');
        });
    }
})();
</script>
