-- ============================================================
-- Mapos - Central de notificações / gatilhos
-- Equivalente à migration 20260709000001. Seguro para rodar mais de uma vez.
-- ============================================================
SET NAMES utf8mb4;

-- 1) Tabela de gatilhos
CREATE TABLE IF NOT EXISTS `notification_triggers` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `evento` VARCHAR(60) NOT NULL,
    `nome` VARCHAR(120) NOT NULL,
    `descricao` VARCHAR(255) NULL DEFAULT NULL,
    `grupo` VARCHAR(40) NULL DEFAULT NULL,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `canais` VARCHAR(120) NULL DEFAULT NULL,
    `destinatarios` VARCHAR(160) NULL DEFAULT NULL,
    `blocos` TEXT NULL,
    `anexos` VARCHAR(120) NULL DEFAULT NULL,
    `template_slug` VARCHAR(60) NULL DEFAULT NULL,
    `data_criacao` DATETIME NULL DEFAULT NULL,
    `data_atualizacao` DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `evento` (`evento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) Intervalo do disparo automático (segundos)
INSERT INTO `configuracoes` (`config`, `valor`)
SELECT 'notif_intervalo_disparo', '120'
WHERE NOT EXISTS (SELECT 1 FROM `configuracoes` WHERE `config` = 'notif_intervalo_disparo');

-- 3) Gatilhos padrão
INSERT INTO `notification_triggers` (`evento`,`nome`,`descricao`,`grupo`,`ativo`,`canais`,`destinatarios`,`blocos`,`anexos`,`template_slug`,`data_criacao`,`data_atualizacao`)
SELECT 'os_aberta','OS aberta','Ao criar uma nova Ordem de Serviço.','Ordem de Serviço',1,'email','cliente','dados,defeito,laudo,observacoes,produtos,servicos,valores',NULL,'os',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `notification_triggers` WHERE `evento`='os_aberta');

INSERT INTO `notification_triggers` (`evento`,`nome`,`descricao`,`grupo`,`ativo`,`canais`,`destinatarios`,`blocos`,`anexos`,`template_slug`,`data_criacao`,`data_atualizacao`)
SELECT 'os_editada','OS editada / status alterado','Ao editar a OS ou mudar o status.','Ordem de Serviço',1,'email','cliente','dados,defeito,laudo,observacoes,produtos,servicos,valores',NULL,'os',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `notification_triggers` WHERE `evento`='os_editada');

INSERT INTO `notification_triggers` (`evento`,`nome`,`descricao`,`grupo`,`ativo`,`canais`,`destinatarios`,`blocos`,`anexos`,`template_slug`,`data_criacao`,`data_atualizacao`)
SELECT 'os_aprovada','OS aprovada','Quando o cliente aprova o orçamento/OS.','Ordem de Serviço',1,'email,whatsapp','cliente,tecnico','dados,valores',NULL,'os',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `notification_triggers` WHERE `evento`='os_aprovada');

INSERT INTO `notification_triggers` (`evento`,`nome`,`descricao`,`grupo`,`ativo`,`canais`,`destinatarios`,`blocos`,`anexos`,`template_slug`,`data_criacao`,`data_atualizacao`)
SELECT 'os_finalizada','OS finalizada','Quando a OS é concluída.','Ordem de Serviço',1,'email','cliente','dados,defeito,laudo,observacoes,produtos,servicos,valores',NULL,'os',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `notification_triggers` WHERE `evento`='os_finalizada');

INSERT INTO `notification_triggers` (`evento`,`nome`,`descricao`,`grupo`,`ativo`,`canais`,`destinatarios`,`blocos`,`anexos`,`template_slug`,`data_criacao`,`data_atualizacao`)
SELECT 'cobranca_gerada','Boleto / cobrança gerada','Ao gerar o boleto/PIX da nota ou cobrança.','Cobrança',1,'email','cliente,cliente_secundario',NULL,'boleto','cobranca',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `notification_triggers` WHERE `evento`='cobranca_gerada');

INSERT INTO `notification_triggers` (`evento`,`nome`,`descricao`,`grupo`,`ativo`,`canais`,`destinatarios`,`blocos`,`anexos`,`template_slug`,`data_criacao`,`data_atualizacao`)
SELECT 'cobranca_enviada','Cobrança enviada (manual)','Ao clicar em enviar a cobrança por e-mail.','Cobrança',1,'email','cliente,cliente_secundario',NULL,'boleto','cobranca',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `notification_triggers` WHERE `evento`='cobranca_enviada');

INSERT INTO `notification_triggers` (`evento`,`nome`,`descricao`,`grupo`,`ativo`,`canais`,`destinatarios`,`blocos`,`anexos`,`template_slug`,`data_criacao`,`data_atualizacao`)
SELECT 'pagamento_confirmado','Pagamento confirmado','Quando o pagamento do boleto/PIX é identificado.','Cobrança',0,'email,whatsapp','cliente',NULL,NULL,NULL,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `notification_triggers` WHERE `evento`='pagamento_confirmado');

INSERT INTO `notification_triggers` (`evento`,`nome`,`descricao`,`grupo`,`ativo`,`canais`,`destinatarios`,`blocos`,`anexos`,`template_slug`,`data_criacao`,`data_atualizacao`)
SELECT 'nota_emitida','Nota fiscal emitida','Ao autorizar uma NF-e / NFS-e.','Fiscal',0,'email','cliente,cliente_secundario',NULL,'nota_fiscal',NULL,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `notification_triggers` WHERE `evento`='nota_emitida');

INSERT INTO `notification_triggers` (`evento`,`nome`,`descricao`,`grupo`,`ativo`,`canais`,`destinatarios`,`blocos`,`anexos`,`template_slug`,`data_criacao`,`data_atualizacao`)
SELECT 'cliente_novo','Cliente cadastrado (boas-vindas)','Ao cadastrar um novo cliente.','Cliente',0,'email','cliente',NULL,NULL,NULL,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `notification_triggers` WHERE `evento`='cliente_novo');

-- 4) Coluna de anexos na fila de e-mail (adiciona só se não existir)
SET @col_existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'email_queue'
      AND COLUMN_NAME = 'attachments'
);
SET @ddl := IF(
    @col_existe = 0,
    'ALTER TABLE `email_queue` ADD COLUMN `attachments` TEXT NULL AFTER `headers`',
    'DO 0'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
