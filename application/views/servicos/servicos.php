<style>
  select {
    width: 70px;
  }
</style>
<div class="new122">
    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="fas fa-wrench"></i> Serviços</h1>
            <p class="ui-subtitulo">Serviços oferecidos e seus preços. Busque por nome ou descrição.</p>
        </div>
        <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'aServico')) : ?>
            <div class="ui-acoes">
                <a href="<?= base_url() ?>index.php/servicos/adicionar" class="button btn btn-mini btn-success">
                    <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2"> Serviços</span>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <form class="ui-filtros" method="get" action="<?= base_url() ?>index.php/servicos">
        <div class="ui-campo">
            <label for="pesquisa">Buscar</label>
            <input type="text" name="pesquisa" id="pesquisa" placeholder="Nome ou descrição" value="<?=$this->input->get('pesquisa')?>">
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
                        <th>Preço</th>
                        <th>Descrição</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if (!$results) {
                            echo '<tr>
                                    <td colspan="5"><div class="ui-vazio"><i class="bx bx-wrench"></i><strong>Nenhum serviço cadastrado</strong>Use o botão "Serviços" para cadastrar o primeiro.</div></td>
                                </tr>';
                        }
                        foreach ($results as $r) {
                            echo '<tr>';
                            echo '<td>' . $r->idServicos . '</td>';
                            echo '<td>' . $r->nome . '</td>';
                            echo '<td>' . number_format($r->preco, 2, ',', '.') . '</td>';
                            echo '<td>' . $r->descricao . '</td>';
                            echo '<td>';
                            if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eServico')) {
                                echo '<a style="margin-right: 1%" href="' . base_url() . 'index.php/servicos/editar/' . $r->idServicos . '" class="btn-nwe3" title="Editar Serviço" aria-label="Editar Serviço"><i class="bx bx-edit bx-xs"></i></a>';
                            }
                            if ($this->permission->checkPermission($this->session->userdata('permissao'), 'dServico')) {
                                echo '<a href="#modal-excluir" role="button" data-toggle="modal" servico="' . $r->idServicos . '" class="btn-nwe4" title="Excluir Serviço" aria-label="Excluir Serviço"><i class="bx bx-trash-alt bx-xs"></i></a>  ';
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
<div id="modal-excluir" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <form action="<?php echo base_url() ?>index.php/servicos/excluir" method="post">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h5 id="myModalLabel">Excluir Serviço</h5>
        </div>
        <div class="modal-body">
            <input type="hidden" id="idServico" name="id" value="" />
            <h5 style="text-align: center">Deseja realmente excluir este serviço?</h5>
        </div>
        <div class="modal-footer" style="display:flex;justify-content: center">
          <button class="button btn btn-warning" data-dismiss="modal" aria-hidden="true"><span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Cancelar</span></button>
          <button class="button btn btn-danger"><span class="button__icon"><i class='bx bx-trash'></i></span> <span class="button__text2">Excluir</span></button>
        </div>
    </form>
</div>

<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function() {
        $(document).on('click', 'a', function(event) {
            var servico = $(this).attr('servico');
            $('#idServico').val(servico);
        });
    });
</script>
