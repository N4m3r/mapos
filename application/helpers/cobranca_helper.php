<?php

/**
 * Funcoes puras (sem CodeIgniter) da tela de cobrancas: resumo do que sera
 * cobrado, estimativa informativa de impostos e KPIs da listagem.
 */

if (! function_exists('cobranca_resumo')) {
    /**
     * Normaliza itens e totais (valores em reais, 2 casas).
     * Item de entrada: ['descricao'|'nome', 'quantidade', 'preco'|'unitario', 'subTotal'?].
     */
    function cobranca_resumo(array $produtos, array $servicos, float $desconto, float $outros = 0): array
    {
        $norm = static function (array $itens, string $tipo): array {
            $out = [];
            foreach ($itens as $i) {
                $i = (array) $i;
                $qtd = (float) ($i['quantidade'] ?? $i['qtd'] ?? 1);
                if ($qtd <= 0) {
                    $qtd = 1;
                }
                $unit = round((float) ($i['preco'] ?? $i['unitario'] ?? 0), 2);
                $sub = isset($i['subTotal']) && $i['subTotal'] !== '' ? round((float) $i['subTotal'], 2) : round($qtd * $unit, 2);
                $out[] = [
                    'tipo' => $tipo,
                    'descricao' => (string) ($i['descricao'] ?? $i['nome'] ?? ''),
                    'qtd' => $qtd,
                    'unitario' => $unit,
                    'subtotal' => $sub,
                    'chave' => isset($i['chave']) ? (string) $i['chave'] : null,
                ];
            }

            return $out;
        };

        $p = $norm($produtos, 'produto');
        $s = $norm($servicos, 'servico');
        $subP = round(array_sum(array_column($p, 'subtotal')), 2);
        $subS = round(array_sum(array_column($s, 'subtotal')), 2);
        $desconto = round(max(0, $desconto), 2);
        $outros = round($outros, 2);

        return [
            'itens' => array_merge($p, $s),
            'subtotal_produtos' => $subP,
            'subtotal_servicos' => $subS,
            'outros' => $outros,
            'desconto' => $desconto,
            'total' => round(max(0, $subP + $subS + $outros - $desconto), 2),
        ];
    }
}

if (! function_exists('cobranca_impostos_estimados')) {
    /**
     * ESTIMATIVA informativa (nao e apuracao fiscal). Retorna
     * ['itens' => [[rotulo, percentual, valor, nota, cobrado]], 'liquido_estimado', 'aviso'].
     * So o DAS reduz o liquido; CBS/IBS de 2026 sao apenas destacados na nota.
     */
    function cobranca_impostos_estimados(float $valor, ?object $config): array
    {
        $r = [
            'itens' => [],
            'liquido_estimado' => round($valor, 2),
            'aviso' => 'Estimativa apenas informativa; não altera o valor cobrado do cliente.',
        ];
        if ($config === null || $valor <= 0) {
            return $r;
        }

        $das = (float) ($config->simples_aliquota_efetiva ?? 0);
        if ($das > 0) {
            $v = round($valor * $das / 100, 2);
            $r['itens'][] = ['rotulo' => 'DAS (Simples Nacional)', 'percentual' => $das, 'valor' => $v, 'nota' => 'Imposto estimado a recolher', 'cobrado' => false];
            $r['liquido_estimado'] = round($valor - $v, 2);
        }

        if (! empty($config->reforma_ativa)) {
            $nota = '2026: destacado na nota, não é cobrado';
            $ibs = (float) ($config->reforma_ibs_uf ?? 0) + (float) ($config->reforma_ibs_mun ?? 0);
            $cbs = (float) ($config->reforma_cbs ?? 0);
            $r['itens'][] = ['rotulo' => 'CBS', 'percentual' => $cbs, 'valor' => round($valor * $cbs / 100, 2), 'nota' => $nota, 'cobrado' => false];
            $r['itens'][] = ['rotulo' => 'IBS (UF + Mun.)', 'percentual' => $ibs, 'valor' => round($valor * $ibs / 100, 2), 'nota' => $nota, 'cobrado' => false];
        }

        return $r;
    }
}

