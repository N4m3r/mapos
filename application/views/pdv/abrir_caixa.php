<div class="ui-cabecalho">
    <div>
        <h1><i class="bx bx-store"></i> PDV - abrir caixa</h1>
        <p>Para vender no PDV, abra o seu caixa informando o dinheiro que está na gaveta (fundo de troco).</p>
    </div>
</div>
<div class="widget-box">
    <div class="widget-content">
        <form action="<?= site_url('pdv/abrirCaixa') ?>" method="post" style="max-width:420px">
            <label for="valor_abertura">Valor de abertura (R$)</label>
            <input type="text" id="valor_abertura" name="valor_abertura" inputmode="decimal" value="0,00" style="font-size:20px; width:180px; text-align:right">
            <label for="observacao">Observação (opcional)</label>
            <input type="text" id="observacao" name="observacao" maxlength="200" style="width:100%">
            <div class="ui-rodape-form" style="justify-content:flex-start">
                <button type="submit" class="button btn btn-success"><span class="button__icon"><i class="bx bx-lock-open"></i></span><span class="button__text2">Abrir caixa</span></button>
                <a href="<?= site_url('caixa') ?>" class="button btn btn-info"><span class="button__icon"><i class="bx bx-history"></i></span><span class="button__text2">Histórico de caixas</span></a>
            </div>
        </form>
    </div>
</div>
