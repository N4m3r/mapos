<?php
$tokenName = $this->security->get_csrf_token_name();
$tokenHash = $this->security->get_csrf_hash();
?>
<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="fas fa-palette"></i></span>
        <h5>Layout &amp; CSS global dos e-mails</h5>
    </div>
    <div class="widget-content">
        <p style="color:#6b7191">
            O HTML abaixo envolve o conteúdo de todos os e-mails. Use <code>{{conteudo}}</code> onde o corpo
            do modelo deve aparecer e <code>{{css}}</code> onde o CSS deve ser injetado. Também é possível usar as
            tags da empresa, por exemplo <code>{{empresa_nome}}</code> e <code>{{empresa_logo_img}}</code>.
        </p>

        <form action="<?= site_url('emailtemplates/salvarLayout') ?>" method="post" id="formLayout">
            <input type="hidden" name="<?= $tokenName ?>" value="<?= $tokenHash ?>">

            <div class="row-fluid">
                <div class="span6">
                    <div class="control-group">
                        <label class="control-label" for="email_layout">HTML do layout</label>
                        <div class="controls">
                            <textarea id="email_layout" name="email_layout" rows="20" class="span12" style="font-family:'Courier New', monospace; font-size:13px;"><?= html_escape($layout) ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="span6">
                    <div class="control-group">
                        <label class="control-label" for="email_css">CSS</label>
                        <div class="controls">
                            <textarea id="email_css" name="email_css" rows="20" class="span12" style="font-family:'Courier New', monospace; font-size:13px;"><?= html_escape($css) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button type="submit" class="button btn btn-success">
                    <span class="button__icon"><i class="bx bx-save"></i></span>
                    <span class="button__text2">Salvar layout</span>
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

        <div class="row-fluid" style="margin-top:18px;">
            <div class="span12">
                <h5>Pré-visualização</h5>
                <iframe id="previewFrame" name="previewFrame" style="width:100%; height:560px; border:1px solid #e2e6f0; border-radius:8px; background:#fff;"></iframe>
            </div>
        </div>
    </div>
</div>

<form action="<?= site_url('emailtemplates/preview') ?>" method="post" target="previewFrame" id="formPreview" style="display:none;">
    <input type="hidden" name="<?= $tokenName ?>" value="<?= $tokenHash ?>">
    <input type="hidden" name="email_layout" id="previewLayout" value="">
    <input type="hidden" name="email_css" id="previewCss" value="">
</form>

<script type="text/javascript">
    (function() {
        document.getElementById('btnPreview').addEventListener('click', function() {
            document.getElementById('previewLayout').value = document.getElementById('email_layout').value;
            document.getElementById('previewCss').value = document.getElementById('email_css').value;
            document.getElementById('formPreview').submit();
        });
        document.getElementById('btnPreview').click();
    })();
</script>
