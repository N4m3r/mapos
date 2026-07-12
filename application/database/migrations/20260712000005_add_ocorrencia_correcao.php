<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Módulo de RH — correção estruturada na ocorrência.
 *
 * Para que a APROVAÇÃO de uma ocorrência de "correção de ponto" já aplique a
 * correção automaticamente, o colaborador informa a batida desejada (tipo +
 * data/hora). Campos:
 *  - correcao_tipo:      tipo da batida solicitada (entrada/saida/...)
 *  - correcao_data_hora: data/hora desejada da batida
 *  - correcao_aplicada:  1 quando a correção já foi efetivada (evita duplicar)
 */
class Migration_add_ocorrencia_correcao extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('rh_ocorrencias')) {
            return;
        }
        if (! $this->db->field_exists('correcao_tipo', 'rh_ocorrencias')) {
            $this->dbforge->add_column('rh_ocorrencias', [
                'correcao_tipo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                    'comment' => 'entrada | saida | inicio_intervalo | fim_intervalo',
                    'after' => 'registro_id',
                ],
            ]);
        }
        if (! $this->db->field_exists('correcao_data_hora', 'rh_ocorrencias')) {
            $this->dbforge->add_column('rh_ocorrencias', [
                'correcao_data_hora' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'correcao_tipo',
                ],
            ]);
        }
        if (! $this->db->field_exists('correcao_aplicada', 'rh_ocorrencias')) {
            $this->dbforge->add_column('rh_ocorrencias', [
                'correcao_aplicada' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'after' => 'correcao_data_hora',
                ],
            ]);
        }
    }

    public function down()
    {
        foreach (['correcao_aplicada', 'correcao_data_hora', 'correcao_tipo'] as $col) {
            if ($this->db->field_exists($col, 'rh_ocorrencias')) {
                $this->dbforge->drop_column('rh_ocorrencias', $col);
            }
        }
    }
}
