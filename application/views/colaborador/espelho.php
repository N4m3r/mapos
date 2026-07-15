<?php
$this->load->view('colaborador/_topo', [
    'titulo' => 'Meu Espelho',
    'header_icone' => 'bx-calendar-check',
    'header_sub' => 'Competência ' . date('m/Y', strtotime($competencia . '-01')),
    'voltar_url' => site_url('colaborador'),
]);
$diasSemana = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$lblBat = [
    'entrada' => 'Entrada',
    'saida' => 'Saída',
    'inicio_intervalo' => 'Início int.',
    'fim_intervalo' => 'Fim int.',
];
$corBat = [
    'entrada' => '#166534',
    'saida' => '#991b1b',
    'inicio_intervalo' => '#92400e',
    'fim_intervalo' => '#1e40af',
];
?>
<div class="ponto-wrap">
    <form method="get" action="<?= site_url('colaborador/espelho') ?>" style="margin-bottom:12px" onsubmit="return irCompetencia(event)">
        <input type="month" id="competencia" value="<?= $competencia ?>" class="span12" style="width:100%">
    </form>

    <div class="espelho-tot">
        <div class="box"><div class="k">Trabalhadas</div><div class="v"><?= $calc->minParaHoras($totais['minutos_trabalhados'] ?? 0) ?></div></div>
        <div class="box"><div class="k">Previstas</div><div class="v"><?= $calc->minParaHoras($totais['minutos_previstos'] ?? 0) ?></div></div>
        <div class="box"><div class="k">Extra 50%</div><div class="v"><?= $calc->minParaHoras($totais['minutos_extras_50'] ?? 0) ?></div></div>
        <div class="box"><div class="k">Extra 100%</div><div class="v"><?= $calc->minParaHoras($totais['minutos_extras_100'] ?? 0) ?></div></div>
        <div class="box"><div class="k">Faltas</div><div class="v"><?= $calc->minParaHoras($totais['minutos_faltas'] ?? 0) ?></div></div>
        <div class="box"><div class="k">Saldo banco</div>
            <div class="v" style="color:<?= ($totais['saldo_banco_min'] ?? 0) < 0 ? '#ef4444':'#10b981' ?>"><?= $calc->minParaHoras($totais['saldo_banco_min'] ?? 0) ?></div></div>
    </div>

    <div style="font-size:11px;color:#9ca3af;margin-bottom:8px;text-align:center">
        <span style="color:<?= $corBat['entrada'] ?>">E</span> Entrada ·
        <span style="color:<?= $corBat['inicio_intervalo'] ?>">II</span> Início int. ·
        <span style="color:<?= $corBat['fim_intervalo'] ?>">FI</span> Fim int. ·
        <span style="color:<?= $corBat['saida'] ?>">S</span> Saída · 📍 local
    </div>

    <div style="overflow-x:auto">
    <table class="espelho-tab">
        <thead><tr><th>Dia</th><th>Batidas</th><th>Trab.</th><th>Saldo</th></tr></thead>
        <tbody>
        <?php foreach ($linhas as $l):
            $cls = ! $l['eh_util'] ? 'folga' : ($l['calc']['falta'] > 0 ? 'falta' : '');
            $d = (int) substr($l['data'], 8, 2); ?>
            <tr class="<?= $cls ?>">
                <td><?= sprintf('%02d', $d) ?><br><small><?= $diasSemana[$l['dia_semana']] ?></small></td>
                <td style="text-align:left;font-size:12px">
                    <?php if (empty($l['batidas'])): ?>—
                    <?php else: foreach ($l['batidas'] as $b):
                        $cor = $corBat[$b->tipo] ?? '#374151';
                        $lab = $lblBat[$b->tipo] ?? $b->tipo; ?>
                        <div style="margin:3px 0;padding:2px 0;border-left:3px solid <?= $cor ?>;padding-left:6px">
                            <strong style="color:<?= $cor ?>"><?= $lab ?></strong>
                            <?= date('H:i', strtotime($b->data_hora)) ?>
                            <?php if (! empty($b->latitude) && ! empty($b->longitude)): ?>
                                <a href="https://www.google.com/maps?q=<?= rawurlencode($b->latitude . ',' . $b->longitude) ?>"
                                   target="_blank" rel="noopener" style="color:#2563eb;margin-left:2px" title="Ver local">📍</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; endif; ?>
                    <?php if (strtotime($l['data']) <= strtotime(date('Y-m-d'))): ?>
                        <a href="<?= site_url('colaborador/ocorrencias') ?>?ref=<?= $l['data'] ?>" title="Justificar/corrigir este dia" style="color:#c3c9d4;font-size:12px"><i class='bx bx-edit'></i> justificar</a>
                    <?php endif; ?>
                </td>
                <td><?= $calc->minParaHoras($l['calc']['trabalhado']) ?></td>
                <td style="color:<?= $l['calc']['saldo'] < 0 ? '#ef4444':'#111827' ?>"><?= $calc->minParaHoras($l['calc']['saldo']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<script>
function irCompetencia(e){ e.preventDefault();
    var c = document.getElementById('competencia').value;
    if (c) window.location = '<?= site_url('colaborador/espelho') ?>/' + c;
    return false;
}
document.getElementById('competencia').addEventListener('change', function(){
    window.location = '<?= site_url('colaborador/espelho') ?>/' + this.value;
});
</script>
<?php $this->load->view('colaborador/_nav', ['nav_ativo' => 'espelho', 'pode_bater_ponto' => $pode_bater_ponto]); ?>
</body>
</html>
