<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Logica pura do painel inicial (sem banco): tons, rotulos, prazos e a serie
 * do grafico. Evita repetir switch/case de status em cada tabela da view.
 */
class Painel_inicio
{
    /** Tom visual do status (classes .ui-selo / .ui-kpi do moderno.css). */
    const TONS = [
        'Aberto' => 'info',
        'Em Andamento' => 'info',
        'Orçamento' => 'neutro',
        'Negociação' => 'neutro',
        'Aprovado' => 'neutro',
        'Aguardando Peças' => 'alerta',
        'Finalizado' => 'sucesso',
        'Faturado' => 'sucesso',
        'Cancelado' => 'perigo',
        'Não Realizado' => 'perigo',
    ];

    /** Icone boxicons por status. */
    const ICONES = [
        'Aberto' => 'bx-envelope-open',
        'Em Andamento' => 'bx-play-circle',
        'Orçamento' => 'bx-calculator',
        'Negociação' => 'bx-chat',
        'Aprovado' => 'bx-check-circle',
        'Aguardando Peças' => 'bx-package',
        'Finalizado' => 'bx-check-double',
        'Faturado' => 'bx-receipt',
        'Cancelado' => 'bx-x-circle',
        'Não Realizado' => 'bx-x-circle',
    ];

    /** Ordem em que os status aparecem no resumo e nos graficos. */
    const ORDEM_STATUS = [
        'Aberto', 'Orçamento', 'Negociação', 'Aprovado',
        'Aguardando Peças', 'Em Andamento', 'Finalizado', 'Faturado',
        'Cancelado', 'Não Realizado',
    ];

    /** Status considerados em aberto (ainda exigem acao da equipe). */
    const EM_ABERTO = ['Aberto', 'Orçamento', 'Negociação', 'Aprovado', 'Aguardando Peças', 'Em Andamento'];

    public static function tom($status)
    {
        return self::TONS[(string) $status] ?? 'neutro';
    }

    public static function icone($status)
    {
        return self::ICONES[(string) $status] ?? 'bx-file';
    }

    /**
     * Ordena as contagens por status conforme ORDEM_STATUS; status fora da
     * lista (dado antigo/importado) vao para o fim, em ordem alfabetica.
     *
     * @param array $contagens [status => quantidade]
     *
     * @return array lista de ['status', 'total', 'tom', 'icone']
     */
    public static function resumoStatus(array $contagens)
    {
        $fora = array_diff(array_keys($contagens), self::ORDEM_STATUS);
        sort($fora);
        $saida = [];
        foreach (array_merge(self::ORDEM_STATUS, $fora) as $status) {
            $total = (int) ($contagens[$status] ?? 0);
            $saida[] = [
                'status' => $status,
                'total' => $total,
                'tom' => self::tom($status),
                'icone' => self::icone($status),
            ];
        }

        return $saida;
    }

    /** Soma das contagens que representam trabalho ainda em aberto. */
    public static function totalEmAberto(array $contagens)
    {
        $total = 0;
        foreach (self::EM_ABERTO as $status) {
            $total += (int) ($contagens[$status] ?? 0);
        }

        return $total;
    }

    /**
     * Prazo em texto curto e tom para uma data (vencimento, data_agendamento).
     * Devolve ['texto', 'tom']; tom vazio = neutro.
     */
    public static function prazo($data, $hoje = null)
    {
        $data = trim((string) $data);
        if ($data === '' || strpos($data, '0000-00-00') === 0) {
            return ['Sem data', ''];
        }
        $hoje = $hoje ?: date('Y-m-d');
        $dia = substr($data, 0, 10);
        if (strlen($data) >= 16) {
            $dia .= ' ' . substr($data, 11, 5);
        }
        $dias = (int) floor((strtotime(substr($dia, 0, 10)) - strtotime(substr($hoje, 0, 10))) / 86400);
        if ($dias < 0) {
            return ['Atrasado ' . abs($dias) . (abs($dias) === 1 ? ' dia' : ' dias') . ' · ' . self::dataCurta($dia), 'perigo'];
        }
        if ($dias === 0) {
            return ['Hoje' . (strlen($dia) > 10 ? ' ' . substr($dia, 11, 5) : ''), 'alerta'];
        }
        if ($dias === 1) {
            return ['Amanhã' . (strlen($dia) > 10 ? ' ' . substr($dia, 11, 5) : ''), 'alerta'];
        }

        return [self::dataCurta($dia), $dias <= 7 ? 'alerta' : ''];
    }

