<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon">
            <i class="fas fa-clipboard-list"></i>
        </span>
        <h5>Formulários de Atendimento</h5>
    </div>
    <div class="widget-content nopadding">
        <p style="padding: 14px 16px 0; color:#6b7191; margin:0;">
            Monte formulários personalizados (texto, área de texto, seleção suspensa e mais) para o
            técnico preencher em cada etapa do atendimento: <strong>ao iniciar</strong>,
            <strong>durante</strong> e <strong>ao finalizar</strong> a atividade.
        </p>
        <div style="padding: 12px 16px 0;">
            <a href="<?= site_url('formularios/adicionar') ?>" class="btn btn-success">
                <i class="bx bx-plus"></i> Novo formulário
            </a>
        </div>
        <table class="table table-bordered" style="margin-top: 10px">
            <thead>
                <tr>
                    <th>Formulário</th>
                    <th style="width:200px">Etapa</th>
                    <th style="width:90px; text-align:center">Campos</th>
                    <th style="width:110px; text-align:center">Status</th>
                    <th style="width:220px; text-align:center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)) { ?>
                    <tr>
                        <td colspan="5">Nenhum formulário cadastrado ainda. Clique em <strong>Novo formulário</strong> para começar.</td>
                    </tr>
                <?php } ?>
                <?php foreach ($results as $r) { ?>
                    <tr>
                        <td>
                            <strong><?= html_escape($r->nome) ?></strong>
                            <?php if (! empty($r->descricao)) { ?>
                                <br><small class="text-muted"><?= html_escape($r->descricao) ?></small>
                            <?php } ?>
                            <?php if ((int) $r->obrigatorio === 1) { ?>
                                <br><span class="badge badge-important">Obrigatório</span>
                            <?php } ?>
                        </td>
                        <td><?= html_escape($etapas[$r->etapa] ?? $r->etapa) ?></td>
                        <td style="text-align:center"><?= (int) ($camposCount[$r->idFormulario] ?? 0) ?></td>
                        <td style="text-align:center">
                            <?php if ((int) $r->ativo === 1) { ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php } else { ?>
                                <span class="badge badge-warning">Desativado</span>
                            <?php } ?>
                        </td>
                        <td style="text-align:center">
                            <a href="<?= site_url('formularios/editar/' . $r->idFormulario) ?>" class="btn btn-primary btn-mini" title="Editar">
                                <i class="bx bx-edit"></i> Editar
                            </a>
                            <a href="<?= site_url('formularios/duplicar/' . $r->idFormulario) ?>" class="btn btn-mini" title="Duplicar">
                                <i class="bx bx-copy"></i>
                            </a>
                            <a href="<?= site_url('formularios/excluir/' . $r->idFormulario) ?>" class="btn btn-danger btn-mini btn-excluir-formulario" title="Excluir">
                                <i class="bx bx-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    $(function () {
        $('.btn-excluir-formulario').on('click', function (e) {
            if (!confirm('Excluir este formulário? As respostas já coletadas não são apagadas, mas o formulário deixará de aparecer no atendimento.')) {
                e.preventDefault();
            }
        });
    });
</script>
