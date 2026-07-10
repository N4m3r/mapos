<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Faturamento agendado (emissão em espera).
 *
 * Para clientes marcados, quando a OS é aprovada dentro do mês, a emissão da
 * NFS-e + boleto NÃO sai na hora: fica "aguardando" e é disparada só no dia de
 * faturamento configurado (padrão: dia 01 do mês seguinte). Assim o cliente
 * consolida tudo em uma data fixa de cobrança.
 *
 *  - clientes.faturamento_agendado: flag por cliente (segurar a emissão).
 *  - faturamentos_agendados: fila do que está em espera até o dia de emissão.
 *  - automacao_faturamento_dia: dia do mês em que a fila é liberada (1..28).
 */
class Migration_add_faturamento_agendado extends CI_Migration
{
    public function up()
    {
        // Flag por cliente: segurar a emissão até o dia de faturamento.
        if (! $this->db->field_exists('faturamento_agendado', 'clientes')) {
            $this->dbforge->add_column('clientes', [
                'faturamento_agendado' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'after' => 'automacao_aprovacao',
                ],
            ]);
        }

        // Fila de emissões seguradas até o dia de faturamento.
        if (! $this->db->table_exists('faturamentos_agendados')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'os_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'cliente_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'data_aprovacao' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'data_agendada' => [
                    'type' => 'DATE',
                    'null' => false,
                    'comment' => 'Dia em que a emissão deve ser liberada',
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'aguardando',
                    'comment' => 'aguardando | processado | erro | cancelado',
                ],
                'tentativas' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'nota_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'FK para notas_fiscais.idNota após a emissão',
                ],
                'motivo' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'processed_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('faturamentos_agendados', true);

            $this->db->query('ALTER TABLE `faturamentos_agendados` ADD INDEX `idx_fa_os` (`os_id`)');
            $this->db->query('ALTER TABLE `faturamentos_agendados` ADD INDEX `idx_fa_status_data` (`status`, `data_agendada`)');
        }

        // Dia do mês em que a fila é liberada (padrão: dia 01).
        if ($this->db->where('config', 'automacao_faturamento_dia')->count_all_results('configuracoes') == 0) {
            $this->db->insert('configuracoes', ['config' => 'automacao_faturamento_dia', 'valor' => '1']);
        }
    }

    public function down()
    {
        if ($this->db->table_exists('faturamentos_agendados')) {
            $this->dbforge->drop_table('faturamentos_agendados', true);
        }
        if ($this->db->field_exists('faturamento_agendado', 'clientes')) {
            $this->dbforge->drop_column('clientes', 'faturamento_agendado');
        }
        $this->db->where('config', 'automacao_faturamento_dia')->delete('configuracoes');
    }
}
