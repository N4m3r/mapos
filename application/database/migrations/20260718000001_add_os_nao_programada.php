<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Atividade não programada (aberta pelo técnico em campo).
 *
 * 1) Adiciona a coluna `os.nao_programada` (flag) para diferenciar as OS que o
 *    próprio técnico abre na Área do Técnico, sem passar pelo administrativo.
 * 2) Concede a permissão `aTecnicoAtividade` (criar atividade não programada) a
 *    todos os grupos que já possuem acesso à Área do Técnico (vTecnicoDashboard),
 *    para que os técnicos existentes ganhem a capacidade automaticamente.
 *
 * Idempotente: só age quando falta a coluna/permissão.
 */
class Migration_add_os_nao_programada extends CI_Migration
{
    public function up()
    {
        // 1) Coluna de marcação da OS -----------------------------------
        if ($this->db->table_exists('os') && ! $this->db->field_exists('nao_programada', 'os')) {
            $this->db->query(
                "ALTER TABLE `os` ADD COLUMN `nao_programada` TINYINT(1) NOT NULL DEFAULT 0"
            );
        }

        // 2) Permissão para os grupos com acesso à Área do Técnico -------
        if ($this->db->table_exists('permissoes')) {
            $grupos = $this->db->get('permissoes')->result();
            foreach ($grupos as $g) {
                // Desserialização resiliente (blobs podem estar corrompidos).
                set_error_handler(static function () {
                    return true;
                });
                $perms = unserialize((string) $g->permissoes);
                restore_error_handler();

                if (! is_array($perms)) {
                    continue;
                }

                // Só concede a quem já acessa a Área do Técnico e ainda não tem a permissão.
                $temAreaTecnico = ! empty($perms['vTecnicoDashboard']);
                if ($temAreaTecnico && ! array_key_exists('aTecnicoAtividade', $perms)) {
                    $perms['aTecnicoAtividade'] = 1;
                    $this->db->where('idPermissao', $g->idPermissao);
                    $this->db->update('permissoes', ['permissoes' => serialize($perms)]);
                }
            }
        }
    }

    public function down()
    {
        if ($this->db->table_exists('os') && $this->db->field_exists('nao_programada', 'os')) {
            $this->db->query('ALTER TABLE `os` DROP COLUMN `nao_programada`');
        }

        // Remove a permissão dos grupos (mantém o restante intacto).
        if ($this->db->table_exists('permissoes')) {
            $grupos = $this->db->get('permissoes')->result();
            foreach ($grupos as $g) {
                set_error_handler(static function () {
                    return true;
                });
                $perms = unserialize((string) $g->permissoes);
                restore_error_handler();

                if (is_array($perms) && array_key_exists('aTecnicoAtividade', $perms)) {
                    unset($perms['aTecnicoAtividade']);
                    $this->db->where('idPermissao', $g->idPermissao);
                    $this->db->update('permissoes', ['permissoes' => serialize($perms)]);
                }
            }
        }
    }
}
