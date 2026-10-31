<?php
$podeEditar = $this->permission->checkPermission($this->session->userdata('permissao'), 'eRh');
$filtrando = ($busca !== null && $busca !== '') || $filtro_situacao !== '';
?>
<div class="new122">
    <?php $this->load->view('rh/_subnav', ['ativo' => 'colaboradores']); ?>
    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="bx bx-group"></i> Colaboradores</h1>
            <p class="ui-subtitulo">Cadastro e situação dos colaboradores.</p>
        </div>
        <?php if ($podeEditar): ?>
            <a href="<?= site_url('rh/adicionarColaborador') ?>" class="button btn btn-success">
                <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2"> Novo colaborador</span>
            </a>
        <?php endif; ?>
    </div>
    <form method="get" action="<?= site_url('rh/colaboradores') ?>" class="ui-filtros">
        <div class="ui-campo">
            <label for="filtro-busca">Buscar</label>
            <input type="text" id="filtro-busca" name="busca" placeholder="Nome, CPF ou cargo" value="<?= html_escape($busca) ?>">
        </div>
        <div class="ui-campo">
            <label for="filtro-situacao">Situação</label>
            <select name="situacao" id="filtro-situacao" data-ao-mudar="enviar">
                <option value="" <?= $filtro_situacao===''?'selected':'' ?>>Todos</option>
                <option value="1" <?= $filtro_situacao==='1'?'selected':'' ?>>Ativos</option>
                <option value="0" <?= $filtro_situacao==='0'?'selected':'' ?>>Inativos e desligados</option>
            </select>
        </div>
        <div class="ui-campo-acao">
            <button class="button btn btn-warning"><span class="button__icon"><i class='bx bx-search-alt'></i></span><span class="button__text2"> Buscar</span></button>
            <?php if ($filtrando): ?>
                <a href="<?= site_url('rh/colaboradores') ?>" class="button btn"><span class="button__text2">Limpar</span></a>
            <?php endif; ?>
        </div>
    </form>
    <div class="widget-box">
        <div class="widget-content nopadding tab-content">
            <?php if (empty($colaboradores)): ?>
                <div class="ui-vazio">
                    <i class='bx bx-user-plus'></i>
                    <strong><?= $filtrando ? 'Nenhum colaborador encontrado' : 'Nenhum colaborador cadastrado' ?></strong>
                    <?= $filtrando ? 'Tente outra busca ou limpe os filtros.' : 'Cadastre o primeiro colaborador para começar a controlar o ponto.' ?>
                    <?php if ($podeEditar && ! $filtrando): ?>
                        <div style="margin-top:10px"><a href="<?= site_url('rh/adicionarColaborador') ?>" class="button btn btn-success"><span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2"> Novo colaborador</span></a></div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <table id="tabela" class="table table-bordered">
                <thead><tr><th>Nome</th><th>Cargo</th><th>Unidade</th><th>Jornada</th><th>Situação</th><th>Ações</th></tr></thead>
                <tbody>
                <?php
                    $ph = 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="34" height="34"><rect width="34" height="34" rx="17" fill="#eef0f4"/><circle cx="17" cy="13" r="7" fill="#c3c9d4"/><path d="M5 31c0-6 5-9 12-9s12 3 12 9z" fill="#c3c9d4"/></svg>');
                    $voltarLista = '?voltar=' . rawurlencode('rh/colaboradores');
                    foreach ($colaboradores as $c): ?>
                    <tr>
                        <td style="white-space:nowrap">
                            <img src="<?= ! empty($c->tem_foto) ? site_url('rh/fotoColaborador/'.$c->id) : $ph ?>" alt=""
                                 style="width:34px;height:34px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:8px">
                            <a href="<?= site_url('rh/ficha/'.$c->id.$voltarLista) ?>"><?= html_escape($c->nome) ?></a>
                        </td>
                        <td><?= html_escape($c->cargo ?: '-') ?></td>
                        <td><?= html_escape($c->nome_unidade ?: '-') ?></td>
                        <td><?= html_escape($c->nome_jornada ?: '-') ?></td>
                        <td><?= $c->situacao ? '<span class="ui-selo sucesso">Ativo</span>' : '<span class="ui-selo neutro">Inativo</span>' ?></td>
                        <td>
                            <a href="<?= site_url('rh/espelho/'.$c->id.'/'.date('Y-m').$voltarLista) ?>" class="btn-nwe3" title="Espelho de ponto" aria-label="Espelho de ponto"><i class="bx bx-calendar-check bx-xs"></i></a>
                            <?php if ($podeEditar): ?>
                                <a href="<?= site_url('rh/biometria/'.$c->id) ?>" class="btn-nwe3" title="Biometria facial" aria-label="Biometria facial"><i class="bx bx-face bx-xs"></i></a>
                                <a href="<?= site_url('rh/editarColaborador/'.$c->id.$voltarLista) ?>" class="btn-nwe3" title="Editar" aria-label="Editar"><i class="bx bx-edit bx-xs"></i></a>
                                <a href="#modal-excluir" role="button" data-toggle="modal" reg="<?= (int) $c->id ?>" data-nome="<?= html_escape($c->nome) ?>" class="btn-nwe4" title="Excluir" aria-label="Excluir"><i class="bx bx-trash-alt bx-xs"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="modal-excluir" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('rh/excluirColaborador') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Fechar">×</button><h5>Excluir colaborador</h5></div>
        <div class="modal-body">
            <input type="hidden" id="reg-id" name="id" value="">
            <h5 style="text-align:center">Deseja realmente excluir <strong id="reg-nome">este colaborador</strong>?</h5>
            <p style="text-align:center" class="ui-subtitulo">Para manter o histórico, prefira desligar o colaborador na tela de edição.</p>
        </div>
        <div class="modal-footer" style="display:flex;justify-content:center">
            <button type="button" class="button btn btn-warning" data-dismiss="modal"><span class="button__text2">Cancelar</span></button>
            <button class="button btn btn-danger"><span class="button__text2">Excluir</span></button>
        </div>
    </form>
</div>
<script nonce="<?= csp_nonce() ?>">
$(document).on('click', 'a[reg]', function(){ $('#reg-id').val($(this).attr('reg')); $('#reg-nome').text($(this).attr('data-nome') || 'este colaborador'); });
</script>
