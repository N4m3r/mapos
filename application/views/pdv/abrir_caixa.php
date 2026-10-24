<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="bx bx-store"></i></span>
        <h5>PDV - abrir caixa</h5>
    </div>
    <div class="widget-content">
        <p>Para vender no PDV, abra o seu caixa informando o dinheiro que está na gaveta (fundo de troco).</p>
        <form action="<?= site_url('pdv/abrirCaixa') ?>" method="post" style="max-width:420px">
            <label>Valor de abertura (R$)</label>
            <input type="text" name="valor_abertura" inputmode="decimal" value="0,00" style="font-size:20px; width:180px; text-align:right">
            <label>Observação (opcional)</label>
            <input type="text" name="observacao" maxlength="200" style="width:100%">
            <div style="margin-top:12px">
                <button type="submit" class="button btn btn-success"><span class="button__icon"><i class="bx bx-lock-open"></i></span><span class="button__text2">Abrir caixa</span></button>
                <a href="<?= site_url('caixa') ?>" class="button btn btn-info"><span class="button__icon"><i class="bx bx-history"></i></span><span class="button__text2">Histórico de caixas</span></a>
            </div>
        </form>
    </div>
</div>
