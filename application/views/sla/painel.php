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

    return '<span class="badge" style="background:' . $cor . '">' . $texto . '</span>';
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
<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="bx bx-timer"></i></span>
        <h5>Painel de SLA</h5>
    </div>
    <div class="widget-content">
        <?php if (! $disponivel) { ?>
            <div class="alert">Os campos de SLA ainda não existem no banco. Rode as migrations (Configurações &gt; Atualizar banco de dados).</div>
        <?php } ?>

        <div class="row-fluid" style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:14px">
            <?php foreach ([
                [null, 'OS abertas com SLA', $kpis['abertas'], '#34495e'],
                ['estourado', 'Estourados', $kpis['estourado'], '#c0392b'],
                ['risco', 'Vencem em até ' . Sla_model::HORAS_RISCO . 'h', $kpis['risco'], '#e67e22'],
            ] as [$sit, $titulo, $valor, $cor]) { ?>
                <a href="<?= $link($sit) ?>" style="flex:1; min-width:170px; text-decoration:none; border:1px solid #e2e6f0; border-left:4px solid <?= $cor ?>; border-radius:8px; padding:12px 14px; background:<?= $filtros['situacao'] === $sit ? '#f4f6fb' : '#fff' ?>">
                    <div style="color:#777; font-size:12px"><?= $titulo ?></div>
                    <div style="font-size:26px; font-weight:700; color:<?= $cor ?>"><?= (int) $valor ?></div>
                </a>
            <?php } ?>
            <div style="flex:1; min-width:170px; border:1px solid #e2e6f0; border-left:4px solid #27ae60; border-radius:8px; padding:12px 14px">
                <div style="color:#777; font-size:12px">Solução no prazo (mês)</div>
                <div style="font-size:26px; font-weight:700; color:#27ae60"><?= $kpis['percentual_mes'] === null ? '-' : $kpis['percentual_mes'] . '%' ?></div>
                <div style="color:#999; font-size:11px"><?= (int) $kpis['cumpridas_mes'] ?> de <?= (int) $kpis['solucionadas_mes'] ?> OS solucionadas</div>
            </div>
        </div>

        <form method="get" action="<?= site_url('sla') ?>" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-bottom:10px">
            <div><label>Técnico</label>
                <select name="tecnico" style="width:180px"><option value="">Todos</option>
                    <?php foreach ($tecnicos as $t) { ?><option value="<?= (int) $t->idUsuarios ?>" <?= (int) $filtros['tecnico'] === (int) $t->idUsuarios ? 'selected' : '' ?>><?= html_escape($t->nome) ?></option><?php } ?>
                </select></div>
            <div><label>Contrato</label>
                <select name="contrato" style="width:200px"><option value="">Todos</option>
                    <?php foreach ($contratos as $c) { ?><option value="<?= (int) $c->id ?>" <?= (int) $filtros['contrato'] === (int) $c->id ? 'selected' : '' ?>><?= html_escape(trim(($c->codigo ? $c->codigo . ' - ' : '') . $c->descricao)) ?></option><?php } ?>
                </select></div>
            <div><label>Prioridade</label>
                <select name="prioridade" style="width:130px"><option value="">Todas</option>
                    <?php foreach ($prioridades as $p) { ?><option <?= $filtros['prioridade'] === $p ? 'selected' : '' ?>><?= html_escape($p) ?></option><?php } ?>
                </select></div>
            <?php if ($filtros['situacao']) { ?><input type="hidden" name="situacao" value="<?= html_escape($filtros['situacao']) ?>"><?php } ?>
            <div><button class="button btn btn-mini btn-warning" type="submit"><span class="button__icon"><i class="bx bx-search-alt"></i></span><span class="button__text2">Filtrar</span></button></div>
            <div><a href="<?= site_url('agenda') ?>" class="button btn btn-mini btn-info"><span class="button__icon"><i class="bx bx-calendar"></i></span><span class="button__text2">Agenda da equipe</span></a></div>
        </form>

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
                    <tr><td colspan="9">Nenhuma OS aberta com SLA <?= $filtros['situacao'] ? 'nesta situação' : '' ?>. O SLA é calculado quando a OS tem contrato e prioridade.</td></tr>
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
