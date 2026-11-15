/*
 * Medidor de seguranca da senha no cadastro de usuario
 * (application/views/usuarios/formulario.php).
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
        { texto: 'Fraca', classe: 'bg-danger', largura: 25 },
        { texto: 'Razoável', classe: 'bg-warning', largura: 50 },
        { texto: 'Boa', classe: 'bg-info', largura: 75 },
        { texto: 'Forte', classe: 'bg-success', largura: 100 },
    ];

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { forcaSenha: forcaSenha };
        return;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var campo = document.getElementById('senha');
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
                texto.textContent = textoInicial;
                return;
            }
            var nivel = NIVEIS[forcaSenha(senha)];
            barra.className = 'progress-bar ' + nivel.classe;
            barra.style.width = nivel.largura + '%';
            texto.textContent = 'Força: ' + nivel.texto + (Array.from(senha).length < 6 ? ' (use pelo menos 6 caracteres)' : '');
        });
    });
})();
