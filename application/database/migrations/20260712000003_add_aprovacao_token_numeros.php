<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Números extras (WhatsApp) que recebem o código de verificação da aprovação.
 *
 * Um por linha. O código é enviado para o celular do cliente + todos estes.
 *  - clientes.aprovacao_token_numeros: padrão do cliente (vale p/ todas as OS)
 *  - os.aprovacao_token_numeros: avulsos desta OS
 *
 * Idempotente: cada coluna só é criada se ainda faltar.
 */
class Migration_add_aprovacao_token_numeros extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('os') && ! $this->db->field_exists('aprovacao_token_numeros', 'os')) {
            $this->db->query("ALTER TABLE `os` ADD COLUMN `aprovacao_token_numeros` TEXT NULL DEFAULT NULL");
        }

        if ($this->db->table_exists('clientes') && ! $this->db->field_exists('aprovacao_token_numeros', 'clientes')) {
            $this->db->query("ALTER TABLE `clientes` ADD COLUMN `aprovacao_token_numeros` TEXT NULL DEFAULT NULL");
        }
    }

    public function down()
    {
        if ($this->db->field_exists('aprovacao_token_numeros', 'os')) {
            $this->dbforge->drop_column('os', 'aprovacao_token_numeros');
        }
        if ($this->db->field_exists('aprovacao_token_numeros', 'clientes')) {
            $this->dbforge->drop_column('clientes', 'aprovacao_token_numeros');
        }
    }
}
