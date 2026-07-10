-- ============================================================
-- GRUPOS DE WHATSAPP NOS GATILHOS DE NOTIFICAÇÃO
-- ============================================================
-- Guarda os JIDs dos grupos de WhatsApp (ex.: 12036...@g.us,
-- separados por vírgula) para onde o gatilho também dispara a mensagem.
-- Idempotente: só adiciona a coluna se ainda não existir.
-- ============================================================

DROP PROCEDURE IF EXISTS `mapos_add_grupos_col`;
DELIMITER $$
CREATE PROCEDURE `mapos_add_grupos_col`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'notification_triggers'
          AND COLUMN_NAME = 'whatsapp_grupos'
    ) THEN
        ALTER TABLE `notification_triggers` ADD COLUMN `whatsapp_grupos` TEXT NULL DEFAULT NULL;
    END IF;
END$$
DELIMITER ;

CALL `mapos_add_grupos_col`();
DROP PROCEDURE IF EXISTS `mapos_add_grupos_col`;
