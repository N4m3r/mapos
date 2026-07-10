<?php
$tokenName = $this->security->get_csrf_token_name();
$tokenHash = $this->security->get_csrf_hash();
$tagsPadrao = array_filter(array_map('trim', explode(',', Whatsapp_templates_model::tagsPadraoOs())));
?>
<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="fas fa-comment-dots"></i></span>
        <h5>Novo modelo de WhatsApp</h5>
    </div>
    <div class="widget-content">
        <p style="color:#6b7191; margin-top:0">
            Crie um modelo personalizado para usar nos gatilhos. As tags de OS abaixo são substituídas no envio.
        </p>

        <form action="<?= site_url('whatsapptemplates/criar') ?>" method="post">
            <input type="hidden" name="<?= $tokenName ?>" value="<?= $tokenHash ?>">

            <div class="control-group">
                <label class="control-label" for="nome">Nome *</label>
                <div class="controls">
                    <input type="text" id="nome" name="nome" placeholder="Ex.: Aviso de conclusão (curto)" required>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="descricao">Descrição</label>
                <div class="controls">
                    <input type="text" id="descricao" name="descricao" placeholder="Quando/para que serve este modelo">
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="ativo" value="1" checked> Modelo ativo
                </label>
            </div>

            <div class="control-group">
                <label class="control-label" for="conteudo">Mensagem</label>
                <div class="controls">
                    <textarea id="conteudo" name="conteudo" rows="8" style="width:100%; box-sizing:border-box;" placeholder="Digite a mensagem. Use as tags abaixo."></textarea>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label">Tags disponíveis</label>
                <div class="controls">
                    <div style="display:flex; flex-wrap:wrap; gap:6px;">
                        <?php foreach ($tagsPadrao as $tag) { ?>
                            <button type="button" class="btn btn-mini tag-btn" data-tag="<?= html_escape($tag) ?>"><?= html_escape($tag) ?></button>
                        <?php } ?>
                    </div>
                    <input type="hidden" name="tags" value="<?= html_escape(Whatsapp_templates_model::tagsPadraoOs()) ?>">
                    <span class="help-inline">Formatação: <strong>*negrito*</strong>, _itálico_, ~riscado~. Enter cria nova linha.</span>
                </div>
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
                <button type="submit" class="button btn btn-success">
                    <span class="button__icon"><i class="bx bx-save"></i></span>
                    <span class="button__text2">Criar modelo</span>
                </button>
                <a href="<?= site_url('whatsapptemplates') ?>" class="button btn btn-warning">
                    <span class="button__icon"><i class="bx bx-arrow-back"></i></span>
                    <span class="button__text2">Voltar</span>
                </a>
            </div>
        </form>
    </div>
</div>

<script>
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
