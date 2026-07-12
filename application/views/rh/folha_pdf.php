<?php
$fmt = function ($min) { return $this->rh_calculo->minParaHoras($min); };
$brl = function ($v) { return number_format($v, 2, ',', '.'); };
$emp = $emitente ?? null;
$compLabel = date('m/Y', strtotime($competencia . '-01'));
?>
<style>
    body { font-family: sans-serif; font-size: 10px; color: #222; }
    .cab { border-bottom: 2px solid #333; padding-bottom: 6px; margin-bottom: 10px; }
    h2 { margin: 0; font-size: 15px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #999; padding: 3px 5px; }
    th { background: #eee; }
    td.num, th.num { text-align: right; }
    tfoot td { background: #f0f0f0; font-weight: bold; }
</style>

<div class="cab">
    <table style="border:none"><tr>
        <td style="border:none;text-align:left"><h2><?= htmlspecialchars($emp->nome ?? 'Empresa') ?></h2>
            <?php if (! empty($emp->cnpj)): ?><div>CNPJ: <?= htmlspecialchars($emp->cnpj) ?></div><?php endif; ?></td>
        <td style="border:none;text-align:right"><strong>FOLHA DE PAGAMENTO</strong><br>Competência: <?= $compLabel ?></td>
    </tr></table>
</div>

<table>
    <thead><tr>
        <th>Colaborador</th><th>Cargo</th><th class="num">Trab.</th><th class="num">Extras</th>
        <th class="num">Faltas</th><th class="num">Salário base</th><th class="num">Proventos</th>
        <th class="num">Descontos</th><th class="num">Líquido</th>
    </tr></thead>
    <tbody>
    <?php foreach ($linhas as $l): $c = $l['colaborador']; ?>
        <tr>
            <td><?= htmlspecialchars($c->nome) ?></td>
            <td><?= htmlspecialchars($c->cargo ?: '-') ?></td>
            <td class="num"><?= $fmt($l['horas']['minutos_trabalhados'] ?? 0) ?></td>
            <td class="num"><?= $fmt(($l['horas']['minutos_extras_50'] ?? 0) + ($l['horas']['minutos_extras_100'] ?? 0)) ?></td>
            <td class="num"><?= $fmt($l['horas']['minutos_faltas'] ?? 0) ?></td>
            <td class="num"><?= $brl($l['salario_base']) ?></td>
            <td class="num"><?= $brl($l['proventos']) ?></td>
            <td class="num"><?= $brl($l['descontos']) ?></td>
            <td class="num"><?= $brl($l['liquido']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr>
        <td colspan="2">TOTAIS (<?= count($linhas) ?> colaboradores)</td>
        <td class="num"><?= $fmt($tot['trab']) ?></td>
        <td class="num"><?= $fmt($tot['extras']) ?></td>
        <td class="num"><?= $fmt($tot['faltas']) ?></td>
        <td class="num"><?= $brl($tot['salario']) ?></td>
        <td class="num"><?= $brl($tot['proventos']) ?></td>
        <td class="num"><?= $brl($tot['descontos']) ?></td>
        <td class="num"><?= $brl($tot['liquido']) ?></td>
    </tr></tfoot>
</table>

<div style="margin-top:8px;font-size:9px;color:#666">
    Relatório gerencial — proventos = salário base + lançamentos aprovados. Não inclui encargos fiscais (INSS/IRRF/FGTS).
    Emitido em <?= date('d/m/Y H:i') ?>.
</div>
