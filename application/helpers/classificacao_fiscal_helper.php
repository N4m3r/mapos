<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (! function_exists('classificacao_prop')) {
    function classificacao_prop($o, $k)
    {
        $v = is_object($o) ? ($o->$k ?? null) : ($o[$k] ?? null);

        return $v === null ? '' : trim((string) $v);
    }
}

if (! function_exists('classificacao_cst_cclass_valido')) {
    /** CST (3 dig.) + cClassTrib (6 dig.) coerentes: os 3 primeiros digitos do cClassTrib sao o CST. */
    function classificacao_cst_cclass_valido($cst, $cclass)
    {
        return preg_match('/^\d{3}$/', $cst) && preg_match('/^\d{6}$/', $cclass) && substr($cclass, 0, 3) === $cst;
    }
}

if (! function_exists('classificacao_efetiva')) {
    /**
     * Classificacao usada na emissao: a do item quando CST+cClassTrib estao preenchidos e validos,
     * senao o padrao da configuracao fiscal. $tipo: 'produto' | 'servico'.
     */
    function classificacao_efetiva($item, $config, $tipo)
    {
        $cst = classificacao_prop($item, 'ibscbs_cst');
        $cclass = classificacao_prop($item, 'ibscbs_cclasstrib');
        $itemOk = classificacao_cst_cclass_valido($cst, $cclass);

        $cindop = $tipo === 'servico' ? classificacao_prop($item, 'cindop') : '';
        if (! preg_match('/^\d{6}$/', $cindop)) {
            $cindop = classificacao_prop($config, 'reforma_cindop');
        }
        $origem = classificacao_prop($item, 'origem');
        $origem = ($origem !== '' && preg_match('/^[0-8]$/', $origem)) ? (int) $origem : 0;

        return [
            'cst' => $itemOk ? $cst : classificacao_prop($config, 'reforma_cst'),
            'cclasstrib' => $itemOk ? $cclass : classificacao_prop($config, 'reforma_cclasstrib'),
            'cindop' => $cindop,
            'origem' => $origem,
            'fonte' => $itemOk ? 'item' : 'padrao',
        ];
    }
}

if (! function_exists('classificacao_dados_post')) {
    /**
     * Valida e normaliza os campos do formulario. Retorna ['dados' => [...], 'erros' => [...]];
     * campo invalido ou vazio vira NULL (usa o padrao).
     */
    function classificacao_dados_post(array $post, $tipo)
    {
        $d = [];
        $erros = [];
        $g = function ($k) use ($post) {
            return preg_replace('/\D/', '', (string) ($post[$k] ?? ''));
        };
        $cst = $g('ibscbs_cst');
        $cclass = $g('ibscbs_cclasstrib');
        if ($cst === '' && $cclass === '') {
            $d['ibscbs_cst'] = $d['ibscbs_cclasstrib'] = null;
        } elseif (classificacao_cst_cclass_valido($cst, $cclass)) {
            $d['ibscbs_cst'] = $cst;
            $d['ibscbs_cclasstrib'] = $cclass;
        } else {
            $d['ibscbs_cst'] = $d['ibscbs_cclasstrib'] = null;
            $erros[] = 'CST (3 dígitos) e cClassTrib (6 dígitos, começando pelo CST) não conferem; usei o padrão da empresa.';
        }
        if ($tipo === 'produto') {
            $o = $g('origem');
            $d['origem'] = ($o !== '' && preg_match('/^[0-8]$/', $o)) ? (int) $o : null;
            // IVA-T17: ICMS por produto (regime normal); vazio = padrao da empresa
            $ic = $g('icms_cst');
            $d['icms_cst'] = in_array($ic, ['00', '40', '41', '50', '60'], true) ? $ic : null;
            $ia = trim(str_replace(',', '.', (string) ($post['icms_aliquota'] ?? '')));
            $d['icms_aliquota'] = ($ia !== '' && is_numeric($ia) && $ia >= 0 && $ia <= 100) ? round((float) $ia, 2) : null;
            $p = trim(str_replace(',', '.', (string) ($post['p_red_ibscbs'] ?? '')));
            if ($p !== '' && is_numeric($p) && $p >= 0 && $p <= 100) {
                $d['p_red_ibscbs'] = round((float) $p, 4);
            } else {
                $d['p_red_ibscbs'] = null;
                if ($p !== '') {
                    $erros[] = 'A redução deve ficar entre 0 e 100.';
                }
            }
        } else {
            foreach (['nbs' => 9, 'cindop' => 6] as $k => $n) {
                $v = $g($k);
                if ($v !== '' && strlen($v) !== $n) {
                    $erros[] = strtoupper($k) . " deve ter {$n} dígitos.";
                    $v = '';
                }
                $d[$k] = $v === '' ? null : $v;
            }
        }

        return ['dados' => $d, 'erros' => $erros];
    }
}

if (! function_exists('classificacao_aplicar_post')) {
    /** Junta ao $data os campos da reforma vindos do POST (so colunas existentes); avisa por flashdata se algo foi descartado. */
    function classificacao_aplicar_post(array $data, $tipo, $tabela)
    {
        $CI = &get_instance();
        $r = classificacao_dados_post($CI->input->post() ?: [], $tipo);
        foreach ($r['dados'] as $campo => $valor) {
            if ($CI->db->field_exists($campo, $tabela)) {
                $data[$campo] = $valor;
            }
        }
        if ($r['erros']) {
            $CI->session->set_flashdata('error', 'Reforma tributária: ' . implode(' ', $r['erros']));
        }

        return $data;
    }
}

if (! function_exists('fiscal_prevalidar_itens')) {
    /**
     * Pre-validacao por item antes de montar/transmitir: valida o CST/cClassTrib efetivo de cada
     * item (proprio ou padrao) e devolve a lista de erros (vazia = ok). $tipo: 'produto' | 'servico'.
     */
    function fiscal_prevalidar_itens(array $itens, $config, int $modelo, string $tipo = 'produto'): array
    {
        if (! function_exists('tributacao_validar_ibscbs')) {
            require_once APPPATH . 'helpers/tributacao_helper.php';
        }
        $erros = [];
        $n = 0;
        foreach ($itens as $item) {
            $n++;
            $c = classificacao_efetiva($item, $config, $tipo);
            $cst = $c['cst'] !== '' ? $c['cst'] : '000';
            $cclass = $c['cclasstrib'] !== '' ? $c['cclasstrib'] : '000001';
            $erro = tributacao_validar_ibscbs($cst, $cclass, $modelo);
            if ($erro !== null) {
                $nome = classificacao_prop($item, 'descricao') ?: classificacao_prop($item, 'nome');
                $erros[] = "Item {$n}" . ($nome !== '' ? " ({$nome})" : '') . ": {$erro}";
            }
        }

        return $erros;
    }
}

if (! function_exists('fiscal_ind_final_nfse')) {
    /** indFinal da NFS-e: 1 para tomador nao contribuinte pessoa fisica (CPF), senao o padrao da config. */
    function fiscal_ind_final_nfse($cliente, $config, string $documento): string
    {
        if (! function_exists('cliente_ind_ie_dest')) {
            require_once APPPATH . 'helpers/cliente_fiscal_helper.php';
        }
        $doc = preg_replace('/\D/', '', $documento);
        if (strlen($doc) === 11 && cliente_ind_ie_dest($cliente) === 9) {
            return '1';
        }

        return classificacao_prop($config, 'reforma_indfinal') ?: '0';
    }
}
