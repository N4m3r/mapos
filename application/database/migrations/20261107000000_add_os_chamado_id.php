<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Identificador do chamado/protocolo de origem informado pelo cliente.
 *
 * Serve para o cliente citar o número ao solicitar aprovação de um orçamento
 * e para a equipe localizar a OS pela mesma referência usada no atendimento.
 * Idempotente.
 */
class Migration_add_os_chamado_id extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('os')) {
            return;
        }

        if (! $this->db->field_exists('chamado_id', 'os')) {
            $this->db->query('ALTER TABLE `os` ADD COLUMN `chamado_id` VARCHAR(60) NULL DEFAULT NULL');
        }

        $temIndice = $this->db->query(
            'SELECT 1 FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            ['os', 'idx_os_chamado_id']
        )->num_rows();
        if (! $temIndice) {
            $this->db->query('ALTER TABLE `os` ADD INDEX `idx_os_chamado_id` (`chamado_id`)');
        }

        log_message('info', 'Migration add_os_chamado_id executada com sucesso');
    }

    public function down()
    {
        if (! $this->db->table_exists('os') || ! $this->db->field_exists('chamado_id', 'os')) {
            return;
        }

        $temIndice = $this->db->query(
            'SELECT 1 FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            ['os', 'idx_os_chamado_id']
        )->num_rows();
        if ($temIndice) {
            $this->db->query('ALTER TABLE `os` DROP INDEX `idx_os_chamado_id`');
        }

        $this->dbforge->drop_column('os', 'chamado_id');
    }
}
