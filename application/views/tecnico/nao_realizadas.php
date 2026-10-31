<?php
$this->load->view('tecnico/_topo', [
    'titulo'       => 'Não deu para fazer',
    'header_icone' => 'bx-x-circle',
    'voltar_url'   => site_url('tecnico'),
]);
?>
<link rel="stylesheet" href="<?= base_url('assets/css/tecnico-inicio.css') ?>?v=<?= asset_versao('assets/css/tecnico-inicio.css', '1') ?>">

<div class="tec-container ti">

    <p class="ti-dica">
        Visitas que não foi possível fazer. Escolha <strong>Reagendar</strong> para marcar outra data
        ou <strong>Tentar de novo</strong> para fazer o serviço agora. Nada se perde.
    </p>

    <?php if (!empty($pode_gerenciar_motivos)): ?>
        <a href="<?= site_url('tecnico/motivos_nao_realizado') ?>" class="ti-btn soft" style="margin-bottom:16px;">
            <i class='bx bx-cog'></i> Gerenciar motivos
        </a>
    <?php endif; ?>

    <?php if (empty($ocorrencias)): ?>
        <div class="ti-vazio">
            <i class='bx bx-check-circle'></i>
            <p>Nada pendente. Tudo certo por aqui!</p>
        </div>
    <?php else: ?>
        <?php foreach ($ocorrencias as $oc): ?>
            <article class="ti-os nao-feita">
                <div class="ti-os-topo">
                    <a class="ti-os-hora" href="<?= site_url('tecnico/visualizar/' . (int) $oc->os_id) ?>">OS <?= sprintf('%04d', $oc->os_id) ?></a>
                    <span class="ti-selo nao-feita"><i class='bx bx-x-circle'></i> Não deu para fazer</span>
                </div>
                <h3 class="ti-os-cli"><?= html_escape($oc->nomeCliente ?: 'Cliente') ?></h3>
                <?php if (!empty($oc->motivo_texto)): ?>
                    <div class="ti-nr-motivo"><strong>Motivo:</strong> <?= html_escape($oc->motivo_texto) ?></div>
                <?php endif; ?>
                <?php if (!empty($oc->observacao)): ?>
                    <div class="ti-nr-motivo"><?= character_limiter(strip_tags($oc->observacao), 120) ?></div>
                <?php endif; ?>
                <div class="ti-os-lin"><i class='bx bx-time'></i> <span>Registrado em <?= !empty($oc->data_registro) ? date('d/m/Y H:i', strtotime($oc->data_registro)) : '--' ?></span></div>
                <div class="ti-os-acoes">
                    <button type="button" class="ti-btn warn"
                        data-chamar="abrirReagendar" data-args="[<?= (int) $oc->idOcorrencia ?>]">
                        <i class='bx bx-calendar-plus'></i> Reagendar
                    </button>
                    <button type="button" class="ti-btn main"
                        data-ocorrencia="<?= (int) $oc->idOcorrencia ?>" data-chamar="reabrirAtividade" data-args='["@this"]'>
                        <i class='bx bx-revision'></i> Tentar de novo
                    </button>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal: reagendar -->
<div id="reagendarModal" class="nr-modal" style="display:none;">
    <div class="nr-modal-box">
        <h3><i class='bx bx-calendar-plus'></i> Reagendar visita</h3>
        <p class="nr-modal-sub">Escolha a nova data. A visita volta para a sua agenda.</p>
        <input type="hidden" id="reag-ocorrencia" value="">
        <label class="nr-label">Nova data</label>
        <input type="date" id="reag-data" class="atv-input" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
        <div class="nr-modal-actions">
            <button type="button" class="btn-tec ghost" data-chamar="fecharReagendar">Cancelar</button>
            <button type="button" class="btn-tec warning" data-chamar="confirmarReagendar"><i class='bx bx-check'></i> Reagendar</button>
        </div>
    </div>
</div>

<style>
.nr-modal{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:2100;display:flex;align-items:flex-end;justify-content:center;padding:0;}
.nr-modal-box{background:var(--tec-card,#fff);width:100%;max-width:520px;border-radius:16px 16px 0 0;padding:20px 18px calc(20px + env(safe-area-inset-bottom));box-shadow:0 -8px 30px rgba(0,0,0,.25);}
.nr-modal-box h3{margin:0 0 4px;font-size:17px;display:flex;align-items:center;gap:8px;}
.nr-modal-sub{margin:0 0 14px;font-size:16px;color:#4d5166;}
.nr-label{display:block;font-size:16px;font-weight:600;color:#4d5166;margin:10px 0 5px;}
.nr-modal-box .atv-input{width:100%;padding:11px 12px;min-height:48px;border:1px solid rgba(0,0,0,.3);border-radius:10px;font-size:16px;background:transparent;color:inherit;box-sizing:border-box;}
.nr-modal-actions{display:flex;gap:10px;margin-top:18px;}
.nr-modal-actions .btn-tec{flex:1;justify-content:center;}
@media (min-width:600px){.nr-modal{align-items:center;}.nr-modal-box{border-radius:16px;}}
</style>

<?php $this->load->view('tecnico/_nav', ['nav_ativo' => 'os', 'pode_ver_sistema' => isset($pode_ver_sistema) ? $pode_ver_sistema : false]); ?>

<script nonce="<?= csp_nonce() ?>">
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
        if (!confirm('Tentar fazer este serviço de novo?')) { return; }
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
