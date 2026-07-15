<?php
$diasSemana = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$fmt = function ($min) { return $this->rh_calculo->minParaHoras($min); };
$podeFin = $this->permission->checkPermission($this->session->userdata('permissao'), 'vRhFinanceiro');
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
<div class="new122">
    <?php $this->load->view('rh/_subnav', ['ativo' => 'colaboradores']); ?>
    <div class="widget-title" style="margin:0 0 10px">
        <span class="icon"><i class="fas fa-calendar-check"></i></span>
        <h5>Espelho de Ponto — <?= htmlspecialchars($colaborador->nome) ?></h5>
    </div>

    <div class="span12" style="margin-left:0;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <form method="get" onsubmit="return false" style="display:flex;gap:6px;align-items:center">
            <input type="month" id="competencia" value="<?= $competencia ?>">
            <span style="color:#888">competência</span>
        </form>
        <div style="display:flex;gap:6px">
            <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eRh')): ?>
                <a href="<?= site_url("rh/ajustarPonto/{$colaborador->id}/{$competencia}") ?>" class="button btn btn-mini btn-warning"><span class="button__icon"><i class='bx bx-edit-alt'></i></span><span class="button__text2"> Ajustar ponto</span></a>
                <a href="<?= site_url("rh/recalcular/{$colaborador->id}/{$competencia}") ?>" class="button btn btn-mini btn-primary" title="Recalcula horas e banco de horas"><span class="button__text2">Recalcular</span></a>
                <?php if ($podeFin): ?>
                    <a href="<?= site_url("rh/recalcular/{$colaborador->id}/{$competencia}?extras=1") ?>" class="button btn btn-mini btn-success"
                       onclick="return confirm('Gerar horas extras como lançamentos PENDENTES de aprovação?')"><span class="button__text2">Gerar extras (pendente)</span></a>
                <?php endif; ?>
            <?php endif; ?>
            <a href="<?= site_url("rh/espelhoPdf/{$colaborador->id}/{$competencia}") ?>" target="_blank" class="button btn btn-mini btn-inverse"><span class="button__text2">PDF</span></a>
        </div>
    </div>

    <?php if (empty($ponto_inicio)): ?>
        <div class="alert alert-info" style="margin:0 0 10px">
            <strong>Controle de ponto ainda não habilitado</strong> neste colaborador.
            Dias sem batida <em>não</em> geram falta nem horas negativas.
            Defina a data em <a href="<?= site_url('rh/editarColaborador/'.$colaborador->id) ?>">Editar colaborador → Início do controle de ponto</a>.
        </div>
    <?php else: ?>
        <div style="font-size:12px;color:#6b7280;margin:0 0 8px">
            Controle de ponto desde <strong><?= date('d/m/Y', strtotime($ponto_inicio)) ?></strong>
            · Desconto em R$ automático: <strong><?= ! empty($desconto_auto) ? 'ligado' : 'desligado' ?></strong>
            <?php if (empty($desconto_auto)): ?>
                <span style="color:#9ca3af">(faltas só no banco/espelho — ative em RH → Descontos CLT)</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="widget-box"><div class="widget-content">
        <?php $sem = $totais_semana ?? []; ?>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
            <div class="box" style="flex:1;min-width:160px;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
                <div class="k" style="font-size:11px;color:#6b7280;text-transform:uppercase">Semana (seg–dom)</div>
                <div style="font-size:13px;margin-top:4px">
                    Trab. <strong><?= $fmt($sem['minutos_trabalhados'] ?? 0) ?></strong>
                    · Deve <strong style="color:#dc2626"><?= $fmt($sem['minutos_faltas'] ?? 0) ?></strong>
                    · Saldo <strong style="color:<?= ($sem['saldo_banco_min']??0)<0?'#dc2626':'#16a34a' ?>"><?= $fmt($sem['saldo_banco_min'] ?? 0) ?></strong>
                </div>
                <?php if (! empty($sem['inicio'])): ?>
                    <div style="font-size:10px;color:#9ca3af;margin-top:2px"><?= date('d/m', strtotime($sem['inicio'])) ?>–<?= date('d/m', strtotime($sem['fim'])) ?></div>
                <?php endif; ?>
            </div>
            <div class="box" style="flex:1;min-width:160px;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
                <div class="k" style="font-size:11px;color:#6b7280;text-transform:uppercase">Banco de horas (mês)</div>
                <div style="font-size:20px;font-weight:700;color:<?= ($totais['saldo_banco_min']??0)<0?'#dc2626':'#16a34a' ?>"><?= $fmt($totais['saldo_banco_min'] ?? 0) ?></div>
                <div style="font-size:11px;color:#9ca3af">Faltas no mês: <?= $fmt($totais['minutos_faltas'] ?? 0) ?></div>
            </div>
            <div class="box" style="flex:1;min-width:160px;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
                <div class="k" style="font-size:11px;color:#6b7280;text-transform:uppercase">Extras (mês)</div>
                <div style="font-size:13px;margin-top:4px">
                    50% <strong><?= $fmt($totais['minutos_extras_50'] ?? 0) ?></strong>
                    · 100% <strong><?= $fmt($totais['minutos_extras_100'] ?? 0) ?></strong>
                </div>
                <div style="font-size:11px;color:#9ca3af">Total extra <?= $fmt(($totais['minutos_extras_50']??0)+($totais['minutos_extras_100']??0)) ?></div>
            </div>
        </div>
        <div class="espelho-tot">
            <div class="box"><div class="k">Trabalhadas</div><div class="v"><?= $fmt($totais['minutos_trabalhados'] ?? 0) ?></div></div>
            <div class="box"><div class="k">Previstas</div><div class="v"><?= $fmt($totais['minutos_previstos'] ?? 0) ?></div></div>
            <div class="box"><div class="k">Extra 50%</div><div class="v"><?= $fmt($totais['minutos_extras_50'] ?? 0) ?></div></div>
            <div class="box"><div class="k">Extra 100%</div><div class="v"><?= $fmt($totais['minutos_extras_100'] ?? 0) ?></div></div>
            <div class="box"><div class="k">Faltas (banco)</div><div class="v"><?= $fmt($totais['minutos_faltas'] ?? 0) ?></div></div>
            <div class="box"><div class="k">Saldo banco</div><div class="v" style="color:<?= ($totais['saldo_banco_min']??0)<0?'#dc2626':'#16a34a' ?>"><?= $fmt($totais['saldo_banco_min'] ?? 0) ?></div></div>
        </div>

        <div style="font-size:11px;color:#6b7280;margin:6px 0 10px">
            Legenda:
            <span style="color:<?= $corBat['entrada'] ?>">● Entrada</span> ·
            <span style="color:<?= $corBat['inicio_intervalo'] ?>">● Início int.</span> ·
            <span style="color:<?= $corBat['fim_intervalo'] ?>">● Fim int.</span> ·
            <span style="color:<?= $corBat['saida'] ?>">● Saída</span> ·
            <i class='bx bx-map-pin'></i> local da batida
        </div>

        <div style="overflow-x:auto">
        <table class="espelho-tab">
            <thead><tr><th>Dia</th><th>Sem.</th><th>Batidas (tipo · hora · local)</th><th>Trab.</th><th>Extra</th><th>Falta</th><th>Saldo</th></tr></thead>
            <tbody>
            <?php foreach ($linhas as $l):
                $cls = ! empty($l['abonado']) ? 'folga' : (! $l['eh_util'] ? 'folga' : ($l['calc']['falta'] > 0 ? 'falta' : '')); ?>
                <tr class="<?= $cls ?>">
                    <td><?= (int) substr($l['data'],8,2) ?></td>
                    <td><?= $diasSemana[$l['dia_semana']] ?></td>
                    <td style="text-align:left;font-size:12px">
                        <?php if (! empty($l['abonado'])): ?>
                            <span style="color:#0369a1;font-weight:600" title="Ausência aprovada — sem desconto de falta">Abono: <?= htmlspecialchars($l['tipo_abono'] ?? 'ausência') ?></span>
                            <?php if (! empty($l['batidas'])): ?> · <?php endif; ?>
                        <?php endif; ?>
                        <?php if (empty($l['batidas']) && empty($l['abonado']) && empty($l['cobra_falta']) && ! empty($l['eh_util']) && strtotime($l['data']) < strtotime(date('Y-m-d'))): ?>
                            <span style="color:#9ca3af" title="Fora do período de controle de ponto">— (sem cobrança)</span>
                        <?php elseif (empty($l['batidas']) && empty($l['abonado'])): ?>—
                        <?php elseif (! empty($l['batidas'])): foreach ($l['batidas'] as $b):
                            $cor = $corBat[$b->tipo] ?? '#374151';
                            $lab = $lblBat[$b->tipo] ?? $b->tipo; ?>
                            <span style="display:inline-block;margin:2px 6px 2px 0;padding:1px 6px;border-radius:4px;background:#f3f4f6;border-left:3px solid <?= $cor ?>">
                                <strong style="color:<?= $cor ?>"><?= $lab ?></strong>
                                <?= date('H:i', strtotime($b->data_hora)) ?>
                                <?php if (! empty($b->latitude) && ! empty($b->longitude)): ?>
                                    <a href="https://www.google.com/maps?q=<?= rawurlencode($b->latitude . ',' . $b->longitude) ?>" target="_blank" rel="noopener" title="Lat <?= $b->latitude ?>, Lng <?= $b->longitude ?>" style="color:#2563eb;margin-left:2px"><i class='bx bx-map-pin'></i></a>
                                <?php else: ?>
                                    <span title="Sem GPS" style="color:#d1d5db;margin-left:2px"><i class='bx bx-map'></i></span>
                                <?php endif; ?>
                                <?php if (! empty($b->os_id)): ?><small style="color:#9ca3af"> OS#<?= sprintf('%04d', $b->os_id) ?></small><?php endif; ?>
                            </span>
                        <?php endforeach; endif; ?>
                    </td>
                    <td><?= $fmt($l['calc']['trabalhado']) ?></td>
                    <td><?= $fmt($l['calc']['extra50'] + $l['calc']['extra100']) ?></td>
                    <td><?= $fmt($l['calc']['falta']) ?></td>
                    <td style="color:<?= $l['calc']['saldo']<0?'#dc2626':'#111827' ?>"><?= $fmt($l['calc']['saldo']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div></div>
    <a href="<?= site_url('rh/colaboradores') ?>" class="button btn btn-warning"><span class="button__text2">Voltar</span></a>
</div>
<script>
document.getElementById('competencia').addEventListener('change', function(){
    window.location = '<?= site_url('rh/espelho/'.$colaborador->id) ?>/' + this.value;
});
</script>
