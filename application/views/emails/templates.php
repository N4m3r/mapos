<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon">
            <i class="fas fa-envelope-open-text"></i>
        </span>
        <h5>Modelos de E-mail</h5>
    </div>
    <div class="widget-content nopadding">
        <p style="padding: 14px 16px 0; color:#6b7191; margin:0;">
            Configure quais e-mails são enviados, edite o texto/HTML de cada modelo e use as
            <strong>tags</strong> para inserir dados do cliente, da OS e da cobrança automaticamente.
        </p>
        <table class="table table-bordered" style="margin-top: 10px">
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
                        <td colspan="4">Nenhum modelo cadastrado. Rode a migration de e-mail.</td>
                    </tr>
                <?php } ?>
                <?php foreach ($results as $r) { ?>
                    <tr>
                        <td><strong><?= html_escape($r->nome) ?></strong></td>
                        <td><?= html_escape($r->descricao) ?></td>
                        <td style="text-align:center">
                            <?php if ((int) $r->ativo === 1) { ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php } else { ?>
                                <span class="badge badge-warning">Desativado</span>
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

<div class="widget-box">
    <div class="widget-title">
        <span class="icon"><i class="fas fa-palette"></i></span>
        <h5>Layout &amp; CSS global</h5>
    </div>
    <div class="widget-content" style="padding:16px">
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
