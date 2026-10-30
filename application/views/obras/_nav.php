<?php
$ativo = isset($obra_menu) ? $obra_menu : '';
$permE = $this->permission->checkPermission($this->session->userdata('permissao'), 'vObras');
$permC = $this->permission->checkPermission($this->session->userdata('permissao'), 'cObras');
$itens = [
    ['painel', 'obras/painel', 'Painel', 'bx-grid-alt'],
    ['obras', 'obras/gerenciar', 'Projetos', 'bx-network-chart'],
    ['relatorio', 'relatorioprojetos', 'Relatório', 'bx-file'],
];
if ($permE) {
    $itens[] = ['equipes', 'obraequipe', 'Equipes', 'bx-group'];
}
?>
<link rel="stylesheet" href="<?= base_url('assets/css/projeto-lista.css') ?>?v=<?= asset_versao('assets/css/projeto-lista.css', '1') ?>">
<nav class="projeto-nav" aria-label="Projetos">
    <ul class="projeto-nav-lista">
        <?php foreach ($itens as $i): ?>
            <li><a href="<?= site_url($i[1]) ?>" class="<?= $ativo === $i[0] ? 'ativo' : '' ?>" <?= $ativo === $i[0] ? 'aria-current="page"' : '' ?>><i class='bx <?= $i[3] ?>'></i> <?= $i[2] ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php if ($permC && in_array($ativo, ['painel', 'obras'], true)): ?>
        <a href="<?= site_url('obras/adicionar') ?>" class="button btn btn-mini btn-primary projeto-nav-novo">
            <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Novo projeto</span></a>
    <?php endif; ?>
</nav>
