<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Aliquotas anuais da reforma tributaria (tabela tributos_parametros).
 * Resiliente a tabela ausente (migration nao aplicada).
 */
class Tributos_parametros_model extends CI_Model
{
    public function listar()
    {
        if (! $this->db->table_exists('tributos_parametros')) {
            return [];
        }
        $this->db->order_by('ano', 'ASC');

        return $this->db->get('tributos_parametros')->result_array();
    }

    /**
     * Cria ou atualiza o ano informado.
     * $dados: cbs, ibs_uf, ibs_mun, fonte, confirmado (demais campos sao ignorados).
     */
    public function salvar(int $ano, array $dados): bool
    {
        if (! $this->db->table_exists('tributos_parametros')) {
            return false;
        }
        $registro = [
            'cbs' => (float) str_replace(',', '.', (string) ($dados['cbs'] ?? 0)),
            'ibs_uf' => (float) str_replace(',', '.', (string) ($dados['ibs_uf'] ?? 0)),
            'ibs_mun' => (float) str_replace(',', '.', (string) ($dados['ibs_mun'] ?? 0)),
            'fonte' => mb_substr((string) ($dados['fonte'] ?? ''), 0, 255),
            'confirmado' => !empty($dados['confirmado']) ? 1 : 0,
            'atualizado_em' => date('Y-m-d H:i:s'),
        ];

        $existe = $this->db->where('ano', $ano)->count_all_results('tributos_parametros') > 0;
        if ($existe) {
            $this->db->where('ano', $ano);

            return (bool) $this->db->update('tributos_parametros', $registro);
        }
        $registro['ano'] = $ano;

        return (bool) $this->db->insert('tributos_parametros', $registro);
    }

    /** Parametros do ano (com fallback e flag 'estimado'). */
    public function doAno(int $ano): array
    {
        $this->load->helper('tributacao');

        return tributacao_parametros_do_ano($ano, $this->listar());
    }
}
