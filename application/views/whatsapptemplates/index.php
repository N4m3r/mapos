<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="fas fa-comment-dots"></i></span>
        <h5>Modelos de WhatsApp</h5>
    </div>
    <div class="widget-content nopadding">
        <div style="padding:14px 16px 0; display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
            <p style="color:#6b7191; margin:0;">
                Edite as mensagens enviadas por WhatsApp em cada situação. Use as tags entre chaves
                (ex.: <code>{CLIENTE_NOME}</code>) para inserir dados dinâmicos. Modelos personalizados
                podem ser selecionados nos gatilhos.
            </p>
            <a href="<?= site_url('whatsapptemplates/novo') ?>" class="button btn btn-success btn-mini" style="white-space:nowrap">
                <span class="button__icon"><i class="bx bx-plus-circle"></i></span><span class="button__text2">Novo modelo</span>
            </a>
        </div>
        <table class="table table-bordered" style="margin-top:10px">
            <thead>
                <tr>
                    <th>Modelo</th>
                    <th style="width:110px; text-align:center">Status</th>
                    <th style="width:110px; text-align:center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($templates)) { ?>
                    <tr><td colspan="3">Nenhum modelo cadastrado. Rode <code>updates/update_whatsapp_templates.sql</code>.</td></tr>
                <?php } ?>
                <?php foreach ($templates as $t) { ?>
                    <tr>
                        <td>
                            <strong><?= html_escape($t->nome) ?></strong><br>
                            <span style="color:#8a90a6; font-size:12px"><?= html_escape($t->descricao) ?></span>
                        </td>
                        <td style="text-align:center">
                            <?php if ((int) $t->ativo === 1) { ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php } else { ?>
                                <span class="badge badge-warning">Desativado</span>
                            <?php } ?>
                        </td>
                        <td style="text-align:center; white-space:nowrap">
                            <a href="<?= site_url('whatsapptemplates/editar/' . $t->slug) ?>" class="btn btn-primary btn-mini">
                                <i class="bx bx-edit"></i> Editar
                            </a>
                            <?php if (! in_array($t->slug, Whatsapp_templates_model::slugsCore(), true)) { ?>
                                <a href="<?= site_url('whatsapptemplates/excluir/' . $t->slug) ?>" class="btn btn-danger btn-mini"
                                   onclick="return confirm('Excluir o modelo \'<?= html_escape($t->nome) ?>\'?');">
                                    <i class="bx bx-trash"></i>
                                </a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