if (! function_exists('cobranca_impostos_ano')) {
    /**
     * IVA-T10: estimativa por ano. CBS/IBS usam as aliquotas de $parametrosAno
     * (tributos_parametros); $regime 'das' (IBS/CBS no DAS) ou 'regular' (recolhido
     * por fora) do semestre. Mesmo formato de cobranca_impostos_estimados, mais
     * 'credito_cliente' => ['texto', 'percentual'] (percentual so no regime regular).
     * ESTIMATIVA informativa; nao altera o valor cobrado.
     */
    function cobranca_impostos_ano(float $valor, ?object $config, array $parametrosAno, string $regime, int $ano): array
    {
        if (! function_exists('tributacao_texto_credito_cliente')) {
            require_once __DIR__ . '/tributacao_helper.php';
        }
        $regime = $regime === 'regular' ? 'regular' : 'das';
        $cbs = (float) ($parametrosAno['cbs'] ?? 0);
        $ibs = (float) ($parametrosAno['ibs_uf'] ?? 0) + (float) ($parametrosAno['ibs_mun'] ?? 0);
        $sufixo = (! empty($parametrosAno['estimado']) || empty($parametrosAno['confirmado'])) ? ' (alíquota estimada)' : '';
        if ($ano === 2026) {
            $nota = '2026: destacado na nota, não é cobrado';
        } else {
            $nota = $regime === 'regular' ? 'Recolhido pelo regime regular' : 'Incluído no DAS';
        }

        $r = [
            'itens' => [],
            'liquido_estimado' => round($valor, 2),
            'aviso' => 'Estimativa apenas informativa; não altera o valor cobrado do cliente.',
            'credito_cliente' => [
                'texto' => tributacao_texto_credito_cliente($regime),
                'percentual' => $regime === 'regular' ? round($cbs + $ibs, 4) : null,
            ],
        ];
        if ($config === null || $valor <= 0) {
            return $r;
        }

        $das = (float) ($config->simples_aliquota_efetiva ?? 0);
        if (in_array((int) ($config->op_simp_nac ?? 0), [2, 3], true) && $das > 0) {
            $v = round($valor * $das / 100, 2);
            $r['itens'][] = ['rotulo' => 'DAS (Simples Nacional)', 'percentual' => $das, 'valor' => $v, 'nota' => 'Imposto estimado a recolher', 'cobrado' => false];
            $r['liquido_estimado'] = round($valor - $v, 2);
        }

        if (! empty($config->reforma_ativa)) {
            $r['itens'][] = ['rotulo' => 'CBS' . $sufixo, 'percentual' => $cbs, 'valor' => round($valor * $cbs / 100, 2), 'nota' => $nota, 'cobrado' => false];
            $r['itens'][] = ['rotulo' => 'IBS (UF + Mun.)' . $sufixo, 'percentual' => $ibs, 'valor' => round($valor * $ibs / 100, 2), 'nota' => $nota, 'cobrado' => false];
        }

        return $r;
    }
}

if (! function_exists('cobranca_status_cancelados')) {
    /** Status (minúsculos, de qualquer gateway) que contam como cobrança cancelada. */
    function cobranca_status_cancelados(): array
    {
        return ['canceled', 'cancelled', 'cancelada', 'expired', 'refunded', 'rejected', 'charged_back', 'unpaid', 'refund_requested', 'chargeback_requested', 'draft'];
    }
}

