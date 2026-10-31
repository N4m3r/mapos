
<div class="new122">
    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="bx bx-user"></i> Usuários</h1>
            <p class="ui-subtitulo">Quem acessa o sistema, com o nível de permissão e a validade de cada conta.</p>
        </div>
        <div class="ui-acoes">
            <a href="<?= base_url('index.php/usuarios/adicionar') ?>" class="button btn btn-success">
                <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Adicionar Usuário</span>
            </a>
        </div>
    </div>

    <div class="widget-box">
        <div class="widget-content nopadding tab-content ui-tabela-rolagem">
            <table id="tabela" class="table table-bordered ">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Telefone</th>
                        <th>Nível</th>
                        <th>Situação</th>
                        <th>Validade</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="ui-vazio"><i class="bx bx-user"></i><strong>Nenhum usuário cadastrado</strong>Use o botão "Adicionar Usuário" para criar o primeiro acesso.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($results as $r): ?>
                            <tr>
                                <td><?= $r->idUsuarios ?></td>
                                <td><?= html_escape($r->nome) ?></td>
                                <td><?= html_escape($r->cpf) ?></td>
                                <td><?= html_escape($r->telefone) ?></td>
                                <td><?= $r->permissao ?></td>
                                <?php
                                $situacao = ($r->situacao == 1) ? 'Ativo' : 'Inativo';
                                $situacaoClasse = ($r->situacao == 1) ? 'situacao-ativo' : 'situacao-inativo';
                                ?>
                                <td><span class="ui-selo <?= $r->situacao == 1 ? 'sucesso' : 'perigo' ?>"><?= ucfirst($situacao) ?></span></td>
                                <td><?= $r->dataExpiracao ?></td>
                                <td>
                                    <div class="ui-acoes-linha"><a href="<?= base_url('index.php/usuarios/editar/' . $r->idUsuarios) ?>" class="btn-nwe3" title="Editar usuário" aria-label="Editar usuário"><i class="bx bx-edit"></i></a></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->pagination->create_links(); ?>
