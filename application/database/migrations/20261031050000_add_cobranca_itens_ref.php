<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cobrança por item: guarda em `cobrancas.itens_ref` quais itens do documento
 * a cobrança cobriu ("p:<idProdutos_os|idItens>,s:<idServicos_os>").
 * NULL = documento inteiro (cobranças antigas e "tudo junto").
 *
 * Idempotente: só cria a coluna se ainda não existir.
 */
class Migration_add_cobranca_itens_ref extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('cobrancas') && ! $this->db->field_exists('itens_ref', 'cobrancas')) {
            $this->db->query('ALTER TABLE `cobrancas` ADD COLUMN `itens_ref` VARCHAR(1000) NULL DEFAULT NULL');
        }
    }

    public function down()
    {
        if ($this->db->field_exists('itens_ref', 'cobrancas')) {
            $this->dbforge->drop_column('cobrancas', 'itens_ref');
        }
    }
}
