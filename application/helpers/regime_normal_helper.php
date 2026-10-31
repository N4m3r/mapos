<?php

defined('BASEPATH') or define('BASEPATH', true);

/**
 * IVA-T17 - Regime normal (Lucro Presumido/Real): funcoes puras de ICMS e PIS/COFINS por item.
 * Escopo: venda interna/interestadual simples (sem ST, sem DIFAL).
 */
if (! function_exists('rn_prop')) {
    function rn_prop($o, $k, $padrao = null)
    {
        if (is_array($o)) {
            return $o[$k] ?? $padrao;
        }

        return (is_object($o) && isset($o->$k)) ? $o->$k : $padrao;
    }
}

if (! function_exists('rn_crt')) {
    /** op_simp_nac (1 nao optante, 2 MEI, 3 ME/EPP) -> CRT (3 normal, 4 MEI, 1 Simples). */
    function rn_crt(int $opSimpNac): int
    {
        return [1 => 3, 2 => 4, 3 => 1][$opSimpNac] ?? 1;
    }
}

if (! function_exists('rn_icms_item')) {
    /**
     * ICMS do item: CST/aliquota do produto (override) ou padrao da empresa.
     * Retorna ['cst','pICMS','vBC','vICMS']; CST 40/41/50/60 saem sem valor.
     */
    function rn_icms_item(array $item, object $config, bool $interestadual): array
    {
        $cstItem = preg_replace('/\D/', '', (string) rn_prop($item, 'icms_cst', ''));
        $cst = $cstItem !== '' ? $cstItem : preg_replace('/\D/', '', (string) rn_prop($config, 'icms_cst_padrao', '00'));
        $cst = str_pad($cst, 2, '0', STR_PAD_LEFT);
        if (! in_array($cst, ['00', '40', '41', '50', '60'], true)) {
            throw new Exception("CST de ICMS {$cst} ainda não é suportado (use 00, 40, 41, 50 ou 60); consulte o contador.");
        }
        if ($cst !== '00') {
            return ['cst' => $cst, 'pICMS' => 0.0, 'vBC' => 0.0, 'vICMS' => 0.0];
        }
        $vBC = round((float) rn_prop($item, 'vProd', 0) - (float) rn_prop($item, 'vDesc', 0), 2);
        $aliqItem = rn_prop($item, 'icms_aliquota');
        $p = ($aliqItem !== null && $aliqItem !== '')
            ? (float) $aliqItem
            : (float) rn_prop($config, $interestadual ? 'icms_aliquota_interestadual' : 'icms_aliquota_interna', 0);

        return ['cst' => '00', 'pICMS' => $p, 'vBC' => $vBC, 'vICMS' => round($vBC * $p / 100, 2)];
    }
}

if (! function_exists('rn_pis_cofins_item')) {
    /** PIS/COFINS do item: cumulativo 0,65%/3%; nao cumulativo 1,65%/7,6%. So o CST 01 leva valor. */
    function rn_pis_cofins_item(float $vProd, object $config): array
    {
        $nc = rn_prop($config, 'pis_cofins_regime', 'cumulativo') === 'nao_cumulativo';
        $cst = str_pad(preg_replace('/\D/', '', (string) rn_prop($config, 'pis_cofins_cst', '01')), 2, '0', STR_PAD_LEFT);
        if ($cst !== '01') {
            return ['cst' => $cst, 'vBC' => 0.0, 'pPIS' => 0.0, 'vPIS' => 0.0, 'pCOFINS' => 0.0, 'vCOFINS' => 0.0];
        }
        $pPis = $nc ? 1.65 : 0.65;
        $pCof = $nc ? 7.6 : 3.0;
        $vBC = round($vProd, 2);

        return [
            'cst' => '01', 'vBC' => $vBC,
            'pPIS' => $pPis, 'vPIS' => round($vBC * $pPis / 100, 2),
            'pCOFINS' => $pCof, 'vCOFINS' => round($vBC * $pCof / 100, 2),
        ];
    }
}
