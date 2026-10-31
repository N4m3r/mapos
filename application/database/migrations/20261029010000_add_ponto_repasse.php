<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Repasse de pontos entre tecnicos e fotos por ponto (ADR-014). Idempotente.
 */
class Migration_add_ponto_repasse extends CI_Migration
{
    private function addColunas($tabela, array $colunas)
    {
        if (! $this->db->table_exists($tabela)) {
            return;
        }
        foreach ($colunas as $nome => $def) {
            if (! $this->db->field_exists($nome, $tabela)) {
                $this->dbforge->add_column($tabela, [$nome => $def]);
            }
        }
    }

    private function temIndice($tabela, $nome)
    {
        $r = $this->db->query('SHOW INDEX FROM ' . $this->db->protect_identifiers($tabela, true) . ' WHERE Key_name = ?', [$nome]);
        return $r->num_rows() > 0;
    }

    public function up()
    {
        $this->addColunas('obra_pontos', [
            'responsavel_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'aguardando' => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true],
            'repassado_por' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'repassado_em' => ['type' => 'DATETIME', 'null' => true],
            'instrucao' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        if ($this->db->table_exists('obra_pontos') && ! $this->temIndice('obra_pontos', 'idx_ponto_responsavel')) {
            $this->db->query('CREATE INDEX idx_ponto_responsavel ON ' . $this->db->protect_identifiers('obra_pontos', true) . ' (responsavel_id, aguardando)');
        }

        $this->addColunas('obra_ponto_historico', [
            'evento' => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'status'],
            'destinatario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ]);

        if (! $this->db->table_exists('obra_ponto_foto')) {
            $this->dbforge->add_field([
                'idFoto' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'ponto_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'obra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'historico_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'etapa' => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true],
                'foto' => ['type' => 'LONGBLOB'],
                'legenda' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'data' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('idFoto', true);
            $this->dbforge->add_key('ponto_id');
            $this->dbforge->add_key('obra_id');
            $this->dbforge->create_table('obra_ponto_foto', true, ['ENGINE' => 'InnoDB']);
        }

        log_message('info', 'Migration add_ponto_repasse executada com sucesso');
    }

    public function down()
    {
        if ($this->db->table_exists('obra_ponto_foto')) {
            $this->dbforge->drop_table('obra_ponto_foto', true);
        }
        if ($this->db->table_exists('obra_pontos') && $this->temIndice('obra_pontos', 'idx_ponto_responsavel')) {
            $this->db->query('DROP INDEX idx_ponto_responsavel ON ' . $this->db->protect_identifiers('obra_pontos', true));
        }
        foreach (['obra_ponto_historico' => ['evento', 'destinatario_id'],
            'obra_pontos' => ['responsavel_id', 'aguardando', 'repassado_por', 'repassado_em', 'instrucao']] as $tabela => $cols) {
            if (! $this->db->table_exists($tabela)) {
                continue;
            }
            foreach ($cols as $c) {
                if ($this->db->field_exists($c, $tabela)) {
                    $this->dbforge->drop_column($tabela, $c);
                }
            }
        }
    }
}
