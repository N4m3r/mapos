<?php
$this->load->view('colaborador/_topo', [
    'titulo' => 'Minha Área',
    'header_icone' => 'bxs-user-circle',
    'header_sub' => $colaborador->nome,
]);
$labels = ['entrada'=>'Entrada','saida'=>'Saída','inicio_intervalo'=>'Início intervalo','fim_intervalo'=>'Fim intervalo'];
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

    <div class="rh-cards">
        <div class="rh-card">
            <div class="k">Horas no mês</div>
            <div class="v"><?= $calc->minParaHoras($horas->minutos_trabalhados ?? 0) ?></div>
        </div>
        <div class="rh-card">
            <div class="k">Saldo banco</div>
            <div class="v" style="color:<?= ($horas->saldo_banco_min ?? 0) < 0 ? '#ef4444' : '#10b981' ?>">
                <?= $calc->minParaHoras($horas->saldo_banco_min ?? 0) ?>
            </div>
        </div>
        <div class="rh-card">
            <div class="k">Extras (50%)</div>
            <div class="v"><?= $calc->minParaHoras($horas->minutos_extras_50 ?? 0) ?></div>
        </div>
        <div class="rh-card">
            <div class="k">Solicitações pend.</div>
            <div class="v"><?= (int) $pendentes ?></div>
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
