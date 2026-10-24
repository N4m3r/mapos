<?php
$fmt = function ($v, $d = 2) { return number_format((float) $v, $d, ',', '.'); };
$perm = function ($p) { return $this->permission->checkPermission($this->session->userdata('permissao'), $p); };
$tipos = ['mensal' => 'Mensal', 'trimestral' => 'Trimestral', 'semestral' => 'Semestral', 'anual' => 'Anual'];
$statusLabels = ['ativo' => 'Ativo', 'suspenso' => 'Suspenso', 'encerrado' => 'Encerrado'];
$statusCor = ['ativo' => 'label-success', 'suspenso' => 'label-warning', 'encerrado' => 'label-important'];
$f = isset($filtros) ? $filtros : ['pesquisa' => '', 'status' => ''];
?>

<div class="new122" style="margin-top:0">
    <div class="widget-title" style="margin:-6px 0 8px">
        <span class="icon"><i class="fas fa-file-signature"></i></span>
        <h5>Contratos</h5>
    </div>

    <?php $this->load->view('contratos/_nav', ['contrato_menu' => 'contratos']); ?>

    <div class="widget-box">
        <div class="widget-content">
            <form action="<?= site_url('contratos/gerenciar') ?>" method="get" class="form-inline" style="margin-bottom:8px">
                <input type="text" name="pesquisa" value="<?= html_escape($f['pesquisa']) ?>" placeholder="Descrição, código ou cliente" style="width:260px">
                <select name="status" style="width:auto">
                    <option value="">Todos os status</option>
                    <?php foreach ($statusLabels as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $f['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button btn btn-mini btn-primary"><span class="button__icon"><i class='bx bx-search'></i></span><span class="button__text2">Filtrar</span></button>
            </form>

            <table id="tabela" class="table table-bordered">
                <thead>
                    <tr><th>Código</th><th>Descrição</th><th>Cliente</th><th>Tipo</th><th>Valor</th><th>Venc.</th><th>Status</th><th>OS</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (! $results) echo '<tr><td colspan="9">Nenhum contrato encontrado.</td></tr>'; ?>
                    <?php foreach ($results as $c): ?>
                        <tr>
                            <td><?= html_escape($c->codigo ?: '#' . $c->idContrato) ?></td>
                            <td><?= html_escape($c->descricao) ?></td>
                            <td><?= html_escape($c->nomeCliente ?: '—') ?></td>
                            <td><?= $tipos[$c->tipo] ?? html_escape($c->tipo) ?></td>
                            <td>R$ <?= $fmt($c->valor) ?></td>
                            <td>Dia <?= (int) $c->dia_vencimento ?></td>
                            <td><span class="label <?= $statusCor[$c->status] ?? '' ?>"><?= $statusLabels[$c->status] ?? html_escape($c->status) ?></span></td>
                            <td><?= (int) $c->total_os ?></td>
                            <td style="white-space:nowrap">
                                <a href="<?= site_url('contratos/visualizar/' . $c->idContrato) ?>" class="btn-nwe" title="Visualizar"><i class="bx bx-show"></i></a>
                                <?php if ($perm('eContrato')): ?>
                                    <a href="<?= site_url('contratos/editar/' . $c->idContrato) ?>" class="btn-nwe3" title="Editar"><i class="bx bx-edit"></i></a>
                                <?php endif; ?>
                                <?php if ($perm('dContrato')): ?>
                                    <a href="#modal-excluir" role="button" data-toggle="modal" data-id="<?= $c->idContrato ?>" class="btn-nwe4 btn-excluir" title="Excluir"><i class="bx bx-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?= $this->pagination->create_links(); ?>
        </div>
    </div>
</div>

<!-- Modal excluir -->
<div id="modal-excluir" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('contratos/excluir') ?>" method="post">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h5>Excluir Contrato</h5>
        </div>
        <div class="modal-body">
            <input type="hidden" id="idContrato" name="idContrato" value="">
            <p style="text-align:center">Deseja realmente excluir este contrato? As OS vinculadas serão apenas desvinculadas.</p>
        </div>
        <div class="modal-footer" style="display:flex;justify-content:center">
            <button type="button" class="button btn btn-warning" data-dismiss="modal"><span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Cancelar</span></button>
            <button class="button btn btn-danger"><span class="button__icon"><i class='bx bx-trash'></i></span><span class="button__text2">Excluir</span></button>
        </div>
    </form>
</div>

<script>
    $(function () {
        $(document).on('click', '.btn-excluir', function () {
            $('#idContrato').val($(this).data('id'));
        });
    });
</script>
