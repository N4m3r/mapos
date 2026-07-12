<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Módulo de RH — holerites.
 *
 * Guarda o holerite/recibo OFICIAL (PDF) que o RH sobe por colaborador e
 * competência, para o colaborador baixar na área dele. O cálculo fiscal
 * (INSS/IRRF/FGTS) continua com a contabilidade — aqui é só o arquivo +
 * um resumo de valores opcional. O demonstrativo gerencial vem dos
 * lançamentos (rh_lancamentos), este arquivo é o documento definitivo.
 */
class Migration_create_rh_holerites extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('rh_holerites')) {
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
                'arquivo_base64' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                    'comment' => 'PDF oficial (data URI base64)',
                ],
                'arquivo_mime' => [
                    'type' => 'VARCHAR',
                    'constraint' => 60,
                    'null' => true,
                ],
                'arquivo_nome' => [
                    'type' => 'VARCHAR',
                    'constraint' => 160,
                    'null' => true,
                ],
                'valor_liquido' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                    'comment' => 'Líquido informado no recibo (opcional)',
                ],
                'observacao' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_by' => [
                    'type' => 'INT',
                    'constraint' => 11,
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
            $this->dbforge->create_table('rh_holerites', true);

            $this->db->query('ALTER TABLE `rh_holerites` ADD UNIQUE INDEX `uniq_rh_holerite_colab_comp` (`colaborador_id`, `competencia`)');
        }
    }

    public function down()
    {
        if ($this->db->table_exists('rh_holerites')) {
            $this->dbforge->drop_table('rh_holerites', true);
        }
    }
}
