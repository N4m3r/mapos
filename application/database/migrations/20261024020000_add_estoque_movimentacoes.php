<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Historico de movimentacao de estoque e inventario (ADR-005, F1).
 *
 * Idempotente.
 */
class Migration_add_estoque_movimentacoes extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('estoque_movimentacoes')) {
            $this->db->query("CREATE TABLE `estoque_movimentacoes` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `produtos_id` INT(11) NOT NULL,
                `tipo` VARCHAR(20) NOT NULL COMMENT 'os, venda, tecnico, api, ajuste, cadastro, inventario',
                `quantidade` DECIMAL(12,3) NOT NULL COMMENT 'positiva = entrada, negativa = saida',
                `estoque_anterior` DECIMAL(12,3) NULL,
                `estoque_novo` DECIMAL(12,3) NULL,
                `referencia_id` INT(11) NULL COMMENT 'id da OS, venda ou inventario',
                `observacao` VARCHAR(255) NULL,
                `usuarios_id` INT(11) NULL,
                `criado_em` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_produto_data` (`produtos_id`, `criado_em`),
                KEY `idx_tipo_ref` (`tipo`, `referencia_id`),
                KEY `idx_data` (`criado_em`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (! $this->db->table_exists('inventarios')) {
            $this->db->query("CREATE TABLE `inventarios` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `descricao` VARCHAR(120) NOT NULL,
                `status` VARCHAR(12) NOT NULL DEFAULT 'aberto' COMMENT 'aberto, finalizado, cancelado',
                `observacao` TEXT NULL,
                `usuarios_id` INT(11) NULL,
                `criado_em` DATETIME NOT NULL,
                `finalizado_em` DATETIME NULL,
                PRIMARY KEY (`id`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (! $this->db->table_exists('inventario_itens')) {
            $this->db->query("CREATE TABLE `inventario_itens` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `inventarios_id` INT(11) NOT NULL,
                `produtos_id` INT(11) NOT NULL,
                `estoque_sistema` DECIMAL(12,3) NOT NULL COMMENT 'estoque no momento da abertura',
                `contado` DECIMAL(12,3) NULL COMMENT 'null = nao contado (nao altera o estoque)',
                `ajuste` DECIMAL(12,3) NULL COMMENT 'diferenca aplicada ao finalizar',
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_inventario_produto` (`inventarios_id`, `produtos_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function down()
    {
        foreach (['inventario_itens', 'inventarios', 'estoque_movimentacoes'] as $tabela) {
            if ($this->db->table_exists($tabela)) {
                $this->dbforge->drop_table($tabela);
            }
        }
    }
}
