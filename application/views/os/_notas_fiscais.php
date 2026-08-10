<?php
// Partial: tabela de notas fiscais emitidas para uma OS, com o boleto/PIX (Cora)
// vinculado a cada nota.
// Espera: $notas (linhas de notas_fiscais) e $boletosLista (nota_id => [cobranças]).
$notas = isset($notas) ? $notas : [];
// $boletosLista: mapa nota_id => [cobranças] (todas as parcelas). Compat: aceita
// também $boletos (mapa nota_id => cobrança única) de chamadas antigas.
$boletosLista = isset($boletosLista) && is_array($boletosLista) ? $boletosLista : [];
if (empty($boletosLista) && isset($boletos) && is_array($boletos)) {
    foreach ($boletos as $notaId => $b) {
        $boletosLista[$notaId] = [$b];
    }
}
$coraStage = isset($coraStage) ? $coraStage : false;
$podeCancelar = $this->permission->checkPermission($this->session->userdata('permissao'), 'dNfe');
$podeEmitir = $this->permission->checkPermission($this->session->userdata('permissao'), 'eNfe');
$podeGerarBoleto = $this->permission->checkPermission($this->session->userdata('permissao'), 'aCobranca');
$podeVerBoleto = $this->permission->checkPermission($this->session->userdata('permissao'), 'vCobranca');
$this->load->config('payment_gateways');
$this->load->helper('general');
$gwConfig = $this->config->item('payment_gateways');
?>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Nº / Série</th>
                <th>Valor</th>
                <th>Emissão</th>
                <th>Status</th>
                <th>Chave / Retorno</th>
                <th>Boleto / PIX</th>
                <th style="text-align:center">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($notas)) { ?>
                <tr>
                    <td colspan="8" style="text-align:center">Nenhuma nota fiscal emitida para esta OS.</td>
                </tr>
            <?php } else {
                foreach ($notas as $nota) {
                    $corStatus = match ($nota->status) {
                        'autorizada' => '#4d9c79',
                        'rejeitada' => '#f24c6f',
                        'cancelada' => '#CD0000',
                        'substituida' => '#9b59b6',
                        'erro' => '#FF7F00',
                        default => '#AEB404',
                    };
                    $tipoLabel = $nota->tipo === 'nfe' ? 'NF-e (produtos)' : 'NFS-e (serviços)';
                    $tipoIcon = $nota->tipo === 'nfe' ? 'bx-box' : 'bx-receipt';
                    $boletosDaNota = $boletosLista[$nota->idNota] ?? [];
                    // Parcelas ativas (ignora canceladas) para saber se já há boleto.
                    $boletosAtivos = array_filter($boletosDaNota, fn ($b) => ! in_array($b->status, ['CANCELLED', 'cancelada'], true));
                    ?>
                    <tr>
                        <td><i class="bx <?php echo $tipoIcon; ?>"></i> <?php echo $tipoLabel; ?><?php echo $nota->ambiente == 2 ? ' <span class="badge" style="background:#AEB404">Homolog.</span>' : ''; ?></td>
                        <td><?php echo $nota->numero; ?> / <?php echo $nota->serie; ?></td>
                        <td>R$ <?php echo number_format($nota->valor_total, 2, ',', '.'); ?></td>
                        <td><?php echo $nota->data_emissao ? date('d/m/Y H:i', strtotime($nota->data_emissao)) : '-'; ?></td>
                        <td><span class="badge" style="background-color:<?php echo $corStatus; ?>;border-color:<?php echo $corStatus; ?>"><?php echo ucfirst($nota->status); ?></span></td>
                        <td style="font-size:11px;max-width:240px;word-break:break-all">
                            <?php echo $nota->chave ? html_escape($nota->chave) : ''; ?>
                            <?php echo $nota->motivo ? '<br><span style="color:#888">' . html_escape(mb_substr($nota->motivo, 0, 140)) . '</span>' : ''; ?>
                        </td>
                        <td style="font-size:11px;min-width:150px" id="boleto-nota-<?php echo $nota->idNota; ?>">
                            <?php if (! empty($boletosAtivos)) {
                                $totalParcelas = count($boletosAtivos);
                                $indiceParcela = 0;
                                foreach ($boletosAtivos as $boleto) {
                                    $indiceParcela++;
                                    $statusBoleto = $boleto->status;
                                    try {
                                        $statusLabel = getCobrancaTransactionStatus($gwConfig, $boleto->payment_gateway, $statusBoleto);
                                    } catch (\Throwable $e) {
                                        $statusLabel = $statusBoleto;
                                    }
                                    $pago = in_array($statusBoleto, ['PAID', 'RECEIVED', 'CONFIRMED']);
                                    $problema = in_array($statusBoleto, ['LATE', 'CANCELLED', 'OVERDUE']);
                                    $corBoleto = $pago ? '#4d9c79' : ($problema ? '#CD0000' : '#AEB404');
                                    ?>
                                    <div style="padding:3px 0<?php echo $indiceParcela < $totalParcelas ? ';border-bottom:1px dashed #e0e0e0' : ''; ?>">
                                        <?php if ($totalParcelas > 1) { ?><strong title="Parcela"><?php echo $indiceParcela . '/' . $totalParcelas; ?></strong> <?php } ?>
                                        <span class="badge badge-status-boleto" data-id="<?php echo $boleto->idCobranca; ?>" style="background-color:<?php echo $corBoleto; ?>;border-color:<?php echo $corBoleto; ?>"><?php echo html_escape($statusLabel); ?></span>
                                        R$ <?php echo number_format($boleto->total / 100, 2, ',', '.'); ?>
                                        <?php if (! empty($boleto->expire_at)) { ?>
                                            <span style="color:#888">venc. <?php echo date('d/m/Y', strtotime($boleto->expire_at)); ?></span>
                                        <?php } ?>
                                        <?php if ($boleto->valor_iss_retido > 0) { ?>
                                            <span style="color:#888" title="ISS retido abatido">(ISS ret.: R$ <?php echo number_format($boleto->valor_iss_retido, 2, ',', '.'); ?>)</span>
                                        <?php } ?>
                                        <span style="white-space:nowrap">
                                            <?php if (! empty($boleto->pdf)) { ?>
                                                <a href="<?php echo html_escape($boleto->pdf); ?>" target="_blank" class="btn-nwe6" title="Baixar boleto (PDF)"><i class="bx bx-barcode bx-xs"></i></a>
                                            <?php } ?>
                                            <?php if (! empty($boleto->pix)) { ?>
                                                <a href="#" class="btn-nwe6 btn-copiar-pix" data-pix="<?php echo html_escape($boleto->pix); ?>" title="Copiar código PIX (copia e cola)"><i class="bx bx-qr bx-xs"></i></a>
                                            <?php } ?>
                                            <?php if (! $pago && $podeVerBoleto) { ?>
                                                <a href="#" class="btn-nwe6 btn-verificar-boleto" data-id="<?php echo $boleto->idCobranca; ?>" data-nota="<?php echo $nota->idNota; ?>" title="Verificar pagamento"><i class="bx bx-refresh bx-xs"></i></a>
                                            <?php } ?>
                                            <?php if (! $pago && $coraStage && $podeVerBoleto) { ?>
                                                <a href="#" class="btn-nwe4 btn-simular-pgto" data-id="<?php echo $boleto->idCobranca; ?>" data-nota="<?php echo $nota->idNota; ?>" title="Simular pagamento (só em homologação/Stage)"><i class="bx bx-test-tube bx-xs"></i> teste</a>
                                            <?php } ?>
                                        </span>
                                    </div>
                                <?php }
                            } elseif ($nota->status === 'autorizada' && $podeGerarBoleto) { ?>
                                <button type="button" class="btn btn-mini btn-info btn-gerar-boleto" data-nota="<?php echo $nota->idNota; ?>" data-valor="<?php echo number_format((float) $nota->valor_total, 2, '.', ''); ?>" title="Gerar boleto híbrido (boleto + PIX) na Cora, à vista ou parcelado">
                                    <i class="bx bx-dollar bx-xs"></i> Gerar Boleto/PIX
                                </button>
                            <?php } else { ?>
                                <span style="color:#999">-</span>
                            <?php } ?>
                        </td>
                        <td style="text-align:center;white-space:nowrap">
                            <?php if (in_array($nota->status, ['autorizada', 'cancelada'], true)) { ?>
                                <a href="<?php echo site_url('nfe/xml/' . $nota->idNota); ?>" class="btn-nwe6" title="Baixar XML"><i class="bx bx-code-alt bx-xs"></i></a>
                            <?php } ?>
                            <?php if ($nota->status === 'autorizada') { ?>
                                <a href="<?php echo site_url('nfe/danfe/' . $nota->idNota); ?>" target="_blank" class="btn-nwe6" title="Imprimir <?php echo $nota->tipo === 'nfe' ? 'DANFE' : 'DANFSe'; ?>"><i class="bx bx-printer bx-xs"></i></a>
                            <?php } ?>
                            <?php if ($nota->status === 'autorizada' && $podeCancelar) { ?>
                                <a href="<?php echo site_url('nfe/gerenciar?status=autorizada'); ?>" class="btn-nwe4" title="Cancelar (na tela de Notas Fiscais)"><i class="bx bx-x-circle bx-xs"></i></a>
                            <?php } ?>
                            <?php if (in_array($nota->status, ['rejeitada', 'erro']) && $podeEmitir && !empty($nota->os_id)) {
                                $classeRetransmitir = $nota->tipo === 'nfe' ? 'btn-transmitir-nfe' : 'btn-transmitir-nfse';
                                $modalRetransmitir = $nota->tipo === 'nfe' ? '#modal-nfe' : '#modal-nfse';
                                ?>
                                <a href="<?php echo $modalRetransmitir; ?>" role="button" data-toggle="modal" class="btn-nwe3 <?php echo $classeRetransmitir; ?>" data-os="<?php echo $nota->os_id; ?>" title="Corrigir e retransmitir (mantém o nº <?php echo $nota->numero; ?>)"><i class="bx bx-refresh bx-xs"></i></a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php }
            } ?>
        </tbody>
    </table>
</div>
<?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vNfe')) { ?>
    <div style="text-align:right;margin-top:6px">
        <a href="<?php echo site_url('nfe/gerenciar'); ?>" class="button btn btn-mini btn-inverse">
            <span class="button__icon"><i class='bx bx-list-ul'></i></span><span class="button__text2">Gerenciar Notas Fiscais</span>
        </a>
    </div>
<?php } ?>

