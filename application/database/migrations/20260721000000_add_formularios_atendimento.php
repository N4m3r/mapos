<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Formulários de Atendimento personalizados.
 *
 * Permite ao administrador montar formulários com campos livres (texto,
 * área de texto, seleção suspensa, etc.) vinculados às etapas do fluxo de
 * atendimento (iniciar / durante / finalizar). As respostas do técnico
 * ficam vinculadas à OS e ao check-in.
 */
class Migration_add_formularios_atendimento extends CI_Migration
{
    public function up()
    {
        // 1) Formulários -------------------------------------------------
        if (! $this->db->table_exists('formularios_atendimento')) {
            $this->dbforge->add_field([
                'idFormulario' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'nome' => ['type' => 'VARCHAR', 'constraint' => 150],
                'descricao' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'etapa' => ['type' => "ENUM('iniciar','durante','finalizar')", 'default' => 'iniciar'],
                'obrigatorio' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'ativo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'ordem' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'data_cadastro' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idFormulario', true);
            $this->dbforge->create_table('formularios_atendimento', true, ['ENGINE' => 'InnoDB']);
        }

        // 2) Campos de cada formulário -----------------------------------
        if (! $this->db->table_exists('formularios_atendimento_campos')) {
            $this->dbforge->add_field([
                'idCampo' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'formulario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'label' => ['type' => 'VARCHAR', 'constraint' => 200],
                'tipo' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'texto'],
                'opcoes' => ['type' => 'TEXT', 'null' => true],
                'placeholder' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
                'ajuda' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'obrigatorio' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'ordem' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            ]);
            $this->dbforge->add_key('idCampo', true);
            $this->dbforge->add_key('formulario_id');
            $this->dbforge->create_table('formularios_atendimento_campos', true, ['ENGINE' => 'InnoDB']);
        }

        // 3) Respostas (uma por formulário/OS/check-in) ------------------
        if (! $this->db->table_exists('formularios_atendimento_respostas')) {
            $this->dbforge->add_field([
                'idResposta' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'formulario_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'os_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'checkin_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'usuarios_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'etapa' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'data_resposta' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idResposta', true);
            $this->dbforge->add_key('os_id');
            $this->dbforge->add_key('formulario_id');
            $this->dbforge->create_table('formularios_atendimento_respostas', true, ['ENGINE' => 'InnoDB']);
        }

        // 4) Itens da resposta (valor por campo) -------------------------
        if (! $this->db->table_exists('formularios_atendimento_respostas_itens')) {
            $this->dbforge->add_field([
                'idItem' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'resposta_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'campo_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'label' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
                'valor' => ['type' => 'TEXT', 'null' => true],
            ]);
            $this->dbforge->add_key('idItem', true);
            $this->dbforge->add_key('resposta_id');
            $this->dbforge->create_table('formularios_atendimento_respostas_itens', true, ['ENGINE' => 'InnoDB']);
        }

        // 5) Permissão cFormularioAtendimento ----------------------------
        // Concede automaticamente a quem já administra o sistema (cSistema=1).
        $grupos = $this->db->get('permissoes')->result();
        foreach ($grupos as $g) {
            $perms = @unserialize($g->permissoes);
            if (! is_array($perms)) {
                continue;
            }
            if (! array_key_exists('cFormularioAtendimento', $perms)) {
                $perms['cFormularioAtendimento'] = ! empty($perms['cSistema']) ? '1' : 0;
                $this->db->where('idPermissao', $g->idPermissao)
                    ->update('permissoes', ['permissoes' => serialize($perms)]);
            }
        }

        log_message('info', 'Migration formularios_atendimento executada com sucesso');
    }

    public function down()
    {
        $this->dbforge->drop_table('formularios_atendimento_respostas_itens', true);
        $this->dbforge->drop_table('formularios_atendimento_respostas', true);
        $this->dbforge->drop_table('formularios_atendimento_campos', true);
        $this->dbforge->drop_table('formularios_atendimento', true);

        $grupos = $this->db->get('permissoes')->result();
        foreach ($grupos as $g) {
            $perms = @unserialize($g->permissoes);
            if (is_array($perms) && array_key_exists('cFormularioAtendimento', $perms)) {
                unset($perms['cFormularioAtendimento']);
                $this->db->where('idPermissao', $g->idPermissao)
                    ->update('permissoes', ['permissoes' => serialize($perms)]);
            }
        }
    }
}
