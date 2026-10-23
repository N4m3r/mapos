/**
 * Rascunho automatico de formularios no navegador (localStorage) - ADR-003.
 *
 * Evita perder o que foi digitado se a pagina fechar ou a conexao cair.
 * O rascunho fica so neste navegador e expira em 7 dias. Nunca restaura
 * sozinho um formulario inteiro: mostra um aviso com "Restaurar" / "Descartar".
 *
 * Uso:
 *   Rascunho.formulario('#formOs', 'os_nova_5', { editores: '.editor' });
 *   Rascunho.campo('#checkout-observacao', 'checkout_obs_12');   // um campo so
 *   Rascunho.apagar('checkout_obs_12');
 */
(function (window, $) {
    'use strict';

    var PREFIXO = 'mapos_rascunho_';
    var VALIDADE_MS = 7 * 24 * 60 * 60 * 1000;

    function ler(chave) {
        try {
            var bruto = window.localStorage.getItem(PREFIXO + chave);
            if (!bruto) {
                return null;
            }
            var item = JSON.parse(bruto);
            if (!item || !item.em || (Date.now() - item.em) > VALIDADE_MS) {
                window.localStorage.removeItem(PREFIXO + chave);
                return null;
            }
            return item;
        } catch (e) {
            return null;
        }
    }

    function salvar(chave, dados) {
        try {
            window.localStorage.setItem(PREFIXO + chave, JSON.stringify({ em: Date.now(), dados: dados }));
        } catch (e) {
            // Sem localStorage (modo privado, cota cheia): segue sem rascunho
        }
    }

    function apagar(chave) {
        try {
            window.localStorage.removeItem(PREFIXO + chave);
        } catch (e) {
            // ignora
        }
    }

    function adiar(fn, ms) {
        var t;
        return function () {
            clearTimeout(t);
            t = setTimeout(fn, ms);
        };
    }

    function dataHora(ms) {
        var d = new Date(ms);
        function p(n) { return (n < 10 ? '0' : '') + n; }
        return p(d.getDate()) + '/' + p(d.getMonth() + 1) + ' as ' + p(d.getHours()) + ':' + p(d.getMinutes());
    }

    // Campos que nunca entram no rascunho
    function ignorar(el) {
        var nome = el.name || '';
        var tipo = (el.type || '').toLowerCase();
        var csrf = $('meta[name="csrf-token-name"]').attr('content') || 'MAPOS_CSRF_TOKEN';
        return !nome || nome === csrf || tipo === 'file' || tipo === 'password' || tipo === 'submit' || tipo === 'button';
    }

    function coletar($form) {
        var dados = {};
        $form.find('input, select, textarea').each(function () {
            if (ignorar(this)) {
                return;
            }
            if (this.type === 'checkbox' || this.type === 'radio') {
                dados[this.name + '::' + this.value] = this.checked;
            } else {
                dados[this.name] = $(this).val();
            }
        });
        return dados;
    }

    function aplicar($form, dados, editores) {
        $form.find('input, select, textarea').each(function () {
            if (ignorar(this)) {
                return;
            }
            if (this.type === 'checkbox' || this.type === 'radio') {
                var k = this.name + '::' + this.value;
                if (Object.prototype.hasOwnProperty.call(dados, k)) {
                    this.checked = !!dados[k];
                }
            } else if (Object.prototype.hasOwnProperty.call(dados, this.name)) {
                $(this).val(dados[this.name]);
            }
        });
        // Editores Trumbowyg mostram o conteudo pelo proprio editor, nao pelo textarea
        if (editores && $.fn.trumbowyg) {
            $form.find(editores).each(function () {
                if (Object.prototype.hasOwnProperty.call(dados, this.name)) {
                    $(this).trumbowyg('html', dados[this.name] || '');
                }
            });
        }
    }

    function formulario(seletor, chave, opcoes) {
        opcoes = opcoes || {};
        var $form = $(seletor);
        if (!$form.length || !chave) {
            return;
        }

        var estadoInicial = JSON.stringify(coletar($form));
        var gravar = adiar(function () {
            var atual = coletar($form);
            // So guarda se o usuario mudou algo em relacao ao que a pagina carregou
            if (JSON.stringify(atual) !== estadoInicial) {
                salvar(chave, atual);
            }
        }, 800);

        $form.on('input change keyup', 'input, select, textarea', gravar);
        if (opcoes.editores) {
            $form.on('tbwchange tbwblur', opcoes.editores, gravar);
        }
        // Se a validacao (jquery.validate) barrar o envio, o rascunho continua
        $form.on('submit', function (e) {
            if (!e.isDefaultPrevented()) {
                apagar(chave);
            }
        });

        var existente = ler(chave);
        if (!existente || JSON.stringify(existente.dados) === estadoInicial) {
            return;
        }

        var $aviso = $(
            '<div class="alert alert-info rascunho-aviso" style="margin-bottom:10px">' +
            '<i class="bx bx-save"></i> Existe um rascunho nao salvo deste formulario de <strong></strong>. ' +
            '<a href="#" class="btn btn-mini btn-primary rascunho-restaurar" style="margin-left:8px">Restaurar</a> ' +
            '<a href="#" class="btn btn-mini rascunho-descartar">Descartar</a>' +
            '</div>'
        );
        $aviso.find('strong').text(dataHora(existente.em));
        $aviso.on('click', '.rascunho-restaurar', function (e) {
            e.preventDefault();
            aplicar($form, existente.dados, opcoes.editores);
            $aviso.remove();
        });
        $aviso.on('click', '.rascunho-descartar', function (e) {
            e.preventDefault();
            apagar(chave);
            $aviso.remove();
        });
        $form.prepend($aviso);
    }

    // Um campo isolado (sem <form>): restaura so se o campo estiver vazio
    function campo(seletor, chave) {
        var $campo = $(seletor);
        if (!$campo.length || !chave) {
            return;
        }
        var existente = ler(chave);
        if (existente && !$campo.val()) {
            $campo.val(existente.dados);
        }
        $campo.off('.rascunho').on('input.rascunho', adiar(function () {
            var v = $campo.val();
            if (v) {
                salvar(chave, v);
            } else {
                apagar(chave);
            }
        }, 600));
    }

    window.Rascunho = { formulario: formulario, campo: campo, apagar: apagar };
})(window, jQuery);
