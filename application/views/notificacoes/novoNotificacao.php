<?php
$tokenName = $this->security->get_csrf_token_name();
$tokenHash = $this->security->get_csrf_hash();

$canais = Notification_triggers_model::canaisDisponiveis();
$destinatarios = Notification_triggers_model::destinatariosDisponiveis();
$blocos = Notification_triggers_model::blocosDisponiveis();
$anexos = Notification_triggers_model::anexosDisponiveis();
?>
<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="fas fa-bolt"></i></span>
        <h5>Novo gatilho de notificação</h5>
    </div>
    <div class="widget-content">
        <p style="color:#6b7191; margin-top:0">
            Escolha um evento que o sistema sabe disparar e configure por onde e para quem notificar.
            É possível ter mais de um gatilho para o mesmo evento (no WhatsApp, todos os ativos disparam).
        </p>

        <form action="<?= site_url('notificacoes/criar') ?>" method="post">
            <input type="hidden" name="<?= $tokenName ?>" value="<?= $tokenHash ?>">

            <div class="control-group">
                <label class="control-label" for="evento">Evento *</label>
                <div class="controls">
                    <select id="evento" name="evento" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($eventos as $slug => $ev) { ?>
                            <option value="<?= html_escape($slug) ?>"><?= html_escape($ev['grupo'] . ' — ' . $ev['nome']) ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="nome">Nome do gatilho</label>
                <div class="controls">
                    <input type="text" id="nome" name="nome" placeholder="Ex.: WhatsApp para o cliente ao finalizar">
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="ativo" value="1" checked>
                    Gatilho ativo
                </label>
            </div>

            <div class="row-fluid">
                <div class="span4">
                    <fieldset style="border:1px solid #e2e6f0; border-radius:8px; padding:12px 14px;">
                        <legend style="font-size:14px; font-weight:700; color:#1e3a8a; width:auto; padding:0 6px;">Por onde disparar</legend>
                        <?php foreach ($canais as $valor => $rotulo) { ?>
                            <label style="display:block; margin-bottom:6px;">
                                <input type="checkbox" name="canais[]" value="<?= $valor ?>" <?= $valor === 'whatsapp' ? 'checked' : '' ?>>
                                <?= html_escape($rotulo) ?>
                            </label>
                        <?php } ?>
                    </fieldset>
                </div>

                <div class="span4">
                    <fieldset style="border:1px solid #e2e6f0; border-radius:8px; padding:12px 14px;">
                        <legend style="font-size:14px; font-weight:700; color:#1e3a8a; width:auto; padding:0 6px;">Para quem</legend>
                        <?php foreach ($destinatarios as $valor => $rotulo) { ?>
                            <label style="display:block; margin-bottom:6px;">
                                <input type="checkbox" name="destinatarios[]" value="<?= $valor ?>" <?= $valor === 'cliente' ? 'checked' : '' ?>>
                                <?= html_escape($rotulo) ?>
                            </label>
                        <?php } ?>
                    </fieldset>
                </div>

                <div class="span4">
                    <fieldset style="border:1px solid #e2e6f0; border-radius:8px; padding:12px 14px;">
                        <legend style="font-size:14px; font-weight:700; color:#1e3a8a; width:auto; padding:0 6px;">Anexos (e-mail)</legend>
                        <?php foreach ($anexos as $valor => $rotulo) { ?>
                            <label style="display:block; margin-bottom:6px;">
                                <input type="checkbox" name="anexos[]" value="<?= $valor ?>">
                                <?= html_escape($rotulo) ?>
                            </label>
                        <?php } ?>
                    </fieldset>
                </div>
            </div>

            <?php if (! empty($whatsappTemplates)) { ?>
                <div class="control-group" style="margin-top:10px">
                    <label class="control-label" for="whatsapp_template">Modelo de mensagem (WhatsApp)</label>
                    <div class="controls">
                        <select id="whatsapp_template" name="whatsapp_template">
                            <option value="">— padrão da OS —</option>
                            <?php foreach ($whatsappTemplates as $wt) { ?>
                                <option value="<?= html_escape($wt->slug) ?>"><?= html_escape($wt->nome) ?></option>
                            <?php } ?>
                        </select>
                        <span class="help-inline">Mensagem usada no WhatsApp deste gatilho. <a href="<?= site_url('whatsapptemplates') ?>" target="_blank">Gerenciar modelos</a>.</span>
                    </div>
                </div>
            <?php } ?>

            <div class="control-group" style="margin-top:10px">
                <fieldset style="border:1px solid #e2e6f0; border-radius:8px; padding:12px 14px;">
                    <legend style="font-size:14px; font-weight:700; color:#1e3a8a; width:auto; padding:0 6px;">Grupos de WhatsApp (opcional)</legend>
                    <p style="color:#6b7191; font-size:12px; margin:0 0 8px">Dispara a mensagem também para os grupos selecionados (requer canal WhatsApp).</p>
                    <button type="button" id="btnCarregarGrupos" class="btn btn-mini btn-primary">
                        <i class="bx bx-download"></i> Carregar grupos do WhatsApp
                    </button>
                    <span id="gruposMsg" style="margin-left:8px; font-size:12px"></span>
                    <div id="gruposContainer" style="margin-top:10px;">
                        <span style="color:#8a90a6; font-size:12px">Nenhum grupo selecionado. Clique em "Carregar grupos do WhatsApp".</span>
                    </div>
                </fieldset>
            </div>

            <div class="control-group" style="margin-top:10px">
                <fieldset style="border:1px solid #e2e6f0; border-radius:8px; padding:12px 14px;">
                    <legend style="font-size:14px; font-weight:700; color:#1e3a8a; width:auto; padding:0 6px;">O que a notificação de OS deve conter</legend>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:4px 16px;">
                        <?php foreach ($blocos as $valor => $rotulo) { ?>
                            <label style="display:block; margin-bottom:6px;">
                                <input type="checkbox" name="blocos[]" value="<?= $valor ?>" checked>
                                <?= html_escape($rotulo) ?>
                            </label>
                        <?php } ?>
                    </div>
                    <span class="help-block" style="font-size:11px">Aplica-se apenas aos eventos de Ordem de Serviço.</span>
                </fieldset>
            </div>

            <?php if (! empty($templates)) { ?>
                <div class="control-group" style="margin-top:10px">
                    <label class="control-label" for="template_slug">Modelo de e-mail usado</label>
                    <div class="controls">
                        <select id="template_slug" name="template_slug">
                            <option value="">— nenhum (usa o padrão) —</option>
                            <?php foreach ($templates as $slug => $nome) { ?>
                                <option value="<?= html_escape($slug) ?>"><?= html_escape($nome) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            <?php } ?>

            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
                <button type="submit" class="button btn btn-success">
                    <span class="button__icon"><i class="bx bx-save"></i></span>
                    <span class="button__text2">Criar gatilho</span>
                </button>
                <a href="<?= site_url('notificacoes') ?>" class="button btn btn-warning">
                    <span class="button__icon"><i class="bx bx-arrow-back"></i></span>
                    <span class="button__text2">Voltar</span>
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    $(function () {
        function escapa(t) { return $('<div>').text(t == null ? '' : t).html(); }
        $('#btnCarregarGrupos').on('click', function () {
            var $btn = $(this).prop('disabled', true);
            $('#gruposMsg').html('<i class="bx bx-loader bx-spin"></i> Carregando...');
            $.ajax({ url: '<?= site_url('whatsapp/grupos') ?>', type: 'GET', dataType: 'json' })
                .done(function (d) {
                    if (!d.result) { $('#gruposMsg').html('<span style="color:#b94a48">' + escapa(d.mensagem || 'Falha.') + '</span>'); return; }
                    var marcados = $('#gruposContainer input:checked').map(function () { return this.value; }).get();
                    var grupos = d.grupos || [];
                    var html = '';
                    grupos.forEach(function (g) {
                        var ck = marcados.indexOf(g.id) !== -1 ? 'checked' : '';
                        html += '<label style="display:block;margin-bottom:6px;"><input type="checkbox" name="whatsapp_grupos[]" value="' + escapa(g.id) + '" ' + ck + '> ' + escapa(g.nome || g.id) + ' <small style="color:#8a90a6">' + escapa(g.id) + '</small></label>';
                    });
                    if (!html) { html = '<span style="color:#8a90a6; font-size:12px">Nenhum grupo encontrado nesta instância.</span>'; }
                    $('#gruposContainer').html(html);
                    $('#gruposMsg').html('<span style="color:green">' + grupos.length + ' grupo(s) carregado(s).</span>');
                })
                .fail(function (xhr) { var m = 'Falha ao carregar grupos.'; try { m = JSON.parse(xhr.responseText).mensagem || m; } catch (e) {} $('#gruposMsg').html('<span style="color:#b94a48">' + escapa(m) + '</span>'); })
                .always(function () { $btn.prop('disabled', false); });
        });
    });
</script>
