<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Módulo de RH — vínculo do ponto com Ordem de Serviço.
 *
 *  - rh_ponto_registros.os_id: batida vinculada a uma OS (atendimento em
 *    campo). Quando vinculada, o geofence é informativo (cada chamado é num
 *    local diferente) — grava-se o GPS como prova.
 *  - os.latitude / os.longitude: local do atendimento (opcional). Permite
 *    mostrar/checar a distância quando a OS tiver coordenadas.
 */
class Migration_add_ponto_os_vinculo extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('rh_ponto_registros')
            && ! $this->db->field_exists('os_id', 'rh_ponto_registros')) {
            $this->dbforge->add_column('rh_ponto_registros', [
                'os_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'OS vinculada à batida (atendimento em campo)',
                    'after' => 'unidade_id',
                ],
            ]);
            $this->db->query('ALTER TABLE `rh_ponto_registros` ADD INDEX `idx_rh_ponto_os` (`os_id`)');
        }

        if ($this->db->table_exists('os')) {
            if (! $this->db->field_exists('latitude', 'os')) {
                $this->dbforge->add_column('os', [
                    'latitude' => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
                ]);
            }
            if (! $this->db->field_exists('longitude', 'os')) {
                $this->dbforge->add_column('os', [
                    'longitude' => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->field_exists('os_id', 'rh_ponto_registros')) {
            $this->dbforge->drop_column('rh_ponto_registros', 'os_id');
        }
        // Mantém os.latitude/longitude (podem ser usados por outros recursos).
    }
}
