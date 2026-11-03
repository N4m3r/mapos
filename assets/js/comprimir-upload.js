/* Reduz fotos JPEG/WebP antes de qualquer upload (lado maior 1600 px, JPEG 0,8).
 * Foto de celular tem 4-12 MB; varias juntas passam do post_max_size do PHP, que
 * descarta o POST inteiro (inclusive o token CSRF) e o sistema mostra
 * "The action you have requested is not allowed".
 *
 * Age em todo input[type=file] da pagina: intercepta o "change" na captura,
 * troca os arquivos pelos reduzidos e dispara o "change" de novo para os
 * scripts da tela. Ignora PNG/GIF/PDF, arquivos pequenos e inputs que ja
 * comprimem por conta propria (data-foto-compacta, .checkin-foto-input, #fs-input)
 * ou marcados com data-sem-compressao. Sem suporte (DataTransfer), nao faz nada. */
(function () {
    'use strict';
    var LADO_MAX = 1600, QUALIDADE = 0.8, MINIMO = 700 * 1024;

    function suportado() {
        try { return typeof DataTransfer !== 'undefined' && !!new DataTransfer().items && !!document.createElement('canvas').toBlob; } catch (e) { return false; }
    }
    if (!suportado()) { return; }

    function ignorar(input) {
        return input.hasAttribute('data-foto-compacta') || input.hasAttribute('data-sem-compressao')
            || input.classList.contains('checkin-foto-input') || input.id === 'fs-input';
    }

    function precisa(f) {
        return /^image\/(jpeg|jpg|pjpeg|webp)$/i.test(f.type) && f.size > MINIMO;
    }

    function carregar(f) {
        if (window.createImageBitmap) {
            return createImageBitmap(f, { imageOrientation: 'from-image' }).catch(function () { return createImageBitmap(f); }).catch(function () { return viaImg(f); });
        }
        return viaImg(f);
    }

    function viaImg(f) {
        return new Promise(function (ok, erro) {
            var url = URL.createObjectURL(f), img = new Image();
            img.onload = function () { URL.revokeObjectURL(url); ok(img); };
            img.onerror = function () { URL.revokeObjectURL(url); erro(new Error('imagem')); };
            img.src = url;
        });
    }

    function reduzir(f) {
        if (!precisa(f)) { return Promise.resolve(f); }
        return carregar(f).then(function (img) {
            var w = img.width, h = img.height, k = Math.min(1, LADO_MAX / Math.max(w, h));
            var c = document.createElement('canvas');
            c.width = Math.round(w * k); c.height = Math.round(h * k);
            c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
            if (img.close) { img.close(); }
            return new Promise(function (ok) {
                c.toBlob(function (blob) {
                    if (!blob || blob.size >= f.size) { ok(f); return; }
                    var nome = f.name.replace(/\.(jpe?g|webp)$/i, '') + '.jpg';
                    ok(new File([blob], nome, { type: 'image/jpeg', lastModified: f.lastModified }));
                }, 'image/jpeg', QUALIDADE);
            });
        }).catch(function () { return f; });
    }

    window.addEventListener('change', function (e) {
        var input = e.target;
        if (!input || input.tagName !== 'INPUT' || input.type !== 'file' || input._comprimido || ignorar(input)) { return; }
        var arquivos = Array.prototype.slice.call(input.files || []);
        if (!arquivos.some(precisa)) { return; }

        // Segura o evento ate terminar de reduzir; depois dispara de novo
        e.stopImmediatePropagation();
        var form = input.form, botoes = form ? form.querySelectorAll('button[type=submit], button:not([type]), input[type=submit]') : [];
        Array.prototype.forEach.call(botoes, function (b) { b.disabled = true; });
        input.setAttribute('aria-busy', 'true');

        // Um de cada vez para nao estourar a memoria do celular
        var saida = [];
        arquivos.reduce(function (cadeia, f) {
            return cadeia.then(function () { return reduzir(f).then(function (r) { saida.push(r); }); });
        }, Promise.resolve()).then(function () {
            try {
                var dt = new DataTransfer();
                saida.forEach(function (f) { dt.items.add(f); });
                input.files = dt.files;
            } catch (err) { /* mantem os originais */ }
        }).then(function () {
            Array.prototype.forEach.call(botoes, function (b) { b.disabled = false; });
            input.removeAttribute('aria-busy');
            input._comprimido = true;
            input.dispatchEvent(new Event('change', { bubbles: true }));
            input._comprimido = false;
        });
    }, true);
})();
