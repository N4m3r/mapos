<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Gatilhos do aceite do cliente e reenvio periódico do link de assinatura.
 *
 * - notification_triggers ganha reenvio_intervalo_horas e reenvio_max_tentativas
 *   (vazio/0 = não reenvia);
 * - tabela aceite_reenvios: uma linha por OS com link de aceite em loop;
 * - modelos de WhatsApp e gatilhos (desligados) dos eventos novos:
 *   atividade_np_finalizada, aceite_concluido, aceite_recusado, aceite_esgotado,
 *   além do modelo "aceite_lembrete" usado nos reenvios.
 *
 * Idempotente: só cria o que falta e não sobrescreve edições.
 */
class Migration_add_reenvio_aceite extends CI_Migration
{
    private const TAGS = '{CLIENTE_NOME},{NUMERO_OS},{LINK},{CHAMADO},{TENTATIVA},{NOME_ASSINANTE},{OBSERVACAO}';

    private function modelos(): array
    {
        return [
            ['slug' => 'aceite_np', 'nome' => 'Aceite: atividade não programada', 'descricao' => 'Link de aceite enviado ao finalizar uma atividade não programada.',
                'conteudo' => "Olá {CLIENTE_NOME}! A atividade não programada (OS #{NUMERO_OS}) foi concluída. Confirme o aceite, informe o Nº do chamado se houver e assine pelo link:\n{LINK}"],
            ['slug' => 'aceite_lembrete', 'nome' => 'Aceite: lembrete de assinatura', 'descricao' => 'Reenvio do link de aceite enquanto o cliente não assina.',
                'conteudo' => "Olá {CLIENTE_NOME}! Ainda falta o seu aceite do serviço da OS #{NUMERO_OS} (lembrete {TENTATIVA}). Leva menos de um minuto:\n{LINK}"],
            ['slug' => 'aceite_concluido', 'nome' => 'Aceite: cliente assinou', 'descricao' => 'Quando o cliente aceita e assina o serviço.',
                'conteudo' => "Aceite registrado na OS #{NUMERO_OS} por {NOME_ASSINANTE} ({CLIENTE_NOME}). Chamado: {CHAMADO}"],
            ['slug' => 'aceite_recusado', 'nome' => 'Aceite: cliente recusou', 'descricao' => 'Quando o cliente recusa o serviço pelo link.',
                'conteudo' => "A OS #{NUMERO_OS} foi RECUSADA por {NOME_ASSINANTE} ({CLIENTE_NOME}).\nMotivo: {OBSERVACAO}"],
            ['slug' => 'aceite_esgotado', 'nome' => 'Aceite: lembretes esgotados', 'descricao' => 'Quando acabam as tentativas de reenvio sem assinatura.',
                'conteudo' => "A OS #{NUMERO_OS} ({CLIENTE_NOME}) continua sem aceite depois de {TENTATIVA} lembrete(s). Entre em contato com o cliente."],
        ];
    }

    private function gatilhos(): array
    {
        return [
            ['evento' => 'atividade_np_finalizada', 'nome' => 'Atividade não programada finalizada', 'destinatarios' => 'cliente', 'slug' => 'aceite_np',
                'descricao' => 'Envia o link de aceite ao finalizar uma atividade não programada (substitui o "Aceite da OS" nessas OS).'],
            ['evento' => 'aceite_concluido', 'nome' => 'Cliente assinou o aceite', 'destinatarios' => '', 'slug' => 'aceite_concluido',
                'descricao' => 'Quando o cliente aceita e assina pelo link.'],
            ['evento' => 'aceite_recusado', 'nome' => 'Cliente recusou o aceite', 'destinatarios' => '', 'slug' => 'aceite_recusado',
                'descricao' => 'Quando o cliente recusa o serviço pelo link.'],
            ['evento' => 'aceite_esgotado', 'nome' => 'Aceite sem resposta (lembretes esgotados)', 'destinatarios' => '', 'slug' => 'aceite_esgotado',
                'descricao' => 'Quando acabam os reenvios do link sem a assinatura do cliente.'],
        ];
    }

