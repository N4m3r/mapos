<?php
$diasSemana = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$fmt = function ($min) { return $this->rh_calculo->minParaHoras($min); };
$emp = $emitente ?? null;
?>
<style>
    body { font-family: sans-serif; font-size: 11px; color:#222; }
    h2 { margin:0; font-size:16px; }
    .cab { border-bottom:2px solid #333; padding-bottom:6px; margin-bottom:10px; }
    table { width:100%; border-collapse:collapse; }
    th, td { border:1px solid #999; padding:3px 5px; text-align:center; }
    th { background:#eee; }
    tr.folga td { background:#f6f6f6; color:#888; }
    tr.falta td { background:#fdecec; }
    .tot { margin:10px 0; }
    .tot td { border:none; text-align:left; padding:2px 8px; }
    .assinaturas { margin-top:40px; width:100%; }
    .assinaturas td { border:none; border-top:1px solid #333; text-align:center; padding-top:4px; width:45%; }
</style>

<div class="cab">
    <table style="border:none"><tr>
        <td style="border:none;text-align:left"><h2><?= htmlspecialchars($emp->nome ?? 'Empresa') ?></h2>
            <?php if (! empty($emp->cnpj)): ?><div>CNPJ: <?= htmlspecialchars($emp->cnpj) ?></div><?php endif; ?></td>
        <td style="border:none;text-align:right">
            <strong>ESPELHO DE PONTO</strong><br>
            Competência: <?= date('m/Y', strtotime($competencia.'-01')) ?>
        </td>
    </tr></table>
</div>

<div><strong>Colaborador:</strong> <?= htmlspecialchars($colaborador->nome) ?>
    &nbsp; <strong>Cargo:</strong> <?= htmlspecialchars($colaborador->cargo ?: '-') ?>
    <?php if (! empty($jornada)): ?>&nbsp; <strong>Jornada:</strong> <?= htmlspecialchars($jornada->nome) ?><?php endif; ?>
</div>

<?php
$lblBat = ['entrada'=>'E','saida'=>'S','inicio_intervalo'=>'II','fim_intervalo'=>'FI'];
?>
<table style="margin-top:8px">
    <thead><tr><th>Dia</th><th>Sem.</th><th>Batidas (tipo/hora/local)</th><th>Trab.</th><th>Extra</th><th>Falta</th><th>Saldo</th></tr></thead>
    <tbody>
    <?php foreach ($linhas as $l):
        $cls = ! $l['eh_util'] ? 'folga' : ($l['calc']['falta'] > 0 ? 'falta' : ''); ?>
        <tr class="<?= $cls ?>">
            <td><?= (int) substr($l['data'],8,2) ?></td>
            <td><?= $diasSemana[$l['dia_semana']] ?></td>
            <td style="text-align:left;font-size:9px"><?php
                if (empty($l['batidas'])) {
                    echo '—';
                } else {
                    $parts = [];
                    foreach ($l['batidas'] as $b) {
                        $t = $lblBat[$b->tipo] ?? $b->tipo;
                        $p = $t . ' ' . date('H:i', strtotime($b->data_hora));
                        if (! empty($b->latitude) && ! empty($b->longitude)) {
                            $p .= ' [' . round((float)$b->latitude, 4) . ',' . round((float)$b->longitude, 4) . ']';
                        }
                        $parts[] = $p;
                    }
                    echo htmlspecialchars(implode(' · ', $parts));
                }
            ?></td>
            <td><?= $fmt($l['calc']['trabalhado']) ?></td>
            <td><?= $fmt($l['calc']['extra50'] + $l['calc']['extra100']) ?></td>
            <td><?= $fmt($l['calc']['falta']) ?></td>
            <td><?= $fmt($l['calc']['saldo']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div style="font-size:9px;color:#666;margin-top:4px">Legenda: E=Entrada · II=Início intervalo · FI=Fim intervalo · S=Saída · [lat,lng]=local GPS</div>

<table class="tot">
    <tr>
        <td><strong>Trabalhadas:</strong> <?= $fmt($totais['minutos_trabalhados'] ?? 0) ?></td>
        <td><strong>Previstas:</strong> <?= $fmt($totais['minutos_previstos'] ?? 0) ?></td>
        <td><strong>Extra 50%:</strong> <?= $fmt($totais['minutos_extras_50'] ?? 0) ?></td>
        <td><strong>Extra 100%:</strong> <?= $fmt($totais['minutos_extras_100'] ?? 0) ?></td>
    </tr>
    <tr>
        <td><strong>Faltas:</strong> <?= $fmt($totais['minutos_faltas'] ?? 0) ?></td>
        <td><strong>Dias trab.:</strong> <?= (int) ($totais['dias_trabalhados'] ?? 0) ?></td>
        <td colspan="2"><strong>Saldo banco:</strong> <?= $fmt($totais['saldo_banco_min'] ?? 0) ?></td>
    </tr>
</table>

<table class="assinaturas">
    <tr><td>Colaborador</td><td>Responsável / RH</td></tr>
</table>
<div style="text-align:right;margin-top:10px;font-size:9px;color:#888">Emitido em <?= date('d/m/Y H:i') ?></div>
