<?php
$fotoUsuario = (!$usuario->url_image_user || !is_file(FCPATH . 'assets/userImage/' . $usuario->url_image_user))
    ? base_url('assets/img/User.png')
    : base_url('assets/userImage/' . $usuario->url_image_user);

// Validade em branco = conta sem expiracao. Sem a checagem, strtotime(null) vira 01/01/1970.
$validade = trim((string) ($usuario->dataExpiracao ?? ''));
if ($validade === '' || strpos($validade, '0000-00-00') === 0) {
    $validadeTexto = 'Sem expiração';
} else {
    $ts = strtotime($validade);
    $validadeTexto = $ts ? date('d/m/Y', $ts) : $validade;
}

$modoTema = $modoTema ?? 'sistema';
$acessosRecentes = $acessosRecentes ?? [];
$doisFatoresAtivo = !empty($usuario->totp_ativo);
?>
<style>
    .conta-grade { row-gap: 16px; }
    .conta-card { height: 100%; border-radius: 12px; }
    .conta-card > .card-header { display: flex; align-items: center; gap: 8px; font-weight: 600; background: transparent; }
    .conta-card > .card-header i { font-size: 18px; opacity: .8; }
    .conta-perfil { text-align: center; }
    .conta-avatar { position: relative; width: 104px; height: 104px; margin: 4px auto 12px; }
    .conta-avatar img { width: 104px; height: 104px; border-radius: 50%; object-fit: cover; border: 3px solid var(--bs-border-color); }
    .conta-avatar .conta-foto-btn { position: absolute; right: 0; bottom: 2px; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; padding: 0; }
    .conta-perfil h2 { font-size: 18px; margin: 0; }
    .conta-perfil .conta-email { color: var(--bs-secondary-color); word-break: break-all; }
    .conta-dados { margin: 16px 0 0; text-align: left; }
    .conta-dados > div { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-top: 1px solid var(--bs-border-color); }
    .conta-dados dt { font-weight: 500; color: var(--bs-secondary-color); margin: 0; }
    .conta-dados dd { margin: 0; text-align: right; word-break: break-word; }
    .conta-modos { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .conta-modos .btn { display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 10px 6px; }
    .conta-modos .btn i { font-size: 22px; }
    .conta-senha-campo { position: relative; }
    .conta-senha-campo .form-control { padding-right: 42px; background-image: none; }
    .col-lg-4 > .conta-card { height: auto; }
    .conta-senha-campo .conta-ver-senha { position: absolute; right: 4px; top: 50%; transform: translateY(-50%); border: 0; background: transparent; width: 34px; height: 34px; color: var(--bs-secondary-color); }
    .conta-forca { height: 6px; margin-top: 6px; }
    .conta-forca-texto { min-height: 18px; }
    .conta-acessos { margin: 0; }
    .conta-acessos td, .conta-acessos th { padding: 6px 8px; }
    .conta-2fa-acoes { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
    @media (max-width: 575px) {
        .conta-modos .btn span { font-size: 12px; }
    }
</style>

<div class="ui-cabecalho">
    <div>
        <h1 class="ui-titulo"><i class="bx bx-user-circle"></i> Minha Conta</h1>
        <p class="ui-subtitulo">Seus dados, foto, aparência do painel, senha e verificação em duas etapas.</p>
    </div>
</div>

<div class="row conta-grade" id="minha-conta">
    <!-- Perfil -->
    <div class="col-lg-4">
        <div class="card conta-card">
            <div class="card-body conta-perfil">
                <div class="conta-avatar">
                    <img src="<?= html_escape($fotoUsuario) ?>" alt="Foto de <?= html_escape($usuario->nome) ?>">
                    <a href="#modalImageUser" data-bs-toggle="modal" role="button" class="btn btn-primary conta-foto-btn" title="Alterar foto" aria-label="Alterar foto"><i class="bx bxs-camera" aria-hidden="true"></i></a>
                </div>
                <h2><?= html_escape($usuario->nome) ?></h2>
                <div class="conta-email"><?= html_escape($usuario->email) ?></div>
                <span class="badge text-bg-secondary mt-2"><?= html_escape($usuario->permissao) ?></span>

                <dl class="conta-dados">
                    <div><dt>Telefone</dt><dd><?= html_escape($usuario->telefone ?: '—') ?></dd></div>
                    <div><dt>Celular</dt><dd><?= html_escape(($usuario->celular ?? '') ?: '—') ?></dd></div>
                    <div><dt>Acesso expira em</dt><dd><?= html_escape($validadeTexto) ?></dd></div>
                    <div><dt>Verificação em duas etapas</dt><dd><?= $doisFatoresAtivo ? '<span class="badge text-bg-success">Ativa</span>' : '<span class="badge text-bg-warning">Desativada</span>' ?></dd></div>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row conta-grade">
            <!-- Aparencia -->
            <div class="col-12">
                <div class="card conta-card">
                    <div class="card-header"><i class="bx bx-palette" aria-hidden="true"></i> Aparência</div>
                    <div class="card-body">
                        <p class="text-muted mb-2">Vale só para você, neste navegador. O mesmo que o botão de lua/sol no topo.</p>
                        <div class="conta-modos" role="radiogroup" aria-label="Modo de cor do painel">
                            <?php foreach (['claro' => ['bx-sun', 'Claro'], 'escuro' => ['bx-moon', 'Escuro'], 'sistema' => ['bx-cog', 'Como o sistema']] as $valor => [$icone, $rotulo]) { ?>
                                <button type="button" class="btn <?= $modoTema === $valor ? 'btn-primary' : 'btn-outline-secondary' ?>" role="radio" aria-checked="<?= $modoTema === $valor ? 'true' : 'false' ?>" data-modo="<?= $valor ?>">
                                    <i class="bx <?= $icone ?>" aria-hidden="true"></i><span><?= $rotulo ?></span>
                                </button>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Senha -->
            <div class="col-md-6">
                <div class="card conta-card">
                    <div class="card-header"><i class="bx bx-lock-alt" aria-hidden="true"></i> Alterar senha</div>
                    <div class="card-body">
                        <form id="formSenha" action="<?= site_url('mapos/alterarSenha'); ?>" method="post" novalidate>
                            <div class="mb-2">
                                <label for="oldSenha" class="form-label">Senha atual</label>
                                <div class="conta-senha-campo">
                                    <input type="password" id="oldSenha" name="oldSenha" class="form-control" autocomplete="current-password" required>
                                    <button type="button" class="conta-ver-senha" data-ver-senha="oldSenha" aria-label="Mostrar senha atual" title="Mostrar senha"><i class="bx bx-show" aria-hidden="true"></i></button>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label for="novaSenha" class="form-label">Nova senha</label>
                                <div class="conta-senha-campo">
                                    <input type="password" id="novaSenha" name="novaSenha" class="form-control" autocomplete="new-password" minlength="6" required aria-describedby="forcaSenhaTexto">
                                    <button type="button" class="conta-ver-senha" data-ver-senha="novaSenha" aria-label="Mostrar nova senha" title="Mostrar senha"><i class="bx bx-show" aria-hidden="true"></i></button>
                                </div>
                                <div class="progress conta-forca" role="presentation"><div class="progress-bar" id="forcaSenhaBarra" style="width: 0%"></div></div>
                                <div class="form-text conta-forca-texto" id="forcaSenhaTexto" aria-live="polite">Mínimo de 6 caracteres. Misture letras, números e símbolos.</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirmarSenha" class="form-label">Confirmar nova senha</label>
                                <input type="password" id="confirmarSenha" name="confirmarSenha" class="form-control" autocomplete="new-password" required>
                            </div>
                            <button class="button btn btn-primary" type="submit">
                                <span class="button__icon"><i class="bx bx-lock-alt"></i></span><span class="button__text2">Alterar senha</span></button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Verificacao em duas etapas -->
            <div class="col-md-6">
                <div class="card conta-card">
                    <div class="card-header"><i class="bx bx-shield-quarter" aria-hidden="true"></i> Verificação em duas etapas</div>
                    <div class="card-body">
                        <?php if (!$doisFatoresAtivo) { ?>
                            <p><span class="badge text-bg-warning">Desativada</span></p>
                            <p>Além da senha, o login passa a pedir um código do aplicativo autenticador do seu celular.
                                Recomendado principalmente para administradores.</p>
                            <a href="<?= site_url('mapos/doisFatores') ?>" class="button btn btn-success"><span class="button__icon"><i class="bx bx-shield-quarter"></i></span><span class="button__text2">Ativar</span></a>
                        <?php } else { ?>
                            <p><span class="badge text-bg-success">Ativa</span>
                                &nbsp; Códigos de recuperação restantes: <strong><?= (int) $codigosRestantes ?></strong></p>
                            <?php if ($codigosRestantes <= 3) { ?>
                                <div class="alert alert-warning">Restam poucos códigos de recuperação. Gere novos.</div>
                            <?php } ?>
                            <div class="conta-2fa-acoes">
                                <form action="<?= site_url('mapos/doisFatoresNovosCodigos') ?>" method="post">
                                    <label class="form-label" for="senha2faCodigos">Senha atual</label>
                                    <input class="form-control mb-2" id="senha2faCodigos" type="password" name="senha" required autocomplete="current-password">
                                    <button class="button btn btn-info" type="submit"><span class="button__icon"><i class="bx bx-key"></i></span><span class="button__text2">Novos códigos</span></button>
                                </form>
                                <form action="<?= site_url('mapos/doisFatoresDesativar') ?>" method="post" data-confirmar="Desativar a verificação em duas etapas?">
                                    <label class="form-label" for="senha2faDesativar">Senha atual</label>
                                    <input class="form-control mb-2" id="senha2faDesativar" type="password" name="senha" required autocomplete="current-password">
                                    <button class="button btn btn-danger" type="submit"><span class="button__icon"><i class="bx bx-shield-x"></i></span><span class="button__text2">Desativar</span></button>
                                </form>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- Acessos recentes -->
            <div class="col-12">
                <div class="card conta-card">
                    <div class="card-header"><i class="bx bx-history" aria-hidden="true"></i> Acessos recentes</div>
                    <div class="card-body">
                        <?php if (!$acessosRecentes) { ?>
                            <p class="text-muted mb-0" id="acessosVazio">Nenhum login registrado na auditoria ainda.</p>
                        <?php } else { ?>
                            <div class="table-responsive">
                                <table class="table table-sm conta-acessos" id="acessosRecentes">
                                    <thead><tr><th>Data</th><th>Hora</th><th>IP</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($acessosRecentes as $acesso) { ?>
                                        <tr>
                                            <td><?= html_escape($acesso->data ? date('d/m/Y', strtotime($acesso->data)) : '') ?></td>
                                            <td><?= html_escape(substr((string) $acesso->hora, 0, 5)) ?></td>
                                            <td><?= html_escape($acesso->ip) ?></td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <p class="form-text mb-0">Os 5 últimos logins da sua conta. Não reconhece algum? Troque a senha e ative a verificação em duas etapas.</p>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modalImageUser" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modalImageUserTitulo" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
    <form action="<?= site_url('mapos/uploadUserImage'); ?>" id="formImageUser" enctype="multipart/form-data" method="post">
        <div class="modal-header">
            <h5 class="modal-title" id="modalImageUserTitulo">Alterar foto</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
            <p class="text-muted">Use uma imagem quadrada (o tamanho indicado é 130 × 130).</p>
            <label for="userfile" class="form-label">Foto <span class="required">*</span></label>
            <input class="form-control" type="file" id="userfile" name="userfile" accept="image/*">
        </div>
        <div class="modal-footer" style="display:flex;justify-content: center">
            <button type="button" class="button btn btn-warning" data-bs-dismiss="modal" id="btnCancelExcluir"><span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Cancelar</span></button>
            <button class="button btn btn-primary"><span class="button__icon"><i class="bx bx-sync"></i></span><span class="button__text2">Atualizar</span></button>
        </div>
    </form>
</div></div></div>

<script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/jquery.validate.js"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/minha-conta.js') ?>?v=<?= asset_versao('assets/js/minha-conta.js') ?>"></script>
