<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Observações do técnico sobre a atividade realizada (durante o atendimento
 * e nas atividades não programadas). Idempotente.
 */
class Migration_create_os_observacoes_tecnico extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `os_observacoes_tecnico` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `os_id` INT(11) NOT NULL,
            `checkin_id` INT(11) NULL DEFAULT NULL,
            `usuarios_id` INT(11) NOT NULL,
            `texto` TEXT NOT NULL,
            `data_hora` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_os_obs_tec_os` (`os_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS `os_observacoes_tecnico`');
    }
}
