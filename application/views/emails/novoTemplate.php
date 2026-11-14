<?php
/**
 * Novo modelo de e-mail (Emailtemplates::novo).
 *
 * O modelo nasce como cópia de um existente (assunto, corpo e tags) e abre no
 * editor. Depois é só escolhê-lo no evento, em "Modelo de e-mail".
 *
 * Variáveis: $modelosBase
 */
$e = function ($v) {
    return html_escape((string) ($v ?? ''));
};
?>
<div class="ui-cabecalho">
    <div>
        <h1 class="ui-titulo"><i class="bx bx-plus-circle"></i> Novo modelo de e-mail</h1>
        <p class="ui-subtitulo">Dê um nome e escolha em qual modelo ele se baseia. O texto pode ser ajustado em seguida.</p>
    </div>
    <div class="ui-acoes">
        <a href="<?= site_url('notificacoes?aba=modelos') ?>" class="button btn btn-warning"><span class="button__icon"><i class="bx bx-arrow-back"></i></span><span class="button__text2">Voltar</span></a>
    </div>
</div>

<div class="widget-box">
    <div class="widget-content">
        <form action="<?= site_url('emailtemplates/criar') ?>" method="post">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

            <div class="mb-3">
                <label class="form-label" for="nome">Nome do modelo *</label>
                <div class="">
                    <input id="nome" type="text" name="nome" class="w-100 form-control" maxlength="120" required placeholder="Ex.: Aceite do serviço - clientes corporativos">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="descricao">Descrição</label>
                <div class="">
                    <input id="descricao" type="text" name="descricao" class="w-100 form-control" maxlength="255" placeholder="Quando este e-mail é usado">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="base">Baseado em *</label>
                <div class="">
                    <select id="base" name="base" class="w-100 form-select" required>
                        <?php foreach ($modelosBase as $m) { ?>
                            <option value="<?= $e($m->slug) ?>" <?= $m->slug === 'os' ? 'selected' : '' ?>><?= $e($m->nome) ?></option>
                        <?php } ?>
                    </select>
                    <span class="form-text">O novo modelo copia o assunto, o corpo e as tags do modelo escolhido.</span>
                </div>
            </div>

            <button type="submit" class="button btn btn-success">
                <span class="button__icon"><i class="bx bx-save"></i></span>
                <span class="button__text2">Criar e editar</span>
            </button>
        </form>
    </div>
</div>
