<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Notification_triggers_model extends CI_Model
{
    protected $table = 'notification_triggers';

    /* ------------------------------------------------------------------ */
    /* Catálogos de opções (rótulos exibidos na tela)                      */
    /* ------------------------------------------------------------------ */

    public static function canaisDisponiveis()
    {
        return [
            'email' => 'E-mail',
            'whatsapp' => 'WhatsApp',
        ];
    }

    public static function destinatariosDisponiveis()
    {
        return [
            'cliente' => 'Cliente',
            'cliente_secundario' => 'E-mail secundário do cliente',
            'tecnico' => 'Técnico responsável',
            'emitente' => 'Empresa (emitente)',
        ];
    }

    public static function blocosDisponiveis()
    {
        return [
            'dados' => 'Dados da OS (status, datas, garantia)',
            'defeito' => 'Defeito apresentado',
            'laudo' => 'Laudo técnico',
            'observacoes' => 'Observações',
            'produtos' => 'Produtos',
            'servicos' => 'Serviços',
            'valores' => 'Valores / total',
        ];
    }

    public static function anexosDisponiveis()
    {
        return [
            'boleto' => 'Boleto / PIX (PDF)',
            'nota_fiscal' => 'Nota fiscal (produto ou serviço)',
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Consultas                                                           */
    /* ------------------------------------------------------------------ */

    public function getAll()
    {
        if (! $this->db->table_exists($this->table)) {
            return [];
        }

        return $this->db->order_by('grupo', 'ASC')->order_by('id', 'ASC')->get($this->table)->result();
    }

    public function getById($id)
    {
        if (! $this->db->table_exists($this->table)) {
            return null;
        }

        return $this->db->where('id', $id)->limit(1)->get($this->table)->row();
    }

    public function getByEvento($evento)
    {
        if (! $this->db->table_exists($this->table)) {
            return null;
        }

        return $this->db->where('evento', $evento)->limit(1)->get($this->table)->row();
    }

    /**
     * Todos os gatilhos ATIVOS de um evento, opcionalmente filtrados por canal.
     * Usado no disparo para permitir múltiplos gatilhos por evento.
     */
    public function getActiveByEvento($evento, $canal = null)
    {
        if (! $this->db->table_exists($this->table)) {
            return [];
        }

        $rows = $this->db->where('evento', $evento)->where('ativo', 1)->get($this->table)->result();
        if ($canal === null) {
            return $rows;
        }

        return array_values(array_filter($rows, function ($r) use ($canal) {
            return in_array($canal, self::toList($r->canais), true);
        }));
    }

    /**
     * Eventos que o sistema sabe disparar (catálogo para criar novos gatilhos).
     * Só permitir criar gatilhos para estes evita gatilhos "mortos" sem hook.
     */
    public static function eventosCatalogo()
    {
        return [
            'os_aberta' => ['nome' => 'OS aberta', 'grupo' => 'Ordem de Serviço'],
            'os_editada' => ['nome' => 'OS editada / status alterado', 'grupo' => 'Ordem de Serviço'],
            'os_aprovada' => ['nome' => 'OS aprovada', 'grupo' => 'Ordem de Serviço'],
            'os_finalizada' => ['nome' => 'OS finalizada', 'grupo' => 'Ordem de Serviço'],
            'cobranca_gerada' => ['nome' => 'Boleto / cobrança gerada', 'grupo' => 'Cobrança'],
            'cobranca_enviada' => ['nome' => 'Cobrança enviada (manual)', 'grupo' => 'Cobrança'],
            'pagamento_confirmado' => ['nome' => 'Pagamento confirmado', 'grupo' => 'Cobrança'],
            'nota_emitida' => ['nome' => 'Nota fiscal emitida', 'grupo' => 'Fiscal'],
            'cliente_novo' => ['nome' => 'Cliente cadastrado (boas-vindas)', 'grupo' => 'Cliente'],
        ];
    }

    public function create(array $data)
    {
        if (! $this->db->table_exists($this->table)) {
            return false;
        }

        $data['data_criacao'] = date('Y-m-d H:i:s');
        $data['data_atualizacao'] = $data['data_criacao'];
        $this->db->insert($this->table, $data);

        return $this->db->insert_id();
    }

    public function delete($id)
    {
        if (! $this->db->table_exists($this->table)) {
            return false;
        }

        $this->db->where('id', $id)->delete($this->table);

        return $this->db->affected_rows() > 0;
    }

    public function update($id, array $data)
    {
        if (! $this->db->table_exists($this->table)) {
            return false;
        }

        $data['data_atualizacao'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update($this->table, $data);

        return $this->db->affected_rows() >= 0;
    }

    /**
     * Converte uma string separada por vírgula em array limpo.
     */
    public static function toList($valor)
    {
        if (empty($valor)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $valor))));
    }
}
