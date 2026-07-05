<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_permissao_atendimentos extends CI_Migration {

    public function up()
    {
        // Adiciona a permissão vRelatorioAtendimentos ao grupo de permissões
        // Esta migration adiciona a permissão necessária para acessar o relatório de atendimentos

        // Verifica se já existe permissão com este nome
        $this->db->where('nome', 'Visualizar Relatório de Atendimentos');
        $exists = $this->db->get('permissoes');

        if ($exists->num_rows() == 0) {
            $permissoes = [
                'vRelatorioAtendimentos' => 1,
            ];

            $this->db->insert('permissoes', [
                'nome' => 'Visualizar Relatório de Atendimentos',
                'data' => date('Y-m-d'),
                'permissoes' => serialize($permissoes),
                'situacao' => 1,
            ]);
        }
    }

    public function down()
    {
        // Remove a permissão
        $this->db->where('nome', 'Visualizar Relatório de Atendimentos');
        $this->db->delete('permissoes');
    }
}
