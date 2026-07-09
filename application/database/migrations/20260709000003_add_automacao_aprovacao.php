<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_automacao_aprovacao extends CI_Migration
{
    public function up()
    {
        // Flag por cliente: usar a automação de aprovação (NFS-e + boleto).
        if (! $this->db->field_exists('automacao_aprovacao', 'clientes')) {
            $this->dbforge->add_column('clientes', [
                'automacao_aprovacao' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'after' => 'email_secundario',
                ],
            ]);
        }

        // Configurações globais da automação (padrões da NFS-e). Desligada por padrão.
        $this->seedConfig('automacao_aprovacao_ativa', '0');
        $this->seedConfig('automacao_desc_servico', 'Serviços referentes à OS nº {os_numero}');
        $this->seedConfig('automacao_info_complementar', '');
        $this->seedConfig('automacao_ctribnac', '');
        $this->seedConfig('automacao_ctribmun', '');
        $this->seedConfig('automacao_aliquota_iss', '');
        $this->seedConfig('automacao_tp_ret_issqn', '');
    }

    public function down()
    {
        if ($this->db->field_exists('automacao_aprovacao', 'clientes')) {
            $this->dbforge->drop_column('clientes', 'automacao_aprovacao');
        }
        $this->db->where_in('config', [
            'automacao_aprovacao_ativa', 'automacao_desc_servico', 'automacao_info_complementar',
            'automacao_ctribnac', 'automacao_ctribmun', 'automacao_aliquota_iss', 'automacao_tp_ret_issqn',
        ])->delete('configuracoes');
    }

    private function seedConfig($config, $valor)
    {
        if ($this->db->where('config', $config)->count_all_results('configuracoes') == 0) {
            $this->db->insert('configuracoes', ['config' => $config, 'valor' => $valor]);
        }
    }
}
