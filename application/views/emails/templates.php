<div class="ui-cabecalho">
    <div>
        <h1 class="ui-titulo"><i class="bx bx-envelope-open"></i> Modelos de E-mail</h1>
        <p class="ui-subtitulo">Textos dos e-mails automáticos, com ativação e edição de cada modelo.</p>
    </div>
</div>
<div class="widget-box">
    <div class="widget-content nopadding">
        <div class="ui-dica" style="margin:14px 16px 0">
            <i class="bx bx-info-circle"></i>
            <div>
                Configure quais e-mails são enviados, edite o texto/HTML de cada modelo e use as
                <strong>tags</strong> para inserir dados do cliente, da OS e da cobrança automaticamente.
            </div>
        </div>
        <div class="ui-tabela-rolagem" style="margin-top: 10px">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Modelo</th>
                    <th>Quando é enviado</th>
                    <th style="width:110px; text-align:center">Status</th>
                    <th style="width:110px; text-align:center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)) { ?>
                    <tr>
                        <td colspan="4"><div class="ui-vazio"><i class="bx bx-envelope-open"></i><strong>Nenhum modelo cadastrado</strong>Rode a migration de e-mail para criar os modelos padrão.</div></td>
                    </tr>
                <?php } ?>
                <?php foreach ($results as $r) { ?>
                    <tr>
                        <td><strong><?= html_escape($r->nome) ?></strong></td>
                        <td><?= html_escape($r->descricao) ?></td>
                        <td style="text-align:center">
                            <?php if ((int) $r->ativo === 1) { ?>
                                <span class="ui-selo sucesso">Ativo</span>
                            <?php } else { ?>
                                <span class="ui-selo alerta">Desativado</span>
                            <?php } ?>
                        </td>
                        <td style="text-align:center">
                            <a href="<?= site_url('emailtemplates/editar/' . $r->id) ?>" class="btn btn-primary btn-mini" title="Editar modelo">
                                <i class="bx bx-edit"></i> Editar
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<div class="widget-box">
    <div class="widget-content" style="padding:16px">
        <div class="ui-secao">
            <h4><i class="bx bx-palette"></i> Layout &amp; CSS global</h4>
        </div>
        <p style="color:#6b7191">
            O layout global define o cabeçalho, o rodapé e o CSS aplicados a <strong>todos</strong>
            os e-mails, deixando o envio com a identidade visual da empresa.
        </p>
        <a href="<?= site_url('emailtemplates/editarLayout') ?>" class="button btn btn-primary">
            <span class="button__icon"><i class="bx bx-palette"></i></span>
            <span class="button__text2">Editar layout e CSS</span>
        </a>
    </div>
</div>
