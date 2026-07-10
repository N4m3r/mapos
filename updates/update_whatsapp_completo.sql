-- =====================================================================
-- ATUALIZAÇÃO COMBINADA — recursos recentes de WhatsApp (rodar UMA vez)
-- =====================================================================
-- Idempotente (seguro re-rodar). Cria:
--   1) coluna `whatsapp_grupos` em `notification_triggers` (grupos no gatilho)
--   2) tabela `whatsapp_envios` (log/auditoria de envios)
--   3) tabela `whatsapp_templates` (modelos de mensagem editáveis) + seeds
--
-- phpMyAdmin: aba Importar > este arquivo. Ou:
--   mysql -h HOST -u USUARIO -p BANCO < updates/update_whatsapp_completo.sql
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) Coluna whatsapp_grupos em notification_triggers (só se não existir)
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `mapos_add_grupos_col`;
DELIMITER $$
CREATE PROCEDURE `mapos_add_grupos_col`()
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.TABLES
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notification_triggers')
       AND NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                       WHERE TABLE_SCHEMA = DATABASE()
                         AND TABLE_NAME = 'notification_triggers'
                         AND COLUMN_NAME = 'whatsapp_grupos') THEN
        ALTER TABLE `notification_triggers` ADD COLUMN `whatsapp_grupos` TEXT NULL DEFAULT NULL;
    END IF;
END$$
DELIMITER ;
CALL `mapos_add_grupos_col`();
DROP PROCEDURE IF EXISTS `mapos_add_grupos_col`;

-- ---------------------------------------------------------------------
-- 2) Log de envios de WhatsApp
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `whatsapp_envios` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- 3) Modelos (templates) de mensagens de WhatsApp + seeds
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `whatsapp_templates` (
    `id`        INT(11)      NOT NULL AUTO_INCREMENT,
    `slug`      VARCHAR(40)  NOT NULL,
    `nome`      VARCHAR(120) NOT NULL,
    `descricao` VARCHAR(255) NULL,
    `tags`      VARCHAR(400) NULL,
    `conteudo`  TEXT         NULL,
    `ativo`     TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `whatsapp_templates` (`slug`, `nome`, `descricao`, `tags`, `conteudo`, `ativo`)
SELECT 'os',
       'Notificação da OS',
       'Usado nas notificações de Ordem de Serviço (gatilhos e envio manual).',
       '{CLIENTE_NOME},{NUMERO_OS},{STATUS_OS},{VALOR_OS},{DESCRI_PRODUTOS},{EMITENTE},{TELEFONE_EMITENTE},{OBS_OS},{DEFEITO_OS},{LAUDO_OS},{DATA_FINAL},{DATA_INICIAL},{DATA_GARANTIA}',
       COALESCE((SELECT `valor` FROM `configuracoes` WHERE `config` = 'notifica_whats' LIMIT 1),
                'Olá {CLIENTE_NOME}, sua Ordem de Serviço #{NUMERO_OS} está com status: {STATUS_OS}.'),
       1;

INSERT IGNORE INTO `whatsapp_templates` (`slug`, `nome`, `descricao`, `tags`, `conteudo`, `ativo`) VALUES
('cobranca', 'Cobrança / Link de pagamento', 'Enviado ao mandar o link de pagamento/boleto por WhatsApp.',
 '{CLIENTE_NOME},{REFERENCIA},{LINK}',
 'Olá {CLIENTE_NOME}! Segue o link para pagamento da {REFERENCIA}:\n{LINK}', 1),
('aprovacao', 'Link de aprovação', 'Enviado ao mandar o link de aprovação da OS por WhatsApp.',
 '{CLIENTE_NOME},{NUMERO_OS},{LINK}',
 'Olá {CLIENTE_NOME}! Para aprovar ou reprovar a OS #{NUMERO_OS}, acesse o link:\n{LINK}', 1),
('aceite', 'Link de aceite do serviço', 'Enviado ao mandar o link de aceite do serviço realizado por WhatsApp.',
 '{CLIENTE_NOME},{NUMERO_OS},{LINK}',
 'Olá {CLIENTE_NOME}! Seu serviço (OS #{NUMERO_OS}) foi concluído. Confirme o aceite e assine pelo link:\n{LINK}', 1);
