<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Lista de contas de acesso.
 *
 * Mostra o que decide uma revisão de acessos: quem é, com qual perfil, se está
 * ativo, até quando vale e quando entrou pela última vez. Filtros por texto,
 * perfil e situação usam query string, para a paginação manter o recorte.
 *
 * Variáveis: $results, $filtros, $perfis, $pagination.
 */
$e = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$hoje = date('Y-m-d');
$tokenName = $this->security->get_csrf_token_name();
$tokenHash = $this->security->get_csrf_hash();
$proprioId = (int) $this->session->userdata('id_admin');
$podePerfis = $this->permission->checkPermission($this->session->userdata('permissao'), 'cPermissao');

$dataBr = static function ($data) {
    if (empty($data) || strpos((string) $data, '0000-00-00') === 0) {
        return '—';
    }
    $ts = strtotime((string) $data);

    return $ts ? date('d/m/Y', $ts) : '—';
};
?>
<div class="new122">
    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="bx bx-user"></i> Usuários</h1>
            <p class="ui-subtitulo">Quem acessa o sistema, com o perfil, a situação e a validade de cada conta.</p>
        </div>
        <div class="ui-acoes">
            <?php if ($podePerfis) { ?>
                <a href="<?= site_url('permissoes') ?>" class="button btn btn-dark">
                    <span class="button__icon"><i class="bx bx-lock-alt"></i></span><span class="button__text2">Perfis</span>
                </a>
            <?php } ?>
            <a href="<?= site_url('usuarios/adicionar') ?>" class="button btn btn-success">
                <span class="button__icon"><i class="bx bx-plus-circle"></i></span><span class="button__text2">Novo usuário</span>
            </a>
        </div>
    </div>

    <form class="ui-filtros" method="get" action="<?= site_url('usuarios') ?>">
        <div class="ui-campo">
            <label for="pesquisa">Buscar</label>
            <input class="form-control" type="search" name="pesquisa" id="pesquisa" placeholder="Nome, e-mail, CPF ou telefone"
                value="<?= $e($filtros['pesquisa']) ?>">
        </div>
        <div class="ui-campo">
            <label for="perfil">Perfil</label>
            <select class="form-select" name="perfil" id="perfil">
                <option value="">Todos os perfis</option>
                <?php foreach ($perfis as $perfil) { ?>
                    <option value="<?= (int) $perfil->idPermissao ?>" <?= (int) $filtros['perfil'] === (int) $perfil->idPermissao ? 'selected' : '' ?>>
                        <?= $e($perfil->nome) ?><?= (int) $perfil->situacao === 1 ? '' : ' (inativo)' ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <div class="ui-campo">
            <label for="situacao">Situação</label>
            <select class="form-select" name="situacao" id="situacao">
                <option value="">Todas</option>
                <option value="1" <?= $filtros['situacao'] === '1' ? 'selected' : '' ?>>Ativos</option>
                <option value="0" <?= $filtros['situacao'] === '0' ? 'selected' : '' ?>>Inativos</option>
            </select>
        </div>
        <div class="ui-campo ui-campo-acao">
            <button class="button btn btn-warning" type="submit">
                <span class="button__icon"><i class="bx bx-search-alt"></i></span><span class="button__text2">Filtrar</span>
            </button>
        </div>
        <?php if ($filtros['pesquisa'] !== '' || $filtros['perfil'] > 0 || $filtros['situacao'] !== '') { ?>
            <div class="ui-campo ui-campo-acao">
                <a href="<?= site_url('usuarios') ?>" class="button btn btn-dark">
                    <span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Limpar</span>
                </a>
            </div>
        <?php } ?>
    </form>

    <div class="widget-box">
        <div class="widget-content nopadding tab-content ui-tabela-rolagem">
            <table id="tabela" class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Situação</th>
                        <th>Validade</th>
                        <th>Último acesso</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="ui-vazio">
                                    <i class="bx bx-user"></i>
                                    <strong><?= $filtros['pesquisa'] !== '' || $filtros['perfil'] > 0 || $filtros['situacao'] !== '' ? 'Nenhum usuário para este filtro' : 'Nenhum usuário cadastrado' ?></strong>
                                    <?= $filtros['pesquisa'] !== '' || $filtros['perfil'] > 0 || $filtros['situacao'] !== '' ? 'Ajuste a busca ou limpe os filtros.' : 'Use o botão "Novo usuário" para criar o primeiro acesso.' ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($results as $r): ?>
                            <?php
                            $ativo = (int) $r->situacao === 1;
                            $vencido = ! empty($r->dataExpiracao) && $r->dataExpiracao < $hoje;
                            $perfilInativo = isset($r->permissao_situacao) && (int) $r->permissao_situacao !== 1;
                            $proprio = (int) $r->idUsuarios === $proprioId;
                            ?>
                            <tr>
                                <td><?= (int) $r->idUsuarios ?></td>
                                <td>
                                    <strong><?= $e($r->nome) ?></strong>
                                    <?php if ($proprio) { ?>
                                        <span class="ui-selo info" title="Esta é a sua conta">você</span>
                                    <?php } ?>
                                </td>
                                <td><?= $e($r->email) ?></td>
                                <td>
                                    <?= $e($r->permissao ?: 'sem perfil') ?>
                                    <?php if ($perfilInativo) { ?>
                                        <span class="ui-selo alerta" title="O perfil está inativo">perfil inativo</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <span class="ui-selo <?= $ativo ? 'sucesso' : 'perigo' ?>"><?= $ativo ? 'Ativo' : 'Inativo' ?></span>
                                </td>
                                <td>
                                    <?php if (empty($r->dataExpiracao)) { ?>
                                        <span class="ui-selo neutro">sem expiração</span>
                                    <?php } else { ?>
                                        <span class="<?= $vencido ? 'ui-negativo' : '' ?>"><?= $dataBr($r->dataExpiracao) ?></span>
                                        <?php if ($vencido) { ?>
                                            <span class="ui-selo perigo">vencida</span>
                                        <?php } ?>
                                    <?php } ?>
                                </td>
                                <td><?= $dataBr($r->ultimo_acesso ?? null) ?></td>
                                <td>
                                    <div class="ui-acoes-linha">
                                        <a href="<?= site_url('usuarios/editar/' . (int) $r->idUsuarios) ?>" class="btn-nwe3"
                                            title="Editar usuário" aria-label="Editar usuário">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <?php if (! $proprio && (int) $r->idUsuarios !== 1) { ?>
                                            <form action="<?= site_url('usuarios/excluir') ?>" method="post" style="display:inline"
                                                data-confirmar="Excluir a conta de <?= $e($r->nome) ?>? Só funciona se ela não tiver histórico.">
                                                <input type="hidden" name="<?= $e($tokenName) ?>" value="<?= $e($tokenHash) ?>">
                                                <input type="hidden" name="id" value="<?= (int) $r->idUsuarios ?>">
                                                <button type="submit" class="btn-nwe4" title="Excluir usuário" aria-label="Excluir usuário">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </form>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->pagination->create_links() ?>
