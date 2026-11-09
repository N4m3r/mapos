<?php
/**
 * Aba Modelos da Central de Notificacoes.
 *
 * Atalho unico para os textos enviados: modelos de e-mail (com o layout global)
 * e modelos de WhatsApp. A edicao continua na tela propria de cada modelo.
 *
 * Variaveis: $modelosEmail, $modelosWhatsapp, $whatsappSlugsCore, $podeEmail,
 * $podeSistema
 */
$e = function ($v) {
    return html_escape((string) ($v ?? ''));
};
?>

<div class="ui-dica">
    <i class="bx bx-info-circle"></i>
    <div>
        Os modelos abaixo preenchem o texto de cada aviso. Use as tags dos próprios modelos para inserir dados
        do cliente, da OS e da cobrança. Um evento pode apontar para um modelo específico na aba
        <strong>Eventos</strong>.
    </div>
</div>

<?php if ($podeEmail) { ?>
<section class="nt-grupo">
    <h3 class="nt-grupo-titulo">
        <i class="bx bx-envelope-open"></i> E-mail
        <span class="ui-selo neutro"><?= count($modelosEmail) ?> modelo(s)</span>
    </h3>

    <?php if (empty($modelosEmail)) { ?>
        <div class="widget-box">
            <div class="ui-vazio">
                <i class="bx bx-envelope"></i>
                <strong>Nenhum modelo de e-mail cadastrado</strong>
                Rode a atualização do banco para criar os modelos padrão.
            </div>
        </div>
    <?php } else { ?>
        <div class="nt-modelos">
            <?php foreach ($modelosEmail as $m) { ?>
                <article class="nt-modelo">
                    <div class="nt-modelo-topo">
                        <span class="nt-modelo-nome"><?= $e($m->nome) ?></span>
                        <span class="ui-selo <?= (int) $m->ativo === 1 ? 'sucesso' : 'neutro' ?>"><?= (int) $m->ativo === 1 ? 'ativo' : 'desligado' ?></span>
                    </div>
                    <p class="nt-modelo-desc ui-m0"><?= $e($m->descricao ?: 'Sem descrição.') ?></p>
                    <div>
                        <a href="<?= site_url('emailtemplates/editar/' . (int) $m->id) ?>" class="btn btn-mini btn-primary">
                            <i class="bx bx-edit"></i> Editar texto
                        </a>
                    </div>
                </article>
            <?php } ?>
        </div>
    <?php } ?>

    <div class="ui-acoes ui-mt">
        <a href="<?= site_url('emailtemplates/novo') ?>" class="button btn btn-success">
            <span class="button__icon"><i class="bx bx-plus-circle"></i></span>
            <span class="button__text2">Novo modelo de e-mail</span>
        </a>
        <a href="<?= site_url('emailtemplates/editarLayout') ?>" class="button btn">
            <span class="button__icon"><i class="bx bx-palette"></i></span>
            <span class="button__text2">Layout e CSS dos e-mails</span>
        </a>
    </div>
</section>
<?php } ?>

<?php if ($podeSistema) { ?>
<section class="nt-grupo">
    <h3 class="nt-grupo-titulo">
        <i class="bx bxl-whatsapp"></i> WhatsApp
        <span class="ui-selo neutro"><?= count($modelosWhatsapp) ?> modelo(s)</span>
    </h3>

    <?php if (empty($modelosWhatsapp)) { ?>
        <div class="widget-box">
            <div class="ui-vazio">
                <i class="bx bx-comment-dots"></i>
                <strong>Nenhum modelo de WhatsApp cadastrado</strong>
                Rode a atualização do banco para criar os modelos padrão.
            </div>
        </div>
    <?php } else { ?>
        <div class="nt-modelos">
            <?php foreach ($modelosWhatsapp as $t) { ?>
                <?php
                $previa = trim((string) $t->conteudo);
                if (mb_strlen($previa) > 160) {
                    $previa = mb_substr($previa, 0, 160) . '...';
                }
                ?>
                <article class="nt-modelo">
                    <div class="nt-modelo-topo">
                        <span class="nt-modelo-nome"><?= $e($t->nome) ?></span>
                        <span class="ui-selo <?= (int) $t->ativo === 1 ? 'sucesso' : 'neutro' ?>"><?= (int) $t->ativo === 1 ? 'ativo' : 'desligado' ?></span>
                    </div>
                    <p class="nt-modelo-desc ui-m0"><?= $e($t->descricao ?: 'Sem descrição.') ?></p>
                    <?php if ($previa !== '') { ?>
                        <div class="nt-modelo-preview"><?= $e($previa) ?></div>
                    <?php } ?>
                    <div class="ui-acoes-linha">
                        <a href="<?= site_url('whatsapptemplates/editar/' . rawurlencode((string) $t->slug)) ?>" class="btn btn-mini btn-primary">
                            <i class="bx bx-edit"></i> Editar
                        </a>
                        <?php if (! in_array($t->slug, $whatsappSlugsCore ?? [], true)) { ?>
                            <a href="<?= site_url('whatsapptemplates/excluir/' . rawurlencode((string) $t->slug)) ?>"
                               class="btn btn-mini btn-danger"
                               data-confirmar="Excluir o modelo '<?= $e($t->nome) ?>'?"
                               title="Excluir modelo" aria-label="Excluir modelo">
                                <i class="bx bx-trash"></i>
                            </a>
                        <?php } ?>
                    </div>
                </article>
            <?php } ?>
        </div>
    <?php } ?>

    <div class="ui-acoes ui-mt">
        <a href="<?= site_url('whatsapptemplates/novo') ?>" class="button btn btn-success">
            <span class="button__icon"><i class="bx bx-plus-circle"></i></span>
            <span class="button__text2">Novo modelo de WhatsApp</span>
        </a>
    </div>
</section>
<?php } ?>
