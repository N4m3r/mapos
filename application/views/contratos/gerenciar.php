<?php
$fmt = function ($v, $d = 2) { return number_format((float) $v, $d, ',', '.'); };
$perm = function ($p) { return $this->permission->checkPermission($this->session->userdata('permissao'), $p); };
$tipos = ['mensal' => 'Mensal', 'trimestral' => 'Trimestral', 'semestral' => 'Semestral', 'anual' => 'Anual'];
$statusLabels = ['ativo' => 'Ativo', 'suspenso' => 'Suspenso', 'encerrado' => 'Encerrado'];
$statusCor = ['ativo' => 'sucesso', 'suspenso' => 'alerta', 'encerrado' => 'perigo'];
$f = isset($filtros) ? $filtros : ['pesquisa' => '', 'status' => ''];
?>

<div class="new122" style="margin-top:0">
    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="bx bx-file"></i> Contratos</h1>
            <p class="ui-subtitulo">Contratos de manutenção, valores, vigência e SLA. Filtre por descrição, código ou cliente.</p>
        </div>
    </div>

    <?php $this->load->view('contratos/_nav', ['contrato_menu' => 'contratos']); ?>

    <div class="widget-box">
        <div class="widget-content">
            <form action="<?= site_url('contratos/gerenciar') ?>" method="get" class="ui-filtros">
                <div class="ui-campo">
                    <label for="filtro-pesquisa">Buscar</label>
                    <input type="text" id="filtro-pesquisa" name="pesquisa" value="<?= html_escape($f['pesquisa']) ?>" placeholder="Descrição, código ou cliente">
                </div>
                <div class="ui-campo">
                    <label for="filtro-status">Status</label>
                    <select id="filtro-status" name="status">
                        <option value="">Todos os status</option>
                        <?php foreach ($statusLabels as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $f['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ui-campo-acao">
                    <button class="button btn btn-mini btn-primary"><span class="button__icon"><i class='bx bx-search'></i></span><span class="button__text2">Filtrar</span></button>
                </div>
            </form>

            <div class="ui-tabela-rolagem">
            <table id="tabela" class="table table-bordered">
                <thead>
                    <tr><th>Código</th><th>Descrição</th><th>Cliente</th><th>Tipo</th><th>Valor</th><th>Venc.</th><th>Status</th><th>OS</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (! $results) echo '<tr><td colspan="9"><div class="ui-vazio"><i class="bx bx-file"></i><strong>Nenhum contrato encontrado.</strong>Ajuste os filtros ou cadastre um novo contrato.</div></td></tr>'; ?>
                    <?php foreach ($results as $c): ?>
                        <tr>
                            <td><?= html_escape($c->codigo ?: '#' . $c->idContrato) ?></td>
                            <td><?= html_escape($c->descricao) ?></td>
                            <td><?= html_escape($c->nomeCliente ?: '—') ?></td>
                            <td><?= $tipos[$c->tipo] ?? html_escape($c->tipo) ?></td>
                            <td>R$ <?= $fmt($c->valor) ?></td>
                            <td>Dia <?= (int) $c->dia_vencimento ?></td>
                            <td><span class="ui-selo <?= $statusCor[$c->status] ?? '' ?>"><?= $statusLabels[$c->status] ?? html_escape($c->status) ?></span></td>
                            <td><?= (int) $c->total_os ?></td>
                            <td>
                                <div class="ui-acoes-linha">
                                <a href="<?= site_url('contratos/visualizar/' . $c->idContrato) ?>" class="btn-nwe" title="Visualizar" aria-label="Visualizar contrato"><i class="bx bx-show"></i></a>
                                <?php if ($perm('eContrato')): ?>
                                    <a href="<?= site_url('contratos/editar/' . $c->idContrato) ?>" class="btn-nwe3" title="Editar" aria-label="Editar contrato"><i class="bx bx-edit"></i></a>
                                <?php endif; ?>
                                <?php if ($perm('dContrato')): ?>
                                    <a href="#modal-excluir" role="button" data-toggle="modal" data-id="<?= $c->idContrato ?>" class="btn-nwe4 btn-excluir" title="Excluir" aria-label="Excluir contrato"><i class="bx bx-trash"></i></a>
                                <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
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

<script nonce="<?= csp_nonce() ?>">
    $(function () {
        $(document).on('click', '.btn-excluir', function () {
            $('#idContrato').val($(this).data('id'));
        });
    });
</script>
