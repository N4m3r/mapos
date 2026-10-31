<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Link publico de acompanhamento da OS e pesquisa de satisfacao NPS (ADR-007).
 *
 * Idempotente.
 */
class Migration_add_acompanhamento_nps extends CI_Migration
{
    public function up()
    {
        if (! $this->db->field_exists('acompanhar_token', 'os')) {
            $this->db->query('ALTER TABLE `os` ADD `acompanhar_token` VARCHAR(64) NULL DEFAULT NULL');
            $this->db->query('ALTER TABLE `os` ADD INDEX `idx_os_acompanhar_token` (`acompanhar_token`)');
        }

        if (! $this->db->table_exists('os_avaliacoes')) {
            $this->db->query("CREATE TABLE `os_avaliacoes` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `os_id` INT(11) NOT NULL,
                `nota` TINYINT(2) NOT NULL COMMENT '0 a 10 (NPS)',
                `comentario` VARCHAR(1000) NULL,
                `ip` VARCHAR(45) NULL,
                `criado_em` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_os_avaliacao` (`os_id`),
                KEY `idx_criado` (`criado_em`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function down()
    {
        if ($this->db->table_exists('os_avaliacoes')) {
            $this->dbforge->drop_table('os_avaliacoes');
        }
        if ($this->db->field_exists('acompanhar_token', 'os')) {
            $this->db->query('ALTER TABLE `os` DROP INDEX `idx_os_acompanhar_token`, DROP COLUMN `acompanhar_token`');
        }
    }
}
