-- ============================================================
-- CÓDIGOS DE TRIBUTAÇÃO PADRÃO (NFS-e)
-- ============================================================
--   ctribnac_padrao (6 díg., default 010701 = suporte em informática)
--   ctribmun_padrao (opcional; SEM default fixo — um valor arbitrário como
--                    "100" é rejeitado pelo schema da NFS-e, erro E1235.
--                    Só preencha com o código municipal VÁLIDO do município.)
-- Idempotente: só adiciona as colunas se ainda não existirem e limpa o "100"
-- gravado por versões anteriores.
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
            ALTER TABLE `configuracoes_nfe` ADD COLUMN `ctribmun_padrao` VARCHAR(10) NULL DEFAULT NULL;
        END IF;
        -- Limpa o "100" que versões anteriores gravavam como default (invalido no schema).
        UPDATE `configuracoes_nfe` SET `ctribmun_padrao` = '' WHERE `ctribmun_padrao` = '100';
    END IF;
END$$
DELIMITER ;
CALL `mapos_add_ctrib_cols`();
DROP PROCEDURE IF EXISTS `mapos_add_ctrib_cols`;
