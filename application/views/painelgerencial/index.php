<?php
// Painel gerencial - ADR-008
$moeda = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$num = fn ($v, $d = 1, $suf = '') => $v === null ? '—' : number_format((float) $v, $d, ',', '.') . $suf;
$maxTec = $ind['porTecnico'] ? max(array_column($ind['porTecnico'], 'valor')) : 0;
$maxSrv = $ind['topServicos'] ? max(array_column($ind['topServicos'], 'valor')) : 0;
$kpis = [
    ['OS abertas', (int) $ind['abertas']],
    ['OS concluídas', (int) $ind['concluidas']],
    ['Tempo médio de atendimento', $ind['checkin'] ? $num($ind['tempoAtendimentoH'], 1, ' h') : 'sem check-in'],
    ['Abertura → conclusão', $num($ind['tempoConclusaoDias'], 1, ' dias')],
    ['Retrabalho (30 dias)', $num($ind['retrabalhoPct'], 1, '%') . ($ind['retrabalhoQtd'] ? ' (' . (int) $ind['retrabalhoQtd'] . ')' : '')],
    ['Ticket médio', $ind['ticketMedio'] === null ? '—' : $moeda($ind['ticketMedio'])],
];
?>

<div class="row-fluid" style="margin-top: 0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon"><i class="bx bx-line-chart"></i></span>
                <h5>Painel gerencial</h5>
            </div>
            <div class="widget-content">
                <form action="<?= site_url('painelgerencial') ?>" method="get" class="span12 well" style="margin-left:0">
                    <div class="span3">
                        <label>De:</label>
                        <input type="date" name="dataInicial" class="span12" value="<?= html_escape($filtro['dataInicial']) ?>" />
                    </div>
                    <div class="span3">
                        <label>até:</label>
                        <input type="date" name="dataFinal" class="span12" value="<?= html_escape($filtro['dataFinal']) ?>" />
                    </div>
                    <div class="span3">
                        <label>&nbsp;</label>
                        <button class="button btn btn-inverse"><span class="button__icon"><i class="bx bx-filter-alt"></i></span><span class="button__text">Filtrar</span></button>
                    </div>
                </form>

                <div class="span12" style="margin-left:0">
                    <?php foreach ($kpis as $k) : ?>
                        <div class="span2 well" style="text-align:center;margin-left:1%">
                            <div style="font-size:22px;font-weight:700"><?= html_escape($k[1]) ?></div>
                            <?= html_escape($k[0]) ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="span6" style="margin-left:0">
                    <h5>Faturamento por técnico (<?= html_escape($moeda($ind['faturamento'])) ?> no total)</h5>
                    <?php if (! $ind['porTecnico']) : ?>
                        <p class="muted">Sem OS concluídas no período.</p>
                    <?php endif; ?>
                    <?php foreach ($ind['porTecnico'] as $t) : ?>
                        <div style="margin-bottom:8px">
                            <?= html_escape($t['tecnico']) ?> — <?= html_escape($moeda($t['valor'])) ?> (<?= (int) $t['qtd'] ?> OS)
                            <div style="background:#e6e8eb;height:10px;border-radius:3px">
                                <div style="background:#2e9e5b;height:10px;border-radius:3px;width:<?= $maxTec > 0 ? round($t['valor'] * 100 / $maxTec) : 0 ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="span6">
                    <h5>Top 10 serviços</h5>
                    <?php if (! $ind['topServicos']) : ?>
                        <p class="muted">Sem serviços no período.</p>
                    <?php endif; ?>
                    <?php foreach ($ind['topServicos'] as $s) : ?>
                        <div style="margin-bottom:8px">
                            <?= html_escape($s['nome'] ?: 'Serviço removido') ?> — <?= html_escape($num($s['qtd'], 0)) ?> un. — <?= html_escape($moeda($s['valor'])) ?>
                            <div style="background:#e6e8eb;height:10px;border-radius:3px">
                                <div style="background:#3a7bd5;height:10px;border-radius:3px;width:<?= $maxSrv > 0 ? round($s['valor'] * 100 / $maxSrv) : 0 ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
