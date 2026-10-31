<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (! function_exists('apuracao_montar')) {
    /**
     * Totaliza débitos, créditos e pendentes por tributo.
     * Cada item: ['v_ibs_uf','v_ibs_mun','v_cbs'] (numéricos). Chaves ausentes valem 0.
     */
    function apuracao_montar(array $debitos, array $creditos, array $pendentes): array
    {
        $soma = function (array $itens): array {
            $t = ['ibs_uf' => 0.0, 'ibs_mun' => 0.0, 'cbs' => 0.0, 'zfm' => 0.0, 'qtd' => count($itens)];
            foreach ($itens as $i) {
                $i = (array) $i;
                if (! isset($i['v_ibs_uf']) && ! isset($i['v_ibs_mun']) && ! isset($i['v_cbs']) && isset($i['v_zfm'])) {
                    $t['qtd']--; // linha só de ZFM não conta como documento
                }
                $t['ibs_uf'] += (float) ($i['v_ibs_uf'] ?? 0);
                $t['ibs_mun'] += (float) ($i['v_ibs_mun'] ?? 0);
                $t['cbs'] += (float) ($i['v_cbs'] ?? 0);
                $t['zfm'] += (float) ($i['v_zfm'] ?? 0);
            }
            foreach (['ibs_uf', 'ibs_mun', 'cbs', 'zfm'] as $k) {
                $t[$k] = round($t[$k], 2);
            }
            // Crédito presumido ZFM: o XML (vCredPresIBSZFM) não separa UF/Município, então fica em linha própria,
            // somada só ao total de créditos (não entra no saldo por tributo).
            $t['total'] = round($t['ibs_uf'] + $t['ibs_mun'] + $t['cbs'] + $t['zfm'], 2);

            return $t;
        };

        $d = $soma($debitos);
        $c = $soma($creditos);
        $p = $soma($pendentes);
        $saldo = [];
        foreach (['ibs_uf', 'ibs_mun', 'cbs', 'total'] as $k) {
            $v = round($d[$k] - $c[$k], 2);
            $saldo[$k] = ['a_recolher' => max($v, 0.0), 'credor' => max(-$v, 0.0), 'valor' => $v];
        }

        return ['debitos' => $d, 'creditos' => $c, 'pendentes' => $p, 'saldo' => $saldo];
    }
}

if (! function_exists('apuracao_csv')) {
    /** CSV (;) com decimal em vírgula. $linhas: tipo, documento, numero, data, v_ibs_uf, v_ibs_mun, v_cbs. */
    function apuracao_csv(array $apuracao, array $linhas): string
    {
        $n = fn ($v) => number_format((float) $v, 2, ',', '');
        $q = function ($v): string {
            $v = (string) $v;
            if (preg_match('/^[=+\-@]/', $v)) {
                $v = "'" . $v;
            }

            return preg_match('/[;"\r\n]/', $v) ? '"' . str_replace('"', '""', $v) . '"' : $v;
        };
        $out = ['Tipo;Documento;Número;Data;IBS estadual;IBS municipal;CBS;Crédito presumido ZFM'];
        foreach ($linhas as $l) {
            $l = (array) $l;
            $out[] = implode(';', [$q($l['tipo'] ?? ''), $q($l['documento'] ?? ''), $q($l['numero'] ?? ''), $q($l['data'] ?? ''),
                $n($l['v_ibs_uf'] ?? 0), $n($l['v_ibs_mun'] ?? 0), $n($l['v_cbs'] ?? 0), $n($l['v_zfm'] ?? 0)]);
        }
        $out[] = '';
        $out[] = 'Resumo;;;;IBS estadual;IBS municipal;CBS;Crédito presumido ZFM';
        foreach ([['Débitos (vendas e serviços)', 'debitos'], ['Créditos (compras pagas)', 'creditos'], ['Crédito pendente de pagamento', 'pendentes']] as [$rot, $k]) {
            $t = $apuracao[$k];
            $out[] = "$rot;;;;" . $n($t['ibs_uf']) . ';' . $n($t['ibs_mun']) . ';' . $n($t['cbs']) . ';' . $n($t['zfm']);
        }
        $s = $apuracao['saldo'];
        $out[] = 'Saldo a recolher;;;;' . $n($s['ibs_uf']['a_recolher']) . ';' . $n($s['ibs_mun']['a_recolher']) . ';' . $n($s['cbs']['a_recolher']) . ';0,00';
        $out[] = 'Saldo credor;;;;' . $n($s['ibs_uf']['credor']) . ';' . $n($s['ibs_mun']['credor']) . ';' . $n($s['cbs']['credor']) . ';0,00';

        return implode("\r\n", $out) . "\r\n";
    }
}

