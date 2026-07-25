<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Geofence do atendimento em campo (check-in/check-out da OS).
 *
 * Amarra a batida de presença ao LOCAL da OS: ao fechar a OS o técnico
 * precisa (ou não, conforme o modo) estar dentro da área do atendimento.
 *
 *  - Colunas em os_checkin guardam a distância e se estava dentro da área,
 *    tanto na entrada quanto na saída — usadas para validação e para o
 *    painel de revisão do gestor.
 *  - Configs globais controlam o comportamento:
 *      os_geofence_modo         off | soft | hard   (começa em "soft")
 *      os_geofence_raio_metros  raio da área em metros (padrão 200)
 *
 * No modo "soft" (adaptação) nada trava: o sistema apenas registra e
 * sinaliza "fora da área". Trocando para "hard", o fechamento fora do raio
 * passa a ser bloqueado.
 */
class Migration_add_geofence_atendimento extends CI_Migration
{
    public function up()
    {
        // 1) Colunas de geofence no check-in ----------------------------
        if ($this->db->table_exists('os_checkin')) {
            $novas = [];
            if (! $this->db->field_exists('distancia_entrada_metros', 'os_checkin')) {
                $novas['distancia_entrada_metros'] = [
                    'type' => 'INT', 'constraint' => 11, 'null' => true,
                    'comment' => 'Distância (m) da entrada até o local da OS',
                    'after' => 'longitude_entrada',
                ];
            }
            if (! $this->db->field_exists('dentro_geofence_entrada', 'os_checkin')) {
                $novas['dentro_geofence_entrada'] = [
                    'type' => 'TINYINT', 'constraint' => 1, 'null' => true,
                    'comment' => '1=dentro da área, 0=fora, NULL=sem GPS/local',
                ];
            }
            if (! $this->db->field_exists('distancia_saida_metros', 'os_checkin')) {
                $novas['distancia_saida_metros'] = [
                    'type' => 'INT', 'constraint' => 11, 'null' => true,
                    'comment' => 'Distância (m) da saída até o local da OS',
                    'after' => 'longitude_saida',
                ];
            }
            if (! $this->db->field_exists('dentro_geofence_saida', 'os_checkin')) {
                $novas['dentro_geofence_saida'] = [
                    'type' => 'TINYINT', 'constraint' => 1, 'null' => true,
                    'comment' => '1=dentro da área, 0=fora, NULL=sem GPS/local',
                ];
            }
            if (! empty($novas)) {
                $this->dbforge->add_column('os_checkin', $novas);
            }
        }

        // 2) Configs globais (semeadas só se ainda não existirem) --------
        if ($this->db->table_exists('configuracoes')) {
            $this->semearConfig('os_geofence_modo', 'soft');
            $this->semearConfig('os_geofence_raio_metros', '200');
        }

        log_message('info', 'Migration add_geofence_atendimento executada com sucesso');
    }

    public function down()
    {
        if ($this->db->table_exists('os_checkin')) {
            foreach (['distancia_entrada_metros', 'dentro_geofence_entrada', 'distancia_saida_metros', 'dentro_geofence_saida'] as $col) {
                if ($this->db->field_exists($col, 'os_checkin')) {
                    $this->dbforge->drop_column('os_checkin', $col);
                }
            }
        }

        if ($this->db->table_exists('configuracoes')) {
            $this->db->where_in('config', ['os_geofence_modo', 'os_geofence_raio_metros'])
                ->delete('configuracoes');
        }
    }

    /** Insere a config apenas se a chave ainda não existir. */
    private function semearConfig($config, $valor)
    {
        $existe = $this->db->where('config', $config)->count_all_results('configuracoes');
        if ($existe == 0) {
            $this->db->insert('configuracoes', ['config' => $config, 'valor' => $valor]);
        }
    }
}
