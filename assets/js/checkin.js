// Helper para obter cookie CSRF
function getCsrfCookie(name) {
    var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    if (match) return match[2];
    return null;
}

const CheckinManager = {
    config: {
        baseUrl: '',
        osId: null,
        checkinId: null,
        debug: false
    },

    estado: {
        emAtendimento: false,
        fotosEntrada: [],
        fotosDurante: [],
        fotosSaida: [],
        assinaturas: {}
    },

    // Obtém o token CSRF configurado no meta ou cookie
    getCsrfToken: function() {
        // Tenta pegar do meta tag primeiro
        var tokenName = $('meta[name="csrf-token-name"]').attr('content') || 'MAPOS_TOKEN';
        var cookieName = $('meta[name="csrf-cookie-name"]').attr('content') || 'MAPOS_COOKIE';
        var token = getCsrfCookie(cookieName);
        return { name: tokenName, value: token };
    },

    // Adiciona CSRF token aos dados
    addCsrfToken: function(dados) {
        var csrf = this.getCsrfToken();
        if (csrf.name && csrf.value) {
            dados[csrf.name] = csrf.value;
        }
        return dados;
    },

    init: function(opcoes) {
        this.config = $.extend({}, this.config, opcoes);
        this.bindEventos();
        // Carrega status de forma assíncrona sem bloquear
        if (this.config.osId) {
            // Usa setTimeout para não bloquear a thread principal
            setTimeout(function() {
                if (typeof CheckinManager !== 'undefined') {
                    CheckinManager.carregarStatus();
                }
            }, 0);
        }
    },

    bindEventos: function() {
        const self = this;

        // Evita registro duplicado de eventos
        if (this._eventosRegistrados) {
            return;
        }
        this._eventosRegistrados = true;

        $(document).on('click', '#btn-iniciar-atendimento', function(e) {
            e.preventDefault();
            console.log('Botão Iniciar Atendimento clicado');
            self.abrirModalCheckin();
        });
        $(document).on('click', '#btn-finalizar-atendimento', function(e) {
            e.preventDefault();
            console.log('Botão Finalizar Atendimento clicado');
            self.abrirModalCheckout();
        });
        $(document).on('click', '#btn-confirmar-checkin', function() {
            console.log('Botão Confirmar Check-in clicado');
            self.iniciarAtendimento();
        });
        $(document).on('click', '#btn-confirmar-checkout', function(e) {
            e.preventDefault();
            console.log('Botão Confirmar Check-out clicado');
            self.finalizarAtendimento();
        });
        // Evento change para upload de fotos - usa namespace para evitar duplicatas
        $(document).off('change.checkinFoto').on('change.checkinFoto', '.checkin-foto-input', function(e) {
            const etapa = $(this).data('etapa') || 'durante';
            const input = $(this);

            console.log('Evento change disparado para etapa:', etapa, 'Files:', e.target.files.length);

            // Evita processamento se não houver arquivos
            if (!e.target.files || e.target.files.length === 0) {
                return;
            }

            // Desabilita o input temporariamente para evitar cliques duplos
            input.prop('disabled', true);

            // Processa os arquivos
            self.processarArquivos(e.target.files, etapa);

            // Limpa o input após um delay para permitir selecionar o mesmo arquivo novamente
            // mas sem causar duplicação
            setTimeout(function() {
                input.val('').prop('disabled', false);
            }, 500);
        });
        $(document).on('click', '.btn-capturar-foto', function() {
            const etapa = $(this).data('etapa') || 'durante';
            self.abrirCamera(etapa);
        });
        $(document).on('click', '.btn-remover-foto', function() {
            const fotoId = $(this).data('foto-id');
            self.removerFoto(fotoId);
        });
        $(document).on('click', '.btn-remover-assinatura', function() {
            const assinaturaId = $(this).data('assinatura-id');
            self.removerAssinatura(assinaturaId);
        });
    },

    carregarStatus: function() {
        const self = this;
        let dados = { os_id: this.config.osId };
        dados = this.addCsrfToken(dados);

        if (this.config.debug) {
            console.log('Enviando requisição para checkin/status:', dados);
        }

        $.ajax({
            url: this.config.baseUrl + 'index.php/checkin/status',
            type: 'POST',
            dataType: 'json',
            data: dados,
            cache: false, // Evita cache para garantir dados atualizados
            async: true,  // Garante que seja assíncrono
            success: function(response) {
                if (response.success) {
                    self.estado.emAtendimento = response.em_atendimento;
                    self.config.checkinId = response.checkin ? response.checkin.idCheckin : null;
                    self.estado.assinaturas = response.assinaturas || {};
                    self.atualizarUI();
                }
            },
            error: function(xhr, status, error) {
                if (self.config.debug) {
                    console.error('Erro ao carregar status do checkin:', error);
                }
            }
        });
    },

    atualizarUI: function() {
        if (this.estado.emAtendimento) {
            $('#btn-iniciar-atendimento').addClass('hidden');
            $('#btn-finalizar-atendimento').removeClass('hidden');
        } else {
            $('#btn-iniciar-atendimento').removeClass('hidden');
            $('#btn-finalizar-atendimento').addClass('hidden');
        }
    },

    abrirModalCheckin: function() {
        $('#modal-checkin').modal('show');

        // Limpa os campos do formulário
        $('#checkin-observacao').val('');
        $('#checkin-latitude, #checkin-longitude').val('');
        $('#checkin-geo-status').text('');

        // Limpa a assinatura ao abrir o modal
        if (typeof AssinaturaManager !== 'undefined') {
            const assinatura = AssinaturaManager.obter('assinatura-tecnico-entrada');
            if (assinatura) assinatura.limpar();
        }

        // Limpa previews de fotos
        $('#preview-fotos-entrada').empty();
    },

    abrirModalCheckout: function() {
        $('#modal-checkout').modal('show');

        // Limpa os campos do formulário (exceto nome/documento que vêm da OS)
        $('#checkout-observacao').val('');
        $('#checkout-latitude, #checkout-longitude').val('');
        $('#checkout-geo-status').text('');

        // Limpa as assinaturas ao abrir o modal
        if (typeof AssinaturaManager !== 'undefined') {
            const assinaturaTecnico = AssinaturaManager.obter('assinatura-tecnico-saida');
            const assinaturaCliente = AssinaturaManager.obter('assinatura-cliente-saida');
            if (assinaturaTecnico) assinaturaTecnico.limpar();
            if (assinaturaCliente) assinaturaCliente.limpar();
        }

        // Limpa previews de fotos
        $('#preview-fotos-saida').empty();
    },

    iniciarAtendimento: function() {
        const self = this;
        const osId = this.config.osId;

        if (!osId) {
            alert('OS não identificada');
            return;
        }

        // Verifica se a assinatura foi inicializada
        if (typeof AssinaturaManager === 'undefined') {
            alert('Sistema de assinaturas não carregado. Recarregue a página.');
            return;
        }

        const assinatura = AssinaturaManager.obter('assinatura-tecnico-entrada');
        if (!assinatura) {
            alert('Canvas de assinatura não inicializado. Feche e reabra o modal.');
            return;
        }

        // Coleta dados da assinatura
        let assinaturaImg = '';
        if (!assinatura.estaVazio()) {
            assinaturaImg = assinatura.obterImagem();
        }

        // Usar FormData para enviar dados sem codificação URL
        let dados = new FormData();
        dados.append('os_id', osId);
        dados.append('observacao', $('#checkin-observacao').val() || '');
        dados.append('latitude', $('#checkin-latitude').val() || '');
        dados.append('longitude', $('#checkin-longitude').val() || '');
        dados.append('assinatura', assinaturaImg);

        // Adiciona token CSRF
        const csrf = this.getCsrfToken();
        if (csrf.name && csrf.value) {
            dados.append(csrf.name, csrf.value);
        }

        const btn = $('#btn-confirmar-checkin');
        const textoOriginal = btn.html();
        btn.html('<i class="bx bx-loader bx-spin"></i> Iniciando...').prop('disabled', true);

        $.ajax({
            url: this.config.baseUrl + 'index.php/checkin/iniciar',
            type: 'POST',
            dataType: 'json',
            data: dados,
            processData: false,
            contentType: false,
            success: function(response) {
                btn.html(textoOriginal).prop('disabled', false);
                if (response.success) {
                    $('#modal-checkin').modal('hide');
                    self.config.checkinId = response.checkin_id;
                    self.estado.emAtendimento = true;
                    self.atualizarUI();
                    alert('Atendimento iniciado com sucesso!');
                } else {
                    alert(response.message || 'Erro ao iniciar atendimento');
                }
            },
            error: function(xhr, status, error) {
                btn.html(textoOriginal).prop('disabled', false);
                console.error('Erro:', xhr.responseText);
                var msg = 'Erro ao iniciar atendimento.';
                if (xhr.responseText && xhr.responseText.indexOf('<') !== 0) {
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        msg = resp.message || msg;
                    } catch(e) {
                        msg += ' Verifique o console para mais detalhes.';
                    }
                } else if (xhr.status === 403) {
                    msg = 'Erro de permissão ou token CSRF inválido. Recarregue a página.';
                }
                alert(msg);
            }
        });
    },

    finalizarAtendimento: function() {
        const self = this;
        const osId = this.config.osId;

        if (!osId) {
            alert('OS não identificada');
            return;
        }

        // Verifica se o sistema de assinaturas está carregado
        if (typeof AssinaturaManager === 'undefined') {
            alert('Sistema de assinaturas não carregado. Recarregue a página.');
            return;
        }

        // Obtém as assinaturas
        const assinaturaTecnico = AssinaturaManager.obter('assinatura-tecnico-saida');
        const assinaturaCliente = AssinaturaManager.obter('assinatura-cliente-saida');

        if (!assinaturaTecnico || !assinaturaCliente) {
            alert('Canvas de assinatura não inicializado. Feche e reabra o modal.');
            return;
        }

        // Verifica se as assinaturas foram preenchidas
        if (assinaturaTecnico.estaVazio()) {
            alert('Por favor, faça a assinatura do técnico');
            return;
        }

        if (assinaturaCliente.estaVazio()) {
            alert('Por favor, faça a assinatura do cliente');
            return;
        }

        // Obtém o nome do cliente do campo (opcional - já temos na OS)
        // O campo pode ter ID 'assinatura-cliente-saida-nome' ou similar
        var nomeCliente = 'Cliente';
        try {
            var campoNomeCliente = $('#assinatura-cliente-saida-nome');
            if (campoNomeCliente.length && campoNomeCliente.val()) {
                var val = campoNomeCliente.val();
                if (val && typeof val === 'string') {
                    nomeCliente = val.trim() || 'Cliente';
                }
            }
        } catch (e) {
            console.log('Campo nome cliente não encontrado, usando padrão');
            nomeCliente = 'Cliente';
        }

        // Coleta as imagens das assinaturas
        const assinaturaTecnicoImg = assinaturaTecnico.obterImagem();
        const assinaturaClienteImg = assinaturaCliente.obterImagem();

        // Usar FormData para enviar dados sem codificação URL
        let dados = new FormData();
        dados.append('os_id', osId);
        dados.append('observacao', $('#checkout-observacao').val() || '');
        dados.append('nome_cliente', nomeCliente);
        // Documento do cliente - campo pode ter ID diferente
        var documentoCliente = '';
        var campoDocumento = $('#assinatura-cliente-saida-documento');
        if (campoDocumento.length) {
            documentoCliente = campoDocumento.val() || '';
        }
        dados.append('documento_cliente', documentoCliente);
        dados.append('latitude', $('#checkout-latitude').val() || '');
        dados.append('longitude', $('#checkout-longitude').val() || '');
        dados.append('assinatura_tecnico', assinaturaTecnicoImg);
        dados.append('assinatura_cliente', assinaturaClienteImg);

        // Adiciona token CSRF
        const csrf = this.getCsrfToken();
        if (csrf.name && csrf.value) {
            dados.append(csrf.name, csrf.value);
        }

        const btn = $('#btn-confirmar-checkout');
        const textoOriginal = btn.html();
        btn.html('<i class="bx bx-loader bx-spin"></i> Finalizando...').prop('disabled', true);

        $.ajax({
            url: this.config.baseUrl + 'index.php/checkin/finalizar',
            type: 'POST',
            dataType: 'json',
            data: dados,
            processData: false,
            contentType: false,
            success: function(response) {
                btn.html(textoOriginal).prop('disabled', false);
                if (response.success) {
                    $('#modal-checkout').modal('hide');
                    self.config.checkinId = null;
                    self.estado.emAtendimento = false;
                    self.atualizarUI();
                    alert('Atendimento finalizado com sucesso!');
                } else {
                    alert(response.message || 'Erro ao finalizar atendimento');
                }
            },
            error: function(xhr, status, error) {
                btn.html(textoOriginal).prop('disabled', false);
                console.error('Erro:', xhr.responseText);
                var msg = 'Erro ao finalizar atendimento.';
                if (xhr.responseText && xhr.responseText.indexOf('<') !== 0) {
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        msg = resp.message || msg;
                    } catch(e) {
                        msg += ' Verifique o console para mais detalhes.';
                    }
                } else if (xhr.status === 403) {
                    msg = 'Erro de permissão ou token CSRF inválido. Recarregue a página.';
                }
                alert(msg);
            }
        });
    },

    // Flag para evitar processamento duplicado
    _processandoUpload: false,

    processarArquivos: function(arquivos, etapa) {
        const self = this;

        // Evita processamento duplicado simultâneo
        if (this._processandoUpload) {
            console.log('Upload já em andamento, aguardando...');
            return;
        }

        // Valida e prepara os arquivos para upload (apenas UM arquivo)
        const arquivosValidos = [];
        for (let i = 0; i < arquivos.length; i++) {
            const arquivo = arquivos[i];

            // Valida tamanho máximo (10MB antes da compressão)
            if (arquivo.size > 10 * 1024 * 1024) {
                alert('Arquivo "' + arquivo.name + '" muito grande. Tamanho máximo: 10MB');
                continue;
            }

            // Valida tipo de arquivo (incluindo extensão para mobile)
            const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/heic', 'image/heif', 'image/webp'];
            const extensao = arquivo.name.split('.').pop().toLowerCase();
            const extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'heic', 'heif', 'webp'];

            if (tiposPermitidos.indexOf(arquivo.type.toLowerCase()) === -1 &&
                extensoesPermitidas.indexOf(extensao) === -1) {
                alert('Arquivo "' + arquivo.name + '" não é uma imagem válida. Use JPG, PNG, GIF ou HEIC.');
                continue;
            }

            arquivosValidos.push(arquivo);
            break; // Apenas o primeiro arquivo válido é processado
        }

        if (arquivosValidos.length === 0) {
            return;
        }

        // Marca como processando para evitar duplicatas
        this._processandoUpload = true;
        console.log('Processando 1 arquivo para etapa:', etapa);

        // Adiciona preview primeiro
        for (let i = 0; i < arquivosValidos.length; i++) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const base64 = e.target.result;
                const containerId = etapa === 'entrada' ? 'preview-fotos-entrada' : 'preview-fotos-' + etapa;
                self.adicionarPreviewFoto(containerId, base64);
            };
            reader.readAsDataURL(arquivosValidos[i]);
        }

        // Processa cada arquivo com compressão antes do upload
        if (typeof CheckinFotos !== 'undefined') {
            console.log('Iniciando processamento de ' + arquivosValidos.length + ' foto(s)...');

            var arquivosProcessados = [];
            var processadosCount = 0;

            // Processa cada imagem (redimensiona e comprime)
            for (let i = 0; i < arquivosValidos.length; i++) {
                (function(arquivo) {
                    CheckinFotos.processarImagem(arquivo, function(imagemProcessada) {
                        processadosCount++;

                        if (imagemProcessada) {
                            arquivosProcessados.push(imagemProcessada);
                        }

                        // Quando todos estiverem processados, faz o upload
                        if (processadosCount === arquivosValidos.length) {
                            if (arquivosProcessados.length === 0) {
                                alert('Nenhuma imagem pôde ser processada.');
                                self._processandoUpload = false;
                                return;
                            }

                            self._enviarFotosProcessadas(arquivosProcessados, etapa);
                        }
                    });
                })(arquivosValidos[i]);
            }
        } else {
            console.warn('CheckinFotos não disponível - upload não realizado');
            this._processandoUpload = false;
        }
    },

    /**
     * Envia fotos já processadas (comprimidas) para o servidor
     * Agora limitado a apenas UMA foto por vez
     */
    _enviarFotosProcessadas: function(fotosBase64, etapa) {
        const self = this;

        // Limita a apenas a primeira foto (uma por vez)
        if (fotosBase64.length > 1) {
            console.log('Apenas a primeira foto será enviada. Ignorando ' + (fotosBase64.length - 1) + ' foto(s) extra(s).');
        }

        // Pega apenas a primeira foto
        var fotoBase64 = fotosBase64[0];
        if (!fotoBase64) {
            alert('Nenhuma foto válida para enviar.');
            this._processandoUpload = false;
            return;
        }

        let dados = { os_id: self.config.osId };
        if (self.config.checkinId) {
            dados.checkin_id = self.config.checkinId;
        }
        dados.etapa = etapa;
        dados.foto = fotoBase64;

        // Adiciona token CSRF
        const csrf = self.getCsrfToken();
        if (csrf.name && csrf.value) {
            dados[csrf.name] = csrf.value;
        }

        $.ajax({
            url: self.config.baseUrl + 'index.php/checkin/adicionarFoto',
            type: 'POST',
            data: dados,
            dataType: 'json',
            success: function(response) {
                // Libera o flag de processamento
                self._processandoUpload = false;

                if (response.success) {
                    console.log('Foto enviada com sucesso:', response.foto_id);
                    // Limpa o preview após upload bem-sucedido
                    $('#preview-fotos-entrada, #preview-fotos-saida').empty();
                    // Recarrega apenas as fotos via AJAX sem fechar o modal
                    self.recarregarFotos(etapa);
                    // Mostra mensagem de sucesso
                    if (typeof swal !== 'undefined') {
                        swal('Sucesso!', 'Foto enviada com sucesso!', 'success');
                    }
                } else {
                    alert('Erro ao enviar foto: ' + (response.message || 'Erro desconhecido'));
                }
            },
            error: function(xhr, status, error) {
                // Libera o flag de processamento
                self._processandoUpload = false;

                console.error('Erro ao enviar foto:', error);
                alert('Erro ao enviar foto. Verifique sua conexão.');
                // Limpa o preview mesmo em caso de erro
                $('#preview-fotos-entrada, #preview-fotos-saida').empty();
            }
        });
    },

    /**
     * Recarrega as fotos de uma etapa específica via AJAX
     * sem fechar o modal ou recarregar a página
     */
    recarregarFotos: function(etapa) {
        const self = this;

        let dados = { os_id: this.config.osId };
        if (etapa) {
            dados.etapa = etapa;
        }
        dados = this.addCsrfToken(dados);

        $.ajax({
            url: this.config.baseUrl + 'index.php/checkin/listarFotos',
            type: 'POST',
            data: dados,
            dataType: 'json',
            success: function(response) {
                if (response.success && response.fotos) {
                    // Dispara evento customizado para atualizar a galeria
                    $(document).trigger('checkin:fotosAtualizadas', [response.fotos, etapa]);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro ao recarregar fotos:', error);
            }
        });
    },

    adicionarPreviewFoto: function(containerId, base64) {
        // Limita a apenas um preview por vez (remove previews anteriores)
        $('#' + containerId).empty();

        const html = '<div class="preview-foto-item" style="display:inline-block; margin:5px; position:relative;">' +
            '<img src="' + base64 + '" style="max-width:100px;max-height:100px; border:1px solid #ddd; border-radius:4px;">' +
            '<div class="upload-status" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:rgba(0,0,0,0.7); color:white; padding:2px 8px; border-radius:4px; font-size:11px;">Enviando...</div>' +
            '</div>';
        $('#' + containerId).append(html);
    },

    abrirCamera: function(etapa) {
        // Evita abrir múltiplas câmeras simultâneas
        if (this._processandoUpload) {
            console.log('Upload em andamento, aguarde...');
            return;
        }

        const input = $('<input type="file" accept="image/*" capture="camera" style="display:none">');
        $('body').append(input);
        input.one('change', function(e) {
            if (e.target.files && e.target.files.length > 0) {
                CheckinManager.processarArquivos(e.target.files, etapa);
            }
            // Remove o input após um delay para garantir que o processamento iniciou
            setTimeout(function() {
                input.remove();
            }, 100);
        });
        input.trigger('click');
    },

    removerFoto: function(fotoId) {
        if (!confirm('Tem certeza que deseja remover esta foto?')) return;

        const self = this;
        let dados = { foto_id: fotoId };
        dados = this.addCsrfToken(dados);

        $.ajax({
            url: this.config.baseUrl + 'index.php/checkin/removerFoto',
            type: 'POST',
            dataType: 'json',
            data: dados,
            success: function(response) {
                if (response.success) {
                    // Remove o elemento da DOM
                    $('#foto-item-' + fotoId).fadeOut(300, function() {
                        $(this).remove();
                    });
                    // Mostra mensagem de sucesso
                    if (typeof swal !== 'undefined') {
                        swal('Sucesso!', 'Foto removida com sucesso.', 'success');
                    }
                } else {
                    if (typeof swal !== 'undefined') {
                        swal('Erro!', response.message || 'Erro ao remover foto.', 'error');
                    } else {
                        alert(response.message || 'Erro ao remover foto.');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro ao remover foto:', error);
                if (typeof swal !== 'undefined') {
                    swal('Erro!', 'Erro ao comunicar com o servidor.', 'error');
                } else {
                    alert('Erro ao comunicar com o servidor.');
                }
            }
        });
    },

    removerAssinatura: function(assinaturaId) {
        if (!confirm('Tem certeza que deseja remover esta assinatura?')) return;

        const self = this;
        let dados = { assinatura_id: assinaturaId };
        dados = this.addCsrfToken(dados);

        $.ajax({
            url: this.config.baseUrl + 'index.php/checkin/removerAssinatura',
            type: 'POST',
            dataType: 'json',
            data: dados,
            success: function(response) {
                if (response.success) {
                    // Remove o elemento da DOM
                    $('#assinatura-item-' + assinaturaId).fadeOut(300, function() {
                        $(this).remove();
                    });
                    // Mostra mensagem de sucesso
                    if (typeof swal !== 'undefined') {
                        swal('Sucesso!', 'Assinatura removida com sucesso.', 'success');
                    }
                } else {
                    if (typeof swal !== 'undefined') {
                        swal('Erro!', response.message || 'Erro ao remover assinatura.', 'error');
                    } else {
                        alert(response.message || 'Erro ao remover assinatura.');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro ao remover assinatura:', error);
                if (typeof swal !== 'undefined') {
                    swal('Erro!', 'Erro ao comunicar com o servidor.', 'error');
                } else {
                    alert('Erro ao comunicar com o servidor.');
                }
            }
        });
    }
};

window.CheckinManager = CheckinManager;

// Flag para evitar dupla inicialização
CheckinManager._inicializado = false;
