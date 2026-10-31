<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Conciliacao bancaria por OFX (ADR-008 item 4). Idempotente.
 */
class Migration_add_conciliacao_bancaria extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('conciliacao_itens')) {
            $this->db->query("CREATE TABLE `conciliacao_itens` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `fitid` VARCHAR(100) NOT NULL,
                `conta` VARCHAR(100) NOT NULL,
                `data` DATE NOT NULL,
                `valor` DECIMAL(15,2) NOT NULL,
                `descricao` VARCHAR(255) NULL,
                `lancamentos_id` INT(11) NULL DEFAULT NULL,
                `status` VARCHAR(10) NOT NULL DEFAULT 'pendente',
                `criado_em` DATETIME NOT NULL,
                `usuarios_id` INT(11) NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_conta_fitid` (`conta`, `fitid`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS `conciliacao_itens`');
    }
}
