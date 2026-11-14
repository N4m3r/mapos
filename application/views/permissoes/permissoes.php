<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

$e = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$tokenName = $this->security->get_csrf_token_name();
$tokenHash = $this->security->get_csrf_hash();
?>
<div class="new122">
    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="bx bx-lock-alt"></i> Perfis de permissão</h1>
            <p class="ui-subtitulo">Cada perfil define o que os usuários podem ver e fazer. Um usuário herda o perfil da sua conta.</p>
        </div>
        <div class="ui-acoes">
            <a href="<?= site_url('usuarios') ?>" class="button btn btn-dark">
                <span class="button__icon"><i class="bx bx-user-circle"></i></span><span class="button__text2">Ver usuários</span>
            </a>
            <a href="<?= site_url('permissoes/adicionar') ?>" class="button btn btn-success">
                <span class="button__icon"><i class="bx bx-plus-circle"></i></span><span class="button__text2">Novo perfil</span>
            </a>
        </div>
    </div>

    <div class="ui-dica">
        <i class="bx bx-info-circle"></i>
        <div>O perfil só pode ser desativado quando nenhuma conta ativa estiver usando. Troque o perfil dessas contas em Usuários antes.</div>
    </div>

    <div class="widget-box">
        <div class="widget-content nopadding tab-content ui-tabela-rolagem">
            <table id="tabela" class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Perfil</th>
                        <th>Permissões liberadas</th>
                        <th>Usuários ativos</th>
                        <th>Criado em</th>
                        <th>Situação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="ui-vazio">
                                    <i class="bx bx-lock-alt"></i>
                                    <strong>Nenhum perfil cadastrado</strong>
                                    Crie um perfil para começar a dar acesso às contas.
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($results as $r): ?>
                            <?php
                            $ativo = (int) $r->situacao === 1;
                            $liberadas = (int) $r->liberadas;
                            $total = (int) $r->total_chaves;
                            $percentual = $total > 0 ? (int) round($liberadas * 100 / $total) : 0;
                            $emUso = (int) $r->usuarios;
                            ?>
                            <tr>
                                <td><?= (int) $r->idPermissao ?></td>
                                <td>
                                    <strong><?= $e($r->nome) ?></strong>
                                    <?php if ((int) $r->idPermissao === 1): ?>
                                        <span class="ui-selo roxo" title="Perfil do super admin">admin</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="perfil-barra" title="<?= $liberadas ?> de <?= $total ?> permissões">
                                        <span style="width: <?= $percentual ?>%"></span>
                                    </div>
                                    <small><?= $liberadas ?> de <?= $total ?> (<?= $percentual ?>%)</small>
                                </td>
                                <td>
                                    <?php if ($emUso > 0): ?>
                                        <a href="<?= site_url('usuarios?perfil=' . (int) $r->idPermissao) ?>"><?= $emUso ?></a>
                                    <?php else: ?>
                                        <span class="ui-selo neutro">nenhum</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $r->data ? date('d/m/Y', strtotime($r->data)) : '—' ?></td>
                                <td>
                                    <span class="ui-selo <?= $ativo ? 'sucesso' : 'perigo' ?>"><?= $ativo ? 'Ativo' : 'Inativo' ?></span>
                                </td>
                                <td>
                                    <div class="ui-acoes-linha">
                                        <a href="<?= site_url('permissoes/editar/' . (int) $r->idPermissao) ?>" class="btn-nwe3" title="Editar perfil" aria-label="Editar perfil">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <a href="<?= site_url('permissoes/duplicar/' . (int) $r->idPermissao) ?>" class="btn-nwe3" title="Duplicar perfil" aria-label="Duplicar perfil">
                                            <i class="bx bx-copy"></i>
                                        </a>
                                        <?php if ($ativo): ?>
                                            <form action="<?= site_url('permissoes/desativar') ?>" method="post" style="display:inline" data-confirmar="Desativar o perfil &quot;<?= $e($r->nome) ?>&quot;?">
                                                <input type="hidden" name="<?= $e($tokenName) ?>" value="<?= $e($tokenHash) ?>">
                                                <input type="hidden" name="id" value="<?= (int) $r->idPermissao ?>">
                                                <button type="submit" class="btn-nwe4" title="Desativar perfil" aria-label="Desativar perfil">
                                                    <i class="bx bx-power-off"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form action="<?= site_url('permissoes/reativar') ?>" method="post" style="display:inline">
                                                <input type="hidden" name="<?= $e($tokenName) ?>" value="<?= $e($tokenHash) ?>">
                                                <input type="hidden" name="id" value="<?= (int) $r->idPermissao ?>">
                                                <button type="submit" class="btn-nwe3" title="Reativar perfil" aria-label="Reativar perfil">
                                                    <i class="bx bx-check-circle"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
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

<?= $this->pagination->create_links(); ?>

<style>
    .perfil-barra { height: 8px; width: 120px; border-radius: 999px; background: rgba(127,127,127,.18); overflow: hidden; margin-bottom: 4px; }
    .perfil-barra > span { display: block; height: 100%; background: var(--ui-primaria); }
</style>
