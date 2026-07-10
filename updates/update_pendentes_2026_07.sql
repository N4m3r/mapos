-- =====================================================================
-- ATUALIZAÇÃO COMBINADA (rodar UMA vez) — features recentes do Mapos
-- =====================================================================
-- Junta, de forma IDEMPOTENTE (seguro re-rodar), as três atualizações
-- pendentes:
--   1) Portal do cliente multi-CNPJ ...... tabela `clientes_vinculos`
--   2) Aceite do serviço realizado ....... colunas `aceite_*` em `os`
--   3) Nº de notificação por cliente ..... coluna `whatsapp_notificacao` em `clientes`
--
-- Pode ser importado pelo phpMyAdmin (aba Importar) ou via:
--   mysql -h HOST -u USUARIO -p BANCO < updates/update_pendentes_2026_07.sql
--
-- Usa procedures auxiliares (criadas e removidas ao final) para só aplicar
-- o que ainda não existe — não dá erro "Duplicate column" ao re-rodar.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) Portal multi-CNPJ: tabela de vínculos
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clientes_vinculos` (
    `id`                INT(11)   NOT NULL AUTO_INCREMENT,
    `cliente_master_id` INT(11)   NOT NULL,
    `cliente_id`        INT(11)   NOT NULL,
    `data_cadastro`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_master_cliente` (`cliente_master_id`, `cliente_id`),
    KEY `idx_master` (`cliente_master_id`),
    KEY `idx_cliente` (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Procedures auxiliares (adicionar coluna/índice só se não existir)
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `mapos_add_column`;
DROP PROCEDURE IF EXISTS `mapos_add_index`;

DELIMITER $$

CREATE PROCEDURE `mapos_add_column`(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND COLUMN_NAME = col
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN ', ddl);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END$$

CREATE PROCEDURE `mapos_add_index`(IN tbl VARCHAR(64), IN idx VARCHAR(64), IN cols VARCHAR(255))
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND INDEX_NAME = idx
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD INDEX `', idx, '` (', cols, ')');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- ---------------------------------------------------------------------
-- 2) Aceite do serviço realizado (colunas em `os`)
-- ---------------------------------------------------------------------
CALL `mapos_add_column`('os', 'aceite_token',         '`aceite_token` VARCHAR(64) NULL DEFAULT NULL');
CALL `mapos_add_column`('os', 'aceite_status',        '`aceite_status` VARCHAR(20) NULL DEFAULT NULL');
CALL `mapos_add_column`('os', 'aceite_expira',        '`aceite_expira` DATETIME NULL DEFAULT NULL');
CALL `mapos_add_column`('os', 'aceite_data',          '`aceite_data` DATETIME NULL DEFAULT NULL');
CALL `mapos_add_column`('os', 'aceite_nome',          '`aceite_nome` VARCHAR(150) NULL DEFAULT NULL');
CALL `mapos_add_column`('os', 'aceite_ip',            '`aceite_ip` VARCHAR(45) NULL DEFAULT NULL');
CALL `mapos_add_column`('os', 'aceite_obs',           '`aceite_obs` TEXT NULL DEFAULT NULL');
CALL `mapos_add_column`('os', 'aceite_assinatura_id', '`aceite_assinatura_id` INT(11) NULL DEFAULT NULL');
CALL `mapos_add_index`('os', 'idx_os_aceite_token', '`aceite_token`');

-- ---------------------------------------------------------------------
-- 3) Nº de notificação (WhatsApp) por cliente
-- ---------------------------------------------------------------------
CALL `mapos_add_column`('clientes', 'whatsapp_notificacao', '`whatsapp_notificacao` VARCHAR(20) NULL DEFAULT NULL');

-- ---------------------------------------------------------------------
-- Limpeza das procedures auxiliares
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `mapos_add_column`;
DROP PROCEDURE IF EXISTS `mapos_add_index`;

-- Fim. Pode apagar este arquivo do servidor após rodar.
