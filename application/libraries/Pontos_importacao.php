<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Logica pura da importacao de pontos por planilha (CSV/XLSX): cabecalho,
 * separador, encoding, tipo e validacao de linha. Sem acesso a banco.
 */
class Pontos_importacao
{
    const MAX_LINHAS = 1000;
    const MAX_BYTES = 2097152;

    /** Campo => limite de caracteres (iguais aos de Obras::salvarPonto). */
    public static function limites()
    {
        return [
            'identificacao' => 30, 'local' => 150, 'ip' => 45, 'canal' => 10,
            'porta' => 30, 'modelo' => 80, 'serie' => 60, 'observacao' => 255,
        ];
    }

    /** Minusculas, sem acento e sem espacos/pontuacao. */
    public static function normalizar($s)
    {
        $s = trim((string) $s);
        $s = preg_replace('/^\xEF\xBB\xBF/', '', $s);
        $de = 'áàâãäéèêëíìîïóòôõöúùûüçñÁÀÂÃÄÉÈÊËÍÌÎÏÓÒÔÕÖÚÙÛÜÇÑ';
        $para = ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c', 'n'];
        $mapa = [];
        $chars = preg_split('//u', $de, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($chars as $i => $c) {
            $mapa[$c] = $para[$i % 24];
        }
        $s = strtr($s, $mapa);
        $s = strtolower($s);

        return preg_replace('/[^a-z0-9]/', '', $s);
    }

    /** Mapeia colunas do cabecalho para campos; devolve campo => indice. */
    public static function mapearCabecalho(array $cols)
    {
        $alias = [
            'identificacao' => 'identificacao', 'id' => 'identificacao', 'nome' => 'identificacao',
            'tipo' => 'tipo', 'local' => 'local', 'ip' => 'ip', 'canal' => 'canal',
            'porta' => 'porta', 'modelo' => 'modelo', 'serie' => 'serie', 'nserie' => 'serie',
            'observacao' => 'observacao', 'obs' => 'observacao',
        ];
        $map = [];
        foreach (array_values($cols) as $i => $c) {
            $k = self::normalizar($c);
            if (isset($alias[$k]) && ! isset($map[$alias[$k]])) {
                $map[$alias[$k]] = $i;
            }
        }

        return $map;
    }

    /** Detecta ; ou , pela primeira linha nao vazia. */
    public static function detectarSeparador($texto)
    {
        foreach (preg_split('/\r\n|\r|\n/', (string) $texto) as $linha) {
            if (trim($linha) === '') {
                continue;
            }

            return substr_count($linha, ';') >= substr_count($linha, ',') && strpos($linha, ';') !== false ? ';' : ',';
        }

        return ';';
    }

    /** Remove BOM e converte Windows-1252 para UTF-8 quando nao for UTF-8 valido. */
    public static function paraUtf8($bin)
    {
        $bin = (string) $bin;
        if (substr($bin, 0, 3) === "\xEF\xBB\xBF") {
            $bin = substr($bin, 3);
        }
        if (! mb_check_encoding($bin, 'UTF-8')) {
            $bin = mb_convert_encoding($bin, 'UTF-8', 'Windows-1252');
        }

        return $bin;
    }

    /** Aceita a chave (camera) ou o rotulo (Camera); vazio => camera; invalido => null. */
    public static function normalizarTipo($valor, array $tipos)
    {
        $n = self::normalizar($valor);
        if ($n === '') {
            return 'camera';
        }
        foreach ($tipos as $chave => $t) {
            if ($n === self::normalizar($chave) || $n === self::normalizar($t['rotulo'])) {
                return $chave;
            }
        }

        return null;
    }

    /**
     * Le CSV (conteudo bruto) e devolve ['linhas' => [[campo=>valor,...]], 'erro' => string|null].
     * Cada linha traz '_n' (numero da linha na planilha).
     */
    public static function lerCsv($bin)
    {
        $texto = self::paraUtf8($bin);
        $sep = self::detectarSeparador($texto);
        $h = fopen('php://memory', 'r+');
        fwrite($h, $texto);
        rewind($h);
        $linhas = [];
        while (($r = fgetcsv($h, 0, $sep, '"', '')) !== false) {
            $linhas[] = $r;
        }
        fclose($h);

        return self::linhasParaRegistros($linhas);
    }

    /** Converte matriz (primeira linha = cabecalho) em registros por campo. */
    public static function linhasParaRegistros(array $matriz)
    {
        $cab = null;
        $numCab = 0;
        $out = [];
        foreach ($matriz as $i => $r) {
            $r = array_map(static function ($v) {
                return trim((string) $v);
            }, (array) $r);
            if (implode('', $r) === '') {
                continue;
            }
            if ($cab === null) {
                $cab = self::mapearCabecalho($r);
                $numCab = $i + 1;
                if (! isset($cab['identificacao'])) {
                    return ['linhas' => [], 'erro' => 'Cabeçalho sem a coluna "identificacao" (aceita também id ou nome).'];
                }
                continue;
            }
            if (count($out) >= self::MAX_LINHAS) {
                return ['linhas' => [], 'erro' => 'A planilha tem mais de ' . self::MAX_LINHAS . ' linhas.'];
            }
            $reg = ['_n' => $i + 1];
            foreach (array_keys(self::limites()) as $campo) {
                $reg[$campo] = isset($cab[$campo], $r[$cab[$campo]]) ? $r[$cab[$campo]] : '';
            }
            $reg['tipo'] = isset($cab['tipo'], $r[$cab['tipo']]) ? $r[$cab['tipo']] : '';
            $out[] = $reg;
        }
        if ($cab === null) {
            return ['linhas' => [], 'erro' => 'Planilha vazia.'];
        }

        return ['linhas' => $out, 'erro' => null];
    }

    /**
     * Valida uma linha isoladamente (sem olhar o banco nem outras linhas).
     * Devolve ['dados' => [...], 'tipo_vazio' => bool, 'erro' => string|null].
     */
    public static function validarLinha(array $reg, array $tipos)
    {
        $dados = [];
        foreach (self::limites() as $campo => $max) {
            $dados[$campo] = mb_substr(trim((string) ($reg[$campo] ?? '')), 0, $max);
        }
        $tipoVazio = trim((string) ($reg['tipo'] ?? '')) === '';
        $tipo = self::normalizarTipo($reg['tipo'] ?? '', $tipos);
        $erro = null;
        if ($dados['identificacao'] === '') {
            $erro = 'Identificação obrigatória.';
        } elseif ($tipo === null) {
            $erro = 'Tipo inválido: ' . mb_substr(trim((string) $reg['tipo']), 0, 40) . '.';
        } elseif (($e = Obra_pontos_model::validarRede($dados['ip'], $dados['canal'], $dados['porta'])) !== null) {
            $erro = $e;
        }
        $dados['tipo'] = $tipo ?? 'camera';

        return ['dados' => $dados, 'tipo_vazio' => $tipoVazio, 'erro' => $erro];
    }

    /**
     * Classifica as linhas validadas: novo | atualizar | pular | erro.
     *
     * @param array  $linhas     saida de validarLinha acrescida de '_n'
     * @param array  $existentes lista de ['idPonto','identificacao','ip'] do projeto
     * @param string $modo       'atualizar' ou 'pular' para identificacao ja existente
     */
    public static function classificar(array $linhas, array $existentes, $modo)
    {
        $porIdent = [];
        $porIp = [];
        foreach ($existentes as $e) {
            $e = (array) $e;
            $porIdent[mb_strtolower($e['identificacao'])] = (int) $e['idPonto'];
            if (($e['ip'] ?? '') !== '' && $e['ip'] !== null) {
                $porIp[$e['ip']] = (int) $e['idPonto'];
            }
        }
        $identsPlan = [];
        $ipsPlan = [];
        $out = [];
        foreach ($linhas as $l) {
            $l['status'] = 'novo';
            $l['idPonto'] = 0;
            $l['motivo'] = '';
            if (! empty($l['erro'])) {
                $l['status'] = 'erro';
                $l['motivo'] = $l['erro'];
                $out[] = $l;
                continue;
            }
            $d = $l['dados'];
            $chave = mb_strtolower($d['identificacao']);
            if (isset($identsPlan[$chave])) {
                $l['status'] = 'erro';
                $l['motivo'] = 'Identificação repetida na planilha (linha ' . $identsPlan[$chave] . ').';
                $out[] = $l;
                continue;
            }
            $identsPlan[$chave] = $l['_n'];
            $idExistente = $porIdent[$chave] ?? 0;
            if ($idExistente) {
                $l['idPonto'] = $idExistente;
                if ($modo !== 'atualizar') {
                    $l['status'] = 'pular';
                    $l['motivo'] = 'Já existe no projeto.';
                    $out[] = $l;
                    continue;
                }
                $l['status'] = 'atualizar';
            }
            if ($d['ip'] !== '') {
                if (isset($ipsPlan[$d['ip']])) {
                    $l['status'] = 'erro';
                    $l['motivo'] = 'IP ' . $d['ip'] . ' repetido na planilha (linha ' . $ipsPlan[$d['ip']] . ').';
                } elseif (isset($porIp[$d['ip']]) && $porIp[$d['ip']] !== $idExistente) {
                    $l['status'] = 'erro';
                    $l['motivo'] = 'IP ' . $d['ip'] . ' já está em outro ponto do projeto.';
                } else {
                    $ipsPlan[$d['ip']] = $l['_n'];
                }
            }
            $out[] = $l;
        }

        return $out;
    }

    /** Contagem por status. */
    public static function contar(array $linhas)
    {
        $c = ['novo' => 0, 'atualizar' => 0, 'pular' => 0, 'erro' => 0];
        foreach ($linhas as $l) {
            $c[$l['status']]++;
        }

        return $c;
    }

    /** CSV de exemplo (UTF-8 com BOM, separador ;). */
    public static function modeloCsv()
    {
        return "\xEF\xBB\xBF" . "identificacao;tipo;local;ip;canal;porta;modelo;serie;observacao\r\n"
            . "CAM-01;Câmera;Entrada principal;192.168.1.101;1;80;DS-2CD2143G2;SN0001;\r\n"
            . "SW-01;Switch;Rack 1;192.168.1.2;;;PoE 24p;;Switch do rack\r\n";
    }
}
