<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Ajusta a tabela `cobrancas` para suportar boletos híbridos (boleto + PIX) do Cora
 * emitidos a partir de uma nota fiscal:
 *  - charge_id passa a VARCHAR (o id de fatura do Cora/Asaas é uma string, ex: inv_xxx);
 *  - nota_id liga a cobrança à nota fiscal de origem (notas_fiscais);
 *  - pix guarda o "copia e cola" (EMV) do QR Code;
 *  - valor_iss_retido registra o ISS abatido no valor líquido do boleto.
 */
class Migration_add_cora_boleto_cobrancas extends CI_Migration
{
    public function up()
    {
        // charge_id: INT -> VARCHAR(64). Gateways (Cora, Asaas) usam id textual.
        $this->db->query('ALTER TABLE `cobrancas` MODIFY `charge_id` VARCHAR(64) NOT NULL');

        if (! $this->db->field_exists('nota_id', 'cobrancas')) {
            $this->dbforge->add_column('cobrancas', [
                'nota_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'FK para notas_fiscais.idNota (boleto emitido a partir da nota)',
                ],
            ]);
            $this->db->query('ALTER TABLE `cobrancas` ADD INDEX `idx_cobrancas_nota_id` (`nota_id`)');
        }

        if (! $this->db->field_exists('pix', 'cobrancas')) {
            $this->dbforge->add_column('cobrancas', [
                'pix' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'comment' => 'PIX copia e cola (EMV) do boleto híbrido',
                ],
            ]);
        }

        if (! $this->db->field_exists('valor_iss_retido', 'cobrancas')) {
            $this->dbforge->add_column('cobrancas', [
                'valor_iss_retido' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                    'default' => 0,
                    'comment' => 'ISS retido pelo tomador abatido do valor do boleto',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->field_exists('valor_iss_retido', 'cobrancas')) {
            $this->dbforge->drop_column('cobrancas', 'valor_iss_retido');
        }
        if ($this->db->field_exists('pix', 'cobrancas')) {
            $this->dbforge->drop_column('cobrancas', 'pix');
        }
        if ($this->db->field_exists('nota_id', 'cobrancas')) {
            $this->db->query('ALTER TABLE `cobrancas` DROP INDEX `idx_cobrancas_nota_id`');
            $this->dbforge->drop_column('cobrancas', 'nota_id');
        }
        // charge_id permanece VARCHAR (compatível com todos os gateways).
    }
}
