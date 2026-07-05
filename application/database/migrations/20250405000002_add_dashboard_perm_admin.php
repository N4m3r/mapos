<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_dashboard_perm_admin extends CI_Migration {

    public function up()
    {
        // Buscar o grupo Administrador (idPermissao = 1)
        $this->db->where('idPermissao', 1);
        $query = $this->db->get('permissoes');

        if ($query->num_rows() > 0) {
            $admin = $query->row();

            // Deserializa as permissões
            $permissoes = unserialize($admin->permissoes);

            if (is_array($permissoes)) {
                // Adiciona as novas permissões do Dashboard
                $permissoes['vDashboard'] = 1;
                $permissoes['vRelatorioCompleto'] = 1;
                $permissoes['vExportarDados'] = 1;

                // Atualiza no banco
                $this->db->where('idPermissao', 1);
                $this->db->update('permissoes', ['permissoes' => serialize($permissoes)]);

                log_message('info', 'Permissões do Dashboard adicionadas ao grupo Administrador');
            }
        }
    }

    public function down()
    {
        // Buscar o grupo Administrador (idPermissao = 1)
        $this->db->where('idPermissao', 1);
        $query = $this->db->get('permissoes');

        if ($query->num_rows() > 0) {
            $admin = $query->row();

            // Deserializa as permissões
            $permissoes = unserialize($admin->permissoes);

            if (is_array($permissoes)) {
                // Remove as permissões do Dashboard
                unset($permissoes['vDashboard']);
                unset($permissoes['vRelatorioCompleto']);
                unset($permissoes['vExportarDados']);

                // Atualiza no banco
                $this->db->where('idPermissao', 1);
                $this->db->update('permissoes', ['permissoes' => serialize($permissoes)]);
            }
        }
    }
}
