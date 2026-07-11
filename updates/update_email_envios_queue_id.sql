-- ============================================================
-- VÍNCULO LOG DE E-MAIL -> LINHA DA FILA (para "Reenviar" com anexos)
-- ============================================================
-- Guarda em email_envios o id da linha de email_queue que gerou o envio.
-- Assim o botão "Reenviar" do histórico reenfileira a MESMA mensagem, com os
-- mesmos anexos (email_queue.attachments). Idempotente.
-- ============================================================

DROP PROCEDURE IF EXISTS `mapos_add_email_envios_queue_id`;
DELIMITER $$
CREATE PROCEDURE `mapos_add_email_envios_queue_id`()
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.TABLES
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'email_envios') THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'email_envios'
                         AND COLUMN_NAME = 'queue_id') THEN
            ALTER TABLE `email_envios` ADD COLUMN `queue_id` INT(11) NULL DEFAULT NULL;
        END IF;
    END IF;
END$$
DELIMITER ;
CALL `mapos_add_email_envios_queue_id`();
DROP PROCEDURE IF EXISTS `mapos_add_email_envios_queue_id`;
