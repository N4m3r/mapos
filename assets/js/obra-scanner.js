/*
 * Módulo de material da obra: leitura de código de barras (câmera) + tabela de
 * itens + assinatura. Sem dependências externas: usa a API BarcodeDetector
 * quando disponível; caso contrário, o campo de digitação manual continua
 * funcionando (fallback sempre presente).
 *
 * Espera no HTML:
 *   - #cod-barras   : input de texto para digitar/ler o código
 *   - #btn-scan     : botão liga/desliga a câmera
 *   - #scanner-video: <video> onde a câmera aparece
 *   - #scanner-status: span de status
 *   - #itens-tbody  : tbody da tabela de itens
 *   - data-buscar-url: URL do endpoint AJAX de busca por código (no #obra-material)
 *   - data-modo: 'receber' | 'entregar'
 */
(function () {
    var cfg = {}, stream = null, detector = null, loopId = null, ultimoCod = '', ultimoTs = 0;

    function $(id) { return document.getElementById(id); }

    function status(msg) { var s = $('scanner-status'); if (s) s.textContent = msg || ''; }

    function beep() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var o = ctx.createOscillator(); o.frequency.value = 880; o.connect(ctx.destination);
            o.start(); setTimeout(function () { o.stop(); ctx.close(); }, 120);
        } catch (e) {}
    }

    // ---- Tabela de itens -------------------------------------------------
    function novaLinha(dados) {
        dados = dados || {};
        var modo = cfg.modo;
        var tr = document.createElement('tr');
        // Só o recebimento captura valor unitário (custo do material comprado).
        var colQtd = modo === 'receber'
            ? '<td class="col-qtd"><input type="number" step="0.001" name="quantidade_conferida" value="' + (dados.qtd || 1) + '"></td>'
            : '<td class="col-qtd"><input type="number" step="0.001" name="quantidade" value="' + (dados.qtd || 1) + '"></td>';
        var colValor = modo === 'receber'
            ? '<td class="col-valor"><input type="number" step="0.01" name="valor_unitario" value="' + (dados.preco || 0) + '"></td>'
            : '';
        tr.innerHTML =
            '<td><input type="hidden" name="produto_id" value="' + (dados.produto_id || '') + '">' +
            '<input type="text" name="descricao" class="span12" value="' + (dados.descricao || '') + '" placeholder="Descrição do item"></td>' +
            '<td><input type="text" name="cod_barras" value="' + (dados.cod_barras || '') + '" style="width:120px"></td>' +
            '<td><input type="text" name="unidade" value="' + (dados.unidade || '') + '" style="width:60px"></td>' +
            colQtd + colValor +
            '<td><a href="#" class="btn-nwe4" title="Remover"><i class="bx bx-trash-alt"></i></a></td>';
        tr.querySelector('a.btn-nwe4').addEventListener('click', function (e) { e.preventDefault(); tr.remove(); });
        $('itens-tbody').appendChild(tr);
        return tr;
    }

    function existeCod(cod) {
        if (!cod) return null;
        var inputs = $('itens-tbody').querySelectorAll('input[name="cod_barras"]');
        for (var i = 0; i < inputs.length; i++) {
            if (inputs[i].value === cod) return inputs[i].closest('tr');
        }
        return null;
    }

    function incrementarQtd(tr) {
        var q = tr.querySelector('.col-qtd input');
        if (q) q.value = (parseFloat(q.value || 0) + 1);
    }

    // ---- Registrar um código lido/digitado -------------------------------
    function registrarCodigo(cod) {
        cod = (cod || '').trim();
        if (!cod) return;

        var jaTem = existeCod(cod);
        if (jaTem) { incrementarQtd(jaTem); status('Item já na lista: +1'); beep(); return; }

        status('Buscando ' + cod + '...');
        var xhr = new XMLHttpRequest();
        xhr.open('GET', cfg.buscarUrl + '?cod=' + encodeURIComponent(cod), true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            var p = null;
            try { p = JSON.parse(xhr.responseText); } catch (e) {}
            if (p && !p.erro) {
                novaLinha({ produto_id: p.id, descricao: p.descricao, cod_barras: p.cod_barras, unidade: p.unidade || '', preco: p.preco || 0 });
                status('Adicionado: ' + p.descricao);
            } else {
                // item avulso: sem cadastro, só com o código
                novaLinha({ cod_barras: cod, descricao: '' });
                status('Código ' + cod + ' não cadastrado — adicionado como avulso.');
            }
            beep();
        };
        xhr.send();
    }

    // ---- Câmera / detector ----------------------------------------------
    function tick() {
        var video = $('scanner-video');
        if (!detector || !video || video.readyState < 2) { loopId = requestAnimationFrame(tick); return; }
        detector.detect(video).then(function (codes) {
            if (codes && codes.length) {
                var cod = codes[0].rawValue;
                var agora = Date.now();
                if (cod && (cod !== ultimoCod || agora - ultimoTs > 1500)) {
                    ultimoCod = cod; ultimoTs = agora;
                    registrarCodigo(cod);
                }
            }
        }).catch(function () {});
        loopId = requestAnimationFrame(tick);
    }

    function pararScan() {
        if (loopId) cancelAnimationFrame(loopId), loopId = null;
        if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); stream = null; }
        var v = $('scanner-video'); if (v) { v.style.display = 'none'; v.srcObject = null; }
        var b = $('btn-scan'); if (b) b.querySelector('.button__text2') && (b.querySelector('.button__text2').textContent = 'Ler código');
    }

    function iniciarScan() {
        if (!('BarcodeDetector' in window)) {
            status('Este navegador não suporta leitura por câmera — digite o código manualmente.');
            return;
        }
        if (!detector) detector = new window.BarcodeDetector();
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(function (s) {
            stream = s;
            var v = $('scanner-video');
            v.style.display = 'block'; v.srcObject = s; v.play();
            var b = $('btn-scan'); if (b && b.querySelector('.button__text2')) b.querySelector('.button__text2').textContent = 'Parar';
            status('Câmera ativa — aponte para o código.');
            loopId = requestAnimationFrame(tick);
        }).catch(function () { status('Não foi possível acessar a câmera.'); });
    }

    // ---- Assinatura ------------------------------------------------------
    function initAssinatura() {
        var canvas = $('assinatura'); if (!canvas) return;
        var ctx = canvas.getContext('2d'), desenhando = false;
        function pos(e) {
            var r = canvas.getBoundingClientRect();
            var t = e.touches ? e.touches[0] : e;
            return { x: t.clientX - r.left, y: t.clientY - r.top };
        }
        function start(e) { desenhando = true; var p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
        function move(e) { if (!desenhando) return; var p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); }
        function end() { desenhando = false; salvar(); }
        function salvar() { var h = $('assinatura_hidden'); if (h) h.value = canvas.toDataURL('image/png'); }
        ctx.lineWidth = 2; ctx.strokeStyle = '#111';
        ['mousedown', 'touchstart'].forEach(function (ev) { canvas.addEventListener(ev, start); });
        ['mousemove', 'touchmove'].forEach(function (ev) { canvas.addEventListener(ev, move); });
        ['mouseup', 'touchend', 'mouseleave'].forEach(function (ev) { canvas.addEventListener(ev, end); });
        var lim = $('assinatura_limpar');
        if (lim) lim.addEventListener('click', function (e) { e.preventDefault(); ctx.clearRect(0, 0, canvas.width, canvas.height); var h = $('assinatura_hidden'); if (h) h.value = ''; });
    }

    // ---- Fotos -----------------------------------------------------------
    function initFotos() {
        var input = $('fotos-input'); if (!input) return;
        input.addEventListener('change', function () {
            $('fotos-hidden').innerHTML = ''; $('fotos-prev').innerHTML = '';
            Array.prototype.forEach.call(this.files, function (file) {
                var reader = new FileReader();
                reader.onload = function (ev) {
                    var h = document.createElement('input');
                    h.type = 'hidden'; h.name = 'fotos[]'; h.value = ev.target.result;
                    $('fotos-hidden').appendChild(h);
                    var img = document.createElement('img'); img.src = ev.target.result;
                    $('fotos-prev').appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // ---- Bootstrap -------------------------------------------------------
    document.addEventListener('DOMContentLoaded', function () {
        var root = $('obra-material'); if (!root) return;
        cfg.buscarUrl = root.getAttribute('data-buscar-url');
        cfg.modo = root.getAttribute('data-modo');

        var btn = $('btn-scan');
        if (btn) btn.addEventListener('click', function (e) { e.preventDefault(); stream ? pararScan() : iniciarScan(); });

        var cod = $('cod-barras');
        if (cod) cod.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); registrarCodigo(cod.value); cod.value = ''; cod.focus(); }
        });
        var btnAdd = $('btn-add-cod');
        if (btnAdd) btnAdd.addEventListener('click', function (e) { e.preventDefault(); registrarCodigo(cod.value); cod.value = ''; cod.focus(); });

        var btnManual = $('btn-add-manual');
        if (btnManual) btnManual.addEventListener('click', function (e) { e.preventDefault(); novaLinha({}); });

        initAssinatura(); initFotos();

        // garante ao menos uma linha
        if ($('itens-tbody') && !$('itens-tbody').children.length) novaLinha({});

        window.addEventListener('beforeunload', pararScan);
    });
})();
