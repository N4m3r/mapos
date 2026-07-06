-- ============================================================
-- SISTEMA DE APROVAÇÃO DE OS VIA LINK TEMPORÁRIO
-- ============================================================
-- Adiciona os campos necessários para gerar um link público e
-- temporário de aprovação/reprovação da Ordem de Serviço pelo
-- cliente (sem necessidade de login).
--
-- aprovacao_status: pendente | aprovado | reprovado (NULL = sem link)
-- aprovacao_expira: data/hora limite de validade do link
-- ============================================================

ALTER TABLE `os`
    ADD COLUMN `aprovacao_token`  VARCHAR(64)  NULL DEFAULT NULL AFTER `faturado`,
    ADD COLUMN `aprovacao_status` VARCHAR(20)  NULL DEFAULT NULL AFTER `aprovacao_token`,
    ADD COLUMN `aprovacao_expira` DATETIME     NULL DEFAULT NULL AFTER `aprovacao_status`,
    ADD COLUMN `aprovacao_data`   DATETIME     NULL DEFAULT NULL AFTER `aprovacao_expira`,
    ADD COLUMN `aprovacao_nome`   VARCHAR(150) NULL DEFAULT NULL AFTER `aprovacao_data`,
    ADD COLUMN `aprovacao_ip`     VARCHAR(45)  NULL DEFAULT NULL AFTER `aprovacao_nome`,
    ADD COLUMN `aprovacao_obs`    TEXT         NULL DEFAULT NULL AFTER `aprovacao_ip`,
    ADD INDEX `idx_os_aprovacao_token` (`aprovacao_token` ASC);
