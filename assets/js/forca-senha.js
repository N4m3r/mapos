/*
 * Medidor de seguranca da senha no cadastro de usuario
 * (application/views/usuarios/formulario.php) e na tela de primeiro acesso
 * (mapos/login_nova_senha.php). Campo: [data-forca-senha] ou #senha.
 * Tambem cuida do botao "Gerar senha temporaria" ([data-gerar-senha]).
 *
 * Mesma pontuacao de senha_forca() em general_helper.php, que e o valor
 * gravado em usuarios.senha_forca ao salvar: 1 fraca, 2 razoavel, 3 boa,
 * 4 forte. Se mudar uma, mude a outra.
 */
(function () {
    'use strict';

    var OBVIAS = ['123456', '1234567', '12345678', '123456789', '1234567890', '654321', '111111', '000000',
        'senha', 'senha123', 'senha1234', 'password', 'qwerty', 'abc123', 'admin', 'admin123', 'mapos', 'mapos123'];

    function forcaSenha(senha) {
        if (!senha) {
            return 0;
        }
        var tamanho = Array.from(senha).length;
        if (tamanho < 6 || OBVIAS.indexOf(senha.toLowerCase()) !== -1 || /^(.)\1*$/u.test(senha)) {
            return 1;
        }
        var pontos = 1;
        if (tamanho >= 10) pontos++;
        if (/[a-z]/.test(senha) && /[A-Z]/.test(senha)) pontos++;
        if (/\d/.test(senha)) pontos++;
        if (/[^A-Za-z0-9]/.test(senha)) pontos++;
        return Math.max(1, Math.min(pontos, 4));
    }

    var NIVEIS = [
        null,
        { texto: 'Fraca', classe: 'bg-danger', cor: '#e5484d', largura: 25 },
        { texto: 'Razoável', classe: 'bg-warning', cor: '#e8a317', largura: 50 },
        { texto: 'Boa', classe: 'bg-info', cor: '#0b8bd9', largura: 75 },
        { texto: 'Forte', classe: 'bg-success', cor: '#12a454', largura: 100 },
    ];

    // Senha temporaria legivel para ditar ao tecnico: sem 0/O, 1/l/I.
    function gerarSenha() {
        var partes = [
            ['abcdefghjkmnpqrstuvwxyz', 4],
            ['ABCDEFGHJKLMNPQRSTUVWXYZ', 2],
            ['23456789', 2],
            ['!@#$%*', 1],
            ['abcdefghjkmnpqrstuvwxyz', 1],
        ];
        var valores = new Uint32Array(10);
        globalThis.crypto.getRandomValues(valores);
        var senha = '';
        var n = 0;
        partes.forEach(function (parte) {
            for (var i = 0; i < parte[1]; i++) {
                senha += parte[0].charAt(valores[n++] % parte[0].length);
            }
        });
        return senha;
    }

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { forcaSenha: forcaSenha, gerarSenha: gerarSenha };
        return;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var campo = document.querySelector('[data-forca-senha]') || document.getElementById('senha');
        var barra = document.getElementById('forcaSenhaBarra');
        var texto = document.getElementById('forcaSenhaTexto');
        if (!campo || !barra || !texto) {
            return;
        }
        var textoInicial = texto.textContent;

        campo.addEventListener('input', function () {
            var senha = campo.value;
            if (!senha) {
                barra.className = 'progress-bar';
                barra.style.width = '0%';
                barra.style.backgroundColor = '';
                texto.textContent = textoInicial;
                return;
            }
            var nivel = NIVEIS[forcaSenha(senha)];
            barra.className = 'progress-bar ' + nivel.classe;
            barra.style.backgroundColor = nivel.cor;
            barra.style.width = nivel.largura + '%';
            texto.textContent = 'Força: ' + nivel.texto + (Array.from(senha).length < 6 ? ' (use pelo menos 6 caracteres)' : '');
        });

        var gerar = document.querySelector('[data-gerar-senha]');
        if (gerar) {
            gerar.addEventListener('click', function () {
                campo.value = gerarSenha();
                campo.type = 'text';
                campo.dispatchEvent(new Event('input'));
                var temporaria = document.getElementById('senha_temporaria');
                if (temporaria) temporaria.checked = true;
                campo.select();
            });
        }
    });
})();
