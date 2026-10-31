<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Reforma Tributária na NFS-e (DPS, NT 004-009): na DPS o emitente só declara
 * a classificação; IBS/CBS são calculados pelo ambiente nacional.
 *
 *  - reforma_cindop   : código indicador da operação (6 díg., Anexo VIII:
 *                       LC 116 x NBS x cIndOp). Vazio = grupo IBSCBS não é enviado.
 *  - reforma_indfinal : 0 = operação não destinada a consumidor final; 1 = consumidor final.
 *
 * Idempotente: só cria coluna que ainda não existe.
 */
class Migration_add_reforma_nfse_campos extends CI_Migration
{
    public function up()
    {
        $colunas = [
            'reforma_cindop' => "`reforma_cindop` VARCHAR(6) NULL DEFAULT NULL",
            'reforma_indfinal' => "`reforma_indfinal` TINYINT(1) NOT NULL DEFAULT 0",
        ];
        foreach ($colunas as $coluna => $definicao) {
            if ($this->db->table_exists('configuracoes_nfe') && ! $this->db->field_exists($coluna, 'configuracoes_nfe')) {
                $this->db->query("ALTER TABLE `configuracoes_nfe` ADD COLUMN {$definicao}");
            }
        }
    }

    public function down()
    {
        foreach (['reforma_cindop', 'reforma_indfinal'] as $c) {
            if ($this->db->field_exists($c, 'configuracoes_nfe')) {
                $this->dbforge->drop_column('configuracoes_nfe', $c);
            }
        }
    }
}
