<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Módulo de RH — extras, consolidação de horas e ausências.
 *
 *  - rh_lancamentos: extras financeiros por competência (hora extra,
 *    adicional, comissão, bônus, adiantamento/vale, desconto, falta).
 *  - rh_horas:       consolidação mensal de horas por colaborador
 *    (trabalhadas/previstas/extras/faltas/saldo de banco de horas).
 *  - rh_ausencias:   férias/folgas/atestados/licenças (com anexo e aprovação).
 */
class Migration_create_rh_extras extends CI_Migration
{
    public function up()
    {
        // ------------------------------------------------------------------
        // rh_lancamentos — extras financeiros por competência
        // ------------------------------------------------------------------
        if (! $this->db->table_exists('rh_lancamentos')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'colaborador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'competencia' => [
                    'type' => 'VARCHAR',
                    'constraint' => 7,
                    'comment' => 'Competência no formato YYYY-MM',
                ],
                'tipo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'comment' => 'hora_extra | adicional | comissao | bonus | adiantamento | desconto | falta | vale',
                ],
                'natureza' => [
                    'type' => 'VARCHAR',
                    'constraint' => 10,
                    'default' => 'provento',
                    'comment' => 'provento | desconto',
                ],
                'descricao' => [
                    'type' => 'VARCHAR',
                    'constraint' => 160,
                    'null' => true,
                ],
                'quantidade' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                    'comment' => 'Qtd de horas/itens quando aplicável',
                ],
                'valor' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => 0,
                ],
                'aprovado' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ],
                'aprovador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'origem' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'manual',
                    'comment' => 'manual | automatico (gerado pelo cálculo de horas)',
                ],
                'referencia_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'Origem do lançamento (ex.: rh_horas.id)',
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
            $this->dbforge->create_table('rh_lancamentos', true);

            $this->db->query('ALTER TABLE `rh_lancamentos` ADD INDEX `idx_rh_lanc_colab_comp` (`colaborador_id`, `competencia`)');
        }

        // ------------------------------------------------------------------
        // rh_horas — consolidação mensal de horas
        // ------------------------------------------------------------------
        if (! $this->db->table_exists('rh_horas')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'colaborador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'competencia' => [
                    'type' => 'VARCHAR',
                    'constraint' => 7,
                    'comment' => 'YYYY-MM',
                ],
                'dias_trabalhados' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'minutos_trabalhados' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'minutos_previstos' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'minutos_extras_50' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'minutos_extras_100' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'minutos_faltas' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'saldo_banco_min' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                    'comment' => 'Saldo de banco de horas em minutos (+/-)',
                ],
                'fechado' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ],
                'data_fechamento' => [
                    'type' => 'DATETIME',
                    'null' => true,
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
            $this->dbforge->create_table('rh_horas', true);

            $this->db->query('ALTER TABLE `rh_horas` ADD UNIQUE INDEX `uniq_rh_horas_colab_comp` (`colaborador_id`, `competencia`)');
        }

        // ------------------------------------------------------------------
        // rh_ausencias — férias/folgas/atestados/licenças (com aprovação)
        // ------------------------------------------------------------------
        if (! $this->db->table_exists('rh_ausencias')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'colaborador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'tipo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'comment' => 'ferias | folga | atestado | licenca',
                ],
                'data_inicio' => [
                    'type' => 'DATE',
                ],
                'data_fim' => [
                    'type' => 'DATE',
                ],
                'dias' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'motivo' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'anexo_base64' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'anexo_mime' => [
                    'type' => 'VARCHAR',
                    'constraint' => 60,
                    'null' => true,
                ],
                'anexo_nome' => [
                    'type' => 'VARCHAR',
                    'constraint' => 160,
                    'null' => true,
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'pendente',
                    'comment' => 'pendente | aprovado | recusado',
                ],
                'aprovador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'data_analise' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'resposta' => [
                    'type' => 'TEXT',
                    'null' => true,
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
            $this->dbforge->create_table('rh_ausencias', true);

            $this->db->query('ALTER TABLE `rh_ausencias` ADD INDEX `idx_rh_ausencia_colab` (`colaborador_id`)');
            $this->db->query('ALTER TABLE `rh_ausencias` ADD INDEX `idx_rh_ausencia_status` (`status`)');
        }
    }

    public function down()
    {
        foreach (['rh_ausencias', 'rh_horas', 'rh_lancamentos'] as $tabela) {
            if ($this->db->table_exists($tabela)) {
                $this->dbforge->drop_table($tabela, true);
            }
        }
    }
}
