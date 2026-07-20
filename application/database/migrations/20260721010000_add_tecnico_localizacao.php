<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Localização em tempo real do técnico.
 *
 * 1) Cria a tabela `tecnico_localizacao` (histórico de pings de GPS enviados
 *    pelo dispositivo do técnico durante um atendimento ativo). O ping mais
 *    recente de cada técnico representa a posição atual; a sequência de pings
 *    de um mesmo check-in representa o trajeto percorrido no atendimento.
 * 2) Semeia a permissão `vTecnicoMapa` (ver o mapa de despacho) nos grupos de
 *    permissão que já enxergam OS (perfis administrativos), sem sobrescrever
 *    quem já tiver a chave.
 *
 * Idempotente: só cria o que faltar. Timestamp > 20260720000000 (consolidada),
 * então roda 1x via migration->latest().
 */
class Migration_Add_tecnico_localizacao extends CI_Migration
{
    public function up()
    {
        $dbg = $this->db->db_debug;
        $this->db->db_debug = false;

        if (! $this->db->table_exists('tecnico_localizacao')) {
            $this->dbforge->add_field([
                'idLocalizacao' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                    'auto_increment' => true,
                ],
                'usuarios_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                    'comment' => 'Técnico (usuarios.idUsuarios)',
                ],
                'os_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'OS do atendimento em andamento (se houver)',
                ],
                'checkin_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'Check-in ativo que originou o ping (os_checkin.idCheckin)',
                ],
                'latitude' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,7',
                    'null' => false,
                ],
                'longitude' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,7',
                    'null' => false,
                ],
                'precisao' => [
                    'type' => 'DECIMAL',
                    'constraint' => '8,2',
                    'null' => true,
                    'comment' => 'Precisão do GPS em metros (accuracy)',
                ],
                'velocidade' => [
                    'type' => 'DECIMAL',
                    'constraint' => '8,2',
                    'null' => true,
                    'comment' => 'Velocidade em m/s reportada pelo dispositivo',
                ],
                'bateria' => [
                    'type' => 'TINYINT',
                    'constraint' => 4,
                    'null' => true,
                    'comment' => 'Nível de bateria do dispositivo (0-100)',
                ],
                'data_hora' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
            ]);
            $this->dbforge->add_key('idLocalizacao', true);
            $this->dbforge->create_table('tecnico_localizacao', true);
            $this->db->query('ALTER TABLE `tecnico_localizacao` ENGINE = InnoDB');
            $this->db->query('ALTER TABLE `tecnico_localizacao` ADD INDEX `idx_tecloc_usuario_data` (`usuarios_id`, `data_hora`)');
            $this->db->query('ALTER TABLE `tecnico_localizacao` ADD INDEX `idx_tecloc_checkin` (`checkin_id`)');
            $this->db->query('ALTER TABLE `tecnico_localizacao` ADD INDEX `idx_tecloc_os` (`os_id`)');
        }

        // Semeia a permissão vTecnicoMapa nos grupos administrativos (que já veem OS).
        $this->seedPermissaoMapa();

        $this->db->db_debug = $dbg;
    }

    public function down()
    {
        $this->dbforge->drop_table('tecnico_localizacao', true);
    }

    /**
     * Adiciona a chave vTecnicoMapa=1 aos grupos de permissão que já possuem
     * vOs=1 (perfis administrativos) e ainda não têm a chave. Perfis de técnico
     * de campo não recebem — o mapa é uma tela de coordenação/despacho.
     */
    private function seedPermissaoMapa()
    {
        if (! $this->db->table_exists('permissoes')) {
            return;
        }

        $grupos = $this->db->get('permissoes')->result();
        foreach ($grupos as $grupo) {
            set_error_handler(static function () {
                return true;
            });
            $perms = unserialize((string) $grupo->permissoes);
            restore_error_handler();

            if (! is_array($perms)) {
                continue;
            }

            // Já tem a chave definida? não mexe (respeita escolha do admin).
            if (array_key_exists('vTecnicoMapa', $perms)) {
                continue;
            }

            // Concede a quem já vê OS no painel principal (perfil administrativo).
            $perms['vTecnicoMapa'] = (isset($perms['vOs']) && $perms['vOs'] == 1) ? 1 : 0;

            $this->db->where('idPermissao', $grupo->idPermissao);
            $this->db->update('permissoes', ['permissoes' => serialize($perms)]);
        }
    }
}
