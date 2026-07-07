-- ============================================================
-- ACEITE DO SERVIÇO REALIZADO (pós-execução) VIA LINK PÚBLICO
-- ============================================================
-- Diferente da aprovação de orçamento (aprovacao_*), estes campos
-- registram o ACEITE do cliente sobre o serviço já executado, com
-- assinatura digital. Não alteram o status/orçamento da OS.
--
-- aceite_status: pendente | aceito | recusado (NULL = sem link)
-- aceite_assinatura_id: FK logica -> os_assinaturas.idAssinatura
-- ============================================================

ALTER TABLE `os`
    ADD COLUMN `aceite_token`         VARCHAR(64)  NULL DEFAULT NULL,
    ADD COLUMN `aceite_status`        VARCHAR(20)  NULL DEFAULT NULL,
    ADD COLUMN `aceite_expira`        DATETIME     NULL DEFAULT NULL,
    ADD COLUMN `aceite_data`          DATETIME     NULL DEFAULT NULL,
    ADD COLUMN `aceite_nome`          VARCHAR(150) NULL DEFAULT NULL,
    ADD COLUMN `aceite_ip`            VARCHAR(45)  NULL DEFAULT NULL,
    ADD COLUMN `aceite_obs`           TEXT         NULL DEFAULT NULL,
    ADD COLUMN `aceite_assinatura_id` INT(11)      NULL DEFAULT NULL,
    ADD INDEX `idx_os_aceite_token` (`aceite_token` ASC);
