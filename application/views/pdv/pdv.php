<style>
    .pdv { display:flex; gap:14px; flex-wrap:wrap; align-items:flex-start; }
    .pdv-esq { flex:2 1 520px; min-width:300px; }
    .pdv-dir { flex:1 1 300px; min-width:280px; background:#fff; border:1px solid #e2e6f0; border-radius:10px; padding:14px; position:sticky; top:10px; }
    .pdv-busca { position:relative; }
    .pdv-busca input { width:100%; font-size:20px; padding:10px 12px; height:auto; box-sizing:border-box; margin:0; }
    .pdv-resultados { position:absolute; left:0; right:0; top:100%; z-index:50; background:#fff; border:1px solid #ccd; border-radius:0 0 8px 8px; max-height:320px; overflow:auto; display:none; box-shadow:0 8px 20px rgba(0,0,0,.12); }
    .pdv-resultados div { padding:8px 12px; cursor:pointer; display:flex; justify-content:space-between; gap:10px; }
    .pdv-resultados div:hover, .pdv-resultados div.ativo { background:#eef2ff; }
    .pdv-carrinho { width:100%; margin-top:12px; background:#fff; }
    .pdv-carrinho td, .pdv-carrinho th { vertical-align:middle !important; }
    .pdv-carrinho input { width:60px; margin:0; text-align:center; }
    .pdv-total { font-size:34px; font-weight:700; text-align:right; color:#2d3436; }
    .pdv-linha { display:flex; justify-content:space-between; align-items:center; margin:6px 0; }
    .pdv-formas { display:flex; flex-wrap:wrap; gap:6px; margin:8px 0; }
    .pdv-formas button { flex:1 1 45%; }
    .pdv-pagamentos div { display:flex; justify-content:space-between; align-items:center; padding:4px 0; border-bottom:1px dashed #ddd; }
    .pdv-atalhos { color:#888; font-size:11px; margin-top:8px; }
    #pdvFinal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:3000; align-items:center; justify-content:center; }
    #pdvFinal .caixa { background:#fff; border-radius:12px; padding:22px; width:min(92vw, 420px); text-align:center; }
</style>

<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="bx bx-store"></i></span>
        <h5>PDV - caixa #<?= (int) $caixa->id ?></h5>
    </div>
    <div class="widget-content">
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:10px; color:#666; font-size:13px">
            <span>Aberto em <?= date('d/m/Y H:i', strtotime($caixa->aberto_em)) ?></span>
            <span>· Vendido: <strong>R$ <?= number_format($totais['vendas'], 2, ',', '.') ?></strong></span>
            <span>· Dinheiro na gaveta: <strong>R$ <?= number_format($totais['esperado_dinheiro'], 2, ',', '.') ?></strong></span>
            <a href="<?= site_url('caixa/ver/' . (int) $caixa->id) ?>" class="btn btn-mini" style="margin-left:auto"><i class="bx bx-wallet"></i> Caixa (sangria, suprimento, fechar)</a>
        </div>

        <div class="pdv">
            <div class="pdv-esq">
                <div class="pdv-busca">
                    <input type="text" id="pdvBusca" placeholder="Código de barras ou nome do produto (F2)" autocomplete="off" autofocus>
                    <div class="pdv-resultados" id="pdvResultados"></div>
                </div>
                <table class="table table-bordered table-condensed pdv-carrinho">
                    <thead><tr><th>Produto</th><th style="width:80px; text-align:center">Qtd</th><th style="width:100px; text-align:right">Unitário</th><th style="width:110px; text-align:right">Subtotal</th><th style="width:36px"></th></tr></thead>
                    <tbody id="pdvItens"><tr class="vazio"><td colspan="5" style="color:#999; text-align:center; padding:30px">Nenhum item. Leia o código de barras ou busque pelo nome.</td></tr></tbody>
                </table>
            </div>

            <div class="pdv-dir">
                <div class="pdv-linha"><span>Itens</span><strong id="pdvQtdItens">0</strong></div>
                <div class="pdv-linha"><span>Subtotal</span><strong id="pdvSubtotal">R$ 0,00</strong></div>
                <div class="pdv-linha"><span>Desconto (R$)</span><input type="text" id="pdvDesconto" inputmode="decimal" value="0,00" style="width:100px; text-align:right; margin:0"></div>
                <div class="pdv-total" id="pdvTotal">R$ 0,00</div>

                <hr style="margin:10px 0">
                <label style="margin:0">Cliente (opcional)</label>
                <div style="position:relative">
                    <input type="text" id="pdvCliente" placeholder="Buscar cliente..." autocomplete="off" style="width:100%; box-sizing:border-box; margin:0">
                    <div class="pdv-resultados" id="pdvClientes"></div>
                </div>
                <label style="margin:6px 0 0">CPF/CNPJ na nota (opcional)</label>
                <input type="text" id="pdvCpf" inputmode="numeric" maxlength="18" style="width:100%; box-sizing:border-box; margin:0">

                <hr style="margin:10px 0">
                <strong>Pagamento</strong>
                <div class="pdv-formas">
                    <?php foreach ($formas as $chave => $nome) { ?>
                        <button type="button" class="btn btn-small" data-forma="<?= $chave ?>"><?= $nome ?></button>
                    <?php } ?>
                </div>
                <div class="pdv-pagamentos" id="pdvPagamentos"></div>
                <div class="pdv-linha"><span>Falta</span><strong id="pdvFalta">R$ 0,00</strong></div>
                <div class="pdv-linha"><span>Troco</span><strong id="pdvTroco" style="color:#27ae60">R$ 0,00</strong></div>

                <button type="button" id="pdvFinalizar" class="button btn btn-success" style="width:100%; margin-top:8px" disabled>
                    <span class="button__icon"><i class="bx bx-check"></i></span><span class="button__text2">Finalizar venda (F4)</span></button>
                <button type="button" id="pdvCancelar" class="btn btn-mini btn-danger" style="width:100%; margin-top:6px">Cancelar venda</button>
                <div class="pdv-atalhos">F2 busca · Enter adiciona · F4 finaliza · Esc limpa a busca</div>
            </div>
        </div>
    </div>
</div>

<div id="pdvFinal">
    <div class="caixa">
        <h3 style="margin-top:0">Venda <span id="pdvFinalNumero"></span> registrada</h3>
        <p style="font-size:18px">Total: <strong id="pdvFinalTotal"></strong></p>
        <p style="font-size:24px; color:#27ae60" id="pdvFinalTroco"></p>
        <p id="pdvFinalNfce" style="font-size:13px"></p>
        <div style="display:flex; gap:8px; justify-content:center; flex-wrap:wrap">
            <a href="#" target="_blank" id="pdvFinalCupom" class="button btn btn-info"><span class="button__icon"><i class="bx bx-printer"></i></span><span class="button__text2">Comprovante</span></a>
            <?php if ($nfceDisponivel) { ?>
                <button type="button" id="pdvEmitirNfce" class="button btn btn-warning"><span class="button__icon"><i class="bx bx-receipt"></i></span><span class="button__text2">Emitir NFC-e</span></button>
            <?php } ?>
            <button type="button" id="pdvNovaVenda" class="button btn btn-success"><span class="button__icon"><i class="bx bx-plus"></i></span><span class="button__text2">Nova venda (Enter)</span></button>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
(function () {
    var URL_BASE = '<?= site_url() ?>/';
    var FORMAS = <?= json_encode($formas) ?>;
    var itens = [];        // {id, descricao, preco, quantidade}
    var pagamentos = [];   // {forma, valor}
    var cliente = null;
    var ultimaVenda = null;
    var $ = window.jQuery;
    // Atalhos globais do painel (F2 = Produtos, F4 = OS) conflitam com os do PDV
    if (window.shortcut && shortcut.remove) { shortcut.remove('F2'); shortcut.remove('F4'); }

    var dinheiro = function (v) { return 'R$ ' + v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
    var numero = function (t) { t = String(t || '').trim(); if (t.indexOf(',') !== -1) { t = t.replace(/\./g, '').replace(',', '.'); } var n = parseFloat(t); return isNaN(n) ? 0 : n; };
    var escapar = function (t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; };
    var arred = function (v) { return Math.round(v * 100) / 100; };

    function totais() {
        var subtotal = arred(itens.reduce(function (s, i) { return s + i.preco * i.quantidade; }, 0));
        var desconto = Math.min(arred(numero($('#pdvDesconto').val())), subtotal);
        var total = arred(subtotal - desconto);
        var pago = arred(pagamentos.reduce(function (s, p) { return s + p.valor; }, 0));
        return { subtotal: subtotal, desconto: desconto, total: total, pago: pago, falta: Math.max(0, arred(total - pago)), troco: Math.max(0, arred(pago - total)) };
    }

    function render() {
        var corpo = $('#pdvItens').empty();
        if (!itens.length) {
            corpo.append('<tr class="vazio"><td colspan="5" style="color:#999; text-align:center; padding:30px">Nenhum item. Leia o código de barras ou busque pelo nome.</td></tr>');
        }
        itens.forEach(function (i, n) {
            corpo.append('<tr><td>' + escapar(i.descricao) + '</td>' +
                '<td style="text-align:center"><input type="number" min="1" step="1" value="' + i.quantidade + '" data-n="' + n + '" class="pdv-qtd"></td>' +
                '<td style="text-align:right">' + dinheiro(i.preco) + '</td>' +
                '<td style="text-align:right">' + dinheiro(arred(i.preco * i.quantidade)) + '</td>' +
                '<td><button type="button" class="btn btn-mini btn-danger pdv-remover" data-n="' + n + '" title="Remover">&times;</button></td></tr>');
        });
        var t = totais();
        $('#pdvQtdItens').text(itens.reduce(function (s, i) { return s + i.quantidade; }, 0));
        $('#pdvSubtotal').text(dinheiro(t.subtotal));
        $('#pdvTotal').text(dinheiro(t.total));
        $('#pdvFalta').text(dinheiro(t.falta));
        $('#pdvTroco').text(dinheiro(t.troco));
        var pag = $('#pdvPagamentos').empty();
        pagamentos.forEach(function (p, n) {
            pag.append('<div><span>' + escapar(FORMAS[p.forma]) + '</span><span>' + dinheiro(p.valor) +
                ' <button type="button" class="btn btn-mini pdv-rem-pag" data-n="' + n + '">&times;</button></span></div>');
        });
        var temDinheiro = pagamentos.some(function (p) { return p.forma === 'dinheiro'; });
        var trocoInvalido = t.troco > 0 && !temDinheiro;
        $('#pdvFinalizar').prop('disabled', !itens.length || t.falta > 0 || trocoInvalido);
    }

    function adicionar(produto) {
        var existente = itens.filter(function (i) { return i.id === produto.id; })[0];
        if (existente) { existente.quantidade++; } else { itens.push({ id: produto.id, descricao: produto.descricao, preco: produto.preco, quantidade: 1 }); }
        $('#pdvResultados').hide().empty();
        $('#pdvBusca').val('').focus();
        render();
    }

    var ultimaBusca = null;
    function buscar(adicionarSeExato) {
        var termo = $('#pdvBusca').val().trim();
        if (!termo) { $('#pdvResultados').hide(); return; }
        ultimaBusca = termo;
        $.getJSON(URL_BASE + 'pdv/produtos', { termo: termo }).done(function (lista) {
            if (termo !== ultimaBusca) { return; }
            if (adicionarSeExato && lista.length === 1 && (lista[0].exato || lista.length === 1)) { adicionar(lista[0]); return; }
            var box = $('#pdvResultados').empty();
            if (!lista.length) { box.append('<div style="color:#999">Nenhum produto encontrado</div>').show(); return; }
            lista.forEach(function (p, n) {
                $('<div' + (n === 0 ? ' class="ativo"' : '') + '><span>' + escapar(p.descricao) + ' <small style="color:#999">' + escapar(p.codigo || '') + ' · estoque ' + p.estoque + '</small></span><strong>' + dinheiro(p.preco) + '</strong></div>')
                    .data('produto', p).appendTo(box);
            });
            box.show();
        });
    }

    var espera = null;
    $('#pdvBusca').on('input', function () { clearTimeout(espera); espera = setTimeout(function () { buscar(false); }, 250); })
        .on('keydown', function (e) {
            var box = $('#pdvResultados');
            if (e.key === 'Enter') {
                e.preventDefault();
                var ativo = box.is(':visible') ? box.children('.ativo').data('produto') : null;
                if (ativo) { adicionar(ativo); } else { buscar(true); }
            } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                var filhos = box.children(); var i = filhos.index(filhos.filter('.ativo'));
                i = e.key === 'ArrowDown' ? Math.min(filhos.length - 1, i + 1) : Math.max(0, i - 1);
                filhos.removeClass('ativo').eq(i).addClass('ativo');
            } else if (e.key === 'Escape') { $(this).val(''); box.hide(); }
        });
    $('#pdvResultados').on('click', 'div', function () { if ($(this).data('produto')) { adicionar($(this).data('produto')); } });

    $('#pdvItens').on('change', '.pdv-qtd', function () {
        var n = $(this).data('n'); var q = parseInt(this.value, 10);
        itens[n].quantidade = q > 0 ? q : 1; render();
    }).on('click', '.pdv-remover', function () { itens.splice($(this).data('n'), 1); render(); $('#pdvBusca').focus(); });
    $('#pdvDesconto').on('input', render);

    $('.pdv-formas').on('click', 'button', function () {
        var t = totais();
        var sugestao = t.falta > 0 ? t.falta : 0;
        var valor = numero(window.prompt('Valor em ' + FORMAS[$(this).data('forma')] + ' (R$):', sugestao.toFixed(2).replace('.', ',')));
        if (valor > 0) { pagamentos.push({ forma: $(this).data('forma'), valor: arred(valor) }); render(); }
    });
    $('#pdvPagamentos').on('click', '.pdv-rem-pag', function () { pagamentos.splice($(this).data('n'), 1); render(); });

    var esperaCli = null;
    $('#pdvCliente').on('input', function () {
        cliente = null; var termo = this.value.trim(); clearTimeout(esperaCli);
        if (termo.length < 2) { $('#pdvClientes').hide(); return; }
        esperaCli = setTimeout(function () {
            $.getJSON(URL_BASE + 'pdv/clientes', { termo: termo }).done(function (lista) {
                var box = $('#pdvClientes').empty();
                lista.forEach(function (c) { $('<div><span>' + escapar(c.nome) + '</span><small>' + escapar(c.documento || '') + '</small></div>').data('cliente', c).appendTo(box); });
                box.toggle(lista.length > 0);
            });
        }, 250);
    });
    $('#pdvClientes').on('click', 'div', function () {
        cliente = $(this).data('cliente'); $('#pdvCliente').val(cliente.nome); $('#pdvClientes').hide();
        if (cliente.documento && !$('#pdvCpf').val()) { $('#pdvCpf').val(cliente.documento); }
    });

    function limpar() {
        itens = []; pagamentos = []; cliente = null;
        $('#pdvDesconto').val('0,00'); $('#pdvCliente').val(''); $('#pdvCpf').val('');
        $('#pdvFinal').hide(); render(); $('#pdvBusca').focus();
    }
    $('#pdvCancelar').on('click', function () { if (!itens.length || confirm('Cancelar esta venda?')) { limpar(); } });

    function finalizar() {
        if ($('#pdvFinalizar').prop('disabled')) { return; }
        var t = totais();
        $('#pdvFinalizar').prop('disabled', true);
        $.ajax({
            url: URL_BASE + 'pdv/finalizar', type: 'POST', dataType: 'json',
            data: { venda: JSON.stringify({
                itens: itens.map(function (i) { return { produto: i.id, quantidade: i.quantidade }; }),
                desconto: t.desconto, pagamentos: pagamentos, cliente: cliente ? cliente.id : null, cpf: $('#pdvCpf').val()
            }) }
        }).done(function (r) {
            ultimaVenda = r;
            $('#pdvFinalNumero').text('#' + r.venda);
            $('#pdvFinalTotal').text(dinheiro(r.liquido));
            $('#pdvFinalTroco').text(r.troco > 0 ? 'Troco: ' + dinheiro(r.troco) : '');
            $('#pdvFinalCupom').attr('href', r.cupom);
            $('#pdvFinalNfce').html('');
            $('#pdvEmitirNfce').prop('disabled', false).show();
            $('#pdvFinal').css('display', 'flex');
            $('#pdvNovaVenda').focus();
        }).fail(function (xhr) {
            var r = xhr.responseJSON || {};
            alert(r.mensagem || 'Erro ao finalizar a venda.');
            render();
        });
    }
    $('#pdvFinalizar').on('click', finalizar);
    $('#pdvNovaVenda').on('click', limpar);

    $('#pdvEmitirNfce').on('click', function () {
        var botao = $(this).prop('disabled', true);
        $('#pdvFinalNfce').text('Transmitindo para a SEFAZ...');
        $.ajax({ url: ultimaVenda.nfce, type: 'POST', dataType: 'json', data: {} }).done(function (r) {
            if (r.success || r.result) {
                $('#pdvFinalNfce').html('<span style="color:#27ae60">' + escapar(r.message || 'NFC-e autorizada') + '</span>' + (r.urlDanfe ? ' <a href="' + r.urlDanfe + '" target="_blank">Imprimir DANFCE</a>' : ''));
                botao.hide();
            } else {
                $('#pdvFinalNfce').html('<span style="color:#c0392b">' + escapar(r.message || 'Falha ao emitir') + '</span>');
                botao.prop('disabled', false);
            }
        }).fail(function () { $('#pdvFinalNfce').html('<span style="color:#c0392b">Erro de comunicação ao emitir a NFC-e. A venda está registrada; tente pela tela da venda.</span>'); botao.prop('disabled', false); });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'F2') { e.preventDefault(); $('#pdvBusca').focus().select(); }
        if (e.key === 'F4') { e.preventDefault(); finalizar(); }
        if (e.key === 'Enter' && $('#pdvFinal').is(':visible') && document.activeElement.id !== 'pdvEmitirNfce') { e.preventDefault(); limpar(); }
    });
    render();
})();
</script>
