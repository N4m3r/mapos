-- Filtro de clientes nos gatilhos de notificação WhatsApp.
-- Quando preenchido (IDs separados por vírgula), o envio do modelo
-- (e grupos WhatsApp do gatilho) só ocorre se a OS for de um desses
-- clientes. NULL/vazio = todos os clientes (comportamento atual).
--
-- Se a coluna já existir, ignore o erro de duplicidade.

ALTER TABLE `notification_triggers`
    ADD COLUMN `whatsapp_clientes` TEXT NULL DEFAULT NULL
    COMMENT 'IDs de clientes (csv). Vazio = todos. Restringe envio aos grupos WhatsApp.';
