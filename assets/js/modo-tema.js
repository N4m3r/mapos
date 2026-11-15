/*
 * Modo claro/escuro do painel: botao do topo (#btn-modo-escuro) e a secao
 * Aparencia da Minha Conta (window.MaposModoTema).
 *
 * O servidor ja entrega a pagina no tema certo (cookie mapos_modo_<id> lido no
 * MY_Controller), entao nao ha piscada ao carregar. Aqui so a troca sem recarregar:
 * a folha do tema nova e carregada antes de a antiga sair, e o atributo
 * data-bs-theme do <html> acompanha para os componentes do Bootstrap 5.
 * Cada troca dispara o evento 'mapos:modo-tema' no document.
 */
(function () {
    'use strict';

    var LOGO_ESCURO = ['white', 'whitegreen'];
    var UM_ANO = 60 * 60 * 24 * 365;

    function corpo() {
        return document.body;
    }

    function escuroAgora() {
        return document.documentElement.getAttribute('data-bs-theme') === 'dark';
    }

    function gravarCookie(valor, maxAge) {
        var b = corpo();
        var nome = b && b.getAttribute('data-modo-cookie');
        if (!nome) {
            return;
        }
        var seguro = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = nome + '=' + valor + '; Max-Age=' + maxAge + '; Path=' +
            (b.getAttribute('data-cookie-path') || '/') + '; SameSite=Lax' + seguro;
    }

    // modo: 'claro' | 'escuro' | 'sistema' (volta ao tema das Configuracoes: precisa recarregar)
    function aplicar(modo) {
        var b = corpo();
        if (!b || !b.getAttribute('data-tema-escuro')) {
            return;
        }
        if (modo === 'sistema') {
            gravarCookie('', 0);
            location.reload();
            return;
        }
        var escuro = modo === 'escuro';
        var tema = b.getAttribute('data-tema-' + modo);

        trocarFolha(b.getAttribute('data-css-' + modo) || '');
        b.setAttribute('data-theme', tema);
        document.documentElement.setAttribute('data-bs-theme', escuro ? 'dark' : 'light');
        trocarLogo(tema);
        atualizarBotao(escuro);
        gravarCookie(modo, UM_ANO);

        var evento;
        try {
            evento = new CustomEvent('mapos:modo-tema', { detail: { modo: modo } });
        } catch (e) {
            evento = document.createEvent('CustomEvent');
            evento.initCustomEvent('mapos:modo-tema', false, false, { modo: modo });
        }
        document.dispatchEvent(evento);
    }

    function atualizarBotao(escuro) {
        var botao = document.getElementById('btn-modo-escuro');
        if (!botao) {
            return;
        }
        var rotulo = escuro ? 'Ativar modo claro' : 'Ativar modo escuro';
        botao.setAttribute('aria-pressed', escuro ? 'true' : 'false');
        botao.setAttribute('aria-label', rotulo);
        botao.setAttribute('title', rotulo);
        var icone = botao.querySelector('i');
        if (icone) {
            icone.classList.toggle('bx-sun', escuro);
            icone.classList.toggle('bx-moon', !escuro);
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

    function iniciar() {
        var botao = document.getElementById('btn-modo-escuro');
        if (botao) {
            botao.addEventListener('click', function (evento) {
                evento.preventDefault();
                aplicar(escuroAgora() ? 'claro' : 'escuro');
            });
        }
    }

    window.MaposModoTema = { aplicar: aplicar, escuro: escuroAgora };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
