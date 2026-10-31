<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pontos do projeto de seguranca eletronica (ADR-013).
 *
 * obra_pontos: cada camera, leitor, ponto de rede etc. com status de instalacao.
 * obra_ponto_historico: cada mudanca de status, com tecnico, data e metros de cabo.
 */
class Migration_add_obra_pontos extends CI_Migration
{
    public function up()
    {
        $comum = ['ENGINE' => 'InnoDB'];

        if (! $this->db->table_exists('obra_pontos')) {
            $this->dbforge->add_field([
                'idPonto' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'obra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'tipo' => ['type' => 'VARCHAR', 'constraint' => 20],
                'identificacao' => ['type' => 'VARCHAR', 'constraint' => 30], // ex.: CAM-07
                'local' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
                'canal' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true], // canal do NVR / porta do controlador
                'porta' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true], // patch panel / porta do switch
                'modelo' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
                'serie' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true], // serie ou MAC
                'metragem_cabo' => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 0],
                'status' => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'previsto'],
                'observacao' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'atualizado_por' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'atualizado_em' => ['type' => 'DATETIME', 'null' => true],
                'data_cadastro' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idPonto', true);
            $this->dbforge->add_key('obra_id');
            $this->dbforge->create_table('obra_pontos', true, $comum);
        }

        if (! $this->db->table_exists('obra_ponto_historico')) {
            $this->dbforge->add_field([
                'idHistorico' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'ponto_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'obra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'status_anterior' => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true],
                'status_novo' => ['type' => 'VARCHAR', 'constraint' => 15],
                'metragem_cabo' => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 0],
                'usuario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'data' => ['type' => 'DATETIME'],
                'observacao' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            ]);
            $this->dbforge->add_key('idHistorico', true);
            $this->dbforge->add_key('ponto_id');
            $this->dbforge->add_key(['obra_id', 'data']);
            $this->dbforge->create_table('obra_ponto_historico', true, $comum);
        }

        log_message('info', 'Migration add_obra_pontos executada com sucesso');
    }

    public function down()
    {
        foreach (['obra_ponto_historico', 'obra_pontos'] as $tabela) {
            if ($this->db->table_exists($tabela)) {
                $this->dbforge->drop_table($tabela, true);
            }
        }
    }
}
