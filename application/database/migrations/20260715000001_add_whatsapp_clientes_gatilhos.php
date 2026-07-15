<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Gatilhos de notificação: filtro opcional de clientes para envio
 * ao grupo WhatsApp (modelo + grupo só disparam para OS desses clientes).
 */
class Migration_add_whatsapp_clientes_gatilhos extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('notification_triggers')) {
            return;
        }
        if ($this->db->field_exists('whatsapp_clientes', 'notification_triggers')) {
            return;
        }

        $this->db->query(
            'ALTER TABLE `notification_triggers`
             ADD COLUMN `whatsapp_clientes` TEXT NULL DEFAULT NULL
             COMMENT \'IDs de clientes (csv). Vazio = todos. Restringe o envio do modelo aos grupos WhatsApp.\''
        );
    }

    public function down()
    {
        if ($this->db->table_exists('notification_triggers')
            && $this->db->field_exists('whatsapp_clientes', 'notification_triggers')) {
            $this->dbforge->drop_column('notification_triggers', 'whatsapp_clientes');
        }
    }
}
