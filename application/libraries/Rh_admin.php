<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Regras puras (sem banco) da parte administrativa do RH:
 * conversão hh:mm <-> minutos, validação do parâmetro ?voltar= e rótulos.
 */
class Rh_admin
{
    /**
     * Converte "8:48", "08:48" ou "8h48" em minutos. Número puro é tratado
     * como minutos (compatibilidade com o formato antigo). Inválido => null.
     */
    public static function horasParaMinutos($texto)
    {
        $t = strtolower(trim((string) $texto));
        if ($t === '') {
            return null;
        }
        if (ctype_digit($t)) {
            $min = (int) $t;
        } elseif (preg_match('/^(\d{1,2})\s*(?::|h)\s*([0-5]?\d)\s*(?:min)?$/', $t, $m)) {
            $min = ((int) $m[1]) * 60 + (int) $m[2];
        } else {
            return null;
        }

        return ($min > 0 && $min <= 1440) ? $min : null;
    }

    /** 528 => "08:48". */
    public static function minutosParaHoras($min)
    {
        $min = max(0, (int) $min);

        return sprintf('%02d:%02d', intdiv($min, 60), $min % 60);
    }

    /**
     * Valida o destino do botão "Voltar": só caminhos internos rh/* de uma lista
     * permitida (sem esquema, host, "..", barras duplas). Senão devolve $padrao.
     */
    public static function voltarSeguro($voltar, $padrao = 'rh/colaboradores')
    {
        $v = trim((string) $voltar);
        $v = ltrim($v, '/');
        if ($v === '' || strlen($v) > 120 || preg_match('/[^A-Za-z0-9_\/\-]/', $v) || strpos($v, '..') !== false || strpos($v, '//') !== false) {
            return $padrao;
        }
        $permitidas = [
            '#^rh$#',
            '#^rh/(colaboradores|lancamentos|folha|descontosClt|ocorrencias|ausencias|unidades|jornadas)$#',
            '#^rh/folha/\d{4}-\d{2}$#',
            '#^rh/(ficha|editarColaborador)/\d+$#',
            '#^rh/(espelho|holerite)/\d+(/\d{4}-\d{2})?$#',
        ];
        foreach ($permitidas as $re) {
            if (preg_match($re, $v)) {
                return $v;
            }
        }

        return $padrao;
    }

    /** Aba do subnav, rótulo e URL da seção para um destino "voltar" válido. */
    public static function secaoDe($rota)
    {
        $mapa = [
            'folha' => ['folha', 'Folha', 'rh/folha'],
            'lancamentos' => ['lancamentos', 'Lançamentos', 'rh/lancamentos'],
            'ocorrencias' => ['ocorrencias', 'Ocorrências', 'rh/ocorrencias'],
            'ausencias' => ['ausencias', 'Ausências', 'rh/ausencias'],
            'descontosClt' => ['descontos', 'Descontos CLT', 'rh/descontosClt'],
            'unidades' => ['unidades', 'Unidades', 'rh/unidades'],
            'jornadas' => ['jornadas', 'Jornadas', 'rh/jornadas'],
        ];
        $partes = explode('/', trim((string) $rota, '/'));
        $chave = $partes[1] ?? '';

        return $mapa[$chave] ?? ['colaboradores', 'Colaboradores', 'rh/colaboradores'];
    }

    /** Texto do botão "Voltar" conforme o destino. */
    public static function rotuloVoltar($rota)
    {
        $partes = explode('/', trim((string) $rota, '/'));
        $chave = $partes[1] ?? '';
        switch ($chave) {
            case 'ficha':
                return 'Voltar à ficha';
            case 'editarColaborador':
                return 'Voltar ao cadastro';
            case 'espelho':
                return 'Voltar ao espelho';
            case 'holerite':
                return 'Voltar ao holerite';
            case '':
                return 'Voltar ao painel';
        }

        return 'Voltar para ' . mb_strtolower(self::secaoDe($rota)[1]);
    }

    /** Texto do selo de contagem do menu: vazio se 0, "99+" se grande. */
    public static function rotuloContagem($n)
    {
        $n = (int) $n;
        if ($n <= 0) {
            return '';
        }

        return $n > 99 ? '99+' : (string) $n;
    }

    /** Situação do holerite da competência: nenhum | anexado | liberado. */
    public static function situacaoHolerite($holerite)
    {
        if (! $holerite || empty($holerite->arquivo_base64)) {
            return 'nenhum';
        }

        return ! empty($holerite->liberado_colaborador) ? 'liberado' : 'anexado';
    }
}
