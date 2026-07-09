<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_automacao_override_os_e_permissao extends CI_Migration
{
    public function up()
    {
        // Override por OS: NULL = herda do cliente; 1 = forçar ativo; 0 = desativar nesta OS.
        if (! $this->db->field_exists('automacao_override', 'os')) {
            $this->dbforge->add_column('os', [
                'automacao_override' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }

        // Concede a nova permissão cAutomacao a quem já é admin (tem cSistema),
        // para não trancar o próprio dono fora da automação.
        $grupos = $this->db->get('permissoes')->result();
        foreach ($grupos as $g) {
            $perms = @unserialize((string) $g->permissoes);
            if (! is_array($perms)) {
                continue;
            }
            if (! empty($perms['cSistema']) && empty($perms['cAutomacao'])) {
                $perms['cAutomacao'] = 1;
                $this->db->where('idPermissao', $g->idPermissao)
                    ->update('permissoes', ['permissoes' => serialize($perms)]);
            }
        }
    }

    public function down()
    {
        if ($this->db->field_exists('automacao_override', 'os')) {
            $this->dbforge->drop_column('os', 'automacao_override');
        }
    }
}
