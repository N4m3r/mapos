<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * IVA-T9 - Opcao semestral do Simples Nacional: IBS/CBS no DAS ou regime regular. Idempotente.
 */
class Migration_add_simples_opcao_ibscbs extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('simples_opcao_ibscbs')) {
            return;
        }
        $this->dbforge->add_field([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ano' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true],
            'semestre' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true],
            'regime' => ['type' => 'ENUM', 'constraint' => ['das', 'regular'], 'default' => 'das'],
            'registrado_em' => ['type' => 'DATETIME', 'null' => true],
            'observacao' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('simples_opcao_ibscbs', true);
        $this->db->query('ALTER TABLE `simples_opcao_ibscbs` ADD UNIQUE KEY `uq_ano_semestre` (`ano`, `semestre`)');
    }

    public function down()
    {
        $this->dbforge->drop_table('simples_opcao_ibscbs', true);
    }
}
