/**
 * Assinatura em Tela Cheia (overlay)
 * MAPOS OS - Sistema de Check-in
 *
 * Abre um overlay ocupando toda a tela para o usuario assinar com conforto
 * (util no celular, onde o canvas do modal fica pequeno). Ao confirmar,
 * a imagem e transferida de volta para a assinatura de origem (o AssinaturaCanvas
 * registrado no AssinaturaManager), preservando toda a logica de envio existente.
 *
 * Dispara em:
 *   - clique em .btn-assinatura-ampliar[data-target="<id-do-container>"]
 *   - toque na tarja .assinatura-tap-hint (mobile) dentro de um .assinatura-container
 *
 * API: window.AssinaturaFullscreen.open(containerId, titulo)
 */
(function () {
    'use strict';

    if (window.AssinaturaFullscreen) { return; }

    var overlay, bigCanvas, ctx, titleEl;
    var desenhando = false, vazio = true, targetId = null;

    function injectStyles() {
        if (document.getElementById('assinatura-fs-styles')) { return; }
        var css = ''
            + '.assinatura-fs-overlay{position:fixed;top:0;left:0;right:0;bottom:0;z-index:20000;background:#f4f6f9;display:none;flex-direction:column;}'
            + '.assinatura-fs-overlay.aberto{display:flex;}'
            + '.assinatura-fs-header{flex:0 0 auto;display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#fff;border-bottom:1px solid #e2e6ea;}'
            + '.assinatura-fs-header h4{margin:0;font-size:16px;font-weight:700;color:#333;}'
            + '.assinatura-fs-hint{font-size:12px;color:#8a939c;margin-top:2px;}'
            + '.assinatura-fs-close{border:none;background:transparent;font-size:28px;line-height:1;color:#9aa2ab;cursor:pointer;padding:2px 10px;}'
            + '.assinatura-fs-body{flex:1 1 auto;position:relative;padding:12px;}'
            + '.assinatura-fs-canvas-wrap{position:relative;width:100%;height:100%;background:#fff;border:2px dashed #cbd3da;border-radius:10px;overflow:hidden;}'
            + '.assinatura-fs-canvas-wrap canvas{position:absolute;top:0;left:0;width:100%;height:100%;touch-action:none;cursor:crosshair;-webkit-user-select:none;user-select:none;}'
            + '.assinatura-fs-baseline{position:absolute;left:6%;right:6%;bottom:26%;border-bottom:2px solid #e6eaee;pointer-events:none;}'
            + '.assinatura-fs-baseline:before{content:"\\2715";position:absolute;left:0;bottom:1px;color:#cdd3d9;font-size:16px;line-height:1;}'
            + '.assinatura-fs-footer{flex:0 0 auto;display:flex;gap:10px;padding:12px 16px;background:#fff;border-top:1px solid #e2e6ea;}'
            + '.assinatura-fs-footer .btn{flex:1;padding:14px;font-size:15px;font-weight:600;border-radius:8px;}'
            + '.assinatura-tap-hint{display:none;}'
            + '@media (max-width:768px){'
            + '  .assinatura-canvas-wrapper{position:relative;}'
            + '  .assinatura-tap-hint{display:flex;align-items:center;justify-content:center;gap:6px;position:absolute;top:0;left:0;right:0;bottom:0;z-index:5;background:rgba(255,255,255,.78);color:#2a6fb0;font-weight:600;font-size:14px;text-align:center;cursor:pointer;border-radius:4px;padding:8px;}'
            + '  .assinatura-container.assinado .assinatura-tap-hint{background:rgba(255,255,255,.30);color:#2e7d32;}'
            + '}';
        var style = document.createElement('style');
        style.id = 'assinatura-fs-styles';
        style.type = 'text/css';
        style.appendChild(document.createTextNode(css));
        document.head.appendChild(style);
    }

    function build() {
        if (overlay) { return; }
        injectStyles();
        overlay = document.createElement('div');
        overlay.className = 'assinatura-fs-overlay';
        overlay.innerHTML =
            '<div class="assinatura-fs-header">'
            + '<div><h4 class="assinatura-fs-title">Assinatura</h4>'
            + '<div class="assinatura-fs-hint"><i class="bx bx-mobile-landscape"></i> Gire o aparelho na horizontal para mais espaco</div></div>'
            + '<button type="button" class="assinatura-fs-close" aria-label="Fechar">&times;</button>'
            + '</div>'
            + '<div class="assinatura-fs-body"><div class="assinatura-fs-canvas-wrap">'
            + '<canvas class="assinatura-fs-canvas"></canvas>'
            + '<div class="assinatura-fs-baseline"></div>'
            + '</div></div>'
            + '<div class="assinatura-fs-footer">'
            + '<button type="button" class="btn assinatura-fs-limpar"><i class="bx bx-eraser"></i> Limpar</button>'
            + '<button type="button" class="btn btn-success assinatura-fs-confirmar"><i class="bx bx-check"></i> Confirmar</button>'
            + '</div>';
        document.body.appendChild(overlay);

        bigCanvas = overlay.querySelector('.assinatura-fs-canvas');
        ctx = bigCanvas.getContext('2d');
        titleEl = overlay.querySelector('.assinatura-fs-title');

        overlay.querySelector('.assinatura-fs-close').addEventListener('click', fechar);
        overlay.querySelector('.assinatura-fs-limpar').addEventListener('click', limpar);
        overlay.querySelector('.assinatura-fs-confirmar').addEventListener('click', confirmar);
        bindDesenho();

        window.addEventListener('resize', function () {
            if (overlay.classList.contains('aberto')) { ajustarCanvas(true); }
        });
    }

    function ajustarCanvas(preservar) {
        var imagem = (preservar && !vazio) ? bigCanvas.toDataURL() : null;
        var wrap = bigCanvas.parentElement;
        bigCanvas.width = wrap.clientWidth;
        bigCanvas.height = wrap.clientHeight;
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, bigCanvas.width, bigCanvas.height);
        ctx.strokeStyle = '#111111';
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        if (imagem) {
            var img = new Image();
            img.onload = function () { ctx.drawImage(img, 0, 0, bigCanvas.width, bigCanvas.height); };
            img.src = imagem;
        }
    }

    function obterPos(e) {
        var rect = bigCanvas.getBoundingClientRect();
        var cx, cy;
        if (e.touches && e.touches.length) { cx = e.touches[0].clientX; cy = e.touches[0].clientY; }
        else if (e.changedTouches && e.changedTouches.length) { cx = e.changedTouches[0].clientX; cy = e.changedTouches[0].clientY; }
        else { cx = e.clientX; cy = e.clientY; }
        return {
            x: (cx - rect.left) * (bigCanvas.width / rect.width),
            y: (cy - rect.top) * (bigCanvas.height / rect.height)
        };
    }

    function iniciar(e) {
        e.preventDefault();
        desenhando = true;
        vazio = false;
        var p = obterPos(e);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
        if (navigator.vibrate) { try { navigator.vibrate(8); } catch (_) {} }
    }
    function mover(e) {
        if (!desenhando) { return; }
        e.preventDefault();
        var p = obterPos(e);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
    }
    function terminar(e) {
        if (!desenhando) { return; }
        desenhando = false;
        ctx.closePath();
    }
    function bindDesenho() {
        bigCanvas.addEventListener('mousedown', iniciar);
        bigCanvas.addEventListener('mousemove', mover);
        window.addEventListener('mouseup', terminar);
        bigCanvas.addEventListener('touchstart', iniciar, { passive: false });
        bigCanvas.addEventListener('touchmove', mover, { passive: false });
        bigCanvas.addEventListener('touchend', terminar, { passive: false });
        bigCanvas.addEventListener('touchcancel', terminar, { passive: false });
    }

    function open(containerId, titulo) {
        build();
        targetId = containerId;
        titleEl.textContent = titulo || 'Assinatura';
        overlay.classList.add('aberto');
        document.body.style.overflow = 'hidden';
        vazio = true;
        // Aguarda o layout do overlay para medir o canvas e pre-carregar assinatura existente
        setTimeout(function () {
            ajustarCanvas(false);
            var src = window.AssinaturaManager && AssinaturaManager.obter(containerId);
            if (src && !src.estaVazio()) {
                var img = new Image();
                img.onload = function () { ctx.drawImage(img, 0, 0, bigCanvas.width, bigCanvas.height); vazio = false; };
                img.src = src.obterImagem();
            }
        }, 60);
    }

    function limpar() {
        ctx.clearRect(0, 0, bigCanvas.width, bigCanvas.height);
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, bigCanvas.width, bigCanvas.height);
        vazio = true;
    }

    function fechar() {
        overlay.classList.remove('aberto');
        document.body.style.overflow = '';
        targetId = null;
    }

    function confirmar() {
        var src = window.AssinaturaManager && AssinaturaManager.obter(targetId);
        if (!src) { fechar(); return; }
        if (vazio) { src.limpar(); marcarContainer(targetId, false); fechar(); return; }
        var data = bigCanvas.toDataURL('image/png');
        src.carregarImagem(data, function () { marcarContainer(targetId, true); });
        fechar();
    }

    function marcarContainer(id, assinado) {
        var el = document.getElementById(id);
        if (!el) { return; }
        if (assinado) { el.className += ' assinado'; }
        else { el.className = el.className.replace(/\bassinado\b/g, '').replace(/\s+/g, ' ').trim(); }
        var hint = el.querySelector('.assinatura-tap-hint');
        if (hint) {
            hint.innerHTML = assinado
                ? '<i class="bx bx-check-circle"></i> Assinado &mdash; toque para refazer'
                : '<i class="bx bx-edit"></i> Toque para assinar em tela cheia';
        }
    }

    // Delegacao de eventos (jQuery, presente em todas as telas)
    if (window.jQuery) {
        jQuery(document).on('click', '.btn-assinatura-ampliar', function (e) {
            e.preventDefault();
            open(jQuery(this).data('target'), jQuery(this).data('titulo'));
        });
        jQuery(document).on('click', '.assinatura-tap-hint', function (e) {
            e.preventDefault();
            var c = jQuery(this).closest('.assinatura-container');
            if (c.length) { open(c.attr('id'), jQuery(this).data('titulo')); }
        });
    }

    window.AssinaturaFullscreen = { open: open };
})();
