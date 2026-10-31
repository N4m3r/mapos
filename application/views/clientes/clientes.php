<style>
    select {
        width: 70px;
    }
</style>
<div class="new122">
    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="fas fa-user"></i> Clientes</h1>
            <p class="ui-subtitulo">Clientes e fornecedores cadastrados. Busque, visualize ou edite um registro.</p>
        </div>
        <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'aCliente')) { ?>
            <div class="ui-acoes">
                <a href="<?= base_url() ?>index.php/clientes/adicionar" class="button btn btn-mini btn-success">
                    <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">
                        Cliente / Fornecedor
                    </span>
                </a>
            </div>
        <?php } ?>
    </div>

    <form class="ui-filtros" method="get" action="<?= base_url() ?>index.php/clientes">
        <div class="ui-campo">
            <label for="pesquisa">Buscar</label>
            <input type="text" name="pesquisa" id="pesquisa"
                placeholder="Nome, documento, e-mail ou telefone" value="<?= $this->input->get('pesquisa') ?>">
        </div>
        <div class="ui-campo ui-campo-acao">
            <button class="button btn btn-mini btn-warning" title="Buscar" aria-label="Buscar">
                <span class="button__icon"><i class='bx bx-search-alt'></i></span><span class="button__text2">Buscar</span></button>
        </div>
    </form>

    <div class="widget-box">
        <h5 style="padding: 3px 0"></h5>
        <div class="widget-content nopadding tab-content ui-tabela-rolagem">
            <table id="tabela" class="table table-bordered ">
                <thead>
                    <tr>
                        <th>Cod.</th>
                        <th>Nome</th>
                        <th>Contato</th>
                        <th>CPF/CNPJ</th>
                        <th>Telefone</th>
                        <th>Celular</th>
                        <th>Email</th>
                        <th>Tipo</th> <!-- Nova coluna para Fornecedor/Cliente -->
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!$results) {
                        echo '<tr>
                    <td colspan="9"><div class="ui-vazio"><i class="bx bx-user"></i><strong>Nenhum cliente ou fornecedor cadastrado</strong>Use o botão "Cliente / Fornecedor" para cadastrar o primeiro.</div></td>
                  </tr>';
                    }
                    foreach ($results as $r) {
                        echo '<tr>';
                        echo '<td>' . $r->idClientes . '</td>';
                        echo '<td><a href="' . base_url() . 'index.php/clientes/visualizar/' . $r->idClientes . '" style="margin-right: 1%">' . $r->nomeCliente . '</a></td>';
                        echo '<td>' . $r->contato . '</td>';
                        echo '<td>' . $r->documento . '</td>';
                        echo '<td>' . $r->telefone . '</td>';
                        echo '<td>' . $r->celular . '</td>';
                        echo '<td>' . $r->email . '</td>';

                        // Verifica se é Fornecedor ou Cliente
                        if ($r->fornecedor == 1) {
                            echo '<td><span class="label label-primary">Fornecedor</span></td>';
                        } else {
                            echo '<td><span class="label label-success">Cliente</span></td>';
                        }

                        echo '<td>';
                        if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vCliente')) {
                            echo '<a href="' . base_url() . 'index.php/clientes/visualizar/' . $r->idClientes . '" style="margin-right: 1%" class="btn-nwe" title="Ver mais detalhes" aria-label="Ver mais detalhes"><i class="bx bx-show bx-xs"></i></a>';
                            echo '<a href="' . base_url() . 'index.php/mine?e=' . $r->email . '" target="new" style="margin-right: 1%" class="btn-nwe2" title="Área do cliente" aria-label="Área do cliente"><i class="bx bx-key bx-xs"></i></a>';
                        }
                        if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eCliente')) {
                            echo '<a href="' . base_url() . 'index.php/clientes/editar/' . $r->idClientes . '" style="margin-right: 1%" class="btn-nwe3" title="Editar Cliente" aria-label="Editar Cliente"><i class="bx bx-edit bx-xs"></i></a>';
                        }
                        if ($this->permission->checkPermission($this->session->userdata('permissao'), 'dCliente')) {
                            echo '<a href="#modal-excluir" role="button" data-toggle="modal" cliente="' . $r->idClientes . '" style="margin-right: 1%" class="btn-nwe4" title="Excluir Cliente" aria-label="Excluir Cliente"><i class="bx bx-trash-alt bx-xs"></i></a>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    } ?>
                </tbody>
            </table>

        </div>
    </div>
</div>
<?php echo $this->pagination->create_links(); ?>

<!-- Modal -->
<div id="modal-excluir" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
    aria-hidden="true">
    <form action="<?php echo base_url() ?>index.php/clientes/excluir" method="post">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h5 id="myModalLabel">Excluir Cliente</h5>
        </div>
        <div class="modal-body">
            <input type="hidden" id="idCliente" name="id" value="" />
            <h5 style="text-align: center">Deseja realmente excluir este cliente e os dados associados a ele (OS,
                Vendas, Receitas)?</h5>
        </div>
        <div class="modal-footer" style="display:flex;justify-content: center">
            <button class="button btn btn-warning" data-dismiss="modal" aria-hidden="true"><span class="button__icon"><i
                        class="bx bx-x"></i></span><span class="button__text2">Cancelar</span></button>
            <button class="button btn btn-danger"><span class="button__icon"><i class='bx bx-trash'></i></span> <span
                    class="button__text2">Excluir</span></button>
        </div>
    </form>
</div>

<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function () {
        $(document).on('click', 'a', function (event) {
            var cliente = $(this).attr('cliente');
            $('#idCliente').val(cliente);
        });
    });
</script>
