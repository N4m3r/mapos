<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Exportacao CSV da lista de projetos (puro, sem banco).
 */
class Projeto_csv
{
    /** Ordenacoes permitidas: chave GET => coluna SQL. */
    public const ORDENS = [
        'nome' => 'obras.nome',
        'inicio' => 'obras.data_inicio_prevista',
        'fim' => 'obras.data_fim_prevista',
        'progresso' => 'obras.percentual_concluido',
        'status' => 'obras.status',
    ];

    public static function colunaOrdem($ordem, $dir = 'asc'): array
    {
        $ordem = (string) $ordem;
        if (! isset(self::ORDENS[$ordem])) {
            return ['obras.idObra', 'desc'];
        }
        return [self::ORDENS[$ordem], strtolower((string) $dir) === 'desc' ? 'desc' : 'asc'];
    }

    /** Neutraliza injecao de formula e escapa para CSV com ';'. */
    public static function celula($valor): string
    {
        $s = str_replace(["\r\n", "\r", "\n"], ' ', (string) $valor);
        if ($s !== '' && strpos("=+-@\t", $s[0]) !== false) {
            $s = "'" . $s;
        }
        if (strpbrk($s, ";\"") !== false) {
            $s = '"' . str_replace('"', '""', $s) . '"';
        }
        return $s;
    }

    public static function numero($n, int $casas = 2): string
    {
        return number_format((float) $n, $casas, ',', '');
    }

    public static function linha(array $campos): string
    {
        return implode(';', array_map([self::class, 'celula'], $campos)) . "\r\n";
    }
}
