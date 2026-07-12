<?php

class Migration_asaas_payment_gateway extends CI_Migration
{
    public function up()
    {
        // Um comando SQL por chamada: o driver mysqli/MySQL 8 rejeita
        // multiplas instrucoes num unico query() (erro 1064).
        if (! $this->db->field_exists('asaas_id', 'clientes')) {
            $this->db->query('ALTER TABLE clientes ADD asaas_id varchar(255) NULL');
        }
        $this->db->query('ALTER TABLE cobrancas MODIFY COLUMN charge_id VARCHAR(255) NOT NULL');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE cobrancas MODIFY COLUMN charge_id INT(11) NOT NULL');
        if ($this->db->field_exists('asaas_id', 'clientes')) {
            $this->db->query('ALTER TABLE clientes DROP COLUMN asaas_id');
        }
    }
}
