/**
 * Comportamentos da Central de Notificacoes (Eventos e Canais).
 *
 * Sem handlers inline (CSP): os botoes de teste carregam a URL em data-url e
 * este arquivo cuida do envio. Na tela do evento, liga o autocomplete de
 * clientes/projetos, os chips e o carregamento dos grupos do WhatsApp.
 */
(function (window, $) {
    'use strict';

    if (!$) {
        return;
    }

    function saida($el, html, ok) {
        $el.html('<span style="color:' + (ok ? 'var(--ui-sucesso)' : 'var(--ui-perigo)') + '">' + html + '</span>');
    }

    function escapa(t) {
        return $('<div>').text(t == null ? '' : t).html();
    }

    function ajax(url, dados, $out, $botoes) {
        $botoes.prop('disabled', true);
        saida($out, '<i class="bx bx-loader-alt bx-spin"></i> Enviando...', true);

        $.ajax({ url: url, type: 'POST', dataType: 'json', data: dados || {} })
            .done(function (d) {
                saida($out, escapa(d && d.mensagem ? d.mensagem : 'Concluído.'), !!(d && d.result));
            })
            .fail(function (xhr) {
                var msg = 'Falha na operação.';
                try {
                    msg = JSON.parse(xhr.responseText).mensagem || msg;
                } catch (e) {
                    msg = 'Falha (' + xhr.status + ').';
                }
                saida($out, escapa(msg), false);
            })
            .always(function () {
                $botoes.prop('disabled', false);
            });
    }

    /* ---------------- Testes da aba Canais ---------------- */

    function iniciarTestes() {
        $('#nt_testar_wpp, #nt_enviar_wpp').on('click', function () {
            var enviar = this.id === 'nt_enviar_wpp';
            var numero = $.trim($('#nt_teste_numero').val() || '');
            if (enviar && numero === '') {
                saida($('#nt_testar_wpp_saida'), 'Informe um número para enviar o teste.', false);
                return;
            }
            ajax($(this).data('url'), enviar ? { numero: numero } : {}, $('#nt_testar_wpp_saida'), $('#nt_testar_wpp, #nt_enviar_wpp'));
        });

        $('#nt_testar_email').on('click', function () {
            var dest = $.trim($('#nt_teste_email').val() || '');
            if (dest === '') {
                saida($('#nt_testar_email_saida'), 'Informe um e-mail para receber o teste.', false);
                return;
            }
            ajax($(this).data('url'), { email: dest }, $('#nt_testar_email_saida'), $(this));
        });
    }

    /* ---------------- Tela do evento ---------------- */

    function iniciarEvento(opcoes) {
        var $out = $('#nt-grupos-msg');

        $('#nt-carregar-grupos').on('click', function () {
            var $btn = $(this).prop('disabled', true);
            $out.html('<i class="bx bx-loader-alt bx-spin"></i> Carregando...');

            $.ajax({ url: $(this).data('url'), type: 'GET', dataType: 'json' })
                .done(function (d) {
                    if (!d || !d.result) {
                        saida($out, (d && d.mensagem) || 'Falha ao carregar grupos.', false);
                        return;
                    }
                    var marcados = $('#nt-grupos input:checked').map(function () { return this.value; }).get();
                    var html = '';
                    (d.grupos || []).forEach(function (g) {
                        var ck = marcados.indexOf(g.id) !== -1 ? 'checked' : '';
                        html += '<label style="display:block;margin-bottom:6px"><input type="checkbox" name="whatsapp_grupos[]" value="' + escapa(g.id) + '" ' + ck + '> ' + escapa(g.nome || g.id) + ' <small style="opacity:.7">' + escapa(g.id) + '</small></label>';
                    });
                    marcados.forEach(function (jid) {
                        if (!(d.grupos || []).some(function (g) { return g.id === jid; })) {
                            html += '<label style="display:block;margin-bottom:6px"><input type="checkbox" name="whatsapp_grupos[]" value="' + escapa(jid) + '" checked> ' + escapa(jid) + '</label>';
                        }
                    });
                    $('#nt-grupos').html(html || '<span style="color:var(--ui-texto-2);font-size:12px">Nenhum grupo encontrado nesta instância.</span>');
                    saida($out, (d.grupos || []).length + ' grupo(s) carregado(s).', true);
                })
                .fail(function (xhr) {
                    var m = 'Falha ao carregar grupos.';
                    try { m = JSON.parse(xhr.responseText).mensagem || m; } catch (e) {}
                    saida($out, m, false);
                })
                .always(function () {
                    $btn.prop('disabled', false);
                });
        });

        function chip(tipo, id, rotulo) {
            var $caixa = $('#nt-' + tipo + 's');
            var existente = $caixa.find('input[name="' + (tipo === 'cliente' ? 'whatsapp_clientes' : 'projetos_id') + '[]"]').filter(function () {
                return parseInt(this.value, 10) === id;
            });
            if (existente.length) {
                return;
            }
            var nomeCampo = tipo === 'cliente' ? 'whatsapp_clientes[]' : 'projetos_id[]';
            $caixa.append(
                '<span class="nt-chip-' + tipo + '" style="display:inline-flex;align-items:center;gap:6px;background:var(--ui-suave);border:1px solid var(--ui-borda);border-radius:16px;padding:4px 10px;font-size:12px">'
                + escapa(rotulo)
                + '<input type="hidden" name="' + nomeCampo + '" value="' + id + '">'
                + '<a href="#" class="nt-remover-' + tipo + '" style="color:var(--ui-perigo);text-decoration:none;font-weight:700">&times;</a></span>'
            );
        }

        if ($.fn.autocomplete) {
            $('#nt-busca-cliente').autocomplete({
                source: opcoes.buscaCliente,
                minLength: 2,
                select: function (ev, ui) {
                    if (ui.item && ui.item.id) { chip('cliente', parseInt(ui.item.id, 10), ui.item.label); }
                    $(this).val('');
                    return false;
                }
            });
            $('#nt-busca-projeto').autocomplete({
                source: opcoes.buscaProjeto,
                minLength: 2,
                select: function (ev, ui) {
                    if (ui.item && ui.item.id) { chip('projeto', parseInt(ui.item.id, 10), ui.item.label); }
                    $(this).val('');
                    return false;
                }
            });
        }

        $(document).on('click', '.nt-remover-cliente, .nt-remover-projeto', function (ev) {
            ev.preventDefault();
            $(this).closest('span').remove();
        });
    }

    window.NotificacoesEvento = iniciarEvento;
    window.NotificacoesCanais = iniciarTestes;

    $(iniciarTestes);
})(window, window.jQuery);
