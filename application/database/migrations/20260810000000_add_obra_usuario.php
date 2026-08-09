<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Usuários com acesso de execução a um projeto (obra).
 *
 * Vincula usuários do sistema a um projeto. Apenas usuários vinculados
 * (ou o responsável do projeto, ou um gestor com permissão) podem dar
 * seguimento e registrar a execução das atividades.
 */
class Migration_add_obra_usuario extends CI_Migration
{
    public function up()
    {
        $comum = ['ENGINE' => 'InnoDB'];

        if (! $this->db->table_exists('obra_usuario')) {
            $this->dbforge->add_field([
                'idObraUsuario' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'obra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'data_vinculo' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idObraUsuario', true);
            $this->dbforge->add_key('obra_id');
            $this->dbforge->add_key('usuario_id');
            $this->dbforge->create_table('obra_usuario', true, $comum);
        }

        log_message('info', 'Migration add_obra_usuario executada com sucesso');
    }

    public function down()
    {
        if ($this->db->table_exists('obra_usuario')) {
            $this->dbforge->drop_table('obra_usuario', true);
        }
    }
}