if (! function_exists('apuracao_totais_xml_nfe')) {
    /** IBS UF/Mun e CBS do grupo IBSCBSTot de uma NF-e/NFC-e (zeros se ausente). */
    function apuracao_totais_xml_nfe(string $xml): array
    {
        $r = ['v_ibs_uf' => 0.0, 'v_ibs_mun' => 0.0, 'v_cbs' => 0.0];
        if (trim($xml) === '') {
            return $r;
        }
        $dom = new \DOMDocument();
        $antes = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($antes);
        if (! $ok) {
            return $r;
        }
        $xp = new \DOMXPath($dom);
        $f = function (string $nome) use ($xp): float {
            $n = $xp->query("//*[local-name()='IBSCBSTot']//*[local-name()='" . $nome . "']")->item(0);

            return $n ? (float) $n->nodeValue : 0.0;
        };
        $r['v_ibs_uf'] = $f('vIBSUF');
        $r['v_ibs_mun'] = $f('vIBSMun');
        $r['v_cbs'] = $f('vCBS');
        if ($r['v_ibs_uf'] == 0.0 && $r['v_ibs_mun'] == 0.0 && $f('vIBS') > 0) {
            $r['v_ibs_uf'] = $f('vIBS'); // sem detalhe UF/Município
        }

        return $r;
    }
}

if (! function_exists('apuracao_credito_nota')) {
    /**
     * Separa o crédito de uma nota de entrada em IBS UF / IBS Município / CBS / ZFM.
     * $nota: xml (opcional), v_ibs, v_cbs, v_cred_pres_zfm. Origem 'xml' se o IBSCBSTot trouxe
     * vIBSUF/vIBSMun; senão 'manual' (lançamento sem XML): todo o IBS fica em UF.
     * Crédito presumido ZFM: o XML só tem vCredPresIBSZFM (sem separar UF/Município), por isso é linha própria.
     */
    function apuracao_credito_nota(array $nota): array
    {
        $ibs = (float) ($nota['v_ibs'] ?? 0);
        $r = ['v_ibs_uf' => $ibs, 'v_ibs_mun' => 0.0, 'v_cbs' => (float) ($nota['v_cbs'] ?? 0),
            'v_zfm' => (float) ($nota['v_cred_pres_zfm'] ?? 0), 'origem' => 'manual'];
        $xml = trim((string) ($nota['xml'] ?? ''));
        if ($xml !== '') {
            $t = apuracao_totais_xml_nfe($xml);
            $soma = $t['v_ibs_uf'] + $t['v_ibs_mun'];
            if ($soma > 0) {
                // Mantém o total gravado (v_ibs) e reparte na proporção do XML.
                $r['v_ibs_uf'] = round($ibs > 0 ? $ibs * $t['v_ibs_uf'] / $soma : $t['v_ibs_uf'], 2);
                $r['v_ibs_mun'] = round($ibs > 0 ? $ibs - $r['v_ibs_uf'] : $t['v_ibs_mun'], 2);
                $r['origem'] = 'xml';
            }
        }

        return $r;
    }
}
