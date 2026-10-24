<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Filtro de projeto no gatilho: permite escolher quais projetos disparam um
 * gatilho específico (ex.: o RDO de uma obra dispara pra um e-mail/grupo, o de
 * outra obra dispara pra outro gatilho). Sem projeto selecionado, vale para
 * todos — mesmo comportamento do filtro de clientes (whatsapp_clientes) já
 * existente.
 *
 * Idempotente.
 */
class Migration_add_projetos_filtro_gatilho extends CI_Migration
{
    public function up()
    {
        $this->addColuna('notification_triggers', 'projetos_id', "`projetos_id` TEXT NULL DEFAULT NULL AFTER `email_conversa`");

        log_message('info', 'Migration add_projetos_filtro_gatilho executada com sucesso');
    }

    public function down()
    {
        if ($this->db->field_exists('projetos_id', 'notification_triggers')) {
            $this->dbforge->drop_column('notification_triggers', 'projetos_id');
        }
    }

    /** Adiciona uma coluna só se a tabela existir e a coluna faltar. */
    private function addColuna($tabela, $coluna, $definicao)
    {
        if ($this->db->table_exists($tabela) && ! $this->db->field_exists($coluna, $tabela)) {
            $this->db->query("ALTER TABLE `{$tabela}` ADD COLUMN {$definicao}");
        }
    }
}
