<link rel="stylesheet" href="<?= base_url() ?>assets/css/obras.css">
<link rel="stylesheet" href="<?= base_url() ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script src="<?= base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>

<div id="obra-material" data-buscar-url="<?= site_url('obramaterial/buscarProduto') ?>" data-modo="receber">
    <div class="widget-box">
        <div class="widget-title"><span class="icon"><i class="fas fa-truck-loading"></i></span><h5>Receber material — <?= html_escape($obra->nome) ?></h5></div>
        <div class="widget-content">
            <form action="<?= current_url() ?>" method="post">

                <div class="span12">
                    <div class="span3" style="margin-left:0">
                        <label>Origem</label>
                        <select name="origem" class="span12">
                            <option value="cliente">Material do cliente</option>
                            <option value="compra">Compra própria</option>
                            <option value="transferencia">Transferência</option>
                            <option value="doacao">Doação</option>
                        </select>
                    </div>
                    <div class="span5"><label>Fornecedor / quem entregou</label><input type="text" class="span12" name="fornecedor_nome"></div>
                    <div class="span4"><label>Documento (NF/romaneio)</label><input type="text" class="span12" name="documento"></div>
                    <div class="span12" style="margin-left:0">
                        <span class="scanner-status"><i class='bx bx-info-circle'></i> Em <strong>Compra própria</strong>, informe o <strong>valor unitário</strong> — o material comprado pela empresa entra no custo realizado do projeto.</span>
                    </div>
                </div>

                <!-- Scanner -->
                <div class="span12" style="margin-left:0">
                    <div class="scanner-box">
                        <div class="scanner-actions">
                            <input type="text" id="cod-barras" placeholder="Leia ou digite o código de barras" style="max-width:280px">
                            <button id="btn-add-cod" class="button btn btn-mini btn-inverse"><span class="button__icon"><i class='bx bx-plus'></i></span><span class="button__text2">Adicionar</span></button>
                            <button id="btn-scan" class="button btn btn-mini btn-primary"><span class="button__icon"><i class='bx bx-barcode-reader'></i></span><span class="button__text2">Ler código</span></button>
                            <button id="btn-add-manual" class="button btn btn-mini btn-success"><span class="button__icon"><i class='bx bx-list-plus'></i></span><span class="button__text2">Item manual</span></button>
                            <span class="scanner-status" id="scanner-status"></span>
                        </div>
                        <video id="scanner-video" style="display:none" playsinline></video>
                    </div>
                </div>

                <!-- Itens -->
                <div class="span12" style="margin-left:0">
                    <table class="table table-bordered itens-material">
                        <thead><tr><th>Descrição</th><th>Cód. barras</th><th>Un.</th><th>Qtd. recebida</th><th>Valor unit. (R$)</th><th></th></tr></thead>
                        <tbody id="itens-tbody"></tbody>
                    </table>
                </div>

                <!-- Fotos + assinatura -->
                <div class="span6" style="margin-left:0">
                    <label>Fotos do material / romaneio</label>
                    <input type="file" id="fotos-input" accept="image/*" capture="environment" multiple>
                    <div id="fotos-hidden"></div>
                    <div id="fotos-prev" class="obra-fotos-prev"></div>
                </div>
                <div class="span6">
                    <label>Assinatura de quem entregou</label>
                    <canvas id="assinatura" class="assinatura-pad" width="420" height="150"></canvas>
                    <input type="hidden" name="assinatura" id="assinatura_hidden">
                    <div><a href="#" id="assinatura_limpar" class="button btn btn-mini btn-warning"><span class="button__text2">Limpar</span></a></div>
                </div>

                <div class="span12" style="margin-left:0"><label>Observação</label><textarea class="span12" name="observacao" rows="2"></textarea></div>

                <div class="span12" style="padding:1%; margin-left:0"><div class="span6 offset3 obra-actionbar" style="display:flex;justify-content:center">
                    <button class="button btn btn-success"><span class="button__icon"><i class='bx bx-save'></i></span><span class="button__text2">Confirmar recebimento</span></button>
                    <a href="<?= site_url('obras/visualizar/' . $obra->idObra) ?>" class="button btn btn-mini btn-warning"><span class="button__icon"><i class="bx bx-undo"></i></span><span class="button__text2">Voltar</span></a>
                </div></div>
            </form>
        </div>
    </div>
</div>
<script src="<?= base_url() ?>assets/js/obra-scanner.js"></script>
