<?php
/**
 * Sub-navegação compartilhada do módulo de RH (abas).
 * Variável: $ativo = 'painel'|'colaboradores'|'lancamentos'|'ocorrencias'|'ausencias'|'unidades'|'jornadas'
 */
$ativo = isset($ativo) ? $ativo : '';
$perm = $this->session->userdata('permissao');
$pode = function ($p) use ($perm) { return $this->permission->checkPermission($perm, $p); };
$pendencias = 0;
if (isset($this->rh_extras_model) && method_exists($this->rh_extras_model, 'contarPendencias')) {
    $pendencias = $this->rh_extras_model->contarPendencias();
}

$itens = [
    ['key' => 'painel',        'url' => 'rh',              'icon' => 'bx-grid-alt',      'label' => 'Painel',       'perm' => 'vRh'],
    ['key' => 'colaboradores', 'url' => 'rh/colaboradores','icon' => 'bx-group',         'label' => 'Colaboradores','perm' => 'vRh'],
    ['key' => 'lancamentos',   'url' => 'rh/lancamentos',  'icon' => 'bx-money',         'label' => 'Lançamentos',  'perm' => 'vRhFinanceiro'],
    ['key' => 'ocorrencias',   'url' => 'rh/ocorrencias',  'icon' => 'bx-error-circle',  'label' => 'Ocorrências',  'perm' => 'vRh'],
    ['key' => 'ausencias',     'url' => 'rh/ausencias',    'icon' => 'bx-calendar-star', 'label' => 'Ausências',    'perm' => 'vRh'],
    ['key' => 'unidades',      'url' => 'rh/unidades',     'icon' => 'bx-buildings',     'label' => 'Unidades',     'perm' => 'eRh'],
    ['key' => 'jornadas',      'url' => 'rh/jornadas',     'icon' => 'bx-time',          'label' => 'Jornadas',     'perm' => 'eRh'],
];
?>
<link rel="stylesheet" href="<?= base_url('assets/css/rh.css') ?>?v=1">
<div class="rh-subnav">
    <?php foreach ($itens as $it): if (! $pode($it['perm'])) continue; ?>
        <a href="<?= site_url($it['url']) ?>" class="<?= $ativo === $it['key'] ? 'active' : '' ?>">
            <i class='bx <?= $it['icon'] ?>'></i> <?= $it['label'] ?>
            <?php if (in_array($it['key'], ['ocorrencias', 'ausencias'], true) && $pendencias > 0): ?>
                <span class="rh-subnav-badge"><?= $pendencias ?></span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>
