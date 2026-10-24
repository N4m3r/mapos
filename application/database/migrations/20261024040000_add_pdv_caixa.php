<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PDV, controle de caixa e NFC-e (ADR-005, F5).
 *
 * Idempotente.
 */
class Migration_add_pdv_caixa extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('caixas')) {
            $this->db->query("CREATE TABLE `caixas` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `usuarios_id` INT(11) NOT NULL COMMENT 'operador',
                `status` VARCHAR(10) NOT NULL DEFAULT 'aberto' COMMENT 'aberto, fechado',
                `valor_abertura` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `aberto_em` DATETIME NOT NULL,
                `fechado_em` DATETIME NULL,
                `contado_dinheiro` DECIMAL(12,2) NULL COMMENT 'dinheiro contado no fechamento',
                `esperado_dinheiro` DECIMAL(12,2) NULL,
                `resumo_fechamento` TEXT NULL COMMENT 'JSON: totais por forma de pagamento',
                `observacao` TEXT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_usuario_status` (`usuarios_id`, `status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (! $this->db->table_exists('caixa_movimentos')) {
            $this->db->query("CREATE TABLE `caixa_movimentos` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `caixas_id` INT(11) NOT NULL,
                `tipo` VARCHAR(12) NOT NULL COMMENT 'venda, sangria, suprimento, troco',
                `forma` VARCHAR(12) NOT NULL DEFAULT 'dinheiro' COMMENT 'dinheiro, credito, debito, pix, outros',
                `valor` DECIMAL(12,2) NOT NULL COMMENT 'positivo entra, negativo sai',
                `vendas_id` INT(11) NULL,
                `descricao` VARCHAR(255) NULL,
                `usuarios_id` INT(11) NULL,
                `criado_em` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_caixa` (`caixas_id`),
                KEY `idx_venda` (`vendas_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if ($this->db->table_exists('vendas')) {
            if (! $this->db->field_exists('origem', 'vendas')) {
                $this->db->query("ALTER TABLE `vendas` ADD `origem` VARCHAR(10) NULL DEFAULT NULL COMMENT 'pdv ou NULL (venda comum)'");
            }
            if (! $this->db->field_exists('caixas_id', 'vendas')) {
                $this->db->query('ALTER TABLE `vendas` ADD `caixas_id` INT(11) NULL DEFAULT NULL, ADD KEY `idx_caixa` (`caixas_id`)');
            }
        }

        if ($this->db->table_exists('configuracoes_nfe')) {
            $colunas = [
                'serie_nfce' => "INT(11) NOT NULL DEFAULT 1",
                'proximo_numero_nfce' => "INT(11) NOT NULL DEFAULT 1",
                'csc' => "VARCHAR(64) NULL COMMENT 'Codigo de Seguranca do Contribuinte (NFC-e)'",
                'csc_id' => "VARCHAR(10) NULL COMMENT 'Identificador do CSC (ex.: 000001)'",
            ];
            foreach ($colunas as $coluna => $definicao) {
                if (! $this->db->field_exists($coluna, 'configuracoes_nfe')) {
                    $this->db->query("ALTER TABLE `configuracoes_nfe` ADD `{$coluna}` {$definicao}");
                }
            }
        }
    }

    public function down()
    {
        foreach (['caixa_movimentos', 'caixas'] as $tabela) {
            if ($this->db->table_exists($tabela)) {
                $this->dbforge->drop_table($tabela);
            }
        }
    }
}
