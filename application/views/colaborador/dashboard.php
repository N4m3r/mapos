<?php
$this->load->view('colaborador/_topo', [
    'titulo' => 'Minha Área',
    'header_icone' => 'bxs-user-circle',
    'header_sub' => $colaborador->nome,
]);
$labels = ['entrada'=>'Entrada','saida'=>'Saída','inicio_intervalo'=>'Início intervalo','fim_intervalo'=>'Fim intervalo'];
$mes = $totais_mes ?? [];
$sem = $totais_semana ?? [];
$extraTotal = (int) ($mes['minutos_extras_50'] ?? 0) + (int) ($mes['minutos_extras_100'] ?? 0);
?>
<div class="ponto-wrap">
    <?php if ($var = $this->session->flashdata('success')): ?>
        <div class="tec-alert success"><?= $var ?></div><?php endif; ?>
    <?php if ($var = $this->session->flashdata('error')): ?>
        <div class="tec-alert error"><?= $var ?></div><?php endif; ?>

    <?php if ($pode_bater_ponto): ?>
    <a href="<?= site_url('ponto') ?>" class="btn-bater" style="text-align:center;text-decoration:none;margin-bottom:16px">
        <i class='bx bx-fingerprint'></i> Bater Ponto
    </a>
    <?php endif; ?>

    <?php if (empty($ponto_inicio)): ?>
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 12px;margin-bottom:12px;font-size:13px;color:#1e40af">
            O controle de faltas ainda não foi iniciado pelo RH. Seus dias sem batida <strong>não geram dívida</strong> no banco de horas.
        </div>
    <?php endif; ?>

    <!-- Status separados: semanal · banco · extras -->
    <div class="rh-cards">
        <div class="rh-card">
            <div class="k">Semana (seg–dom)</div>
            <div class="v"><?= $calc->minParaHoras($sem['minutos_trabalhados'] ?? 0) ?></div>
            <div style="font-size:11px;color:#6b7280;margin-top:4px">
                <?php if (! empty($sem['inicio'])): ?>
                    <?= date('d/m', strtotime($sem['inicio'])) ?>–<?= date('d/m', strtotime($sem['fim'])) ?>
                    ·
                <?php endif; ?>
                Deve: <span style="color:#ef4444"><?= $calc->minParaHoras($sem['minutos_faltas'] ?? 0) ?></span>
            </div>
        </div>
        <div class="rh-card">
            <div class="k">Banco de horas (mês)</div>
            <div class="v" style="color:<?= ($mes['saldo_banco_min'] ?? 0) < 0 ? '#ef4444' : '#10b981' ?>">
                <?= $calc->minParaHoras($mes['saldo_banco_min'] ?? 0) ?>
            </div>
            <div style="font-size:11px;color:#6b7280;margin-top:4px">
                Faltas: <?= $calc->minParaHoras($mes['minutos_faltas'] ?? 0) ?>
            </div>
        </div>
        <div class="rh-card">
            <div class="k">Extras (mês)</div>
            <div class="v"><?= $calc->minParaHoras($extraTotal) ?></div>
            <div style="font-size:11px;color:#6b7280;margin-top:4px">
                50% <?= $calc->minParaHoras($mes['minutos_extras_50'] ?? 0) ?>
                · 100% <?= $calc->minParaHoras($mes['minutos_extras_100'] ?? 0) ?>
            </div>
        </div>
        <div class="rh-card">
            <div class="k">Horas no mês</div>
            <div class="v"><?= $calc->minParaHoras($mes['minutos_trabalhados'] ?? ($horas->minutos_trabalhados ?? 0)) ?></div>
            <div style="font-size:11px;color:#6b7280;margin-top:4px">
                Solic. pendentes: <?= (int) $pendentes ?>
            </div>
        </div>
    </div>

    <div class="ponto-timeline">
        <h4><i class='bx bx-time-five'></i> Batidas de hoje</h4>
        <?php if (empty($batidas_hoje)): ?>
            <div style="color:#9ca3af;font-size:13px">Nenhuma batida hoje.</div>
        <?php else: foreach ($batidas_hoje as $b): ?>
            <div class="ponto-batida <?= ($b->dentro_geofence === '0') ? 'fora' : '' ?>">
                <span class="dot"></span>
                <span class="tipo"><?= $labels[$b->tipo] ?? $b->tipo ?></span>
                <span class="hora"><?= date('H:i', strtotime($b->data_hora)) ?></span>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:16px">
        <a href="<?= site_url('colaborador/espelho') ?>" class="rh-card" style="text-decoration:none;text-align:center">
            <i class='bx bx-calendar-check' style="font-size:26px;color:#667eea"></i>
            <div class="k">Espelho de ponto</div>
        </a>
        <a href="<?= site_url('colaborador/holerite') ?>" class="rh-card" style="text-decoration:none;text-align:center">
            <i class='bx bx-receipt' style="font-size:26px;color:#667eea"></i>
            <div class="k">Holerite</div>
        </a>
        <a href="<?= site_url('colaborador/ocorrencias') ?>" class="rh-card" style="text-decoration:none;text-align:center">
            <i class='bx bx-error-circle' style="font-size:26px;color:#667eea"></i>
            <div class="k">Justificar/Corrigir</div>
        </a>
        <a href="<?= site_url('colaborador/ausencias') ?>" class="rh-card" style="text-decoration:none;text-align:center">
            <i class='bx bx-calendar-star' style="font-size:26px;color:#667eea"></i>
            <div class="k">Folga / Férias</div>
        </a>
    </div>
</div>
<?php $this->load->view('colaborador/_nav', ['nav_ativo' => 'home', 'pode_bater_ponto' => $pode_bater_ponto]); ?>
</body>
</html>
