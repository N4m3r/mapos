<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Indices nas colunas mais filtradas (ADR-002): status de OS/vendas, datas e
 * baixa de lancamentos (dashboard e financeiro), codigo de barras de produtos,
 * fila de e-mail e anotacoes da OS. Sem eles cada filtro varre a tabela toda.
 *
 * So cria indice se a tabela e a coluna existirem e o indice ainda nao existir.
 * Em bases grandes, rodar fora do horario de uso (ALTER TABLE trava escrita).
 *
 * Idempotente.
 */
class Migration_add_indices_performance extends CI_Migration
{
    private $indices = [
        ['os', 'idx_os_status', 'status'],
        ['vendas', 'idx_vendas_status', 'status'],
        ['lancamentos', 'idx_lanc_data_pagamento', 'data_pagamento'],
        ['lancamentos', 'idx_lanc_data_vencimento', 'data_vencimento'],
        ['lancamentos', 'idx_lanc_baixado', 'baixado'],
        ['produtos', 'idx_prod_cod_barra', 'codDeBarra'],
        ['email_queue', 'idx_email_queue_status', 'status'],
        ['anotacoes_os', 'idx_anotacoes_os_id', 'os_id'],
    ];

    public function up()
    {
        foreach ($this->indices as [$tabela, $indice, $coluna]) {
            $this->addIndice($tabela, $indice, $coluna);
        }

        log_message('info', 'Migration add_indices_performance executada com sucesso');
    }

    public function down()
    {
        foreach ($this->indices as [$tabela, $indice]) {
            if ($this->db->table_exists($tabela) && $this->indiceExiste($tabela, $indice)) {
                $this->db->query("ALTER TABLE `{$tabela}` DROP INDEX `{$indice}`");
            }
        }
    }

    /** Cria o indice so se a tabela e a coluna existirem e o indice faltar. */
    private function addIndice($tabela, $indice, $coluna)
    {
        if (! $this->db->table_exists($tabela) || ! $this->db->field_exists($coluna, $tabela)) {
            return;
        }
        if ($this->indiceExiste($tabela, $indice)) {
            return;
        }
        $this->db->query("ALTER TABLE `{$tabela}` ADD INDEX `{$indice}` (`{$coluna}`)");
    }

    private function indiceExiste($tabela, $indice)
    {
        $query = $this->db->query("SHOW INDEX FROM `{$tabela}` WHERE Key_name = ?", [$indice]);

        return $query !== false && $query->num_rows() > 0;
    }
}
