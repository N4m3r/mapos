<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tabelas oficiais da reforma tributaria (CST IBS/CBS, cClassTrib, cCredPres,
 * Anexo VIII da NFS-e), versionadas e atualizaveis por importacao de CSV.
 * Idempotente: so cria o que ainda nao existe.
 */
class Migration_add_tabelas_reforma extends CI_Migration
{
    public function up()
    {
        $q = [
            'trib_tabelas_versao' => 'CREATE TABLE `trib_tabelas_versao` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `tabela` VARCHAR(30) NOT NULL,
                `versao` VARCHAR(40) NULL,
                `fonte_url` VARCHAR(255) NULL,
                `vigencia_inicio` DATE NULL,
                `importado_em` DATETIME NOT NULL,
                `usuarios_id` INT NULL,
                PRIMARY KEY (`id`), KEY `idx_tabela` (`tabela`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
            'trib_cst' => 'CREATE TABLE `trib_cst` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `cst` CHAR(3) NOT NULL,
                `descricao` VARCHAR(500) NULL,
                `vigencia` DATE NULL,
                PRIMARY KEY (`id`), KEY `idx_cst` (`cst`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
            'trib_cclasstrib' => 'CREATE TABLE `trib_cclasstrib` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `codigo` CHAR(6) NOT NULL,
                `cst` CHAR(3) NOT NULL,
                `descricao` TEXT NULL,
                `p_red_ibs` DECIMAL(7,4) NULL,
                `p_red_cbs` DECIMAL(7,4) NULL,
                `ind_nfe` TINYINT(1) NOT NULL DEFAULT 0,
                `ind_nfce` TINYINT(1) NOT NULL DEFAULT 0,
                `ind_nfse` TINYINT(1) NOT NULL DEFAULT 0,
                `vigencia_inicio` DATE NULL,
                `vigencia_fim` DATE NULL,
                PRIMARY KEY (`id`), KEY `idx_codigo` (`codigo`), KEY `idx_cst` (`cst`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
            'trib_credpres' => 'CREATE TABLE `trib_credpres` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `codigo` VARCHAR(10) NOT NULL,
                `descricao` VARCHAR(500) NULL,
                PRIMARY KEY (`id`), KEY `idx_codigo` (`codigo`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
            'trib_nfse_correlacao' => 'CREATE TABLE `trib_nfse_correlacao` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `item_lc116` VARCHAR(10) NOT NULL,
                `nbs` VARCHAR(12) NULL,
                `cindop` VARCHAR(10) NULL,
                `cclasstrib` CHAR(6) NULL,
                `descricao` VARCHAR(500) NULL,
                PRIMARY KEY (`id`), KEY `idx_item` (`item_lc116`), KEY `idx_nbs` (`nbs`), KEY `idx_cclass` (`cclasstrib`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
        ];
        foreach ($q as $tabela => $sql) {
            if (! $this->db->table_exists($tabela)) {
                $this->db->query($sql);
            }
        }
    }

    public function down()
    {
        foreach (['trib_nfse_correlacao', 'trib_credpres', 'trib_cclasstrib', 'trib_cst', 'trib_tabelas_versao'] as $t) {
            $this->dbforge->drop_table($t, true);
        }
    }
}
