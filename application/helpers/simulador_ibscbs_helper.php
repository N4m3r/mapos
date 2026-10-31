<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (! defined('SIMULADOR_PARCELA_IBSCBS_DAS_PADRAO')) {
    // Percentual do DAS que corresponde a IBS/CBS. VALOR ILUSTRATIVO: depende da tabela/Anexo do Simples
    // (repartição dos tributos) e da regulamentação final; confirme com o contador antes de decidir.
    define('SIMULADOR_PARCELA_IBSCBS_DAS_PADRAO', 30.0);
}

if (! function_exists('simulador_ibscbs')) {
    /**
     * Simulador "continuar no DAS" x "regime regular de IBS/CBS" (decisão semestral do Simples).
     * Entradas ($p): faturamento_mensal, perc_clientes_empresa (0-100), aliquota_das_efetiva (%),
     * parcela_ibscbs_no_das (% do DAS que é IBS/CBS), aliquota_cbs (%), aliquota_ibs (%), creditos_compras_mensais.
     * Por cenário: imposto_total (mês), credito_cliente (crédito que clientes empresa recebem),
     * custo_efetivo (= imposto_total − credito_cliente: quanto o imposto pesa depois de considerar o crédito
     * que você entrega aos clientes) e perc_faturamento.
     */
    function simulador_ibscbs(array $p): array
    {
        $num = static fn ($k, $def = 0.0) => (float) ($p[$k] ?? $def);
        $fat = max($num('faturamento_mensal'), 0.0);
        $perc = min(max($num('perc_clientes_empresa'), 0.0), 100.0) / 100;
        $das = max($num('aliquota_das_efetiva'), 0.0) / 100;
        $parcela = min(max($num('parcela_ibscbs_no_das', SIMULADOR_PARCELA_IBSCBS_DAS_PADRAO), 0.0), 100.0) / 100;
        $aliq = (max($num('aliquota_cbs'), 0.0) + max($num('aliquota_ibs'), 0.0)) / 100;
        $cred = max($num('creditos_compras_mensais'), 0.0);

        $dasTotal = $fat * $das;
        $dasIbsCbs = $dasTotal * $parcela;

        $r2 = static fn ($v) => round($v, 2);
        $cenario = static function (float $imposto, float $credCliente) use ($r2, $fat): array {
            return [
                'imposto_total' => $r2($imposto),
                'credito_cliente' => $r2($credCliente),
                'custo_efetivo' => $r2($imposto - $credCliente),
                'perc_faturamento' => $fat > 0 ? round(($imposto - $credCliente) / $fat * 100, 2) : 0.0,
            ];
        };

        // DAS: paga tudo no DAS; o cliente só credita o IBS/CBS contido no DAS.
        $cDas = $cenario($dasTotal, $perc * $dasIbsCbs);

        // Regular: DAS reduzido da parcela IBS/CBS + IBS/CBS por fora (alíquotas cheias − créditos de compras).
        $ibsCbsBruto = $fat * $aliq;
        // Crédito maior que o débito vira saldo credor para os meses seguintes:
        // não abate o DAS nem deixa o imposto do mês negativo.
        $ibsCbsLiquido = max($ibsCbsBruto - $cred, 0.0);
        $cReg = $cenario($dasTotal - $dasIbsCbs + $ibsCbsLiquido, $perc * $ibsCbsBruto);
        $cReg['ibscbs_liquido'] = $r2($ibsCbsLiquido);
        $cReg['saldo_credor'] = $r2(max($cred - $ibsCbsBruto, 0.0));

        $dif = $r2($cReg['custo_efetivo'] - $cDas['custo_efetivo']); // >0: regular custa mais
        if (abs($dif) <= $fat * 0.002) { // menos de 0,2% do faturamento = empate prático
            $melhor = 'empate';
            $txt = 'Os dois caminhos ficam praticamente iguais. Se seus clientes são empresas, o regime regular costuma facilitar a venda para eles; se são pessoas físicas, manter o DAS é mais simples.';
        } elseif ($dif < 0) {
            $melhor = 'regular';
            $txt = 'Pelos números informados, o regime regular tende a custar cerca de R$ ' . number_format(abs($dif), 2, ',', '.') . ' a menos por mês, já considerando o crédito que seus clientes empresa recebem.';
        } else {
            $melhor = 'das';
            $txt = 'Pelos números informados, continuar no DAS tende a custar cerca de R$ ' . number_format($dif, 2, ',', '.') . ' a menos por mês.';
        }
        $txt .= ' Esta é uma simulação com estimativas (a parcela de IBS/CBS dentro do DAS e as alíquotas podem mudar). Decida com seu contador.';

        return ['das' => $cDas, 'regular' => $cReg, 'diferenca_regular_menos_das' => $dif, 'melhor' => $melhor, 'recomendacao' => $txt];
    }
}
