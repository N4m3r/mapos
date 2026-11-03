/* Visualizador leve de anexos (sem dependencias). Uso:
 *   <a href="URL" data-lightbox="grupo">...</a>
 * Abre em tela cheia no mobile e em janela ajustada ao navegador no desktop.
 * Imagem: exibe com zoom por duplo toque/clique e navegacao (setas, swipe).
 * Outros (PDF etc.): iframe no desktop; no mobile mostra botao "Abrir arquivo".
 * O link original continua funcionando sem JS (target=_blank). */
(function () {
    'use strict';
    var ov, box, cnt, btnPrev, btnNext, btnOpen, lista = [], idx = 0, zoom = false, sx = 0, sy = 0, st = 0, lastTap = 0;
    var mobile = function () { return window.matchMedia('(max-width: 768px)').matches; };

    function el(tag, cls, html) { var e = document.createElement(tag); if (cls) { e.className = cls; } if (html) { e.innerHTML = html; } return e; }

    function montar() {
        if (ov) { return; }
        ov = el('div', 'alb-ov'); ov.setAttribute('role', 'dialog'); ov.setAttribute('aria-modal', 'true');
        var jan = el('div', 'alb-jan');
        var topo = el('div', 'alb-topo');
        cnt = el('span', 'alb-cnt');
        btnOpen = el('a', 'alb-btn', '&#8599;'); btnOpen.target = '_blank'; btnOpen.rel = 'noopener'; btnOpen.title = 'Abrir em nova aba'; btnOpen.setAttribute('aria-label', 'Abrir em nova aba');
        var fechar = el('button', 'alb-btn', '&times;'); fechar.type = 'button'; fechar.setAttribute('aria-label', 'Fechar');
        topo.appendChild(cnt); topo.appendChild(btnOpen); topo.appendChild(fechar);
        box = el('div', 'alb-box');
        btnPrev = el('button', 'alb-nav alb-prev', '&#8249;'); btnPrev.type = 'button'; btnPrev.setAttribute('aria-label', 'Anterior');
        btnNext = el('button', 'alb-nav alb-next', '&#8250;'); btnNext.type = 'button'; btnNext.setAttribute('aria-label', 'Próximo');
        jan.appendChild(topo); jan.appendChild(box); jan.appendChild(btnPrev); jan.appendChild(btnNext);
        ov.appendChild(jan); document.body.appendChild(ov);

        fechar.addEventListener('click', fecharOv);
        ov.addEventListener('click', function (e) { if (e.target === ov) { fecharOv(); } });
        btnPrev.addEventListener('click', function () { ir(-1); });
        btnNext.addEventListener('click', function () { ir(1); });
        box.addEventListener('dblclick', function (e) { if (e.target.tagName === 'IMG') { alternarZoom(); } });
        box.addEventListener('touchstart', function (e) {
            if (e.touches.length > 1) { return; }
            sx = e.touches[0].clientX; sy = e.touches[0].clientY; st = Date.now();
        }, { passive: true });
        box.addEventListener('touchend', function (e) {
            var t = e.changedTouches[0], dx = t.clientX - sx, dy = t.clientY - sy, agora = Date.now();
            if (Math.abs(dx) < 10 && Math.abs(dy) < 10 && e.target.tagName === 'IMG') {
                if (agora - lastTap < 300) { alternarZoom(); lastTap = 0; e.preventDefault(); return; }
                lastTap = agora; return;
            }
            if (!zoom && Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5 && agora - st < 800) { ir(dx < 0 ? 1 : -1); }
        });
        document.addEventListener('keydown', function (e) {
            if (!ov.classList.contains('aberto')) { return; }
            if (e.key === 'Escape') { fecharOv(); } else if (e.key === 'ArrowLeft') { ir(-1); } else if (e.key === 'ArrowRight') { ir(1); }
        });
    }

    function alternarZoom() { zoom = !zoom; box.classList.toggle('zoom', zoom); }
    function ir(d) { var n = idx + d; if (n >= 0 && n < lista.length) { mostrar(n); } }

    function arquivo(url) {
        box.innerHTML = '';
        if (mobile()) {
            var d = el('div', 'alb-file', '<p>Pré-visualização indisponível neste formato.</p>');
            var a = el('a', 'alb-abrir', 'Abrir arquivo'); a.href = url; a.target = '_blank'; a.rel = 'noopener';
            d.appendChild(a); box.appendChild(d);
        } else {
            var f = el('iframe', 'alb-frame'); f.src = url; f.title = 'Anexo'; box.appendChild(f);
        }
    }

    function mostrar(i) {
        idx = i; zoom = false; box.classList.remove('zoom'); box.scrollTop = 0; box.scrollLeft = 0;
        var url = lista[i];
        btnOpen.href = url;
        btnPrev.style.display = i > 0 ? '' : 'none';
        btnNext.style.display = i < lista.length - 1 ? '' : 'none';
        cnt.textContent = lista.length > 1 ? (i + 1) + ' / ' + lista.length : '';
        if (/\.pdf(\?|#|$)/i.test(url)) { arquivo(url); return; }
        box.innerHTML = '<div class="alb-load">Carregando…</div>';
        var img = new Image();
        img.className = 'alb-img'; img.alt = 'Anexo';
        img.onload = function () { if (lista[idx] === url) { box.innerHTML = ''; box.appendChild(img); } };
        img.onerror = function () { if (lista[idx] === url) { arquivo(url); } };
        img.src = url;
    }

    function fecharOv() {
        ov.classList.remove('aberto'); box.innerHTML = '';
        document.documentElement.classList.remove('alb-trava');
    }

    document.addEventListener('click', function (e) {
        var a = e.target.closest ? e.target.closest('a[data-lightbox]') : null;
        if (!a || e.ctrlKey || e.metaKey || e.shiftKey || e.button > 0) { return; }
        var href = a.getAttribute('href');
        if (!href || href === '#') { return; }
        e.preventDefault();
        montar();
        var g = a.getAttribute('data-lightbox'), links = [];
        document.querySelectorAll('a[data-lightbox]').forEach(function (x) {
            var h = x.getAttribute('href');
            if (x.getAttribute('data-lightbox') === g && h && h !== '#' && x.offsetParent !== null && links.indexOf(x.href) < 0) { links.push(x.href); }
        });
        if (links.indexOf(a.href) < 0) { links = [a.href]; }
        lista = links;
        ov.classList.add('aberto');
        document.documentElement.classList.add('alb-trava');
        mostrar(lista.indexOf(a.href));
    });
})();
