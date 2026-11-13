<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Área do Cliente · <?= htmlspecialchars($this->config->item('app_name'), ENT_QUOTES, 'UTF-8') ?></title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#070b18">
    <meta name="description" content="<?= htmlspecialchars($this->config->item('app_name') . ' - ' . $this->config->item('app_subname'), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="csrf-token-name" content="<?= config_item("csrf_token_name") ?>">
    <meta name="csrf-cookie-name" content="<?= config_item("csrf_cookie_name") ?>">
    <link rel="shortcut icon" href="<?= base_url('assets/img/favicon.png') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= base_url('assets/boxicons/css/boxicons.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/login-cliente.css') ?>?v=<?= asset_versao('assets/css/login-cliente.css', '1') ?>" />
    <?= jquery_tags() ?>
    <script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?= base_url('assets/js/csrf.js') ?>?v=<?= asset_versao('assets/js/csrf.js', '4') ?>"></script>
</head>

<?php
$parse_email = trim((string) $this->input->get('e'));
$logoEmitente = emitente_logo_url();
$logo = $logoEmitente !== null ? $logoEmitente : base_url('assets/img/logo-mapos-branco.png');
?>

<body class="lc">
    <div class="lc-bg" aria-hidden="true">
        <div class="lc-grid"></div>
        <div class="lc-blob b1"></div>
        <div class="lc-blob b2"></div>
        <div class="lc-blob b3"></div>
        <div class="lc-particles" id="lcParticles"></div>
    </div>

    <main class="lc-main">
        <section class="lc-hero">
            <span class="lc-badge"><span class="dot"></span> Portal online</span>
            <h1 class="lc-title">Área do<br><span class="grad">Cliente</span></h1>
            <p class="lc-sub">Acompanhe suas ordens de serviço, orçamentos e cobranças em um só lugar, a qualquer hora.</p>
            <ul class="lc-features">
                <li><i class="bx bx-wrench"></i> Status das ordens de serviço em tempo real</li>
                <li><i class="bx bx-receipt"></i> Orçamentos, vendas e cobranças</li>
                <li><i class="bx bx-shield-quarter"></i> Acesso seguro aos seus documentos</li>
            </ul>
            <div class="lc-orbit" aria-hidden="true">
                <div class="ring"><i class="bx bx-cog"></i></div>
                <div class="ring r2"><i class="bx bx-file"></i></div>
                <div class="core"></div>
            </div>
        </section>

        <div class="lc-card-wrap">
            <div class="lc-card" id="lcCard">
                <form id="formLogin" method="post" action="<?= site_url('mine/login') ?>" novalidate>
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                    <div class="lc-stagger">
                        <div class="lc-logo"><img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo"></div>
                        <div>
                            <h2>Bem-vindo de volta</h2>
                            <p class="lc-hint">Entre com seus dados de acesso</p>
                        </div>

                        <div class="lc-field">
                            <input id="email" name="email" type="email" placeholder=" " autocomplete="username" value="<?= htmlspecialchars($parse_email, ENT_QUOTES, 'UTF-8') ?>" />
                            <i class="bx bx-envelope lc-ico"></i>
                            <label for="email">E-mail</label>
                        </div>

                        <div class="lc-field">
                            <input id="senha" name="senha" type="password" placeholder=" " autocomplete="current-password" />
                            <i class="bx bx-lock-alt lc-ico"></i>
                            <label for="senha">Senha</label>
                            <button type="button" class="lc-eye" id="lcEye" aria-label="Mostrar senha"><i class="bx bx-show"></i></button>
                        </div>

                        <div class="lc-row">
                            <a class="lc-link" href="<?= site_url('mine/resetarSenha') ?>">Esqueceu a senha?</a>
                        </div>

                        <button type="submit" class="lc-btn" id="lcBtn">
                            <span class="lc-txt">Acessar</span><i class="bx bx-right-arrow-alt"></i><span class="lc-spin"></span>
                        </button>

                        <div class="lc-foot">
                            <span>Versão <?= htmlspecialchars($this->config->item('app_version'), ENT_QUOTES, 'UTF-8') ?></span>
                            <a class="lc-link" href="https://github.com/RamonSilva20/mapos" target="_blank" rel="noopener"><?= date('Y'); ?> &copy; Ramon Silva</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

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
            var reduzir = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Particulas subindo no fundo
            if (!reduzir) {
                var box = document.getElementById('lcParticles');
                for (var i = 0; i < 28; i++) {
                    var p = document.createElement('span');
                    var s = (Math.random() * 3 + 1.5).toFixed(1);
                    p.style.left = (Math.random() * 100).toFixed(2) + '%';
                    p.style.width = p.style.height = s + 'px';
                    p.style.animationDuration = (Math.random() * 14 + 10).toFixed(1) + 's';
                    p.style.animationDelay = (-Math.random() * 20).toFixed(1) + 's';
                    box.appendChild(p);
                }

                // Inclinacao 3D do card seguindo o ponteiro (desktop)
                var card = document.getElementById('lcCard');
                if (window.matchMedia('(hover: hover) and (min-width: 901px)').matches) {
                    document.addEventListener('mousemove', function(e) {
                        var x = (e.clientX / window.innerWidth - 0.5) * 2;
                        var y = (e.clientY / window.innerHeight - 0.5) * 2;
                        card.style.transform = 'rotateY(' + (x * 4).toFixed(2) + 'deg) rotateX(' + (-y * 4).toFixed(2) + 'deg)';
                    });
                    document.addEventListener('mouseleave', function() { card.style.transform = ''; });
                }
            }

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
                        $('#lcCard .lc-field').addClass('error');
                        setTimeout(function() { $('#lcCard .lc-field').removeClass('error'); }, 600);
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
                errorPlacement: function(error, element) { error.appendTo(element.closest('.lc-field')); },
                highlight: function(element) { $(element).closest('.lc-field').addClass('error'); },
                unhighlight: function(element) { $(element).closest('.lc-field').removeClass('error'); }
            });
        });
    </script>
</body>

</html>
