-- =====================================================
-- SQL para criar tabelas do Sistema de Checkin/Atendimento
-- Execute este script no seu banco de dados MySQL
-- =====================================================

-- Tabela de Checkin/Atendimento
CREATE TABLE IF NOT EXISTS `os_checkin` (
    `idCheckin` INT(11) NOT NULL AUTO_INCREMENT,
    `os_id` INT(11) NOT NULL,
    `usuarios_id` INT(11) NOT NULL,
    `data_entrada` DATETIME NOT NULL,
    `data_saida` DATETIME NULL DEFAULT NULL,
    `latitude_entrada` VARCHAR(50) NULL DEFAULT NULL,
    `longitude_entrada` VARCHAR(50) NULL DEFAULT NULL,
    `latitude_saida` VARCHAR(50) NULL DEFAULT NULL,
    `longitude_saida` VARCHAR(50) NULL DEFAULT NULL,
    `observacao_entrada` TEXT NULL DEFAULT NULL,
    `observacao_saida` TEXT NULL DEFAULT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Em Andamento',
    `data_atualizacao` DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`idCheckin`),
    KEY `os_id` (`os_id`),
    KEY `usuarios_id` (`usuarios_id`),
    KEY `data_entrada` (`data_entrada`),
    CONSTRAINT `fk_checkin_os` FOREIGN KEY (`os_id`) REFERENCES `os` (`idOs`) ON DELETE CASCADE,
    CONSTRAINT `fk_checkin_usuario` FOREIGN KEY (`usuarios_id`) REFERENCES `usuarios` (`idUsuarios`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de Assinaturas
CREATE TABLE IF NOT EXISTS `os_assinaturas` (
    `idAssinatura` INT(11) NOT NULL AUTO_INCREMENT,
    `os_id` INT(11) NOT NULL,
    `checkin_id` INT(11) NULL DEFAULT NULL,
    `tipo` VARCHAR(50) NOT NULL COMMENT 'tecnico_entrada, tecnico_saida, cliente_saida',
    `assinatura` VARCHAR(255) NOT NULL COMMENT 'nome do arquivo',
    `nome_assinante` VARCHAR(255) NULL DEFAULT NULL,
    `documento_assinante` VARCHAR(50) NULL DEFAULT NULL,
    `data_assinatura` DATETIME NOT NULL,
    `ip_address` VARCHAR(45) NULL DEFAULT NULL,
    PRIMARY KEY (`idAssinatura`),
    KEY `os_id` (`os_id`),
    KEY `checkin_id` (`checkin_id`),
    KEY `tipo` (`tipo`),
    CONSTRAINT `fk_assinatura_os` FOREIGN KEY (`os_id`) REFERENCES `os` (`idOs`) ON DELETE CASCADE,
    CONSTRAINT `fk_assinatura_checkin` FOREIGN KEY (`checkin_id`) REFERENCES `os_checkin` (`idCheckin`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Tabela de Fotos do Atendimento
CREATE TABLE IF NOT EXISTS `os_fotos_atendimento` (
    `idFoto` INT(11) NOT NULL AUTO_INCREMENT,
    `os_id` INT(11) NOT NULL,
    `checkin_id` INT(11) NULL DEFAULT NULL,
    `usuarios_id` INT(11) NOT NULL,
    `arquivo` VARCHAR(255) NOT NULL COMMENT 'nome do arquivo',
    `path` VARCHAR(500) NOT NULL COMMENT 'caminho completo do arquivo',
    `url` VARCHAR(500) NOT NULL COMMENT 'URL para acesso',
    `descricao` TEXT NULL DEFAULT NULL,
    `etapa` VARCHAR(50) NOT NULL DEFAULT 'durante' COMMENT 'entrada, durante, saida',
    `tamanho` INT(11) NULL DEFAULT NULL,
    `tipo_arquivo` VARCHAR(10) NULL DEFAULT NULL COMMENT 'jpg, png, gif',
    `data_upload` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`idFoto`),
    KEY `os_id` (`os_id`),
    KEY `checkin_id` (`checkin_id`),
    KEY `usuarios_id` (`usuarios_id`),
    KEY `etapa` (`etapa`),
    CONSTRAINT `fk_foto_os` FOREIGN KEY (`os_id`) REFERENCES `os` (`idOs`) ON DELETE CASCADE,
    CONSTRAINT `fk_foto_checkin` FOREIGN KEY (`checkin_id`) REFERENCES `os_checkin` (`idCheckin`) ON DELETE CASCADE,
    CONSTRAINT `fk_foto_usuario` FOREIGN KEY (`usuarios_id`) REFERENCES `usuarios` (`idUsuarios`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
