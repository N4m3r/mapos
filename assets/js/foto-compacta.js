/*
 * Foto compactada para o app do tecnico (ADR-014). JS puro, sem dependencias.
 *
 * - input[type=file][data-foto-compacta]: cada imagem e reduzida com canvas
 *   (lado maior 1600 px, image/jpeg 0,8) e vira <input type="hidden" name="fotos[]">
 *   no proprio form, com miniatura em [data-foto-preview] e botao para remover.
 * - Maximo de 6 fotos por form.
 * - Enquanto comprime, os botoes de envio do form ficam desabilitados.
 * - form[data-exige-foto]: bloqueia o envio sem foto e mostra o texto do atributo
 *   em [data-foto-erro] (ou alert). Nao bloqueia se o form tiver data-ja-tem-foto="1".
 * - input[data-foto-nome="fotos_etiqueta[]"]: a foto vai nesse campo (foto da etiqueta)
 *   e conta para o limite e para data-exige-foto.
 * - form[data-exige-etiqueta]: bloqueia o envio sem foto da etiqueta, salvo
 *   data-ja-tem-etiqueta="1".
 */
(function () {
    'use strict';

    var MAX_FOTOS = 6;
    var LADO_MAX = 1600;
    var QUALIDADE = 0.8;
    var SELETOR_BOTOES = 'button:not([type="button"]):not([type="reset"]), input[type="submit"]';

    // Compressoes em andamento por form: [{ form: <form>, n: <int> }]
    var estados = [];

    function estadoDo(form) {
        for (var i = 0; i < estados.length; i++) {
            if (estados[i].form === form) {
                return estados[i];
            }
        }
        var estado = { form: form, n: 0 };
        estados.push(estado);
        return estado;
    }

    function totalFotos(form) {
        return form.querySelectorAll('input[type="hidden"][name="fotos[]"], input[type="hidden"][name="fotos_etiqueta[]"]').length;
    }

    function totalEtiquetas(form) {
        return form.querySelectorAll('input[type="hidden"][name="fotos_etiqueta[]"]').length;
    }

    function mostrarErro(form, mensagem) {
        var erro = form.querySelector('[data-foto-erro]');
        if (erro) {
            erro.textContent = mensagem;
            erro.style.display = mensagem ? 'block' : 'none';
        } else if (mensagem) {
            alert(mensagem);
        }
    }

    function atualizarBotoes(form) {
        var ocupado = estadoDo(form).n > 0;
        Array.prototype.forEach.call(form.querySelectorAll(SELETOR_BOTOES), function (botao) {
            if (ocupado) {
                if (botao.getAttribute('data-foto-desab') === null) {
                    botao.setAttribute('data-foto-desab', botao.disabled ? '1' : '0');
                }
                botao.disabled = true;
            } else if (botao.getAttribute('data-foto-desab') !== null) {
                botao.disabled = botao.getAttribute('data-foto-desab') === '1';
                botao.removeAttribute('data-foto-desab');
            }
        });
    }

    // Reduz a imagem: lado maior no maximo LADO_MAX, JPEG com QUALIDADE. Devolve data URL.
    function comprimir(arquivo) {
        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(arquivo);
            var img = new Image();
            img.onload = function () {
                try {
                    var w = img.naturalWidth || img.width;
                    var h = img.naturalHeight || img.height;
                    if (!w || !h) {
                        throw new Error('Imagem sem dimensões.');
                    }
                    var escala = Math.min(1, LADO_MAX / Math.max(w, h));
                    var canvas = document.createElement('canvas');
                    canvas.width = Math.round(w * escala);
                    canvas.height = Math.round(h * escala);
                    canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                    resolve(canvas.toDataURL('image/jpeg', QUALIDADE));
                } catch (e) {
                    reject(e);
                } finally {
                    URL.revokeObjectURL(url);
                }
            };
            img.onerror = function () {
                URL.revokeObjectURL(url);
                reject(new Error('Não foi possível ler a imagem.'));
            };
            img.src = url;
        });
    }

    function adicionarFoto(form, preview, dataUrl, nome) {
        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = nome || 'fotos[]';
        hidden.value = dataUrl;
        form.appendChild(hidden);

        if (!preview) {
            return;
        }
        var item = document.createElement('div');
        item.style.cssText = 'position:relative;width:70px;height:70px;';

        var img = document.createElement('img');
        img.src = dataUrl;
        img.alt = 'Foto';
        img.style.cssText = 'width:70px;height:70px;object-fit:cover;border-radius:8px;border:1px solid #ccc;';

        var remover = document.createElement('button');
        remover.type = 'button';
        remover.setAttribute('data-foto-remover', '');
        remover.setAttribute('aria-label', 'Remover foto');
        remover.textContent = '×';
        remover.style.cssText = 'position:absolute;top:-6px;right:-6px;width:24px;height:24px;border-radius:50%;border:0;background:#e74c3c;color:#fff;line-height:22px;padding:0;font-size:16px;';
        remover.addEventListener('click', function () {
            if (hidden.parentNode) {
                hidden.parentNode.removeChild(hidden);
            }
            if (item.parentNode) {
                item.parentNode.removeChild(item);
            }
            mostrarErro(form, '');
        });

        item.appendChild(img);
        item.appendChild(remover);
        preview.appendChild(item);
    }

    function ligarInput(input) {
        var form = input.form;
        if (!form) {
            return;
        }
        // Miniatura no bloco do proprio botao (label + [data-foto-preview] logo depois), senao a do form
        var rotulo = input.closest ? input.closest('label') : null;
        var vizinho = rotulo ? rotulo.nextElementSibling : null;
        var preview = (vizinho && vizinho.hasAttribute('data-foto-preview')) ? vizinho : form.querySelector('[data-foto-preview]');
        var nome = input.getAttribute('data-foto-nome') || 'fotos[]';

        input.addEventListener('change', function () {
            var arquivos = Array.prototype.slice.call(input.files || []);
            if (!arquivos.length) {
                return;
            }
            mostrarErro(form, '');

            var estado = estadoDo(form);
            estado.n++;
            atualizarBotoes(form);

            var limite = function () {
                mostrarErro(form, 'Máximo de ' + MAX_FOTOS + ' fotos por registro. As demais foram ignoradas.');
            };

            var cadeia = arquivos.reduce(function (anterior, arquivo) {
                return anterior.then(function () {
                    if (totalFotos(form) >= MAX_FOTOS) {
                        limite();
                        return;
                    }
                    return comprimir(arquivo).then(function (dataUrl) {
                        if (totalFotos(form) >= MAX_FOTOS) {
                            limite();
                            return;
                        }
                        adicionarFoto(form, preview, dataUrl, nome);
                    }, function () {
                        mostrarErro(form, 'Não foi possível usar esta imagem. Tente outra foto (JPEG ou PNG).');
                    });
                });
            }, Promise.resolve());

            var fim = function () {
                estado.n--;
                atualizarBotoes(form);
                input.value = '';
            };
            cadeia.then(fim, fim);
        });
    }

    function ligarForm(form) {
        form.addEventListener('submit', function (evento) {
            if (estadoDo(form).n > 0) {
                evento.preventDefault();
                mostrarErro(form, 'Aguarde: a foto ainda está sendo preparada.');
                return;
            }
            if (form.hasAttribute('data-exige-etiqueta') && form.getAttribute('data-ja-tem-etiqueta') !== '1'
                && totalEtiquetas(form) === 0) {
                evento.preventDefault();
                mostrarErro(form, form.getAttribute('data-exige-etiqueta') || 'Tire a foto da etiqueta.');
                return;
            }
            if (!form.hasAttribute('data-exige-foto') || form.getAttribute('data-ja-tem-foto') === '1') {
                return;
            }
            if (totalFotos(form) > 0) {
                return;
            }
            evento.preventDefault();
            mostrarErro(form, form.getAttribute('data-exige-foto') || 'Tire ao menos uma foto.');
        });
    }

    function iniciar() {
        var forms = [];
        var adicionarForm = function (form) {
            if (form && forms.indexOf(form) === -1) {
                forms.push(form);
            }
        };

        Array.prototype.forEach.call(document.querySelectorAll('input[type="file"][data-foto-compacta]'), function (input) {
            ligarInput(input);
            adicionarForm(input.form);
        });
        Array.prototype.forEach.call(document.querySelectorAll('form[data-exige-foto], form[data-exige-etiqueta]'), adicionarForm);

        forms.forEach(ligarForm);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
