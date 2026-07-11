<?php
$this->load->view('colaborador/_topo', [
    'titulo' => 'Folga / Férias',
    'header_icone' => 'bx-calendar-star',
    'voltar_url' => site_url('colaborador'),
]);
$tipos = ['folga'=>'Folga','ferias'=>'Férias','atestado'=>'Atestado','licenca'=>'Licença'];
$csrf_n = $this->security->get_csrf_token_name();
$csrf_h = $this->security->get_csrf_hash();
?>
<div class="ponto-wrap">
    <?php if ($var = $this->session->flashdata('success')): ?><div class="tec-alert success"><?= $var ?></div><?php endif; ?>
    <?php if ($var = $this->session->flashdata('error')): ?><div class="tec-alert error"><?= $var ?></div><?php endif; ?>

    <form method="post" action="<?= site_url('colaborador/solicitarAusencia') ?>" enctype="multipart/form-data"
          class="rh-card" style="margin-bottom:16px">
        <input type="hidden" name="<?= $csrf_n ?>" value="<?= $csrf_h ?>">
        <h4 style="margin:0 0 10px"><i class='bx bx-plus-circle'></i> Nova solicitação</h4>
        <label>Tipo</label>
        <select name="tipo" class="span12" style="width:100%">
            <?php foreach ($tipos as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
        </select>
        <div style="display:flex;gap:8px;margin-top:8px">
            <div style="flex:1"><label>Início</label><input type="date" name="data_inicio" class="span12" style="width:100%" required></div>
            <div style="flex:1"><label>Fim</label><input type="date" name="data_fim" class="span12" style="width:100%"></div>
        </div>
        <label style="margin-top:8px">Motivo</label>
        <textarea name="motivo" rows="2" class="span12" style="width:100%"></textarea>
        <label style="margin-top:8px">Anexo (ex.: atestado) — opcional</label>
        <input type="file" name="anexo" accept="image/*,application/pdf" class="span12" style="width:100%">
        <button type="submit" class="btn-bater" style="margin-top:12px"><i class='bx bx-send'></i> Enviar ao RH</button>
    </form>

    <h4 style="color:#374151"><i class='bx bx-history'></i> Minhas solicitações</h4>
    <?php if (empty($ausencias)): ?>
        <div style="color:#9ca3af;font-size:13px">Nenhuma solicitação ainda.</div>
    <?php else: foreach ($ausencias as $a): ?>
        <div class="rh-list-item">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <strong><?= $tipos[$a->tipo] ?? $a->tipo ?></strong>
                <span class="rh-badge <?= $a->status ?>"><?= ucfirst($a->status) ?></span>
            </div>
            <small style="color:#6b7280"><?= date('d/m/Y', strtotime($a->data_inicio)) ?> a <?= date('d/m/Y', strtotime($a->data_fim)) ?> (<?= (int)$a->dias ?> dia<?= $a->dias > 1 ? 's':'' ?>)</small>
            <?php if ($a->motivo): ?><div style="font-size:13px;margin-top:4px"><?= nl2br(htmlspecialchars($a->motivo)) ?></div><?php endif; ?>
            <?php if (! empty($a->anexo_base64)): ?><a href="<?= site_url('colaborador/anexo/ausencia/'.$a->id) ?>" target="_blank" style="font-size:12px">Ver anexo</a><?php endif; ?>
            <?php if ($a->resposta): ?><div style="font-size:12px;color:#065f46;margin-top:4px">RH: <?= htmlspecialchars($a->resposta) ?></div><?php endif; ?>
        </div>
    <?php endforeach; endif; ?>
</div>
<?php $this->load->view('colaborador/_nav', ['nav_ativo' => 'solicitacoes', 'pode_bater_ponto' => $pode_bater_ponto]); ?>
</body>
</html>
