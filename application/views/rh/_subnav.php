<?php
/**
 * Sub-navegação compartilhada do módulo de RH (abas).
 * Variável: $ativo = 'painel'|'colaboradores'|'lancamentos'|'folha'|'descontos'|'ocorrencias'|'ausencias'|'unidades'|'jornadas'
 * Nas telas de detalhe o controller informa $rh_ativo (aba de origem).
 */
$ativo = isset($ativo) ? $ativo : (isset($rh_ativo) ? $rh_ativo : '');
$perm = $this->session->userdata('permissao');
$pode = function ($p) use ($perm) { return $this->permission->checkPermission($perm, $p); };
$contagem = ['ocorrencias' => 0, 'ausencias' => 0];
if (isset($this->rh_extras_model) && method_exists($this->rh_extras_model, 'contarPendenciasPorTipo')) {
    $porTipo = $this->rh_extras_model->contarPendenciasPorTipo();
    $contagem['ocorrencias'] = $porTipo['ocorrencias'];
    $contagem['ausencias'] = $porTipo['ausencias'];
}

$itens = [
    ['key' => 'painel',        'url' => 'rh',              'icon' => 'bx-grid-alt',      'label' => 'Painel',       'perm' => 'vRh'],
    ['key' => 'colaboradores', 'url' => 'rh/colaboradores','icon' => 'bx-group',         'label' => 'Colaboradores','perm' => 'vRh'],
    ['key' => 'lancamentos',   'url' => 'rh/lancamentos',  'icon' => 'bx-money',         'label' => 'Lançamentos',  'perm' => 'vRhFinanceiro'],
    ['key' => 'folha',         'url' => 'rh/folha',        'icon' => 'bx-file',          'label' => 'Folha',        'perm' => 'vRhFinanceiro'],
    ['key' => 'descontos',     'url' => 'rh/descontosClt', 'icon' => 'bx-calculator',    'label' => 'Descontos CLT','perm' => 'eRh'],
    ['key' => 'ocorrencias',   'url' => 'rh/ocorrencias',  'icon' => 'bx-error-circle',  'label' => 'Ocorrências',  'perm' => 'vRh'],
    ['key' => 'ausencias',     'url' => 'rh/ausencias',    'icon' => 'bx-calendar-star', 'label' => 'Ausências',    'perm' => 'vRh'],
    ['key' => 'unidades',      'url' => 'rh/unidades',     'icon' => 'bx-buildings',     'label' => 'Unidades',     'perm' => 'eRh'],
    ['key' => 'jornadas',      'url' => 'rh/jornadas',     'icon' => 'bx-time',          'label' => 'Jornadas',     'perm' => 'eRh'],
];
?>
<div class="rh-subnav">
    <?php foreach ($itens as $it): if (! $pode($it['perm'])) continue;
        $rotulo = isset($contagem[$it['key']]) ? Rh_admin::rotuloContagem($contagem[$it['key']]) : ''; ?>
        <a href="<?= site_url($it['url']) ?>" class="<?= $ativo === $it['key'] ? 'active' : '' ?>" <?= $ativo === $it['key'] ? 'aria-current="page"' : '' ?>>
            <i class='bx <?= $it['icon'] ?>'></i> <?= $it['label'] ?>
            <?php if ($rotulo !== ''): ?>
                <span class="rh-subnav-badge" title="Pendentes de análise"><?= html_escape($rotulo) ?></span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>
