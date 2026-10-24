<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * As migrations de obras e contratos herdaram cObras/cContrato de "cOs", mas a
 * permissao de adicionar OS se chama "aOs". Resultado: nenhum grupo (nem o
 * Administrador) conseguia criar obra ou contrato.
 *
 * Libera cObras/cContrato para os grupos que podem adicionar OS e que estao
 * com a permissao desligada. Nao remove nada. Idempotente.
 */
class Migration_corrige_permissao_criar_contrato_obra extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('permissoes')) {
            return;
        }

        foreach ($this->db->get('permissoes')->result() as $grupo) {
            $perms = unserialize((string) $grupo->permissoes, ['allowed_classes' => false]);
            if (! is_array($perms) || empty($perms['aOs'])) {
                continue;
            }

            $mudou = false;
            foreach (['cObras', 'cContrato'] as $chave) {
                if (empty($perms[$chave])) {
                    $perms[$chave] = 1;
                    $mudou = true;
                }
            }

            if ($mudou) {
                $this->db->where('idPermissao', $grupo->idPermissao)
                    ->update('permissoes', ['permissoes' => serialize($perms)]);
            }
        }
    }

    public function down()
    {
        // Nao ha como saber quais grupos estavam sem a permissao de proposito.
    }
}
