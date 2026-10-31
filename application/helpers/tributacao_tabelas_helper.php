<?php

defined('BASEPATH') or exit('No direct script access allowed');

/** Funcoes puras para importar as tabelas oficiais da reforma tributaria. */

if (! function_exists('trib_tabelas_definicao')) {
    /** Tabelas importaveis: tabela SQL, colunas aceitas e obrigatorias. */
    function trib_tabelas_definicao()
    {
        return [
            'cst' => ['sql' => 'trib_cst', 'nome' => 'CST do IBS/CBS', 'colunas' => ['cst', 'descricao', 'vigencia'], 'obrig' => ['cst']],
            'cclasstrib' => ['sql' => 'trib_cclasstrib', 'nome' => 'cClassTrib', 'colunas' => ['codigo', 'cst', 'descricao', 'p_red_ibs', 'p_red_cbs', 'ind_nfe', 'ind_nfce', 'ind_nfse', 'vigencia_inicio', 'vigencia_fim'], 'obrig' => ['codigo']],
            'credpres' => ['sql' => 'trib_credpres', 'nome' => 'Crédito presumido (cCredPres)', 'colunas' => ['codigo', 'descricao'], 'obrig' => ['codigo']],
            'nfse_correlacao' => ['sql' => 'trib_nfse_correlacao', 'nome' => 'Anexo VIII da NFS-e', 'colunas' => ['item_lc116', 'nbs', 'cindop', 'cclasstrib', 'descricao'], 'obrig' => ['item_lc116']],
        ];
    }
}

if (! function_exists('trib_normaliza_coluna')) {
    /** "Código cClassTrib" -> "codigo"; mapeia sinonimos comuns dos cabecalhos oficiais. */
    function trib_normaliza_coluna($nome)
    {
        $n = trim((string) $nome, " \t\n\r\0\x0B\"");
        $n = preg_replace('/^\xEF\xBB\xBF/', '', $n);
        $n = strtolower(strtr($n, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e',
            'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ü' => 'u', 'ç' => 'c',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ç' => 'c',
        ]));
        $n = trim(preg_replace('/[^a-z0-9]+/', '_', $n), '_');
        $mapa = [
            'cst_ibs_cbs' => 'cst', 'codigo_cst' => 'cst', 'cod_cst' => 'cst',
            'cclasstrib' => 'codigo', 'codigo_cclasstrib' => 'codigo', 'codigo_classificacao_tributaria' => 'codigo', 'cod' => 'codigo',
            'descricao_cclasstrib' => 'descricao', 'descricao_cst' => 'descricao', 'nome' => 'descricao',
            'predibs' => 'p_red_ibs', 'pred_ibs' => 'p_red_ibs', 'percentual_reducao_ibs' => 'p_red_ibs',
            'predcbs' => 'p_red_cbs', 'pred_cbs' => 'p_red_cbs', 'percentual_reducao_cbs' => 'p_red_cbs',
            'nfe' => 'ind_nfe', 'nfce' => 'ind_nfce', 'nfse' => 'ind_nfse',
            'inicio_vigencia' => 'vigencia_inicio', 'fim_vigencia' => 'vigencia_fim', 'dt_ini_vig' => 'vigencia_inicio', 'dt_fim_vig' => 'vigencia_fim',
            'item' => 'item_lc116', 'item_lc_116' => 'item_lc116', 'lc116' => 'item_lc116',
            'c_ind_op' => 'cindop', 'indop' => 'cindop', 'codigo_nbs' => 'nbs',
        ];

        return $mapa[$n] ?? $n;
    }
}

if (! function_exists('trib_detecta_separador')) {
    function trib_detecta_separador($primeiraLinha)
    {
        return substr_count($primeiraLinha, ';') >= substr_count($primeiraLinha, ',') ? ';' : ',';
    }
}

if (! function_exists('trib_data')) {
    /** dd/mm/aaaa ou aaaa-mm-dd -> aaaa-mm-dd; vazio/invalido -> null. */
    function trib_data($s)
    {
        $s = trim((string) $s);
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $s, $m)) {
            return "$m[3]-$m[2]-$m[1]";
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : null;
    }
}

