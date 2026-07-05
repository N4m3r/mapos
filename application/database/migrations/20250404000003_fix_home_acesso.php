<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_fix_home_acesso extends CI_Migration {

    public function up()
    {
        // Garantir que as permissões de técnico existam mas não bloqueiem o acesso
        // Se o usuário não tem permissão vTecnicoDashboard, ele deve acessar o Home normalmente

        // Verificar se existe a coluna tecnico_responsavel na tabela os
        if (!$this->db->field_exists('tecnico_responsavel', 'os')) {
            $this->dbforge->add_column('os', [
                'tecnico_responsavel' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'ID do usuario tecnico responsavel pela OS',
                ]
            ]);

            $this->db->query('ALTER TABLE `os` ADD INDEX `idx_tecnico_responsavel` (`tecnico_responsavel`)');
        }

        // Verificar se a tabela de atribuicoes existe
        if (!$this->db->table_exists('os_tecnico_atribuicao')) {
            $this->dbforge->add_field([
                'idAtribuicao' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                    'auto_increment' => true,
                ],
                'os_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                ],
                'tecnico_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                ],
                'atribuido_por' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                ],
                'data_atribuicao' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'data_remocao' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'motivo_remocao' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'observacao' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('idAtribuicao', true);
            $this->dbforge->create_table('os_tecnico_atribuicao', true);
        }

        log_message('info', 'Migration fix_home_acesso executada com sucesso - estrutura do tecnico verificada');
    }

    public function down()
    {
        // Nao remove nada para manter a compatibilidade
    }
}
