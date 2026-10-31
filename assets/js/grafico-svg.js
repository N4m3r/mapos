/* Tooltip dos graficos SVG (Grafico_svg.php): hover e foco de teclado. Texto via textContent. */
(function () {
    'use strict';
    var tip = null;

    function criar() {
        tip = document.createElement('div');
        tip.className = 'gsvg-tip';
        tip.setAttribute('role', 'status');
        tip.hidden = true;
        document.body.appendChild(tip);
    }

    function mostrar(marca, x, y) {
        if (!tip) { criar(); }
        var linhas = (marca.getAttribute('data-dica') || '').split('\n');
        tip.textContent = '';
        linhas.forEach(function (t, i) {
            var l = document.createElement('div');
            l.className = i === 0 ? 'gsvg-tip-t' : 'gsvg-tip-l';
            l.textContent = t;
            tip.appendChild(l);
        });
        tip.hidden = false;
        var w = tip.offsetWidth, h = tip.offsetHeight;
        var px = Math.min(Math.max(8, x - w / 2), document.documentElement.clientWidth - w - 8);
        var py = y - h - 10;
        if (py < 8) { py = y + 18; }
        tip.style.left = (px + window.pageXOffset) + 'px';
        tip.style.top = (py + window.pageYOffset) + 'px';
        marca.classList.add('gsvg-ativa');
    }

    function esconder() {
        if (tip) { tip.hidden = true; }
        var a = document.querySelectorAll('.gsvg-ativa');
        for (var i = 0; i < a.length; i++) { a[i].classList.remove('gsvg-ativa'); }
    }

    function marcaDe(el) {
        return el && el.closest ? el.closest('.gsvg-marca') : null;
    }

    document.addEventListener('pointermove', function (e) {
        var m = marcaDe(e.target);
        if (!m) { esconder(); return; }
        if (!m.classList.contains('gsvg-ativa')) { esconder(); }
        mostrar(m, e.clientX, e.clientY);
    });
    document.addEventListener('pointerleave', esconder);
    function mostrarNoFoco(m) {
        var r = m.getBoundingClientRect();
        mostrar(m, r.left + r.width / 2, r.top + Math.min(r.height / 2, 30));
    }

    document.addEventListener('focusin', function (e) {
        var m = marcaDe(e.target);
        if (m) { mostrarNoFoco(m); }
    });
    document.addEventListener('focusout', esconder);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { esconder(); } });
    // Focar uma marca pelo teclado rola a pagina: reposiciona em vez de esconder
    window.addEventListener('scroll', function () {
        var m = marcaDe(document.activeElement);
        if (m && tip && !tip.hidden) { mostrarNoFoco(m); } else { esconder(); }
    }, { passive: true });
})();
