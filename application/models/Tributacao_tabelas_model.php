<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Tributacao_tabelas_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('tributacao_tabelas');
    }

    /** Substitui toda a tabela em transacao e registra a versao. Retorna ['ok','qtd','erros']. */
    public function importar($tabela, $csv, $versao = '', $fonteUrl = '', $vigencia = null, $usuarioId = null)
    {
        $def = trib_tabelas_definicao()[$tabela] ?? null;
        if (! $def) {
            return ['ok' => false, 'qtd' => 0, 'erros' => ['Tabela desconhecida.']];
        }
        $p = trib_parse_csv($csv, $tabela);
        if ($p['erros'] || ! $p['linhas']) {
            return ['ok' => false, 'qtd' => 0, 'erros' => $p['erros'] ?: ['Nenhuma linha válida.']];
        }
        $this->db->trans_start();
        $this->db->empty_table($def['sql']);
        $this->db->insert_batch($def['sql'], $p['linhas']);
        $this->db->insert('trib_tabelas_versao', [
            'tabela' => $tabela, 'versao' => $versao ?: null, 'fonte_url' => $fonteUrl ?: null,
            'vigencia_inicio' => trib_data($vigencia), 'importado_em' => date('Y-m-d H:i:s'), 'usuarios_id' => $usuarioId,
        ]);
        $this->db->trans_complete();
        if (! $this->db->trans_status()) {
            return ['ok' => false, 'qtd' => 0, 'erros' => ['Falha ao gravar; nada foi alterado.']];
        }

        return ['ok' => true, 'qtd' => count($p['linhas']), 'erros' => []];
    }

    /** Resumo por tabela: contagem e ultima versao importada. */
    public function resumo()
    {
        $out = [];
        foreach (trib_tabelas_definicao() as $k => $d) {
            $ok = $this->db->table_exists($d['sql']);
            $v = $ok ? $this->db->where('tabela', $k)->order_by('id', 'desc')->get('trib_tabelas_versao')->row() : null;
            $out[$k] = ['nome' => $d['nome'], 'qtd' => $ok ? $this->db->count_all($d['sql']) : 0, 'versao' => $v];
        }

        return $out;
    }

    /** cClassTrib por CST e/ou documento ('nfe','nfce','nfse'), so vigentes. */
    public function buscarCClassTrib($cst = null, $documento = null)
    {
        if ($cst) {
            $this->db->where('cst', $cst);
        }
        if (in_array($documento, ['nfe', 'nfce', 'nfse'], true)) {
            $this->db->where('ind_' . $documento, 1);
        }
        $this->db->group_start()->where('vigencia_fim IS NULL', null, false)->or_where('vigencia_fim >=', date('Y-m-d'))->group_end();

        return $this->db->order_by('codigo')->get('trib_cclasstrib')->result();
    }

    /** Sugestoes (cClassTrib/NBS/cIndOp) para um item da LC 116 (ex.: "14.01" ou "1401"). */
    public function sugerirPorItemLc116($item)
    {
        $item = trim((string) $item);
        $alt = preg_match('/^\d{4}$/', $item) ? substr($item, 0, 2) . '.' . substr($item, 2) : $item;
        $this->db->where_in('item_lc116', array_unique([$item, $alt]));

        return $this->db->get('trib_nfse_correlacao')->result();
    }
}
