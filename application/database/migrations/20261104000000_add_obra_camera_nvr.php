<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Configuração de câmeras e NVR pelo app mobile (Hikvision).
 *
 * Guarda só dados técnicos descobertos na instalação (MAC, canal do NVR,
 * firmware, se a câmera já foi ativada). Senhas de NVR e câmera continuam fora
 * do banco: o app as usa apenas enquanto configura o equipamento.
 * Idempotente.
 */
class Migration_add_obra_camera_nvr extends CI_Migration
{
    private function temIndice($tabela, $nome)
    {
        $r = $this->db->query('SHOW INDEX FROM ' . $this->db->protect_identifiers($tabela, true) . ' WHERE Key_name = ?', [$nome]);

        return $r && $r->num_rows() > 0;
    }

    public function up()
    {
        if (! $this->db->table_exists('obra_pontos')) {
            return;
        }

        $colunas = [
            'mac' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'nvr_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'sadp_id' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'fabricante' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'firmware' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'ativada' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'configurada_em' => ['type' => 'DATETIME', 'null' => true],
        ];
        foreach ($colunas as $nome => $def) {
            if (! $this->db->field_exists($nome, 'obra_pontos')) {
                $this->dbforge->add_column('obra_pontos', [$nome => $def]);
            }
        }

        if (! $this->temIndice('obra_pontos', 'idx_ponto_nvr')) {
            $this->db->query('CREATE INDEX idx_ponto_nvr ON ' . $this->db->protect_identifiers('obra_pontos', true) . ' (obra_id, nvr_id, canal)');
        }
        if (! $this->temIndice('obra_pontos', 'idx_ponto_mac')) {
            $this->db->query('CREATE INDEX idx_ponto_mac ON ' . $this->db->protect_identifiers('obra_pontos', true) . ' (obra_id, mac)');
        }

        log_message('info', 'Migration add_obra_camera_nvr executada com sucesso');
    }

    public function down()
    {
        if (! $this->db->table_exists('obra_pontos')) {
            return;
        }
        foreach (['idx_ponto_nvr', 'idx_ponto_mac'] as $indice) {
            if ($this->temIndice('obra_pontos', $indice)) {
                $this->db->query('DROP INDEX ' . $this->db->protect_identifiers($indice, true) . ' ON ' . $this->db->protect_identifiers('obra_pontos', true));
            }
        }
        foreach (['mac', 'nvr_id', 'sadp_id', 'fabricante', 'firmware', 'ativada', 'configurada_em'] as $coluna) {
            if ($this->db->field_exists($coluna, 'obra_pontos')) {
                $this->dbforge->drop_column('obra_pontos', $coluna);
            }
        }
    }
}
