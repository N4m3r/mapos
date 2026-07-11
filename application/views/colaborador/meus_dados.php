<?php
$this->load->view('colaborador/_topo', [
    'titulo' => 'Meus Dados',
    'header_icone' => 'bx-id-card',
    'voltar_url' => site_url('colaborador'),
]);
$csrf_n = $this->security->get_csrf_token_name();
$csrf_h = $this->security->get_csrf_hash();
?>
<div class="ponto-wrap">
    <?php if ($var = $this->session->flashdata('success')): ?><div class="tec-alert success"><?= $var ?></div><?php endif; ?>

    <div class="rh-card" style="margin-bottom:12px">
        <div style="font-size:13px;color:#6b7280">Nome</div><div style="font-weight:600"><?= htmlspecialchars($colaborador->nome) ?></div>
        <div style="font-size:13px;color:#6b7280;margin-top:6px">Cargo</div><div><?= htmlspecialchars($colaborador->cargo ?: '-') ?></div>
        <div style="font-size:13px;color:#6b7280;margin-top:6px">Admissão</div><div><?= $colaborador->admissao ? date('d/m/Y', strtotime($colaborador->admissao)) : '-' ?></div>
    </div>

    <form method="post" action="<?= site_url('colaborador/meusDados') ?>" class="rh-card">
        <input type="hidden" name="<?= $csrf_n ?>" value="<?= $csrf_h ?>">
        <input type="hidden" name="salvar" value="1">
        <label>E-mail</label>
        <input type="email" name="email" value="<?= htmlspecialchars($colaborador->email) ?>" class="span12" style="width:100%">
        <label style="margin-top:8px">Celular</label>
        <input type="text" name="celular" value="<?= htmlspecialchars($colaborador->celular) ?>" class="span12" style="width:100%">
        <div style="display:flex;gap:8px;margin-top:8px">
            <div style="flex:1"><label>Tipo PIX</label>
                <input type="text" name="pix_tipo" value="<?= htmlspecialchars($colaborador->pix_tipo) ?>" class="span12" style="width:100%" placeholder="CPF, e-mail...">
            </div>
            <div style="flex:2"><label>Chave PIX</label>
                <input type="text" name="pix_chave" value="<?= htmlspecialchars($colaborador->pix_chave) ?>" class="span12" style="width:100%">
            </div>
        </div>
        <button type="submit" class="btn-bater" style="margin-top:12px"><i class='bx bx-save'></i> Salvar</button>
    </form>
</div>
<?php $this->load->view('colaborador/_nav', ['nav_ativo' => '', 'pode_bater_ponto' => $pode_bater_ponto]); ?>
</body>
</html>
