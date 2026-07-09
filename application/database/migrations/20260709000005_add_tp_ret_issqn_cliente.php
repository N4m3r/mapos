<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_tp_ret_issqn_cliente extends CI_Migration
{
    public function up()
    {
        // Retenção de ISS da NFS-e por cliente:
        //   NULL = usa o padrão (automação / config fiscal) | 1 = não retido | 2 = retido
        if (! $this->db->field_exists('tp_ret_issqn', 'clientes')) {
            $this->dbforge->add_column('clientes', [
                'tp_ret_issqn' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => true,
                    'default' => null,
                    'after' => 'automacao_aprovacao',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->field_exists('tp_ret_issqn', 'clientes')) {
            $this->dbforge->drop_column('clientes', 'tp_ret_issqn');
        }
    }
}
