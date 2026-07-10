-- Faturamento agendado (emissão em espera).
-- Aplique manualmente caso não rode as migrations do CodeIgniter.

-- Flag por cliente: segurar a emissão (NFS-e + boleto) até o dia de faturamento.
ALTER TABLE `clientes`
  ADD COLUMN `faturamento_agendado` TINYINT(1) NOT NULL DEFAULT 0 AFTER `automacao_aprovacao`;

-- Fila de emissões seguradas até o dia de faturamento.
CREATE TABLE IF NOT EXISTS `faturamentos_agendados` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `os_id` INT(11) NOT NULL,
  `cliente_id` INT(11) DEFAULT NULL,
  `data_aprovacao` DATETIME DEFAULT NULL,
  `data_agendada` DATE NOT NULL COMMENT 'Dia em que a emissão deve ser liberada',
  `status` VARCHAR(20) NOT NULL DEFAULT 'aguardando' COMMENT 'aguardando | processado | erro | cancelado',
  `tentativas` INT(11) NOT NULL DEFAULT 0,
  `nota_id` INT(11) DEFAULT NULL COMMENT 'FK para notas_fiscais.idNota apos a emissao',
  `motivo` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `processed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fa_os` (`os_id`),
  KEY `idx_fa_status_data` (`status`, `data_agendada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dia do mês em que a fila é liberada (1..28). Padrão: dia 01.
INSERT INTO `configuracoes` (`config`, `valor`)
SELECT 'automacao_faturamento_dia', '1'
WHERE NOT EXISTS (SELECT 1 FROM `configuracoes` WHERE `config` = 'automacao_faturamento_dia');
