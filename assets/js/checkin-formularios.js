/**
 * Formulários de Atendimento personalizados.
 *
 * Módulo desacoplado do checkin.js: carrega os formulários ativos de cada
 * etapa (iniciar / durante / finalizar) dentro dos modais de atendimento e
 * salva as respostas de forma independente, sem interferir no fluxo de
 * check-in/check-out existente. O token CSRF é injetado automaticamente
 * pelo $.ajaxSetup de csrf.js.
 */
window.CheckinFormularios = (function () {
    'use strict';

    var cfg = { baseUrl: '', osId: 0 };

    function init(options) {
        cfg.baseUrl = (options && options.baseUrl) || '';
        cfg.osId = (options && options.osId) || 0;
    }

    function url(metodo) {
        return cfg.baseUrl + 'index.php/formularios/' + metodo;
    }

    function esc(v) {
        return $('<div>').text(v == null ? '' : v).html();
    }

    /* -------------------------------------------------------------- */
    /* Renderização                                                    */
    /* -------------------------------------------------------------- */

    function renderCampo(campo) {
        var id = campo.id;
        var req = campo.obrigatorio ? ' <span style="color:#e5364b">*</span>' : '';
        var html = '<div class="campo-atendimento" data-campo-id="' + id + '" data-tipo="' + campo.tipo + '" data-obrigatorio="' + (campo.obrigatorio ? 1 : 0) + '" style="margin-bottom:10px">';
        html += '<label style="font-weight:600">' + esc(campo.label) + req + '</label>';

        var valor = campo.valor == null ? '' : String(campo.valor);
        var ph = campo.placeholder ? ' placeholder="' + esc(campo.placeholder) + '"' : '';
        var opcoes = Array.isArray(campo.opcoes) ? campo.opcoes : [];

        switch (campo.tipo) {
            case 'textarea':
                html += '<textarea class="span12 fa-input" rows="3"' + ph + '>' + esc(valor) + '</textarea>';
                break;
            case 'select':
                html += '<select class="span12 fa-input"><option value="">-- selecione --</option>';
                opcoes.forEach(function (o) {
                    html += '<option value="' + esc(o) + '"' + (valor === String(o) ? ' selected' : '') + '>' + esc(o) + '</option>';
                });
                html += '</select>';
                break;
            case 'sim_nao':
                html += '<select class="span12 fa-input"><option value="">--</option>';
                ['Sim', 'Não'].forEach(function (o) {
                    html += '<option value="' + o + '"' + (valor === o ? ' selected' : '') + '>' + o + '</option>';
                });
                html += '</select>';
                break;
            case 'radio':
                opcoes.forEach(function (o) {
                    html += '<label class="radio inline"><input type="radio" class="fa-input" name="fa_radio_' + id + '" value="' + esc(o) + '"' + (valor === String(o) ? ' checked' : '') + '> ' + esc(o) + '</label>';
                });
                break;
            case 'checkbox':
                var marcados = valor ? valor.split(',').map(function (s) { return s.trim(); }) : [];
                opcoes.forEach(function (o) {
                    var chk = marcados.indexOf(String(o)) !== -1 ? ' checked' : '';
                    html += '<label class="checkbox inline"><input type="checkbox" class="fa-input" value="' + esc(o) + '"' + chk + '> ' + esc(o) + '</label>';
                });
                break;
            case 'number':
                html += '<input type="number" class="span12 fa-input"' + ph + ' value="' + esc(valor) + '">';
                break;
            case 'date':
                html += '<input type="date" class="span12 fa-input" value="' + esc(valor) + '">';
                break;
            case 'time':
                html += '<input type="time" class="span12 fa-input" value="' + esc(valor) + '">';
                break;
            case 'email':
                html += '<input type="email" class="span12 fa-input"' + ph + ' value="' + esc(valor) + '">';
                break;
            case 'tel':
                html += '<input type="tel" class="span12 fa-input"' + ph + ' value="' + esc(valor) + '">';
                break;
            default: // texto
                html += '<input type="text" class="span12 fa-input"' + ph + ' value="' + esc(valor) + '">';
        }

        if (campo.ajuda) {
            html += '<small class="text-muted" style="display:block">' + esc(campo.ajuda) + '</small>';
        }
        html += '</div>';
        return html;
    }

    function renderForm(f) {
        var html = '<div class="form-atendimento" data-form-id="' + f.id + '" data-obrigatorio="' + (f.obrigatorio ? 1 : 0) + '" style="border:1px solid #e2e5ef;border-radius:6px;padding:12px;margin-bottom:12px">';
        html += '<h6 style="margin:0 0 6px;font-weight:700"><i class="bx bx-clipboard"></i> ' + esc(f.nome) + '</h6>';
        if (f.descricao) {
            html += '<p class="text-muted" style="margin:0 0 8px">' + esc(f.descricao) + '</p>';
        }
        f.campos.forEach(function (c) { html += renderCampo(c); });
        html += '</div>';
        return html;
    }

    /* -------------------------------------------------------------- */
    /* Carregar / Salvar                                               */
    /* -------------------------------------------------------------- */

    function carregar(etapa, container, osId, cb) {
        var $c = $(container);
        if (!$c.length) { if (cb) cb(); return; }
        osId = osId || cfg.osId;
        $c.html('<p class="text-muted" style="margin:6px 0">Carregando formulários...</p>');

        $.ajax({ url: url('porEtapa'), method: 'POST', dataType: 'json', data: { etapa: etapa, os_id: osId } })
            .done(function (resp) {
                if (!resp || !resp.success || !resp.formularios || !resp.formularios.length) {
                    $c.empty();
                    if (cb) cb();
                    return;
                }
                var html = '<div class="formularios-atendimento-bloco"><hr style="margin:8px 0">'
                    + '<h5 style="margin:0 0 8px"><i class="bx bx-list-check"></i> Formulários</h5>';
                resp.formularios.forEach(function (f) { html += renderForm(f); });
                html += '</div>';
                $c.html(html);
                if (cb) cb();
            })
            .fail(function () { $c.empty(); if (cb) cb(); });
    }

    function coletarValores($form) {
        var valores = {};
        $form.find('.campo-atendimento').each(function () {
            var $campo = $(this);
            var id = $campo.data('campo-id');
            var tipo = $campo.data('tipo');

            if (tipo === 'checkbox') {
                var arr = [];
                $campo.find('input[type=checkbox].fa-input:checked').each(function () { arr.push($(this).val()); });
                valores[id] = arr;
            } else if (tipo === 'radio') {
                valores[id] = $campo.find('input[type=radio].fa-input:checked').val() || '';
            } else {
                valores[id] = $campo.find('.fa-input').val() || '';
            }
        });
        return valores;
    }

    function validarObrigatorios($container) {
        var faltando = null;
        $container.find('.campo-atendimento[data-obrigatorio="1"]').each(function () {
            if (faltando) { return; }
            var $campo = $(this);
            var tipo = $campo.data('tipo');
            var preenchido;
            if (tipo === 'checkbox') {
                preenchido = $campo.find('input.fa-input:checked').length > 0;
            } else if (tipo === 'radio') {
                preenchido = $campo.find('input.fa-input:checked').length > 0;
            } else {
                preenchido = String($campo.find('.fa-input').val() || '').trim() !== '';
            }
            if (!preenchido) { faltando = $campo.find('label').first().text().replace('*', '').trim(); }
        });
        return faltando;
    }

    /**
     * Salva todas as respostas dentro do container. Retorna uma Promise
     * ($.when). Se algum campo obrigatório estiver vazio, rejeita cedo.
     */
    function salvar(etapa, container, osId, checkinId) {
        var $c = $(container);
        var dfd = $.Deferred();
        if (!$c.length || !$c.find('.form-atendimento').length) {
            return dfd.resolve().promise();
        }

        var faltando = validarObrigatorios($c);
        if (faltando) {
            return dfd.reject('Preencha o campo obrigatório: ' + faltando).promise();
        }

        osId = osId || cfg.osId;
        var posts = [];
        $c.find('.form-atendimento').each(function () {
            var $form = $(this);
            posts.push($.ajax({
                url: url('salvarResposta'),
                method: 'POST',
                dataType: 'json',
                data: {
                    formulario_id: $form.data('form-id'),
                    os_id: osId,
                    etapa: etapa,
                    checkin_id: checkinId || '',
                    valores: coletarValores($form)
                }
            }));
        });

        $.when.apply($, posts)
            .done(function () { dfd.resolve(); })
            .fail(function () { dfd.reject('Não foi possível salvar as respostas dos formulários.'); });

        return dfd.promise();
    }

    return { init: init, carregar: carregar, salvar: salvar };
})();
