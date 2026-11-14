/*
 * Botao de modo claro/escuro do topo do painel (#btn-modo-escuro).
 *
 * O servidor ja entrega a pagina no tema certo (cookie mapos_modo_<id> lido no
 * MY_Controller), entao nao ha piscada ao carregar. Aqui so a troca sem recarregar:
 * a folha do tema nova e carregada antes de a antiga sair, e o atributo
 * data-bs-theme do <html> acompanha para os componentes do Bootstrap 5.
 */
(function () {
    'use strict';

    var LOGO_ESCURO = ['white', 'whitegreen'];
    var UM_ANO = 60 * 60 * 24 * 365;

    function iniciar() {
        var botao = document.getElementById('btn-modo-escuro');
        var corpo = document.body;
        if (!botao || !corpo || !corpo.getAttribute('data-tema-escuro')) {
            return;
        }

        botao.addEventListener('click', function (evento) {
            evento.preventDefault();
            var escuroAgora = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            aplicar(escuroAgora ? 'claro' : 'escuro');
        });

        function aplicar(modo) {
            var tema = corpo.getAttribute('data-tema-' + modo);
            var css = corpo.getAttribute('data-css-' + modo) || '';
            var escuro = modo === 'escuro';

            trocarFolha(css);
            corpo.setAttribute('data-theme', tema);
            document.documentElement.setAttribute('data-bs-theme', escuro ? 'dark' : 'light');
            trocarLogo(tema);

            var rotulo = escuro ? 'Ativar modo claro' : 'Ativar modo escuro';
            botao.setAttribute('aria-pressed', escuro ? 'true' : 'false');
            botao.setAttribute('aria-label', rotulo);
            botao.setAttribute('title', rotulo);
            var icone = botao.querySelector('i');
            if (icone) {
                icone.classList.toggle('bx-sun', escuro);
                icone.classList.toggle('bx-moon', !escuro);
            }

            var cookie = corpo.getAttribute('data-modo-cookie');
            if (cookie) {
                var seguro = location.protocol === 'https:' ? '; Secure' : '';
                document.cookie = cookie + '=' + modo + '; Max-Age=' + UM_ANO + '; Path=' +
                    (corpo.getAttribute('data-cookie-path') || '/') + '; SameSite=Lax' + seguro;
            }
        }
    }

    // Carrega a folha nova ao lado da atual e so remove a antiga quando a nova chegar.
    function trocarFolha(href) {
        var atual = document.getElementById('tema-css');
        if (!atual) {
            return;
        }
        if (!href) {
            atual.removeAttribute('href');
            return;
        }
        if (atual.getAttribute('href') === href) {
            return;
        }
        var nova = document.createElement('link');
        nova.rel = 'stylesheet';
        nova.href = href;
        var concluir = function () {
            if (atual.parentNode) {
                atual.parentNode.removeChild(atual);
            }
            nova.id = 'tema-css';
        };
        nova.addEventListener('load', concluir);
        nova.addEventListener('error', concluir);
        atual.parentNode.insertBefore(nova, atual.nextSibling);
    }

    // Logo do menu: escura nos temas de menu claro, branca nos demais (igual a menu.php).
    function trocarLogo(tema) {
        var arquivo = LOGO_ESCURO.indexOf(tema) >= 0 ? 'logo-mapos.png' : 'logo-mapos-branco.png';
        var imagens = document.querySelectorAll('img[src*="assets/img/logo-mapos"]');
        for (var i = 0; i < imagens.length; i++) {
            imagens[i].src = imagens[i].src.replace(/logo-mapos(-branco)?\.png/, arquivo);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
