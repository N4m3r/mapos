<style>
    .img-user {
      bottom: 15px;
      right: 18px;
      padding: 6px;
      background: #d4d7df;
      color: #333649;
      border-radius: 50%;
      width: 15px;
      height: 15px;
      align-items: center;
      opacity: 0.8;
      position: absolute;
    }

    .pass-user {
        bottom: 27px;
        right: 40px;
        padding: 6px;
        background: transparent;
        border-radius: 50%;
        width: 15px;
        height: 15px;
        align-items: center;
        opacity: 0.7;
        position: absolute;
    }

    .img-user:before {
        opacity: 1;
    }

    .profileMC {
        margin-top: -60px;
    }

    section .profileMC .profile-img {
        border : 4px solid #e6e9f3;
        padding: 0;
        height: 100px;
        width: 100px;
    }

    @media (max-width: 480px) {
        .table-sm td {
            padding: 4px 5px;
        }

        .table {
            width: 100%;
        }

        .panel-body {
            padding: 0;
        }
    }
    

</style>
<div class="ui-cabecalho">
    <div>
        <h1 class="ui-titulo"><i class="bx bx-user-circle"></i> Minha Conta</h1>
        <p class="ui-subtitulo">Foto, senha e verificação em duas etapas do seu acesso.</p>
    </div>
</div>
<div class="row">
<div class="col-md-6" style="margin-left: 0">
    <div class="widget-box">
        <div class="widget-contentMC" style="margin: 20px 0 0;">
            <div id="userMC">
                <div class="row">
                    <div class="col-12" style="text-align:center;">
                        <section>
                            <div class="profileMC">
                                <div class="profile-img">
                                    <img src="<?= (!$usuario->url_image_user || !is_file(FCPATH . "assets/userImage/" . $usuario->url_image_user)) ?  base_url() . "assets/img/User.png" : base_url() . "assets/userImage/" . $usuario->url_image_user ?>" alt="">
                                    <a href="#modalImageUser" data-bs-toggle="modal" role="button"><span class="tip-top img-user button__icon" title="Alterar Foto"><i class='bx bxs-camera'></i></span></a>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <ul class="site-stats">
                        <li class="bg_ls col-md-12"><strong>Nome:
                                <?= html_escape($usuario->nome) ?></strong></li>
                        <li class="bg_lb col-md-12" style="margin-left: 0"><strong>Telefone:
                                <?= html_escape($usuario->telefone) ?></strong></li>
                        <li class="bg_lg col-md-12" style="margin-left: 0"><strong>Email:
                                <?= html_escape($usuario->email) ?></strong></li>
                        <li class="bg_lo col-md-12" style="margin-left: 0"><strong>Nível:
                                <?= $usuario->permissao; ?></strong></li>
                        <li class="bg_lh col-md-12" style="margin-left: 0; border-bottom-left-radius: 9px;border-bottom-right-radius: 9px"><strong>Acesso expira em:
                                <?php
                                // Validade em branco = conta sem expiracao. Sem a checagem,
                                // strtotime(null) vira 01/01/1970 na tela.
                                $validade = trim((string) ($usuario->dataExpiracao ?? ''));
                                if ($validade === '' || strpos($validade, '0000-00-00') === 0) {
                                    echo 'sem expiração';
                                } else {
                                    $ts = strtotime($validade);
                                    echo html_escape($ts ? date('d/m/Y', $ts) : $validade);
                                }
                                ?></strong></li>
                    </ul>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="col-md-6">
    <div class="widget-box">
        <div class="widget-title" style="margin: -20px 0 0">
            <span class="icon">
                <i class="fas fa-lock"></i>
            </span>
            <h5>Alterar Minha Senha</h5>
        </div>
        <div class="widget-content">
            <div class="row">
                <div class="col-md-12" style="min-height: 260px">
                    <form class="row" id="formSenha" action="<?= site_url('mapos/alterarSenha'); ?>" method="post">

                        <div class="col-md-12" style="margin-left: 0">
                            <label for="">Senha Atual</label>
                            <input type="password" id="oldSenha" name="oldSenha" class="w-100 form-control" />
                        </div>
                        <div class="col-md-12" style="margin-left: 0">
                            <label for="">Nova Senha</label>
                            <input type="password" id="novaSenha" name="novaSenha" class="w-100 form-control" />
                        </div>
                        <div class="col-md-12" style="margin-left: 0">
                            <label for="">Confirmar Senha</label>
                            <input type="password" name="confirmarSenha" class="w-100 form-control" />
                        </div>
                            <button class="button btn btn-primary" style="max-width: 140px;text-align: center">
                              <span class="button__icon"><i class='bx bx-lock-alt'></i></span><span class="button__text2">Alterar Senha</span></button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="col-md-6" style="margin-left: 0">
    <div class="widget-box">
        <div class="widget-title" style="margin: -20px 0 0">
            <span class="icon"><i class="fas fa-shield-alt"></i></span>
            <h5>Verificação em duas etapas</h5>
        </div>
        <div class="widget-content">
            <?php if (empty($usuario->totp_ativo)) { ?>
                <p><span class="ui-selo">Desativada</span></p>
                <p>Além da senha, o login passa a pedir um código do aplicativo autenticador do seu celular.
                    Recomendado principalmente para administradores.</p>
                <a href="<?= site_url('mapos/doisFatores') ?>" class="button btn btn-success"><span class="button__icon"><i class="bx bx-shield-quarter"></i></span><span class="button__text2">Ativar</span></a>
            <?php } else { ?>
                <p><span class="ui-selo sucesso">Ativa</span>
                    &nbsp; Códigos de recuperação restantes: <strong><?= (int) $codigosRestantes ?></strong></p>
                <?php if ($codigosRestantes <= 3) { ?>
                    <div class="alert">Restam poucos códigos de recuperação. Gere novos.</div>
                <?php } ?>
                <form action="<?= site_url('mapos/doisFatoresNovosCodigos') ?>" method="post" style="display:inline-block;margin-right:16px">
                    <label>Senha atual</label>
                    <input class="form-control" type="password" name="senha" required autocomplete="current-password">
                    <br><button class="button btn btn-info" type="submit"><span class="button__icon"><i class="bx bx-key"></i></span><span class="button__text2">Novos códigos</span></button>
                </form>
                <form action="<?= site_url('mapos/doisFatoresDesativar') ?>" method="post" style="display:inline-block" data-confirmar="Desativar a verificação em duas etapas?">
                    <label>Senha atual</label>
                    <input class="form-control" type="password" name="senha" required autocomplete="current-password">
                    <br><button class="button btn btn-danger" type="submit"><span class="button__icon"><i class="bx bx-shield-x"></i></span><span class="button__text2">Desativar</span></button>
                </form>
            <?php } ?>
        </div>
    </div>
