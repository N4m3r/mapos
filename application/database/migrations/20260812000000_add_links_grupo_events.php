<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Encurtador de links + envio para grupos de WhatsApp de eventos com link.
 *
 * 1) Tabela `short_links`: encurtador interno (dominio/l/<slug>) usado para
 *    deixar o link do aceite e do RDO curtos na mensagem de WhatsApp.
 * 2) Colunas de token público no `obra_rdo`: permitem abrir o RDO por um link
 *    temporário (dominio/rdo/<token>) sem login, para enviar ao grupo.
 * 3) Eventos novos de notificação: `os_aceite` (dispara o link de aceite ao
 *    finalizar o atendimento) e `rdo_registrado` (dispara o link do RDO ao
 *    registrar um diário de projeto). Semeados INATIVOS: o gestor escolhe o(s)
 *    grupo(s) e os clientes e depois ativa.
 * 4) Modelo de WhatsApp `rdo` (mensagem do envio do RDO ao grupo).
 *
 * Idempotente: só cria o que ainda não existe.
 */
class Migration_add_links_grupo_events extends CI_Migration
{
    public function up()
    {
        $comum = ['ENGINE' => 'InnoDB'];

        // 1) Encurtador de links interno ------------------------------------
        if (! $this->db->table_exists('short_links')) {
            $this->dbforge->add_field([
                'idLink' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'slug' => ['type' => 'VARCHAR', 'constraint' => 16],
                'url' => ['type' => 'TEXT'],
                'url_hash' => ['type' => 'CHAR', 'constraint' => 32, 'null' => true], // md5(url) p/ reuso
                'clicks' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
                'expira' => ['type' => 'DATETIME', 'null' => true],
                'criado_em' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idLink', true);
            $this->dbforge->create_table('short_links', true, $comum);

            // Slug único (busca por slug) e índice de reuso por hash da URL.
            $this->db->query('ALTER TABLE `short_links` ADD UNIQUE KEY `uq_slug` (`slug`)');
            $this->db->query('ALTER TABLE `short_links` ADD KEY `ix_url_hash` (`url_hash`)');
        }

        // 2) Token público do RDO -------------------------------------------
        $this->addColuna('obra_rdo', 'token', "`token` VARCHAR(64) NULL DEFAULT NULL");
        $this->addColuna('obra_rdo', 'token_expira', "`token_expira` DATETIME NULL DEFAULT NULL");

        // 3) Eventos de notificação novos (inativos por padrão) -------------
        if ($this->db->table_exists('notification_triggers')) {
            $agora = date('Y-m-d H:i:s');
            $eventos = [
                [
                    'evento' => 'os_aceite',
                    'nome' => 'Aceite da OS (resolução do chamado)',
                    'grupo' => 'Ordem de Serviço',
                    'descricao' => 'Ao finalizar/resolver o chamado, envia o link de aceite (assinatura do cliente). Use {LINK} na mensagem.',
                    'template_slug' => 'aceite',
                ],
                [
                    'evento' => 'rdo_registrado',
                    'nome' => 'RDO de projeto registrado',
                    'grupo' => 'Projetos',
                    'descricao' => 'Ao registrar um diário de projeto (RDO), envia o link com o que foi feito. Use {LINK} na mensagem.',
                    'template_slug' => 'rdo',
                ],
            ];
            foreach ($eventos as $ev) {
                if ($this->db->where('evento', $ev['evento'])->count_all_results('notification_triggers') == 0) {
                    $this->db->insert('notification_triggers', [
                        'evento' => $ev['evento'],
                        'nome' => $ev['nome'],
                        'grupo' => $ev['grupo'],
                        'descricao' => $ev['descricao'],
                        'ativo' => 0,
                        'canais' => 'whatsapp',
                        'destinatarios' => 'cliente',
                        'blocos' => null,
                        'anexos' => null,
                        'template_slug' => null,
                        'data_criacao' => $agora,
                        'data_atualizacao' => $agora,
                    ]);
                }
            }
        }

        // 4) Modelo de WhatsApp do RDO --------------------------------------
        if ($this->db->table_exists('whatsapp_templates')
            && $this->db->where('slug', 'rdo')->count_all_results('whatsapp_templates') == 0) {
            $this->db->insert('whatsapp_templates', [
                'slug' => 'rdo',
                'nome' => 'RDO de projeto',
                'descricao' => 'Enviado ao registrar um diário de projeto (RDO) para o(s) grupo(s) selecionado(s).',
                'tags' => '{PROJETO},{NUMERO_RDO},{RESPONSAVEL},{DATA},{ATIVIDADES},{LINK}',
                'conteudo' => "📋 *RDO Nº {NUMERO_RDO}* — {PROJETO}\nResponsável: {RESPONSAVEL}\nData: {DATA}\n\n*O que foi feito:*\n{ATIVIDADES}\n\nDetalhes e fotos:\n{LINK}",
                'ativo' => 1,
            ]);
        }

        log_message('info', 'Migration add_links_grupo_events executada com sucesso');
    }

    public function down()
    {
        if ($this->db->table_exists('short_links')) {
            $this->dbforge->drop_table('short_links', true);
        }
        foreach (['token', 'token_expira'] as $c) {
            if ($this->db->field_exists($c, 'obra_rdo')) {
                $this->dbforge->drop_column('obra_rdo', $c);
            }
        }
        if ($this->db->table_exists('notification_triggers')) {
            $this->db->where_in('evento', ['os_aceite', 'rdo_registrado'])->delete('notification_triggers');
        }
        if ($this->db->table_exists('whatsapp_templates')) {
            $this->db->where('slug', 'rdo')->delete('whatsapp_templates');
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