    public function up()
    {
        $this->addColuna('notification_triggers', 'reenvio_intervalo_horas', '`reenvio_intervalo_horas` INT(11) NULL DEFAULT NULL');
        $this->addColuna('notification_triggers', 'reenvio_max_tentativas', '`reenvio_max_tentativas` INT(11) NULL DEFAULT NULL');

        if (! $this->db->table_exists('aceite_reenvios')) {
            $this->db->query("CREATE TABLE `aceite_reenvios` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `os_id` INT(11) NOT NULL,
                `evento` VARCHAR(60) NOT NULL COMMENT 'gatilho que configurou o reenvio',
                `intervalo_horas` INT(11) NOT NULL,
                `max_tentativas` INT(11) NOT NULL,
                `tentativas` INT(11) NOT NULL DEFAULT 0 COMMENT 'reenvios feitos (sem contar o envio inicial)',
                `situacao` VARCHAR(20) NOT NULL DEFAULT 'ativo' COMMENT 'ativo, aceito, recusado, esgotado, cancelado',
                `ultimo_envio` DATETIME NULL,
                `proximo_envio` DATETIME NULL,
                `criado_em` DATETIME NOT NULL,
                `encerrado_em` DATETIME NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_os` (`os_id`),
                KEY `idx_situacao_proximo` (`situacao`, `proximo_envio`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        $agora = date('Y-m-d H:i:s');
        if ($this->db->table_exists('whatsapp_templates')) {
            foreach ($this->modelos() as $m) {
                if ($this->db->where('slug', $m['slug'])->count_all_results('whatsapp_templates') == 0) {
                    $this->db->insert('whatsapp_templates', $m + ['tags' => self::TAGS, 'ativo' => 1]);
                }
            }
        }

        if ($this->db->table_exists('notification_triggers')) {
            foreach ($this->gatilhos() as $g) {
                if ($this->db->where('evento', $g['evento'])->count_all_results('notification_triggers') > 0) {
                    continue;
                }
                $linha = [
                    'evento' => $g['evento'],
                    'nome' => $g['nome'],
                    'grupo' => 'Aceite do cliente',
                    'descricao' => $g['descricao'],
                    'ativo' => 0,
                    'canais' => 'whatsapp',
                    'destinatarios' => $g['destinatarios'] ?: null,
                    'blocos' => null,
                    'anexos' => null,
                    'template_slug' => null,
                    'data_criacao' => $agora,
                    'data_atualizacao' => $agora,
                ];
                if ($this->db->field_exists('whatsapp_template', 'notification_triggers')) {
                    $linha['whatsapp_template'] = $g['slug'];
                }
                $this->db->insert('notification_triggers', $linha);
            }
        }

        log_message('info', 'Migration add_reenvio_aceite executada com sucesso');
    }

    public function down()
    {
        if ($this->db->table_exists('notification_triggers')) {
            $this->db->where_in('evento', array_column($this->gatilhos(), 'evento'))->delete('notification_triggers');
            foreach (['reenvio_intervalo_horas', 'reenvio_max_tentativas'] as $coluna) {
                if ($this->db->field_exists($coluna, 'notification_triggers')) {
                    $this->dbforge->drop_column('notification_triggers', $coluna);
                }
            }
        }
        if ($this->db->table_exists('whatsapp_templates')) {
            $this->db->where_in('slug', array_column($this->modelos(), 'slug'))->delete('whatsapp_templates');
        }
        if ($this->db->table_exists('aceite_reenvios')) {
            $this->dbforge->drop_table('aceite_reenvios');
        }
    }

    /** Adiciona uma coluna só se a tabela existir e a coluna faltar. */
    private function addColuna($tabela, $coluna, $definicao)
    {
        if ($this->db->table_exists($tabela) && ! $this->db->field_exists($coluna, $tabela)) {
            $this->db->query("ALTER TABLE `{$tabela}` ADD COLUMN {$definicao}");
        }
    }
}
