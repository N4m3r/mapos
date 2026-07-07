-- ============================================================
-- PORTAL DO CLIENTE MULTI-CNPJ (login com vários clientes vinculados)
-- ============================================================
-- Permite que um cliente (login principal da área do cliente) enxergue
-- os documentos (OS, cobranças, notas fiscais, aprovações) de vários
-- outros clientes/CNPJs vinculados.
--
-- cliente_master_id: o cliente/login que enxerga (FK lógica -> clientes.idClientes)
-- cliente_id:        um CNPJ acessível por esse login (FK lógica -> clientes.idClientes)
-- O conjunto acessível de um login = [cliente_master_id] + todos os cliente_id vinculados.
-- ============================================================

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
