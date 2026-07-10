-- ============================================================
-- LOG DE ENVIOS DE E-MAIL (auditoria)
-- ============================================================
-- Registra cada envio de e-mail (fila e teste), com status e o motivo da
-- falha, para acompanhar em Configurações > E-mail > "Log de envios".
-- ============================================================

CREATE TABLE IF NOT EXISTS `email_envios` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `data_envio` DATETIME     NOT NULL,
    `destino`    VARCHAR(255) NULL,
    `assunto`    VARCHAR(255) NULL,
    `tipo`       VARCHAR(30)  NULL,
    `status`     VARCHAR(20)  NOT NULL DEFAULT 'enviado',
    `erro`       TEXT         NULL,
    PRIMARY KEY (`id`),
    KEY `idx_data` (`data_envio`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
