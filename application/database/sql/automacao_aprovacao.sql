-- ============================================================
-- Mapos - Automação na aprovação (NFS-e + boleto)
-- Equivalente à migration 20260709000003. Seguro para rodar mais de uma vez.
-- ============================================================
SET NAMES utf8mb4;

-- 1) Flag por cliente (adiciona só se não existir)
SET @col_existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'clientes'
      AND COLUMN_NAME = 'automacao_aprovacao'
);
SET @ddl := IF(
    @col_existe = 0,
    'ALTER TABLE `clientes` ADD COLUMN `automacao_aprovacao` TINYINT(1) NOT NULL DEFAULT 0 AFTER `email_secundario`',
    'DO 0'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) Configurações globais da automação (desligada por padrão)
INSERT INTO `configuracoes` (`config`, `valor`)
SELECT 'automacao_aprovacao_ativa', '0'
WHERE NOT EXISTS (SELECT 1 FROM `configuracoes` WHERE `config` = 'automacao_aprovacao_ativa');

INSERT INTO `configuracoes` (`config`, `valor`)
SELECT 'automacao_desc_servico', 'Serviços referentes à OS nº {os_numero}'
WHERE NOT EXISTS (SELECT 1 FROM `configuracoes` WHERE `config` = 'automacao_desc_servico');

INSERT INTO `configuracoes` (`config`, `valor`)
SELECT 'automacao_info_complementar', ''
WHERE NOT EXISTS (SELECT 1 FROM `configuracoes` WHERE `config` = 'automacao_info_complementar');

INSERT INTO `configuracoes` (`config`, `valor`)
SELECT 'automacao_ctribnac', ''
WHERE NOT EXISTS (SELECT 1 FROM `configuracoes` WHERE `config` = 'automacao_ctribnac');

INSERT INTO `configuracoes` (`config`, `valor`)
SELECT 'automacao_ctribmun', ''
WHERE NOT EXISTS (SELECT 1 FROM `configuracoes` WHERE `config` = 'automacao_ctribmun');

INSERT INTO `configuracoes` (`config`, `valor`)
SELECT 'automacao_aliquota_iss', ''
WHERE NOT EXISTS (SELECT 1 FROM `configuracoes` WHERE `config` = 'automacao_aliquota_iss');

INSERT INTO `configuracoes` (`config`, `valor`)
SELECT 'automacao_tp_ret_issqn', ''
WHERE NOT EXISTS (SELECT 1 FROM `configuracoes` WHERE `config` = 'automacao_tp_ret_issqn');

-- 3) Override por OS (null=herda do cliente | 1=ativa | 0=desativa nesta OS)
SET @col_os := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'os'
      AND COLUMN_NAME = 'automacao_override'
);
SET @ddl_os := IF(
    @col_os = 0,
    'ALTER TABLE `os` ADD COLUMN `automacao_override` TINYINT(1) NULL DEFAULT NULL',
    'DO 0'
);
PREPARE stmt2 FROM @ddl_os;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- 4) Permissão "cAutomacao": conceda pela tela Configurações > Permissões
--    (marque "Automação" nos grupos desejados). Para liberar automaticamente a
--    todos os grupos que já são admin (têm "Sistema"), rode o bloco abaixo:
-- UPDATE `permissoes`
--   SET `permissoes` = ...  -- (dados serializados; prefira a tela de Permissões)

