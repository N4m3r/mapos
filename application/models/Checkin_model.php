<?php

class Checkin_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Verifica se a tabela existe
     */
    private function tabelaExiste()
    {
        return $this->db->table_exists('os_checkin');
    }

    /**
     * Obtém check-in por ID
     */
    public function getById($id)
    {
        if (!$this->tabelaExiste()) {
            return null;
        }

        $this->db->select('*');
        $this->db->from('os_checkin');
        $this->db->where('idCheckin', $id);
        $this->db->limit(1);

        $query = $this->db->get();
        return $query ? $query->row() : null;
    }

    /**
     * Obtém check-in ativo por OS (sem data de saída)
     */
    public function getByOs($os_id)
    {
        if (!$this->tabelaExiste()) {
            return null;
        }

        $this->db->select('*');
        $this->db->from('os_checkin');
        $this->db->where('os_id', $os_id);
        $this->db->order_by('idCheckin', 'desc');
        $this->db->limit(1);

        $query = $this->db->get();
        return $query ? $query->row() : null;
    }

    /**
     * Obtém todos os check-ins de uma OS
     */
    public function getAllByOs($os_id)
    {
        if (!$this->tabelaExiste()) {
            return [];
        }

        $this->db->select('os_checkin.*, usuarios.nome as nome_tecnico');
        $this->db->from('os_checkin');
        $this->db->join('usuarios', 'usuarios.idUsuarios = os_checkin.usuarios_id', 'left');
        $this->db->where('os_id', $os_id);
        $this->db->order_by('idCheckin', 'desc');

        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    /**
     * Verifica se existe check-in ativo (sem check-out)
     */
    public function hasCheckinAtivo($os_id)
    {
        if (!$this->tabelaExiste()) {
            return false;
        }

        $this->db->where('os_id', $os_id);
        $this->db->where('data_saida IS NULL');
        // Ignora checkins antigos (mais de 24 horas) - considera "presos"
        $this->db->where('data_entrada >', date('Y-m-d H:i:s', strtotime('-24 hours')));
        $query = $this->db->get('os_checkin');

        return $query && $query->num_rows() > 0;
    }

    /**
     * Obtém check-in ativo
     */
    public function getCheckinAtivo($os_id)
    {
        if (!$this->tabelaExiste()) {
            return null;
        }

        $this->db->select('*');
        $this->db->from('os_checkin');
        $this->db->where('os_id', $os_id);
        $this->db->where('data_saida IS NULL');
        // Ignora checkins antigos (mais de 24 horas) - considera "presos"
        $this->db->where('data_entrada >', date('Y-m-d H:i:s', strtotime('-24 hours')));
        $this->db->limit(1);

        $query = $this->db->get();
        return $query ? $query->row() : null;
    }

    /**
     * Obtém último check-in (ativo ou não) para a OS
     * Nota: Este método retorna o último checkin independente da data.
     * Para verificar checkins ativos (considerando regra de 24h), use getCheckinAtivo()
     */
    public function getUltimoCheckin($os_id)
    {
        if (!$this->tabelaExiste()) {
            return null;
        }

        $this->db->select('*');
        $this->db->from('os_checkin');
        $this->db->where('os_id', $os_id);
        $this->db->order_by('idCheckin', 'DESC');
        $this->db->limit(1);

        $query = $this->db->get();
        return $query ? $query->row() : null;
    }

    /**
     * Obtém check-in ativo considerando a regra de 24h
     * Auto-finaliza checkins "presos" com mais de 24h automaticamente
     */
    public function getCheckinAtivoComAutoFinalizacao($os_id)
    {
        if (!$this->tabelaExiste()) {
            return null;
        }

        // Primeiro tenta obter checkin ativo (menos de 24h)
        $checkin = $this->getCheckinAtivo($os_id);

        if ($checkin) {
            return $checkin;
        }

        // Verifica se existe checkin "preso" (sem data_saida)
        $ultimo = $this->getUltimoCheckin($os_id);
        if ($ultimo && empty($ultimo->data_saida)) {
            // Se tem mais de 24h, auto-finaliza
            if (strtotime($ultimo->data_entrada) < strtotime('-24 hours')) {
                $this->finalizarAtendimento($ultimo->idCheckin, [
                    'data_saida' => date('Y-m-d H:i:s'),
                    'observacao_saida' => 'Finalizado automaticamente (atendimento anterior não concluído - expirado)',
                    'status' => 'Finalizado'
                ]);
                log_info('Auto-finalizado checkin expirado da OS: ' . $os_id . ' (mais de 24h)');
            }
        }

        return null;
    }

    /**
     * Adiciona novo check-in
     */
    public function add($data, $returnId = false)
    {
        if (!$this->tabelaExiste()) {
            return false;
        }

        $this->db->insert('os_checkin', $data);
        if ($this->db->affected_rows() == '1') {
            if ($returnId == true) {
                return $this->db->insert_id();
            }

            return true;
        }

        return false;
    }

    /**
     * Atualiza check-in
     */
    public function edit($data, $id)
    {
        if (!$this->tabelaExiste()) {
            return false;
        }

        $this->db->where('idCheckin', $id);
        $this->db->update('os_checkin', $data);

        if ($this->db->affected_rows() >= 0) {
            return true;
        }

        return false;
    }

    /**
     * Realiza check-out
     */
    public function finalizarAtendimento($id, $data)
    {
        if (!$this->tabelaExiste()) {
            return false;
        }

        $this->db->where('idCheckin', $id);
        $this->db->update('os_checkin', $data);

        if ($this->db->affected_rows() >= 0) {
            return true;
        }

        return false;
    }

    /**
     * Remove check-in
     */
    public function delete($id)
    {
        if (!$this->tabelaExiste()) {
            return false;
        }

        $this->db->where('idCheckin', $id);
        $this->db->delete('os_checkin');

        if ($this->db->affected_rows() == '1') {
            return true;
        }

        return false;
    }

    /**
     * Conta total de check-ins
     */
    public function count()
    {
        if (!$this->tabelaExiste()) {
            return 0;
        }

        return $this->db->count_all('os_checkin');
    }

    /**
     * Relatório de atendimentos por período
     */
    public function getAtendimentosPorPeriodo($data_inicio, $data_fim, $usuario_id = null)
    {
        if (!$this->tabelaExiste()) {
            return [];
        }

        $this->db->select('os_checkin.*, usuarios.nome as nome_tecnico, os.status as os_status');
        $this->db->from('os_checkin');
        $this->db->join('usuarios', 'usuarios.idUsuarios = os_checkin.usuarios_id', 'left');
        $this->db->join('os', 'os.idOs = os_checkin.os_id', 'left');
        $this->db->where('DATE(os_checkin.data_entrada) >=', $data_inicio);
        $this->db->where('DATE(os_checkin.data_entrada) <=', $data_fim);

        if ($usuario_id) {
            $this->db->where('os_checkin.usuarios_id', $usuario_id);
        }

        $this->db->order_by('os_checkin.data_entrada', 'desc');

        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    /**
     * Estatísticas de atendimentos
     */
    public function getEstatisticas($data_inicio, $data_fim, $usuario_id = null)
    {
        if (!$this->tabelaExiste()) {
            return (object)[
                'total_atendimentos' => 0,
                'tempo_medio_minutos' => 0,
                'finalizados' => 0,
                'em_andamento' => 0
            ];
        }

        $this->db->select('
            COUNT(*) as total_atendimentos,
            AVG(TIMESTAMPDIFF(MINUTE, data_entrada, data_saida)) as tempo_medio_minutos,
            SUM(CASE WHEN data_saida IS NOT NULL THEN 1 ELSE 0 END) as finalizados,
            SUM(CASE WHEN data_saida IS NULL THEN 1 ELSE 0 END) as em_andamento
        ');
        $this->db->from('os_checkin');
        $this->db->where('DATE(data_entrada) >=', $data_inicio);
        $this->db->where('DATE(data_entrada) <=', $data_fim);

        if ($usuario_id) {
            $this->db->where('usuarios_id', $usuario_id);
        }

        $query = $this->db->get();
        return $query ? $query->row() : (object)[
            'total_atendimentos' => 0,
            'tempo_medio_minutos' => 0,
            'finalizados' => 0,
            'em_andamento' => 0
        ];
    }
}
