<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Campos de aprovação da OS via link público temporário.
 *
 * Gera um link sem login para o cliente aprovar/reprovar a Ordem de Serviço.
 * Antes existia só como updates/update_os_aprovacao.sql (aplicado à mão); aqui
 * vira migration para o deploy ser apenas `php index.php tools migrate`.
 *
 *  aprovacao_status: pendente | aprovado | reprovado (NULL = sem link)
 *  aprovacao_expira: data/hora limite de validade do link
 *
 * Idempotente: cada coluna/índice só é criado se ainda faltar.
 */
class Migration_add_os_aprovacao extends CI_Migration
{
    private $colunas = [
        'aprovacao_token' => "`aprovacao_token` VARCHAR(64) NULL DEFAULT NULL",
        'aprovacao_status' => "`aprovacao_status` VARCHAR(20) NULL DEFAULT NULL",
        'aprovacao_expira' => "`aprovacao_expira` DATETIME NULL DEFAULT NULL",
        'aprovacao_data' => "`aprovacao_data` DATETIME NULL DEFAULT NULL",
        'aprovacao_nome' => "`aprovacao_nome` VARCHAR(150) NULL DEFAULT NULL",
        'aprovacao_ip' => "`aprovacao_ip` VARCHAR(45) NULL DEFAULT NULL",
        'aprovacao_obs' => "`aprovacao_obs` TEXT NULL DEFAULT NULL",
    ];

    public function up()
    {
        if (! $this->db->table_exists('os')) {
            return;
        }

        foreach ($this->colunas as $coluna => $definicao) {
            if (! $this->db->field_exists($coluna, 'os')) {
                $this->db->query("ALTER TABLE `os` ADD COLUMN {$definicao}");
            }
        }

        $existeIndice = $this->db->query(
            'SELECT 1 FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            ['os', 'idx_os_aprovacao_token']
        )->num_rows();
        if (! $existeIndice) {
            $this->db->query('ALTER TABLE `os` ADD INDEX `idx_os_aprovacao_token` (`aprovacao_token`)');
        }
    }

    public function down()
    {
        foreach (array_keys($this->colunas) as $coluna) {
            if ($this->db->field_exists($coluna, 'os')) {
                $this->dbforge->drop_column('os', $coluna);
            }
        }
    }
}
