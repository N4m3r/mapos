<?php
$this->load->config('payment_gateways');
// Expõe ao JS só nome e formas de pagamento (nunca as credenciais).
$gatewaysPublicos = [];
foreach ((array) $this->config->item('payment_gateways') as $chave => $gw) {
    if (! empty($gw['fiscal_only']) && empty($gw['avulso'])) {
        continue;
    }
    $gatewaysPublicos[$gw['library_name']] = [
        'name' => $gw['name'],
        'payment_methods' => $gw['payment_methods'],
        'aviso' => $gw['aviso_avulso'] ?? '',
    ];
}
?>

<script nonce="<?= csp_nonce() ?>">
    var paymentGatewaysConfig = <?= json_encode($gatewaysPublicos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<style>
    #modal-gerar-pagamento .cb-passo { margin-bottom: 14px; }
    #modal-gerar-pagamento .cb-passo h5 { margin: 0 0 8px; font-weight: 700; }
    #modal-gerar-pagamento .cb-itens { width: 100%; font-size: 13px; }
    #modal-gerar-pagamento .cb-itens td { padding: 2px 4px; }
    #modal-gerar-pagamento .cb-total { font-size: 26px; font-weight: 700; text-align: right; margin-top: 8px; }
    #modal-gerar-pagamento .cb-imp { border: 1px dashed currentColor; border-radius: 6px; padding: 8px 10px; margin-top: 10px; font-size: 12px; opacity: .85; }
    #modal-gerar-pagamento .cb-opcoes { display: flex; flex-wrap: wrap; gap: 8px; }
    #modal-gerar-pagamento .cb-opcao { flex: 1 1 140px; border: 2px solid rgba(128,128,128,.4); border-radius: 8px; padding: 12px; text-align: center; cursor: pointer; font-weight: 600; }
    #modal-gerar-pagamento .cb-opcao input { display: none; }
    #modal-gerar-pagamento .cb-opcao.ativa { border-color: #2d8cf0; box-shadow: 0 0 0 2px rgba(45,140,240,.25); }
    #modal-gerar-pagamento .modal-body { max-height: 70vh; overflow-y: auto; }
    #modal-gerar-pagamento #cb-modos { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    #modal-gerar-pagamento .cb-modo { text-align: left; padding: 8px 10px; margin: 0; }
    #modal-gerar-pagamento .cb-modo small { display: block; font-weight: 400; opacity: .75; margin-top: 2px; }
    #modal-gerar-pagamento .cb-grupo td { padding-top: 8px; font-weight: 700; text-transform: uppercase; font-size: 11px; opacity: .7; }
    #modal-gerar-pagamento .cb-item label { margin: 0; display: flex; gap: 6px; align-items: center; cursor: pointer; }
    #modal-gerar-pagamento .cb-item.cobrado { opacity: .5; }
    #modal-gerar-pagamento .cb-lotes { font-size: 12px; text-align: right; opacity: .85; }
</style>

<div class="modal fade" id="modal-gerar-pagamento" tabindex="-1" role="dialog" data-id="<?= html_escape($id) ?>" data-tipo="<?= html_escape($tipo) ?>" data-resumo-url="<?= site_url('cobrancas/resumo/' . $tipo . '/' . $id) ?>">
    <div class="modal-dialog" role="document">
        <form id="form-gerar-cobranca" name="cobranca" method="post" action="<?php echo base_url() . 'index.php/cobrancas/adicionar'; ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Gerar cobrança</h4>
                </div>
                <div class="modal-body">
                    <input value="<?= html_escape($id) ?>" name="id" type="hidden">
                    <input value="<?= html_escape($tipo) ?>" name="tipo" type="hidden">

                    <div class="cb-passo">
                        <h5>1. O que você vai cobrar</h5>
                        <div id="cb-resumo"><em>Carregando...</em></div>
                        <input type="hidden" id="cb-agrupar" name="agrupar" value="junto">
                    </div>

                    <div class="cb-passo">
                        <h5>2. Como o cliente vai pagar</h5>
                        <div id="cb-gateways" class="cb-opcoes"></div>
                        <div id="cb-formas" class="cb-opcoes" style="margin-top:8px"></div>
                        <div id="cb-aviso-gateway" class="alert alert-info" style="display:none;margin:8px 0 0"></div>
                        <input type="hidden" id="gateway_de_pagamento" name="gateway_de_pagamento" value="">
                        <input type="hidden" id="forma_pagamento" name="forma_pagamento" value="">
                    </div>

                    <div class="cb-passo">
                        <h5>3. Confirmar</h5>
                        <small>A cobrança será enviada ao gateway escolhido e você poderá acompanhar o status na tela de Cobranças.</small>
                    </div>
                </div>
                <div class="modal-footer" style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                    <button type="button" class="button btn btn-warning" data-dismiss="modal"><span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Cancelar</span></button>
                    <button id="payment" type="submit" class="button btn btn-info" disabled>
                        <span class="button__icon"><i class='bx bx-qr'></i></span><span class="button__text" id="payment-texto">Gerar cobrança</span></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/script-payments.js'); ?>"></script>
