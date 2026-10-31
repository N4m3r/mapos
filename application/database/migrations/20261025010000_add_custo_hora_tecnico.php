<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Custo real e margem por OS (ADR-008): config custo_hora_tecnico. Idempotente.
 */
class Migration_add_custo_hora_tecnico extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('configuracoes')) {
            return;
        }
        $existe = $this->db->where('config', 'custo_hora_tecnico')->count_all_results('configuracoes');
        if (! $existe) {
            $this->db->insert('configuracoes', ['config' => 'custo_hora_tecnico', 'valor' => '0']);
        }
    }

    public function down()
    {
        if ($this->db->table_exists('configuracoes')) {
            $this->db->where('config', 'custo_hora_tecnico')->delete('configuracoes');
        }
    }
}
