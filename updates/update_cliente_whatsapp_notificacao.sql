-- ============================================================
-- NÚMERO DE NOTIFICAÇÃO (WhatsApp) POR CLIENTE
-- ============================================================
-- Número dedicado para onde as notificações WhatsApp do cliente são
-- enviadas. Se vazio, o sistema usa o Celular do cliente (fallback).
-- ============================================================

ALTER TABLE `clientes`
    ADD COLUMN `whatsapp_notificacao` VARCHAR(20) NULL DEFAULT NULL AFTER `celular`;
