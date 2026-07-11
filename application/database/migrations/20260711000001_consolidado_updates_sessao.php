<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Consolidação dos updates manuais da sessão em uma única migration.
 *
 * Reúne o que antes eram vários updates/*.sql (portal multi-CNPJ, aceite do
 * serviço, número de notificação por cliente, grupos/modelo de WhatsApp nos
 * gatilhos, logs de WhatsApp e e-mail, modelos de WhatsApp, códigos de
 * tributação padrão da NFS-e, descrição do serviço na nota e a paleta de tags
 * do modelo de cobrança). Tudo idempotente: cada passo só roda se faltar, então
 * é seguro mesmo onde algum SQL já foi aplicado à mão.
 */
class Migration_consolidado_updates_sessao extends CI_Migration
{
    public function up()
    {
        /* 1) Portal do cliente multi-CNPJ ------------------------------- */
        $this->db->query(
            'CREATE TABLE IF NOT EXISTS `clientes_vinculos` (
                `id`                INT(11)   NOT NULL AUTO_INCREMENT,
                `cliente_master_id` INT(11)   NOT NULL,
                `cliente_id`        INT(11)   NOT NULL,
                `data_cadastro`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_master_cliente` (`cliente_master_id`, `cliente_id`),
                KEY `idx_master` (`cliente_master_id`),
                KEY `idx_cliente` (`cliente_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

        /* 2) Aceite do serviço realizado (os.*) ------------------------- */
        $this->addColuna('os', 'aceite_token', "`aceite_token` VARCHAR(64) NULL DEFAULT NULL");
        $this->addColuna('os', 'aceite_status', "`aceite_status` VARCHAR(20) NULL DEFAULT NULL");
        $this->addColuna('os', 'aceite_expira', "`aceite_expira` DATETIME NULL DEFAULT NULL");
        $this->addColuna('os', 'aceite_data', "`aceite_data` DATETIME NULL DEFAULT NULL");
        $this->addColuna('os', 'aceite_nome', "`aceite_nome` VARCHAR(150) NULL DEFAULT NULL");
        $this->addColuna('os', 'aceite_ip', "`aceite_ip` VARCHAR(45) NULL DEFAULT NULL");
        $this->addColuna('os', 'aceite_obs', "`aceite_obs` TEXT NULL DEFAULT NULL");
        $this->addColuna('os', 'aceite_assinatura_id', "`aceite_assinatura_id` INT(11) NULL DEFAULT NULL");
        $this->addIndice('os', 'idx_os_aceite_token', 'ALTER TABLE `os` ADD INDEX `idx_os_aceite_token` (`aceite_token`)');

        /* 3) Número de notificação (WhatsApp) por cliente --------------- */
        $this->addColuna('clientes', 'whatsapp_notificacao', "`whatsapp_notificacao` VARCHAR(20) NULL DEFAULT NULL");

        /* 4) Gatilhos: grupos e modelo de WhatsApp ---------------------- */
        $this->addColuna('notification_triggers', 'whatsapp_grupos', "`whatsapp_grupos` TEXT NULL DEFAULT NULL");
        $this->addColuna('notification_triggers', 'whatsapp_template', "`whatsapp_template` VARCHAR(40) NULL DEFAULT NULL");

        /* 5) Log de envios de WhatsApp ---------------------------------- */
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `whatsapp_envios` (
                `id`         INT(11)      NOT NULL AUTO_INCREMENT,
                `data_envio` DATETIME     NOT NULL,
                `destino`    VARCHAR(120) NULL,
                `tipo`       VARCHAR(30)  NULL,
                `os_id`      INT(11)      NULL,
                `evento`     VARCHAR(80)  NULL,
                `status`     VARCHAR(20)  NOT NULL DEFAULT 'enviado',
                `erro`       TEXT         NULL,
                `retorno`    VARCHAR(120) NULL,
                `mensagem`   TEXT         NULL,
                PRIMARY KEY (`id`),
                KEY `idx_data` (`data_envio`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        /* 6) Log de envios de e-mail (+ vínculo com a fila) ------------- */
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `email_envios` (
                `id`         INT(11)      NOT NULL AUTO_INCREMENT,
                `data_envio` DATETIME     NOT NULL,
                `destino`    VARCHAR(255) NULL,
                `assunto`    VARCHAR(255) NULL,
                `tipo`       VARCHAR(30)  NULL,
                `status`     VARCHAR(20)  NOT NULL DEFAULT 'enviado',
                `erro`       TEXT         NULL,
                `queue_id`   INT(11)      NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_data` (`data_envio`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
        // Instalações que já tinham a email_envios sem a coluna de vínculo.
        $this->addColuna('email_envios', 'queue_id', "`queue_id` INT(11) NULL DEFAULT NULL");

        /* 7) Modelos de mensagens de WhatsApp + seeds ------------------- */
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `whatsapp_templates` (
                `id`        INT(11)      NOT NULL AUTO_INCREMENT,
                `slug`      VARCHAR(40)  NOT NULL,
                `nome`      VARCHAR(120) NOT NULL,
                `descricao` VARCHAR(255) NULL,
                `tags`      VARCHAR(400) NULL,
                `conteudo`  TEXT         NULL,
                `ativo`     TINYINT(1)   NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
        $this->seedWhatsappTemplates();

        /* 8) Códigos de tributação padrão da NFS-e ---------------------- */
        $this->addColuna('configuracoes_nfe', 'ctribnac_padrao', "`ctribnac_padrao` VARCHAR(6) NOT NULL DEFAULT '010701'");
        $this->addColuna('configuracoes_nfe', 'ctribmun_padrao', "`ctribmun_padrao` VARCHAR(10) NOT NULL DEFAULT '100'");
        if ($this->db->table_exists('configuracoes_nfe')) {
            $this->db->query("UPDATE `configuracoes_nfe` SET `ctribmun_padrao` = '100' WHERE `ctribmun_padrao` IS NULL OR `ctribmun_padrao` = ''");
        }

        /* 9) Descrição do serviço persistida na nota (p/ o boleto) ------ */
        $this->addColuna('notas_fiscais', 'descricao_servico', "`descricao_servico` TEXT NULL DEFAULT NULL");

        /* 10) Paleta de tags do modelo de e-mail de cobrança ------------ */
        if ($this->db->table_exists('email_templates')) {
            $this->db->where('slug', 'cobranca')->update('email_templates', [
                'tags' => 'cliente_nome, cliente_email, empresa_nome, cobranca_numero, cobranca_valor, cobranca_vencimento, cobranca_descricao, cobranca_pagamento_html, cobranca_link, cobranca_pdf, cobranca_barcode, cobranca_pix, os_numero, os_status, os_data_inicial, os_data_final, os_garantia, os_aprovador, os_descricao, os_defeito, os_observacoes, os_laudo, os_produtos_html, os_servicos_html, os_itens_html, os_valor_total, data_atual',
            ]);
        }
    }

    public function down()
    {
        foreach (['clientes_vinculos', 'whatsapp_envios', 'whatsapp_templates', 'email_envios'] as $t) {
            if ($this->db->table_exists($t)) {
                $this->dbforge->drop_table($t, true);
            }
        }

        $colunas = [
            'os' => ['aceite_token', 'aceite_status', 'aceite_expira', 'aceite_data', 'aceite_nome', 'aceite_ip', 'aceite_obs', 'aceite_assinatura_id'],
            'clientes' => ['whatsapp_notificacao'],
            'notification_triggers' => ['whatsapp_grupos', 'whatsapp_template'],
            'configuracoes_nfe' => ['ctribnac_padrao', 'ctribmun_padrao'],
            'notas_fiscais' => ['descricao_servico'],
        ];
        foreach ($colunas as $tabela => $cols) {
            foreach ($cols as $c) {
                if ($this->db->field_exists($c, $tabela)) {
                    $this->dbforge->drop_column($tabela, $c);
                }
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                             */
    /* ------------------------------------------------------------------ */

    /** Adiciona uma coluna só se a tabela existir e a coluna faltar. */
    private function addColuna($tabela, $coluna, $definicao)
    {
        if ($this->db->table_exists($tabela) && ! $this->db->field_exists($coluna, $tabela)) {
            $this->db->query("ALTER TABLE `{$tabela}` ADD COLUMN {$definicao}");
        }
    }

    /** Cria um índice só se a tabela existir e o índice ainda não existir. */
    private function addIndice($tabela, $indice, $sqlCriacao)
    {
        if (! $this->db->table_exists($tabela)) {
            return;
        }
        $existe = $this->db->query(
            'SELECT 1 FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$tabela, $indice]
        )->num_rows();
        if (! $existe) {
            $this->db->query($sqlCriacao);
        }
    }

    /** Semeia os modelos de WhatsApp (só os slugs ainda inexistentes). */
    private function seedWhatsappTemplates()
    {
        if (! $this->db->table_exists('whatsapp_templates')) {
            return;
        }

        // Modelo da OS: herda o texto atual de configuracoes.notifica_whats.
        if ($this->db->where('slug', 'os')->count_all_results('whatsapp_templates') == 0) {
            $row = $this->db->where('config', 'notifica_whats')->limit(1)->get('configuracoes')->row();
            $conteudo = ($row && trim((string) $row->valor) !== '')
                ? $row->valor
                : 'Olá {CLIENTE_NOME}, sua Ordem de Serviço #{NUMERO_OS} está com status: {STATUS_OS}.';
            $this->db->insert('whatsapp_templates', [
                'slug' => 'os',
                'nome' => 'Notificação da OS',
                'descricao' => 'Usado nas notificações de Ordem de Serviço (gatilhos e envio manual).',
                'tags' => '{CLIENTE_NOME},{NUMERO_OS},{STATUS_OS},{VALOR_OS},{DESCRI_PRODUTOS},{EMITENTE},{TELEFONE_EMITENTE},{OBS_OS},{DEFEITO_OS},{LAUDO_OS},{DATA_FINAL},{DATA_INICIAL},{DATA_GARANTIA}',
                'conteudo' => $conteudo,
                'ativo' => 1,
            ]);
        }

        $outros = [
            ['cobranca', 'Cobrança / Link de pagamento', 'Enviado ao mandar o link de pagamento/boleto por WhatsApp.', '{CLIENTE_NOME},{REFERENCIA},{LINK}', "Olá {CLIENTE_NOME}! Segue o link para pagamento da {REFERENCIA}:\n{LINK}"],
            ['aprovacao', 'Link de aprovação', 'Enviado ao mandar o link de aprovação da OS por WhatsApp.', '{CLIENTE_NOME},{NUMERO_OS},{LINK}', "Olá {CLIENTE_NOME}! Para aprovar ou reprovar a OS #{NUMERO_OS}, acesse o link:\n{LINK}"],
            ['aceite', 'Link de aceite do serviço', 'Enviado ao mandar o link de aceite do serviço realizado por WhatsApp.', '{CLIENTE_NOME},{NUMERO_OS},{LINK}', "Olá {CLIENTE_NOME}! Seu serviço (OS #{NUMERO_OS}) foi concluído. Confirme o aceite e assine pelo link:\n{LINK}"],
        ];
        foreach ($outros as $t) {
            if ($this->db->where('slug', $t[0])->count_all_results('whatsapp_templates') == 0) {
                $this->db->insert('whatsapp_templates', [
                    'slug' => $t[0],
                    'nome' => $t[1],
                    'descricao' => $t[2],
                    'tags' => $t[3],
                    'conteudo' => $t[4],
                    'ativo' => 1,
                ]);
            }
        }
    }
}
