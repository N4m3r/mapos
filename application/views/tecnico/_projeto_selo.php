<?php if (! empty($vinculo_projeto)): $v = $vinculo_projeto; ?>
    <div class="info-card">
        <h3><i class="bx bx-buildings"></i> Projeto</h3>
        <div class="info-row"><span class="info-label">Projeto</span><span class="info-value">
            <?php if (! empty($pode_ver_projeto)): ?>
                <a href="<?= site_url('obras/visualizar/' . (int) $v->idObra) ?>"><?= html_escape($v->obra_nome) ?></a>
            <?php else: ?>
                <?= html_escape($v->obra_nome) ?>
            <?php endif; ?>
        </span></div>
        <?php if (! empty($v->etapa_nome)): ?>
            <div class="info-row"><span class="info-label">Etapa</span><span class="info-value"><?= html_escape(trim(($v->codigo_eap ?: '') . ' ' . $v->etapa_nome)) ?></span></div>
        <?php endif; ?>
    </div>
<?php endif; ?>
