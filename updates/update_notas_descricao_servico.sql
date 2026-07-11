-- ============================================================
-- notas_fiscais.descricao_servico — descrição do serviço da NF (xDescServ)
-- ============================================================
-- Guarda na nota a MESMA descrição enviada no campo xDescServ da NFS-e, para o
-- boleto (Cora) reutilizar exatamente a descrição da nota. Idempotente.
-- ============================================================

DROP PROCEDURE IF EXISTS `mapos_add_notas_descricao_servico`;
DELIMITER $$
CREATE PROCEDURE `mapos_add_notas_descricao_servico`()
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.TABLES
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notas_fiscais') THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notas_fiscais'
                         AND COLUMN_NAME = 'descricao_servico') THEN
            ALTER TABLE `notas_fiscais` ADD COLUMN `descricao_servico` TEXT NULL DEFAULT NULL AFTER `motivo`;
        END IF;
    END IF;
END$$
DELIMITER ;
CALL `mapos_add_notas_descricao_servico`();
DROP PROCEDURE IF EXISTS `mapos_add_notas_descricao_servico`;
