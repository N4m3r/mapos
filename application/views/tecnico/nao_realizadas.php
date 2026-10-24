<?php
$this->load->view('tecnico/_topo', [
    'titulo'       => 'Serviços Não Realizados',
    'header_icone' => 'bx-x-circle',
    'voltar_url'   => site_url('tecnico'),
]);
?>

<div class="tec-container">

    <p style="font-size:13px; color:#8a8fa3; margin:0 0 14px;">
        OS que não puderam ser executadas em campo. Reagende para uma nova data
        ou reabra para refazer — nada se perde.
    </p>

    <?php if (!empty($pode_gerenciar_motivos)): ?>
        <a href="<?= site_url('tecnico/motivos_nao_realizado') ?>" class="btn-tec ghost block" style="margin-bottom:16px;">
            <i class='bx bx-cog'></i> Gerenciar motivos
        </a>
    <?php endif; ?>

    <?php if (empty($ocorrencias)): ?>
        <div class="empty-state">
            <i class='bx bx-check-circle'></i>
            <p>Nenhum serviço não realizado em espera.</p>
        </div>
    <?php else: ?>
        <?php foreach ($ocorrencias as $oc): ?>
            <div class="os-card pendente" style="border-left:4px solid var(--tec-danger, #e74c3c);">
                <div class="os-head">
                    <a href="<?= site_url('tecnico/visualizar/' . $oc->os_id) ?>" style="text-decoration:none; color:inherit;">
                        <span class="os-num">#OS <?= sprintf('%04d', $oc->os_id) ?></span>
                    </a>
                    <span class="badge-status pendente">Não Realizado</span>
                </div>
                <div class="os-cliente"><i class='bx bx-user'></i> <?= html_escape($oc->nomeCliente ?: 'Cliente') ?></div>
                <?php if (!empty($oc->motivo_texto)): ?>
                    <div class="os-desc"><strong>Motivo:</strong> <?= html_escape($oc->motivo_texto) ?></div>
                <?php endif; ?>
                <?php if (!empty($oc->observacao)): ?>
                    <div class="os-desc"><?= character_limiter(strip_tags($oc->observacao), 120) ?></div>
                <?php endif; ?>
                <div class="os-foot" style="margin-bottom:10px;">
                    <span class="os-meta">
                        <span><i class='bx bx-time'></i> <?= !empty($oc->data_registro) ? date('d/m/Y H:i', strtotime($oc->data_registro)) : '--' ?></span>
                    </span>
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <button type="button" class="btn-tec warning" style="flex:1; justify-content:center;"
                        onclick="abrirReagendar(<?= (int) $oc->idOcorrencia ?>)">
                        <i class='bx bx-calendar-plus'></i> Reagendar
                    </button>
                    <button type="button" class="btn-tec neutral" style="flex:1; justify-content:center;"
                        data-ocorrencia="<?= (int) $oc->idOcorrencia ?>" onclick="reabrirAtividade(this)">
                        <i class='bx bx-revision'></i> Reabrir
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal: reagendar -->
<div id="reagendarModal" class="nr-modal" style="display:none;">
    <div class="nr-modal-box">
        <h3><i class='bx bx-calendar-plus'></i> Reagendar OS</h3>
        <p class="nr-modal-sub">Escolha a nova data. A OS volta para a sua agenda como "Aberto".</p>
        <input type="hidden" id="reag-ocorrencia" value="">
        <label class="nr-label">Nova data</label>
        <input type="date" id="reag-data" class="atv-input" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
        <div class="nr-modal-actions">
            <button type="button" class="btn-tec ghost" onclick="fecharReagendar()">Cancelar</button>
            <button type="button" class="btn-tec warning" onclick="confirmarReagendar()"><i class='bx bx-check'></i> Reagendar</button>
        </div>
    </div>
</div>

<style>
.nr-modal{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:2100;display:flex;align-items:flex-end;justify-content:center;padding:0;}
.nr-modal-box{background:var(--tec-card,#fff);width:100%;max-width:520px;border-radius:16px 16px 0 0;padding:20px 18px calc(20px + env(safe-area-inset-bottom));box-shadow:0 -8px 30px rgba(0,0,0,.25);}
.nr-modal-box h3{margin:0 0 4px;font-size:17px;display:flex;align-items:center;gap:8px;}
.nr-modal-sub{margin:0 0 14px;font-size:13px;color:#8a8fa3;}
.nr-label{display:block;font-size:12px;font-weight:600;color:#8a8fa3;margin:10px 0 5px;}
.nr-modal-box .atv-input{width:100%;padding:11px 12px;border:1px solid rgba(0,0,0,.15);border-radius:10px;font-size:15px;background:transparent;color:inherit;box-sizing:border-box;}
.nr-modal-actions{display:flex;gap:10px;margin-top:18px;}
.nr-modal-actions .btn-tec{flex:1;justify-content:center;}
@media (min-width:600px){.nr-modal{align-items:center;}.nr-modal-box{border-radius:16px;}}
</style>

<?php $this->load->view('tecnico/_nav', ['nav_ativo' => 'os', 'pode_ver_sistema' => isset($pode_ver_sistema) ? $pode_ver_sistema : false]); ?>

<script>
    var NR_BASE = '<?= site_url('tecnico') ?>';

    function abrirReagendar(ocorrenciaId) {
        document.getElementById('reag-ocorrencia').value = ocorrenciaId;
        document.getElementById('reagendarModal').style.display = 'flex';
    }
    function fecharReagendar() {
        document.getElementById('reagendarModal').style.display = 'none';
    }
    function confirmarReagendar() {
        var oc = document.getElementById('reag-ocorrencia').value;
        var data = document.getElementById('reag-data').value;
        if (!data) { alert('Escolha uma data.'); return; }
        $.ajax({
            url: NR_BASE + '/reagendar_atividade',
            type: 'POST',
            dataType: 'json',
            data: { ocorrencia_id: oc, nova_data: data }
        }).done(function (r) {
            alert((r && r.message) || (r && r.success ? 'Reagendado.' : 'Falha.'));
            if (r && r.success) { window.location.reload(); }
        }).fail(function () { alert('Erro de conexão.'); });
    }
    function reabrirAtividade(btn) {
        if (!confirm('Reabrir esta OS para refazer o serviço?')) { return; }
        var oc = btn.getAttribute('data-ocorrencia');
        btn.disabled = true;
        $.ajax({
            url: NR_BASE + '/reabrir_atividade',
            type: 'POST',
            dataType: 'json',
            data: { ocorrencia_id: oc }
        }).done(function (r) {
            alert((r && r.message) || (r && r.success ? 'Reaberta.' : 'Falha.'));
            if (r && r.success) { window.location.reload(); } else { btn.disabled = false; }
        }).fail(function () { alert('Erro de conexão.'); btn.disabled = false; });
    }
</script>
</body>
</html>
