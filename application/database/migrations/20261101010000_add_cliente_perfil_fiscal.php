<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Perfil fiscal do cliente (NF-e/NFS-e e reforma tributaria):
 * - ind_ie_dest: 1 = contribuinte ICMS, 2 = isento de IE, 9 = nao contribuinte (NULL = deduzir da IE)
 * - regime_tributario: 'mei', 'simples', 'normal' (NULL = nao informado)
 * - orgao_publico: 1 = orgao publico
 * - inscricao_suframa: 9 digitos (NULL = nao informado)
 *
 * Idempotente: so cria cada coluna se ainda nao existir.
 */
class Migration_add_cliente_perfil_fiscal extends CI_Migration
{
    private const COLUNAS = [
        'ind_ie_dest' => 'TINYINT NULL DEFAULT NULL',
        'regime_tributario' => 'VARCHAR(10) NULL DEFAULT NULL',
        'orgao_publico' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'inscricao_suframa' => 'VARCHAR(9) NULL DEFAULT NULL',
    ];

    public function up()
    {
        if (! $this->db->table_exists('clientes')) {
            return;
        }

        foreach (self::COLUNAS as $coluna => $definicao) {
            if (! $this->db->field_exists($coluna, 'clientes')) {
                $this->db->query("ALTER TABLE `clientes` ADD COLUMN `{$coluna}` {$definicao}");
            }
        }
    }

    public function down()
    {
        foreach (array_keys(self::COLUNAS) as $coluna) {
            if ($this->db->field_exists($coluna, 'clientes')) {
                $this->dbforge->drop_column('clientes', $coluna);
            }
        }
    }
}