<?php if (! defined('MAPOS_BOLETO_NOTA_JS')) { define('MAPOS_BOLETO_NOTA_JS', true); ?>
<div id="modal-gerar-boleto" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h5>Gerar Boleto/PIX (Cora)</h5>
    </div>
    <div class="modal-body">
        <input type="hidden" id="boletoNotaId" value="" />
        <p style="margin-bottom:8px">Valor da nota fiscal: <strong id="boletoValorNota">—</strong></p>
        <div class="control-group">
            <label for="boletoParcelas"><strong>Parcelas</strong></label>
            <select id="boletoParcelas" class="span12">
                <?php for ($p = 1; $p <= 12; $p++) { ?>
                    <option value="<?php echo $p; ?>"><?php echo $p; ?>x<?php echo $p === 1 ? ' (à vista)' : ''; ?></option>
                <?php } ?>
            </select>
            <span style="color:#999;font-size:11px">Cada parcela é um boleto próprio, vencendo a partir da data do 1º vencimento (intervalo configurável abaixo). Mínimo de R$ 5,00 por parcela.</span>
        </div>
        <div class="control-group">
            <label for="boletoVencimento"><strong>1º vencimento</strong></label>
            <input type="date" id="boletoVencimento" class="span12" />
            <span style="color:#999;font-size:11px">Deixe em branco para usar o prazo padrão configurado na Cora.</span>
        </div>
        <div class="control-group" id="boletoIntervaloBox" style="display:none">
            <label for="boletoIntervaloTipo"><strong>Vencimento da 2ª parcela em diante</strong></label>
            <select id="boletoIntervaloTipo" class="span12">
                <option value="mensal">A cada mês (mesmo dia)</option>
                <option value="dias">A cada N dias</option>
            </select>
            <div id="boletoIntervaloDiasBox" style="display:none;margin-top:6px">
                <label for="boletoIntervaloDias">Dias entre as parcelas</label>
                <input type="number" id="boletoIntervaloDias" class="span12" min="1" max="365" value="30" />
            </div>
            <span style="color:#999;font-size:11px">Define o espaçamento a partir da 2ª parcela: por mês (mesmo dia) ou por um prazo em dias a contar do 1º vencimento.</span>
        </div>
        <div id="boletoResumoParcela" style="color:#555;font-size:12px;margin-top:4px"></div>
    </div>
    <div class="modal-footer" style="display:flex;justify-content:center">
        <button type="button" class="button btn btn-warning" data-dismiss="modal">
            <span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Cancelar</span>
        </button>
        <button type="button" id="boletoConfirmarGerar" class="button btn btn-success">
            <span class="button__icon"><i class="bx bx-dollar"></i></span><span class="button__text2">Gerar</span>
        </button>
    </div>
</div>
<script type="text/javascript">
(function () {
    var urlGerar = "<?php echo site_url('cobrancas/gerarPorNota'); ?>";
    var urlVerificar = "<?php echo site_url('cobrancas/verificarPagamento'); ?>";
    var urlSimular = "<?php echo site_url('cobrancas/simularPagamentoCora'); ?>";

    function boletoMoney(v) {
        return 'R$ ' + (parseFloat(v) || 0).toFixed(2).replace('.', ',');
    }

    // Descreve o intervalo escolhido para a 2ª parcela em diante.
    function boletoIntervaloTexto() {
        if ($('#boletoIntervaloTipo').val() === 'dias') {
            var d = parseInt($('#boletoIntervaloDias').val(), 10) || 30;
            return 'a cada ' + d + ' dia(s)';
        }
        return 'mensal (mesmo dia)';
    }

    // Atualiza o resumo do parcelamento (valor por parcela e intervalo) no modal.
    function boletoAtualizarResumo() {
        var valor = parseFloat($('#boletoNotaId').data('valor')) || 0;
        var parcelas = parseInt($('#boletoParcelas').val(), 10) || 1;
        // O intervalo só faz sentido a partir de 2 parcelas.
        $('#boletoIntervaloBox').toggle(parcelas > 1);
        if (parcelas <= 1) {
            $('#boletoResumoParcela').html('Boleto único de <strong>' + boletoMoney(valor) + '</strong>.');
            return;
        }
        var porParcela = Math.floor((valor * 100) / parcelas) / 100;
        $('#boletoResumoParcela').html(parcelas + ' boletos de aprox. <strong>' + boletoMoney(porParcela) + '</strong>, vencimento ' + boletoIntervaloTexto() + ' (a 1ª ajusta as diferenças de centavos).');
    }

    // Abre o modal de geração (à vista ou parcelado) para a nota clicada.
    $(document).on('click', '.btn-gerar-boleto', function (e) {
        e.preventDefault();
        var $btn = $(this);
        $('#boletoNotaId').val($btn.data('nota')).data('valor', $btn.data('valor'));
        $('#boletoValorNota').text(boletoMoney($btn.data('valor')));
        $('#boletoParcelas').val('1');
        $('#boletoVencimento').val('');
        $('#boletoIntervaloTipo').val('mensal');
        $('#boletoIntervaloDias').val('30');
        $('#boletoIntervaloDiasBox').hide();
        $('#boletoConfirmarGerar').prop('disabled', false).html("<span class='button__icon'><i class='bx bx-dollar'></i></span><span class='button__text2'>Gerar</span>");
        boletoAtualizarResumo();
        $('#modal-gerar-boleto').modal('show');
    });

    $('#boletoParcelas').on('change', boletoAtualizarResumo);
    $('#boletoIntervaloTipo').on('change', function () {
        $('#boletoIntervaloDiasBox').toggle($(this).val() === 'dias');
        boletoAtualizarResumo();
    });
    $('#boletoIntervaloDias').on('input', boletoAtualizarResumo);

    // Confirma a geração do(s) boleto(s) conforme parcelas/vencimento escolhidos.
    $('#boletoConfirmarGerar').on('click', function () {
        var $btn = $(this);
        var notaId = $('#boletoNotaId').val();
        $btn.prop('disabled', true).html("<span class='button__icon'><i class='bx bx-loader bx-spin'></i></span><span class='button__text2'>Gerando...</span>");
        $.ajax({
            type: 'POST',
            url: urlGerar,
            dataType: 'json',
            data: {
                nota_id: notaId,
                parcelas: $('#boletoParcelas').val(),
                vencimento: $('#boletoVencimento').val(),
                intervalo_tipo: $('#boletoIntervaloTipo').val(),
                intervalo_dias: $('#boletoIntervaloDias').val()
            },
            success: function () {
                $('#modal-gerar-boleto').modal('hide');
                swal({ type: 'success', title: 'Boleto gerado!', text: 'Boleto híbrido (boleto + PIX) criado com sucesso.' },
                    function () { location.reload(); });
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Erro ao gerar boleto.';
                swal({ type: 'error', title: 'Atenção', text: msg });
                $btn.prop('disabled', false).html("<span class='button__icon'><i class='bx bx-dollar'></i></span><span class='button__text2'>Gerar</span>");
            }
        });
    });

    // Verificar pagamento (sincroniza status com a Cora)
    $(document).on('click', '.btn-verificar-boleto', function (e) {
        e.preventDefault();
        var $link = $(this);
        var id = $link.data('id');
        var $badge = $('#boleto-nota-' + $link.data('nota')).find('.badge-status-boleto[data-id="' + id + '"]');
        var htmlOriginal = $link.html();
        $link.html("<i class='bx bx-loader bx-spin bx-xs'></i>");
        $.ajax({
            type: 'POST',
            url: urlVerificar,
            dataType: 'json',
            data: { idCobranca: id },
            success: function (data) {
                $badge.text(data.label || data.status);
                if (['PAID', 'RECEIVED', 'CONFIRMED'].indexOf(data.status) !== -1) {
                    $badge.css({ 'background-color': '#4d9c79', 'border-color': '#4d9c79' });
                    swal({ type: 'success', title: 'Pago!', text: 'Pagamento confirmado.' },
                        function () { location.reload(); });
                } else {
                    swal({ type: 'info', title: 'Status atualizado', text: data.label || data.status });
                    $link.html(htmlOriginal);
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Erro ao verificar pagamento.';
                swal({ type: 'error', title: 'Atenção', text: msg });
                $link.html(htmlOriginal);
            }
        });
    });

    // Simular pagamento (somente ambiente de Stage/homologação)
    $(document).on('click', '.btn-simular-pgto', function (e) {
        e.preventDefault();
        var $link = $(this);
        var id = $link.data('id');
        var htmlOriginal = $link.html();
        swal({
            title: 'Simular pagamento?',
            text: 'Isto marca o boleto como pago no ambiente de teste da Cora (homologação) para validar a baixa automática.',
            type: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, simular',
            cancelButtonText: 'Cancelar'
        }, function (ok) {
            if (!ok) { return; }
            $link.html("<i class='bx bx-loader bx-spin bx-xs'></i>");
            $.ajax({
                type: 'POST',
                url: urlSimular,
                dataType: 'json',
                data: { idCobranca: id },
                success: function (data) {
                    swal({ type: 'success', title: 'Pagamento simulado!', text: 'Status atual: ' + (data.status || '—') + '. A baixa é confirmada quando a Cora liquidar (status PAID).' },
                        function () { location.reload(); });
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Erro ao simular pagamento.';
                    swal({ type: 'error', title: 'Atenção', text: msg });
                    $link.html(htmlOriginal);
                }
            });
        });
    });

    // Copiar código PIX (copia e cola)
    $(document).on('click', '.btn-copiar-pix', function (e) {
        e.preventDefault();
        var pix = $(this).data('pix');
        var done = function () { swal({ type: 'success', title: 'PIX copiado!', text: 'Cole no app do banco para pagar.' }); };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(pix).then(done, function () { window.prompt('Copie o código PIX:', pix); });
        } else {
            window.prompt('Copie o código PIX:', pix);
        }
    });
})();
</script>
<?php } ?>