if (! function_exists('cobranca_classificar_status')) {
    /**
     * Classifica o status bruto (de qualquer gateway) em: paga | cancelada | vencida | pendente.
     * Pago: paid, settled, identified, approved, authorized, received, confirmed, received_in_cash, dunning_received.
     * Cancelado: canceled/cancelled/cancelada, expired, refunded, rejected, charged_back, unpaid, draft e afins.
     * Vencida: LATE, OVERDUE, ou pendente com expire_at anterior a $hoje.
     * Qualquer outro (new, waiting, OPEN, PENDING, in_process...) e pendente.
     */
    function cobranca_classificar_status(?string $status, ?string $expireAt, string $hoje): string
    {
        $s = strtolower(trim((string) $status));
        $pagos = ['paid', 'settled', 'identified', 'approved', 'authorized', 'received', 'confirmed', 'received_in_cash', 'dunning_received'];
        $cancelados = cobranca_status_cancelados();
        if (in_array($s, $pagos, true)) {
            return 'paga';
        }
        if (in_array($s, $cancelados, true)) {
            return 'cancelada';
        }
        if (in_array($s, ['late', 'overdue'], true)) {
            return 'vencida';
        }
        if ($expireAt && substr($expireAt, 0, 10) < $hoje) {
            return 'vencida';
        }

        return 'pendente';
    }
}

if (! function_exists('cobranca_kpis')) {
    /**
     * KPIs: a_receber (pendentes nao vencidas), vencidas, pagas_mes (pagas com
     * expire_at no mes de $hoje). Cada um: ['qtd', 'total'] com total em REAIS
     * (a coluna cobrancas.total vem em centavos).
     */
    function cobranca_kpis(array $cobrancas, string $hoje): array
    {
        $k = [
            'a_receber' => ['qtd' => 0, 'total' => 0.0],
            'vencidas' => ['qtd' => 0, 'total' => 0.0],
            'pagas_mes' => ['qtd' => 0, 'total' => 0.0],
        ];
        $mes = substr($hoje, 0, 7);
        foreach ($cobrancas as $c) {
            $c = (object) $c;
            $cls = cobranca_classificar_status($c->status ?? '', $c->expire_at ?? null, $hoje);
            $v = ((float) ($c->total ?? 0)) / 100;
            if ($cls === 'pendente') {
                $k['a_receber']['qtd']++;
                $k['a_receber']['total'] += $v;
            } elseif ($cls === 'vencida') {
                $k['vencidas']['qtd']++;
                $k['vencidas']['total'] += $v;
            } elseif ($cls === 'paga' && substr((string) ($c->expire_at ?? ''), 0, 7) === $mes) {
                $k['pagas_mes']['qtd']++;
                $k['pagas_mes']['total'] += $v;
            }
        }
        foreach ($k as &$x) {
            $x['total'] = round($x['total'], 2);
        }

        return $k;
    }
}

if (! function_exists('cobranca_prazo_texto')) {
    /** "vence em X dias", "vence hoje" ou "vencida ha X dias". */
    function cobranca_prazo_texto(?string $expireAt, string $hoje): string
    {
        if (! $expireAt) {
            return '';
        }
        $d = (int) round((strtotime(substr($expireAt, 0, 10)) - strtotime($hoje)) / 86400);
        if ($d === 0) {
            return 'vence hoje';
        }

        return $d > 0 ? 'vence em ' . $d . ($d === 1 ? ' dia' : ' dias') : 'vencida há ' . abs($d) . (abs($d) === 1 ? ' dia' : ' dias');
    }
}

if (! function_exists('cobranca_chave_item')) {
    /** Chave de um item do documento: 'p:<id>' (produto) ou 's:<id>' (serviço). */
    function cobranca_chave_item(string $tipo, $id): string
    {
        return ($tipo === 'servico' ? 's:' : 'p:') . (int) $id;
    }
}

if (! function_exists('cobranca_itens_cobrados')) {
    /**
     * O que já foi cobrado de um documento, pelas cobranças existentes.
     * Canceladas não contam. Cobrança sem itens_ref = documento inteiro
     * (cobranças antigas ou "tudo junto" sem nada cobrado antes).
     *
     * @return array{inteiro: bool, chaves: string[]}
     */
    function cobranca_itens_cobrados(array $cobrancas): array
    {
        $chaves = [];
        foreach ($cobrancas as $c) {
            $c = (array) $c;
            if (cobranca_classificar_status($c['status'] ?? '', null, '0000-00-00') === 'cancelada') {
                continue;
            }
            $ref = trim((string) ($c['itens_ref'] ?? ''));
            if ($ref === '') {
                return ['inteiro' => true, 'chaves' => []];
            }
            foreach (explode(',', $ref) as $k) {
                if ($k !== '') {
                    $chaves[$k] = true;
                }
            }
        }

        return ['inteiro' => false, 'chaves' => array_keys($chaves)];
    }
}

