/**
 * Assinatura em Tela Cheia (overlay)
 * MAPOS OS - Sistema de Check-in
 *
 * Abre um overlay ocupando toda a tela para o usuario assinar com conforto
 * (util no celular, onde o canvas do modal fica pequeno). Usa o proprio
 * AssinaturaCanvas (tracos suaves, DPR, desfazer) e, ao confirmar, transfere os
 * tracos vetoriais para a assinatura de origem (AssinaturaManager), preservando
 * toda a logica de envio existente (exportacao recortada, fundo branco).
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

    var overlay, big, titleEl, erroEl, targetId = null;

    function injectStyles() {
        if (document.getElementById('assinatura-fs-styles')) { return; }
        var css = ''
            + '.assinatura-fs-overlay{position:fixed;top:0;left:0;right:0;bottom:0;z-index:20000;background:var(--tec-bg,#f4f6f9);color:var(--tec-ink,#2d2f45);display:none;flex-direction:column;}'
            + '.assinatura-fs-overlay.aberto{display:flex;}'
            + '.assinatura-fs-header{flex:0 0 auto;display:flex;align-items:center;justify-content:space-between;padding:10px 16px;background:var(--tec-card,#fff);border-bottom:1px solid var(--tec-line,#e2e6ea);}'
            + '.assinatura-fs-header h4{margin:0;font-size:16px;font-weight:700;color:var(--tec-ink,#333);}'
            + '.assinatura-fs-hint{font-size:12px;color:var(--tec-muted,#5f6478);margin-top:2px;}'
            + '.assinatura-fs-acoes{display:flex;align-items:center;gap:4px;}'
            + '.assinatura-fs-giro{border:1px solid var(--tec-input-line,#8a90a8);background:var(--tec-card,#fff);border-radius:8px;font-size:13px;color:var(--tec-ink,#2d2f45);padding:8px 10px;cursor:pointer;}'
            + '.assinatura-fs-close{border:none;background:transparent;font-size:32px;line-height:1;color:var(--tec-muted,#5f6478);cursor:pointer;padding:2px 12px;}'
            + '.assinatura-fs-erro{display:none;color:#b3261e;background:#fdecea;padding:8px 12px;font-size:13px;text-align:center;}'
            + '.assinatura-fs-body{flex:1 1 auto;position:relative;padding:12px;min-height:0;}'
            + '.assinatura-fs-canvas-wrap{position:relative;width:100%;height:100%;background:#fff;border:2px dashed var(--tec-input-line,#8a90a8);border-radius:10px;overflow:hidden;}'
            + '.assinatura-fs-canvas-wrap canvas{position:absolute;top:0;left:0;width:100%;height:100%;touch-action:none;cursor:crosshair;-webkit-user-select:none;user-select:none;}'
            + '.assinatura-fs-baseline{position:absolute;left:6%;right:6%;bottom:26%;border-bottom:2px solid #e6eaee;pointer-events:none;}'
            + '.assinatura-fs-baseline:before{content:"\\2715";position:absolute;left:0;bottom:1px;color:#cdd3d9;font-size:16px;line-height:1;}'
            + '.assinatura-fs-footer{flex:0 0 auto;display:flex;gap:10px;padding:10px 16px;background:var(--tec-card,#fff);border-top:1px solid var(--tec-line,#e2e6ea);}'
            + '.assinatura-fs-footer .btn{flex:1;padding:14px 6px;font-size:16px;font-weight:700;border-radius:10px;min-height:52px;}'
            + '.assinatura-tap-hint{display:none;}'
            + '@media (max-width:768px){'
            + '  .assinatura-canvas-wrapper{position:relative;}'
            + '  .assinatura-tap-hint{display:flex;align-items:center;justify-content:center;gap:6px;position:absolute;top:0;left:0;right:0;bottom:0;z-index:5;background:rgba(255,255,255,.78);color:#2a6fb0;font-weight:600;font-size:14px;text-align:center;cursor:pointer;border-radius:4px;padding:8px;}'
            + '  .assinatura-container.assinado .assinatura-tap-hint{background:rgba(255,255,255,.30);color:#2e7d32;}'
            + '}'
            + '@media (orientation:landscape) and (max-height:500px){'
            + '  .assinatura-fs-hint{display:none;}'
            + '  .assinatura-fs-header{padding:4px 12px;}'
            + '  .assinatura-fs-body{padding:6px 12px;}'
            + '  .assinatura-fs-footer{padding:6px 12px;}'
            + '  .assinatura-fs-footer .btn{min-height:44px;padding:8px 6px;}'
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
            + '<div class="assinatura-fs-acoes">'
            + '<button type="button" class="assinatura-fs-giro" aria-label="Paisagem"><i class="bx bx-fullscreen"></i> Paisagem</button>'
            + '<button type="button" class="assinatura-fs-close" aria-label="Fechar">&times;</button></div>'
            + '</div>'
            + '<div class="assinatura-fs-erro" role="alert"></div>'
            + '<div class="assinatura-fs-body"><div class="assinatura-fs-canvas-wrap">'
            + '<canvas class="assinatura-fs-canvas" id="assinatura-fs-canvas"></canvas>'
            + '<div class="assinatura-fs-baseline"></div>'
            + '</div></div>'
            + '<div class="assinatura-fs-footer">'
            + '<button type="button" class="btn assinatura-fs-limpar"><i class="bx bx-eraser"></i> Limpar</button>'
            + '<button type="button" class="btn assinatura-fs-desfazer"><i class="bx bx-undo"></i> Desfazer</button>'
            + '<button type="button" class="btn btn-success assinatura-fs-confirmar"><i class="bx bx-check"></i> Confirmar</button>'
            + '</div>';
        document.body.appendChild(overlay);

        titleEl = overlay.querySelector('.assinatura-fs-title');
        erroEl = overlay.querySelector('.assinatura-fs-erro');

        overlay.querySelector('.assinatura-fs-close').addEventListener('click', fechar);
        overlay.querySelector('.assinatura-fs-limpar').addEventListener('click', function () { if (big) { big.limpar(); } erro(''); });
        overlay.querySelector('.assinatura-fs-desfazer').addEventListener('click', function () { if (big) { big.desfazer(); } erro(''); });
        overlay.querySelector('.assinatura-fs-confirmar').addEventListener('click', confirmar);
        overlay.querySelector('.assinatura-fs-giro').addEventListener('click', alternarPaisagem);
    }

    function erro(msg) {
        if (!erroEl) { return; }
        erroEl.textContent = msg || '';
        erroEl.style.display = msg ? 'block' : 'none';
    }

    // Tela cheia + paisagem: opcional e best-effort (iPhone nao suporta Fullscreen API)
    function alternarPaisagem() {
        try {
            if (document.fullscreenElement) {
                if (screen.orientation && screen.orientation.unlock) { screen.orientation.unlock(); }
                document.exitFullscreen();
                return;
            }
            var req = overlay.requestFullscreen || overlay.webkitRequestFullscreen;
            if (!req) { erro('Gire o aparelho manualmente para a horizontal.'); return; }
            var r = req.call(overlay);
            var travar = function () {
                if (screen.orientation && screen.orientation.lock) { screen.orientation.lock('landscape').catch(function () {}); }
            };
            if (r && r.then) { r.then(travar).catch(function () {}); } else { travar(); }
        } catch (_) {}
    }

    function open(containerId, titulo) {
        build();
        targetId = containerId;
        titleEl.textContent = titulo || 'Assinatura';
        erro('');
        overlay.classList.add('aberto');
        document.body.style.overflow = 'hidden';
        // Aguarda o layout do overlay para medir o canvas e pre-carregar assinatura existente
        setTimeout(function () {
            if (big && big.destruir) { big.destruir(); }
            big = new AssinaturaCanvas('assinatura-fs-canvas', {
                espessura: 4,
                preencherContainer: true,
                onChange: function () { erro(''); }
            });
            var src = window.AssinaturaManager && AssinaturaManager.obter(containerId);
            if (src && src.temTracos()) {
                if (src.tracos.length) { big.definirTracos(src.obterTracos()); }
                else if (src._base) { big.carregarImagem(src.obterImagem('image/png', { recortar: false })); }
            }
        }, 60);
    }

    function fechar() {
        try {
            if (document.fullscreenElement) { document.exitFullscreen(); }
            if (screen.orientation && screen.orientation.unlock) { screen.orientation.unlock(); }
        } catch (_) {}
        overlay.classList.remove('aberto');
        document.body.style.overflow = '';
        if (big && big.destruir) { big.destruir(); }
        big = null;
        targetId = null;
    }

    function confirmar() {
        var src = window.AssinaturaManager && AssinaturaManager.obter(targetId);
        if (!src || !big) { fechar(); return; }
        if (!big.temTracos()) { src.limpar(); marcarContainer(targetId, false); fechar(); return; }
        var v = big.validar();
        if (!v.ok) { erro(v.motivo); return; }
        var id = targetId;
        if (big.tracos.length) {
            src.definirTracos(big.obterTracos());
            marcarContainer(id, true);
        } else {
            src.carregarImagem(big.obterImagem('image/png', { recortar: false }), function () { marcarContainer(id, true); });
        }
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
