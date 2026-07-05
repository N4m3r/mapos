-- ============================================
-- ATUALIZAÇÃO DO BANCO PARA SISTEMA DE TÉCNICOS
-- ============================================

-- Adicionar coluna tecnico_responsavel na tabela OS
ALTER TABLE `os`
ADD COLUMN `tecnico_responsavel` INT(11) NULL DEFAULT NULL AFTER `usuarios_id`,
ADD INDEX `fk_os_tecnico` (`tecnico_responsavel` ASC),
ADD CONSTRAINT `fk_os_tecnico`
    FOREIGN KEY (`tecnico_responsavel`)
    REFERENCES `usuarios` (`idUsuarios`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;

-- Criar tabela de histórico de atribuições de técnicos
CREATE TABLE IF NOT EXISTS `os_tecnico_atribuicao` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `os_id` INT(11) NOT NULL,
    `tecnico_id` INT(11) NOT NULL,
    `atribuido_por` INT(11) NOT NULL,
    `data_atribuicao` DATETIME NOT NULL,
    `data_remocao` DATETIME NULL DEFAULT NULL,
    `observacao` TEXT NULL DEFAULT NULL,
    `motivo_remocao` TEXT NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_os_id` (`os_id` ASC),
    INDEX `idx_tecnico_id` (`tecnico_id` ASC),
    INDEX `idx_data_atribuicao` (`data_atribuicao` ASC),
    CONSTRAINT `fk_atribuicao_os`
        FOREIGN KEY (`os_id`)
        REFERENCES `os` (`idOs`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_atribuicao_tecnico`
        FOREIGN KEY (`tecnico_id`)
        REFERENCES `usuarios` (`idUsuarios`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_atribuicao_usuario`
        FOREIGN KEY (`atribuido_por`)
        REFERENCES `usuarios` (`idUsuarios`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;
