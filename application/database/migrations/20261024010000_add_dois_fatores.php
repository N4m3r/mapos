<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Verificacao em duas etapas (ADR-005, F3): codigo de aplicativo autenticador
 * (TOTP) e codigos de recuperacao por usuario.
 *
 * Idempotente.
 */
class Migration_add_dois_fatores extends CI_Migration
{
    private $colunas = [
        'totp_segredo' => "VARCHAR(255) NULL DEFAULT NULL COMMENT 'segredo TOTP cifrado (library Dois_fatores)'",
        'totp_ativo' => "TINYINT(1) NOT NULL DEFAULT 0",
        'totp_recuperacao' => "TEXT NULL COMMENT 'JSON com hashes dos codigos de recuperacao ainda nao usados'",
        'totp_ultimo_passo' => "BIGINT NULL DEFAULT NULL COMMENT 'ultimo passo de 30s aceito (impede reuso do codigo)'",
    ];

    public function up()
    {
        if (! $this->db->table_exists('usuarios')) {
            return;
        }
        foreach ($this->colunas as $coluna => $definicao) {
            if (! $this->db->field_exists($coluna, 'usuarios')) {
                $this->db->query("ALTER TABLE `usuarios` ADD `{$coluna}` {$definicao}");
            }
        }
    }

    public function down()
    {
        foreach (array_keys($this->colunas) as $coluna) {
            if ($this->db->field_exists($coluna, 'usuarios')) {
                $this->db->query("ALTER TABLE `usuarios` DROP COLUMN `{$coluna}`");
            }
        }
    }
}
