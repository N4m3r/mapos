<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Consumo de material e serviços executados num projeto (obra).
 *
 * Cada linha é o que a equipe registrou como utilizado/executado durante
 * a execução (opcional). Material dá baixa no estoque de produtos; serviço
 * apenas marca o que foi feito. Vinculado ao RDO que originou o registro.
 */
class Migration_add_obra_consumo extends CI_Migration
{
    public function up()
    {
        $comum = ['ENGINE' => 'InnoDB'];

        if (! $this->db->table_exists('obra_consumo')) {
            $this->dbforge->add_field([
                'idConsumo' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'obra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'rdo_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'tipo' => ['type' => 'VARCHAR', 'constraint' => 10], // material | servico
                'referencia_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'descricao' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'quantidade' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
                'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'data' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idConsumo', true);
            $this->dbforge->add_key('obra_id');
            $this->dbforge->create_table('obra_consumo', true, $comum);
        }

        log_message('info', 'Migration add_obra_consumo executada com sucesso');
    }

    public function down()
    {
        if ($this->db->table_exists('obra_consumo')) {
            $this->dbforge->drop_table('obra_consumo', true);
        }
    }
}
