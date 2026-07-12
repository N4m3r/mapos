<?php
$this->load->view('colaborador/_topo', [
    'titulo' => 'Meu Espelho',
    'header_icone' => 'bx-calendar-check',
    'header_sub' => 'Competência ' . date('m/Y', strtotime($competencia . '-01')),
    'voltar_url' => site_url('colaborador'),
]);
$diasSemana = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$labels = ['entrada'=>'E','saida'=>'S','inicio_intervalo'=>'II','fim_intervalo'=>'FI'];
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

    <div style="overflow-x:auto">
    <table class="espelho-tab">
        <thead><tr><th>Dia</th><th>Batidas</th><th>Trab.</th><th>Saldo</th></tr></thead>
        <tbody>
        <?php foreach ($linhas as $l):
            $cls = ! $l['eh_util'] ? 'folga' : ($l['calc']['falta'] > 0 ? 'falta' : '');
            $d = (int) substr($l['data'], 8, 2); ?>
            <tr class="<?= $cls ?>">
                <td><?= sprintf('%02d', $d) ?><br><small><?= $diasSemana[$l['dia_semana']] ?></small></td>
                <td style="text-align:left">
                    <?php if (empty($l['batidas'])): ?>—<?php else: foreach ($l['batidas'] as $b):
                        echo '<span title="'.$b->tipo.'">'.date('H:i', strtotime($b->data_hora)).'</span> '; endforeach; endif; ?>
                    <?php if (strtotime($l['data']) <= strtotime(date('Y-m-d'))): ?>
                        <a href="<?= site_url('colaborador/ocorrencias') ?>?ref=<?= $l['data'] ?>" title="Solicitar correção deste dia" style="color:#c3c9d4;margin-left:4px"><i class='bx bx-edit'></i></a>
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
