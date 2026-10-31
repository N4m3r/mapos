<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * IVA-T15: estimativa de IBS/CBS contida em cada cobrança e valor líquido,
 * em reais, para preparação do split payment. Informativo.
 * Idempotente: só cria cada coluna se ainda não existir.
 */
class Migration_add_cobranca_split extends CI_Migration
{
    private $colunas = [
        'v_ibs_estimado' => 'DECIMAL(12,2) NULL DEFAULT NULL',
        'v_cbs_estimado' => 'DECIMAL(12,2) NULL DEFAULT NULL',
        'v_liquido_estimado' => 'DECIMAL(12,2) NULL DEFAULT NULL',
    ];

    public function up()
    {
        if (! $this->db->table_exists('cobrancas')) {
            return;
        }
        foreach ($this->colunas as $coluna => $definicao) {
            if (! $this->db->field_exists($coluna, 'cobrancas')) {
                $this->db->query("ALTER TABLE `cobrancas` ADD COLUMN `$coluna` $definicao");
            }
        }
    }

    public function down()
    {
        foreach (array_keys($this->colunas) as $coluna) {
            if ($this->db->field_exists($coluna, 'cobrancas')) {
                $this->dbforge->drop_column('cobrancas', $coluna);
            }
        }
    }
}
