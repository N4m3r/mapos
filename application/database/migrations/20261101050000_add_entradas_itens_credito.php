<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * IVA-T11 - Entradas: dados para o crédito financeiro de IBS/CBS (art. 47 LC 214).
 * O crédito só vale se o tributo da etapa anterior foi pago: guarda data de
 * pagamento, regime (CRT) do fornecedor, totais ICMS/PIS/COFINS e os itens.
 * Idempotente.
 */
class Migration_add_entradas_itens_credito extends CI_Migration
{
    public function up()
    {
        $novas = [
            'emitente_cnpj' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'emitente_crt' => ['type' => 'TINYINT', 'constraint' => 4, 'null' => true, 'comment' => '1/2/4 Simples-MEI, 3 normal'],
            'data_pagamento' => ['type' => 'DATE', 'null' => true],
            'v_icms' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'v_pis' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'v_cofins' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
        ];
        foreach ($novas as $col => $def) {
            if (! $this->db->field_exists($col, 'notas_entrada')) {
                $this->dbforge->add_column('notas_entrada', [$col => $def]);
            }
        }

        if (! $this->db->table_exists('notas_entrada_itens')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'nota_entrada_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'n_item' => ['type' => 'INT', 'constraint' => 5, 'null' => true],
                'codigo' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                'descricao' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'ncm' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
                'cfop' => ['type' => 'VARCHAR', 'constraint' => 6, 'null' => true],
                'quantidade' => ['type' => 'DECIMAL', 'constraint' => '15,4', 'null' => true],
                'valor' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
                'cst_ibscbs' => ['type' => 'VARCHAR', 'constraint' => 3, 'null' => true],
                'cclasstrib' => ['type' => 'VARCHAR', 'constraint' => 6, 'null' => true],
                'v_bc_ibscbs' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
                'p_ibs_uf' => ['type' => 'DECIMAL', 'constraint' => '9,4', 'null' => true],
                'p_ibs_mun' => ['type' => 'DECIMAL', 'constraint' => '9,4', 'null' => true],
                'p_cbs' => ['type' => 'DECIMAL', 'constraint' => '9,4', 'null' => true],
                'v_ibs' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
                'v_cbs' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
                'c_cred_pres' => ['type' => 'VARCHAR', 'constraint' => 4, 'null' => true],
                'p_cred_pres' => ['type' => 'DECIMAL', 'constraint' => '9,4', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('nota_entrada_id');
            $this->dbforge->create_table('notas_entrada_itens', true, ['ENGINE' => 'InnoDB']);
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('notas_entrada_itens', true);
        foreach (['emitente_crt', 'data_pagamento', 'v_icms', 'v_pis', 'v_cofins'] as $col) {
            if ($this->db->field_exists($col, 'notas_entrada')) {
                $this->dbforge->drop_column('notas_entrada', $col);
            }
        }
    }
}
