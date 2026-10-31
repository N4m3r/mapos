<?php
$tokenName = $this->security->get_csrf_token_name();
$tokenHash = $this->security->get_csrf_hash();
$tags = array_filter(array_map('trim', explode(',', (string) $tpl->tags)));
?>
<div class="ui-cabecalho">
    <div>
        <h1 class="ui-titulo"><i class="bx bx-comment-edit"></i> Modelo de WhatsApp: <?= html_escape($tpl->nome) ?></h1>
        <p class="ui-subtitulo">Edite o texto da mensagem e as tags que ela usa.</p>
    </div>
</div>
<div class="widget-box">
    <div class="widget-content">
        <p style="color:#6b7191; margin-top:0"><?= html_escape($tpl->descricao) ?></p>

        <form action="<?= site_url('whatsapptemplates/salvar') ?>" method="post">
            <input type="hidden" name="<?= $tokenName ?>" value="<?= $tokenHash ?>">
            <input type="hidden" name="slug" value="<?= html_escape($tpl->slug) ?>">

            <div class="control-group">
                <label class="control-label" style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="ativo" value="1" <?= (int) $tpl->ativo === 1 ? 'checked' : '' ?>>
                    Modelo ativo (desmarque para usar a mensagem padrão do sistema)
                </label>
            </div>

            <div class="control-group">
                <label class="control-label" for="conteudo">Mensagem</label>
                <div class="controls">
                    <textarea id="conteudo" name="conteudo" rows="8" style="width:100%; box-sizing:border-box;"><?= html_escape($tpl->conteudo) ?></textarea>
                </div>
            </div>

            <?php if ($tags) { ?>
                <div class="control-group">
                    <label class="control-label">Tags disponíveis</label>
                    <div class="controls">
                        <div style="display:flex; flex-wrap:wrap; gap:6px;">
                            <?php foreach ($tags as $tag) { ?>
                                <button type="button" class="btn btn-mini tag-btn" data-tag="<?= html_escape($tag) ?>"><?= html_escape($tag) ?></button>
                            <?php } ?>
                        </div>
                        <span class="help-inline">Clique para inserir no texto. Formatação do WhatsApp: <strong>*negrito*</strong>, _itálico_, ~riscado~. Enter cria nova linha.</span>
                    </div>
                </div>
            <?php } ?>

            <div class="ui-rodape-form">
                <button type="submit" class="button btn btn-success">
                    <span class="button__icon"><i class="bx bx-save"></i></span>
                    <span class="button__text2">Salvar modelo</span>
                </button>
                <a href="<?= site_url('whatsapptemplates') ?>" class="button btn btn-warning">
                    <span class="button__icon"><i class="bx bx-arrow-back"></i></span>
                    <span class="button__text2">Voltar</span>
                </a>
            </div>
        </form>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
    $(function () {
        $('.tag-btn').on('click', function () {
            var tag = String($(this).data('tag'));
            var el = document.getElementById('conteudo');
            var start = el.selectionStart, end = el.selectionEnd, v = el.value;
            el.value = v.substring(0, start) + tag + v.substring(end);
            el.focus();
            el.selectionStart = el.selectionEnd = start + tag.length;
        });
    });
</script>
