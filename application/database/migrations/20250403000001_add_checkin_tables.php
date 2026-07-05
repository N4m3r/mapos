<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_checkin_tables extends CI_Migration {

    public function up()
    {
        // Tabela de Check-in/Check-out da OS
        $this->dbforge->add_field([
            'idCheckin' => [
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
            'usuarios_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'data_entrada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'data_saida' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'latitude_entrada' => [
                'type' => 'DECIMAL',
                'constraint' => '10,8',
                'null' => true,
            ],
            'longitude_entrada' => [
                'type' => 'DECIMAL',
                'constraint' => '11,8',
                'null' => true,
            ],
            'latitude_saida' => [
                'type' => 'DECIMAL',
                'constraint' => '10,8',
                'null' => true,
            ],
            'longitude_saida' => [
                'type' => 'DECIMAL',
                'constraint' => '11,8',
                'null' => true,
            ],
            'observacao_entrada' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'observacao_saida' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => false,
                'default' => 'Em Andamento',
            ],
            'data_cadastro' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'data_atualizacao' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->dbforge->add_key('idCheckin', true);
        $this->dbforge->create_table('os_checkin', true);
        $this->db->query('ALTER TABLE `os_checkin` ENGINE = InnoDB');

        // Tabela de Assinaturas
        $this->dbforge->add_field([
            'idAssinatura' => [
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
            'checkin_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'tipo' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'comment' => 'tecnico_entrada, tecnico_saida, cliente_saida',
            ],
            'assinatura' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'comment' => 'Caminho da imagem da assinatura',
            ],
            'nome_assinante' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'documento_assinante' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'data_assinatura' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'data_cadastro' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->dbforge->add_key('idAssinatura', true);
        $this->dbforge->create_table('os_assinaturas', true);
        $this->db->query('ALTER TABLE `os_assinaturas` ENGINE = InnoDB');

        // Tabela de Fotos do Atendimento
        $this->dbforge->add_field([
            'idFoto' => [
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
            'checkin_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'usuarios_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'arquivo' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'url' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'descricao' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'etapa' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'durante',
                'comment' => 'entrada, durante, saida',
            ],
            'tamanho' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'tipo_arquivo' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true,
            ],
            'data_upload' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->dbforge->add_key('idFoto', true);
        $this->dbforge->create_table('os_fotos_atendimento', true);
        $this->db->query('ALTER TABLE `os_fotos_atendimento` ENGINE = InnoDB');

        // Adicionar índices para melhor performance
        $this->db->query('ALTER TABLE `os_checkin` ADD INDEX `idx_os_id` (`os_id`)');
        $this->db->query('ALTER TABLE `os_checkin` ADD INDEX `idx_status` (`status`)');
        $this->db->query('ALTER TABLE `os_assinaturas` ADD INDEX `idx_os_id` (`os_id`)');
        $this->db->query('ALTER TABLE `os_assinaturas` ADD INDEX `idx_tipo` (`tipo`)');
        $this->db->query('ALTER TABLE `os_fotos_atendimento` ADD INDEX `idx_os_id` (`os_id`)');
        $this->db->query('ALTER TABLE `os_fotos_atendimento` ADD INDEX `idx_etapa` (`etapa`)');
    }

    public function down()
    {
        $this->dbforge->drop_table('os_checkin', true);
        $this->dbforge->drop_table('os_assinaturas', true);
        $this->dbforge->drop_table('os_fotos_atendimento', true);
    }
}
