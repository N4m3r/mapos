<?php
// Partial: wizard de emissão de NF-e (produtos) a partir de uma OS.
// Acionado por qualquer elemento com a classe .btn-transmitir-nfe e data-os="{idOs}".
?>
<div id="modal-nfe" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h5>Emitir NF-e (produtos) — <span id="nfeStepLabel">Revisão</span></h5>
    </div>
    <div class="modal-body">
        <input type="hidden" id="nfeIdOs" value="" />

        <!-- Passo 1: Revisão -->
        <div id="nfePasso1">
            <div id="nfePreviewLoading" class="alert alert-info">Carregando dados da OS...</div>
            <div id="nfePreview" style="display:none">
                <p style="margin-bottom:6px"><strong>Cliente:</strong> <span id="nfeCliente"></span></p>
                <table class="table table-bordered table-condensed">
                    <thead><tr><th>Produto</th><th style="width:60px">Qtd</th><th style="width:90px">Unit.</th><th style="width:100px">Subtotal</th></tr></thead>
                    <tbody id="nfeItens"></tbody>
                    <tfoot><tr><th colspan="3" style="text-align:right">Total</th><th id="nfeTotal"></th></tr></tfoot>
                </table>
                <div id="nfeAvisos"></div>
            </div>
        </div>

        <!-- Passo 2: Dados da emissão -->
        <div id="nfePasso2" style="display:none">
            <div class="control-group">
                <label for="nfeInfoCpl"><strong>Informações complementares</strong></label>
                <textarea id="nfeInfoCpl" rows="3" class="span12" maxlength="500" placeholder="Texto livre impresso no campo de dados adicionais da NF-e"></textarea>
            </div>
            <div class="alert alert-info" style="padding:6px;font-size:12px">
                Impostos: Simples Nacional (CSOSN <?php echo html_escape(isset($configNfe) && $configNfe ? $configNfe->csosn_padrao : '102'); ?>), sem destaque de ICMS/PIS/COFINS. Os dados fiscais dos itens (NCM/CFOP) vêm do cadastro dos produtos.
            </div>
        </div>

        <!-- Passo 3: Resultado -->
        <div id="nfePasso3" style="display:none">
            <div id="nfeRetorno"></div>
            <div id="nfeAcoes" style="display:none;text-align:center;margin-top:10px">
                <a id="nfeBtnXml" href="#" class="button btn btn-info">
                    <span class="button__icon"><i class='bx bx-code-alt'></i></span><span class="button__text2">Baixar XML</span>
                </a>
                <a id="nfeBtnDanfe" href="#" target="_blank" class="button btn btn-inverse">
                    <span class="button__icon"><i class='bx bx-printer'></i></span><span class="button__text2">Imprimir DANFE</span>
                </a>
            </div>
        </div>
    </div>
    <div class="modal-footer" style="display:flex;justify-content:center">
        <button type="button" class="button btn btn-warning" data-dismiss="modal">
            <span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Fechar</span>
        </button>
        <button type="button" id="nfeBtnVoltar" class="button btn btn-inverse" style="display:none">
            <span class="button__icon"><i class="bx bx-arrow-back"></i></span><span class="button__text2">Voltar</span>
        </button>
        <button type="button" id="nfeBtnAvancar" class="button btn btn-primary">
            <span class="button__icon"><i class="bx bx-right-arrow-alt"></i></span><span class="button__text2">Avançar</span>
        </button>
        <button type="button" id="nfeBtnTransmitir" class="button btn btn-success" style="display:none">
            <span class="button__icon"><i class='bx bx-send'></i></span><span class="button__text2">Transmitir</span>
        </button>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        var nfeEmitida = false;

        function nfeMoney(v) {
            return 'R$ ' + (parseFloat(v) || 0).toFixed(2).replace('.', ',');
        }

        function nfeMostrarPasso(n) {
            $('#nfePasso1, #nfePasso2, #nfePasso3').hide();
            $('#nfePasso' + n).show();
            $('#nfeBtnVoltar, #nfeBtnAvancar, #nfeBtnTransmitir').hide();
            if (n === 1) {
                $('#nfeStepLabel').text('Revisão');
                $('#nfeBtnAvancar').show();
            } else if (n === 2) {
                $('#nfeStepLabel').text('Dados da emissão');
                $('#nfeBtnVoltar').show();
                $('#nfeBtnTransmitir').show();
            } else {
                $('#nfeStepLabel').text('Resultado');
            }
        }

        $(document).on('click', '.btn-transmitir-nfe', function() {
            nfeEmitida = false;
            $('#nfeIdOs').val($(this).data('os'));
            $('#nfePreview').hide();
            $('#nfePreviewLoading').show().text('Carregando dados da OS...');
            $('#nfeItens, #nfeAvisos, #nfeRetorno').html('');
            $('#nfeAcoes').hide();
            $('#nfeBtnTransmitir').attr('disabled', false);
            nfeMostrarPasso(1);

            $.get('<?php echo site_url('nfe/previewOs'); ?>/' + $('#nfeIdOs').val() + '/nfe', function(data) {
                if (!data.success) {
                    $('#nfePreviewLoading').removeClass('alert-info').addClass('alert-danger').text(data.message);
                    return;
                }
                $('#nfeCliente').text(data.cliente.nome + ' (' + data.cliente.documento + ')');
                var linhas = '';
                $.each(data.itens, function(i, it) {
                    linhas += '<tr><td>' + it.descricao + '</td><td>' + it.quantidade + '</td><td>' + nfeMoney(it.preco) + '</td><td>' + nfeMoney(it.subtotal) + '</td></tr>';
                });
                $('#nfeItens').html(linhas || '<tr><td colspan="4">Sem produtos.</td></tr>');
                $('#nfeTotal').text(nfeMoney(data.total));
                if (data.ambiente === 2) {
                    data.avisos.unshift('Ambiente de HOMOLOGAÇÃO — sem valor fiscal.');
                }
                var avisos = '';
                $.each(data.avisos, function(i, a) { avisos += '<div class="alert alert-warning" style="padding:6px">' + a + '</div>'; });
                $('#nfeAvisos').html(avisos);
                $('#nfePreviewLoading').hide();
                $('#nfePreview').show();
            }, 'json').fail(function() {
                $('#nfePreviewLoading').removeClass('alert-info').addClass('alert-danger').text('Falha ao carregar os dados da OS.');
            });
        });

        $('#nfeBtnAvancar').on('click', function() { nfeMostrarPasso(2); });
        $('#nfeBtnVoltar').on('click', function() { nfeMostrarPasso(1); });

        $('#nfeBtnTransmitir').on('click', function() {
            var btn = $(this);
            btn.attr('disabled', true);
            nfeMostrarPasso(3);
            $('#nfeRetorno').html('<div class="alert alert-info">Transmitindo para a SEFAZ, aguarde...</div>');

            $.post('<?php echo site_url('nfe/emitirNfeOs'); ?>/' + $('#nfeIdOs').val(), {
                info_complementar: $('#nfeInfoCpl').val()
            }, function(data) {
                if (data.success) {
                    nfeEmitida = true;
                    $('#nfeRetorno').html('<div class="alert alert-success">' + data.message + '</div>');
                    if (data.urlXml) { $('#nfeBtnXml').attr('href', data.urlXml).show(); } else { $('#nfeBtnXml').hide(); }
                    $('#nfeBtnDanfe').attr('href', data.urlDanfe);
                    $('#nfeAcoes').show();
                } else {
                    $('#nfeRetorno').html('<div class="alert alert-danger">' + data.message + '</div>');
                    btn.attr('disabled', false);
                    $('#nfeBtnVoltar').show();
                    $('#nfeBtnTransmitir').show();
                }
            }, 'json').fail(function() {
                $('#nfeRetorno').html('<div class="alert alert-danger">Falha de comunicação com o servidor.</div>');
                btn.attr('disabled', false);
                $('#nfeBtnVoltar').show();
                $('#nfeBtnTransmitir').show();
            });
        });

        $('#modal-nfe').on('hidden hidden.bs.modal', function() {
            if (nfeEmitida) { window.location.reload(); }
        });
    });
</script>
