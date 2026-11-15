<link rel="stylesheet" href="<?= base_url() ?>assets/css/obras.css?v=<?= asset_versao('assets/css/obras.css') ?>">

<div id="obra-material" data-buscar-url="<?= site_url('obramaterial/buscarProduto') ?>" data-modo="entregar">
    <div class="widget-box">
        <div class="ui-cabecalho">
            <div>
                <h1 class="ui-titulo"><i class="bx bx-up-arrow-circle"></i> Entregar material à equipe</h1>
                <p class="ui-subtitulo">Projeto: <?= html_escape($obra->nome) ?>. Registre a saída do material do almoxarifado para uma equipe.</p>
            </div>
        </div>
        <div class="widget-content row">

            <?php if ($saldos): ?>
                <div class="col-md-12" style="margin-left:0"><small class="scanner-status">Saldo disponível:
                    <?php foreach ($saldos as $s): ?>
                        <span class="ui-selo"><?= html_escape($s->descricao) ?>: <?= rtrim(rtrim(number_format((float) $s->saldo, 3, ',', '.'), '0'), ',') ?><?= $s->unidade ? ' ' . html_escape($s->unidade) : '' ?></span>
                    <?php endforeach; ?>
                </small></div>
            <?php endif; ?>

            <form class="row" action="<?= current_url() ?>" method="post">
                <div class="col-md-12"><div class="row">
                    <div class="col-md-4" style="margin-left:0">
                        <label>Equipe</label>
                        <select name="equipe_id" class="w-100 form-select">
                            <option value="">— Selecione —</option>
                            <?php foreach ($equipes as $q): ?><option value="<?= $q->equipe_id ?>"><?= html_escape($q->nome) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4"><label>Recebedor (nome)</label><input type="text" class="w-100 form-control" name="recebedor_nome"></div>
                    <div class="col-md-4">
                        <label>Etapa (opcional)</label>
                        <select name="etapa_id" class="w-100 form-select">
                            <option value="">—</option>
                            <?php foreach ($etapas as $e): ?><option value="<?= $e->idEtapa ?>"><?= html_escape($e->nome) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div></div>

                <div class="col-md-12" style="margin-left:0">
                    <div class="scanner-box">
                        <div class="scanner-actions">
                            <input class="form-control" type="text" id="cod-barras" placeholder="Leia ou digite o código de barras" style="max-width:280px">
                            <button id="btn-add-cod" class="button btn btn-sm btn-dark"><span class="button__icon"><i class='bx bx-plus'></i></span><span class="button__text2">Adicionar</span></button>
                            <button id="btn-scan" class="button btn btn-sm btn-primary"><span class="button__icon"><i class='bx bx-barcode-reader'></i></span><span class="button__text2">Ler código</span></button>
                            <button id="btn-add-manual" class="button btn btn-sm btn-success"><span class="button__icon"><i class='bx bx-list-plus'></i></span><span class="button__text2">Item manual</span></button>
                            <span class="scanner-status" id="scanner-status"></span>
                        </div>
                        <video id="scanner-video" style="display:none" playsinline></video>
                    </div>
                </div>

                <div class="col-md-12" style="margin-left:0">
                    <table class="table table-bordered itens-material">
                        <thead><tr><th>Descrição</th><th>Cód. barras</th><th>Un.</th><th>Qtd. entregue</th><th></th></tr></thead>
                        <tbody id="itens-tbody"></tbody>
                    </table>
                </div>

                <div class="col-md-6" style="margin-left:0">
                    <label>Fotos (opcional)</label>
                    <input class="form-control" type="file" id="fotos-input" accept="image/*" capture="environment" multiple>
                    <div id="fotos-hidden"></div>
                    <div id="fotos-prev" class="obra-fotos-prev"></div>
                </div>
                <div class="col-md-6">
                    <label>Assinatura do recebedor</label>
                    <canvas id="assinatura" class="assinatura-pad" width="420" height="150"></canvas>
                    <input type="hidden" name="assinatura" id="assinatura_hidden">
                    <div><a href="#" id="assinatura_limpar" class="button btn btn-sm btn-warning"><span class="button__text2">Limpar</span></a></div>
                </div>

                <div class="col-md-12" style="margin-left:0"><label>Observação</label><textarea class="w-100 form-control" name="observacao" rows="2"></textarea></div>

                <div class="col-md-12" style="padding:1%; margin-left:0"><div class="row"><div class="col-md-6 offset-md-3 obra-actionbar" style="display:flex;justify-content:center">
                    <button class="button btn btn-success"><span class="button__icon"><i class='bx bx-save'></i></span><span class="button__text2">Confirmar entrega</span></button>
                    <a href="<?= site_url('obras/visualizar/' . $obra->idObra) ?>" class="button btn btn-sm btn-warning"><span class="button__icon"><i class="bx bx-undo"></i></span><span class="button__text2">Voltar</span></a>
                </div></div></div>
            </form>
        </div>
    </div>
</div>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/obra-scanner.js"></script>
