<?php
// Custo real e margem por OS - ADR-008
$m = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$p = fn ($v) => $v === null ? '—' : number_format((float) $v, 1, ',', '.') . '%';
$h = fn ($v) => number_format((float) $v, 2, ',', '.') . ' h';
$cor = fn ($v) => $v < 0 ? 'color:#d9534f' : 'color:#2e9e5b';
$t = $res['totais'];
?>
<div class="row-fluid" style="margin-top: 0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon"><i class="bx bx-line-chart"></i></span>
                <h5>Custo real e margem por OS</h5>
            </div>
            <div class="widget-content">
                <form action="<?= site_url('margem') ?>" method="get" class="span12 well" style="margin-left:0">
                    <div class="span3">
                        <label>OS concluídas de:</label>
                        <input type="date" name="dataInicial" class="span12" value="<?= html_escape($filtro['dataInicial']) ?>" />
                    </div>
                    <div class="span3">
                        <label>até:</label>
                        <input type="date" name="dataFinal" class="span12" value="<?= html_escape($filtro['dataFinal']) ?>" />
                    </div>
                    <div class="span3">
                        <label>&nbsp;</label>
                        <button class="btn btn-info"><i class="bx bx-search-alt"></i> Filtrar</button>
                    </div>
                </form>

                <?php if ($podeConfigurar) : ?>
                    <form action="<?= site_url('margem/salvarCustoHora') ?>" method="post" class="span12 well" style="margin-left:0">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                        <div class="span3">
                            <label>Custo-hora do técnico (R$):</label>
                            <input type="text" name="custo_hora_tecnico" class="span12" value="<?= html_escape(number_format($custoHora, 2, '.', '')) ?>" />
                        </div>
                        <div class="span3">
                            <label>&nbsp;</label>
                            <button class="btn btn-success"><i class="bx bx-save"></i> Salvar</button>
                        </div>
                    </form>
                <?php else : ?>
                    <p class="span12" style="margin-left:0">Custo-hora do técnico: <strong><?= html_escape($m($custoHora)) ?></strong></p>
                <?php endif; ?>

                <?php if ($custoHora <= 0) : ?>
                    <div class="alert alert-info span12" style="margin-left:0">Custo-hora não configurado: o custo de mão de obra está zerado.</div>
                <?php endif; ?>
                <?php if (! $res['tem_checkin']) : ?>
                    <div class="alert span12" style="margin-left:0">Tabela de check-in inexistente: horas não contabilizadas.</div>
                <?php endif; ?>
                <?php if ($res['avisos']['os_pecas_sem_custo'] > 0) : ?>
                    <div class="alert span12" style="margin-left:0"><?= (int) $res['avisos']['os_pecas_sem_custo'] ?> OS com peças sem preço de compra (custo subestimado).</div>
                <?php endif; ?>
                <?php if ($res['avisos']['os_sem_checkin'] > 0) : ?>
                    <div class="alert span12" style="margin-left:0"><?= (int) $res['avisos']['os_sem_checkin'] ?> OS sem check-in concluído (mão de obra não contabilizada).</div>
                <?php endif; ?>

                <table class="table table-bordered">
                    <thead><tr><th>OS</th><th>Receita</th><th>Custo peças</th><th>Horas</th><th>Custo MO</th><th>Margem</th><th>Margem %</th></tr></thead>
                    <tbody><tr>
                        <td><?= (int) $t['os'] ?></td>
                        <td><?= html_escape($m($t['receita'])) ?></td>
                        <td><?= html_escape($m($t['custo_pecas'])) ?></td>
                        <td><?= html_escape($h($t['horas'])) ?></td>
                        <td><?= html_escape($m($t['custo_mo'])) ?></td>
                        <td><strong style="<?= $cor($t['margem']) ?>"><?= html_escape($m($t['margem'])) ?></strong></td>
                        <td><strong><?= html_escape($p($t['margem_pct'])) ?></strong></td>
                    </tr></tbody>
                </table>

                <?php foreach ([['Por técnico', $res['tecnicos']], ['Por cliente', $res['clientes']]] as [$titulo, $grupo]) : ?>
                    <h5><?= html_escape($titulo) ?></h5>
                    <table class="table table-bordered">
                        <thead><tr><th>Nome</th><th>OS</th><th>Receita</th><th>Custo</th><th>Horas</th><th>Margem</th><th>Margem %</th></tr></thead>
                        <tbody>
                        <?php if (! $grupo) : ?>
                            <tr><td colspan="7">Nenhuma OS concluída no período.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($grupo as $g) : ?>
                            <tr>
                                <td><?= html_escape($g['nome'] ?: '—') ?></td>
                                <td><?= (int) $g['os'] ?></td>
                                <td><?= html_escape($m($g['receita'])) ?></td>
                                <td><?= html_escape($m($g['custo'])) ?></td>
                                <td><?= html_escape($h($g['horas'])) ?></td>
                                <td style="<?= $cor($g['margem']) ?>"><?= html_escape($m($g['margem'])) ?></td>
                                <td><?= html_escape($p($g['margem_pct'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>

                <h5>Por OS</h5>
                <table class="table table-bordered">
                    <thead><tr><th>OS</th><th>Conclusão</th><th>Cliente</th><th>Técnico</th><th>Receita</th><th>Peças</th><th>Horas</th><th>MO</th><th>Margem</th><th>%</th><th></th></tr></thead>
                    <tbody>
                    <?php if (! $res['os']) : ?>
                        <tr><td colspan="11">Nenhuma OS concluída no período.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($res['os'] as $o) : ?>
                        <tr>
                            <td><a href="<?= site_url('os/visualizar/' . (int) $o['id']) ?>">#<?= (int) $o['id'] ?></a></td>
                            <td><?= $o['dataFinal'] ? html_escape(date('d/m/Y', strtotime($o['dataFinal']))) : '—' ?></td>
                            <td><?= html_escape($o['cliente'] ?: '—') ?></td>
                            <td><?= html_escape($o['tecnico'] ?: '—') ?></td>
                            <td><?= html_escape($m($o['receita'])) ?></td>
                            <td><?= html_escape($m($o['custo_pecas'])) ?></td>
                            <td><?= html_escape($h($o['horas'])) ?></td>
                            <td><?= html_escape($m($o['custo_mo'])) ?></td>
                            <td style="<?= $cor($o['margem']) ?>"><?= html_escape($m($o['margem'])) ?></td>
                            <td><?= html_escape($p($o['margem_pct'])) ?></td>
                            <td>
                                <?php if ($o['pecas_sem_custo']) : ?><span class="label label-warning" title="Peça sem preço de compra">custo?</span><?php endif; ?>
                                <?php if ($o['sem_checkin']) : ?><span class="label" title="Sem check-in concluído">sem horas</span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
