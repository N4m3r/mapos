<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Data de atribuição do técnico denormalizada na própria OS.
 *
 * As datas do ciclo de vida da OS já existem em suas fontes:
 *   - abertura ............... os.dataInicial
 *   - aprovação .............. os.aprovacao_data
 *   - aceite da resolução .... os.aceite_data
 *   - emissão da NF .......... notas_fiscais.data_autorizacao
 *   - emissão do boleto ...... cobrancas.created_at
 *
 * A ÚNICA que faltava num ponto conveniente para relatórios era a atribuição
 * do técnico — existia só no histórico `os_tecnico_atribuicao`. Aqui ela é
 * trazida para `os.data_atribuicao` (a 1ª atribuição), fechando o funil do
 * ciclo da OS numa única linha para os relatórios futuros.
 *
 * Idempotente: só cria a coluna/índice se faltarem e faz o backfill uma vez.
 */
class Migration_add_os_data_atribuicao extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('os')) {
            return;
        }

        // 1) Coluna denormalizada na OS.
        if (! $this->db->field_exists('data_atribuicao', 'os')) {
            $this->db->query("ALTER TABLE `os` ADD COLUMN `data_atribuicao` DATETIME NULL DEFAULT NULL COMMENT 'Data da 1a atribuicao de tecnico (funil do ciclo da OS)'");
        }

        // 2) Índice para filtros por período nos relatórios.
        $existeIndice = $this->db->query(
            'SELECT 1 FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            ['os', 'idx_os_data_atribuicao']
        )->num_rows();
        if (! $existeIndice) {
            $this->db->query('ALTER TABLE `os` ADD INDEX `idx_os_data_atribuicao` (`data_atribuicao`)');
        }

        // 3) Backfill: primeira atribuição registrada no histórico, para as OS
        //    que ainda não têm a data carimbada.
        if ($this->db->table_exists('os_tecnico_atribuicao')) {
            $this->db->query(
                'UPDATE `os` o
                    JOIN (
                        SELECT `os_id`, MIN(`data_atribuicao`) AS primeira
                          FROM `os_tecnico_atribuicao`
                         GROUP BY `os_id`
                    ) a ON a.`os_id` = o.`idOs`
                    SET o.`data_atribuicao` = a.`primeira`
                  WHERE o.`data_atribuicao` IS NULL'
            );
        }
    }

    public function down()
    {
        if ($this->db->field_exists('data_atribuicao', 'os')) {
            $existeIndice = $this->db->query(
                'SELECT 1 FROM information_schema.STATISTICS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
                ['os', 'idx_os_data_atribuicao']
            )->num_rows();
            if ($existeIndice) {
                $this->db->query('ALTER TABLE `os` DROP INDEX `idx_os_data_atribuicao`');
            }
            $this->dbforge->drop_column('os', 'data_atribuicao');
        }
    }
}
