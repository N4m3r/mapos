-- ============================================================
-- LOG DE ENVIOS DE WHATSAPP (auditoria)
-- ============================================================
-- Registra cada tentativa de envio via Evolution API (sucesso ou falha),
-- para acompanhar em Configurações > Notificações > "Últimos envios".
-- ============================================================

CREATE TABLE IF NOT EXISTS `whatsapp_envios` (
    `id`         INT(11)     NOT NULL AUTO_INCREMENT,
    `data_envio` DATETIME    NOT NULL,
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