if (! function_exists('cobranca_montar_lotes')) {
    /**
     * Divide os itens escolhidos em cobranças: 'junto' = uma cobrança com
     * todos; 'separado' = uma cobrança por item. Só aceita chaves que estão
     * em $disponiveis e mantém a ordem delas.
     */
    function cobranca_montar_lotes(array $escolhidos, array $disponiveis, string $agrupar): array
    {
        $sel = array_values(array_intersect($disponiveis, $escolhidos));
        if (! $sel) {
            return [];
        }
        if ($agrupar === 'separado') {
            return array_map(static function ($k) {
                return [$k];
            }, $sel);
        }

        return [$sel];
    }
}

if (! function_exists('cobranca_rotulo_lote')) {
    /**
     * Complemento da descrição da cobrança: '' (documento inteiro),
     * ' - Serviços', ' - Produtos', ' - <nome do item>' ou ' - N itens'.
     */
    function cobranca_rotulo_lote(array $lote, array $todas, array $nomes): string
    {
        if (! array_diff($todas, $lote)) {
            return '';
        }
        if (count($lote) === 1) {
            return ' - ' . ($nomes[$lote[0]] ?? $lote[0]);
        }
        $tipos = array_unique(array_map(static function ($k) {
            return substr($k, 0, 1);
        }, $lote));
        if ($tipos === ['s']) {
            return ' - Serviços';
        }
        if ($tipos === ['p']) {
            return ' - Produtos';
        }

        return ' - ' . count($lote) . ' itens';
    }
}

if (! function_exists('cobranca_ratear_centavos')) {
    /**
     * Divide $totalCents entre itens na proporção de $pesos (valores brutos).
     * A soma do resultado é sempre exatamente $totalCents: a sobra de
     * arredondamento vai para o primeiro item. Pesos zerados recebem 0.
     */
    function cobranca_ratear_centavos(array $pesos, int $totalCents): array
    {
        $soma = array_sum(array_map('floatval', $pesos));
        if ($soma <= 0 || ! $pesos) {
            return array_fill(0, count($pesos), 0);
        }
        $partes = [];
        foreach (array_values($pesos) as $p) {
            $partes[] = (int) floor($totalCents * (float) $p / $soma);
        }
        $partes[0] += $totalCents - array_sum($partes);

        return $partes;
    }
}

if (! function_exists('cobranca_split_estimado')) {
    /**
     * IVA-T15: estimativa de IBS/CBS contida no total da cobrança e do líquido
     * (total - IBS - CBS), em reais com 2 casas. Sem reforma ativa: zeros e
     * líquido = total. Informativo; não altera o valor cobrado.
     *
     * @return array{v_ibs: float, v_cbs: float, v_liquido: float}
     */
    function cobranca_split_estimado(int $totalCents, ?object $config): array
    {
        $total = round($totalCents / 100, 2);
        if ($config === null || empty($config->reforma_ativa) || $total <= 0) {
            return ['v_ibs' => 0.0, 'v_cbs' => 0.0, 'v_liquido' => $total];
        }

        $aliqIbs = (float) ($config->reforma_ibs_uf ?? 0) + (float) ($config->reforma_ibs_mun ?? 0);
        $aliqCbs = (float) ($config->reforma_cbs ?? 0);
        $vIbs = round($total * $aliqIbs / 100, 2);
        $vCbs = round($total * $aliqCbs / 100, 2);

        return ['v_ibs' => $vIbs, 'v_cbs' => $vCbs, 'v_liquido' => round($total - $vIbs - $vCbs, 2)];
    }
}
