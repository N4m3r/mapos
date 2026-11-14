<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Cadastro e edicao de conta de acesso, num formulario so.
 *
 * Antes eram duas telas (adicionarUsuario e editarUsuario) com os mesmos
 * campos em ordens diferentes, sem indicar o que era obrigatorio de fato e
 * sem a validade poder ficar em branco. Aqui os dados ficam em abas - dados,
 * endereco, acesso e seguranca - e a senha so e obrigatoria na criacao.
 *
 * Variaveis: $usuario (objeto|null), $editar, $custom_error, $perfis,
 * $tokenName, $tokenHash, $doisFatoresAtivo, $codigosRestantes, $proprioId.
 */
$e = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

$usuario = $usuario ?? null;
$editar = ! empty($editar);
$id = $usuario ? (int) $usuario->idUsuarios : 0;
$proprio = $id > 0 && $id === (int) ($proprioId ?? 0);
$superAdmin = $id === 1;
$doisFatoresAtivo = ! empty($doisFatoresAtivo);
$codigosRestantes = (int) ($codigosRestantes ?? 0);

// set_value() devolve o POST quando houve erro e o valor atual na edicao.
$v = static function ($campo, $padrao = '') use ($usuario) {
    $atual = ($usuario && isset($usuario->$campo)) ? $usuario->$campo : $padrao;

    return set_value($campo, $atual);
};

