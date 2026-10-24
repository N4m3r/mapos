<link rel="stylesheet" href="<?= base_url() ?>assets/css/obras.css">

<div id="obra-material" data-buscar-url="<?= site_url('obramaterial/buscarProduto') ?>" data-modo="entregar">
    <div class="widget-box">
        <div class="widget-title"><span class="icon"><i class="fas fa-people-carry"></i></span><h5>Entregar material à equipe — <?= html_escape($obra->nome) ?></h5></div>
        <div class="widget-content">

            <?php if ($saldos): ?>
                <div class="span12" style="margin-left:0"><small class="scanner-status">Saldo disponível:
                    <?php foreach ($saldos as $s): ?>
                        <span class="label"><?= html_escape($s->descricao) ?>: <?= rtrim(rtrim(number_format((float) $s->saldo, 3, ',', '.'), '0'), ',') ?><?= $s->unidade ? ' ' . html_escape($s->unidade) : '' ?></span>
                    <?php endforeach; ?>
                </small></div>
            <?php endif; ?>

            <form action="<?= current_url() ?>" method="post">
                <div class="span12">
                    <div class="span4" style="margin-left:0">
                        <label>Equipe</label>
                        <select name="equipe_id" class="span12">
                            <option value="">— Selecione —</option>
                            <?php foreach ($equipes as $q): ?><option value="<?= $q->equipe_id ?>"><?= html_escape($q->nome) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="span4"><label>Recebedor (nome)</label><input type="text" class="span12" name="recebedor_nome"></div>
                    <div class="span4">
                        <label>Etapa (opcional)</label>
                        <select name="etapa_id" class="span12">
                            <option value="">—</option>
                            <?php foreach ($etapas as $e): ?><option value="<?= $e->idEtapa ?>"><?= html_escape($e->nome) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>

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

                <div class="span12" style="margin-left:0">
                    <table class="table table-bordered itens-material">
                        <thead><tr><th>Descrição</th><th>Cód. barras</th><th>Un.</th><th>Qtd. entregue</th><th></th></tr></thead>
                        <tbody id="itens-tbody"></tbody>
                    </table>
                </div>

                <div class="span6" style="margin-left:0">
                    <label>Fotos (opcional)</label>
                    <input type="file" id="fotos-input" accept="image/*" capture="environment" multiple>
                    <div id="fotos-hidden"></div>
                    <div id="fotos-prev" class="obra-fotos-prev"></div>
                </div>
                <div class="span6">
                    <label>Assinatura do recebedor</label>
                    <canvas id="assinatura" class="assinatura-pad" width="420" height="150"></canvas>
                    <input type="hidden" name="assinatura" id="assinatura_hidden">
                    <div><a href="#" id="assinatura_limpar" class="button btn btn-mini btn-warning"><span class="button__text2">Limpar</span></a></div>
                </div>

                <div class="span12" style="margin-left:0"><label>Observação</label><textarea class="span12" name="observacao" rows="2"></textarea></div>

                <div class="span12" style="padding:1%; margin-left:0"><div class="span6 offset3 obra-actionbar" style="display:flex;justify-content:center">
                    <button class="button btn btn-success"><span class="button__icon"><i class='bx bx-save'></i></span><span class="button__text2">Confirmar entrega</span></button>
                    <a href="<?= site_url('obras/visualizar/' . $obra->idObra) ?>" class="button btn btn-mini btn-warning"><span class="button__icon"><i class="bx bx-undo"></i></span><span class="button__text2">Voltar</span></a>
                </div></div>
            </form>
        </div>
    </div>
</div>
<script src="<?= base_url() ?>assets/js/obra-scanner.js"></script>
