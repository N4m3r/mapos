(function () {
    var $modal = $('#modal-gerar-pagamento');
    if (!$modal.length) { return; }

    var totalTxt = '';
    var resumo = null;
    var qtdCobrancas = 1;

    function esc(t) { return $('<div>').text(t == null ? '' : t).html(); }
    function brl(v) { return 'R$ ' + Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    function atualizarBotao() {
        var ok = $('#gateway_de_pagamento').val() && $('#forma_pagamento').val();
        var temItem = $('#cb-resumo .cb-chk:checked').length > 0;
        $('#payment').prop('disabled', !(ok && temItem));
        $('#payment-texto').text(qtdCobrancas > 1
            ? 'Gerar ' + qtdCobrancas + ' cobranças (total ' + totalTxt + ')'
            : 'Gerar cobrança' + (totalTxt ? ' de ' + totalTxt : ''));
    }

    function escolherForma(valor) {
        $('#forma_pagamento').val(valor);
        $('#cb-formas .cb-opcao').each(function () {
            $(this).toggleClass('ativa', $(this).data('valor') === valor);
        });
        atualizarBotao();
    }

    function escolherGateway(chave) {
        $('#gateway_de_pagamento').val(chave);
        $('#forma_pagamento').val('');
        $('#cb-gateways .cb-opcao').each(function () {
            $(this).toggleClass('ativa', $(this).data('valor') === chave);
        });
        var $f = $('#cb-formas').empty();
        var aviso = (paymentGatewaysConfig[chave] || {}).aviso || '';
        $('#cb-aviso-gateway').text(aviso).toggle(aviso !== '');
        var metodos = (paymentGatewaysConfig[chave] || {}).payment_methods || [];
        metodos.forEach(function (m) {
            $('<label class="cb-opcao" tabindex="0">').data('valor', m.value).text(m.name).appendTo($f);
        });
        if (metodos.length === 1) { escolherForma(metodos[0].value); }
        atualizarBotao();
    }

    function montarGateways() {
        var chaves = Object.keys(paymentGatewaysConfig || {});
        var $g = $('#cb-gateways').empty();
        if (!chaves.length) {
            $g.html('<em>Nenhum gateway de pagamento configurado.</em>');
            return;
        }
        chaves.forEach(function (c) {
            $('<label class="cb-opcao" tabindex="0">').data('valor', c).text(paymentGatewaysConfig[c].name).appendTo($g);
        });
        if (chaves.length === 1) { escolherGateway(chaves[0]); }
    }

    // Modos de cobrança: quais itens vêm marcados e se gera uma ou várias cobranças.
    var MODOS = {
        tudo: { titulo: 'Tudo junto', desc: 'Serviços e produtos numa cobrança só' },
        servicos: { titulo: 'Só os serviços', desc: 'Uma cobrança só com os serviços' },
        produtos: { titulo: 'Só os produtos', desc: 'Uma cobrança só com os produtos' },
        separado: { titulo: 'Cada item separado', desc: 'Uma cobrança para cada serviço/produto' }
    };

    function livres(tipo) {
        return (resumo.itens || []).filter(function (i) { return !i.cobrado && (!tipo || i.tipo === tipo); });
    }

    function escolherModo(modo) {
        $('#cb-modos .cb-opcao').each(function () { $(this).toggleClass('ativa', $(this).data('valor') === modo); });
        $('#cb-agrupar').val(modo === 'separado' ? 'separado' : 'junto');
        $('#cb-resumo .cb-chk:not(:disabled)').each(function () {
            var tipo = $(this).data('tipo');
            $(this).prop('checked', modo === 'tudo' || modo === 'separado'
                || (modo === 'servicos' && tipo === 'servico') || (modo === 'produtos' && tipo === 'produto'));
        });
        recalcular();
    }

    function recalcular() {
        var bruto = (resumo.subtotal_produtos || 0) + (resumo.subtotal_servicos || 0) + (resumo.outros || 0);
        var fracDesc = bruto > 0 ? (resumo.desconto || 0) / bruto : 0;
        var separado = $('#cb-agrupar').val() === 'separado';
        var soma = 0, lotes = [];
        $('#cb-resumo .cb-chk:checked').each(function () {
            var v = Number($(this).data('valor')) || 0;
            soma += v;
            lotes.push(esc($(this).data('nome')) + ' ' + brl(v * (1 - fracDesc)));
        });
        var desconto = Math.round(soma * fracDesc * 100) / 100;
        var total = Math.round((soma - desconto) * 100) / 100;

        var h = '';
        if (desconto > 0) { h += '<div style="text-align:right">Desconto: - ' + brl(desconto) + '</div>'; }
        h += '<div class="cb-total">Total: ' + brl(total) + '</div>';
        if (separado && lotes.length > 1) {
            h += '<div class="cb-lotes"><strong>' + lotes.length + ' cobranças separadas:</strong> ' + lotes.join(' &middot; ') + '</div>';
        }
        var imp = resumo.impostos || {};
        if ((imp.itens || []).length && total > 0) {
            var das = 0;
            h += '<div class="cb-imp"><strong>Impostos estimados (informativo)</strong><br>';
            imp.itens.forEach(function (i) {
                var v = Math.round(total * i.percentual) / 100;
                if (i.rotulo.indexOf('DAS') === 0) { das += v; }
                h += esc(i.rotulo) + ' (' + i.percentual + '%): ' + brl(v) + ' <small>- ' + esc(i.nota) + '</small><br>';
            });
            h += 'Líquido estimado: <strong>' + brl(total - das) + '</strong><br><small>' + esc(imp.aviso) + '</small></div>';
        }
        var cred = imp.credito_cliente;
        if (cred && total > 0) {
            h += '<div class="cb-credito" style="margin-top:6px"><strong>Crédito para o cliente empresa:</strong> ' + esc(cred.texto);
            if (cred.percentual !== null && cred.percentual !== undefined) {
                h += ' Valor estimado: <strong>' + brl(total * Number(cred.percentual) / 100) + '</strong> (' + Number(cred.percentual) + '%).';
            }
            h += '</div>';
        }
        $('#cb-totais').html(h);
        totalTxt = brl(total);
        qtdCobrancas = separado ? lotes.length : 1;
        atualizarBotao();
    }

    function renderResumo(r) {
        resumo = r;
        var h = '<div><strong>' + esc(r.cliente) + '</strong> &middot; ' + esc(r.referencia) + '</div>';
        if (r.tudo_cobrado) {
            $('#cb-resumo').html(h + '<div class="alert alert-info" style="margin-top:8px">Tudo desta ' + esc(r.referencia) + ' já foi cobrado.</div>');
            totalTxt = '';
            qtdCobrancas = 1;
            atualizarBotao();
            return;
        }

        var modos = ['tudo'];
        if (livres('servico').length && livres('produto').length) { modos.push('servicos', 'produtos'); }
        if (livres().length > 1) { modos.push('separado'); }
        h += '<div id="cb-modos" class="cb-opcoes" style="margin:8px 0">';
        modos.forEach(function (m) {
            h += '<label class="cb-opcao cb-modo" tabindex="0" data-valor="' + m + '">' + MODOS[m].titulo + '<small>' + MODOS[m].desc + '</small></label>';
        });
        h += '</div><table class="cb-itens">';
        [['servico', 'Serviços'], ['produto', 'Produtos']].forEach(function (g) {
            var itens = (r.itens || []).filter(function (i) { return i.tipo === g[0]; });
            if (!itens.length) { return; }
            h += '<tr class="cb-grupo"><td colspan="3">' + g[1] + '</td></tr>';
            itens.forEach(function (i) {
                h += '<tr class="cb-item' + (i.cobrado ? ' cobrado' : '') + '"><td><label>'
                    + '<input type="checkbox" class="cb-chk" name="itens[]" value="' + esc(i.chave) + '" data-tipo="' + esc(i.tipo) + '"'
                    + ' data-valor="' + Number(i.subtotal) + '" data-nome="' + esc(i.descricao) + '"' + (i.cobrado ? ' disabled' : '') + '> '
                    + esc(i.descricao) + (i.cobrado ? ' <small>(já cobrado)</small>' : '') + '</label></td>'
                    + '<td style="text-align:right">' + i.qtd + ' x ' + brl(i.unitario) + '</td>'
                    + '<td style="text-align:right">' + brl(i.subtotal) + '</td></tr>';
            });
        });
        h += '</table><div id="cb-totais"></div>';
        $('#cb-resumo').html(h);
        escolherModo('tudo');
    }

    function carregarResumo() {
        $.getJSON($modal.data('resumo-url'))
            .done(renderResumo)
            .fail(function (xhr) {
                var m = (xhr.responseJSON && xhr.responseJSON.message) || 'Não foi possível carregar o resumo.';
                $('#cb-resumo').text(m);
            });
    }

    $modal.on('click keydown', '.cb-opcao', function (e) {
        if (e.type === 'keydown' && e.which !== 13 && e.which !== 32) { return; }
        e.preventDefault();
        var $o = $(this);
        if ($o.closest('#cb-modos').length) {
            escolherModo($o.data('valor'));
        } else if ($o.closest('#cb-gateways').length) {
            escolherGateway($o.data('valor'));
        } else {
            escolherForma($o.data('valor'));
        }
    });

    // Marcar/desmarcar um item à mão mantém o agrupamento escolhido.
    $modal.on('change', '.cb-chk', recalcular);

    $modal.on('show.bs.modal shown', function () {
        montarGateways();
        carregarResumo();
    });

    $('#form-gerar-cobranca').submit(function (e) {
        e.preventDefault();

        $modal.modal('hide');

        var form = $(this);
        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: form.serialize(),
            beforeSend: function () {
                swal({
                    title: 'Processando',
                    text: 'Criando cobrança...',
                    icon: 'info',
                    showCloseButton: false,
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false
                });
            },
            success: function (response) {
                var varias = response.total_cobrancas > 1;
                swal('Sucesso!', varias ? response.total_cobrancas + ' cobranças criadas com sucesso!' : 'Cobrança criada com sucesso!', 'success');
                setTimeout(function () {
                    window.location.href = window.BaseUrl + 'index.php/cobrancas/'
                        + (varias ? 'cobrancas' : 'visualizar/' + response.idCobranca);
                }, 2500);
            },
            error: function (response) {
                var message = (response.responseJSON && response.responseJSON.message) || 'Erro ao criar cobrança!';
                swal('Erro!', message, 'error');
            }
        });
    });

    // Abertura direta vinda da lista "A cobrar" (?cobrar=1 na OS/venda).
    if (/[?&]cobrar=1/.test(window.location.search)) {
        $(function () { $modal.modal('show'); });
    }
})();