if (! function_exists('trib_normaliza_linha')) {
    function trib_normaliza_linha(array $a, $tabela)
    {
        $def = trib_tabelas_definicao()[$tabela];
        $o = [];
        foreach ($def['colunas'] as $c) {
            $v = isset($a[$c]) ? trim((string) $a[$c]) : '';
            if ($c === 'cst' || ($c === 'codigo' && $tabela === 'cclasstrib') || $c === 'cclasstrib') {
                $v = preg_replace('/\D/', '', $v);
                $tam = ($c === 'cst') ? 3 : 6;
                $v = $v === '' ? '' : str_pad($v, $tam, '0', STR_PAD_LEFT);
            } elseif (in_array($c, ['ind_nfe', 'ind_nfce', 'ind_nfse'], true)) {
                $v = in_array(strtolower($v), ['1', 's', 'sim', 'x', 'true'], true) ? 1 : 0;
            } elseif (in_array($c, ['p_red_ibs', 'p_red_cbs'], true)) {
                $v = $v === '' ? null : (float) str_replace(',', '.', str_replace('%', '', $v));
            } elseif (in_array($c, ['vigencia', 'vigencia_inicio', 'vigencia_fim'], true)) {
                $v = trib_data($v);
            }
            $o[$c] = $v;
        }
        if ($tabela === 'cclasstrib' && $o['cst'] === '' && strlen($o['codigo']) === 6) {
            $o['cst'] = substr($o['codigo'], 0, 3);
        }

        return $o;
    }
}

if (! function_exists('trib_valida_linha')) {
    /** Retorna mensagem de erro ou '' se valida. */
    function trib_valida_linha(array $l, $tabela)
    {
        if ($tabela === 'cst' && ! preg_match('/^\d{3}$/', $l['cst'])) {
            return 'CST deve ter 3 dígitos.';
        }
        if ($tabela === 'cclasstrib') {
            if (! preg_match('/^\d{6}$/', $l['codigo'])) {
                return 'cClassTrib deve ter 6 dígitos.';
            }
            if (! preg_match('/^\d{3}$/', $l['cst']) || substr($l['codigo'], 0, 3) !== $l['cst']) {
                return 'Os 3 primeiros dígitos do cClassTrib devem ser o CST.';
            }
        }
        if ($tabela === 'credpres' && $l['codigo'] === '') {
            return 'Código vazio.';
        }
        if ($tabela === 'nfse_correlacao') {
            if ($l['item_lc116'] === '') {
                return 'Item da LC 116 vazio.';
            }
            if ($l['cclasstrib'] !== '' && ! preg_match('/^\d{6}$/', $l['cclasstrib'])) {
                return 'cClassTrib deve ter 6 dígitos.';
            }
        }

        return '';
    }
}

if (! function_exists('trib_parse_csv')) {
    /** CSV (texto) -> ['linhas' => [assoc normalizado], 'erros' => [msgs]] conforme a tabela. */
    function trib_parse_csv($texto, $tabela)
    {
        $def = trib_tabelas_definicao()[$tabela] ?? null;
        if (! $def) {
            return ['linhas' => [], 'erros' => ['Tabela desconhecida.']];
        }
        $texto = preg_replace('/^\xEF\xBB\xBF/', '', (string) $texto);
        if (! mb_check_encoding($texto, 'UTF-8')) {
            $texto = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
        }
        $rows = preg_split('/\r\n|\n|\r/', trim($texto));
        if (! $rows || trim($rows[0]) === '') {
            return ['linhas' => [], 'erros' => ['Arquivo vazio.']];
        }
        $sep = trib_detecta_separador($rows[0]);
        $cab = array_map('trib_normaliza_coluna', str_getcsv(array_shift($rows), $sep));
        foreach ($def['obrig'] as $c) {
            if (! in_array($c, $cab, true)) {
                return ['linhas' => [], 'erros' => ['Coluna obrigatória ausente: ' . $c]];
            }
        }
        $linhas = [];
        $erros = [];
        foreach ($rows as $i => $r) {
            if (trim($r) === '') {
                continue;
            }
            $v = str_getcsv($r, $sep);
            $assoc = [];
            foreach ($cab as $k => $c) {
                $assoc[$c] = $v[$k] ?? '';
            }
            $linha = trib_normaliza_linha($assoc, $tabela);
            $e = trib_valida_linha($linha, $tabela);
            if ($e) {
                $erros[] = 'Linha ' . ($i + 2) . ': ' . $e;
            } else {
                $linhas[] = $linha;
            }
        }

        return ['linhas' => $linhas, 'erros' => $erros];
    }
}

