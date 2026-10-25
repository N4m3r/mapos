<?php
$rotulos = [
    'estourado' => ['Estourado', '#c0392b'],
    'risco' => ['Em risco', '#e67e22'],
    'no_prazo' => ['No prazo', '#27ae60'],
    'cumprido' => ['Cumprido', '#7f8c8d'],
    'atrasado' => ['Cumprido com atraso', '#8e44ad'],
];
$selo = static function ($situacao) use ($rotulos) {
    if ($situacao === null) {
        return '<span style="color:#aaa">-</span>';
    }
    [$texto, $cor] = $rotulos[$situacao];

    return '<span class="ui-selo" style="background:' . $cor . '; color:#fff">' . $texto . '</span>';
};
$falta = static function ($ts) {
    if ($ts === null) {
        return '-';
    }
    $seg = $ts - time();
    $abs = abs($seg);
    $txt = $abs >= 86400 ? floor($abs / 86400) . 'd ' . floor(($abs % 86400) / 3600) . 'h' : floor($abs / 3600) . 'h ' . floor(($abs % 3600) / 60) . 'min';

    return $seg < 0 ? '<span style="color:#c0392b">há ' . $txt . '</span>' : 'em ' . $txt;
};
$link = static function ($situacao) use ($filtros) {
    return site_url('sla') . '?' . http_build_query(array_filter(array_merge($filtros, ['situacao' => $situacao])));
};
?>
<div class="ui-cabecalho">
    <div>
        <h1 class="ui-titulo"><i class="bx bx-timer"></i> Painel de SLA</h1>
        <p class="ui-subtitulo">Prazos de resposta e solução das OS com contrato. Clique num indicador para filtrar a lista.</p>
    </div>
    <div class="ui-acoes">
        <a href="<?= site_url('agenda') ?>" class="button btn btn-info"><span class="button__icon"><i class="bx bx-calendar"></i></span><span class="button__text2">Agenda da equipe</span></a>
    </div>
</div>
<div class="widget-box">
    <div class="widget-content">
        <?php if (! $disponivel) { ?>
            <div class="ui-dica"><i class="bx bx-error-circle"></i><div>Os campos de SLA ainda não existem no banco. Rode as migrations (Configurações &gt; Atualizar banco de dados).</div></div>
        <?php } ?>

        <div class="ui-kpis">
            <?php foreach ([
                [null, 'OS abertas com SLA', $kpis['abertas'], ''],
                ['estourado', 'Estourados', $kpis['estourado'], 'perigo'],
                ['risco', 'Vencem em até ' . Sla_model::HORAS_RISCO . 'h', $kpis['risco'], 'alerta'],
            ] as [$sit, $titulo, $valor, $tom]) { ?>
                <a href="<?= $link($sit) ?>" class="ui-kpi <?= $tom ?>" style="text-decoration:none; color:inherit; background:<?= $filtros['situacao'] === $sit ? 'var(--ui-suave)' : 'transparent' ?>">
                    <div class="ui-kpi-rotulo"><?= $titulo ?></div>
                    <div class="ui-kpi-valor"><?= (int) $valor ?></div>
                </a>
            <?php } ?>
            <div class="ui-kpi sucesso">
                <div class="ui-kpi-rotulo">Solução no prazo (mês)</div>
                <div class="ui-kpi-valor"><?= $kpis['percentual_mes'] === null ? '-' : $kpis['percentual_mes'] . '%' ?></div>
                <div class="ui-kpi-extra"><?= (int) $kpis['cumpridas_mes'] ?> de <?= (int) $kpis['solucionadas_mes'] ?> OS solucionadas</div>
            </div>
        </div>

        <form method="get" action="<?= site_url('sla') ?>" class="ui-filtros">
            <div class="ui-campo"><label>Técnico</label>
                <select name="tecnico"><option value="">Todos</option>
                    <?php foreach ($tecnicos as $t) { ?><option value="<?= (int) $t->idUsuarios ?>" <?= (int) $filtros['tecnico'] === (int) $t->idUsuarios ? 'selected' : '' ?>><?= html_escape($t->nome) ?></option><?php } ?>
                </select></div>
            <div class="ui-campo"><label>Contrato</label>
                <select name="contrato"><option value="">Todos</option>
                    <?php foreach ($contratos as $c) { ?><option value="<?= (int) $c->id ?>" <?= (int) $filtros['contrato'] === (int) $c->id ? 'selected' : '' ?>><?= html_escape(trim(($c->codigo ? $c->codigo . ' - ' : '') . $c->descricao)) ?></option><?php } ?>
                </select></div>
            <div class="ui-campo"><label>Prioridade</label>
                <select name="prioridade"><option value="">Todas</option>
                    <?php foreach ($prioridades as $p) { ?><option <?= $filtros['prioridade'] === $p ? 'selected' : '' ?>><?= html_escape($p) ?></option><?php } ?>
                </select></div>
            <?php if ($filtros['situacao']) { ?><input type="hidden" name="situacao" value="<?= html_escape($filtros['situacao']) ?>"><?php } ?>
            <div class="ui-campo-acao"><button class="button btn btn-warning" type="submit"><span class="button__icon"><i class="bx bx-search-alt"></i></span><span class="button__text2">Filtrar</span></button></div>
        </form>

        <div class="ui-tabela-rolagem">
        <table class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th style="width:60px">OS</th>
                    <th>Cliente</th>
                    <th style="width:90px">Prioridade</th>
                    <th>Contrato</th>
                    <th style="width:140px">Técnico</th>
                    <th style="width:150px">Resposta</th>
                    <th style="width:150px">Solução</th>
                    <th style="width:110px">Próximo prazo</th>
                    <th style="width:110px; text-align:center">Situação</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lista)) { ?>
                    <tr><td colspan="9"><div class="ui-vazio"><i class="bx bx-check-shield"></i><strong>Nenhuma OS aberta com SLA<?= $filtros['situacao'] ? ' nesta situação' : '' ?></strong>O SLA é calculado quando a OS tem contrato e prioridade.</div></td></tr>
                <?php }
                foreach ($lista as $os) { ?>
                    <tr>
                        <td><a href="<?= site_url('os/visualizar/' . (int) $os->idOs) ?>">#<?= (int) $os->idOs ?></a></td>
                        <td><?= html_escape($os->nomeCliente) ?><br><small style="color:#999"><?= html_escape($os->status) ?></small></td>
                        <td><?= html_escape($os->prioridade ?: '-') ?></td>
                        <td><?= html_escape(trim(($os->contrato_codigo ? $os->contrato_codigo . ' - ' : '') . $os->contrato)) ?: '-' ?></td>
                        <td><?= html_escape($os->tecnico ?: '-') ?></td>
                        <td><?= $os->sla_resposta_prazo ? date('d/m H:i', strtotime($os->sla_resposta_prazo)) . '<br>' . $selo($os->situacao_resposta) : '-' ?></td>
                        <td><?= $os->sla_solucao_prazo ? date('d/m H:i', strtotime($os->sla_solucao_prazo)) . '<br>' . $selo($os->situacao_solucao) : '-' ?></td>
                        <td><?= $falta($os->proximo_prazo) ?></td>
                        <td style="text-align:center"><?= $selo($os->situacao) ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
