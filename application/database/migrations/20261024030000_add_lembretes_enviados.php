<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Registro dos lembretes automaticos enviados ao cliente (ADR-005, F6),
 * para nao mandar o mesmo lembrete duas vezes.
 *
 * Idempotente.
 */
class Migration_add_lembretes_enviados extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('lembretes_enviados')) {
            $this->db->query("CREATE TABLE `lembretes_enviados` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `os_id` INT(11) NOT NULL,
                `tipo` VARCHAR(20) NOT NULL COMMENT 'visita, orcamento, retirada',
                `chave` VARCHAR(40) NOT NULL COMMENT 'o que torna o lembrete unico (ex.: data da visita)',
                `canais` VARCHAR(40) NULL COMMENT 'email, whatsapp',
                `enviado_em` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_os_tipo_chave` (`os_id`, `tipo`, `chave`),
                KEY `idx_enviado` (`enviado_em`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function down()
    {
        if ($this->db->table_exists('lembretes_enviados')) {
            $this->dbforge->drop_table('lembretes_enviados');
        }
    }
}
