<?php
$csrf_n = $this->security->get_csrf_token_name();
$csrf_h = $this->security->get_csrf_hash();
$this->load->view('tecnico/_topo', [
    'titulo'       => 'Motivos de Não Realizado',
    'header_icone' => 'bx-cog',
    'voltar_url'   => site_url('tecnico/nao_realizadas'),
]);
?>

<div class="tec-container">

    <?php if ($this->session->flashdata('success')): ?>
        <div class="info-card" style="border-left:4px solid var(--tec-success, #2ecc71); color:var(--tec-success, #2ecc71);">
            <i class='bx bx-check-circle'></i> <?= $this->session->flashdata('success') ?>
        </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="info-card" style="border-left:4px solid var(--tec-danger, #e74c3c); color:var(--tec-danger, #e74c3c);">
            <i class='bx bx-error-circle'></i> <?= $this->session->flashdata('error') ?>
        </div>
    <?php endif; ?>

    <!-- Adicionar motivo -->
    <div class="info-card">
        <h3><i class='bx bx-plus-circle'></i> Novo motivo</h3>
        <form method="post" action="<?= site_url('tecnico/salvar_motivo_nao_realizado') ?>" style="display:flex; gap:8px; align-items:stretch;">
            <input type="hidden" name="<?= $csrf_n ?>" value="<?= $csrf_h ?>">
            <input type="text" name="nome" maxlength="120" required placeholder="Ex.: Falta de energia no local"
                style="flex:1; padding:11px 12px; border:1px solid rgba(0,0,0,.15); border-radius:10px; font-size:15px; background:transparent; color:inherit;">
            <button type="submit" class="btn-tec primary"><i class='bx bx-plus'></i></button>
        </form>
    </div>

    <!-- Lista de motivos -->
    <h2 class="tec-section-title"><i class='bx bx-list-ul'></i> Motivos cadastrados</h2>

    <?php if (empty($motivos)): ?>
        <div class="empty-state">
            <i class='bx bx-inbox'></i>
            <p>Nenhum motivo cadastrado.</p>
        </div>
    <?php else: ?>
        <?php foreach ($motivos as $m): ?>
            <div class="info-card" style="display:flex; align-items:center; justify-content:space-between; gap:10px; <?= empty($m->ativo) ? 'opacity:.55;' : '' ?>">
                <span style="font-size:15px;">
                    <i class='bx bx-purchase-tag' style="color:#8a8fa3;"></i>
                    <?= html_escape($m->nome) ?>
                    <?php if (empty($m->ativo)): ?>
                        <span class="badge-status pendente" style="font-size:10px;">Inativo</span>
                    <?php endif; ?>
                </span>
                <?php if (empty($m->ativo)): ?>
                    <a href="<?= site_url('tecnico/reativar_motivo_nao_realizado/' . (int) $m->idMotivo) ?>"
                        class="btn-tec ghost" title="Reativar"><i class='bx bx-refresh'></i></a>
                <?php else: ?>
                    <a href="<?= site_url('tecnico/remover_motivo_nao_realizado/' . (int) $m->idMotivo) ?>"
                        class="btn-tec danger" title="Remover"
                        onclick="return confirm('Remover este motivo? Se já foi usado, ele apenas será desativado.');">
                        <i class='bx bx-trash'></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <p style="font-size:12px; color:#8a8fa3; margin-top:12px;">
        Motivos já usados em registros são desativados (não apagados) para preservar o histórico.
    </p>
</div>

<?php $this->load->view('tecnico/_nav', ['nav_ativo' => 'os', 'pode_ver_sistema' => isset($pode_ver_sistema) ? $pode_ver_sistema : false]); ?>
</body>
</html>
