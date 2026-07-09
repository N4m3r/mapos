<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_email_secundario_clientes extends CI_Migration
{
    public function up()
    {
        // E-mail secundário (financeiro) do cliente, usado como destinatário
        // adicional no envio de cobranças e boletos da nota fiscal.
        if (! $this->db->field_exists('email_secundario', 'clientes')) {
            $this->dbforge->add_column('clientes', [
                'email_secundario' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'email',
                ],
            ]);
            log_message('info', 'Coluna clientes.email_secundario criada com sucesso');
        }
    }

    public function down()
    {
        if ($this->db->field_exists('email_secundario', 'clientes')) {
            $this->dbforge->drop_column('clientes', 'email_secundario');
        }
    }
}