if (! function_exists('trib_status_atualizacao')) {
    /**
     * Situacao das tabelas oficiais e das bibliotecas fiscais (funcao pura).
     *
     * @param array $versoesTabelas [tabela => ['versao' => ?string, 'importado_em' => 'Y-m-d H:i:s'] | null]; ausente/null = nunca importada
     * @param array $libs [['pacote' => string, 'instalada' => ?string, 'ultima' => ?string]]
     * @param string $hoje Data de referencia (Y-m-d)
     * @return array<int, array{item:string,situacao:string,mensagem:string}>
     */
    function trib_status_atualizacao(array $versoesTabelas, array $libs, string $hoje): array
    {
        $itens = [];
        $hojeDt = new DateTimeImmutable(substr($hoje, 0, 10));

        foreach (trib_tabelas_definicao() as $chave => $def) {
            $nome = 'Tabela: ' . $def['nome'];
            $v = $versoesTabelas[$chave] ?? null;
            $imp = is_array($v) ? (string) ($v['importado_em'] ?? '') : '';

            if ($imp === '') {
                $itens[] = ['item' => $nome, 'situacao' => 'desatualizado', 'mensagem' => 'Nunca importada. Importe a tabela oficial em Tabelas da Reforma Tributária.'];
                continue;
            }

            $dias = (int) (new DateTimeImmutable(substr($imp, 0, 10)))->diff($hojeDt)->format('%r%a');
            $dataBr = date('d/m/Y', strtotime(substr($imp, 0, 10)));
            $versao = trim((string) ($v['versao'] ?? ''));
            $ref = $versao !== '' ? " (versão {$versao})" : '';

            if ($dias > 90) {
                $itens[] = ['item' => $nome, 'situacao' => 'atencao', 'mensagem' => "Importada há {$dias} dias ({$dataBr}){$ref}. Confira se o governo publicou versão nova."];
            } else {
                $itens[] = ['item' => $nome, 'situacao' => 'ok', 'mensagem' => "Importada em {$dataBr}{$ref}."];
            }
        }

        foreach ($libs as $lib) {
            $pacote = (string) ($lib['pacote'] ?? '');
            $inst = ltrim(trim((string) ($lib['instalada'] ?? '')), 'vV');
            $ult = ltrim(trim((string) ($lib['ultima'] ?? '')), 'vV');

            if ($inst === '') {
                $itens[] = ['item' => 'Biblioteca: ' . $pacote, 'situacao' => 'atencao', 'mensagem' => 'Versão instalada não encontrada.'];
            } elseif ($ult === '') {
                $itens[] = ['item' => 'Biblioteca: ' . $pacote, 'situacao' => 'atencao', 'mensagem' => "Instalada: {$inst}. Versão mais recente não verificada (rode a verificação)."];
            } elseif (version_compare($inst, $ult, '<')) {
                $itens[] = ['item' => 'Biblioteca: ' . $pacote, 'situacao' => 'atencao', 'mensagem' => "Instalada: {$inst}. Há versão mais nova: {$ult}."];
            } else {
                $itens[] = ['item' => 'Biblioteca: ' . $pacote, 'situacao' => 'ok', 'mensagem' => "Instalada: {$inst} (atualizada)."];
            }
        }

        return $itens;
    }
}
