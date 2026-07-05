/**
 * Helper para upload de fotos do Check-in
 * MAPOS OS - Sistema de Assinaturas e Fotos
 */

// Helper para obter cookie CSRF (mesmo do checkin.js)
function getCsrfCookie(name) {
    var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    if (match) return match[2];
    return null;
}

var CheckinFotos = {
    // Configurações padrão
    config: {
        maxFileSize: 10 * 1024 * 1024, // 10MB (aumentado para mobile)
        maxFileSizeUpload: 5 * 1024 * 1024, // 5MB após compressão
        allowedTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/heic', 'image/heif', 'image/webp'],
        allowedExtensions: ['jpg', 'jpeg', 'png', 'gif', 'heic', 'heif', 'webp'],
        baseUrl: '',
        maxWidth: 1920, // Largura máxima para redimensionamento
        maxHeight: 1920, // Altura máxima para redimensionamento
        qualidade: 0.8 // Qualidade da compressão JPEG
    },

    /**
     * Mostra overlay de progresso circular para upload
     */
    mostrarProgressoUpload: function(mensagem, container) {
        container = container || 'body';
        var id = 'upload-progress-' + Date.now();

        var html = '';
        html += '<div id="' + id + '" class="upload-progress-overlay" style="';
        html += 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; ';
        html += 'background: rgba(0,0,0,0.7); z-index: 9999; display: flex; ';
        html += 'justify-content: center; align-items: center; flex-direction: column;';
        html += '">';
        html += '<div style="position: relative; width: 120px; height: 120px;">';
        // Círculo de fundo
        html += '<svg width="120" height="120" style="transform: rotate(-90deg);">';
        html += '<circle cx="60" cy="60" r="50" stroke="#333" stroke-width="8" fill="none"/>';
        html += '<circle id="' + id + '-circle" cx="60" cy="60" r="50" stroke="#28a745" stroke-width="8" fill="none" ';
        html += 'stroke-dasharray="314" stroke-dashoffset="314" stroke-linecap="round" ';
        html += 'style="transition: stroke-dashoffset 0.3s ease;"/>';
        html += '</svg>';
        // Porcentagem no centro
        html += '<div id="' + id + '-texto" style="';
        html += 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); ';
        html += 'color: #fff; font-size: 24px; font-weight: bold; font-family: Arial, sans-serif;';
        html += '">0%</div>';
        html += '</div>';
        // Mensagem
        html += '<div id="' + id + '-msg" style="';
        html += 'color: #fff; margin-top: 20px; font-size: 16px; text-align: center; ';
        html += 'max-width: 80%; padding: 0 20px;';
        html += '">' + (mensagem || 'Enviando...') + '</div>';
        // Nome do arquivo
        html += '<div id="' + id + '-arquivo" style="';
        html += 'color: #aaa; margin-top: 10px; font-size: 12px; text-align: center;';
        html += '"></div>';
        html += '</div>';

        $(container).append(html);

        return {
            id: id,
            atualizar: function(porcentagem) {
                var circle = $('#' + id + '-circle');
                var texto = $('#' + id + '-texto');
                var offset = 314 - (314 * porcentagem / 100);
                circle.css('stroke-dashoffset', offset);
                texto.text(Math.round(porcentagem) + '%');
            },
            definirArquivo: function(nome) {
                $('#' + id + '-arquivo').text(nome);
            },
            definirMensagem: function(msg) {
                $('#' + id + '-msg').text(msg);
            },
            fechar: function() {
                $('#' + id).fadeOut(300, function() {
                    $(this).remove();
                });
            }
        };
    },

    /**
     * Upload com progresso usando XMLHttpRequest
     */
    uploadComProgresso: function(url, formData, callbacks) {
        var self = this;
        var xhr = new XMLHttpRequest();

        // Cria overlay de progresso
        var progresso = self.mostrarProgressoUpload(callbacks.mensagem || 'Enviando foto...');

        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                var percentComplete = (e.loaded / e.total) * 100;
                progresso.atualizar(percentComplete);

                if (callbacks.progress) {
                    callbacks.progress(percentComplete, e.loaded, e.total);
                }
            }
        }, false);

        xhr.addEventListener('load', function() {
            progresso.atualizar(100);

            setTimeout(function() {
                progresso.fechar();

                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (callbacks.success) callbacks.success(response);
                    } catch (e) {
                        if (callbacks.error) callbacks.error(xhr, 'parse_error', e);
                    }
                } else {
                    if (callbacks.error) callbacks.error(xhr, 'http_error', xhr.statusText);
                }
            }, 500); // Mostra 100% por meio segundo antes de fechar
        }, false);

        xhr.addEventListener('error', function() {
            progresso.fechar();
            if (callbacks.error) callbacks.error(xhr, 'network_error', 'Erro de rede');
        }, false);

        xhr.addEventListener('abort', function() {
            progresso.fechar();
            if (callbacks.error) callbacks.error(xhr, 'aborted', 'Upload cancelado');
        }, false);

        xhr.open('POST', url);
        xhr.send(formData);
    },

    /**
     * Obtém o token CSRF
     */
    getCsrfToken: function() {
        var tokenName = $('meta[name="csrf-token-name"]').attr('content') || 'MAPOS_TOKEN';
        var cookieName = $('meta[name="csrf-cookie-name"]').attr('content') || 'MAPOS_COOKIE';
        var token = getCsrfCookie(cookieName);
        return { name: tokenName, value: token };
    },

    /**
     * Adiciona CSRF token ao FormData
     */
    addCsrfToFormData: function(formData) {
        var csrf = this.getCsrfToken();
        if (csrf.name && csrf.value) {
            formData.append(csrf.name, csrf.value);
        }
        return formData;
    },

    /**
     * Inicializa o helper
     */
    init: function(options) {
        this.config = $.extend(this.config, options);
    },

    /**
     * Valida arquivo antes do upload
     */
    validarArquivo: function(arquivo) {
        // Verifica tamanho (permitir até 10MB antes da compressão)
        if (arquivo.size > this.config.maxFileSize) {
            return {
                valido: false,
                erro: 'Arquivo muito grande. Tamanho máximo: 10MB'
            };
        }

        // Verifica tipo MIME ou extensão (para mobile que pode não enviar MIME correto)
        var extensao = arquivo.name.split('.').pop().toLowerCase();
        var tipoValido = this.config.allowedTypes.indexOf(arquivo.type) !== -1 ||
                         this.config.allowedExtensions.indexOf(extensao) !== -1;

        if (!tipoValido) {
            return {
                valido: false,
                erro: 'Tipo de arquivo não permitido. Use JPG, PNG, GIF ou HEIC'
            };
        }

        return { valido: true };
    },

    /**
     * Redimensiona e comprime imagem antes do upload
     * Essencial para fotos de câmeras modernas (iPhone, Android)
     */
    processarImagem: function(arquivo, callback) {
        var self = this;
        var reader = new FileReader();

        reader.onload = function(e) {
            var img = new Image();
            img.onload = function() {
                var canvas = document.createElement('canvas');
                var ctx = canvas.getContext('2d');

                var width = img.width;
                var height = img.height;

                // Calcula novas dimensões mantendo proporção
                if (width > self.config.maxWidth || height > self.config.maxHeight) {
                    if (width / height > self.config.maxWidth / self.config.maxHeight) {
                        height = Math.round(height * (self.config.maxWidth / width));
                        width = self.config.maxWidth;
                    } else {
                        width = Math.round(width * (self.config.maxHeight / height));
                        height = self.config.maxHeight;
                    }
                }

                // Para mobile, garantir tamanho mínimo para assinaturas
                if (width < 300) {
                    var proporcao = 300 / width;
                    width = 300;
                    height = Math.round(height * proporcao);
                }

                canvas.width = width;
                canvas.height = height;

                // Desenha com qualidade
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, width, height);
                ctx.drawImage(img, 0, 0, width, height);

                // Converte para JPEG com compressão
                var qualidade = self.config.qualidade;
                // Reduzir qualidade se imagem ainda for muito grande
                if (arquivo.size > 2 * 1024 * 1024) {
                    qualidade = 0.7;
                }

                var dataUrl = canvas.toDataURL('image/jpeg', qualidade);
                callback(dataUrl);
            };

            img.onerror = function() {
                console.error('Erro ao carregar imagem para processamento');
                callback(null);
            };

            img.src = e.target.result;
        };

        reader.onerror = function() {
            console.error('Erro ao ler arquivo');
            callback(null);
        };

        reader.readAsDataURL(arquivo);
    },

    /**
     * Converte arquivo para base64
     */
    arquivoParaBase64: function(arquivo, callback) {
        var reader = new FileReader();
        reader.onload = function(e) {
            callback(e.target.result);
        };
        reader.onerror = function() {
            callback(null);
        };
        reader.readAsDataURL(arquivo);
    },

    /**
     * Comprime imagem (opcional)
     */
    comprimirImagem: function(base64Imagem, qualidade, maxWidth, callback) {
        var img = new Image();
        img.onload = function() {
            var canvas = document.createElement('canvas');
            var width = img.width;
            var height = img.height;

            // Redimensiona se necessário
            if (maxWidth && width > maxWidth) {
                height = Math.round(height * (maxWidth / width));
                width = maxWidth;
            }

            canvas.width = width;
            canvas.height = height;

            var ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);

            callback(canvas.toDataURL('image/jpeg', qualidade || 0.8));
        };
        img.src = base64Imagem;
    },

    /**
     * Upload de foto via base64
     */
    uploadFoto: function(dados, callbacks) {
        var self = this;
        var data = {
            os_id: dados.os_id,
            checkin_id: dados.checkin_id,
            foto: dados.foto,
            descricao: dados.descricao || ''
        };

        // Adiciona CSRF token
        var csrf = self.getCsrfToken();
        if (csrf.name && csrf.value) {
            data[csrf.name] = csrf.value;
        }

        $.ajax({
            url: self.config.baseUrl + 'index.php/checkin/adicionarFoto',
            type: 'POST',
            data: data,
            dataType: 'json',
            beforeSend: function() {
                if (callbacks.beforeSend) callbacks.beforeSend();
            },
            success: function(response) {
                if (callbacks.success) callbacks.success(response);
            },
            error: function(xhr, status, error) {
                if (callbacks.error) callbacks.error(xhr, status, error);
            },
            complete: function() {
                if (callbacks.complete) callbacks.complete();
            }
        });
    },

    /**
     * Upload tradicional de arquivo com progresso
     */
    uploadArquivo: function(dados, callbacks) {
        var self = this;
        var formData = new FormData();
        formData.append('os_id', dados.os_id);
        formData.append('checkin_id', dados.checkin_id || '');
        formData.append('etapa', dados.etapa || 'durante');
        formData.append('descricao', dados.descricao || '');
        formData.append('arquivo', dados.arquivo);

        // Adiciona CSRF token
        formData = self.addCsrfToFormData(formData);

        // Usa upload com progresso se disponível
        if (typeof XMLHttpRequest !== 'undefined') {
            self.uploadComProgresso(
                self.config.baseUrl + 'index.php/checkin/uploadArquivo',
                formData,
                {
                    mensagem: 'Enviando foto...',
                    progress: callbacks.progress,
                    success: function(response) {
                        if (callbacks.success) callbacks.success(response);
                        if (callbacks.complete) callbacks.complete();
                    },
                    error: function(xhr, status, error) {
                        if (callbacks.error) callbacks.error(xhr, status, error);
                        if (callbacks.complete) callbacks.complete();
                    }
                }
            );
        } else {
            // Fallback para jQuery.ajax
            $.ajax({
                url: self.config.baseUrl + 'index.php/checkin/uploadArquivo',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                beforeSend: function() {
                    if (callbacks.beforeSend) callbacks.beforeSend();
                },
                success: function(response) {
                    if (callbacks.success) callbacks.success(response);
                },
                error: function(xhr, status, error) {
                    if (callbacks.error) callbacks.error(xhr, status, error);
                },
                complete: function() {
                    if (callbacks.complete) callbacks.complete();
                }
            });
        }
    },

    /**
     * Upload múltiplo com progresso circular
     */
    uploadMultiplo: function(dados, callbacks) {
        var self = this;

        // Para upload de múltiplos arquivos, usamos upload individual sequencial com progresso
        // Isso permite mostrar o progresso de cada arquivo
        if (dados.arquivos && dados.arquivos.length > 0) {
            var arquivos = dados.arquivos;
            var arquivosComSucesso = [];
            var arquivosComErro = [];

            // Cria overlay de progresso
            var progresso = self.mostrarProgressoUpload('Preparando upload...');

            function uploadProximo(index) {
                if (index >= arquivos.length) {
                    // Todos os arquivos processados
                    progresso.fechar();
                    var response = {
                        success: arquivosComErro.length === 0,
                        message: arquivosComSucesso.length + ' foto(s) enviada(s) com sucesso' +
                                 (arquivosComErro.length > 0 ? '. ' + arquivosComErro.length + ' falha(s).' : ''),
                        files: arquivosComSucesso
                    };
                    if (response.success) {
                        if (callbacks.success) callbacks.success(response);
                    } else {
                        if (callbacks.error) callbacks.error(null, 'partial_error', response);
                    }
                    if (callbacks.complete) callbacks.complete();
                    return;
                }

                var arquivo = arquivos[index];
                var formData = new FormData();
                formData.append('os_id', dados.os_id);
                formData.append('checkin_id', dados.checkin_id || '');
                formData.append('etapa', dados.etapa || 'durante');
                formData.append('arquivo', arquivo);

                // Adiciona CSRF token
                formData = self.addCsrfToFormData(formData);

                // Atualiza mensagem
                progresso.definirMensagem('Enviando foto ' + (index + 1) + ' de ' + arquivos.length);
                progresso.definirArquivo(arquivo.name);

                // Usa XMLHttpRequest para ter progresso
                var xhr = new XMLHttpRequest();

                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        var percentFile = (e.loaded / e.total) * 100;
                        // Calcula progresso geral: arquivos anteriores + porcentagem do atual
                        var percentGeral = ((index + (percentFile / 100)) / arquivos.length) * 100;
                        progresso.atualizar(percentGeral);
                    }
                }, false);

                xhr.addEventListener('load', function() {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                arquivosComSucesso.push(response);
                            } else {
                                arquivosComErro.push({ arquivo: arquivo.name, erro: response.message });
                            }
                        } catch (e) {
                            arquivosComErro.push({ arquivo: arquivo.name, erro: 'Erro ao processar resposta' });
                        }
                    } else {
                        arquivosComErro.push({ arquivo: arquivo.name, erro: 'Erro HTTP ' + xhr.status });
                    }
                    // Próximo arquivo
                    uploadProximo(index + 1);
                }, false);

                xhr.addEventListener('error', function() {
                    arquivosComErro.push({ arquivo: arquivo.name, erro: 'Erro de rede' });
                    uploadProximo(index + 1);
                }, false);

                xhr.open('POST', self.config.baseUrl + 'index.php/checkin/uploadArquivo');
                xhr.send(formData);
            }

            // Inicia o upload do primeiro arquivo
            uploadProximo(0);
        } else {
            // Fallback: upload tradicional se não houver arquivos
            if (callbacks.error) callbacks.error(null, 'no_files', 'Nenhum arquivo selecionado');
            if (callbacks.complete) callbacks.complete();
        }
    },

    /**
     * Lista fotos de uma OS
     */
    listarFotos: function(os_id, etapa, callbacks) {
        var self = this;
        var data = {
            os_id: os_id,
            etapa: etapa
        };

        // Adiciona CSRF token
        var csrf = self.getCsrfToken();
        if (csrf.name && csrf.value) {
            data[csrf.name] = csrf.value;
        }

        $.ajax({
            url: self.config.baseUrl + 'index.php/checkin/listarFotos',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (callbacks.success) callbacks.success(response);
            },
            error: function(xhr, status, error) {
                if (callbacks.error) callbacks.error(xhr, status, error);
            }
        });
    },

    /**
     * Remove uma foto
     */
    removerFoto: function(foto_id, callbacks) {
        var self = this;

        if (!confirm('Tem certeza que deseja remover esta foto?')) {
            return;
        }

        var data = { foto_id: foto_id };

        // Adiciona CSRF token
        var csrf = self.getCsrfToken();
        if (csrf.name && csrf.value) {
            data[csrf.name] = csrf.value;
        }

        $.ajax({
            url: self.config.baseUrl + 'index.php/checkin/removerFoto',
            type: 'POST',
            data: data,
            dataType: 'json',
            beforeSend: function() {
                if (callbacks.beforeSend) callbacks.beforeSend();
            },
            success: function(response) {
                if (callbacks.success) callbacks.success(response);
            },
            error: function(xhr, status, error) {
                if (callbacks.error) callbacks.error(xhr, status, error);
            },
            complete: function() {
                if (callbacks.complete) callbacks.complete();
            }
        });
    },

    /**
     * Atualiza descrição da foto
     */
    atualizarDescricao: function(foto_id, descricao, callbacks) {
        var self = this;

        $.ajax({
            url: self.config.baseUrl + 'index.php/checkin/atualizarDescricao',
            type: 'POST',
            data: {
                foto_id: foto_id,
                descricao: descricao
            },
            dataType: 'json',
            success: function(response) {
                if (callbacks.success) callbacks.success(response);
            },
            error: function(xhr, status, error) {
                if (callbacks.error) callbacks.error(xhr, status, error);
            }
        });
    },

    /**
     * Envia dados de checkin/checkout com fotos em base64 mostrando progresso
     * Ideal para mobile onde mostramos o progresso de envio das fotos
     */
    enviarCheckinComProgresso: function(url, dados, callbacks) {
        var self = this;

        // Cria overlay de progresso
        var progresso = self.mostrarProgressoUpload('Iniciando...');
        var fotos = dados.fotos || [];
        var totalItens = fotos.length + 1; // Fotos + dados do checkin
        var itensProcessados = 0;

        // Simula progresso baseado no envio dos dados
        function atualizarProgresso(percent) {
            progresso.atualizar(percent);
        }

        // Atualiza mensagem conforme progresso
        progresso.definirMensagem('Enviando dados do atendimento...');

        // Usa XMLHttpRequest para ter mais controle
        var xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                var percent = (e.loaded / e.total) * 100;
                // Se tiver fotos, reserva 30% para elas no final
                if (fotos.length > 0) {
                    percent = percent * 0.7; // 70% para dados, 30% para fotos
                }
                atualizarProgresso(percent);
            }
        }, false);

        xhr.addEventListener('loadstart', function() {
            progresso.definirMensagem('Enviando dados...');
        }, false);

        xhr.addEventListener('load', function() {
            if (xhr.status === 200) {
                atualizarProgresso(100);
                setTimeout(function() {
                    progresso.fechar();
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (callbacks.success) callbacks.success(response);
                    } catch (e) {
                        if (callbacks.error) callbacks.error(xhr, 'parse_error', e);
                    }
                }, 500);
            } else {
                progresso.fechar();
                if (callbacks.error) callbacks.error(xhr, 'http_error', xhr.statusText);
            }
        }, false);

        xhr.addEventListener('error', function() {
            progresso.fechar();
            if (callbacks.error) callbacks.error(xhr, 'network_error', 'Erro de rede');
        }, false);

        xhr.open('POST', url);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        // Converte dados para formato de formulário
        var params = [];
        for (var key in dados) {
            if (key === 'fotos' && Array.isArray(dados[key])) {
                for (var i = 0; i < dados[key].length; i++) {
                    params.push(encodeURIComponent(key + '[]') + '=' + encodeURIComponent(dados[key][i]));
                }
            } else {
                params.push(encodeURIComponent(key) + '=' + encodeURIComponent(dados[key] || ''));
            }
        }

        // Adiciona CSRF
        var csrf = self.getCsrfToken();
        if (csrf.name && csrf.value) {
            params.push(encodeURIComponent(csrf.name) + '=' + encodeURIComponent(csrf.value));
        }

        xhr.send(params.join('&'));
    },

    /**
     * Obtém estatísticas
     */
    estatisticas: function(os_id, callbacks) {
        var self = this;

        $.ajax({
            url: self.config.baseUrl + 'index.php/checkin/estatisticasFotos',
            type: 'POST',
            data: { os_id: os_id },
            dataType: 'json',
            success: function(response) {
                if (callbacks.success) callbacks.success(response);
            },
            error: function(xhr, status, error) {
                if (callbacks.error) callbacks.error(xhr, status, error);
            }
        });
    },

    /**
     * Renderiza galeria de fotos
     */
    renderizarGaleria: function(fotos, container, opcoes) {
        var html = '';
        var op = $.extend({
            mostrarDescricao: true,
            mostrarExcluir: true,
            mostrarDownload: true,
            colunas: 3
        }, opcoes);

        if (!fotos || fotos.length === 0) {
            html = '<p class="text-muted">Nenhuma foto encontrada.</p>';
        } else {
            html += '<div class="row-fluid">';

            for (var i = 0; i < fotos.length; i++) {
                var foto = fotos[i];
                var span = 12 / op.colunas;

                // Usa URL correta - url_visualizacao para base64 ou url normal
                var imgUrl = foto.url_visualizacao || foto.url;
                // Fallback: construir URL do endpoint verFotoDB se tiver idFoto
                if (!imgUrl && foto.idFoto) {
                    imgUrl = this.config.baseUrl + 'index.php/checkin/verFotoDB/' + foto.idFoto;
                }

                html += '<div class="span' + span + ' text-center" style="margin-bottom: 15px;">';
                html += '<div class="thumbnail">';
                html += '<img src="' + imgUrl + '" style="max-height: 150px; cursor: pointer;" class="foto-preview" data-foto-id="' + foto.idFoto + '">';

                if (op.mostrarDescricao && foto.descricao) {
                    html += '<p class="muted" style="margin-top: 5px; font-size: 11px;">' + foto.descricao + '</p>';
                }

                html += '<div class="btn-group" style="margin-top: 5px;">';

                if (op.mostrarDownload) {
                    html += '<a href="' + this.config.baseUrl + 'index.php/checkin/downloadFoto/' + foto.idFoto + '" class="btn btn-mini btn-info" title="Download"><i class="bx bx-download"></i></a>';
                }

                if (op.mostrarExcluir) {
                    html += '<button class="btn btn-mini btn-danger btn-remover-foto" data-foto-id="' + foto.idFoto + '" title="Remover"><i class="bx bx-trash"></i></button>';
                }

                html += '</div>';
                html += '</div>';
                html += '</div>';
            }

            html += '</div>';
        }

        $(container).html(html);
    }
};
