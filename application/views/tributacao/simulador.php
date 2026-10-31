<?php
$campos = [
    ['faturamento_mensal', 'Faturamento mensal (R$)', 'Média dos últimos 6 meses das notas autorizadas.'],
    ['perc_clientes_empresa', 'Clientes empresa (%)', 'Parte das vendas para CNPJ, que aproveitam crédito.'],
    ['aliquota_das_efetiva', 'Alíquota efetiva do DAS (%)', 'Vem das Configurações Fiscais.'],
    ['parcela_ibscbs_no_das', 'Parte do DAS que é IBS/CBS (%)', 'Estimativa: depende da tabela/Anexo do Simples. Confirme com o contador.'],
    ['aliquota_cbs', 'Alíquota CBS (%)', 'Do ano de 2027.'],
    ['aliquota_ibs', 'Alíquota IBS (%)', 'Estadual + municipal do ano.'],
    ['creditos_compras_mensais', 'Créditos das compras por mês (R$)', 'Média do IBS/CBS das compras pagas.'],
];
?>
<style nonce="<?= csp_nonce() ?>">
    .sim-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(230px,1fr)); gap:12px; margin-bottom:14px; }
    .sim-grid label { font-weight:bold; display:block; }
    .sim-grid small { color:#777; display:block; }
    .sim-grid input { width:100%; box-sizing:border-box; }
    .sim-quadro { border:1px solid #d0d0d0; border-radius:8px; padding:14px; background:#fafafa; }
    .sim-quadro.sim-melhor { border:2px solid #3a3; }
    .sim-quadro h4 { margin-top:0; }
    .sim-linha { display:flex; justify-content:space-between; gap:10px; padding:3px 0; }
    .sim-reco { margin-top:14px; padding:12px; border-radius:8px; background:#eef6ff; }
    body.dark-mode .sim-quadro, .dark .sim-quadro, [data-theme="dark"] .sim-quadro { background:#2a2d33; border-color:#444; color:#e6e6e6; }
    body.dark-mode .sim-reco, .dark .sim-reco, [data-theme="dark"] .sim-reco { background:#22303f; color:#e6e6e6; }
    body.dark-mode .sim-grid small, .dark .sim-grid small, [data-theme="dark"] .sim-grid small { color:#aaa; }
</style>
<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-content" style="padding:16px">
                <div class="ui-cabecalho">
                    <div>
                        <h1 class="ui-titulo"><i class="fas fa-balance-scale"></i> Simulador: continuar no DAS ou usar o regime regular?</h1>
                        <p class="ui-subtitulo">A escolha do Simples é feita uma vez por semestre (de 1 a 31 de março de 2027). Altere os valores e veja o resultado.</p>
                    </div>
                </div>
                <?php if (! empty($parametros_estimado)) { ?>
                    <div class="alert alert-warning">As alíquotas de 2027 ainda não foram confirmadas no sistema; estamos usando uma estimativa.</div>
                <?php } ?>
                <div class="sim-grid">
                    <?php foreach ($campos as [$k, $rot, $dica]) { ?>
                        <div>
                            <label for="sim-<?= $k ?>"><?= html_escape($rot) ?></label>
                            <input type="number" step="0.01" min="0" id="sim-<?= $k ?>" data-sim="<?= $k ?>" value="<?= html_escape((string) $padrao[$k]) ?>">
                            <small><?= html_escape($dica) ?></small>
                        </div>
                    <?php } ?>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px">
                    <?php foreach ([['das', 'Continuar no DAS'], ['regular', 'Regime regular de IBS/CBS']] as [$c, $tit]) { ?>
                        <div class="sim-quadro" id="sim-q-<?= $c ?>">
                            <h4><?= $tit ?></h4>
                            <div class="sim-linha"><span>Imposto total do mês</span><strong data-out="<?= $c ?>.imposto_total"></strong></div>
                            <div class="sim-linha"><span>Crédito que o cliente empresa recebe</span><strong data-out="<?= $c ?>.credito_cliente"></strong></div>
                            <div class="sim-linha"><span>Custo efetivo (imposto − crédito do cliente)</span><strong data-out="<?= $c ?>.custo_efetivo"></strong></div>
                            <div class="sim-linha"><span>Em % do faturamento</span><strong data-out="<?= $c ?>.perc_faturamento"></strong></div>
                        </div>
                    <?php } ?>
                </div>
                <div class="sim-reco" id="sim-reco"></div>
            </div>
        </div>
    </div>
</div>
<script nonce="<?= csp_nonce() ?>" type="text/javascript">
(function () {
    var PADRAO_PARCELA = <?= (float) SIMULADOR_PARCELA_IBSCBS_DAS_PADRAO ?>;
    function brl(v) { return 'R$ ' + v.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
    function r2(v) { return Math.round(v * 100) / 100; }
    function clamp(v, a, b) { return Math.min(Math.max(v, a), b); }
    // Espelha simulador_ibscbs() do PHP.
    function simular(p) {
        var fat = Math.max(p.faturamento_mensal, 0), perc = clamp(p.perc_clientes_empresa, 0, 100) / 100;
        var das = Math.max(p.aliquota_das_efetiva, 0) / 100, parcela = clamp(p.parcela_ibscbs_no_das, 0, 100) / 100;
        var aliq = (Math.max(p.aliquota_cbs, 0) + Math.max(p.aliquota_ibs, 0)) / 100, cred = Math.max(p.creditos_compras_mensais, 0);
        var dasTotal = fat * das, dasIC = dasTotal * parcela;
        function cen(imp, cc) { return {imposto_total: r2(imp), credito_cliente: r2(cc), custo_efetivo: r2(imp - cc), perc_faturamento: fat > 0 ? r2((imp - cc) / fat * 100) : 0}; }
        var bruto = fat * aliq;
        // Crédito acima do débito é saldo credor (meses seguintes); não abate o DAS.
        var d = cen(dasTotal, perc * dasIC), g = cen(dasTotal - dasIC + Math.max(bruto - cred, 0), perc * bruto);
        var dif = r2(g.custo_efetivo - d.custo_efetivo), melhor, txt;
        if (Math.abs(dif) <= fat * 0.002) { melhor = 'empate'; txt = 'Os dois caminhos ficam praticamente iguais. Se seus clientes são empresas, o regime regular costuma facilitar a venda para eles; se são pessoas físicas, manter o DAS é mais simples.'; }
        else if (dif < 0) { melhor = 'regular'; txt = 'Pelos números informados, o regime regular tende a custar cerca de ' + brl(Math.abs(dif)) + ' a menos por mês, já considerando o crédito que seus clientes empresa recebem.'; }
        else { melhor = 'das'; txt = 'Pelos números informados, continuar no DAS tende a custar cerca de ' + brl(dif) + ' a menos por mês.'; }
        txt += ' Esta é uma simulação com estimativas (a parcela de IBS/CBS dentro do DAS e as alíquotas podem mudar). Decida com seu contador.';
        return {das: d, regular: g, melhor: melhor, recomendacao: txt};
    }
    var inputs = document.querySelectorAll('[data-sim]');
    function atualizar() {
        var p = {};
        inputs.forEach(function (i) { var v = parseFloat(i.value); p[i.dataset.sim] = isNaN(v) ? (i.dataset.sim === 'parcela_ibscbs_no_das' ? PADRAO_PARCELA : 0) : v; });
        var r = simular(p);
        document.querySelectorAll('[data-out]').forEach(function (el) {
            var par = el.dataset.out.split('.'), v = r[par[0]][par[1]];
            el.textContent = par[1] === 'perc_faturamento' ? v.toLocaleString('pt-BR', {minimumFractionDigits: 2}) + '%' : brl(v);
        });
        ['das', 'regular'].forEach(function (c) { document.getElementById('sim-q-' + c).classList.toggle('sim-melhor', r.melhor === c); });
        document.getElementById('sim-reco').textContent = r.recomendacao;
    }
    inputs.forEach(function (i) { i.addEventListener('input', atualizar); });
    atualizar();
})();
</script>
