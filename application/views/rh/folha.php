<?php
$this->load->view('rh/_subnav', ['ativo' => 'folha']);
$fmt = function ($min) { return $this->rh_calculo->minParaHoras($min); };
$brl = function ($v) { return 'R$ ' . number_format($v, 2, ',', '.'); };
$compLabel = date('m/Y', strtotime($competencia . '-01'));
?>
<div class="new122">
    <div class="widget-title" style="margin:0 0 10px">
        <span class="icon"><i class="fas fa-file-invoice-dollar"></i></span>
        <h5>Folha de Pagamento — <?= $compLabel ?></h5>
    </div>

    <div class="span12" style="margin-left:0;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <input type="month" id="competencia" value="<?= $competencia ?>">
        <a href="<?= site_url("rh/folhaPdf/{$competencia}") ?>" target="_blank" class="button btn btn-mini btn-inverse">
            <span class="button__icon"><i class='bx bx-printer'></i></span><span class="button__text2"> Folha em PDF</span></a>
    </div>

    <div class="widget-box"><div class="widget-content nopadding">
        <table class="table table-bordered">
            <thead><tr>
                <th>Colaborador</th><th>Trab.</th><th>Extras</th><th>Faltas</th>
                <th>Salário base</th><th>Proventos</th><th>Descontos</th><th>Líquido</th><th>Ações</th>
            </tr></thead>
            <tbody>
            <?php if (empty($linhas)): ?>
                <tr><td colspan="9">Nenhum colaborador ativo.</td></tr>
            <?php else: foreach ($linhas as $l): $c = $l['colaborador']; ?>
                <tr>
                    <td><a href="<?= site_url('rh/ficha/'.$c->id) ?>"><?= htmlspecialchars($c->nome) ?></a>
                        <br><small style="color:#9ca3af"><?= htmlspecialchars($c->cargo ?: '-') ?></small></td>
                    <td><?= $fmt($l['horas']['minutos_trabalhados'] ?? 0) ?></td>
                    <td><?= $fmt(($l['horas']['minutos_extras_50'] ?? 0) + ($l['horas']['minutos_extras_100'] ?? 0)) ?></td>
                    <td><?= $fmt($l['horas']['minutos_faltas'] ?? 0) ?></td>
                    <td><?= $brl($l['salario_base']) ?></td>
                    <td style="color:#16a34a"><?= $brl($l['proventos']) ?></td>
                    <td style="color:#dc2626"><?= $brl($l['descontos']) ?></td>
                    <td><strong><?= $brl($l['liquido']) ?></strong></td>
                    <td style="white-space:nowrap">
                        <a href="<?= site_url("rh/holeritePdf/{$c->id}/{$competencia}") ?>" target="_blank" class="btn-nwe3" title="Holerite em PDF"><i class="bx bx-receipt bx-xs"></i></a>
                        <a href="<?= site_url("rh/holerite/{$c->id}/{$competencia}") ?>" class="btn-nwe3" title="Gerenciar holerite"><i class="bx bx-edit bx-xs"></i></a>
                        <a href="<?= site_url("rh/espelho/{$c->id}/{$competencia}") ?>" class="btn-nwe3" title="Espelho"><i class="bx bx-calendar-check bx-xs"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
            <?php if (! empty($linhas)): ?>
            <tfoot><tr style="background:#f3f4f6;font-weight:700">
                <td>TOTAIS (<?= count($linhas) ?>)</td>
                <td><?= $fmt($tot['trab']) ?></td>
                <td><?= $fmt($tot['extras']) ?></td>
                <td><?= $fmt($tot['faltas']) ?></td>
                <td><?= $brl($tot['salario']) ?></td>
                <td><?= $brl($tot['proventos']) ?></td>
                <td><?= $brl($tot['descontos']) ?></td>
                <td><?= $brl($tot['liquido']) ?></td>
                <td></td>
            </tr></tfoot>
            <?php endif; ?>
        </table>
    </div></div>

    <p style="font-size:12px;color:#9ca3af">
        Relatório gerencial. Proventos = salário base + lançamentos aprovados; não inclui encargos fiscais (INSS/IRRF/FGTS), que ficam com a contabilidade.
    </p>
</div>
<script>
document.getElementById('competencia').addEventListener('change', function(){
    window.location = '<?= site_url('rh/folha') ?>/' + this.value;
});
</script>
