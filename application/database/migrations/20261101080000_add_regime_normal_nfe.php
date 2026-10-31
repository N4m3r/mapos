<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * IVA-T17 - Regime normal na NF-e: ICMS e PIS/COFINS. Idempotente.
 */
class Migration_add_regime_normal_nfe extends CI_Migration
{
    private function colunas(): array
    {
        return [
            'configuracoes_nfe' => [
                'icms_cst_padrao' => "VARCHAR(2) NOT NULL DEFAULT '00'",
                'icms_aliquota_interna' => 'DECIMAL(5,2) NOT NULL DEFAULT 0',
                'icms_aliquota_interestadual' => 'DECIMAL(5,2) NOT NULL DEFAULT 12',
                'pis_cofins_regime' => "VARCHAR(16) NOT NULL DEFAULT 'cumulativo'",
                'pis_cofins_cst' => "VARCHAR(2) NOT NULL DEFAULT '01'",
            ],
            'produtos' => [
                'icms_cst' => 'VARCHAR(2) NULL',
                'icms_aliquota' => 'DECIMAL(5,2) NULL',
            ],
        ];
    }

    public function up()
    {
        foreach ($this->colunas() as $tabela => $campos) {
            if (! $this->db->table_exists($tabela)) {
                continue;
            }
            foreach ($campos as $campo => $def) {
                if (! $this->db->field_exists($campo, $tabela)) {
                    $this->db->query("ALTER TABLE `{$tabela}` ADD COLUMN `{$campo}` {$def}");
                }
            }
        }
    }

    public function down()
    {
        foreach ($this->colunas() as $tabela => $campos) {
            foreach (array_keys($campos) as $campo) {
                if ($this->db->table_exists($tabela) && $this->db->field_exists($campo, $tabela)) {
                    $this->dbforge->drop_column($tabela, $campo);
                }
            }
        }
    }
}
