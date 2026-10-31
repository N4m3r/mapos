/**
 * Registra o service worker da Area do Tecnico e avisa quando o aparelho fica sem internet
 * (ADR-005, F4). Tambem cuida do botao "Instalar app" e respeita o modo offline
 * desligado pelo tecnico (localStorage offlineEnabled = 'false').
 */
(function (window, document) {
    'use strict';

    var script = document.currentScript;
    var base = script ? script.src.replace(/assets\/js\/pwa-tecnico\.js.*$/, '') : '/';
    var promptInstalar = null;

    function offlineLigado() {
        try { return window.localStorage.getItem('offlineEnabled') !== 'false'; } catch (e) { return true; }
    }

    function botaoInstalar() { return document.getElementById('install-btn'); }

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        promptInstalar = e;
        var btn = botaoInstalar();
        if (btn) { btn.style.display = 'inline-block'; }
    });

    window.addEventListener('appinstalled', function () {
        promptInstalar = null;
        var btn = botaoInstalar();
        if (btn) { btn.style.display = 'none'; }
    });

    document.addEventListener('DOMContentLoaded', function () {
        var btn = botaoInstalar();
        if (!btn) { return; }
        if (promptInstalar) { btn.style.display = 'inline-block'; }
        btn.addEventListener('click', function () {
            if (!promptInstalar) { return; }
            promptInstalar.prompt();
            promptInstalar.userChoice.then(function () {
                promptInstalar = null;
                btn.style.display = 'none';
            });
        });
    });

    if ('serviceWorker' in navigator && offlineLigado()) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(base + 'sw-tecnico.js', { scope: new URL(base).pathname + 'index.php/tecnico' })
                .catch(function (e) { console.warn('Service worker nao registrado:', e); });
        });
    }

    // Desligar o modo offline remove o service worker (a fila ja gravada continua no aparelho)
    window.addEventListener('offlineToggle', function () {
        if (!('serviceWorker' in navigator)) { return; }
        if (offlineLigado()) {
            navigator.serviceWorker.register(base + 'sw-tecnico.js', { scope: new URL(base).pathname + 'index.php/tecnico' })
                .catch(function () {});
        } else {
            navigator.serviceWorker.getRegistrations().then(function (regs) {
                regs.forEach(function (r) { if (r.active && /sw-tecnico\.js/.test(r.active.scriptURL)) { r.unregister(); } });
            });
        }
    });

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
