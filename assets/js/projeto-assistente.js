/* Assistente "Novo projeto" (ADR-016): 3 passos numa pagina, sem recarregar. */
(function ($) {
    'use strict';
    var passo = 1;
    var tipoAtual = '';
    var tipoCarregado = null;
    var etapasMarcadas = null; // preserva escolhas ao voltar

    function esc(t) { return $('<div>').text(t == null ? '' : t).html(); }

    /* ---------- datas e cliente ---------- */
    $(function () { $('.datepicker').datepicker({ dateFormat: 'dd/mm/yy' }); });

    $(function () {
        var $busca = $('#cliente_busca'), $id = $('#clientes_id'), $limpar = $('#cliente_limpar');
        function limpar() { $id.val(''); $busca.val(''); $limpar.hide(); }
        $busca.autocomplete({
            minLength: 0, delay: 100,
            source: function (req, resp) {
                var t = (req.term || '').toLowerCase().replace(/[.\-\/\s]/g, '');
                var n = (req.term || '').toLowerCase();
                resp(PA_CLIENTES.filter(function (c) {
                    var d = (c.doc || '').toLowerCase().replace(/[.\-\/\s]/g, '');
                    return c.nome.toLowerCase().indexOf(n) !== -1 || (t !== '' && d.indexOf(t) !== -1);
                }).slice(0, 20));
            },
            focus: function (e, ui) { $busca.val(ui.item.label); return false; },
            select: function (e, ui) { $busca.val(ui.item.label); $id.val(ui.item.id); $limpar.show(); return false; }
        }).on('focus', function () { $(this).autocomplete('search', $(this).val()); });
        $busca.on('input', function () {
            if ($.trim($(this).val()) === '') { limpar(); } else { $id.val(''); $limpar.show(); }
        });
        $limpar.on('click', function (e) { e.preventDefault(); limpar(); $busca.focus(); });
    });

    /* ---------- tipo ---------- */
    function tipoValor() {
        var $b = $('.pa-tipo.sel');
        if (!$b.length) { return ''; }
        return $b.data('tipo') === 'outro' ? $.trim($('#tipo_outro').val()) : String($b.data('valor'));
    }
    $('.pa-tipo').on('click', function () {
        $('.pa-tipo').removeClass('sel').attr('aria-checked', 'false');
        $(this).addClass('sel').attr('aria-checked', 'true');
        tipoAtual = String($(this).data('tipo'));
        $('#paOutro').toggle(tipoAtual === 'outro');
        if (tipoAtual === 'outro') { $('#tipo_outro').focus(); }
        $('[data-erro="tipo"]').removeClass('on');
    });

    $('#nome').on('input', function () { $('[data-erro="nome"]').removeClass('on'); });
    $('#tipo_outro').on('input', function () { $('[data-erro="tipo"]').removeClass('on'); });
    $('#data_inicio_prevista, #data_fim_prevista').on('change', function () { $('[data-erro="datas"]').removeClass('on'); });

    /* ---------- passo 2: etapas ---------- */
    function itemEtapa(nome, marcado) {
        return '<li><label><input type="checkbox" name="etapas[]" value="' + esc(nome).replace(/"/g, '&quot;') + '"' + (marcado ? ' checked' : '') + '> <span>' + esc(nome) + '</span></label></li>';
    }
    function carregarEtapas() {
        var v = tipoValor();
        if (tipoCarregado === v) { return; }
        tipoCarregado = v;
        var $ul = $('#paEtapas').html('<li class="pa-dica">Carregando etapas...</li>');
        $.getJSON(PA_URL_ETAPAS, { tipo: v }).done(function (r) {
            var h = '';
            $.each(r.etapas || [], function (i, n) { h += itemEtapa(n, true); });
            $ul.html(h || '<li class="pa-dica">Nenhuma etapa sugerida. Adicione abaixo.</li>');
        }).fail(function () {
            $ul.html('<li class="pa-dica">Não foi possível carregar as etapas sugeridas. Adicione as suas abaixo.</li>');
        });
    }
    function addEtapa() {
        var n = $.trim($('#paEtapaExtra').val());
        if (!n) { return; }
        var dup = false;
        $('#paEtapas input').each(function () { if ($(this).val().toLowerCase() === n.toLowerCase()) { dup = true; $(this).prop('checked', true); } });
        if (!dup) { $('#paEtapas').find('.pa-dica').remove(); $('#paEtapas').append(itemEtapa(n, true)); }
        $('#paEtapaExtra').val('').focus();
    }
    $('#paEtapaAdd').on('click', addEtapa);
    $('#paEtapaExtra').on('keydown', function (e) { if (e.which === 13) { e.preventDefault(); addEtapa(); } });

    /* ---------- passo 2: pontos ---------- */
    function stepper(tipo) {
        var p = PA_PONTOS[tipo];
        return '<div class="pa-ponto" data-tipo="' + tipo + '"><i class="bx ' + p.icone + '"></i><span class="pa-ponto-rot">' + esc(p.rotulo) + '</span>' +
            '<span class="pa-step"><button type="button" class="pa-menos" aria-label="Menos ' + esc(p.rotulo) + '">&minus;</button>' +
            '<input type="number" min="0" max="500" value="0" name="pontos[' + tipo + ']" aria-label="Quantidade de ' + esc(p.rotulo) + '">' +
            '<button type="button" class="pa-mais" aria-label="Mais ' + esc(p.rotulo) + '">+</button></span></div>';
    }
    var tipoPontosMontado = null;
    function montarPontos() {
        var chave = tipoAtual;
        if (tipoPontosMontado === chave) { return; }
        tipoPontosMontado = chave;
        var sug = PA_SUGERIDOS[chave] || PA_SUGERIDOS.outro, h = '', hm = '';
        $.each(sug, function (i, t) { h += stepper(t); });
        $.each(PA_PONTOS, function (t) { if (sug.indexOf(t) === -1) { hm += stepper(t); } });
        $('#paPontos').html(h);
        $('#paPontosMais').html(hm);
    }
    $(document).on('click', '.pa-menos, .pa-mais', function () {
        var $i = $(this).siblings('input'), v = parseInt($i.val(), 10) || 0;
        v += $(this).hasClass('pa-mais') ? 1 : -1;
        $i.val(Math.max(0, Math.min(500, v))).trigger('change');
    });
    $(document).on('change input', '.pa-step input', function () {
        var v = parseInt($(this).val(), 10);
        if (isNaN(v) || v < 0) { v = 0; }
        if (v > 500) { v = 500; }
        if (String(v) !== $(this).val() && $(this).val() !== '') { $(this).val(v); }
        $(this).closest('.pa-ponto').toggleClass('tem', v > 0);
    });
    $('#paMais').on('click', function () {
        var aberto = $(this).attr('aria-expanded') === 'true';
        $(this).attr('aria-expanded', String(!aberto)).toggleClass('aberto', !aberto);
        $('#paPontosMais').prop('hidden', aberto);
    });
    $('#paDetalhesBt').on('click', function () {
        var aberto = $(this).attr('aria-expanded') === 'true';
        $(this).attr('aria-expanded', String(!aberto)).toggleClass('aberto', !aberto);
        $('#paDetalhes').prop('hidden', aberto);
    });

    /* ---------- passo 3: resumo ---------- */
    function resumo() {
        var nEt = $('#paEtapas input:checked').length, nPt = 0, partes = [];
        $('.pa-step input').each(function () {
            var v = parseInt($(this).val(), 10) || 0;
            if (v > 0) { nPt += v; partes.push(v + ' ' + PA_PONTOS[$(this).closest('.pa-ponto').data('tipo')].rotulo); }
        });
        var nTec = $('input[name="tecnicos[]"]:checked').length;
        var h = '<h3>Resumo</h3><dl>' +
            '<dt>Projeto</dt><dd>' + esc($.trim($('#nome').val())) + '</dd>' +
            '<dt>Cliente</dt><dd>' + esc($.trim($('#cliente_busca').val()) || 'Não informado') + '</dd>' +
            '<dt>Tipo</dt><dd>' + esc(tipoValor()) + '</dd>' +
            '<dt>Etapas</dt><dd>' + nEt + '</dd>' +
            '<dt>Pontos</dt><dd>' + (nPt ? nPt + ' (' + esc(partes.join(', ')) + ')' : 'Nenhum') + '</dd>' +
            '<dt>Técnicos</dt><dd>' + nTec + '</dd></dl>';
        $('#paResumo').html(h);
    }

    $(document).on('change', 'input[name="tecnicos[]"], #nome, #cliente_busca', function () { if (passo === 3) { resumo(); } });

    /* ---------- navegacao e validacao ---------- */
    function parseData(s) {
        var m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec($.trim(s));
        return m ? new Date(+m[3], +m[2] - 1, +m[1]).getTime() : null;
    }
    function validar(n) {
        var ok = true;
        $('.pa-erro').removeClass('on');
        if (n === 1) {
            if (!$.trim($('#nome').val())) { $('[data-erro="nome"]').addClass('on'); $('#nome').focus(); ok = false; }
            if (!tipoValor()) {
                $('[data-erro="tipo"]').text(tipoAtual === 'outro' ? 'Informe qual é o tipo.' : 'Escolha o tipo do projeto.').addClass('on');
                if (ok) { (tipoAtual === 'outro' ? $('#tipo_outro') : $('.pa-tipo').first()).focus(); }
                ok = false;
            }
        }
        if (n === 3) {
            var a = parseData($('#data_inicio_prevista').val()), b = parseData($('#data_fim_prevista').val());
            if (a && b && b < a) { $('[data-erro="datas"]').addClass('on'); $('#data_fim_prevista').focus(); ok = false; }
        }
        return ok;
    }
    function ir(n) {
        passo = n;
        $('.pa-painel').each(function () { $(this).prop('hidden', String($(this).data('painel')) !== String(n)); });
        $('#paPassos li').each(function () {
            var p = +$(this).data('passo');
            $(this).toggleClass('ativo', p === n).toggleClass('feito', p < n).attr('aria-current', p === n ? 'step' : null);
        });
        $('#paVoltar').prop('hidden', n === 1);
        $('#paAvancar').prop('hidden', n === 3);
        $('#paCriar').prop('hidden', n !== 3);
        $('#paInfo').text('Passo ' + n + ' de 3');
        if (n === 2) { carregarEtapas(); montarPontos(); }
        if (n === 3) { resumo(); }
        $('html, body').animate({ scrollTop: $('#paPassos').offset().top - 70 }, 150);
    }
    $('#paAvancar').on('click', function () {
        if (!validar(passo)) { return; }
        $('#tipo_obra').val(tipoValor());
        ir(passo + 1);
    });
    $('#paVoltar').on('click', function () { if (passo > 1) { ir(passo - 1); } });
    $('#paPassos li').on('click', function () {
        var alvo = +$(this).data('passo');
        if (alvo < passo) { ir(alvo); }
    });
    $('#formObra').on('keydown', function (e) {
        if (e.which === 13 && e.target.tagName === 'INPUT' && e.target.type !== 'submit') {
            e.preventDefault();
            if (passo < 3) { $('#paAvancar').click(); }
        }
    });
    $('#formObra').on('submit', function (e) {
        if (!validar(1)) { e.preventDefault(); ir(1); return; }
        if (!validar(3)) { e.preventDefault(); ir(3); return; }
        $('#tipo_obra').val(tipoValor());
        if ($.trim($('#cliente_busca').val()) === '') { $('#clientes_id').val(''); }
        // etapas ainda nao carregadas (usuario pulou do 1 ao 3 nao e possivel); nada a fazer
        $('#paCriar').prop('disabled', true);
    });
})(jQuery);
