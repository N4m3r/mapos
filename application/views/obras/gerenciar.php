<link rel="stylesheet" href="<?= base_url() ?>assets/css/obras.css">

<?php $this->load->view('obras/_nav'); ?>

<div class="new122" style="margin-top:0">
    <div class="widget-title" style="margin: -6px 0 8px">
        <span class="icon"><i class="fas fa-hard-hat"></i></span>
        <h5>Obras</h5>
    </div>

    <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'cObras')) { ?>
        <a href="<?= site_url('obras/adicionar') ?>" class="button btn btn-mini btn-success" style="max-width: 160px">
            <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Nova Obra</span></a>
    <?php } ?>

    <form action="<?= site_url('obras/gerenciar') ?>" method="get" style="margin: 10px 0; display:flex; gap:8px; flex-wrap:wrap">
        <input type="text" name="pesquisa" placeholder="Nome, código ou cliente" value="<?= html_escape($this->input->get('pesquisa')) ?>" style="max-width:260px">
        <select name="status" style="width:auto">
            <option value="">Todos os status</option>
            <?php foreach (['planejamento' => 'Planejamento', 'em_execucao' => 'Em execução', 'paralisada' => 'Paralisada', 'concluida' => 'Concluída', 'entregue' => 'Entregue', 'cancelada' => 'Cancelada'] as $k => $v): ?>
                <option value="<?= $k ?>" <?= $this->input->get('status') === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button btn btn-mini btn-inverse" type="submit"><span class="button__icon"><i class='bx bx-search'></i></span><span class="button__text2">Filtrar</span></button>
    </form>

    <div class="widget-box">
        <div class="widget-content nopadding tab-content">
            <table id="tabela" class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Obra</th>
                        <th>Cliente</th>
                        <th>Responsável</th>
                        <th>Status</th>
                        <th>Progresso</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! $results) {
                        echo '<tr><td colspan="8">Nenhuma obra cadastrada.</td></tr>';
                    } ?>
                    <?php foreach ($results as $r): ?>
                        <tr>
                            <td><?= $r->idObra ?></td>
                            <td><?= html_escape($r->codigo) ?></td>
                            <td><?= html_escape($r->nome) ?></td>
                            <td><?= html_escape($r->nomeCliente) ?></td>
                            <td><?= html_escape($r->responsavel) ?></td>
                            <td><span class="label"><?= ucfirst(str_replace('_', ' ', $r->status)) ?></span></td>
                            <td style="min-width:120px">
                                <div class="obra-progress"><div class="obra-progress-bar" style="width:<?= (float) $r->percentual_concluido ?>%"></div></div>
                                <small><?= number_format((float) $r->percentual_concluido, 1, ',', '.') ?>%</small>
                            </td>
                            <td>
                                <a style="margin-right:1%" href="<?= site_url('obras/visualizar/' . $r->idObra) ?>" class="btn-nwe" title="Abrir"><i class="bx bx-show bx-xs"></i></a>
                                <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eObras')) { ?>
                                    <a style="margin-right:1%" href="<?= site_url('obras/editar/' . $r->idObra) ?>" class="btn-nwe3" title="Editar"><i class="bx bx-edit bx-xs"></i></a>
                                <?php } ?>
                                <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'dObras')) { ?>
                                    <a href="#modal-excluir" role="button" data-toggle="modal" obra="<?= $r->idObra ?>" class="btn-nwe4" title="Excluir"><i class="bx bx-trash-alt bx-xs"></i></a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->pagination->create_links(); ?>

<div id="modal-excluir" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('obras/excluir') ?>" method="post">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h5>Excluir Obra</h5>
        </div>
        <div class="modal-body">
            <input type="hidden" id="idObra" name="idObra" value="" />
            <h5 style="text-align:center">Excluir esta obra e todos os seus dados (etapas, RDO, medições, material)?</h5>
        </div>
        <div class="modal-footer" style="display:flex;justify-content:center">
            <button class="button btn btn-warning" data-dismiss="modal" type="button"><span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Cancelar</span></button>
            <button class="button btn btn-danger"><span class="button__icon"><i class='bx bx-trash'></i></span><span class="button__text2">Excluir</span></button>
        </div>
    </form>
</div>

<script>
    $(function () {
        $(document).on('click', 'a[obra]', function () { $('#idObra').val($(this).attr('obra')); });
    });
</script>
