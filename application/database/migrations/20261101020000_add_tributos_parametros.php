<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Parametros anuais da reforma tributaria (CBS/IBS), editaveis na tela fiscal.
 * - ano: UNIQUE. cbs/ibs_uf/ibs_mun em percentual (ex.: 0.9000 = 0,9%).
 * - confirmado: 1 = valor confirmado em lei/resolucao; 0 = a confirmar com o contador.
 * Idempotente: cria a tabela so se nao existir e semeia somente se estiver vazia.
 */
class Migration_add_tributos_parametros extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('tributos_parametros')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `tributos_parametros` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `ano` SMALLINT NOT NULL,
                `cbs` DECIMAL(6,4) NULL DEFAULT NULL,
                `ibs_uf` DECIMAL(6,4) NULL DEFAULT NULL,
                `ibs_mun` DECIMAL(6,4) NULL DEFAULT NULL,
                `fonte` VARCHAR(255) NULL DEFAULT NULL,
                `confirmado` TINYINT(1) NOT NULL DEFAULT 0,
                `atualizado_em` DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_tributos_parametros_ano` (`ano`)
            )");
        }

        $total = (int) $this->db->count_all('tributos_parametros');
        if ($total === 0) {
            $agora = date('Y-m-d H:i:s');
            $this->db->insert_batch('tributos_parametros', [
                [
                    'ano' => 2026,
                    'cbs' => 0.9,
                    'ibs_uf' => 0.1,
                    'ibs_mun' => 0,
                    'confirmado' => 1,
                    'fonte' => 'LC 214/2025 - ano de teste',
                    'atualizado_em' => $agora,
                ],
                [
                    'ano' => 2027,
                    'cbs' => 0.9000,
                    'ibs_uf' => 0.0500,
                    'ibs_mun' => 0.0500,
                    'confirmado' => 0,
                    'fonte' => 'IBS 0,1% (0,05% UF + 0,05% mun.); CBS de 2027 depende de resolucao do Senado - CONFIRMAR',
                    'atualizado_em' => $agora,
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->table_exists('tributos_parametros')) {
            $this->dbforge->drop_table('tributos_parametros', true);
        }
    }
}
