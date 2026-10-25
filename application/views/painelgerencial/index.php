<?php
// Painel gerencial - ADR-008
$moeda = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$num = fn ($v, $d = 1, $suf = '') => $v === null ? '—' : number_format((float) $v, $d, ',', '.') . $suf;
$maxTec = $ind['porTecnico'] ? max(array_column($ind['porTecnico'], 'valor')) : 0;
$maxSrv = $ind['topServicos'] ? max(array_column($ind['topServicos'], 'valor')) : 0;
$kpis = [
    ['OS abertas', (int) $ind['abertas'], 'info'],
    ['OS concluídas', (int) $ind['concluidas'], 'sucesso'],
    ['Tempo médio de atendimento', $ind['checkin'] ? $num($ind['tempoAtendimentoH'], 1, ' h') : 'sem check-in', ''],
    ['Abertura → conclusão', $num($ind['tempoConclusaoDias'], 1, ' dias'), ''],
    ['Retrabalho (30 dias)', $num($ind['retrabalhoPct'], 1, '%') . ($ind['retrabalhoQtd'] ? ' (' . (int) $ind['retrabalhoQtd'] . ')' : ''), 'alerta'],
    ['Ticket médio', $ind['ticketMedio'] === null ? '—' : $moeda($ind['ticketMedio']), ''],
];
?>

<div class="row-fluid" style="margin-top: 0">
    <div class="span12">
        <div class="ui-cabecalho">
            <div>
                <h1><i class="bx bx-line-chart"></i> Painel gerencial</h1>
                <p>Indicadores de atendimento e faturamento das ordens de serviço no período escolhido.</p>
            </div>
        </div>

        <form action="<?= site_url('painelgerencial') ?>" method="get" class="ui-filtros">
            <div class="ui-campo">
                <label for="dataInicial">De</label>
                <input type="date" id="dataInicial" name="dataInicial" value="<?= html_escape($filtro['dataInicial']) ?>" />
            </div>
            <div class="ui-campo">
                <label for="dataFinal">Até</label>
                <input type="date" id="dataFinal" name="dataFinal" value="<?= html_escape($filtro['dataFinal']) ?>" />
            </div>
            <div class="ui-campo ui-campo-acao">
                <button class="button btn btn-inverse"><span class="button__icon"><i class="bx bx-filter-alt"></i></span><span class="button__text">Filtrar</span></button>
            </div>
        </form>

        <div class="ui-kpis">
            <?php foreach ($kpis as $k) : ?>
                <div class="ui-kpi <?= $k[2] ?>">
                    <div class="ui-kpi-rotulo"><?= html_escape($k[0]) ?></div>
                    <div class="ui-kpi-valor"><?= html_escape($k[1]) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row-fluid">
            <div class="span6">
                <div class="widget-box">
                    <div class="widget-title"><span class="icon"><i class="bx bx-user"></i></span><h5>Faturamento por técnico</h5></div>
                    <div class="widget-content">
                        <p class="muted">Total no período: <strong><?= html_escape($moeda($ind['faturamento'])) ?></strong></p>
                        <?php if (! $ind['porTecnico']) : ?>
                            <div class="ui-vazio"><i class="bx bx-user-x"></i><strong>Sem OS concluídas no período</strong></div>
                        <?php endif; ?>
                        <?php foreach ($ind['porTecnico'] as $t) : ?>
                            <div style="margin-bottom:10px">
                                <?= html_escape($t['tecnico']) ?> — <?= html_escape($moeda($t['valor'])) ?> (<?= (int) $t['qtd'] ?> OS)
                                <div class="ui-barra"><span style="width:<?= $maxTec > 0 ? round($t['valor'] * 100 / $maxTec) : 0 ?>%"></span></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="span6">
                <div class="widget-box">
                    <div class="widget-title"><span class="icon"><i class="bx bx-trophy"></i></span><h5>Top 10 serviços</h5></div>
                    <div class="widget-content">
                        <?php if (! $ind['topServicos']) : ?>
                            <div class="ui-vazio"><i class="bx bx-wrench"></i><strong>Sem serviços no período</strong></div>
                        <?php endif; ?>
                        <?php foreach ($ind['topServicos'] as $s) : ?>
                            <div style="margin-bottom:10px">
                                <?= html_escape($s['nome'] ?: 'Serviço removido') ?> — <?= html_escape($num($s['qtd'], 0)) ?> un. — <?= html_escape($moeda($s['valor'])) ?>
                                <div class="ui-barra"><span style="width:<?= $maxSrv > 0 ? round($s['valor'] * 100 / $maxSrv) : 0 ?>%"></span></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
