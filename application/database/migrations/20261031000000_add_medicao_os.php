<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Faturamento por medicao (ADR-015 fase 5).
 *
 * obra_medicao_os liga a OS (fatura_no_projeto) a medicao em que entrou; uma OS
 * entra em uma unica medicao. obra_medicao ganha o lancamento gerado ao faturar,
 * o valor das OS adicionais e o valor total (etapas + OS adicionais).
 */
class Migration_add_medicao_os extends CI_Migration
{
    public function up()
    {
        $comum = ['ENGINE' => 'InnoDB'];

        if (! $this->db->table_exists('obra_medicao_os')) {
            $this->dbforge->add_field([
                'idMedicaoOs' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'medicao_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'os_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'valor' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0], // valor da OS com desconto
                'tipo_vinculo' => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true],
            ]);
            $this->dbforge->add_key('idMedicaoOs', true);
            $this->dbforge->add_key('medicao_id');
            $this->dbforge->create_table('obra_medicao_os', true, $comum);
        }
        // uma OS em uma unica medicao
        $this->criarIndice('obra_medicao_os', 'uq_obra_medicao_os_os_id', 'os_id', true);

        if ($this->db->table_exists('obra_medicao')) {
            $novos = [];
            if (! $this->db->field_exists('lancamento_id', 'obra_medicao')) {
                $novos['lancamento_id'] = ['type' => 'INT', 'constraint' => 11, 'null' => true];
            }
            if (! $this->db->field_exists('valor_os', 'obra_medicao')) {
                $novos['valor_os'] = ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0];
            }
            $novoTotal = ! $this->db->field_exists('valor_total', 'obra_medicao');
            if ($novoTotal) {
                $novos['valor_total'] = ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0];
            }
            if ($novos) {
                $this->dbforge->add_column('obra_medicao', $novos);
            }
            // medicoes antigas: total = valor das etapas
            if ($novoTotal) {
                $this->db->query('UPDATE obra_medicao SET valor_total = valor_medido + valor_os');
            }
        }

        log_message('info', 'Migration add_medicao_os executada com sucesso');
    }

    public function down()
    {
        if ($this->db->table_exists('obra_medicao_os')) {
            $this->dbforge->drop_table('obra_medicao_os', true);
        }
        if ($this->db->table_exists('obra_medicao')) {
            foreach (['lancamento_id', 'valor_os', 'valor_total'] as $campo) {
                if ($this->db->field_exists($campo, 'obra_medicao')) {
                    $this->dbforge->drop_column('obra_medicao', $campo);
                }
            }
        }
    }

    private function criarIndice($tabela, $nome, $coluna, $unico = false)
    {
        if (! $this->db->table_exists($tabela)) {
            return;
        }
        $r = $this->db->query('SHOW INDEX FROM `' . $tabela . '` WHERE Key_name = ' . $this->db->escape($nome));
        if ($r->num_rows() > 0) {
            return;
        }
        $this->db->query('ALTER TABLE `' . $tabela . '` ADD ' . ($unico ? 'UNIQUE ' : '') . 'INDEX `' . $nome . '` (`' . $coluna . '`)');
    }
}
