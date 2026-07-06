<?php
// Partial: wizard de emissão de NFS-e (serviços) a partir de uma OS.
// Acionado por qualquer elemento com a classe .btn-transmitir-nfse e data-os="{idOs}".
?>
<div id="modal-nfse" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h5>Emitir NFS-e (serviços) — <span id="nfseStepLabel">Revisão</span></h5>
    </div>
    <div class="modal-body">
        <input type="hidden" id="nfseIdOs" value="" />

        <!-- Passo 1: Revisão -->
        <div id="nfsePasso1">
            <div id="nfsePreviewLoading" class="alert alert-info">Carregando dados da OS...</div>
            <div id="nfsePreview" style="display:none">
                <p style="margin-bottom:6px"><strong>Cliente:</strong> <span id="nfseCliente"></span></p>
                <table class="table table-bordered table-condensed">
                    <thead><tr><th>Serviço</th><th style="width:60px">Qtd</th><th style="width:90px">Unit.</th><th style="width:100px">Subtotal</th></tr></thead>
                    <tbody id="nfseItens"></tbody>
                    <tfoot><tr><th colspan="3" style="text-align:right">Total</th><th id="nfseTotal"></th></tr></tfoot>
                </table>
                <div id="nfseAvisos"></div>
                <div style="text-align:center;margin:8px 0">
                    <button type="button" id="nfseVerPrevia" class="button btn btn-mini btn-inverse">
                        <span class="button__icon"><i class="bx bx-show"></i></span><span class="button__text2">Ver prévia do documento (DANFSe)</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Passo 2: Dados da emissão -->
        <div id="nfsePasso2" style="display:none">
            <div class="control-group">
                <label for="nfseCtribnac"><strong>Código de Tributação Nacional</strong></label>
                <input type="text" id="nfseCtribnac" maxlength="6" class="span12" list="listaCtribnac" autocomplete="off" placeholder="Digite p/ sugestões (ex: 010701 = suporte em informática)" />
                <?php $this->load->view('nfe/_datalist_ctribnac'); ?>
            </div>
            <div class="control-group">
                <label for="nfseAliquota"><strong>Alíquota ISS (%)</strong></label>
                <input type="text" id="nfseAliquota" class="span12" placeholder="Ex: 3.00" />
                <span style="color:#999;font-size:11px">No Simples Nacional o ISS é apurado no DAS; este valor é informativo na nota.</span>
            </div>
            <div class="control-group">
                <label for="nfseTpRet"><strong>Retenção do ISSQN</strong></label>
                <select id="nfseTpRet" class="span12">
                    <option value="1">1 - Não retido</option>
                    <option value="2">2 - Retido pelo tomador</option>
                </select>
            </div>
            <div class="control-group">
                <label for="nfseDesc"><strong>Descrição do serviço</strong></label>
                <textarea id="nfseDesc" rows="2" class="span12" maxlength="2000"></textarea>
            </div>
            <div class="control-group">
                <label for="nfseInfoCpl"><strong>Informações complementares</strong></label>
                <textarea id="nfseInfoCpl" rows="2" class="span12" maxlength="500" placeholder="Texto livre anexado à descrição da nota"></textarea>
            </div>
        </div>

        <!-- Passo 3: Resultado -->
        <div id="nfsePasso3" style="display:none">
            <div id="nfseRetorno"></div>
            <div id="nfseAcoes" style="display:none;text-align:center;margin-top:10px">
                <a id="nfseBtnXml" href="#" class="button btn btn-info">
                    <span class="button__icon"><i class='bx bx-code-alt'></i></span><span class="button__text2">Baixar XML</span>
                </a>
                <a id="nfseBtnDanfe" href="#" target="_blank" class="button btn btn-inverse">
                    <span class="button__icon"><i class='bx bx-printer'></i></span><span class="button__text2">Imprimir DANFSe</span>
                </a>
            </div>
        </div>
    </div>
    <div class="modal-footer" style="display:flex;justify-content:center">
        <button type="button" class="button btn btn-warning" data-dismiss="modal">
            <span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Fechar</span>
        </button>
        <button type="button" id="nfseBtnVoltar" class="button btn btn-inverse" style="display:none">
            <span class="button__icon"><i class="bx bx-arrow-back"></i></span><span class="button__text2">Voltar</span>
        </button>
        <button type="button" id="nfseBtnAvancar" class="button btn btn-primary">
            <span class="button__icon"><i class="bx bx-right-arrow-alt"></i></span><span class="button__text2">Avançar</span>
        </button>
        <button type="button" id="nfseBtnTransmitir" class="button btn btn-success" style="display:none">
            <span class="button__icon"><i class='bx bx-send'></i></span><span class="button__text2">Transmitir</span>
        </button>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        var nfseEmitida = false;

        function nfseMoney(v) {
            return 'R$ ' + (parseFloat(v) || 0).toFixed(2).replace('.', ',');
        }

        function nfseMostrarPasso(n) {
            $('#nfsePasso1, #nfsePasso2, #nfsePasso3').hide();
            $('#nfsePasso' + n).show();
            $('#nfseBtnVoltar, #nfseBtnAvancar, #nfseBtnTransmitir').hide();
            if (n === 1) {
                $('#nfseStepLabel').text('Revisão');
                $('#nfseBtnAvancar').show();
            } else if (n === 2) {
                $('#nfseStepLabel').text('Dados da emissão');
                $('#nfseBtnVoltar').show();
                $('#nfseBtnTransmitir').show();
            } else {
                $('#nfseStepLabel').text('Resultado');
            }
        }

        $(document).on('click', '.btn-transmitir-nfse', function() {
            nfseEmitida = false;
            $('#nfseIdOs').val($(this).data('os'));
            $('#nfsePreview').hide();
            $('#nfsePreviewLoading').show().text('Carregando dados da OS...');
            $('#nfseItens, #nfseAvisos, #nfseRetorno').html('');
            $('#nfseAcoes').hide();
            $('#nfseBtnTransmitir').attr('disabled', false);
            nfseMostrarPasso(1);

            $.get('<?php echo site_url('nfe/previewOs'); ?>/' + $('#nfseIdOs').val() + '/nfse', function(data) {
                if (!data.success) {
                    $('#nfsePreviewLoading').removeClass('alert-info').addClass('alert-danger').text(data.message);
                    return;
                }
                $('#nfseCliente').text(data.cliente.nome + ' (' + data.cliente.documento + ')');
                var linhas = '';
                $.each(data.itens, function(i, it) {
                    linhas += '<tr><td>' + it.descricao + '</td><td>' + it.quantidade + '</td><td>' + nfseMoney(it.preco) + '</td><td>' + nfseMoney(it.subtotal) + '</td></tr>';
                });
                $('#nfseItens').html(linhas || '<tr><td colspan="4">Sem serviços.</td></tr>');
                $('#nfseTotal').text(nfseMoney(data.total));
                if (data.ambiente === 2) {
                    data.avisos.unshift('Ambiente de HOMOLOGAÇÃO — sem valor fiscal.');
                }
                var avisos = '';
                $.each(data.avisos, function(i, a) { avisos += '<div class="alert alert-warning" style="padding:6px">' + a + '</div>'; });
                $('#nfseAvisos').html(avisos);
                // pré-preenche o passo 2 com os defaults
                $('#nfseCtribnac').val(data.defaults.ctribnac || '');
                $('#nfseAliquota').val(data.defaults.aliquota_iss || '');
                $('#nfseTpRet').val(data.defaults.tp_ret_issqn || 1);
                $('#nfseDesc').val(data.defaults.desc_servico || '');
                $('#nfsePreviewLoading').hide();
                $('#nfsePreview').show();
            }, 'json').fail(function() {
                $('#nfsePreviewLoading').removeClass('alert-info').addClass('alert-danger').text('Falha ao carregar os dados da OS.');
            });
        });

        $('#nfseVerPrevia').on('click', function() {
            window.open('<?php echo site_url('nfe/modeloPreview'); ?>/' + $('#nfseIdOs').val() + '/nfse', '_blank');
        });

        $('#nfseBtnAvancar').on('click', function() { nfseMostrarPasso(2); });
        $('#nfseBtnVoltar').on('click', function() { nfseMostrarPasso(1); });

        $('#nfseBtnTransmitir').on('click', function() {
            var btn = $(this);
            btn.attr('disabled', true);
            nfseMostrarPasso(3);
            $('#nfseRetorno').html('<div class="alert alert-info">Transmitindo para o Sefin Nacional, aguarde...</div>');

            $.post('<?php echo site_url('nfe/emitirNfse'); ?>/' + $('#nfseIdOs').val(), {
                ctribnac: $('#nfseCtribnac').val(),
                aliquota_iss: $('#nfseAliquota').val(),
                tp_ret_issqn: $('#nfseTpRet').val(),
                desc_servico: $('#nfseDesc').val(),
                info_complementar: $('#nfseInfoCpl').val()
            }, function(data) {
                if (data.success) {
                    nfseEmitida = true;
                    $('#nfseRetorno').html('<div class="alert alert-success">' + data.message + '</div>');
                    if (data.urlXml) { $('#nfseBtnXml').attr('href', data.urlXml).show(); } else { $('#nfseBtnXml').hide(); }
                    $('#nfseBtnDanfe').attr('href', data.urlDanfe);
                    $('#nfseAcoes').show();
                } else {
                    $('#nfseRetorno').html('<div class="alert alert-danger">' + data.message + '</div>');
                    btn.attr('disabled', false);
                    $('#nfseBtnVoltar').show();
                    $('#nfseBtnTransmitir').show();
                }
            }, 'json').fail(function() {
                $('#nfseRetorno').html('<div class="alert alert-danger">Falha de comunicação com o servidor.</div>');
                btn.attr('disabled', false);
                $('#nfseBtnVoltar').show();
                $('#nfseBtnTransmitir').show();
            });
        });

        $('#modal-nfse').on('hidden hidden.bs.modal', function() {
            if (nfseEmitida) { window.location.reload(); }
        });
    });
</script>
