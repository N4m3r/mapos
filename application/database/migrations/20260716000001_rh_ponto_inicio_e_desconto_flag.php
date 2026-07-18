<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * RH — controle de ponto e desconto de faltas:
 *  - ponto_inicio no colaborador: só a partir desta data contam faltas/banco negativo
 *  - config rh_falta_desconto_automatico: default OFF (não gera lançamento R$)
 */
class Migration_rh_ponto_inicio_e_desconto_flag extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('rh_colaboradores')
            && ! $this->db->field_exists('ponto_inicio', 'rh_colaboradores')) {
            $this->dbforge->add_column('rh_colaboradores', [
                'ponto_inicio' => [
                    'type' => 'DATE',
                    'null' => true,
                    'comment' => 'Início do controle de ponto (faltas/banco). NULL = só conta batidas reais, sem dívida',
                ],
            ]);
        }

        if ($this->db->table_exists('configuracoes')) {
            $exists = $this->db->where('config', 'rh_falta_desconto_automatico')
                ->count_all_results('configuracoes');
            if ((int) $exists === 0) {
                $this->db->insert('configuracoes', [
                    'config' => 'rh_falta_desconto_automatico',
                    'valor' => '0',
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->table_exists('rh_colaboradores')
            && $this->db->field_exists('ponto_inicio', 'rh_colaboradores')) {
            $this->dbforge->drop_column('rh_colaboradores', 'ponto_inicio');
        }
        if ($this->db->table_exists('configuracoes')) {
            $this->db->where('config', 'rh_falta_desconto_automatico')->delete('configuracoes');
        }
    }
}
