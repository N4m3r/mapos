<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_codigo_tributacao_municipal extends CI_Migration {

    public function up()
    {
        if (!$this->db->field_exists('codigo_tributacao_municipal', 'servicos')) {
            $this->dbforge->add_column('servicos', [
                'codigo_tributacao_municipal' => [
                    'type' => 'VARCHAR',
                    'constraint' => 3,
                    'null' => true,
                    'comment' => 'cTribMun: código de tributação municipal (3 dígitos), exigido por alguns municípios (ex.: Manaus) na NFS-e Nacional',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->field_exists('codigo_tributacao_municipal', 'servicos')) {
            $this->dbforge->drop_column('servicos', 'codigo_tributacao_municipal');
        }
    }
}
