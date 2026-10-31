/*
 * Leitura da etiqueta da camera NO APARELHO (ADR-019). Sem IA, sem envio de imagem.
 *
 * Parte 1: interpretar(textos) - funcao pura (sem DOM), UMD. Devolve {modelo, serie, mac, origem}.
 * Parte 2: UI - input[type=file][data-etiqueta] (junto com data-foto-compacta): le QR/codigo de
 *   barras da foto (BarcodeDetector; reserva jsQR) e preenche modelo e serie do mesmo form.
 *   Atributos opcionais: data-etiqueta-modelo / data-etiqueta-serie (seletores CSS).
 */
(function (raiz, fabrica) {
    'use strict';
    var api = fabrica();
    if (typeof module === 'object' && module.exports) {
        module.exports = api;
    } else {
        raiz.EtiquetaCamera = api;
        api.iniciarUI(raiz);
    }
}(typeof window !== 'undefined' ? window : this, function () {
    'use strict';

    var MAX_MODELO = 80;
    var MAX_SERIE = 60;

    var RE_MAC_SEP = /(?:^|[^0-9A-Fa-f:\-])([0-9A-Fa-f]{2}(?:([:-])[0-9A-Fa-f]{2}(?:\2[0-9A-Fa-f]{2}){4}))(?![0-9A-Fa-f])/;
    var RE_MAC_PLANO = /MAC(?:\s*ADDR(?:ESS)?)?\s*[:=\-]?\s*([0-9A-Fa-f]{12})(?![0-9A-Fa-f])/i;
    var RE_HIK_COMPLETO = /(?:^|[^A-Za-z0-9])((?:i?DS)-[A-Za-z0-9\-]+?(?:\([^)\n]*\))?)((?:19|20)\d{6})([A-Za-z]{4})([A-Za-z0-9]+)/i;
    var RE_HIK_MODELO = /^(?:i?DS)-[A-Za-z0-9][A-Za-z0-9\-]*(?:\([^)]*\))?$/i;
    var RE_MODELO_OUTRAS = /^(VIP|VHD|MHDX|NVD|iM)[\s-]?\w+/i;
    var RE_SN = /(?:^|[^A-Za-z0-9])(?:S\/N|S\.N\.?|SN|SERIAL(?:\s*(?:NO\.?|NUMBER|N[ºo]))?)\s*[:#=]?\s*([A-Za-z0-9][A-Za-z0-9\-]{3,})/i;
    var RE_SERIE_CURTA = /^[A-Za-z]\d{8}$/;
    var RE_URL = /^(?:[a-z][a-z0-9+.\-]*:\/\/|www\.)/i;

    function normalizarMac(hex) {
        var limpo = String(hex).replace(/[^0-9A-Fa-f]/g, '').toUpperCase();
        return limpo.match(/.{2}/g).join(':');
    }

    function interpretar(textos) {
        var resultado = { modelo: '', serie: '', mac: '', origem: 'nenhuma' };
        var bruto = (textos || []).map(function (t) { return String(t == null ? '' : t); }).join('\n');
        var linhas = bruto.split(/\r\n|\r|\n/).map(function (l) { return l.trim(); }).filter(Boolean);
        var m, modelo = '', serie = '', origem = '';

        // MAC
        m = RE_MAC_SEP.exec(bruto);
        if (m) {
            resultado.mac = normalizarMac(m[1]);
        } else if ((m = RE_MAC_PLANO.exec(bruto))) {
            resultado.mac = normalizarMac(m[1]);
        }

        // Hikvision: numero de serie completo
        m = RE_HIK_COMPLETO.exec(bruto);
        if (m) {
            modelo = m[1].replace(/-+$/, '');
            serie = m[0].replace(/^[^A-Za-z0-9]/, '');
            origem = 'hikvision-completo';
        }

        // Modelo por linha
        if (!modelo) {
            for (var i = 0; i < linhas.length; i++) {
                if (RE_URL.test(linhas[i])) { continue; }
                if (RE_HIK_MODELO.test(linhas[i]) || RE_MODELO_OUTRAS.test(linhas[i])) {
                    modelo = linhas[i];
                    break;
                }
            }
        }

        // Serie rotulada (S/N, SN)
        if (!serie) {
            for (var j = 0; j < linhas.length; j++) {
                if (RE_URL.test(linhas[j])) { continue; }
                m = RE_SN.exec(linhas[j]);
                if (m) { serie = m[1]; origem = 'rotulo'; break; }
            }
        }

        // Serie curta Hikvision (letra + 8 digitos)
        var tokens = [];
        linhas.forEach(function (l) {
            if (RE_URL.test(l)) { return; }
            l.split(/[\s,;|=:]+/).forEach(function (t) {
                if (t && t.indexOf('://') < 0) { tokens.push(t); }
            });
        });
        if (!serie) {
            for (var k = 0; k < tokens.length; k++) {
                if (RE_SERIE_CURTA.test(tokens[k])) { serie = tokens[k]; origem = 'hikvision-curto'; break; }
            }
        }

        // Generico: maior token alfanumerico com 8+ caracteres
        if (!serie) {
            var macPlano = resultado.mac.replace(/:/g, '');
            var melhor = '';
            tokens.forEach(function (t) {
                if (/^[A-Za-z0-9]{8,}$/.test(t) && t.toUpperCase() !== macPlano && t !== modelo && t.length > melhor.length) {
                    melhor = t;
                }
            });
            if (melhor) { serie = melhor; origem = 'generico'; }
        }

        if (!serie && resultado.mac) { origem = 'mac'; }
        if (!origem && modelo) { origem = 'modelo'; }

        // Limites
        resultado.modelo = modelo.slice(0, MAX_MODELO);
        if (serie && resultado.mac) {
            var junto = serie + ' / ' + resultado.mac;
            serie = junto.length <= MAX_SERIE ? junto : serie.slice(0, MAX_SERIE);
        } else if (!serie && resultado.mac) {
            serie = resultado.mac;
        } else {
            serie = serie.slice(0, MAX_SERIE);
        }
        resultado.serie = serie;
        resultado.origem = origem || 'nenhuma';
        return resultado;
    }

    // ------------------------------------------------------------------ UI

    function iniciarUI(win) {
        var doc = win.document;
        if (!doc) { return; }
        var LADO_MAX = 1600;
        var URL_JSQR = null;
        var NONCE = '';
        if (doc.currentScript) {
            NONCE = doc.currentScript.nonce || doc.currentScript.getAttribute('nonce') || '';
            URL_JSQR = (doc.currentScript.src || '').replace(/etiqueta-camera\.js(\?.*)?$/, 'jsQR.js$1');
        }
        var carregandoJsQR = null;

        function carregarJsQR() {
            if (typeof win.jsQR === 'function') { return Promise.resolve(true); }
            if (!URL_JSQR) { return Promise.resolve(false); }
            if (!carregandoJsQR) {
                carregandoJsQR = new Promise(function (ok) {
                    var s = doc.createElement('script');
                    if (NONCE) { s.setAttribute('nonce', NONCE); }
                    s.src = URL_JSQR;
                    s.onload = function () { ok(typeof win.jsQR === 'function'); };
                    s.onerror = function () { ok(false); };
                    doc.head.appendChild(s);
                });
            }
            return carregandoJsQR;
        }

        function desenhar(bitmap, sx, sy, sw, sh, ladoMax) {
            var esc = Math.min(1, ladoMax / Math.max(sw, sh));
            var c = doc.createElement('canvas');
            c.width = Math.max(1, Math.round(sw * esc));
            c.height = Math.max(1, Math.round(sh * esc));
            var ctx = c.getContext('2d', { willReadFrequently: true });
            ctx.drawImage(bitmap, sx, sy, sw, sh, 0, 0, c.width, c.height);
            return { ctx: ctx, w: c.width, h: c.height };
        }

        async function lerComBarcodeDetector(bitmap) {
            if (!('BarcodeDetector' in win)) { return []; }
            var formatos = ['qr_code', 'code_128', 'code_39', 'data_matrix', 'ean_13'];
            try {
                if (win.BarcodeDetector.getSupportedFormats) {
                    var sup = await win.BarcodeDetector.getSupportedFormats();
                    formatos = formatos.filter(function (f) { return sup.indexOf(f) >= 0; });
                }
                if (!formatos.length) { return []; }
                var det = new win.BarcodeDetector({ formats: formatos });
                var achados = await det.detect(bitmap);
                return achados.map(function (a) { return a.rawValue; }).filter(Boolean);
            } catch (e) {
                return [];
            }
        }

        async function lerComJsQR(bitmap) {
            if (!(await carregarJsQR())) { return []; }
            var w = bitmap.width, h = bitmap.height;
            var tentativas = [
                [0, 0, w, h],
                [Math.round(w * 0.2), Math.round(h * 0.2), Math.round(w * 0.6), Math.round(h * 0.6)]
            ];
            var textos = [];
            for (var i = 0; i < tentativas.length; i++) {
                var t = tentativas[i];
                // Imagem inteira reduzida a 1600 px; recorte central ampliado ate 1600 px.
                var d = desenhar(bitmap, t[0], t[1], t[2], t[3], LADO_MAX);
                var img = d.ctx.getImageData(0, 0, d.w, d.h);
                var r = win.jsQR(img.data, d.w, d.h, { inversionAttempts: 'attemptBoth' });
                if (r && r.data) { textos.push(r.data); break; }
            }
            return textos;
        }

        async function lerArquivo(arquivo) {
            var bitmap = await win.createImageBitmap(arquivo);
            try {
                var textos = await lerComBarcodeDetector(bitmap);
                if (!textos.length) { textos = await lerComJsQR(bitmap); }
                return textos;
            } finally {
                if (bitmap.close) { bitmap.close(); }
            }
        }

        function statusDe(input) {
            var form = input.closest('form');
            var el = form && form.querySelector('[data-etiqueta-status]');
            if (!el) {
                el = doc.createElement('div');
                el.setAttribute('data-etiqueta-status', '');
                el.setAttribute('role', 'status');
                el.style.cssText = 'font-size:13px;margin-top:6px;';
                var ancora = input.closest('label') || input;
                ancora.parentNode.insertBefore(el, ancora.nextSibling);
            }
            return el;
        }

        function achar(input, attr, padrao) {
            var form = input.closest('form') || doc;
            return form.querySelector(input.getAttribute(attr) || padrao);
        }

        function preencher(campo, valor) {
            if (!campo || !valor) { return false; }
            if (campo.value && campo.value !== valor) {
                if (!win.confirm('Substituir "' + campo.value + '" por "' + valor + '"?')) { return false; }
            }
            campo.value = valor;
            campo.dispatchEvent(new win.Event('input', { bubbles: true }));
            return true;
        }

        async function aoEscolher(input, arquivos) {
            var status = statusDe(input);
            status.style.color = '';
            status.textContent = 'Lendo a etiqueta...';
            var textos = [];
            try {
                for (var i = 0; i < arquivos.length; i++) {
                    textos = textos.concat(await lerArquivo(arquivos[i]));
                }
            } catch (e) {
                textos = [];
            }
            var r = interpretar(textos);
            if (!textos.length || (!r.modelo && !r.serie)) {
                status.style.color = '#b9770e';
                status.textContent = 'Não achei QR ou código de barras na foto — digite os dados.';
                return;
            }
            var okM = preencher(achar(input, 'data-etiqueta-modelo', '[name=modelo]'), r.modelo);
            var okS = preencher(achar(input, 'data-etiqueta-serie', '[name=serie]'), r.serie);
            var partes = [];
            if (r.modelo) { partes.push('modelo ' + r.modelo + (okM ? '' : ' (não alterado)')); }
            if (r.serie) { partes.push('série ' + r.serie + (okS ? '' : ' (não alterado)')); }
            status.style.color = '#12a454';
            status.textContent = 'Lido: ' + partes.join(', ');
        }

        function ligar() {
            Array.prototype.forEach.call(doc.querySelectorAll('input[type=file][data-etiqueta]'), function (input) {
                input.addEventListener('change', function () {
                    // Copia agora: foto-compacta.js limpa o input ao terminar.
                    var arquivos = Array.prototype.slice.call(input.files || []);
                    if (arquivos.length && win.createImageBitmap) { aoEscolher(input, arquivos); }
                });
            });
        }

        if (doc.readyState === 'loading') {
            doc.addEventListener('DOMContentLoaded', ligar);
        } else {
            ligar();
        }
    }

    return { interpretar: interpretar, iniciarUI: iniciarUI };
}));