$acao = $editar ? site_url('usuarios/editar/' . $id) : site_url('usuarios/adicionar');
$validade = (string) $v('dataExpiracao');
if (strpos($validade, '0000-00-00') === 0) {
    $validade = '';
}
?>
<div class="new122">
    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="bx <?= $editar ? 'bx-user' : 'bx-user-plus' ?>"></i> <?= $editar ? 'Editar usuário' : 'Novo usuário' ?></h1>
            <p class="ui-subtitulo">
                <?= $editar
                    ? 'Altere os dados, o perfil de acesso e a validade desta conta.'
                    : 'Preencha os dados e escolha o perfil de acesso. A senha definida aqui pode ser trocada depois em Minha conta.' ?>
            </p>
        </div>
        <div class="ui-acoes">
            <a href="<?= site_url('usuarios') ?>" class="button btn btn-warning">
                <span class="button__icon"><i class="bx bx-undo"></i></span><span class="button__text2">Voltar</span>
            </a>
        </div>
    </div>

    <?php if (! empty($custom_error)) { ?>
        <?= $custom_error ?>
    <?php } ?>

    <form action="<?= $acao ?>" id="formUsuario" method="post">
        <input type="hidden" name="<?= $e($tokenName ?? $this->security->get_csrf_token_name()) ?>" value="<?= $e($tokenHash ?? $this->security->get_csrf_hash()) ?>">
        <?php if ($editar) { ?>
            <input type="hidden" name="idUsuarios" value="<?= $id ?>">
        <?php } ?>

        <div class="widget-box">
            <div class="widget-title">
                <ul class="nav nav-tabs">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#u-dados">Dados pessoais</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#u-endereco">Endereço</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#u-acesso">Acesso</a></li>
                    <?php if ($editar && $doisFatoresAtivo) { ?>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#u-seguranca">Segurança</a></li>
                    <?php } ?>
                </ul>
            </div>

            <div class="widget-content tab-content">
                <div id="u-dados" class="tab-pane fade show active">
                    <div class="ui-secao">
                        <h4 class="ui-secao-titulo">Identificação e contato</h4>
                        <div class="ui-grade">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome<span class="required">*</span></label>
                                <div class="">
                                    <input class="form-control" id="nome" type="text" name="nome" maxlength="80" value="<?= $v('nome') ?>" autocomplete="name">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="rg" class="form-label">RG</label>
                                <div class="">
                                    <input class="form-control" id="rg" type="text" name="rg" maxlength="20" value="<?= $v('rg') ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="cpf" class="form-label">CPF</label>
                                <div class="">
                                    <input id="cpfUser" class="cpfUser form-control" type="text" name="cpf" maxlength="20" value="<?= $v('cpf') ?>">
                                    <span class="form-text">Opcional, mas não pode repetir em outra conta.</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="telefone" class="form-label">Telefone<span class="required">*</span></label>
                                <div class="">
                                    <input class="form-control" id="telefone" type="text" name="telefone" maxlength="20" value="<?= $v('telefone') ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="celular" class="form-label">Celular</label>
                                <div class="">
                                    <input class="form-control" id="celular" type="text" name="celular" maxlength="20" value="<?= $v('celular') ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail<span class="required">*</span></label>
                                <div class="">
                                    <input class="form-control" id="email" type="email" name="email" maxlength="80" value="<?= $v('email') ?>" autocomplete="email">
                                    <span class="form-text">É por ele que a pessoa entra no sistema.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="u-endereco" class="tab-pane fade">
                    <div class="ui-secao">
                        <h4 class="ui-secao-titulo">Endereço</h4>
                        <div class="ui-grade">
                            <div class="mb-3">
                                <label for="cep" class="form-label">CEP</label>
                                <div class="">
                                    <input class="form-control" id="cep" type="text" name="cep" maxlength="9" value="<?= $v('cep') ?>" inputmode="numeric">
                                    <span class="form-text">Preenche o endereço ao sair do campo.</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="rua" class="form-label">Logradouro</label>
                                <div class="">
                                    <input class="form-control" id="rua" type="text" name="rua" maxlength="70" value="<?= $v('rua') ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="numero" class="form-label">Número</label>
                                <div class="">
                                    <input class="form-control" id="numero" type="text" name="numero" maxlength="15" value="<?= $v('numero') ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="bairro" class="form-label">Bairro</label>
                                <div class="">
                                    <input class="form-control" id="bairro" type="text" name="bairro" maxlength="45" value="<?= $v('bairro') ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="cidade" class="form-label">Cidade</label>
                                <div class="">
                                    <input class="form-control" id="cidade" type="text" name="cidade" maxlength="45" value="<?= $v('cidade') ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="estado" class="form-label">UF</label>
                                <div class="">
                                    <input class="form-control" id="estado" type="text" name="estado" maxlength="2" value="<?= $v('estado') ?>" placeholder="SP">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="u-acesso" class="tab-pane fade">
                    <div class="ui-secao">
                        <h4 class="ui-secao-titulo">Acesso ao sistema</h4>
                        <div class="ui-grade">
                            <div class="mb-3">
                                <label for="permissoes_id" class="form-label">Perfil de permissão<span class="required">*</span></label>
                                <div class="">
                                    <?php $perfilAtual = (int) $v('permissoes_id', 0); ?>
                                    <select class="form-select" name="permissoes_id" id="permissoes_id">
                                        <option value="">— escolha um perfil —</option>
                                        <?php foreach ($perfis as $perfil) {
                                            $inativo = (int) $perfil->situacao !== 1;
                                            $selecionado = (int) $perfil->idPermissao === $perfilAtual;
                                            // Perfil inativo continua listado quando ja e o atual,
                                            // senao editar outro campo trocaria o acesso sem aviso.
                                            if ($inativo && ! $selecionado) {
                                                continue;
                                            }
                                            ?>
                                            <option value="<?= (int) $perfil->idPermissao ?>" <?= $selecionado ? 'selected' : '' ?>>
                                                <?= $e($perfil->nome) ?><?= $inativo ? ' (inativo)' : '' ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                    <?php if (! empty($podeVerPerfis)) { ?>
                                        <span class="form-text">Para criar ou ajustar perfis, use <a href="<?= site_url('permissoes') ?>">Perfis de permissão</a>.</span>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="situacao" class="form-label">Situação</label>
                                <div class="">
                                    <?php $situacaoAtual = (string) $v('situacao', '1'); ?>
                                    <select class="form-select" name="situacao" id="situacao" <?= $superAdmin ? 'disabled' : '' ?>>
                                        <option value="1" <?= $situacaoAtual === '1' ? 'selected' : '' ?>>Ativo</option>
                                        <option value="0" <?= $situacaoAtual === '0' ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                    <?php if ($superAdmin) { ?>
                                        <input type="hidden" name="situacao" value="1">
                                        <span class="form-text">O super admin não pode ser desativado.</span>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="dataExpiracao" class="form-label">Acesso válido até</label>
                                <div class="">
                                    <input class="form-control" id="dataExpiracao" type="date" name="dataExpiracao" value="<?= $validade ?>">
                                    <span class="form-text">Em branco = sem expiração. Depois da data, o login é recusado.</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha<?= $editar ? '' : '<span class="required">*</span>' ?></label>
                                <div class="">
                                    <input class="form-control" id="senha" type="password" name="senha" value="" autocomplete="new-password"
                                        placeholder="<?= $editar ? 'Deixe em branco para manter a atual' : 'Mínimo 6 caracteres' ?>">
                                    <?php if ($editar) { ?>
                                        <span class="form-text">Só preencha se quiser trocar a senha desta conta.</span>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($editar) { ?>
                            <div class="ui-dica">
                                <i class="bx bx-info-circle"></i>
                                <div>
                                    <strong>Último acesso:</strong>
                                    <?= ! empty($usuario->ultimo_acesso) ? date('d/m/Y H:i', strtotime($usuario->ultimo_acesso)) : 'nunca entrou' ?>.
                                    Cadastrado em <?= ! empty($usuario->dataCadastro) ? date('d/m/Y', strtotime($usuario->dataCadastro)) : '—' ?>.
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <?php if ($editar && $doisFatoresAtivo) { ?>
                    <div id="u-seguranca" class="tab-pane fade">
                        <div class="ui-secao">
                            <h4 class="ui-secao-titulo">Verificação em duas etapas</h4>
                            <p>
                                <span class="ui-selo sucesso">Ativa</span>
                                Códigos de recuperação restantes: <strong><?= $codigosRestantes ?></strong>.
                            </p>
                            <?php if ($codigosRestantes <= 3) { ?>
                                <div class="ui-dica alerta">
                                    <i class="bx bx-error"></i>
                                    <div>Restam poucos códigos. Peça à pessoa para gerar novos em Minha conta antes de acabar.</div>
                                </div>
                            <?php } ?>
                            <label class="ui-opcao" style="display:inline-flex">
                                <input class="form-check-input" type="checkbox" name="desativar_dois_fatores" value="1">
                                Desativar (use só se a pessoa perdeu o celular e os códigos de recuperação)
                            </label>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="ui-rodape-form">
            <a href="<?= site_url('usuarios') ?>" class="button btn btn-warning">
                <span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Cancelar</span>
            </a>
            <button type="submit" class="button btn btn-primary">
                <span class="button__icon"><i class="bx bx-save"></i></span><span class="button__text2"><?= $editar ? 'Salvar alterações' : 'Cadastrar usuário' ?></span>
            </button>
        </div>
    </form>
</div>

<script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/jquery.mask.min.js"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/sweetalert2.all.min.js"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/funcoes.js"></script>
