<?php
$perm = function ($p) { return $this->permission->checkPermission($this->session->userdata('permissao'), $p); };
$aba = isset($contrato_menu) ? $contrato_menu : '';
?>
<div class="btn-toolbar" style="margin:0 0 10px">
    <div class="btn-group">
        <a href="<?= site_url('contratos/painel') ?>" class="button btn btn-mini <?= $aba === 'painel' ? 'btn-primary' : '' ?>">
            <span class="button__icon"><i class='bx bx-tachometer'></i></span><span class="button__text2">Painel</span></a>
        <a href="<?= site_url('contratos/gerenciar') ?>" class="button btn btn-mini <?= $aba === 'contratos' ? 'btn-primary' : '' ?>">
            <span class="button__icon"><i class='bx bx-file'></i></span><span class="button__text2">Contratos</span></a>
        <?php if ($perm('cContrato')): ?>
            <a href="<?= site_url('contratos/adicionar') ?>" class="button btn btn-mini btn-success">
                <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Novo Contrato</span></a>
        <?php endif; ?>
    </div>
</div>
