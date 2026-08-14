<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * RDO por e-mail: o gatilho "RDO de projeto registrado" passa a poder listar
 * e-mails "responsáveis" fixos (fora do cliente) e, opcionalmente, manter os
 * e-mails do projeto como uma única conversa (thread, via In-Reply-To/References)
 * enquanto ele estiver em andamento — com um último e-mail avisando que o
 * projeto foi finalizado.
 *
 * `notification_triggers.email_destinatarios`: e-mails fixos extras, CSV.
 * `notification_triggers.email_conversa`: liga o encadeamento + o e-mail de
 *   finalização. Desligado por padrão — é opt-in por gatilho (checkbox na tela).
 * `obras.email_thread_id`: Message-ID âncora da conversa em andamento.
 * `obras.email_thread_finalizado`: evita reenviar o e-mail de finalização.
 *
 * Idempotente.
 */
class Migration_add_email_conversa_rdo extends CI_Migration
{
    public function up()
    {
        $this->addColuna('notification_triggers', 'email_destinatarios', "`email_destinatarios` TEXT NULL DEFAULT NULL AFTER `destinatarios`");
        $this->addColuna('notification_triggers', 'email_conversa', "`email_conversa` TINYINT(1) NOT NULL DEFAULT 0 AFTER `email_destinatarios`");
        $this->addColuna('obras', 'email_thread_id', "`email_thread_id` VARCHAR(191) NULL DEFAULT NULL");
        $this->addColuna('obras', 'email_thread_finalizado', "`email_thread_finalizado` TINYINT(1) NOT NULL DEFAULT 0");

        log_message('info', 'Migration add_email_conversa_rdo executada com sucesso');
    }

    public function down()
    {
        foreach (['email_destinatarios', 'email_conversa'] as $c) {
            if ($this->db->field_exists($c, 'notification_triggers')) {
                $this->dbforge->drop_column('notification_triggers', $c);
            }
        }
        foreach (['email_thread_id', 'email_thread_finalizado'] as $c) {
            if ($this->db->field_exists($c, 'obras')) {
                $this->dbforge->drop_column('obras', $c);
            }
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
