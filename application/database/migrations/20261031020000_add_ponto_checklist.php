<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Checklist de teste de imagem das cameras (obra_ponto_teste). O ultimo teste do ponto vale. Idempotente.
 */
class Migration_add_ponto_checklist extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('obra_ponto_teste')) {
            $this->dbforge->add_field([
                'idTeste' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'ponto_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'obra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'itens' => ['type' => 'TEXT', 'null' => true],
                'retencao_dias' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'observacao' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'aprovado' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'data' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('idTeste', true);
            $this->dbforge->add_key('ponto_id');
            $this->dbforge->add_key('obra_id');
            $this->dbforge->create_table('obra_ponto_teste', true, ['ENGINE' => 'InnoDB']);
        }

        log_message('info', 'Migration add_ponto_checklist executada com sucesso');
    }

    public function down()
    {
        if ($this->db->table_exists('obra_ponto_teste')) {
            $this->dbforge->drop_table('obra_ponto_teste', true);
        }
    }
}
