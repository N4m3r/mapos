/**
 * Paleta de comandos (Ctrl+K / Cmd+K) - ADR-002.
 *
 * Lista os links do menu lateral (#sidebar), que ja vem filtrado pelas
 * permissoes do usuario, e permite ir a qualquer tela digitando parte do nome.
 * Enter sem resultado selecionado cai na pesquisa global (mapos/pesquisar).
 *
 * So adiciona comportamento: se #sidebar nao existir, nao faz nada.
 */
(function () {
    'use strict';

    var sidebar = document.getElementById('sidebar');
    if (!sidebar) {
        return;
    }

    var script = document.currentScript;
    var urlPesquisa = (script && script.getAttribute('data-url-pesquisa')) || '';

    function normalizar(texto) {
        return (texto || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
    }

    function coletarItens() {
        var vistos = {};
        var itens = [];
        var links = sidebar.querySelectorAll('a[href]');
        for (var i = 0; i < links.length; i++) {
            var a = links[i];
            var href = a.getAttribute('href');
            if (!href || href === '#' || href.indexOf('javascript:') === 0 || vistos[href]) {
                continue;
            }
            var tituloEl = a.querySelector('.title') || a;
            var titulo = (tituloEl.textContent || '').replace(/\s+/g, ' ').trim();
            if (!titulo) {
                continue;
            }
            vistos[href] = true;
            itens.push({ titulo: titulo, href: a.href, busca: normalizar(titulo) });
        }
        return itens;
    }

    var itens = null;
    var filtrados = [];
    var selecionado = 0;
    var overlay, input, lista;

    function criarInterface() {
        var estilo = document.createElement('style');
        estilo.textContent =
            '.pc-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:100000;display:none;align-items:flex-start;justify-content:center;padding-top:12vh}' +
            '.pc-overlay.aberta{display:flex}' +
            '.pc-caixa{width:min(560px,calc(100vw - 32px));background:#fff;color:#222;border-radius:10px;box-shadow:0 12px 40px rgba(0,0,0,.35);overflow:hidden;font-family:inherit}' +
            '.pc-input{width:100%;box-sizing:border-box;border:0;border-bottom:1px solid #e5e5e5;padding:14px 16px;font-size:16px;outline:none;margin:0;height:auto;border-radius:0;box-shadow:none;color:#222;background:#fff}' +
            '.pc-lista{list-style:none;margin:0;padding:6px 0;max-height:50vh;overflow-y:auto}' +
            '.pc-lista li{padding:9px 16px;cursor:pointer;font-size:14px}' +
            '.pc-lista li.ativo{background:#2d6cdf;color:#fff}' +
            '.pc-dica{padding:8px 16px;font-size:12px;color:#888;border-top:1px solid #eee}';
        document.head.appendChild(estilo);

        overlay = document.createElement('div');
        overlay.className = 'pc-overlay';
        overlay.innerHTML =
            '<div class="pc-caixa" role="dialog" aria-label="Ir para">' +
            '<input class="pc-input" type="text" placeholder="Ir para... (digite o nome da tela ou pesquise)" autocomplete="off">' +
            '<ul class="pc-lista" role="listbox"></ul>' +
            '<div class="pc-dica">&uarr; &darr; navegar &middot; Enter abrir &middot; Esc fechar</div>' +
            '</div>';
        document.body.appendChild(overlay);

        input = overlay.querySelector('.pc-input');
        lista = overlay.querySelector('.pc-lista');

        overlay.addEventListener('mousedown', function (e) {
            if (e.target === overlay) {
                fechar();
            }
        });
        input.addEventListener('input', renderizar);
        input.addEventListener('keydown', teclaNoInput);
        lista.addEventListener('mousedown', function (e) {
            var li = e.target.closest('li[data-i]');
            if (li) {
                e.preventDefault();
                abrirItem(parseInt(li.getAttribute('data-i'), 10));
            }
        });
    }

    function renderizar() {
        var termo = normalizar(input.value);
        filtrados = termo
            ? itens.filter(function (it) { return it.busca.indexOf(termo) !== -1; })
            : itens.slice();
        selecionado = 0;

        var html = '';
        for (var i = 0; i < filtrados.length; i++) {
            html += '<li role="option" data-i="' + i + '"' + (i === 0 ? ' class="ativo"' : '') + '></li>';
        }
        if (termo && urlPesquisa) {
            html += '<li role="option" data-i="' + filtrados.length + '"' + (filtrados.length === 0 ? ' class="ativo"' : '') + '></li>';
        }
        lista.innerHTML = html;

        // textContent evita interpretar HTML vindo dos titulos ou do termo digitado
        var lis = lista.querySelectorAll('li');
        for (var j = 0; j < lis.length; j++) {
            lis[j].textContent = j < filtrados.length
                ? filtrados[j].titulo
                : 'Pesquisar "' + input.value.trim() + '" em clientes, OS, produtos e servicos';
        }
    }

    function marcar(indice) {
        var lis = lista.querySelectorAll('li');
        if (!lis.length) {
            return;
        }
        selecionado = (indice + lis.length) % lis.length;
        for (var i = 0; i < lis.length; i++) {
            lis[i].classList.toggle('ativo', i === selecionado);
        }
        lis[selecionado].scrollIntoView({ block: 'nearest' });
    }

    function abrirItem(indice) {
        if (indice < filtrados.length) {
            window.location.href = filtrados[indice].href;
        } else if (urlPesquisa && input.value.trim()) {
            window.location.href = urlPesquisa + '?termo=' + encodeURIComponent(input.value.trim());
        }
    }

    function teclaNoInput(e) {
        // Impede que os atalhos globais (shortcut.js: Esc volta ao inicio, F1..F12) disparem
        e.stopPropagation();
        if (e.key === 'Escape') {
            e.preventDefault();
            fechar();
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            marcar(selecionado + 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            marcar(selecionado - 1);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            abrirItem(selecionado);
        }
    }

    function abrir() {
        if (!overlay) {
            itens = coletarItens();
            criarInterface();
        }
        input.value = '';
        renderizar();
        overlay.classList.add('aberta');
        input.focus();
    }

    function fechar() {
        overlay.classList.remove('aberta');
    }

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && !e.altKey && !e.shiftKey && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            if (overlay && overlay.classList.contains('aberta')) {
                fechar();
            } else {
                abrir();
            }
        }
    });
})();
