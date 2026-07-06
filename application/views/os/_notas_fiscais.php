<?php
// Partial: tabela de notas fiscais emitidas para uma OS, com o boleto/PIX (Cora)
// vinculado a cada nota.
// Espera: $notas (linhas de notas_fiscais) e $boletos (mapa nota_id => cobranca).
$notas = isset($notas) ? $notas : [];
$boletos = isset($boletos) ? $boletos : [];
$podeCancelar = $this->permission->checkPermission($this->session->userdata('permissao'), 'dNfe');
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
                        'erro' => '#FF7F00',
                        default => '#AEB404',
                    };
                    $tipoLabel = $nota->tipo === 'nfe' ? 'NF-e (produtos)' : 'NFS-e (serviços)';
                    $tipoIcon = $nota->tipo === 'nfe' ? 'bx-box' : 'bx-receipt';
                    $boleto = $boletos[$nota->idNota] ?? null;
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
                            <?php if ($boleto) {
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
                                <span class="badge badge-status-boleto" style="background-color:<?php echo $corBoleto; ?>;border-color:<?php echo $corBoleto; ?>"><?php echo html_escape($statusLabel); ?></span>
                                <br>R$ <?php echo number_format($boleto->total / 100, 2, ',', '.'); ?>
                                <?php if ($boleto->valor_iss_retido > 0) { ?>
                                    <br><span style="color:#888" title="ISS retido abatido">ISS ret.: R$ <?php echo number_format($boleto->valor_iss_retido, 2, ',', '.'); ?></span>
                                <?php } ?>
                                <div style="margin-top:4px;white-space:nowrap">
                                    <?php if (! empty($boleto->pdf)) { ?>
                                        <a href="<?php echo html_escape($boleto->pdf); ?>" target="_blank" class="btn-nwe6" title="Baixar boleto (PDF)"><i class="bx bx-barcode bx-xs"></i></a>
                                    <?php } ?>
                                    <?php if (! empty($boleto->pix)) { ?>
                                        <a href="#" class="btn-nwe6 btn-copiar-pix" data-pix="<?php echo html_escape($boleto->pix); ?>" title="Copiar código PIX (copia e cola)"><i class="bx bx-qr bx-xs"></i></a>
                                    <?php } ?>
                                    <?php if (! $pago && $podeVerBoleto) { ?>
                                        <a href="#" class="btn-nwe6 btn-verificar-boleto" data-id="<?php echo $boleto->idCobranca; ?>" data-nota="<?php echo $nota->idNota; ?>" title="Verificar pagamento"><i class="bx bx-refresh bx-xs"></i></a>
                                    <?php } ?>
                                </div>
                            <?php } elseif ($nota->status === 'autorizada' && $podeGerarBoleto) { ?>
                                <button type="button" class="btn btn-mini btn-info btn-gerar-boleto" data-nota="<?php echo $nota->idNota; ?>" title="Gerar boleto híbrido (boleto + PIX) na Cora">
                                    <i class="bx bx-dollar bx-xs"></i> Gerar Boleto/PIX
                                </button>
                            <?php } else { ?>
                                <span style="color:#999">-</span>
                            <?php } ?>
                        </td>
                        <td style="text-align:center;white-space:nowrap">
                            <?php if (in_array($nota->status, ['autorizada', 'cancelada']) && !empty($nota->xml_path)) { ?>
                                <a href="<?php echo site_url('nfe/xml/' . $nota->idNota); ?>" class="btn-nwe6" title="Baixar XML"><i class="bx bx-code-alt bx-xs"></i></a>
                            <?php } ?>
                            <?php if ($nota->status === 'autorizada') { ?>
                                <a href="<?php echo site_url('nfe/danfe/' . $nota->idNota); ?>" target="_blank" class="btn-nwe6" title="Imprimir <?php echo $nota->tipo === 'nfe' ? 'DANFE' : 'DANFSe'; ?>"><i class="bx bx-printer bx-xs"></i></a>
                            <?php } ?>
                            <?php if ($nota->status === 'autorizada' && $podeCancelar) { ?>
                                <a href="<?php echo site_url('nfe/gerenciar?status=autorizada'); ?>" class="btn-nwe4" title="Cancelar (na tela de Notas Fiscais)"><i class="bx bx-x-circle bx-xs"></i></a>
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
<script type="text/javascript">
(function () {
    var urlGerar = "<?php echo site_url('cobrancas/gerarPorNota'); ?>";
    var urlVerificar = "<?php echo site_url('cobrancas/verificarPagamento'); ?>";

    // Gerar boleto/PIX (Cora) a partir de uma nota fiscal
    $(document).on('click', '.btn-gerar-boleto', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var notaId = $btn.data('nota');
        $btn.prop('disabled', true).html("<i class='bx bx-loader bx-spin'></i> Gerando...");
        $.ajax({
            type: 'POST',
            url: urlGerar,
            dataType: 'json',
            data: { nota_id: notaId },
            success: function () {
                swal({ type: 'success', title: 'Boleto gerado!', text: 'Boleto híbrido (boleto + PIX) criado com sucesso.' },
                    function () { location.reload(); });
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Erro ao gerar boleto.';
                swal({ type: 'error', title: 'Atenção', text: msg });
                $btn.prop('disabled', false).html("<i class='bx bx-dollar bx-xs'></i> Gerar Boleto/PIX");
            }
        });
    });

    // Verificar pagamento (sincroniza status com a Cora)
    $(document).on('click', '.btn-verificar-boleto', function (e) {
        e.preventDefault();
        var $link = $(this);
        var id = $link.data('id');
        var $badge = $('#boleto-nota-' + $link.data('nota')).find('.badge-status-boleto');
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
