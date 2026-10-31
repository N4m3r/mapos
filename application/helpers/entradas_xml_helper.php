<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Leitura pura de NF-e de compra (sem banco). Busca por local-name, sem
 * depender de namespace.
 */

if (! function_exists('entradas_xp')) {
    /** Texto do primeiro nó descendente que casa com o caminho de local-names (ex.: 'total/ICMSTot/vNF'). */
    function entradas_xp(SimpleXMLElement $ctx, string $caminho): ?string
    {
        $partes = array_filter(explode('/', $caminho));
        $xp = './/' . implode('/', array_map(fn ($p) => "*[local-name()='{$p}']", $partes));
        $r = $ctx->xpath($xp);

        return !empty($r) ? trim((string) $r[0]) : null;
    }
}

if (! function_exists('entradas_parse_nfe')) {
    /** Retorna cabeçalho, emitente, totais e itens; array vazio se não for NF-e. */
    function entradas_parse_nfe(string $xml): array
    {
        $xml = trim($xml);
        if ($xml === '') {
            return [];
        }
        $prev = libxml_use_internal_errors(true);
        $sx = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if ($sx === false) {
            return [];
        }
        $inf = $sx->xpath("//*[local-name()='infNFe']");
        if (empty($inf)) {
            return [];
        }
        $inf = $inf[0];
        $g = fn (string $c) => entradas_xp($inf, $c);
        $f = fn (?string $v) => ($v === null || $v === '') ? 0.0 : (float) $v;

        $chave = preg_replace('/\D/', '', (string) $inf['Id']);
        $dh = $g('ide/dhEmi') ?: $g('ide/dEmi');
        $dataEmissao = $competencia = null;
        if ($dh && ($ts = strtotime($dh))) {
            $dataEmissao = date('Y-m-d', $ts);
            $competencia = date('Y-m', $ts);
        }
        $crt = $g('emit/CRT');

        $itens = [];
        foreach ($inf->xpath("./*[local-name()='det']") ?: [] as $det) {
            $d = fn (string $c) => entradas_xp($det, $c);
            $itens[] = [
                'n_item' => (int) $det['nItem'],
                'codigo' => $d('prod/cProd'),
                'descricao' => $d('prod/xProd'),
                'ncm' => $d('prod/NCM'),
                'cfop' => $d('prod/CFOP'),
                'quantidade' => $f($d('prod/qCom')),
                'valor' => $f($d('prod/vProd')),
                'cst_ibscbs' => $d('IBSCBS/CST'),
                'cclasstrib' => $d('IBSCBS/cClassTrib'),
                'v_bc_ibscbs' => $f($d('gIBSCBS/vBC')),
                'p_ibs_uf' => $f($d('gIBSUF/pIBSUF')),
                'p_ibs_mun' => $f($d('gIBSMun/pIBSMun')),
                'p_cbs' => $f($d('gCBS/pCBS')),
                'v_ibs' => $f($d('gIBSCBS/vIBS')),
                'v_cbs' => $f($d('gCBS/vCBS')),
                'c_cred_pres' => $d('gIBSCredPres/cCredPres') ?: $d('gCBSCredPres/cCredPres'),
                'p_cred_pres' => $f($d('gIBSCredPres/pCredPres') ?: $d('gCBSCredPres/pCredPres')),
            ];
        }

        return [
            'chave' => strlen($chave) === 44 ? $chave : null,
            'modelo' => $g('ide/mod') ?: '55',
            'numero' => $g('ide/nNF'),
            'serie' => $g('ide/serie'),
            'emitente_cnpj' => $g('emit/CNPJ') ?: $g('emit/CPF'),
            'emitente_nome' => $g('emit/xNome'),
            'emitente_crt' => ($crt === null || $crt === '') ? null : (int) $crt,
            'data_emissao' => $dataEmissao,
            'competencia' => $competencia,
            'valor_total' => $f($g('total/ICMSTot/vNF')),
            'v_icms' => $f($g('total/ICMSTot/vICMS')),
            'v_pis' => $f($g('total/ICMSTot/vPIS')),
            'v_cofins' => $f($g('total/ICMSTot/vCOFINS')),
            'v_bc_ibscbs' => $f($g('total/IBSCBSTot/vBCIBSCBS')),
            'v_ibs' => $f($g('total/IBSCBSTot/gIBS/vIBS')),
            'v_cbs' => $f($g('total/IBSCBSTot/gCBS/vCBS')),
            'v_cred_pres_zfm' => $f($g('IBSCBSTot/vCredPresIBSZFM') ?: $g('IBSCBSTot/gIBS/vCredPresIBSZFM')),
            'itens' => $itens,
        ];
    }
}
