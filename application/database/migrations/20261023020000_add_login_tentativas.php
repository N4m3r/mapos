<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Registro de tentativas de login erradas (ADR-005, S2), usado pela library
 * Limite_login para bloquear temporariamente ataques de forca bruta.
 *
 * Idempotente.
 */
class Migration_add_login_tentativas extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('login_tentativas')) {
            $this->db->query("CREATE TABLE `login_tentativas` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `chave` CHAR(40) NOT NULL COMMENT 'sha1 de contexto|email|ip',
                `ip` VARCHAR(45) NOT NULL,
                `contexto` VARCHAR(20) NOT NULL COMMENT 'painel, api, api_cliente',
                `criado_em` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_chave_data` (`chave`, `criado_em`),
                KEY `idx_ip_data` (`ip`, `contexto`, `criado_em`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function down()
    {
        if ($this->db->table_exists('login_tentativas')) {
            $this->dbforge->drop_table('login_tentativas');
        }
    }
}
