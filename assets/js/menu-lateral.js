/**
 * Categorias recolhiveis do menu lateral administrativo (tema/menu.php).
 *
 * O menu tem 48 destinos. Sem isso, a barra lateral virava uma lista unica e
 * longa: para chegar em "Conciliação bancária" era preciso rolar por quase
 * tudo. Agora cada grupo do catalogo e uma categoria que abre e fecha, e so a
 * categoria da tela atual (ou a primeira, quando nenhuma esta marcada) nasce
 * aberta.
 *
 * A escolha do usuario fica no localStorage. Sem storage (modo privado, cota
 * cheia) o menu segue funcionando: abre e fecha na sessao, sem persistir.
 *
 * So adiciona comportamento. Se a marcacao de categoria nao existir, nao faz
 * nada — e o caso do menu do tecnico e da area do cliente, que usam a mesma
 * barra lateral sem categoria.
 */
(function () {
    'use strict';

    var sidebar = document.getElementById('sidebar');
    if (!sidebar) {
        return;
    }

    var categorias = sidebar.querySelectorAll('.nv-cat');
    if (!categorias.length) {
        return;
    }

    var CHAVE = 'menuCategorias';

    function ler() {
        try {
            var bruto = window.localStorage.getItem(CHAVE);
            var dados = bruto ? JSON.parse(bruto) : null;

            return dados && typeof dados === 'object' ? dados : {};
        } catch (e) {
            return {};
        }
    }

    function gravar(estado) {
        try {
            window.localStorage.setItem(CHAVE, JSON.stringify(estado));
        } catch (e) {
            /* sem storage: a escolha vale so nesta pagina */
        }
    }

    var estado = ler();

    function abrir(categoria, aberto) {
        var botao = categoria.querySelector('.nv-cat-btn');
        var lista = categoria.querySelector('.nv-sub');

        if (!botao || !lista) {
            return;
        }

        categoria.classList.toggle('nv-cat-aberta', aberto);
        botao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
        // hidden tambem esconde de leitores de tela, nao so visualmente
        if (aberto) {
            lista.removeAttribute('hidden');
        } else {
            lista.setAttribute('hidden', 'hidden');
        }
    }

    var temAtual = sidebar.querySelector('.nv-cat-atual') !== null;

    for (var i = 0; i < categorias.length; i++) {
        var categoria = categorias[i];
        var id = categoria.getAttribute('data-cat') || String(i);
        var aberto;

        if (Object.prototype.hasOwnProperty.call(estado, id)) {
            aberto = !!estado[id];
        } else {
            // Padrao: a categoria da tela atual; sem tela marcada, a primeira.
            aberto = categoria.classList.contains('nv-cat-atual') || (!temAtual && i === 0);
        }

        abrir(categoria, aberto);

        (function (categoria, id) {
            var botao = categoria.querySelector('.nv-cat-btn');
            if (!botao) {
                return;
            }

            botao.addEventListener('click', function () {
                var vaiAbrir = !categoria.classList.contains('nv-cat-aberta');
                abrir(categoria, vaiAbrir);
                estado[id] = vaiAbrir;
                gravar(estado);
            });
        })(categoria, id);
    }
})();
