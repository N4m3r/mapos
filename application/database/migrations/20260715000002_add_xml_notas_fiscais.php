<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Persiste o XML autorizado da nota fiscal na tabela notas_fiscais.
 * Mantém xml_path (arquivo em disco) e adiciona xml (conteúdo no banco).
 */
class Migration_add_xml_notas_fiscais extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('notas_fiscais')) {
            return;
        }
        if ($this->db->field_exists('xml', 'notas_fiscais')) {
            return;
        }

        $this->db->query(
            'ALTER TABLE `notas_fiscais`
             ADD COLUMN `xml` LONGTEXT NULL DEFAULT NULL
             COMMENT \'Conteúdo do XML autorizado (NF-e / NFS-e)\'
             AFTER `xml_path`'
        );
    }

    public function down()
    {
        if ($this->db->table_exists('notas_fiscais')
            && $this->db->field_exists('xml', 'notas_fiscais')) {
            $this->dbforge->drop_column('notas_fiscais', 'xml');
        }
    }
}
