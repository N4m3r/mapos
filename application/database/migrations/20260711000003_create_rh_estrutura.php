<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Módulo de RH — estrutura base.
 *
 * Cria as tabelas de apoio do RH:
 *  - rh_unidades:      locais de trabalho (usados para o geofence do ponto).
 *  - rh_jornadas:      escalas/jornadas de trabalho (carga diária, tolerância).
 *  - rh_colaboradores: cadastro de colaboradores, com vínculo OPCIONAL a
 *                      `usuarios` (nem todo colaborador loga no sistema).
 *
 * Idempotente: cada criação é guardada por table_exists().
 */
class Migration_create_rh_estrutura extends CI_Migration
{
    public function up()
    {
        // ------------------------------------------------------------------
        // rh_unidades — locais de trabalho / geofence
        // ------------------------------------------------------------------
        if (! $this->db->table_exists('rh_unidades')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'nome' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ],
                'endereco' => [
                    'type' => 'VARCHAR',
                    'constraint' => 200,
                    'null' => true,
                ],
                'latitude' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,7',
                    'null' => true,
                ],
                'longitude' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,7',
                    'null' => true,
                ],
                'raio_metros' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 150,
                    'comment' => 'Raio do geofence em metros',
                ],
                'situacao' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('rh_unidades', true);
        }

        // ------------------------------------------------------------------
        // rh_jornadas — escalas/jornadas de trabalho
        // ------------------------------------------------------------------
        if (! $this->db->table_exists('rh_jornadas')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'nome' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ],
                'carga_diaria_min' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 480,
                    'comment' => 'Carga diária prevista em minutos (padrão 8h)',
                ],
                'tolerancia_min' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 10,
                    'comment' => 'Tolerância de atraso/antecipação em minutos',
                ],
                'dias_semana' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => '1,2,3,4,5',
                    'comment' => 'Dias trabalhados (0=dom .. 6=sáb)',
                ],
                'hora_entrada' => [
                    'type' => 'TIME',
                    'null' => true,
                ],
                'hora_saida' => [
                    'type' => 'TIME',
                    'null' => true,
                ],
                'intervalo_min' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 60,
                    'comment' => 'Intervalo (almoço) previsto em minutos',
                ],
                'situacao' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('rh_jornadas', true);
        }

        // ------------------------------------------------------------------
        // rh_colaboradores — cadastro de colaboradores
        // ------------------------------------------------------------------
        if (! $this->db->table_exists('rh_colaboradores')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'usuarios_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'Vínculo opcional com usuarios (login no sistema)',
                ],
                'nome' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ],
                'cpf' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                ],
                'rg' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                ],
                'data_nascimento' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'cargo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => true,
                ],
                'departamento' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => true,
                ],
                'tipo_contrato' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'default' => 'CLT',
                    'comment' => 'CLT | PJ | Estagio | Temporario',
                ],
                'admissao' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'demissao' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'unidade_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'jornada_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'salario_base' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                ],
                'valor_hora' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                    'comment' => 'Valor da hora (base para extras); se nulo, derivado do salário',
                ],
                'email' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'celular' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                    'comment' => 'Usado para casar batidas de ponto via WhatsApp',
                ],
                'pix_tipo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                ],
                'pix_chave' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'foto_base64' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                    'comment' => 'Foto de perfil (data URI base64)',
                ],
                'foto_mime' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => true,
                ],
                'observacoes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'situacao' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'comment' => '1=ativo 0=inativo/desligado',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('rh_colaboradores', true);

            $this->db->query('ALTER TABLE `rh_colaboradores` ADD INDEX `idx_rh_colab_usuario` (`usuarios_id`)');
            $this->db->query('ALTER TABLE `rh_colaboradores` ADD INDEX `idx_rh_colab_situacao` (`situacao`)');
        }
    }

    public function down()
    {
        foreach (['rh_colaboradores', 'rh_jornadas', 'rh_unidades'] as $tabela) {
            if ($this->db->table_exists($tabela)) {
                $this->dbforge->drop_table($tabela, true);
            }
        }
    }
}
