<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Percentual efetivo do DAS (Simples Nacional) sobre o faturamento.
 * Idempotente: so cria a coluna se ainda nao existir.
 */
class Migration_add_simples_aliquota extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('configuracoes_nfe') && !$this->db->field_exists('simples_aliquota_efetiva', 'configuracoes_nfe')) {
            $this->db->query('ALTER TABLE `configuracoes_nfe` ADD COLUMN `simples_aliquota_efetiva` DECIMAL(6,4) NOT NULL DEFAULT 0');
        }
    }

    public function down()
    {
        if ($this->db->field_exists('simples_aliquota_efetiva', 'configuracoes_nfe')) {
            $this->dbforge->drop_column('configuracoes_nfe', 'simples_aliquota_efetiva');
        }
    }
}
