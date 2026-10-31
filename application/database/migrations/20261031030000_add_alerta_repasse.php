<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Alerta de ponto parado (ADR-014): guarda quando o tecnico e o gestor foram
 * avisados de cada ponto, para nao repetir o alerta antes da janela. Idempotente.
 */
class Migration_add_alerta_repasse extends CI_Migration
{
    private $colunas = ['alertado_em', 'alertado_gestor_em'];

    public function up()
    {
        if (! $this->db->table_exists('obra_pontos')) {
            return;
        }
        foreach ($this->colunas as $nome) {
            if (! $this->db->field_exists($nome, 'obra_pontos')) {
                $this->dbforge->add_column('obra_pontos', [$nome => ['type' => 'DATETIME', 'null' => true]]);
            }
        }

        log_message('info', 'Migration add_alerta_repasse executada com sucesso');
    }

    public function down()
    {
        if (! $this->db->table_exists('obra_pontos')) {
            return;
        }
        foreach ($this->colunas as $nome) {
            if ($this->db->field_exists($nome, 'obra_pontos')) {
                $this->dbforge->drop_column('obra_pontos', $nome);
            }
        }
    }
}