</div>
</div>

<div id="modalImageUser" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
    <form action="<?= site_url('mapos/uploadUserImage'); ?>" id="formImageUser" enctype="multipart/form-data" method="post" class="form-horizontal">
        <div class="modal-header">
            <h5 class="modal-title" id="myModalLabel">MapOS - Atualizar Imagem do Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
        <div class="modal-body">
            <div class="alert alert-info">Selecione uma nova imagem do usuario. Tamanho indicado (130 X 130).</div>
            <div class="row mb-2">
                <label for="userfile" class="col-sm-3 col-form-label"><span class="required">Foto*</span></label>
                <div class="col-sm-9">
                    <input class="form-control" type="file" name="userfile" value="" />
                </div>
            </div>
        </div>
        <div class="modal-footer" style="display:flex;justify-content: center">
            <button type="button" class="button btn btn-warning" data-bs-dismiss="modal" id="btnCancelExcluir"><span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Cancelar</span></button>
            <button class="button btn btn-primary"><span class="button__icon"><i class="bx bx-sync"></i></span><span class="button__text2">Atualizar</span></button>
        </div>
    </form>
</div></div></div>


<script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/jquery.validate.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function() {

        $("#formImageUser").validate({
            rules: {
                userfile: {
                    required: true
                }
            },
            messages: {
                userfile: {
                    required: 'Campo Requerido.'
                }
            },

            errorClass: "invalid-feedback",
            errorElement: "span",
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalid');
                $(element).removeClass('is-valid');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalid');
                $(element).addClass('is-valid');
            }
        });

        $('#formSenha').validate({
            rules: {
                oldSenha: {
                    required: true
                },
                novaSenha: {
                    required: true
                },
                confirmarSenha: {
                    equalTo: "#novaSenha"
                }
            },
            messages: {
                oldSenha: {
                    required: 'Campo Requerido'
                },
                novaSenha: {
                    required: 'Campo Requerido.'
                },
                confirmarSenha: {
                    equalTo: 'As senhas não combinam.'
                }
            },

            errorClass: "invalid-feedback",
            errorElement: "span",
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalid');
                $(element).addClass('is-valid');
            }
        });
    });
</script>
