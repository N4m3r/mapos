<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Área do Cliente · <?= htmlspecialchars($this->config->item('app_name'), ENT_QUOTES, 'UTF-8') ?></title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#05080f">
    <meta name="description" content="<?= htmlspecialchars($this->config->item('app_name') . ' - ' . $this->config->item('app_subname'), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="csrf-token-name" content="<?= config_item("csrf_token_name") ?>">
    <meta name="csrf-cookie-name" content="<?= config_item("csrf_cookie_name") ?>">
    <link rel="shortcut icon" href="<?= base_url('assets/img/favicon.png') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= base_url('assets/boxicons/css/boxicons.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/login-cftv.css') ?>?v=<?= asset_versao('assets/css/login-cftv.css') ?>" />
    <?= jquery_tags() ?>
    <script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?= base_url('assets/js/csrf.js') ?>?v=<?= asset_versao('assets/js/csrf.js', '4') ?>"></script>
</head>

<?php
$parse_email = trim((string) $this->input->get('e'));
$logoEmitente = emitente_logo_url();
$logo = $logoEmitente !== null ? $logoEmitente : base_url('assets/img/logo-mapos-branco.png');
?>

<body class="cftv">
    <div class="cftv-wall" data-cftv-wall aria-hidden="true"></div>
    <div class="cftv-veu" aria-hidden="true"></div>

    <main class="cftv-main">
        <section class="cftv-hero">
            <span class="cftv-badge">PORTAL ONLINE</span>
            <?php $this->load->view('tema/login_cftv_domo'); ?>
            <h1>Área do<br><span class="grad">Cliente</span></h1>
            <p>Acompanhe suas ordens de serviço, orçamentos e cobranças em um só lugar, a qualquer hora.</p>
            <ul class="cftv-chips">
                <li><i class="bx bx-wrench"></i> Status das OS em tempo real</li>
                <li><i class="bx bx-receipt"></i> Orçamentos e cobranças</li>
                <li><i class="bx bx-shield-quarter"></i> Documentos protegidos</li>
            </ul>
        </section>

        <div class="cftv-card" id="lcCard">
            <span class="cftv-canto c1"></span><span class="cftv-canto c2"></span>
            <span class="cftv-canto c3"></span><span class="cftv-canto c4"></span>

            <form id="formLogin" method="post" action="<?= site_url('mine/login') ?>" novalidate>
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

                <div class="cftv-logo"><img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo"></div>
                <div class="cftv-titulo">
                    <h2>Bem-vindo de volta</h2>
                    <p>Entre com seus dados de acesso</p>
                </div>

                <div class="cftv-campo">
                    <i class="bx bx-envelope"></i>
                    <input id="email" name="email" type="email" placeholder="E-mail" aria-label="E-mail" autocomplete="username" value="<?= htmlspecialchars($parse_email, ENT_QUOTES, 'UTF-8') ?>" />
                </div>

                <div class="cftv-campo">
                    <i class="bx bx-lock-alt"></i>
                    <input id="senha" name="senha" type="password" placeholder="Senha" aria-label="Senha" autocomplete="current-password" />
                    <button type="button" class="cftv-olho" id="lcEye" aria-label="Mostrar senha"><i class="bx bx-show"></i></button>
                </div>

                <div class="cftv-linha">
                    <a class="cftv-link" href="<?= site_url('mine/resetarSenha') ?>">Esqueceu a senha?</a>
                </div>

                <button type="submit" class="cftv-btn" id="lcBtn">
                    <span class="normal">Acessar</span><i class="bx bx-right-arrow-alt normal"></i>
                    <span class="carregando">Autenticando...</span>
                </button>

                <div class="cftv-rodape">
                    <span>Versão <?= htmlspecialchars($this->config->item('app_version'), ENT_QUOTES, 'UTF-8') ?></span>
                    <a href="https://github.com/RamonSilva20/mapos" target="_blank" rel="noopener"><?= date('Y'); ?> &copy; Ramon Silva</a>
                </div>
            </form>
        </div>
    </main>

    <script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/login-cftv.js') ?>?v=<?= asset_versao('assets/js/login-cftv.js') ?>"></script>
    <script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/jquery.validate.js') ?>"></script>
    <script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/sweetalert2.all.min.js') ?>"></script>
    <?php
    $jsonFlag = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    foreach (['success', 'error'] as $tipoFlash) {
        $msgFlash = $this->session->flashdata($tipoFlash);
        if ($msgFlash != null) { ?>
        <script nonce="<?= csp_nonce() ?>">
            Swal.fire({ position: 'center', icon: <?= json_encode($tipoFlash) ?>, title: <?= json_encode((string) $msgFlash, $jsonFlag) ?>, showConfirmButton: false, timer: 4000 });
        </script>
    <?php }
    } ?>

    <script nonce="<?= csp_nonce() ?>" type="text/javascript">
        $(function() {
            // Mostrar / ocultar senha
            $('#lcEye').on('click', function() {
                var $s = $('#senha'), mostrar = $s.attr('type') === 'password';
                $s.attr('type', mostrar ? 'text' : 'password');
                $(this).attr('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha')
                    .find('i').attr('class', mostrar ? 'bx bx-hide' : 'bx bx-show');
            });

            var $btn = $('#lcBtn');

            $("#formLogin").validate({
                rules: {
                    email: { required: true, email: true },
                    senha: { required: true }
                },
                messages: {
                    email: { required: 'Informe seu e-mail.', email: 'Insira um e-mail válido.' },
                    senha: { required: 'Informe sua senha.' }
                },
                submitHandler: function(form) {
                    $btn.addClass('is-loading');
                    $.ajax({
                        type: "POST",
                        url: "<?= site_url('mine/login') ?>?ajax=true",
                        data: $(form).serialize(),
                        dataType: 'json'
                    }).done(function(data) {
                        if (data.result == true) {
                            window.location.href = "<?= site_url('mine/painel') ?>";
                            return;
                        }
                        $btn.removeClass('is-loading');
                        $('#lcCard .cftv-campo').addClass('erro');
                        setTimeout(function() { $('#lcCard .cftv-campo').removeClass('erro'); }, 600);
                        Swal.fire({
                            position: 'center',
                            icon: 'error',
                            title: 'Os dados de acesso estão incorretos.\n Por favor tente novamente!',
                            showConfirmButton: false,
                            timer: 4000
                        });
                        if (data.MAPOS_TOKEN) {
                            $("input[name='<?= $this->security->get_csrf_token_name(); ?>']").val(data.MAPOS_TOKEN);
                        }
                    }).fail(function() {
                        $btn.removeClass('is-loading');
                        Swal.fire({ position: 'center', icon: 'error', title: 'Não foi possível conectar. Tente novamente.', showConfirmButton: false, timer: 4000 });
                    });
                    return false;
                },
                errorClass: "help-inline",
                errorElement: "span",
                errorPlacement: function(error, element) { error.appendTo(element.closest('.cftv-campo')); },
                highlight: function(element) { $(element).closest('.cftv-campo').addClass('erro'); },
                unhighlight: function(element) { $(element).closest('.cftv-campo').removeClass('erro'); }
            });
        });
    </script>
</body>

</html>
