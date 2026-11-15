<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * usuarios.senha_forca: nivel de seguranca da senha (1 fraca a 4 forte),
 * calculado por senha_forca() no momento em que a senha e definida. O banco
 * so guarda o hash, entao contas antigas ficam NULL ("nao avaliada") ate a
 * proxima troca de senha.
 *
 * Idempotente.
 */
class Migration_add_usuarios_senha_forca extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('usuarios') && ! $this->db->field_exists('senha_forca', 'usuarios')) {
            $this->db->query('ALTER TABLE `usuarios` ADD COLUMN `senha_forca` TINYINT(1) NULL DEFAULT NULL AFTER `senha`');
        }
    }

    public function down()
    {
        if ($this->db->table_exists('usuarios') && $this->db->field_exists('senha_forca', 'usuarios')) {
            $this->dbforge->drop_column('usuarios', 'senha_forca');
        }
    }
}
