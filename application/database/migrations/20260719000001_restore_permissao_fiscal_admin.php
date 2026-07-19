<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Reparo: re-concede as permissoes do modulo fiscal ao grupo Administrador.
 *
 * O modulo fiscal (20260705000001_add_modulo_fiscal) semeou cNfe/eNfe/vNfe/dNfe
 * direto no blob do Administrador, mas o controller Permissoes (adicionar/editar)
 * e as telas de permissao nao incluiam esses campos. Assim, a PRIMEIRA vez que o
 * grupo Administrador era salvo pela tela, o serialize() reconstruia o blob sem
 * as chaves fiscais e a emissao de NF-e/NFS-e "sumia" (o botao e liberado por
 * checkPermission(..., 'eNfe')).
 *
 * Os checkboxes fiscais ja foram adicionados as telas e ao controller; esta
 * migration restaura o que foi perdido no grupo Administrador (idPermissao = 1).
 *
 * Idempotente: apenas garante as 4 chaves = 1; nao mexe em outros grupos nem em
 * outras permissoes do admin.
 */
class Migration_restore_permissao_fiscal_admin extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('permissoes')) {
            return;
        }

        $this->db->where('idPermissao', 1);
        $query = $this->db->get('permissoes');

        if ($query->num_rows() === 0) {
            return;
        }

        $admin = $query->row();
        $permissoes = @unserialize($admin->permissoes);

        if (! is_array($permissoes)) {
            return;
        }

        $permissoes['cNfe'] = 1; // configurar modulo fiscal
        $permissoes['eNfe'] = 1; // emitir notas
        $permissoes['vNfe'] = 1; // visualizar notas
        $permissoes['dNfe'] = 1; // cancelar notas

        $this->db->where('idPermissao', 1);
        $this->db->update('permissoes', ['permissoes' => serialize($permissoes)]);
    }

    public function down()
    {
        // Reparo de dados: sem reversao. A remocao das permissoes fiscais do
        // admin ja e tratada pelo down() da migration do modulo fiscal.
    }
}
