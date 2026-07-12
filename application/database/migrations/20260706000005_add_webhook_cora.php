<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Guarda o id do endpoint de webhook registrado na Cora, para dar baixa
 * automática no pagamento (evita registrar duplicado).
 */
class Migration_add_webhook_cora extends CI_Migration
{
    public function up()
    {
        if (! $this->db->field_exists('webhook_endpoint_id', 'configuracoes_cora')) {
            $this->dbforge->add_column('configuracoes_cora', [
                'webhook_endpoint_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                    'comment' => 'ID do endpoint de webhook registrado na Cora',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->field_exists('webhook_endpoint_id', 'configuracoes_cora')) {
            $this->dbforge->drop_column('configuracoes_cora', 'webhook_endpoint_id');
        }
    }
}
