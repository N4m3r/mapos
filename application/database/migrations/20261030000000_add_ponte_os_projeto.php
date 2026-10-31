<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Ponte entre OS e projeto.
 *
 * obra_os ganha tipo de vinculo e unicidade global de os_id; apontamento, consumo e
 * historico de pontos passam a guardar a OS de origem; obra_etapa_itens guarda o
 * escopo planejado por etapa; obra_ponto_os liga pontos a OS.
 */
class Migration_add_ponte_os_projeto extends CI_Migration
{
    public function up()
    {
        $comum = ['ENGINE' => 'InnoDB'];

        if ($this->db->table_exists('obra_os')) {
            $novos = [];
            if (! $this->db->field_exists('tipo_vinculo', 'obra_os')) {
                $novos['tipo_vinculo'] = ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'execucao']; // origem, execucao, adicional, corretiva
            }
            if (! $this->db->field_exists('fatura_no_projeto', 'obra_os')) {
                $novos['fatura_no_projeto'] = ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1];
            }
            if (! $this->db->field_exists('data_vinculo', 'obra_os')) {
                $novos['data_vinculo'] = ['type' => 'DATETIME', 'null' => true];
            }
            if ($novos) {
                $this->dbforge->add_column('obra_os', $novos);
            }

            // uma OS pertence a um unico projeto: mantem o menor idVinculo
            $this->db->query('DELETE o1 FROM obra_os o1 INNER JOIN obra_os o2 ON o1.os_id = o2.os_id AND o1.idVinculo > o2.idVinculo');
            $this->criarIndice('obra_os', 'uq_obra_os_os_id', 'os_id', true);
            $this->criarIndice('obra_os', 'idx_obra_os_etapa_id', 'etapa_id');
        }

        foreach (['obra_apontamento', 'obra_consumo', 'obra_ponto_historico'] as $tabela) {
            if (! $this->db->table_exists($tabela)) {
                continue;
            }
            if (! $this->db->field_exists('os_id', $tabela)) {
                $this->dbforge->add_column($tabela, [
                    'os_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                ]);
            }
            $this->criarIndice($tabela, 'idx_' . $tabela . '_os_id', 'os_id');
        }

        if (! $this->db->table_exists('obra_etapa_itens')) {
            $this->dbforge->add_field([
                'idItem' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'obra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'etapa_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'tipo' => ['type' => 'VARCHAR', 'constraint' => 10], // produto ou servico
                'referencia_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'descricao' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'quantidade' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 1],
                'preco' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
                'data_cadastro' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idItem', true);
            $this->dbforge->add_key('obra_id');
            $this->dbforge->add_key('etapa_id');
            $this->dbforge->create_table('obra_etapa_itens', true, $comum);
        }

        if (! $this->db->table_exists('obra_ponto_os')) {
            $this->dbforge->add_field([
                'ponto_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'os_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'data_cadastro' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key(['ponto_id', 'os_id'], true);
            $this->dbforge->add_key('os_id');
            $this->dbforge->create_table('obra_ponto_os', true, $comum);
        }

        log_message('info', 'Migration add_ponte_os_projeto executada com sucesso');
    }

    public function down()
    {
        foreach (['obra_ponto_os', 'obra_etapa_itens'] as $tabela) {
            if ($this->db->table_exists($tabela)) {
                $this->dbforge->drop_table($tabela, true);
            }
        }

        foreach (['obra_apontamento', 'obra_consumo', 'obra_ponto_historico'] as $tabela) {
            if ($this->db->table_exists($tabela) && $this->db->field_exists('os_id', $tabela)) {
                $this->dbforge->drop_column($tabela, 'os_id');
            }
        }

        if ($this->db->table_exists('obra_os')) {
            $this->soltarIndice('obra_os', 'uq_obra_os_os_id');
            $this->soltarIndice('obra_os', 'idx_obra_os_etapa_id');
            foreach (['tipo_vinculo', 'fatura_no_projeto', 'data_vinculo'] as $campo) {
                if ($this->db->field_exists($campo, 'obra_os')) {
                    $this->dbforge->drop_column('obra_os', $campo);
                }
            }
        }
    }

    private function temIndice($tabela, $nome)
    {
        $r = $this->db->query('SHOW INDEX FROM `' . $tabela . '` WHERE Key_name = ' . $this->db->escape($nome));
        return $r->num_rows() > 0;
    }

    private function criarIndice($tabela, $nome, $coluna, $unico = false)
    {
        if ($this->temIndice($tabela, $nome)) {
            return;
        }
        $this->db->query('ALTER TABLE `' . $tabela . '` ADD ' . ($unico ? 'UNIQUE ' : '') . 'INDEX `' . $nome . '` (`' . $coluna . '`)');
    }

    private function soltarIndice($tabela, $nome)
    {
        if ($this->temIndice($tabela, $nome)) {
            $this->db->query('ALTER TABLE `' . $tabela . '` DROP INDEX `' . $nome . '`');
        }
    }
}
