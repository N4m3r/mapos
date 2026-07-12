<div class="new122">
    <?php $this->load->view('rh/_subnav', ['ativo' => 'colaboradores']); ?>
    <div class="widget-title" style="margin: 0 0 0">
        <span class="icon"><i class="fas fa-users"></i></span>
        <h5>Colaboradores</h5>
    </div>
    <div class="span12" style="margin-left:0">
        <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eRh')): ?>
            <div class="span3 flexxn" style="display:flex">
                <a href="<?= site_url('rh/adicionarColaborador') ?>" class="button btn btn-mini btn-success" style="max-width:180px">
                    <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2"> Colaborador</span>
                </a>
            </div>
        <?php endif; ?>
        <form class="span9" method="get" action="<?= site_url('rh/colaboradores') ?>" style="display:flex;justify-content:flex-end">
            <div class="span3">
                <input type="text" name="busca" placeholder="Nome, CPF ou cargo..." class="span12" value="<?= htmlspecialchars($busca) ?>">
            </div>
            <div class="span1">
                <button class="button btn btn-mini btn-warning" style="min-width:30px"><span class="button__icon"><i class='bx bx-search-alt'></i></span></button>
            </div>
        </form>
    </div>
    <div class="widget-box">
        <div class="widget-content nopadding tab-content">
            <table id="tabela" class="table table-bordered">
                <thead><tr><th>Nome</th><th>Cargo</th><th>Unidade</th><th>Jornada</th><th>Situação</th><th>Ações</th></tr></thead>
                <tbody>
                <?php if (empty($colaboradores)): ?>
                    <tr><td colspan="6">Nenhum colaborador cadastrado.</td></tr>
                <?php else:
                    $ph = 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="34" height="34"><rect width="34" height="34" rx="17" fill="#eef0f4"/><circle cx="17" cy="13" r="7" fill="#c3c9d4"/><path d="M5 31c0-6 5-9 12-9s12 3 12 9z" fill="#c3c9d4"/></svg>');
                    foreach ($colaboradores as $c): ?>
                    <tr>
                        <td style="white-space:nowrap">
                            <img src="<?= ! empty($c->tem_foto) ? site_url('rh/fotoColaborador/'.$c->id) : $ph ?>"
                                 style="width:34px;height:34px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:8px">
                            <a href="<?= site_url('rh/ficha/'.$c->id) ?>"><?= htmlspecialchars($c->nome) ?></a>
                        </td>
                        <td><?= htmlspecialchars($c->cargo ?: '-') ?></td>
                        <td><?= htmlspecialchars($c->nome_unidade ?: '-') ?></td>
                        <td><?= htmlspecialchars($c->nome_jornada ?: '-') ?></td>
                        <td><?= $c->situacao ? '<span style="color:#16a34a">Ativo</span>' : '<span style="color:#888">Inativo</span>' ?></td>
                        <td>
                            <a href="<?= site_url('rh/espelho/'.$c->id) ?>" class="btn-nwe3" title="Espelho de ponto"><i class="bx bx-calendar-check bx-xs"></i></a>
                            <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eRh')): ?>
                                <a href="<?= site_url('rh/biometria/'.$c->id) ?>" class="btn-nwe3" title="Biometria facial"><i class="bx bx-face bx-xs"></i></a>
                                <a href="<?= site_url('rh/editarColaborador/'.$c->id) ?>" class="btn-nwe3" title="Editar"><i class="bx bx-edit bx-xs"></i></a>
                                <a href="#modal-excluir" role="button" data-toggle="modal" reg="<?= $c->id ?>" class="btn-nwe4" title="Excluir"><i class="bx bx-trash-alt bx-xs"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modal-excluir" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('rh/excluirColaborador') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Excluir Colaborador</h5></div>
        <div class="modal-body"><input type="hidden" id="reg-id" name="id" value=""><h5 style="text-align:center">Deseja realmente excluir?</h5></div>
        <div class="modal-footer" style="display:flex;justify-content:center">
            <button class="button btn btn-warning" data-dismiss="modal"><span class="button__text2">Cancelar</span></button>
            <button class="button btn btn-danger"><span class="button__text2">Excluir</span></button>
        </div>
    </form>
</div>
<script>
$(document).on('click', 'a[reg]', function(){ $('#reg-id').val($(this).attr('reg')); });
</script>
