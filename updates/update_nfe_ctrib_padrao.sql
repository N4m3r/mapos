-- ============================================================
-- CÓDIGOS DE TRIBUTAÇÃO PADRÃO (NFS-e)
-- ============================================================
--   ctribnac_padrao (6 díg., default 010701 = suporte em informática)
--   ctribmun_padrao (3 díg., default 100)
-- Validados por NFS-e AUTORIZADA de Manaus (cTribNac 010701 + cTribMun 100).
-- Usados na emissão automática (com boleto) e como sugestão no wizard quando
-- o serviço não tem código. Idempotente: só adiciona as colunas se faltarem.
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
            ALTER TABLE `configuracoes_nfe` ADD COLUMN `ctribmun_padrao` VARCHAR(10) NOT NULL DEFAULT '100';
        END IF;
        -- Repõe o padrão 100 onde ficou vazio (versão anterior chegou a limpar).
        UPDATE `configuracoes_nfe`
           SET `ctribmun_padrao` = '100'
         WHERE `ctribmun_padrao` IS NULL OR `ctribmun_padrao` = '';
    END IF;
END$$
DELIMITER ;
CALL `mapos_add_ctrib_cols`();
DROP PROCEDURE IF EXISTS `mapos_add_ctrib_cols`;
