<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Elo Ponto <-> Obra.
 *
 * Permite vincular a batida de ponto do colaborador a uma obra (canteiro),
 * do mesmo modo que já era possível vincular a uma OS. Com isso, o efetivo/
 * mão de obra da obra pode ser consolidado a partir do ponto facial/GPS.
 *
 * Idempotente: só adiciona a coluna/índice se ainda não existirem.
 */
class Migration_add_obra_ponto extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('rh_ponto_registros')
            && ! $this->db->field_exists('obra_id', 'rh_ponto_registros')) {
            $coluna = [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ];
            // Posiciona após os_id apenas se essa coluna existir.
            if ($this->db->field_exists('os_id', 'rh_ponto_registros')) {
                $coluna['after'] = 'os_id';
            }
            $this->dbforge->add_column('rh_ponto_registros', ['obra_id' => $coluna]);
            // Índice para consolidar por obra+data rapidamente.
            $this->db->query('ALTER TABLE `rh_ponto_registros` ADD INDEX `idx_ponto_obra` (`obra_id`)');
        }

        log_message('info', 'Migration add_obra_ponto executada com sucesso');
    }

    public function down()
    {
        if ($this->db->table_exists('rh_ponto_registros')
            && $this->db->field_exists('obra_id', 'rh_ponto_registros')) {
            $this->dbforge->drop_column('rh_ponto_registros', 'obra_id');
        }
    }
}
