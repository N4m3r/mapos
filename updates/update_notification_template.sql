-- ============================================================
-- MODELO DE WHATSAPP POR GATILHO
-- ============================================================
-- Permite escolher, em cada gatilho, qual modelo (whatsapp_templates.slug)
-- será usado na mensagem. Vazio = usa o modelo padrão da OS.
-- Idempotente: só adiciona a coluna se ainda não existir.
-- ============================================================

DROP PROCEDURE IF EXISTS `mapos_add_tpl_col`;
DELIMITER $$
CREATE PROCEDURE `mapos_add_tpl_col`()
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.TABLES
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notification_triggers')
       AND NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                       WHERE TABLE_SCHEMA = DATABASE()
                         AND TABLE_NAME = 'notification_triggers'
                         AND COLUMN_NAME = 'whatsapp_template') THEN
        ALTER TABLE `notification_triggers` ADD COLUMN `whatsapp_template` VARCHAR(40) NULL DEFAULT NULL;
    END IF;
END$$
DELIMITER ;
CALL `mapos_add_tpl_col`();
DROP PROCEDURE IF EXISTS `mapos_add_tpl_col`;
