<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Módulo de RH — registro de ponto.
 *
 *  - rh_ponto_registros: cada batida (entrada/saída/intervalo) com foto,
 *    GPS, geofence, score facial e origem (browser|whatsapp|manual).
 *  - rh_face_biometria:  descriptor facial de referência do colaborador
 *    (vetor gerado no navegador com face-api.js) + selfie de referência.
 *  - rh_ocorrencias:     pedidos de correção/justificativa feitos pelo
 *    colaborador, que passam por aprovação (reusa o padrão de aprovação).
 *
 * Fotos e descriptors ficam em base64/JSON no banco — mesmo padrão robusto
 * do check-in de atendimento (funciona em hospedagem sem escrita em disco).
 */
class Migration_create_rh_ponto extends CI_Migration
{
    public function up()
    {
        // ------------------------------------------------------------------
        // rh_ponto_registros — batidas de ponto
        // ------------------------------------------------------------------
        if (! $this->db->table_exists('rh_ponto_registros')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'colaborador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'data_hora' => [
                    'type' => 'DATETIME',
                    'comment' => 'Momento da batida',
                ],
                'tipo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'entrada',
                    'comment' => 'entrada | saida | inicio_intervalo | fim_intervalo',
                ],
                'origem' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'browser',
                    'comment' => 'browser | whatsapp | manual',
                ],
                'unidade_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'latitude' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,7',
                    'null' => true,
                ],
                'longitude' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,7',
                    'null' => true,
                ],
                'dentro_geofence' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => true,
                    'comment' => '1=dentro 0=fora null=sem referência',
                ],
                'distancia_metros' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'face_score' => [
                    'type' => 'DECIMAL',
                    'constraint' => '5,4',
                    'null' => true,
                    'comment' => 'Similaridade facial (0..1); quanto maior, melhor',
                ],
                'foto_base64' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                    'comment' => 'Selfie da batida (data URI base64)',
                ],
                'foto_mime' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => true,
                ],
                'ip' => [
                    'type' => 'VARCHAR',
                    'constraint' => 45,
                    'null' => true,
                ],
                'user_agent' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'valido',
                    'comment' => 'valido | ajustado | pendente | rejeitado',
                ],
                'observacao' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'registrado_por' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'usuarios_id de quem lançou (quando origem=manual)',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('rh_ponto_registros', true);

            $this->db->query('ALTER TABLE `rh_ponto_registros` ADD INDEX `idx_rh_ponto_colab_data` (`colaborador_id`, `data_hora`)');
            $this->db->query('ALTER TABLE `rh_ponto_registros` ADD INDEX `idx_rh_ponto_status` (`status`)');
        }

        // ------------------------------------------------------------------
        // rh_face_biometria — descriptor facial de referência
        // ------------------------------------------------------------------
        if (! $this->db->table_exists('rh_face_biometria')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'colaborador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'descriptor' => [
                    'type' => 'TEXT',
                    'comment' => 'Vetor facial (JSON de floats) gerado no navegador',
                ],
                'foto_ref' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                    'comment' => 'Selfie de referência (data URI base64)',
                ],
                'foto_mime' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => true,
                ],
                'modelo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => true,
                    'comment' => 'Identificação do modelo/lib usada',
                ],
                'situacao' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('rh_face_biometria', true);

            $this->db->query('ALTER TABLE `rh_face_biometria` ADD INDEX `idx_rh_face_colab` (`colaborador_id`)');
        }

        // ------------------------------------------------------------------
        // rh_ocorrencias — justificativas / correções (com aprovação)
        // ------------------------------------------------------------------
        if (! $this->db->table_exists('rh_ocorrencias')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'colaborador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'tipo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'comment' => 'correcao_ponto | justificativa_falta | abono',
                ],
                'data_referencia' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'registro_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'Batida relacionada (rh_ponto_registros.id)',
                ],
                'descricao' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'anexo_base64' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                    'comment' => 'Atestado/comprovante (data URI base64)',
                ],
                'anexo_mime' => [
                    'type' => 'VARCHAR',
                    'constraint' => 60,
                    'null' => true,
                ],
                'anexo_nome' => [
                    'type' => 'VARCHAR',
                    'constraint' => 160,
                    'null' => true,
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'pendente',
                    'comment' => 'pendente | aprovado | recusado',
                ],
                'aprovador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'data_analise' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'resposta' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('rh_ocorrencias', true);

            $this->db->query('ALTER TABLE `rh_ocorrencias` ADD INDEX `idx_rh_ocorr_colab` (`colaborador_id`)');
            $this->db->query('ALTER TABLE `rh_ocorrencias` ADD INDEX `idx_rh_ocorr_status` (`status`)');
        }
    }

    public function down()
    {
        foreach (['rh_ocorrencias', 'rh_face_biometria', 'rh_ponto_registros'] as $tabela) {
            if ($this->db->table_exists($tabela)) {
                $this->dbforge->drop_table($tabela, true);
            }
        }
    }
}
