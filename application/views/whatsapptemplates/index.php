<div class="ui-cabecalho">
    <div>
        <h1 class="ui-titulo"><i class="bx bx-comment-dots"></i> Modelos de WhatsApp</h1>
        <p class="ui-subtitulo">Mensagens enviadas por WhatsApp em cada situação.</p>
    </div>
    <div class="ui-acoes">
        <a href="<?= site_url('whatsapptemplates/novo') ?>" class="button btn btn-success">
            <span class="button__icon"><i class="bx bx-plus-circle"></i></span><span class="button__text2">Novo modelo</span>
        </a>
    </div>
</div>
<div class="widget-box">
    <div class="widget-content nopadding">
        <div class="ui-dica" style="margin:14px 16px 0">
            <i class="bx bx-info-circle"></i>
            <div>
                Edite as mensagens enviadas por WhatsApp em cada situação. Use as tags entre chaves
                (ex.: <code>{CLIENTE_NOME}</code>) para inserir dados dinâmicos. Modelos personalizados
                podem ser selecionados nos gatilhos.
            </div>
        </div>
        <div class="ui-tabela-rolagem" style="margin-top:10px">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Modelo</th>
                    <th style="width:110px; text-align:center">Status</th>
                    <th style="width:110px; text-align:center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($templates)) { ?>
                    <tr><td colspan="3"><div class="ui-vazio"><i class="bx bx-comment-dots"></i><strong>Nenhum modelo cadastrado</strong>Rode <code>updates/update_whatsapp_templates.sql</code> para criar os modelos padrão.</div></td></tr>
                <?php } ?>
                <?php foreach ($templates as $t) { ?>
                    <tr>
                        <td>
                            <strong><?= html_escape($t->nome) ?></strong><br>
                            <span style="color:#8a90a6; font-size:12px"><?= html_escape($t->descricao) ?></span>
                        </td>
                        <td style="text-align:center">
                            <?php if ((int) $t->ativo === 1) { ?>
                                <span class="ui-selo sucesso">Ativo</span>
                            <?php } else { ?>
                                <span class="ui-selo alerta">Desativado</span>
                            <?php } ?>
                        </td>
                        <td style="text-align:center; white-space:nowrap">
                            <a href="<?= site_url('whatsapptemplates/editar/' . $t->slug) ?>" class="btn btn-primary btn-mini">
                                <i class="bx bx-edit"></i> Editar
                            </a>
                            <?php if (! in_array($t->slug, Whatsapp_templates_model::slugsCore(), true)) { ?>
                                <a href="<?= site_url('whatsapptemplates/excluir/' . $t->slug) ?>" class="btn btn-danger btn-mini"
                                   data-confirmar="Excluir o modelo '<?= html_escape($t->nome) ?>'?" title="Excluir modelo" aria-label="Excluir modelo">
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
</div>
