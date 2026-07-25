<?php
/**
 * Linha do tempo ("onde estamos") de uma OS.
 * Espera $timeline = Os_model::getTimeline($os_id).
 * Reutilizado no administrativo e no portal do cliente.
 */
if (empty($timeline) || empty($timeline['stages'])) {
    return;
}
$tl = $timeline;
$fmtData = function ($d) {
    if (empty($d)) {
        return '';
    }
    $ts = strtotime($d);
    if (! $ts) {
        return '';
    }
    return date('H:i', $ts) === '00:00' ? date('d/m/Y', $ts) : date('d/m/Y H\hi', $ts);
};
?>
<div class="os-timeline">
    <div class="ost-head">
        <i class='bx bx-git-commit'></i> Andamento da ordem de serviço
    </div>

    <?php if (! empty($tl['cancelado'])) { ?>
        <div class="ost-aviso ost-aviso-cancel"><i class='bx bx-x-circle'></i> Esta OS foi cancelada.</div>
    <?php } elseif (! empty($tl['nao_realizado'])) { ?>
        <div class="ost-aviso ost-aviso-espera"><i class='bx bx-time-five'></i> Atendimento não realizado — aguardando reagendamento.</div>
    <?php } ?>

    <div class="ost-track">
        <?php foreach ($tl['stages'] as $s) {
            $estado = $s['estado'] ?? 'pendente';
            $data = $fmtData($s['data'] ?? null); ?>
            <div class="ost-step ost-<?= $estado ?>">
                <span class="ost-ico">
                    <i class='bx <?= $estado === 'concluida' ? 'bx-check' : $s['icone'] ?>'></i>
                </span>
                <span class="ost-lbl"><?= htmlspecialchars($s['label']) ?></span>
                <span class="ost-date"><?= $data !== '' ? $data : ($estado === 'atual' ? 'em andamento' : '—') ?></span>
            </div>
        <?php } ?>
    </div>
</div>

<style>
.os-timeline{margin:14px 0;padding:14px 12px;border:1px solid #e5e7eb;border-radius:10px;background:#fff}
.os-timeline .ost-head{font-size:13px;font-weight:600;color:#374151;margin-bottom:12px;display:flex;align-items:center;gap:6px}
.os-timeline .ost-aviso{font-size:12px;padding:6px 10px;border-radius:6px;margin-bottom:12px;display:flex;align-items:center;gap:6px}
.os-timeline .ost-aviso-cancel{background:#fdecea;color:#b02a1e}
.os-timeline .ost-aviso-espera{background:#fff4e5;color:#a15c00}
.os-timeline .ost-track{display:flex;gap:4px;align-items:flex-start}
.os-timeline .ost-step{flex:1 1 0;min-width:0;position:relative;text-align:center;padding-top:34px}
.os-timeline .ost-step::before{content:"";position:absolute;top:13px;left:calc(-50% + 13px);width:100%;height:2px;background:#e0e3e9;z-index:0}
.os-timeline .ost-step:first-child::before{display:none}
.os-timeline .ost-ico{position:absolute;top:0;left:50%;transform:translateX(-50%);width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;z-index:1;background:#eef0f4;color:#9aa0ab;border:2px solid #eef0f4}
.os-timeline .ost-lbl{display:block;font-size:12px;line-height:1.25;color:#6b7280}
.os-timeline .ost-date{display:block;font-size:11px;color:#9aa0ab;margin-top:2px}
.os-timeline .ost-concluida .ost-ico{background:#27ae60;border-color:#27ae60;color:#fff}
.os-timeline .ost-concluida::before{background:#27ae60}
.os-timeline .ost-concluida .ost-lbl{color:#374151;font-weight:500}
.os-timeline .ost-atual .ost-ico{background:#2f6fed;border-color:#c9dbff;color:#fff;box-shadow:0 0 0 3px rgba(47,111,237,.18)}
.os-timeline .ost-atual .ost-lbl{color:#1f4fbf;font-weight:600}
.os-timeline .ost-atual .ost-date{color:#2f6fed}
@media (max-width:720px){
    .os-timeline .ost-track{flex-direction:column;gap:0}
    .os-timeline .ost-step{display:flex;align-items:center;text-align:left;padding:8px 0 8px 40px;min-height:34px}
    .os-timeline .ost-step::before{top:-8px;left:13px;width:2px;height:100%}
    .os-timeline .ost-ico{top:50%;left:0;transform:translateY(-50%)}
    .os-timeline .ost-lbl{flex:1}
    .os-timeline .ost-date{margin-top:0;margin-left:8px;white-space:nowrap}
}
</style>
