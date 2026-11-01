/**
 * Canvas de Assinatura Digital
 * MAPOS OS - Sistema de Check-in
 *
 * Uso:
 * var assinatura = new AssinaturaCanvas('id-do-canvas', opcoes);
 * assinatura.limpar();
 * assinatura.desfazer();            // remove o ultimo traco
 * assinatura.obterImagem();         // base64 (PNG recortado, fundo branco)
 * assinatura.estaVazio();           // true se vazio OU assinatura minima demais
 * assinatura.validar();             // {ok, motivo}
 *
 * Os tracos sao guardados como vetores (coordenadas logicas), entao o desenho
 * sobrevive a rotacao de tela/redimensionamento e e redesenhado nitido em
 * telas com devicePixelRatio > 1.
 *
 * Opcoes: cor, espessura, largura, altura, backgroundColor, minPontos,
 *   minComprimento, preencherContainer, onBegin, onEnd, onChange.
 */

(function (global) {
    'use strict';

    function extend(base, extra) {
        var out = {}, k;
        for (k in base) { if (Object.prototype.hasOwnProperty.call(base, k)) { out[k] = base[k]; } }
        if (extra) {
            for (k in extra) { if (Object.prototype.hasOwnProperty.call(extra, k) && extra[k] !== undefined) { out[k] = extra[k]; } }
        }
        return out;
    }

    function dataUrlParaBlob(dataUrl) {
        var partes = dataUrl.split(',');
        var mime = (partes[0].match(/:(.*?);/) || [])[1] || 'image/png';
        var bin = atob(partes[1]);
        var arr = new Uint8Array(bin.length);
        for (var i = 0; i < bin.length; i++) { arr[i] = bin.charCodeAt(i); }
        return new Blob([arr], { type: mime });
    }

    function AssinaturaCanvas(canvasId, opcoes) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) {
            console.error('Canvas não encontrado: ' + canvasId);
            return;
        }

        this.desenhando = false;
        this.tracos = [];      // [{cor, p:[{x,y,w,t}]}] em coordenadas logicas
        this._atual = null;
        this._base = null;     // imagem importada (carregarImagem)
        this._pointerId = null;
        this._logW = 0;
        this._logH = 0;
        this._dpr = 1;

        this.isMobile = window.innerWidth <= 768 || 'ontouchstart' in window;

        this.config = extend({
            cor: '#000000',
            espessura: this.isMobile ? 3 : 2,
            largura: this.isMobile ? Math.max(window.innerWidth - 40, 280) : 400,
            altura: this.isMobile ? 250 : 150,
            backgroundColor: '#ffffff',
            minPontos: 6,           // pontos minimos para considerar assinatura
            minComprimento: 30,     // comprimento minimo do traco (px logicos)
            preencherContainer: false,
            onBegin: null,
            onEnd: null,
            onChange: null
        }, opcoes);

        this.ctx = this.canvas.getContext('2d');
        this._ajustarTamanhoCanvas();
        this._adicionarEventos();

        var self = this;
        this._onResize = function () { self._handleResize(); };
        window.addEventListener('resize', this._onResize);
        window.addEventListener('orientationchange', this._onResize);

        // Reajusta quando o container passa a ter tamanho (modal abrindo, etc.)
        if (window.ResizeObserver && this.canvas.parentElement) {
            this._ro = new ResizeObserver(function () { self._handleResize(); });
            this._ro.observe(this.canvas.parentElement);
        }
        return this;
    }

    AssinaturaCanvas.prototype = {
        // Compatibilidade: codigo antigo le .vazio
        get vazio() { return this.estaVazio(); },

        /** Calcula tamanho logico (CSS px) desejado para o canvas. */
        _medirLogico: function () {
            var container = this.canvas.parentElement;
            var cw = container ? container.clientWidth : 0;
            var ch = container ? container.clientHeight : 0;

            if (this.config.preencherContainer && cw > 0 && ch > 0) {
                return { w: cw, h: ch };
            }
            if (window.innerWidth <= 768) {
                var w = cw > 0 ? cw - 20 : (this._logW || this.config.largura);
                if (w < 280) { w = 280; }
                if (w > window.innerWidth - 20) { w = window.innerWidth - 20; }
                return { w: Math.round(w), h: 250 };
            }
            return { w: this.config.largura, h: this.config.altura };
        },

        /** (Re)define tamanho do bitmap, preserva os tracos e redesenha. */
        _ajustarTamanhoCanvas: function () {
            if (this.desenhando) { return; }
            var tam = this._medirLogico();
            var dpr = Math.min(window.devicePixelRatio || 1, 3);

            if (this._logW && tam.w === this._logW && tam.h === this._logH && dpr === this._dpr) {
                this._redesenhar();
                return;
            }

            // Reescala os tracos (escala uniforme: sem distorcer a assinatura)
            if (this._logW && this.tracos.length) {
                var s = Math.min(tam.w / this._logW, tam.h / this._logH);
                if (isFinite(s) && s > 0 && s !== 1) {
                    for (var i = 0; i < this.tracos.length; i++) {
                        var pts = this.tracos[i].p;
                        for (var j = 0; j < pts.length; j++) { pts[j].x *= s; pts[j].y *= s; }
                    }
                }
            }

            this._logW = tam.w;
            this._logH = tam.h;
            this._dpr = dpr;
            this.canvas.width = Math.round(tam.w * dpr);
            this.canvas.height = Math.round(tam.h * dpr);
            if (this.config.preencherContainer) {
                this.canvas.style.width = '100%';
                this.canvas.style.height = '100%';
            } else {
                this.canvas.style.width = '100%';
                this.canvas.style.height = 'auto';
            }
            this.ctx = this.canvas.getContext('2d');
            this._redesenhar();
        },

        _handleResize: function () {
            var self = this;
            clearTimeout(this.resizeTimeout);
            this.resizeTimeout = setTimeout(function () {
                if (!self.canvas || self.desenhando) { return; }
                var tam = self._medirLogico();
                // Barra de endereco do celular dispara resize so de altura: ignora
                if (self._logW && Math.abs(tam.w - self._logW) < 2 && Math.abs(tam.h - self._logH) < 2
                    && (window.devicePixelRatio || 1) === self._dpr) { return; }
                self._ajustarTamanhoCanvas();
            }, 150);
        },

        _preparar: function (ctx) {
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
        },

        /** Limpa e redesenha fundo, imagem base e todos os tracos. */
        _redesenhar: function () {
            var ctx = this.ctx;
            ctx.setTransform(this._dpr, 0, 0, this._dpr, 0, 0);
            ctx.fillStyle = this.config.backgroundColor;
            ctx.fillRect(0, 0, this._logW, this._logH);
            if (this._base) {
                ctx.drawImage(this._base, 0, 0, this._logW, this._logH);
            }
            this._preparar(ctx);
            for (var i = 0; i < this.tracos.length; i++) {
                this._desenharTraco(ctx, this.tracos[i]);
            }
        },

        _mid: function (a, b) { return { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 }; },

        /** Segmento i (>=1) do traco: curva quadratica entre pontos medios. */
        _segmento: function (ctx, traco, i) {
            var p = traco.p, n = p.length;
            var a = i === 1 ? p[0] : this._mid(p[i - 1], p[i]);
            var b = (i + 1 < n) ? this._mid(p[i], p[i + 1]) : p[i];
            ctx.strokeStyle = traco.cor;
            ctx.lineWidth = p[i].w;
            ctx.beginPath();
            ctx.moveTo(a.x, a.y);
            if (i + 1 < n) { ctx.quadraticCurveTo(p[i].x, p[i].y, b.x, b.y); }
            else { ctx.lineTo(b.x, b.y); }
            ctx.stroke();
        },

        _ponto: function (ctx, traco) {
            var q = traco.p[0];
            ctx.fillStyle = traco.cor;
            ctx.beginPath();
            ctx.arc(q.x, q.y, Math.max(q.w / 2, 0.8), 0, Math.PI * 2);
            ctx.fill();
        },

        _desenharTraco: function (ctx, traco) {
            var n = traco.p.length;
            if (n === 0) { return; }
            if (n === 1) { this._ponto(ctx, traco); return; }
            for (var i = 1; i < n; i++) { this._segmento(ctx, traco, i); }
        },

        /** Eventos: Pointer Events (mouse, toque e caneta) com captura. */
        _adicionarEventos: function () {
            var self = this, c = this.canvas;

            c.style.touchAction = 'none';
            c.style.webkitTouchCallout = 'none';
            c.style.userSelect = 'none';
            c.style.webkitUserSelect = 'none';
            c.style.cursor = 'crosshair';

            var h = this._h = {
                down: function (e) {
                    if (e.pointerType === 'mouse' && e.button !== 0) { return; }
                    if (self.desenhando) { return; } // ignora segundo dedo/palma
                    e.preventDefault();
                    try { c.setPointerCapture(e.pointerId); } catch (_) {}
                    self._pointerId = e.pointerId;
                    self._iniciarDesenho(e);
                },
                move: function (e) {
                    if (!self.desenhando || e.pointerId !== self._pointerId) { return; }
                    e.preventDefault();
                    var evs = (e.getCoalescedEvents && e.getCoalescedEvents()) || [];
                    if (!evs.length) { evs = [e]; }
                    for (var i = 0; i < evs.length; i++) { self._desenhar(evs[i]); }
                },
                up: function (e) {
                    if (e.pointerId !== self._pointerId) { return; }
                    e.preventDefault();
                    self._desenhar(e);
                    self._terminarDesenho(e);
                },
                cancel: function (e) {
                    if (e.pointerId !== self._pointerId) { return; }
                    self._terminarDesenho(e);
                },
                // Garantia extra contra scroll/zoom da pagina durante o traco (iOS antigo)
                touchmove: function (e) { if (self.desenhando) { e.preventDefault(); } },
                menu: function (e) { e.preventDefault(); }
            };

            c.addEventListener('pointerdown', h.down, { passive: false });
            c.addEventListener('pointermove', h.move, { passive: false });
            c.addEventListener('pointerup', h.up, { passive: false });
            c.addEventListener('pointercancel', h.cancel);
            c.addEventListener('lostpointercapture', h.cancel);
            c.addEventListener('touchmove', h.touchmove, { passive: false });
            c.addEventListener('contextmenu', h.menu);
        },

        _travarScroll: function (travar) {
            var de = document.documentElement;
            if (travar) {
                this._overscrollAnt = de.style.overscrollBehavior;
                de.style.overscrollBehavior = 'none';
            } else if (this._overscrollAnt !== undefined) {
                de.style.overscrollBehavior = this._overscrollAnt;
                this._overscrollAnt = undefined;
            }
        },

        _novoPonto: function (e, ant) {
            var rect = this.canvas.getBoundingClientRect();
            var x = (e.clientX - rect.left) * (this._logW / (rect.width || 1));
            var y = (e.clientY - rect.top) * (this._logH / (rect.height || 1));
            var t = e.timeStamp || Date.now();
            var esp = this.config.espessura;
            var w = esp;

            if (ant) {
                var dist = Math.sqrt(Math.pow(x - ant.x, 2) + Math.pow(y - ant.y, 2));
                var v = dist / Math.max(1, t - ant.t);          // px/ms
                var alvo = esp * 1.35 - (esp * 0.85) * Math.min(v / 2.2, 1); // rapido = fino
                w = ant.w * 0.6 + alvo * 0.4;                   // suaviza variacao
            }
            if (e.pointerType === 'pen' && e.pressure > 0) { w *= (0.55 + e.pressure * 0.9); }
            return { x: x, y: y, w: Math.max(w, 0.6), t: t };
        },

        _iniciarDesenho: function (e) {
            this.desenhando = true;
            this._travarScroll(true);
            var p = this._novoPonto(e, null);
            this._atual = { cor: this.config.cor, p: [p] };
            this.tracos.push(this._atual);
            this._preparar(this.ctx);
            this._ponto(this.ctx, this._atual);
            if (this.config.onBegin) { this.config.onBegin(); }
        },

        _desenhar: function (e) {
            if (!this.desenhando || !this._atual) { return; }
            var pts = this._atual.p;
            var ant = pts[pts.length - 1];
            var p = this._novoPonto(e, ant);
            if (Math.abs(p.x - ant.x) + Math.abs(p.y - ant.y) < 1) { return; } // ignora tremor
            pts.push(p);
            // Desenha o segmento anterior (precisa do ponto novo para a curva)
            if (pts.length >= 3) { this._segmento(this.ctx, this._atual, pts.length - 2); }
        },

        _terminarDesenho: function () {
            if (!this.desenhando) { return; }
            this.desenhando = false;
            this._travarScroll(false);
            var traco = this._atual;
            this._atual = null;
            try { if (this._pointerId !== null) { this.canvas.releasePointerCapture(this._pointerId); } } catch (_) {}
            this._pointerId = null;
            if (traco && traco.p.length >= 2) {
                this._segmento(this.ctx, traco, traco.p.length - 1); // cauda ate o ultimo ponto
            }
            if (this.config.onEnd) { this.config.onEnd(); }
            if (this.config.onChange) { this.config.onChange(); }
        },

        /** Limpa o canvas */
        limpar: function () {
            this.tracos = [];
            this._atual = null;
            this._base = null;
            this.desenhando = false;
            this._travarScroll(false);
            this._redesenhar();
            if (this.config.onChange) { this.config.onChange(); }
        },

        /** Remove o ultimo traco */
        desfazer: function () {
            if (!this.tracos.length) { return false; }
            this.tracos.pop();
            this._redesenhar();
            if (this.config.onChange) { this.config.onChange(); }
            return true;
        },

        _metricas: function () {
            var pontos = 0, comp = 0, minx = Infinity, miny = Infinity, maxx = -Infinity, maxy = -Infinity;
            for (var i = 0; i < this.tracos.length; i++) {
                var p = this.tracos[i].p;
                for (var j = 0; j < p.length; j++) {
                    pontos++;
                    if (p[j].x < minx) { minx = p[j].x; }
                    if (p[j].y < miny) { miny = p[j].y; }
                    if (p[j].x > maxx) { maxx = p[j].x; }
                    if (p[j].y > maxy) { maxy = p[j].y; }
                    if (j > 0) { comp += Math.sqrt(Math.pow(p[j].x - p[j - 1].x, 2) + Math.pow(p[j].y - p[j - 1].y, 2)); }
                }
            }
            return { pontos: pontos, comprimento: comp, minx: minx, miny: miny, maxx: maxx, maxy: maxy };
        },

        /** true se ha algum risco (mesmo que minimo) ou imagem importada */
        temTracos: function () {
            return !!this._base || this.tracos.length > 0;
        },

        /** {ok, motivo}: assinatura vazia ou pequena demais nao e valida */
        validar: function () {
            if (this._base) { return { ok: true, motivo: '' }; }
            if (!this.tracos.length) { return { ok: false, motivo: 'Faça a assinatura antes de continuar.' }; }
            var m = this._metricas();
            var extensao = Math.max(m.maxx - m.minx, m.maxy - m.miny);
            if (m.pontos < this.config.minPontos || m.comprimento < this.config.minComprimento || extensao < 15) {
                return { ok: false, motivo: 'A assinatura está muito curta. Toque em Limpar e assine novamente.' };
            }
            return { ok: true, motivo: '' };
        },

        /** Verifica se esta vazio (inclui assinatura minima/rabisco) */
        estaVazio: function () {
            return !this.validar().ok;
        },

        /**
         * Exporta a assinatura (fundo branco, recortada nas bordas do desenho).
         * formato: 'image/png' (padrao) ou 'image/jpeg'
         * opts: {recortar:true, margem:12, larguraMax:800, qualidade:0.85}
         */
        obterImagem: function (formato, opts) {
            opts = extend({ recortar: true, margem: 12, larguraMax: 800, qualidade: 0.85 }, opts);
            formato = formato === 'image/jpeg' ? 'image/jpeg' : 'image/png';

            var tmp = document.createElement('canvas');
            var tctx = tmp.getContext('2d');
            var recortavel = opts.recortar && !this._base && this.tracos.length > 0;

            if (recortavel) {
                var m = this._metricas();
                var maxW = 0, i, j;
                for (i = 0; i < this.tracos.length; i++) {
                    for (j = 0; j < this.tracos[i].p.length; j++) { maxW = Math.max(maxW, this.tracos[i].p[j].w); }
                }
                var pad = opts.margem + maxW / 2;
                var x0 = m.minx - pad, y0 = m.miny - pad;
                var w = Math.max(m.maxx - m.minx + pad * 2, 40);
                var h = Math.max(m.maxy - m.miny + pad * 2, 40);
                var esc = Math.min(2, opts.larguraMax / w);
                tmp.width = Math.max(1, Math.round(w * esc));
                tmp.height = Math.max(1, Math.round(h * esc));
                tctx.fillStyle = '#FFFFFF';
                tctx.fillRect(0, 0, tmp.width, tmp.height);
                tctx.setTransform(esc, 0, 0, esc, -x0 * esc, -y0 * esc);
                this._preparar(tctx);
                for (i = 0; i < this.tracos.length; i++) { this._desenharTraco(tctx, this.tracos[i]); }
            } else {
                var ew = Math.min(this.canvas.width, opts.larguraMax);
                var er = ew / this.canvas.width;
                tmp.width = Math.round(this.canvas.width * er);
                tmp.height = Math.round(this.canvas.height * er);
                tctx.fillStyle = '#FFFFFF';
                tctx.fillRect(0, 0, tmp.width, tmp.height);
                tctx.drawImage(this.canvas, 0, 0, tmp.width, tmp.height);
            }
            return tmp.toDataURL(formato, opts.qualidade);
        },

        /** Obtém imagem em Blob (callback assincrono) */
        obterBlob: function (callback, formato, opts) {
            var blob = dataUrlParaBlob(this.obterImagem(formato, opts));
            if (callback) { callback(blob); }
        },

        /** Copia dos tracos (para transferir entre canvases) */
        obterTracos: function () {
            return { w: this._logW, h: this._logH, tracos: JSON.parse(JSON.stringify(this.tracos)) };
        },

        /** Substitui os tracos (escala uniforme para o tamanho atual) */
        definirTracos: function (dados) {
            this._base = null;
            this.tracos = [];
            if (dados && dados.tracos && dados.w && dados.h) {
                var s = Math.min(this._logW / dados.w, this._logH / dados.h);
                var offx = (this._logW - dados.w * s) / 2, offy = (this._logH - dados.h * s) / 2;
                var copia = JSON.parse(JSON.stringify(dados.tracos));
                for (var i = 0; i < copia.length; i++) {
                    for (var j = 0; j < copia[i].p.length; j++) {
                        var q = copia[i].p[j];
                        q.x = q.x * s + offx; q.y = q.y * s + offy; q.w = q.w * Math.max(s, 0.5);
                    }
                }
                this.tracos = copia;
            }
            this._redesenhar();
            if (this.config.onChange) { this.config.onChange(); }
        },

        definirCor: function (cor) { this.config.cor = cor; },

        definirEspessura: function (espessura) { this.config.espessura = espessura; },

        /** Desenha imagem no canvas (vira "base"; tracos novos vao por cima) */
        carregarImagem: function (urlImagem, callback) {
            var self = this;
            var img = new Image();
            img.onload = function () {
                self.tracos = [];
                self._base = img;
                self._redesenhar();
                if (self.config.onChange) { self.config.onChange(); }
                if (callback) { callback(true); }
            };
            img.onerror = function () { if (callback) { callback(false); } };
            img.src = urlImagem;
        },

        /** Redimensiona (tamanho logico); preserva os tracos */
        redimensionar: function (largura, altura) {
            this.config.largura = largura;
            this.config.altura = altura;
            this._logW = 0; // forca reajuste
            this._ajustarTamanhoCanvas();
        },

        /** Remove listeners (usado ao recriar o canvas) */
        destruir: function () {
            var c = this.canvas, h = this._h;
            if (!c) { return; }
            if (h) {
                c.removeEventListener('pointerdown', h.down);
                c.removeEventListener('pointermove', h.move);
                c.removeEventListener('pointerup', h.up);
                c.removeEventListener('pointercancel', h.cancel);
                c.removeEventListener('lostpointercapture', h.cancel);
                c.removeEventListener('touchmove', h.touchmove);
                c.removeEventListener('contextmenu', h.menu);
            }
            window.removeEventListener('resize', this._onResize);
            window.removeEventListener('orientationchange', this._onResize);
            if (this._ro) { this._ro.disconnect(); }
            clearTimeout(this.resizeTimeout);
            this._travarScroll(false);
        }
    };

    /**
     * Gerenciador de múltiplos canvases de assinatura
     * Útil quando há assinatura do técnico e do cliente
     */
    var AssinaturaManager = {
        assinaturas: {},

        criar: function (id, canvasId, opcoes) {
            if (this.assinaturas[id] && this.assinaturas[id].destruir) {
                this.assinaturas[id].destruir();
            }
            this.assinaturas[id] = new AssinaturaCanvas(canvasId, opcoes);
            return this.assinaturas[id];
        },

        obter: function (id) {
            return this.assinaturas[id];
        },

        remover: function (id) {
            if (this.assinaturas[id]) {
                if (this.assinaturas[id].destruir) { this.assinaturas[id].destruir(); }
                delete this.assinaturas[id];
            }
        },

        limparTodas: function () {
            for (var id in this.assinaturas) {
                if (this.assinaturas.hasOwnProperty(id)) { this.assinaturas[id].limpar(); }
            }
        },

        obterDados: function () {
            var dados = {};
            for (var id in this.assinaturas) {
                if (this.assinaturas.hasOwnProperty(id)) {
                    var vazio = this.assinaturas[id].estaVazio();
                    dados[id] = {
                        vazio: vazio,
                        imagem: this.assinaturas[id].obterImagem()
                    };
                }
            }
            return dados;
        },

        validarTodasPreenchidas: function () {
            for (var id in this.assinaturas) {
                if (this.assinaturas.hasOwnProperty(id)) {
                    if (this.assinaturas[id].estaVazio()) { return false; }
                }
            }
            return true;
        }
    };

    global.AssinaturaCanvas = AssinaturaCanvas;
    global.AssinaturaManager = AssinaturaManager;
})(window);
