-- ============================================================
-- Mapos - Colunas do cliente para e-mail secundário e automação
-- Adiciona (só se não existir): email_secundario, automacao_aprovacao, tp_ret_issqn.
-- Sem esses campos, os checkboxes/valores da ficha do cliente não têm onde salvar.
-- Seguro para rodar mais de uma vez (MySQL e MariaDB).
-- ============================================================
SET NAMES utf8mb4;

-- email_secundario (cobrança/financeiro)
SET @c1 := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clientes' AND COLUMN_NAME = 'email_secundario');
SET @s1 := IF(@c1 = 0,
    'ALTER TABLE `clientes` ADD COLUMN `email_secundario` VARCHAR(255) NULL DEFAULT NULL AFTER `email`',
    'DO 0');
PREPARE q1 FROM @s1; EXECUTE q1; DEALLOCATE PREPARE q1;

-- automacao_aprovacao (checkbox "Automação na aprovação")
SET @c2 := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clientes' AND COLUMN_NAME = 'automacao_aprovacao');
SET @s2 := IF(@c2 = 0,
    'ALTER TABLE `clientes` ADD COLUMN `automacao_aprovacao` TINYINT(1) NOT NULL DEFAULT 0 AFTER `email_secundario`',
    'DO 0');
PREPARE q2 FROM @s2; EXECUTE q2; DEALLOCATE PREPARE q2;

-- tp_ret_issqn (retenção de ISS da NFS-e: null=padrão | 1=não retido | 2=retido)
SET @c3 := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clientes' AND COLUMN_NAME = 'tp_ret_issqn');
SET @s3 := IF(@c3 = 0,
    'ALTER TABLE `clientes` ADD COLUMN `tp_ret_issqn` TINYINT(1) NULL DEFAULT NULL AFTER `automacao_aprovacao`',
    'DO 0');
PREPARE q3 FROM @s3; EXECUTE q3; DEALLOCATE PREPARE q3;
