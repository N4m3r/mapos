-- ============================================================
-- MODELOS (TEMPLATES) DE MENSAGENS DE WHATSAPP
-- ============================================================
-- Mensagens editáveis por contexto (OS, cobrança, aprovação, aceite),
-- com tags de substituição. Configuráveis em Configurações > Modelos de WhatsApp.
-- ============================================================

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

-- Modelo da OS: herda o texto atual de configuracoes.notifica_whats (fallback default).
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
