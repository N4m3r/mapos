<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Indices do modulo de projetos: o painel e o acompanhamento filtram por data
 * sem obra_id (RDO, historico de pontos, apontamento, consumo, check-in), a
 * consolidacao do ponto busca por obra + dia e por colaborador na equipe, e o
 * guia/sync contam pontos por obra + status.
 *
 * So cria indice se a tabela e as colunas existirem e o indice ainda nao existir.
 * Em bases grandes, rodar fora do horario de uso (ALTER TABLE trava escrita).
 *
 * Idempotente.
 */
class Migration_add_indices_projetos extends CI_Migration
{
    private $indices = [
        ['equipe_membros', 'idx_equipe_membros_colab', ['colaborador_id']],
        ['obra_rdo', 'idx_obra_rdo_data', ['data']],
        ['obra_ponto_historico', 'idx_obra_ponto_hist_data', ['data']],
        ['obra_apontamento', 'idx_obra_apont_data', ['data']],
        ['obra_consumo', 'idx_obra_consumo_data', ['data']],
        ['os_checkin', 'idx_os_checkin_entrada', ['data_entrada']],
        ['os_checkin', 'idx_os_checkin_saida', ['data_saida']],
        ['obra_pontos', 'idx_obra_pontos_obra_status', ['obra_id', 'status']],
        ['rh_ponto_registros', 'idx_rh_ponto_obra_data', ['obra_id', 'data_hora']],
    ];

    public function up()
    {
        foreach ($this->indices as [$tabela, $indice, $colunas]) {
            $this->addIndice($tabela, $indice, $colunas);
        }

        log_message('info', 'Migration add_indices_projetos executada com sucesso');
    }

    public function down()
    {
        foreach ($this->indices as [$tabela, $indice]) {
            if ($this->db->table_exists($tabela) && $this->indiceExiste($tabela, $indice)) {
                $this->db->query("ALTER TABLE `{$tabela}` DROP INDEX `{$indice}`");
            }
        }
    }

    /** Cria o indice so se a tabela e as colunas existirem e o indice faltar. */
    private function addIndice($tabela, $indice, array $colunas)
    {
        if (! $this->db->table_exists($tabela)) {
            return;
        }
        foreach ($colunas as $coluna) {
            if (! $this->db->field_exists($coluna, $tabela)) {
                return;
            }
        }
        if ($this->indiceExiste($tabela, $indice)) {
            return;
        }
        $lista = '`' . implode('`, `', $colunas) . '`';
        $this->db->query("ALTER TABLE `{$tabela}` ADD INDEX `{$indice}` ({$lista})");
    }

    private function indiceExiste($tabela, $indice)
    {
        $query = $this->db->query("SHOW INDEX FROM `{$tabela}` WHERE Key_name = ?", [$indice]);

        return $query !== false && $query->num_rows() > 0;
    }
}
