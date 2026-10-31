<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Avisos ao cliente por WhatsApp durante o atendimento em campo: cria os
 * modelos de mensagem e os gatilhos (ativos, ao cliente) de 5 eventos.
 * Idempotente: só cria o que ainda não existe e não sobrescreve edições.
 */
class Migration_add_gatilhos_atendimento extends CI_Migration
{
    private const TAGS = '{CLIENTE_NOME},{NUMERO_OS},{EMITENTE},{TECNICO_NOME},{DATA_AGENDAMENTO},{MOTIVO},{LINK_ACOMPANHAR}';

    private function definicoes(): array
    {
        return [
            [
                'evento' => 'os_tecnico_a_caminho',
                'nome' => 'Técnico a caminho',
                'descricao' => 'Quando o técnico toca em "Estou a caminho" na Área do Técnico.',
                'slug' => 'atend_a_caminho',
                'modelo' => 'Aviso: técnico a caminho',
                'conteudo' => "Olá {CLIENTE_NOME}! {TECNICO_NOME}, técnico da {EMITENTE}, está a caminho para o atendimento da OS {NUMERO_OS}.\n\nAcompanhe: {LINK_ACOMPANHAR}",
            ],
            [
                'evento' => 'os_tecnico_chegou',
                'nome' => 'Técnico chegou ao local',
                'descricao' => 'Quando o técnico registra a chegada (check-in) no local.',
                'slug' => 'atend_chegou',
                'modelo' => 'Aviso: técnico chegou',
                'conteudo' => "Olá {CLIENTE_NOME}! {TECNICO_NOME}, da {EMITENTE}, chegou ao local e já iniciou o atendimento da OS {NUMERO_OS}.\n\nAcompanhe: {LINK_ACOMPANHAR}",
            ],
            [
                'evento' => 'os_nao_realizada',
                'nome' => 'Atendimento não realizado',
                'descricao' => 'Quando o técnico registra que não foi possível realizar o atendimento.',
                'slug' => 'atend_nao_realizado',
                'modelo' => 'Aviso: atendimento não realizado',
                'conteudo' => "Olá {CLIENTE_NOME}! Infelizmente não foi possível realizar o atendimento da OS {NUMERO_OS}.\nMotivo: {MOTIVO}\n\nA {EMITENTE} entrará em contato para reagendar. Obrigado pela compreensão!",
            ],
            [
                'evento' => 'os_reagendada',
                'nome' => 'Visita reagendada',
                'descricao' => 'Quando a data da visita é alterada (reagendamento ou arrastar na agenda).',
                'slug' => 'atend_reagendada',
                'modelo' => 'Aviso: visita reagendada',
                'conteudo' => "Olá {CLIENTE_NOME}! A visita da OS {NUMERO_OS} foi reagendada para {DATA_AGENDAMENTO}. Técnico: {TECNICO_NOME}.\n\nAcompanhe: {LINK_ACOMPANHAR}",
            ],
            [
                'evento' => 'os_tecnico_definido',
                'nome' => 'Técnico/data da visita definidos',
                'descricao' => 'Quando o técnico e/ou a data da visita são definidos na OS.',
                'slug' => 'atend_tecnico_definido',
                'modelo' => 'Aviso: técnico e data da visita',
                'conteudo' => "Olá {CLIENTE_NOME}! Sua visita da OS {NUMERO_OS} está agendada para {DATA_AGENDAMENTO}, com {TECNICO_NOME} da {EMITENTE}.\n\nAcompanhe: {LINK_ACOMPANHAR}",
            ],
        ];
    }

    public function up()
    {
        $agora = date('Y-m-d H:i:s');

        foreach ($this->definicoes() as $d) {
            if ($this->db->table_exists('whatsapp_templates')
                && $this->db->where('slug', $d['slug'])->count_all_results('whatsapp_templates') == 0) {
                $this->db->insert('whatsapp_templates', [
                    'slug' => $d['slug'],
                    'nome' => $d['modelo'],
                    'descricao' => $d['descricao'],
                    'tags' => self::TAGS,
                    'conteudo' => $d['conteudo'],
                    'ativo' => 1,
                ]);
            }

            if ($this->db->table_exists('notification_triggers')
                && $this->db->where('evento', $d['evento'])->count_all_results('notification_triggers') == 0) {
                $linha = [
                    'evento' => $d['evento'],
                    'nome' => $d['nome'],
                    'grupo' => 'Atendimento em campo',
                    'descricao' => $d['descricao'],
                    'ativo' => 1,
                    'canais' => 'whatsapp',
                    'destinatarios' => 'cliente',
                    'blocos' => null,
                    'anexos' => null,
                    'template_slug' => null,
                    'data_criacao' => $agora,
                    'data_atualizacao' => $agora,
                ];
                if ($this->db->field_exists('whatsapp_template', 'notification_triggers')) {
                    $linha['whatsapp_template'] = $d['slug'];
                }
                $this->db->insert('notification_triggers', $linha);
            }
        }

        log_message('info', 'Migration add_gatilhos_atendimento executada com sucesso');
    }

    public function down()
    {
        $eventos = array_column($this->definicoes(), 'evento');
        $slugs = array_column($this->definicoes(), 'slug');
        if ($this->db->table_exists('notification_triggers')) {
            $this->db->where_in('evento', $eventos)->delete('notification_triggers');
        }
        if ($this->db->table_exists('whatsapp_templates')) {
            $this->db->where_in('slug', $slugs)->delete('whatsapp_templates');
        }
    }
}