    /** dd/mm/aaaa (mantem o horario quando houver). */
    private static function dataCurta($data)
    {
        $dia = substr($data, 0, 10);
        $ts = strtotime($dia);
        if ($ts === false) {
            return $data;
        }

        return date('d/m', $ts) . (strlen($data) > 10 ? ' ' . substr($data, 11, 5) : '');
    }

    /**
     * Valor liquido de um lancamento, aplicando o desconto quando houver
     * (tipo_desconto "real" = valor em reais, "porcento" = percentual).
     */
    public static function valorLancamento($lancamento)
    {
        $valor = (float) ($lancamento->valor ?? 0);
        $desconto = (float) ($lancamento->desconto ?? 0);
        $tipo = (string) ($lancamento->tipo_desconto ?? '');
        if ($tipo === 'real') {
            return max(0.0, $valor - $desconto);
        }
        if ($tipo === 'porcento') {
            return max(0.0, $valor - ($valor * $desconto / 100));
        }

        return $valor;
    }

    /** R$ 1.234,56 */
    public static function moeda($valor)
    {
        return 'R$ ' . number_format((float) $valor, 2, ',', '.');
    }

    /** Quantidade com separador de milhar. */
    public static function numero($valor, $decimais = 0)
    {
        return number_format((float) $valor, (int) $decimais, ',', '.');
    }

    /**
     * Serie dos ultimos 14 dias: [['rotulo','valor','dica'], ...] para Grafico_svg::colunas.
     *
     * @param array $porDia [['dia' => 'Y-m-d', 'os' => n, 'vendas' => n], ...]
     */
    public static function serieDias(array $porDia, $hoje = null)
    {
        $hoje = $hoje ?: date('Y-m-d');
        $indice = [];
        foreach ($porDia as $linha) {
            $indice[substr((string) $linha['dia'], 0, 10)] = $linha;
        }
        $diasSemana = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
        $pontos = [];
        for ($i = 13; $i >= 0; $i--) {
            $dia = date('Y-m-d', strtotime($hoje . ' -' . $i . ' day'));
            $os = (int) ($indice[$dia]['os'] ?? 0);
            $vendas = (int) ($indice[$dia]['vendas'] ?? 0);
            $ts = strtotime($dia);
            $pontos[] = [
                'rotulo' => $diasSemana[(int) date('w', $ts)] . ' ' . date('d', $ts),
                'valor' => $os,
                'dica' => date('d/m/Y', $ts) . "\n" . self::numero($os) . ' OS abertas' . "\n" . self::numero($vendas) . ' vendas',
            ];
        }

        return $pontos;
    }

    /**
     * Status das OS como pontos de barras horizontais (Grafico_svg).
     *
     * @param array $contagens [status => quantidade]
     */
    public static function pontosStatus(array $contagens)
    {
        $pontos = [];
        foreach (self::resumoStatus($contagens) as $linha) {
            if ($linha['total'] === 0) {
                continue;
            }
            $pontos[] = [
                'rotulo' => $linha['status'],
                'valor' => $linha['total'],
                'dica' => $linha['status'] . "\n" . self::numero($linha['total']) . ' OS',
            ];
        }

        return $pontos;
    }

    /** Iniciais do usuario para o avatar textual (sem depender de imagem). */
    public static function iniciais($nome)
    {
        $partes = preg_split('/\s+/', trim((string) $nome)) ?: [];
        $partes = array_values(array_filter($partes, static function ($p) {
            return $p !== '';
        }));
        if (! $partes) {
            return '?';
        }
        $primeira = mb_strtoupper(mb_substr($partes[0], 0, 1));
        $ultima = count($partes) > 1 ? mb_strtoupper(mb_substr($partes[count($partes) - 1], 0, 1)) : '';

        return $primeira . $ultima;
    }
}
