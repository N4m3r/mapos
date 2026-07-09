<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_attachments_email_queue extends CI_Migration
{
    public function up()
    {
        // Anexos do e-mail: JSON com URLs (públicas) e/ou caminhos locais.
        if (! $this->db->field_exists('attachments', 'email_queue')) {
            $this->dbforge->add_column('email_queue', [
                'attachments' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'headers',
                ],
            ]);
            log_message('info', 'Coluna email_queue.attachments criada com sucesso');
        }
    }

    public function down()
    {
        if ($this->db->field_exists('attachments', 'email_queue')) {
            $this->dbforge->drop_column('email_queue', 'attachments');
        }
    }
}
