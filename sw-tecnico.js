/**
 * Service worker da Area do Tecnico (ADR-005, F4).
 *
 * Fica na raiz do site para poder controlar index.php/tecnico/*.
 * - Arquivos de assets/: usa o que estiver guardado e atualiza em segundo plano.
 * - Telas da area do tecnico: busca na rede; sem internet, mostra a ultima versao vista
 *   (ou a pagina "sem conexao"). Paginas que redirecionam (login) nao sao guardadas.
 * - POST nunca passa por cache: a fila offline (assets/js/fila-offline.js) cuida dos envios.
 * - A tela de login apaga estes caches (aparelho compartilhado).
 */
var VERSAO = 'v1';
var CACHE_ESTATICOS = 'mapos-tecnico-estaticos-' + VERSAO;
var CACHE_PAGINAS = 'mapos-tecnico-paginas-' + VERSAO;
var BASE = new URL('./', self.location).pathname;          // raiz do Map-OS (aceita subpasta)
var ESCOPO_TELAS = BASE + 'index.php/tecnico';
var PAGINA_OFFLINE = BASE + 'assets/pwa/offline.html';

var PRE_CACHE = [
    PAGINA_OFFLINE,
    BASE + 'assets/css/bootstrap.min.css',
    BASE + 'assets/boxicons/css/boxicons.min.css',
    BASE + 'assets/css/tecnico.css',
    BASE + 'assets/js/jquery-1.12.4.min.js',
    BASE + 'assets/js/fila-offline.js',
    BASE + 'assets/js/pwa-tecnico.js',
    BASE + 'assets/js/checkin.js',
    BASE + 'assets/js/checkin-fotos.js',
    BASE + 'assets/js/assinatura-canvas.js',
    BASE + 'assets/js/assinatura-fullscreen.js',
    BASE + 'assets/js/csrf.js',
    BASE + 'assets/img/favicon.png',
    BASE + 'assets/pwa/icone-192.png'
];

self.addEventListener('install', function (evento) {
    evento.waitUntil(caches.open(CACHE_ESTATICOS).then(function (cache) {
        // Um a um: um arquivo faltando nao pode impedir a instalacao
        return Promise.all(PRE_CACHE.map(function (url) {
            return cache.add(url).catch(function () {});
        }));
    }).then(function () { return self.skipWaiting(); }));
});

self.addEventListener('activate', function (evento) {
    evento.waitUntil(caches.keys().then(function (nomes) {
        return Promise.all(nomes.filter(function (n) {
            return n.indexOf('mapos-tecnico-') === 0 && n !== CACHE_ESTATICOS && n !== CACHE_PAGINAS;
        }).map(function (n) { return caches.delete(n); }));
    }).then(function () { return self.clients.claim(); }));
});

function ehTelaDoTecnico(url) {
    return url.origin === self.location.origin && url.pathname.indexOf(ESCOPO_TELAS) === 0;
}

function ehEstatico(url) {
    return url.origin === self.location.origin && url.pathname.indexOf(BASE + 'assets/') === 0;
}

self.addEventListener('fetch', function (evento) {
    var req = evento.request;
    if (req.method !== 'GET') {
        return;
    }
    var url = new URL(req.url);

    if (ehEstatico(url)) {
        evento.respondWith(caches.open(CACHE_ESTATICOS).then(function (cache) {
            return cache.match(req, { ignoreSearch: true }).then(function (guardado) {
                var daRede = fetch(req).then(function (resp) {
                    if (resp.ok) {
                        cache.put(req, resp.clone());
                    }
                    return resp;
                }).catch(function () { return guardado; });
                return guardado || daRede;
            });
        }));
        return;
    }

    if (ehTelaDoTecnico(url)) {
        evento.respondWith(fetch(req).then(function (resp) {
            var tipo = resp.headers.get('Content-Type') || '';
            if (resp.ok && !resp.redirected && (tipo.indexOf('text/html') === 0 || tipo.indexOf('application/json') === 0)) {
                var copia = resp.clone();
                caches.open(CACHE_PAGINAS).then(function (cache) { cache.put(req, copia); });
            }
            return resp;
        }).catch(function () {
            return caches.open(CACHE_PAGINAS).then(function (cache) {
                return cache.match(req).then(function (guardado) {
                    if (guardado) {
                        return guardado;
                    }
                    return req.mode === 'navigate'
                        ? caches.match(PAGINA_OFFLINE)
                        : new Response(JSON.stringify({ success: false, message: 'Sem conexão' }), { status: 503, headers: { 'Content-Type': 'application/json' } });
                });
            });
        }));
    }
});

// Background Sync (Chrome/Android): pede para as telas abertas enviarem a fila
self.addEventListener('sync', function (evento) {
    if (evento.tag === 'fila-offline') {
        evento.waitUntil(self.clients.matchAll().then(function (clientes) {
            clientes.forEach(function (c) { c.postMessage('sincronizar-fila'); });
        }));
    }
});

self.addEventListener('message', function (evento) {
    if (evento.data === 'limpar') {
        evento.waitUntil(caches.keys().then(function (nomes) {
            return Promise.all(nomes.filter(function (n) { return n.indexOf('mapos-tecnico-') === 0; }).map(function (n) { return caches.delete(n); }));
        }));
    }
});
