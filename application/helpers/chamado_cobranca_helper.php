<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Regras visuais e de classificacao do painel de cobranca dos chamados.
 *
 * Funcoes puras (sem banco), testadas em tests/Unit/ChamadoCobrancaTest.php.
 * A classificacao de status de cobranca continua vindo de cobranca_helper.
 */
class Chamado_cobranca
{
    /** Situacoes que o painel mostra, na ordem dos filtros. */
    const STATUS = [
        'pendente_aprovacao' => ['rotulo' => 'Pendente de aprovação', 'classe' => 'alerta', 'icone' => 'bx-time-five'],
        'a_cobrar' => ['rotulo' => 'A cobrar', 'classe' => 'info', 'icone' => 'bx-dollar-circle'],
        'pendente' => ['rotulo' => 'A receber', 'classe' => 'info', 'icone' => 'bx-calendar-check'],
        'vencida' => ['rotulo' => 'Vencida', 'classe' => 'perigo', 'icone' => 'bx-error-circle'],
        'paga' => ['rotulo' => 'Paga', 'classe' => 'sucesso', 'icone' => 'bx-check-circle'],
        'cancelada' => ['rotulo' => 'Cancelada', 'classe' => 'neutro', 'icone' => 'bx-x-circle'],
    ];

    /** Situacoes com valor em aberto (entram no total a receber). */
    const EM_ABERTO = ['pendente_aprovacao', 'a_cobrar', 'pendente', 'vencida'];

    public static function rotulo($status)
    {
        return self::STATUS[$status]['rotulo'] ?? (string) $status;
    }

    public static function classe($status)
    {
        return self::STATUS[$status]['classe'] ?? 'neutro';
    }

    public static function icone($status)
    {
        return self::STATUS[$status]['icone'] ?? 'bx-receipt';
    }

    /**
     * Apenas as situacoes validas, na ordem do catalogo (para os chips).
     *
     * @param array $selecionadas
     *
     * @return string[]
     */
    public static function filtro(array $selecionadas)
    {
        $validas = array_keys(self::STATUS);

        return array_values(array_filter($validas, static function ($s) use ($selecionadas) {
            return in_array($s, $selecionadas, true);
        }));
    }

    /**
     * Soma quantidade e valor por situacao.
     *
     * @return array<string, array{qtd: int, valor: float}>
     */
    public static function kpis(array $itens)
    {
        $kpis = [];
        foreach (array_keys(self::STATUS) as $s) {
            $kpis[$s] = ['qtd' => 0, 'valor' => 0.0];
        }
        foreach ($itens as $item) {
            $s = $item['status'] ?? '';
            if (! isset($kpis[$s])) {
                continue;
            }
            $kpis[$s]['qtd']++;
            $kpis[$s]['valor'] += (float) ($item['valor'] ?? 0);
        }
        foreach ($kpis as &$k) {
            $k['valor'] = round($k['valor'], 2);
        }

        return $kpis;
    }

    /** Total em aberto (pendente de aprovacao, a cobrar, a receber e vencida). */
    public static function totalEmAberto(array $itens)
    {
        $total = 0.0;
        foreach ($itens as $item) {
            if (in_array($item['status'] ?? '', self::EM_ABERTO, true)) {
                $total += (float) ($item['valor'] ?? 0);
            }
        }

        return round($total, 2);
    }

    /**
     * Pontos para Grafico_svg::colunas com o valor de cada situacao.
     */
    public static function pontosStatus(array $itens)
    {
        $kpis = self::kpis($itens);
        $pontos = [];
        foreach (self::STATUS as $chave => $cfg) {
            if ($kpis[$chave]['qtd'] === 0) {
                continue;
            }
            $pontos[] = [
                'rotulo' => $cfg['rotulo'],
                'valor' => $kpis[$chave]['valor'],
                'dica' => $cfg['rotulo'] . "\nR$ " . self::moedaNumero($kpis[$chave]['valor']) . ' em ' . $kpis[$chave]['qtd'] . ' item(ns)',
            ];
        }

        return $pontos;
    }

    /**
     * Maiores clientes com valor em aberto, para barras horizontais.
     */
    public static function topClientes(array $itens, $limite = 8)
    {
        $porCliente = [];
        foreach ($itens as $item) {
            if (! in_array($item['status'] ?? '', self::EM_ABERTO, true)) {
                continue;
            }
            $nome = trim((string) ($item['cliente'] ?? '')) ?: 'Sem cliente';
            $porCliente[$nome] = ($porCliente[$nome] ?? 0) + (float) ($item['valor'] ?? 0);
        }
        arsort($porCliente);

        $pontos = [];
        foreach (array_slice($porCliente, 0, max(1, (int) $limite), true) as $nome => $valor) {
            $pontos[] = [
                'rotulo' => $nome,
                'valor' => round($valor, 2),
                'dica' => $nome . "\nR$ " . self::moedaNumero($valor) . ' em aberto',
            ];
        }

        return $pontos;
    }

    /** Valores aceitos em checkbox de multi-selecao: 'os:12' ou 'cob:8'. */
    public static function lerSelecao(array $brutos)
    {
        $itens = [];
        foreach ($brutos as $bruto) {
            if (! is_string($bruto) || ! preg_match('/^(os|cob):(\d+)$/', trim($bruto), $m)) {
                continue;
            }
            $chave = $m[1] . ':' . (int) $m[2];
            $itens[$chave] = true;
        }

        return array_keys($itens);
    }

    public static function moeda($valor)
    {
        return 'R$ ' . self::moedaNumero($valor);
    }

    public static function moedaNumero($valor)
    {
        return number_format((float) $valor, 2, ',', '.');
    }
}

if (! function_exists('chamado_normalizar')) {
    /**
     * Identificador de chamado informado pelo usuario: texto curto, sem tags,
     * sem espacos nas pontas. Vazio vira null (a coluna e opcional).
     */
    function chamado_normalizar($valor, $limite = 60)
    {
        $texto = trim(strip_tags((string) $valor));
        $texto = preg_replace('/\s+/', ' ', $texto);

        return $texto === '' ? null : mb_substr($texto, 0, (int) $limite);
    }
}
