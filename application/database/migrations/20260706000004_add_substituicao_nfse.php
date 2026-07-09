<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_substituicao_nfse extends CI_Migration {

    public function up()
    {
        if (!$this->db->field_exists('substitui_nota_id', 'notas_fiscais')) {
            $this->dbforge->add_column('notas_fiscais', [
                'substitui_nota_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'idNota da nota que ESTA substituiu (NFS-e Nacional). A original fica com status substituida.',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->field_exists('substitui_nota_id', 'notas_fiscais')) {
            $this->dbforge->drop_column('notas_fiscais', 'substitui_nota_id');
        }
    }
}
