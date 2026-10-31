<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Logica pura de etiquetas QR e planilha as-built dos pontos (sem banco).
 */
class Obra_etiquetas
{
    public const COLUNAS = [
        'Identificação', 'Tipo', 'Local', 'IP', 'Canal', 'Porta', 'Modelo', 'Série/MAC',
        'Metragem de cabo (m)', 'Status', 'Responsável', 'Data de conclusão',
    ];

    private static function campo($p, $nome, $padrao = '')
    {
        $v = is_array($p) ? ($p[$nome] ?? null) : ($p->$nome ?? null);
        return $v === null ? $padrao : $v;
    }

    /** URL do cartao do ponto na area do tecnico (ancora #ponto-ID). */
    public static function urlPonto($baseUrl, $obra_id, $ponto_id): string
    {
        return rtrim((string) $baseUrl, '/') . '/tecnico/projeto/' . (int) $obra_id . '#ponto-' . (int) $ponto_id;
    }

    /** Filtra por tipo e/ou ids selecionados; sem filtros devolve tudo. */
    public static function filtrar(array $pontos, $tipo = '', $ids = []): array
    {
        $tipo = (string) $tipo;
        $ids = array_filter(array_map('intval', (array) $ids));
        $out = [];
        foreach ($pontos as $p) {
            if ($tipo !== '' && (string) self::campo($p, 'tipo') !== $tipo) {
                continue;
            }
            if ($ids && ! in_array((int) self::campo($p, 'idPonto', 0), $ids, true)) {
                continue;
            }
            $out[] = $p;
        }
        return $out;
    }

    /** Linhas (arrays de texto) do as-built, na ordem de COLUNAS. */
    public static function linhasAsbuilt(array $pontos, array $tipos, array $statusLista): array
    {
        $linhas = [];
        foreach ($pontos as $p) {
            $tp = (string) self::campo($p, 'tipo');
            $st = (string) self::campo($p, 'status');
            $concl = '';
            if ($st === 'testado' && (string) self::campo($p, 'atualizado_em') !== '') {
                $concl = date('d/m/Y', strtotime((string) self::campo($p, 'atualizado_em')));
            }
            $linhas[] = [
                (string) self::campo($p, 'identificacao'),
                $tipos[$tp]['rotulo'] ?? $tp,
                (string) self::campo($p, 'local'),
                (string) self::campo($p, 'ip'),
                (string) self::campo($p, 'canal'),
                (string) self::campo($p, 'porta'),
                (string) self::campo($p, 'modelo'),
                (string) self::campo($p, 'serie'),
                number_format((float) self::campo($p, 'metragem_cabo', 0), 2, ',', ''),
                $statusLista[$st]['rotulo'] ?? $st,
                (string) self::campo($p, 'responsavel_nome'),
                $concl,
            ];
        }
        return $linhas;
    }

    /** CSV completo (BOM UTF-8, ';'), formulas neutralizadas via Projeto_csv. */
    public static function csv(array $linhas): string
    {
        $out = "\xEF\xBB\xBF" . Projeto_csv::linha(self::COLUNAS);
        foreach ($linhas as $l) {
            $out .= Projeto_csv::linha($l);
        }
        return $out;
    }

    /** QR code como data URI SVG. */
    public static function qrDataUri($url): string
    {
        return (new \chillerlan\QRCode\QRCode(new \chillerlan\QRCode\QROptions(['outputBase64' => true])))->render((string) $url);
    }
}
