-- ============================================================
-- CÓDIGOS DE TRIBUTAÇÃO PADRÃO (NFS-e)
-- ============================================================
-- Defaults usados na emissão automática (com boleto) e como sugestão no
-- wizard quando o serviço não tem código:
--   ctribnac_padrao (6 díg., default 010701 = suporte em informática)
--   ctribmun_padrao (3 díg., default 100)
-- Idempotente: só adiciona as colunas se ainda não existirem.
-- ============================================================

DROP PROCEDURE IF EXISTS `mapos_add_ctrib_cols`;
DELIMITER $$
CREATE PROCEDURE `mapos_add_ctrib_cols`()
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.TABLES
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configuracoes_nfe') THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configuracoes_nfe'
                         AND COLUMN_NAME = 'ctribnac_padrao') THEN
            ALTER TABLE `configuracoes_nfe` ADD COLUMN `ctribnac_padrao` VARCHAR(6) NOT NULL DEFAULT '010701';
        END IF;
        IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configuracoes_nfe'
                         AND COLUMN_NAME = 'ctribmun_padrao') THEN
            ALTER TABLE `configuracoes_nfe` ADD COLUMN `ctribmun_padrao` VARCHAR(3) NOT NULL DEFAULT '100';
        END IF;
    END IF;
END$$
DELIMITER ;
CALL `mapos_add_ctrib_cols`();
DROP PROCEDURE IF EXISTS `mapos_add_ctrib_cols`;
