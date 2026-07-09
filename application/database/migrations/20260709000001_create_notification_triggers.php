<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_create_notification_triggers extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('notification_triggers')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'evento' => ['type' => 'VARCHAR', 'constraint' => 60],
                'nome' => ['type' => 'VARCHAR', 'constraint' => 120],
                'descricao' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'grupo' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
                'ativo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                // Listas separadas por vírgula.
                'canais' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'destinatarios' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
                'blocos' => ['type' => 'TEXT', 'null' => true],
                'anexos' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'template_slug' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                'data_criacao' => ['type' => 'DATETIME', 'null' => true],
                'data_atualizacao' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('evento');
            $this->dbforge->create_table('notification_triggers');
        }

        // Intervalo (em segundos) do disparo automático da fila de e-mail.
        $this->seedConfig('notif_intervalo_disparo', '120');

        $agora = date('Y-m-d H:i:s');
        foreach ($this->eventosPadrao() as $ev) {
            $ev['data_criacao'] = $agora;
            $ev['data_atualizacao'] = $agora;
            $this->seedEvento($ev);
        }
    }

    public function down()
    {
        if ($this->db->table_exists('notification_triggers')) {
            $this->dbforge->drop_table('notification_triggers');
        }
        $this->db->where('config', 'notif_intervalo_disparo')->delete('configuracoes');
    }

    private function seedConfig($config, $valor)
    {
        if ($this->db->where('config', $config)->count_all_results('configuracoes') == 0) {
            $this->db->insert('configuracoes', ['config' => $config, 'valor' => $valor]);
        }
    }

    private function seedEvento($ev)
    {
        if ($this->db->where('evento', $ev['evento'])->count_all_results('notification_triggers') == 0) {
            $this->db->insert('notification_triggers', $ev);
        }
    }

    private function eventosPadrao()
    {
        $blocosOs = 'dados,defeito,laudo,observacoes,produtos,servicos,valores';

        return [
            ['evento' => 'os_aberta', 'nome' => 'OS aberta', 'grupo' => 'Ordem de Serviço', 'descricao' => 'Ao criar uma nova Ordem de Serviço.', 'ativo' => 1, 'canais' => 'email', 'destinatarios' => 'cliente', 'blocos' => $blocosOs, 'anexos' => null, 'template_slug' => 'os'],
            ['evento' => 'os_editada', 'nome' => 'OS editada / status alterado', 'grupo' => 'Ordem de Serviço', 'descricao' => 'Ao editar a OS ou mudar o status.', 'ativo' => 1, 'canais' => 'email', 'destinatarios' => 'cliente', 'blocos' => $blocosOs, 'anexos' => null, 'template_slug' => 'os'],
            ['evento' => 'os_aprovada', 'nome' => 'OS aprovada', 'grupo' => 'Ordem de Serviço', 'descricao' => 'Quando o cliente aprova o orçamento/OS.', 'ativo' => 1, 'canais' => 'email,whatsapp', 'destinatarios' => 'cliente,tecnico', 'blocos' => 'dados,valores', 'anexos' => null, 'template_slug' => 'os'],
            ['evento' => 'os_finalizada', 'nome' => 'OS finalizada', 'grupo' => 'Ordem de Serviço', 'descricao' => 'Quando a OS é concluída.', 'ativo' => 1, 'canais' => 'email', 'destinatarios' => 'cliente', 'blocos' => $blocosOs, 'anexos' => null, 'template_slug' => 'os'],
            ['evento' => 'cobranca_gerada', 'nome' => 'Boleto / cobrança gerada', 'grupo' => 'Cobrança', 'descricao' => 'Ao gerar o boleto/PIX da nota ou cobrança.', 'ativo' => 1, 'canais' => 'email', 'destinatarios' => 'cliente,cliente_secundario', 'blocos' => null, 'anexos' => 'boleto', 'template_slug' => 'cobranca'],
            ['evento' => 'cobranca_enviada', 'nome' => 'Cobrança enviada (manual)', 'grupo' => 'Cobrança', 'descricao' => 'Ao clicar em enviar a cobrança por e-mail.', 'ativo' => 1, 'canais' => 'email', 'destinatarios' => 'cliente,cliente_secundario', 'blocos' => null, 'anexos' => 'boleto', 'template_slug' => 'cobranca'],
            ['evento' => 'pagamento_confirmado', 'nome' => 'Pagamento confirmado', 'grupo' => 'Cobrança', 'descricao' => 'Quando o pagamento do boleto/PIX é identificado.', 'ativo' => 0, 'canais' => 'email,whatsapp', 'destinatarios' => 'cliente', 'blocos' => null, 'anexos' => null, 'template_slug' => null],
            ['evento' => 'nota_emitida', 'nome' => 'Nota fiscal emitida', 'grupo' => 'Fiscal', 'descricao' => 'Ao autorizar uma NF-e / NFS-e.', 'ativo' => 0, 'canais' => 'email', 'destinatarios' => 'cliente,cliente_secundario', 'blocos' => null, 'anexos' => 'nota_fiscal', 'template_slug' => null],
            ['evento' => 'cliente_novo', 'nome' => 'Cliente cadastrado (boas-vindas)', 'grupo' => 'Cliente', 'descricao' => 'Ao cadastrar um novo cliente.', 'ativo' => 0, 'canais' => 'email', 'destinatarios' => 'cliente', 'blocos' => null, 'anexos' => null, 'template_slug' => null],
        ];
    }
}
