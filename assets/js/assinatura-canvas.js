/**
 * Canvas de Assinatura Digital
 * MAPOS OS - Sistema de Check-in
 *
 * Uso:
 * var assinatura = new AssinaturaCanvas('id-do-canvas', opcoes);
 * assinatura.limpar();
 * assinatura.obterImagem(); // retorna base64
 * assinatura.estaVazio(); // boolean
 */

function AssinaturaCanvas(canvasId, opcoes) {
    this.canvas = document.getElementById(canvasId);
    if (!this.canvas) {
        console.error('Canvas não encontrado: ' + canvasId);
        return;
    }

    this.ctx = this.canvas.getContext('2d');
    this.desenhando = false;
    this.vazio = true;

    // Detecta se é mobile
    this.isMobile = window.innerWidth <= 768 || 'ontouchstart' in window;

    // Configurações padrão (ajustadas para mobile)
    var larguraPadrao = this.isMobile ? window.innerWidth - 40 : 400;
    var alturaPadrao = this.isMobile ? 250 : 150;

    this.config = $.extend({
        cor: '#000000',
        espessura: this.isMobile ? 3 : 2, // Traço mais grosso no mobile
        largura: larguraPadrao,
        altura: alturaPadrao,
        backgroundColor: '#ffffff',
        onBegin: null,
        onEnd: null
    }, opcoes || {});

    // Define tamanho do canvas (atributos internos)
    // Usamos uma função interna em vez de método do prototype para garantir disponibilidade
    this._ajustarTamanhoCanvas = function() {
        var container = this.canvas.parentElement;
        var isMobile = window.innerWidth <= 768;

        // Guarda imagem atual se houver
        var imagemAtual = null;
        if (!this.vazio) {
            imagemAtual = this.canvas.toDataURL();
        }

        if (isMobile && container) {
            // Em mobile, usa a largura do container menos padding
            var novaLargura = container.clientWidth - 20;
            if (novaLargura < 280) novaLargura = 280; // Mínimo
            if (novaLargura > window.innerWidth - 20) novaLargura = window.innerWidth - 20;

            this.canvas.width = novaLargura;
            this.canvas.height = 250; // Altura maior no mobile
            this.canvas.style.width = '100%';
            this.canvas.style.height = 'auto';
        } else {
            // Desktop - tamanho padrão ou configurado
            this.canvas.width = this.config.largura;
            this.canvas.height = this.config.altura;
            this.canvas.style.width = '100%';
            this.canvas.style.height = 'auto';
        }

        // Reconfigura contexto após resize
        this.ctx = this.canvas.getContext('2d');
        this.ctx.strokeStyle = this.config.cor;
        this.ctx.lineWidth = this.config.espessura;
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';

        // Restaura imagem se existia
        if (imagemAtual) {
            var self = this;
            var img = new Image();
            img.onload = function() {
                self.ctx.drawImage(img, 0, 0, self.canvas.width, self.canvas.height);
            };
            img.src = imagemAtual;
        } else {
            // Preenche background
            this.ctx.fillStyle = this.config.backgroundColor;
            this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
        }
    };
    this._ajustarTamanhoCanvas();

    // Configura estilo
    this.ctx.strokeStyle = this.config.cor;
    this.ctx.lineWidth = this.config.espessura;
    this.ctx.lineCap = 'round';
    this.ctx.lineJoin = 'round';

    // Preenche background branco
    this.ctx.fillStyle = this.config.backgroundColor;
    this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);

    // Eventos de mouse e touch
    this._adicionarEventos();

    // Listener para redimensionamento ( importante para mobile que muda orientação)
    var self = this;
    window.addEventListener('resize', function() {
        self._handleResize();
    });

    return this;
}

