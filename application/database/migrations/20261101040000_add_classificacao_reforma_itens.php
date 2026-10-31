<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * IVA-T5: classificacao da reforma tributaria por produto e por servico.
 * Vazio = usa o padrao da configuracao fiscal. Idempotente.
 */
class Migration_add_classificacao_reforma_itens extends CI_Migration
{
    private $colunas = [
        'produtos' => [
            'origem' => 'TINYINT NULL DEFAULT NULL',
            'ibscbs_cst' => 'VARCHAR(3) NULL DEFAULT NULL',
            'ibscbs_cclasstrib' => 'VARCHAR(6) NULL DEFAULT NULL',
            'p_red_ibscbs' => 'DECIMAL(6,4) NULL DEFAULT NULL',
        ],
        'servicos' => [
            'nbs' => 'VARCHAR(9) NULL DEFAULT NULL',
            'cindop' => 'VARCHAR(6) NULL DEFAULT NULL',
            'ibscbs_cst' => 'VARCHAR(3) NULL DEFAULT NULL',
            'ibscbs_cclasstrib' => 'VARCHAR(6) NULL DEFAULT NULL',
        ],
    ];

    public function up()
    {
        foreach ($this->colunas as $tabela => $cols) {
            if (! $this->db->table_exists($tabela)) {
                continue;
            }
            foreach ($cols as $nome => $def) {
                if (! $this->db->field_exists($nome, $tabela)) {
                    $this->db->query("ALTER TABLE `{$tabela}` ADD COLUMN `{$nome}` {$def}");
                }
            }
        }
    }

    public function down()
    {
        foreach ($this->colunas as $tabela => $cols) {
            foreach (array_keys($cols) as $nome) {
                if ($this->db->field_exists($nome, $tabela)) {
                    $this->dbforge->drop_column($tabela, $nome);
                }
            }
        }
    }
}
