<?php
/**
 * Menu lateral (drawer) da Area do Tecnico / Colaborador / Ponto.
 * Incluir logo apos </header>. Variavel opcional: $menu_offline (bool) - mostra
 * "Modo offline" e "Instalar app" (so onde pwa-tecnico.js esta carregado).
 * O botao que abre (#tec-menu-btn) fica no cabecalho de cada _topo.
 */
$this->load->helper('menu_tecnico');
$m = menu_tecnico_dados(!empty($menu_offline));
?>
<div class="tec-menu-overlay" id="tec-menu-overlay" hidden></div>
<aside class="tec-menu" id="tec-menu" role="dialog" aria-modal="true" aria-labelledby="tec-menu-titulo" hidden>
    <div class="tec-menu-head">
        <button type="button" class="tec-menu-fechar" id="tec-menu-fechar" aria-label="Fechar menu"><i class='bx bx-x' aria-hidden="true"></i></button>
        <h2 id="tec-menu-titulo" class="tec-menu-sr">Menu</h2>
        <div class="tec-menu-user">
            <?php if ($m['foto_url']): ?>
                <img class="tec-menu-avatar" src="<?= html_escape($m['foto_url']) ?>" alt="">
            <?php else: ?>
                <span class="tec-menu-avatar tec-menu-iniciais" aria-hidden="true"><?= html_escape($m['iniciais']) ?></span>
            <?php endif; ?>
            <div class="tec-menu-user-txt">
                <strong><?= html_escape($m['nome']) ?></strong>
                <?php if ($m['cargo'] !== ''): ?><span><?= html_escape($m['cargo']) ?></span><?php endif; ?>
                <?php if ($m['perfil_url']): ?><a href="<?= $m['perfil_url'] ?>" class="tec-menu-perfil">Meu perfil</a><?php endif; ?>
            </div>
        </div>
    </div>
    <nav class="tec-menu-nav" aria-label="Menu principal">
        <?php foreach ($m['secoes'] as $sec): ?>
            <p class="tec-menu-sec" id="tec-menu-sec-<?= $sec['id'] ?>"><?= html_escape($sec['titulo']) ?></p>
            <ul class="tec-menu-lista" aria-labelledby="tec-menu-sec-<?= $sec['id'] ?>">
            <?php foreach ($sec['itens'] as $it): ?>
                <li>
                <?php if ($it['acao'] === 'offline'): ?>
                    <button type="button" id="toggle-offline" class="tec-menu-item"><i class='bx bx-wifi-off' aria-hidden="true"></i> <span id="toggle-offline-texto"></span></button>
                <?php elseif ($it['acao'] === 'instalar'): ?>
                    <button type="button" id="install-btn" class="tec-menu-item" style="display:none"><i class='bx <?= $it['icone'] ?>' aria-hidden="true"></i> <span>Instalar app</span></button>
                <?php elseif ($it['desabilitado']): ?>
                    <span class="tec-menu-item tec-menu-desab" aria-disabled="true"><i class='bx <?= $it['icone'] ?>' aria-hidden="true"></i> <span><?= html_escape($it['rotulo']) ?></span></span>
                <?php else: ?>
                    <a class="tec-menu-item<?= $it['ativo'] ? ' ativo' : '' ?>" href="<?= $it['acao'] === 'raiz' ? base_url() : site_url($it['rota']) ?>"<?= $it['ativo'] ? ' aria-current="page"' : '' ?>>
                        <i class='bx <?= $it['icone'] ?>' aria-hidden="true"></i>
                        <span><?= html_escape($it['rotulo']) ?></span>
                        <?php if ($it['badge'] > 0): ?><em class="tec-menu-badge" aria-label="<?= (int) $it['badge'] ?> na fila"><?= (int) $it['badge'] ?></em><?php endif; ?>
                    </a>
                <?php endif; ?>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </nav>
</aside>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/menu-tecnico.js') ?>?v=<?= asset_versao('assets/js/menu-tecnico.js') ?>"></script>
<?php if (!empty($menu_offline)): ?>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/tecnico-posicao.js') ?>?v=<?= asset_versao('assets/js/tecnico-posicao.js') ?>" defer></script>
<?php endif; ?>
