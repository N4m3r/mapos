/**
 * Integração do Sistema de Check-in com Assinaturas
 * MAPOS OS
 *
 * Gerencia o fluxo completo de check-in/check-out
 */

var CheckinIntegracao = {
    config: {
        baseUrl: '',
        os_id: null,
        checkin_id: null,
        em_atendimento: false
    },

    /**
     * Inicializa o sistema de check-in
     */
    init: function(opcoes) {
        this.config = $.extend(this.config, opcoes);
        this._carregarStatus();
    },

    /**
     * Carrega status atual do servidor
     */
    _carregarStatus: function() {
        var self = this;

        if (!this.config.os_id) {
            console.error('OS ID não configurado');
            return;
        }

        $.ajax({
            url: self.config.baseUrl + 'index.php/checkin/status',
            type: 'POST',
            data: { os_id: self.config.os_id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    self.config.em_atendimento = response.em_atendimento;
                    self.config.checkin_id = response.checkin ? response.checkin.idCheckin : null;
                    self._atualizarInterface(response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro ao carregar status:', error);
            }
        });
    },

    /**
     * Atualiza interface com dados do servidor
     */
    _atualizarInterface: function(dados) {
        // Atualiza estado dos botões
        if (dados.em_atendimento) {
            $('.btn-iniciar-atendimento').hide();
            $('.btn-finalizar-atendimento').show();
            $('.panel-fotos-durante').show();
        } else {
            $('.btn-iniciar-atendimento').show();
            $('.btn-finalizar-atendimento').hide();
            $('.panel-fotos-durante').hide();
        }

        // Renderiza assinaturas existentes
        if (dados.assinaturas) {
            this._renderizarAssinaturas(dados.assinaturas);
        }

        // Renderiza fotos
        if (dados.fotos) {
            this._renderizarFotos(dados.fotos);
        }
    },

    /**
     * Inicia atendimento
     */
    iniciarAtendimento: function(dados, callbacks) {
        var self = this;

        // Verifica localmente primeiro, mas confia mais na resposta do servidor
        if (this.config.em_atendimento) {
            console.log('Aviso: Já existe atendimento em andamento (local)');
        }

        // Valida assinatura
        if (AssinaturaManager) {
            var assinaturas = AssinaturaManager.obterDados();
            if (assinaturas['assinatura-tecnico'] && assinaturas['assinatura-tecnico'].vazio) {
                if (callbacks.error) {
                    callbacks.error('A assinatura do técnico é obrigatória');
                }
                return;
            }
        }

        // Prepara dados usando FormData para preservar base64
        var postData = new FormData();
        postData.append('os_id', this.config.os_id);
        postData.append('observacao', dados.observacao || '');
        postData.append('latitude', dados.latitude || '');
        postData.append('longitude', dados.longitude || '');

        // Adiciona assinatura do técnico
        if (AssinaturaManager) {
            var assTecnico = AssinaturaManager.obter('assinatura-tecnico');
            if (assTecnico && !assTecnico.estaVazio()) {
                postData.append('assinatura', assTecnico.obterImagem());
            }
        }

        // Adiciona fotos de entrada
        if (dados.fotos && dados.fotos.length > 0) {
            dados.fotos.forEach(function(foto, index) {
                postData.append('fotos[' + index + ']', foto);
            });
        }

        // Obtém localização se disponível
        if (navigator.geolocation && !dados.latitude) {
            navigator.geolocation.getCurrentPosition(function(position) {
                postData.set('latitude', position.coords.latitude);
                postData.set('longitude', position.coords.longitude);
                self._enviarInicio(postData, callbacks);
            }, function() {
                // Sem geolocalização, envia sem
                self._enviarInicio(postData, callbacks);
            });
        } else {
            this._enviarInicio(postData, callbacks);
        }
    },

    /**
     * Envia requisição de início
     */
    _enviarInicio: function(postData, callbacks) {
        var self = this;

        $.ajax({
            url: self.config.baseUrl + 'index.php/checkin/iniciar',
            type: 'POST',
            data: postData,
            dataType: 'json',
            processData: false,
            contentType: false,
            beforeSend: function() {
                if (callbacks.beforeSend) callbacks.beforeSend();
            },
            success: function(response) {
                if (response.success) {
                    self.config.em_atendimento = true;
                    self.config.checkin_id = response.checkin_id;

                    // Limpa assinaturas
                    if (AssinaturaManager) {
                        AssinaturaManager.limparTodas();
                    }

                    if (callbacks.success) callbacks.success(response);
                } else {
                    if (callbacks.error) callbacks.error(response.message);
                }
            },
            error: function(xhr, status, error) {
                if (callbacks.error) callbacks.error('Erro na comunicação: ' + error);
            },
            complete: function() {
                if (callbacks.complete) callbacks.complete();
            }
        });
    },

    /**
     * Finaliza atendimento
     */
    finalizarAtendimento: function(dados, callbacks) {
        var self = this;

        if (!this.config.em_atendimento) {
            if (callbacks.error) {
                callbacks.error('Não há atendimento em andamento');
            }
            return;
        }

        // Valida assinaturas
        if (AssinaturaManager) {
            var assTecnico = AssinaturaManager.obter('assinatura-tecnico-saida');
            var assCliente = AssinaturaManager.obter('assinatura-cliente');

            if (!assTecnico || assTecnico.estaVazio()) {
                if (callbacks.error) {
                    callbacks.error('A assinatura do técnico na saída é obrigatória');
                }
                return;
            }

            if (!assCliente || assCliente.estaVazio()) {
                if (callbacks.error) {
                    callbacks.error('A assinatura do cliente é obrigatória');
                }
                return;
            }
        }

        // Prepara dados usando FormData para preservar base64
        var postData = new FormData();
        postData.append('os_id', this.config.os_id);
        postData.append('observacao', dados.observacao || '');
        postData.append('nome_cliente', dados.nome_cliente || '');
        postData.append('documento_cliente', dados.documento_cliente || '');
        postData.append('latitude', dados.latitude || '');
        postData.append('longitude', dados.longitude || '');

        // Adiciona assinaturas
        if (AssinaturaManager) {
            var assTecnicoSaida = AssinaturaManager.obter('assinatura-tecnico-saida');
            var assClienteSaida = AssinaturaManager.obter('assinatura-cliente');

            if (assTecnicoSaida && !assTecnicoSaida.estaVazio()) {
                postData.append('assinatura_tecnico', assTecnicoSaida.obterImagem());
            }

            if (assClienteSaida && !assClienteSaida.estaVazio()) {
                postData.append('assinatura_cliente', assClienteSaida.obterImagem());
            }
        }

        // Adiciona fotos de saída
        if (dados.fotos && dados.fotos.length > 0) {
            dados.fotos.forEach(function(foto, index) {
                postData.append('fotos[' + index + ']', foto);
            });
        }

        // Obtém localização se disponível
        if (navigator.geolocation && !dados.latitude) {
            navigator.geolocation.getCurrentPosition(function(position) {
                postData.set('latitude', position.coords.latitude);
                postData.set('longitude', position.coords.longitude);
                self._enviarFim(postData, callbacks);
            }, function() {
                self._enviarFim(postData, callbacks);
            });
        } else {
            this._enviarFim(postData, callbacks);
        }
    },

    /**
     * Envia requisição de fim
     */
    _enviarFim: function(postData, callbacks) {
        var self = this;

        $.ajax({
            url: self.config.baseUrl + 'index.php/checkin/finalizar',
            type: 'POST',
            data: postData,
            dataType: 'json',
            processData: false,
            contentType: false,
            beforeSend: function() {
                if (callbacks.beforeSend) callbacks.beforeSend();
            },
            success: function(response) {
                if (response.success) {
                    self.config.em_atendimento = false;
                    self.config.checkin_id = null;

                    // Limpa assinaturas
                    if (AssinaturaManager) {
                        AssinaturaManager.limparTodas();
                    }

                    if (callbacks.success) callbacks.success(response);
                } else {
                    if (callbacks.error) callbacks.error(response.message);
                }
            },
            error: function(xhr, status, error) {
                if (callbacks.error) callbacks.error('Erro na comunicação: ' + error);
            },
            complete: function() {
                if (callbacks.complete) callbacks.complete();
            }
        });
    },

    /**
     * Renderiza assinaturas existentes
     */
    _renderizarAssinaturas: function(assinaturas) {
        // Técnico entrada
        if (assinaturas.tecnico_entrada) {
            this._mostrarAssinaturaSalva('tecnico-entrada', assinaturas.tecnico_entrada);
        }

        // Técnico saída
        if (assinaturas.tecnico_saida) {
            this._mostrarAssinaturaSalva('tecnico-saida', assinaturas.tecnico_saida);
        }

        // Cliente
        if (assinaturas.cliente_saida) {
            this._mostrarAssinaturaSalva('cliente', assinaturas.cliente_saida);
        }
    },

    /**
     * Mostra assinatura já salva
     */
    _mostrarAssinaturaSalva: function(tipo, dados) {
        var container = $('.assinatura-salva-' + tipo);
        if (container.length) {
            // Usa url_visualizacao se disponível (para base64), senão monta URL tradicional
            var imgUrl = dados.url_visualizacao || dados.url;
            // Se ainda não tiver URL válida, tenta construir a partir do arquivo
            if (!imgUrl && dados.assinatura) {
                imgUrl = this.config.baseUrl + dados.assinatura;
            }
            container.html('<img src="' + imgUrl + '" style="max-width: 100%; border: 1px solid #ddd;">');
            container.show();
        }
    },

    /**
     * Renderiza fotos
     */
    _renderizarFotos: function(fotos) {
        // Entrada
        if (fotos.entrada && fotos.entrada.length > 0) {
            this._renderizarGaleriaFotos('entrada', fotos.entrada);
        }

        // Durante
        if (fotos.durante && fotos.durante.length > 0) {
            this._renderizarGaleriaFotos('durante', fotos.durante);
        }

        // Saída
        if (fotos.saida && fotos.saida.length > 0) {
            this._renderizarGaleriaFotos('saida', fotos.saida);
        }
    },

    /**
     * Renderiza galeria de fotos
     */
    _renderizarGaleriaFotos: function(etapa, fotos) {
        var container = $('#fotos-' + etapa + '-container');
        if (!container.length) return;

        var html = '<div class="row-fluid">';
        for (var i = 0; i < fotos.length; i++) {
            var foto = fotos[i];
            // Usa URL correta - url_visualizacao para base64 ou url normal
            var imgUrl = foto.url_visualizacao || foto.url;
            // Fallback: construir URL do endpoint verFotoDB se tiver idFoto
            if (!imgUrl && foto.idFoto) {
                imgUrl = this.config.baseUrl + 'index.php/checkin/verFotoDB/' + foto.idFoto;
            }
            html += '<div class="span4" style="margin-bottom: 10px;">';
            html += '<div class="thumbnail">';
            html += '<a href="' + imgUrl + '" target="_blank"><img src="' + imgUrl + '" style="max-height: 120px;"></a>';
            html += '</div>';
            html += '</div>';
        }
        html += '</div>';

        container.html(html);
    }
};
