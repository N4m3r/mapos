<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * usuarios.senha_temporaria: 1 quando a senha foi definida pelo administrador
 * para o primeiro acesso. No login, a pessoa e levada a criar a propria senha
 * (Login::novaSenha) e a flag volta a 0.
 *
 * Idempotente.
 */
class Migration_add_usuarios_senha_temporaria extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('usuarios') && ! $this->db->field_exists('senha_temporaria', 'usuarios')) {
            $this->db->query('ALTER TABLE `usuarios` ADD COLUMN `senha_temporaria` TINYINT(1) NOT NULL DEFAULT 0 AFTER `senha`');
        }
    }

    public function down()
    {
        if ($this->db->table_exists('usuarios') && $this->db->field_exists('senha_temporaria', 'usuarios')) {
            $this->dbforge->drop_column('usuarios', 'senha_temporaria');
        }
    }
}
