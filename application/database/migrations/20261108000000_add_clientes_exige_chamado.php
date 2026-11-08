<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cliente que exige o ID do chamado na atividade não programada.
 *
 * Alguns clientes (Nova Era - Mercantil, Pátio Gourmet / TGFoods) só aceitam
 * o serviço feito sem agendamento se vier com o número do chamado deles. A
 * flag fica no cadastro do cliente e já nasce ligada para esses nomes.
 * Idempotente.
 */
class Migration_add_clientes_exige_chamado extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('clientes')) {
            return;
        }

        if (! $this->db->field_exists('exige_chamado', 'clientes')) {
            $this->db->query('ALTER TABLE `clientes` ADD COLUMN `exige_chamado` TINYINT(1) NOT NULL DEFAULT 0');
        }

        $this->load->model('atividade_pendencias_model');
        foreach ($this->db->select('idClientes, nomeCliente')->get('clientes')->result() as $c) {
            if (Atividade_pendencias_model::nomeExigeChamado($c->nomeCliente)) {
                $this->db->where('idClientes', (int) $c->idClientes)->update('clientes', ['exige_chamado' => 1]);
            }
        }

        log_message('info', 'Migration add_clientes_exige_chamado executada com sucesso');
    }

    public function down()
    {
        if ($this->db->table_exists('clientes') && $this->db->field_exists('exige_chamado', 'clientes')) {
            $this->dbforge->drop_column('clientes', 'exige_chamado');
        }
    }
}
