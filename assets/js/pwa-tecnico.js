/**
 * Registra o service worker da Area do Tecnico e avisa quando o aparelho fica sem internet
 * (ADR-005, F4).
 */
(function (window, document) {
    'use strict';

    var script = document.currentScript;
    var base = script ? script.src.replace(/assets\/js\/pwa-tecnico\.js.*$/, '') : '/';

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(base + 'sw-tecnico.js', { scope: new URL(base).pathname + 'index.php/tecnico' })
                .catch(function (e) { console.warn('Service worker nao registrado:', e); });
        });
    }

    function faixa() {
        var el = document.getElementById('pwa-sem-internet');
        if (!el) {
            el = document.createElement('div');
            el.id = 'pwa-sem-internet';
            el.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:3001;background:#e17055;color:#fff;' +
                'text-align:center;font-size:13px;padding:6px 10px;display:none';
            el.textContent = 'Sem internet. Você pode continuar: fotos, início e fim do atendimento ficam guardados no aparelho.';
            document.body.appendChild(el);
        }
        el.style.display = navigator.onLine ? 'none' : 'block';
    }

    window.addEventListener('online', faixa);
    window.addEventListener('offline', faixa);
    document.addEventListener('DOMContentLoaded', faixa);
})(window, document);