AssinaturaCanvas.prototype = {
    /**
     * Handle resize com debounce
     */
    _handleResize: function() {
        var self = this;
        clearTimeout(this.resizeTimeout);
        this.resizeTimeout = setTimeout(function() {
            self._ajustarTamanhoCanvas();
        }, 250);
    },

    /**
     * Adiciona eventos de mouse e touch
     */
    _adicionarEventos: function() {
        var self = this;

        // Previne comportamento padrão de touch (scroll, zoom)
        this.canvas.style.touchAction = 'none';
        this.canvas.style.webkitTouchCallout = 'none';
        this.canvas.style.userSelect = 'none';
        this.canvas.style.webkitUserSelect = 'none';
        this.canvas.style.MozUserSelect = 'none';
        this.canvas.style.msUserSelect = 'none';

        // Flag para evitar conflitos mouse/touch
        this.isTouch = false;

        // Mouse events - apenas se não for touch device
        if (!('ontouchstart' in window)) {
            this.canvas.addEventListener('mousedown', function(e) {
                e.preventDefault();
                self._iniciarDesenho(e);
            }, { passive: false });

            this.canvas.addEventListener('mousemove', function(e) {
                e.preventDefault();
                self._desenhar(e);
            }, { passive: false });

            this.canvas.addEventListener('mouseup', function(e) {
                e.preventDefault();
                self._terminarDesenho(e);
            }, { passive: false });

            this.canvas.addEventListener('mouseleave', function(e) {
                self._terminarDesenho(e);
            }, { passive: false });
        }

        // Touch events - com preventDefault para evitar scroll
        this.canvas.addEventListener('touchstart', function(e) {
            self.isTouch = true;
            e.preventDefault();
            e.stopPropagation();
            if (e.touches.length === 1) {
                self._iniciarDesenho(e);
            }
        }, { passive: false, capture: true });

        this.canvas.addEventListener('touchmove', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (e.touches.length === 1) {
                self._desenhar(e);
            }
        }, { passive: false, capture: true });

        this.canvas.addEventListener('touchend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            self._terminarDesenho(e);
        }, { passive: false, capture: true });

        this.canvas.addEventListener('touchcancel', function(e) {
            e.preventDefault();
            e.stopPropagation();
            self._terminarDesenho(e);
        }, { passive: false, capture: true });

        // Previne scroll da página quando tocar no canvas (apenas no canvas)
        this.canvas.addEventListener('touchstart', function(e) {
            if (e.target === self.canvas) {
                e.preventDefault();
            }
        }, { passive: false });
    },

    /**
     * Inicia desenho
     */
    _iniciarDesenho: function(e) {
        this.desenhando = true;
        this.vazio = false;

        var pos = this._obterPosicao(e);
        this.ctx.beginPath();
        this.ctx.moveTo(pos.x, pos.y);

        if (this.config.onBegin) {
            this.config.onBegin();
        }
    },

    /**
     * Desenha linha
     */
    _desenhar: function(e) {
        if (!this.desenhando) return;

        var pos = this._obterPosicao(e);
        this.ctx.lineTo(pos.x, pos.y);
        this.ctx.stroke();
    },

    /**
     * Termina desenho
     */
    _terminarDesenho: function(e) {
        if (!this.desenhando) return;
        this.desenhando = false;
        this.ctx.closePath();

        if (this.config.onEnd) {
            this.config.onEnd();
        }
    },

    /**
     * Obtém posição do mouse/touch relativa ao canvas
     * Corrige a escala quando o canvas é redimensionado via CSS
     * Otimizado para mobile com touch
     */
    _obterPosicao: function(e) {
        var rect = this.canvas.getBoundingClientRect();
        var scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        // Calcula a escala entre o tamanho interno do canvas e o tamanho de exibição
        var scaleX = this.canvas.width / rect.width;
        var scaleY = this.canvas.height / rect.height;

        var clientX, clientY;

        // Se for evento touch, usa as coordenadas do touch
        if (e.touches && e.touches.length > 0) {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        } else if (e.changedTouches && e.changedTouches.length > 0) {
            // Para touchend
            clientX = e.changedTouches[0].clientX;
            clientY = e.changedTouches[0].clientY;
        } else {
            // Mouse
            clientX = e.clientX;
            clientY = e.clientY;
        }

        return {
            x: (clientX - rect.left) * scaleX,
            y: (clientY - rect.top) * scaleY
        };
    },

    /**
     * Limpa o canvas
     */
    limpar: function() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.ctx.fillStyle = this.config.backgroundColor;
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
        this.vazio = true;
    },

    /**
     * Verifica se canvas está vazio
     */
    estaVazio: function() {
        return this.vazio;
    },

    /**
     * Obtém imagem em base64 (formato PNG)
     */
    obterImagem: function(formato) {
        formato = formato || 'image/png';

        // Criar um canvas temporário para adicionar fundo branco
        var tempCanvas = document.createElement('canvas');
        tempCanvas.width = this.canvas.width;
        tempCanvas.height = this.canvas.height;

        var tempCtx = tempCanvas.getContext('2d');

        // Preencher com fundo branco
        tempCtx.fillStyle = '#FFFFFF';
        tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);

        // Desenhar a assinatura por cima
        tempCtx.drawImage(this.canvas, 0, 0);

        // Retornar como PNG
        return tempCanvas.toDataURL('image/png');
    },

    /**
     * Obtém imagem em Blob
     */
    obterBlob: function(callback, formato) {
        formato = formato || 'image/png';
        this.canvas.toBlob(function(blob) {
            if (callback) callback(blob);
        }, formato);
    },

    /**
     * Define cor do traço
     */
    definirCor: function(cor) {
        this.config.cor = cor;
        this.ctx.strokeStyle = cor;
    },

    /**
     * Define espessura do traço
     */
    definirEspessura: function(espessura) {
        this.config.espessura = espessura;
        this.ctx.lineWidth = espessura;
    },

    /**
     * Desenha imagem no canvas
     */
    carregarImagem: function(urlImagem, callback) {
        var self = this;
        var img = new Image();
        img.onload = function() {
            self.ctx.drawImage(img, 0, 0, self.canvas.width, self.canvas.height);
            self.vazio = false;
            if (callback) callback(true);
        };
        img.onerror = function() {
            if (callback) callback(false);
        };
        img.src = urlImagem;
    },

    /**
     * Redimensiona o canvas
     */
    redimensionar: function(largura, altura) {
        var imagemAtual = this.obterImagem();
        this.canvas.width = largura;
        this.canvas.height = altura;
        this.ctx.strokeStyle = this.config.cor;
        this.ctx.lineWidth = this.config.espessura;
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';

        // Recarrega imagem anterior
        if (!this.vazio) {
            var self = this;
            var img = new Image();
            img.onload = function() {
                self.ctx.drawImage(img, 0, 0, largura, altura);
            };
            img.src = imagemAtual;
        } else {
            this.limpar();
        }
    }
};

/**
 * Gerenciador de múltiplos canvases de assinatura
 * Útil quando há assinatura do técnico e do cliente
 */
var AssinaturaManager = {
    assinaturas: {},

    criar: function(id, canvasId, opcoes) {
        this.assinaturas[id] = new AssinaturaCanvas(canvasId, opcoes);
        return this.assinaturas[id];
    },

    obter: function(id) {
        return this.assinaturas[id];
    },

    limparTodas: function() {
        for (var id in this.assinaturas) {
            if (this.assinaturas.hasOwnProperty(id)) {
                this.assinaturas[id].limpar();
            }
        }
    },

    obterDados: function() {
        var dados = {};
        for (var id in this.assinaturas) {
            if (this.assinaturas.hasOwnProperty(id)) {
                dados[id] = {
                    vazio: this.assinaturas[id].estaVazio(),
                    imagem: this.assinaturas[id].obterImagem()
                };
            }
        }
        return dados;
    },

    validarTodasPreenchidas: function() {
        for (var id in this.assinaturas) {
            if (this.assinaturas.hasOwnProperty(id)) {
                if (this.assinaturas[id].estaVazio()) {
                    return false;
                }
            }
        }
        return true;
    }
};
