<?php
$tokenName = $this->security->get_csrf_token_name();
$tokenHash = $this->security->get_csrf_hash();
?>
<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="fas fa-envelope-open-text"></i></span>
        <h5>Editar modelo: <?= html_escape($template->nome) ?></h5>
    </div>
    <div class="widget-content">
        <div class="row-fluid">
            <!-- Editor -->
            <div class="span8">
                <form action="<?= site_url('emailtemplates/salvar') ?>" method="post" id="formTemplate">
                    <input type="hidden" name="<?= $tokenName ?>" value="<?= $tokenHash ?>">
                    <input type="hidden" name="id" value="<?= (int) $template->id ?>">

                    <div class="control-group">
                        <label class="control-label" style="display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="ativo" value="1" <?= (int) $template->ativo === 1 ? 'checked' : '' ?>>
                            Enviar este e-mail (desmarque para não enviar este tipo de e-mail)
                        </label>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="assunto">Assunto</label>
                        <div class="controls">
                            <input id="assunto" type="text" name="assunto" class="span12 campo-tags" value="<?= html_escape($template->assunto) ?>">
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="corpo">Corpo do e-mail (HTML)</label>
                        <div class="controls">
                            <textarea id="corpo" name="corpo" class="span12 campo-tags" rows="18" style="font-family: 'Courier New', monospace; font-size: 13px;"><?= html_escape($template->corpo) ?></textarea>
                            <span class="help-block">Este conteúdo é inserido dentro do layout global (cabeçalho, rodapé e CSS).</span>
                        </div>
                    </div>

                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <button type="submit" class="button btn btn-success">
                            <span class="button__icon"><i class="bx bx-save"></i></span>
                            <span class="button__text2">Salvar modelo</span>
                        </button>
                        <button type="button" class="button btn btn-primary" id="btnPreview">
                            <span class="button__icon"><i class="bx bx-show"></i></span>
                            <span class="button__text2">Pré-visualizar</span>
                        </button>
                        <a href="<?= site_url('emailtemplates') ?>" class="button btn btn-warning">
                            <span class="button__icon"><i class="bx bx-arrow-back"></i></span>
                            <span class="button__text2">Voltar</span>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Tags -->
            <div class="span4">
                <div style="background:#f4f6fb; border-radius:8px; padding:14px;">
                    <h5 style="margin-top:0">Tags disponíveis</h5>
                    <p style="color:#6b7191; font-size:12px;">Clique para inserir no campo selecionado (assunto ou corpo).</p>
                    <div id="listaTags">
                        <?php foreach ($tagsDisponiveis as $tag) { ?>
                            <a href="#" class="btn btn-mini tag-chip" data-tag="{{<?= html_escape($tag) ?>}}" style="margin:0 4px 6px 0;">{{<?= html_escape($tag) ?>}}</a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview -->
        <div class="row-fluid" style="margin-top:18px;">
            <div class="span12">
                <h5>Pré-visualização</h5>
                <iframe id="previewFrame" name="previewFrame" style="width:100%; height:520px; border:1px solid #e2e6f0; border-radius:8px; background:#fff;"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Form oculto usado só para gerar o preview dentro do iframe -->
<form action="<?= site_url('emailtemplates/preview') ?>" method="post" target="previewFrame" id="formPreview" style="display:none;">
    <input type="hidden" name="<?= $tokenName ?>" value="<?= $tokenHash ?>">
    <input type="hidden" name="slug" value="<?= html_escape($template->slug) ?>">
    <input type="hidden" name="assunto" id="previewAssunto" value="">
    <input type="hidden" name="corpo" id="previewCorpo" value="">
</form>

<script type="text/javascript">
    (function() {
        var ultimoCampo = document.getElementById('corpo');
        document.querySelectorAll('.campo-tags').forEach(function(el) {
            el.addEventListener('focus', function() {
                ultimoCampo = el;
            });
        });

        // Inserir tag no cursor do último campo focado.
        document.querySelectorAll('.tag-chip').forEach(function(chip) {
            chip.addEventListener('click', function(e) {
                e.preventDefault();
                var tag = this.getAttribute('data-tag');
                var el = ultimoCampo || document.getElementById('corpo');
                var start = el.selectionStart || 0;
                var end = el.selectionEnd || 0;
                el.value = el.value.substring(0, start) + tag + el.value.substring(end);
                var pos = start + tag.length;
                el.focus();
                el.setSelectionRange(pos, pos);
            });
        });

        // Preview: copia valores atuais para o form oculto e submete no iframe.
        document.getElementById('btnPreview').addEventListener('click', function() {
            document.getElementById('previewAssunto').value = document.getElementById('assunto').value;
            document.getElementById('previewCorpo').value = document.getElementById('corpo').value;
            document.getElementById('formPreview').submit();
        });

        // Gera um preview inicial ao abrir a tela.
        document.getElementById('btnPreview').click();
    })();
</script>
