/*
 * Tela Minha Conta (application/views/mapos/minhaConta.php): validacao dos
 * formularios, forca da nova senha, mostrar/ocultar senha e os botoes de
 * aparencia ligados ao modo claro/escuro do topo (assets/js/modo-tema.js).
 */
(function ($) {
    'use strict';

    // Pontuacao simples: comprimento + variedade de caracteres (0 a 4).
    function forcaSenha(senha) {
        if (!senha) {
            return 0;
        }
        var pontos = 0;
        if (senha.length >= 6) pontos++;
        if (senha.length >= 10) pontos++;
        if (/[a-z]/.test(senha) && /[A-Z]/.test(senha)) pontos++;
        if (/\d/.test(senha)) pontos++;
        if (/[^A-Za-z0-9]/.test(senha)) pontos++;
        if (senha.length < 6) {
            return Math.min(pontos, 1);
        }
        return Math.min(pontos, 4);
    }

    var NIVEIS = [
        { texto: 'Mínimo de 6 caracteres. Misture letras, números e símbolos.', classe: '', largura: 0 },
        { texto: 'Fraca', classe: 'bg-danger', largura: 25 },
        { texto: 'Razoável', classe: 'bg-warning', largura: 50 },
        { texto: 'Boa', classe: 'bg-info', largura: 75 },
        { texto: 'Forte', classe: 'bg-success', largura: 100 },
    ];

    function atualizarForca() {
        var senha = document.getElementById('novaSenha').value;
        var nivel = NIVEIS[senha ? Math.max(forcaSenha(senha), 1) : 0];
        var barra = document.getElementById('forcaSenhaBarra');
        barra.className = 'progress-bar ' + nivel.classe;
        barra.style.width = nivel.largura + '%';
        var texto = senha && senha.length < 6 ? 'Fraca: use pelo menos 6 caracteres' : nivel.texto;
        document.getElementById('forcaSenhaTexto').textContent = senha ? 'Força: ' + texto : nivel.texto;
    }

    function marcarModo(modo) {
        $('.conta-modos [data-modo]').each(function () {
            var ativo = this.getAttribute('data-modo') === modo;
            this.classList.toggle('btn-primary', ativo);
            this.classList.toggle('btn-outline-secondary', !ativo);
            this.setAttribute('aria-checked', ativo ? 'true' : 'false');
        });
    }

    var opcoesValidacao = {
        errorClass: 'invalid-feedback',
        errorElement: 'span',
        errorPlacement: function (erro, elemento) {
            var campo = elemento.closest('.conta-senha-campo');
            erro.addClass('d-block').insertAfter(campo.length ? campo : elemento);
        },
        highlight: function (elemento) {
            $(elemento).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (elemento) {
            $(elemento).removeClass('is-invalid').addClass('is-valid');
        },
    };

    $(function () {
        if (!document.getElementById('minha-conta')) {
            return;
        }

        if ($.fn.validate) {
            $('#formImageUser').validate($.extend({
                rules: { userfile: { required: true } },
                messages: { userfile: { required: 'Escolha uma imagem.' } },
            }, opcoesValidacao));

            $('#formSenha').validate($.extend({
                rules: {
                    oldSenha: { required: true },
                    novaSenha: { required: true, minlength: 6 },
                    confirmarSenha: { required: true, equalTo: '#novaSenha' },
                },
                messages: {
                    oldSenha: { required: 'Informe a senha atual.' },
                    novaSenha: { required: 'Informe a nova senha.', minlength: 'Use pelo menos 6 caracteres.' },
                    confirmarSenha: { required: 'Repita a nova senha.', equalTo: 'As senhas não combinam.' },
                },
            }, opcoesValidacao));
        }

        $('#novaSenha').on('input', atualizarForca);

        $('[data-ver-senha]').on('click', function () {
            var campo = document.getElementById(this.getAttribute('data-ver-senha'));
            var mostrar = campo.type === 'password';
            campo.type = mostrar ? 'text' : 'password';
            this.setAttribute('aria-label', (mostrar ? 'Ocultar' : 'Mostrar') + ' senha');
            this.setAttribute('title', (mostrar ? 'Ocultar' : 'Mostrar') + ' senha');
            $(this).find('i').toggleClass('bx-show', !mostrar).toggleClass('bx-hide', mostrar);
        });

        $('.conta-modos [data-modo]').on('click', function () {
            var modo = this.getAttribute('data-modo');
            if (!window.MaposModoTema) {
                return;
            }
            marcarModo(modo);
            window.MaposModoTema.aplicar(modo);
        });

        // Botao de lua/sol do topo tambem atualiza a selecao aqui.
        document.addEventListener('mapos:modo-tema', function (e) {
            marcarModo(e.detail && e.detail.modo);
        });
    });

    window.MaposForcaSenha = forcaSenha;
})(window.jQuery);
