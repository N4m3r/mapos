<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_tecnico_os_relacao extends CI_Migration {

    public function up()
    {
        // Adicionar campo tecnico_responsavel na tabela os
        if (!$this->db->field_exists('tecnico_responsavel', 'os')) {
            $this->dbforge->add_column('os', [
                'tecnico_responsavel' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'ID do usuario tecnico responsavel pela OS',
                ]
            ]);

            // Adicionar chave estrangeira
            $this->db->query('ALTER TABLE `os` ADD INDEX `idx_tecnico_responsavel` (`tecnico_responsavel`)');
        }

        // Criar tabela de historico de atribuicoes
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
                    'comment' => 'ID do tecnico atribuido',
                ],
                'atribuido_por' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                    'comment' => 'ID do usuario que fez a atribuicao',
                ],
                'data_atribuicao' => [
                    'type' => 'TIMESTAMP',
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
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
            $this->db->query('ALTER TABLE `os_tecnico_atribuicao` ENGINE = InnoDB');

            // Adicionar indices
            $this->db->query('ALTER TABLE `os_tecnico_atribuicao` ADD INDEX `idx_os_id` (`os_id`)');
            $this->db->query('ALTER TABLE `os_tecnico_atribuicao` ADD INDEX `idx_tecnico_id` (`tecnico_id`)');
        }

        // Adicionar permissao de tecnico - verifica se existe grupo com nome 'Área do Técnico'
        $this->db->where('nome', 'Área do Técnico');
        $exists = $this->db->get('permissoes');

        if ($exists->num_rows() == 0) {
            // Cria o grupo de permissões com permissão aTecnico ativada
            $permissoes = [
                'aTecnico' => 1,
            ];

            $this->db->insert('permissoes', [
                'nome' => 'Área do Técnico',
                'data' => date('Y-m-d'),
                'permissoes' => serialize($permissoes),
                'situacao' => 1,
            ]);
        }
    }

    public function down()
    {
        // Remover campo da tabela os
        if ($this->db->field_exists('tecnico_responsavel', 'os')) {
            $this->dbforge->drop_column('os', 'tecnico_responsavel');
        }

        // Remover tabela de atribuicoes
        $this->dbforge->drop_table('os_tecnico_atribuicao', true);
    }
}
