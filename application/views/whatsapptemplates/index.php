<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="fas fa-comment-dots"></i></span>
        <h5>Modelos de WhatsApp</h5>
    </div>
    <div class="widget-content nopadding">
        <p style="padding:14px 16px 0; color:#6b7191; margin:0;">
            Edite as mensagens enviadas por WhatsApp em cada situação. Use as tags entre chaves
            (ex.: <code>{CLIENTE_NOME}</code>) para inserir dados dinâmicos.
        </p>
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
                        <td style="text-align:center">
                            <a href="<?= site_url('whatsapptemplates/editar/' . $t->slug) ?>" class="btn btn-primary btn-mini">
                                <i class="bx bx-edit"></i> Editar
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
