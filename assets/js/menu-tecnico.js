/**
 * Menu lateral (drawer) da Area do Tecnico/Colaborador.
 * Abre pelo botao #tec-menu-btn; fecha com X, toque fora, Esc ou ao escolher um item.
 * Foco preso no drawer enquanto aberto e devolvido ao botao ao fechar.
 */
(function (window, document) {
    'use strict';

    function iniciar() {
        var btn = document.getElementById('tec-menu-btn');
        var menu = document.getElementById('tec-menu');
        var fundo = document.getElementById('tec-menu-overlay');
        var fechar = document.getElementById('tec-menu-fechar');
        if (!btn || !menu || !fundo) { return; }

        var aberto = false;
        var SEL = 'a[href], button:not([disabled])';

        function focaveis() {
            return Array.prototype.filter.call(menu.querySelectorAll(SEL), function (el) {
                return el.offsetParent !== null || el === document.activeElement;
            });
        }

        function abrir() {
            if (aberto) { return; }
            aberto = true;
            menu.hidden = false;
            fundo.hidden = false;
            void menu.offsetWidth; // reflow para a transicao de entrada
            menu.classList.add('aberto');
            fundo.classList.add('aberto');
            document.body.classList.add('tec-menu-travado');
            btn.setAttribute('aria-expanded', 'true');
            var alvo = menu.querySelector('[aria-current="page"]') || fechar || focaveis()[0];
            if (alvo) { alvo.focus(); }
        }

        function fecharMenu(devolverFoco) {
            if (!aberto) { return; }
            aberto = false;
            menu.classList.remove('aberto');
            fundo.classList.remove('aberto');
            document.body.classList.remove('tec-menu-travado');
            btn.setAttribute('aria-expanded', 'false');
            setTimeout(function () {
                if (!aberto) { menu.hidden = true; fundo.hidden = true; }
            }, 220);
            if (devolverFoco !== false) { btn.focus(); }
        }

        btn.addEventListener('click', function () { if (aberto) { fecharMenu(); } else { abrir(); } });
        fundo.addEventListener('click', function () { fecharMenu(); });
        if (fechar) { fechar.addEventListener('click', function () { fecharMenu(); }); }

        // Escolher um item fecha o menu (links navegam normalmente)
        menu.addEventListener('click', function (e) {
            var el = e.target.closest ? e.target.closest('a[href], button.tec-menu-item') : null;
            if (el && menu.contains(el)) { fecharMenu(el.tagName === 'BUTTON'); }
        });

        document.addEventListener('keydown', function (e) {
            if (!aberto) { return; }
            if (e.key === 'Escape' || e.key === 'Esc') {
                e.preventDefault();
                fecharMenu();
                return;
            }
            if (e.key !== 'Tab') { return; }
            var lista = focaveis();
            if (!lista.length) { e.preventDefault(); return; }
            var primeiro = lista[0];
            var ultimo = lista[lista.length - 1];
            var fora = !menu.contains(document.activeElement);
            if (e.shiftKey && (document.activeElement === primeiro || fora)) {
                e.preventDefault(); ultimo.focus();
            } else if (!e.shiftKey && (document.activeElement === ultimo || fora)) {
                e.preventDefault(); primeiro.focus();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})(window, document);
