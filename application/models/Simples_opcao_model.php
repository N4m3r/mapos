<?php

defined('BASEPATH') or exit('No direct script access allowed');

/** Opcao semestral IBS/CBS no DAS x regime regular (tabela simples_opcao_ibscbs). Tolerante a tabela ausente. */
class Simples_opcao_model extends CI_Model
{
    public function listar(): array
    {
        if (! $this->db->table_exists('simples_opcao_ibscbs')) {
            return [];
        }

        return $this->db->order_by('ano')->order_by('semestre')->get('simples_opcao_ibscbs')->result_array();
    }

    public function salvar(int $ano, int $semestre, string $regime, ?string $observacao = null): bool
    {
        if (! $this->db->table_exists('simples_opcao_ibscbs') || $semestre < 1 || $semestre > 2 || ! in_array($regime, ['das', 'regular'], true)) {
            return false;
        }
        $dados = ['regime' => $regime, 'registrado_em' => date('Y-m-d H:i:s'), 'observacao' => $observacao !== null ? mb_substr($observacao, 0, 255) : null];
        $w = ['ano' => $ano, 'semestre' => $semestre];
        if ($this->db->where($w)->count_all_results('simples_opcao_ibscbs') > 0) {
            return (bool) $this->db->where($w)->update('simples_opcao_ibscbs', $dados);
        }

        return (bool) $this->db->insert('simples_opcao_ibscbs', $dados + $w);
    }

    public function doSemestre(string $data, int $opSimpNac): string
    {
        $this->load->helper('tributacao');

        return tributacao_regime_ibscbs($this->listar(), $data, $opSimpNac);
    }
}
