<?php

class Tecnico_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Obtem todas as OS designadas a um tecnico
     */
    public function getMinhasOs($tecnico_id, $status = 'todos', $data_inicio = null, $data_fim = null)
    {
        $this->db->select('os.*, clientes.nomeCliente, clientes.telefone, clientes.celular');
        $this->db->from('os');
        $this->db->join('clientes', 'clientes.idClientes = os.clientes_id', 'left');

        // Filtrar pelo tecnico responsavel
        $this->db->where('os.tecnico_responsavel', $tecnico_id);

        // Filtro por status
        if ($status !== 'todos') {
            if ($status === 'pendente') {
                $this->db->where_in('os.status', ['Aberto', 'Orçamento']);
            } elseif ($status === 'em_andamento') {
                $this->db->where('os.status', 'Em Andamento');
            } elseif ($status === 'finalizado') {
                $this->db->where_in('os.status', ['Finalizado', 'Faturado']);
            }
        }

        // Filtro por data
        if ($data_inicio) {
            $this->db->where('os.dataInicial >=', $data_inicio);
        }
        if ($data_fim) {
            $this->db->where('os.dataInicial <=', $data_fim);
        }

        // Ordenar por data mais recente
        $this->db->order_by('os.dataInicial', 'DESC');

        $query = $this->db->get();

        return $query ? $query->result() : [];
    }

    /**
     * Obtem uma OS especifica verificando se pertence ao tecnico
     */
    public function getOsById($os_id, $tecnico_id = null)
    {
        $this->db->select('os.*, clientes.*');
        $this->db->from('os');
        $this->db->join('clientes', 'clientes.idClientes = os.clientes_id', 'left');
        $this->db->where('os.idOs', $os_id);

        // Se informado tecnico_id, verificar se a OS pertence a ele
        if ($tecnico_id !== null) {
            $this->db->where('os.tecnico_responsavel', $tecnico_id);
        }

        $this->db->limit(1);

        $query = $this->db->get();

        return $query ? $query->row() : null;
    }

    /**
     * Obtem OS do dia atual designadas ao tecnico
     */
    public function getOsHoje($tecnico_id)
    {
        $hoje = date('Y-m-d');

        $this->db->select('os.*, clientes.nomeCliente');
        $this->db->from('os');
        $this->db->join('clientes', 'clientes.idClientes = os.clientes_id', 'left');
        $this->db->where('os.tecnico_responsavel', $tecnico_id);
        $this->db->where('DATE(os.dataInicial)', $hoje);
        $this->db->where_not_in('os.status', ['Finalizado', 'Cancelado']);

        $query = $this->db->get();

        return $query ? $query->result() : [];
    }

    /**
     * Obtem OS pendentes (Aberto/Orçamento) designadas ao tecnico
     */
    public function getOsPendentes($tecnico_id)
    {
        $this->db->select('os.*, clientes.nomeCliente');
        $this->db->from('os');
        $this->db->join('clientes', 'clientes.idClientes = os.clientes_id', 'left');
        $this->db->where('os.tecnico_responsavel', $tecnico_id);
        $this->db->where_in('os.status', ['Aberto', 'Orçamento']);

        $query = $this->db->get();

        return $query ? $query->result() : [];
    }

    /**
     * Obtem OS em andamento designadas ao tecnico
     */
    public function getOsEmAndamento($tecnico_id)
    {
        $this->db->select('os.*, clientes.nomeCliente, os_checkin.data_entrada');
        $this->db->from('os');
        $this->db->join('clientes', 'clientes.idClientes = os.clientes_id', 'left');
        $this->db->join('os_checkin', 'os_checkin.os_id = os.idOs AND os_checkin.status = "Em Andamento"', 'left');
        $this->db->where('os.tecnico_responsavel', $tecnico_id);
        $this->db->where('os.status', 'Em Andamento');

        $query = $this->db->get();

        return $query ? $query->result() : [];
    }

    /**
     * Obtem OS finalizadas hoje pelo tecnico
     */
    public function getOsFinalizadasHoje($tecnico_id)
    {
        $hoje = date('Y-m-d');

        $this->db->select('os.*, clientes.nomeCliente');
        $this->db->from('os');
        $this->db->join('clientes', 'clientes.idClientes = os.clientes_id', 'left');
        $this->db->join('os_checkin', 'os_checkin.os_id = os.idOs', 'left');
        $this->db->where('os.tecnico_responsavel', $tecnico_id);
        $this->db->where_in('os.status', ['Finalizado', 'Faturado']);
        $this->db->where('DATE(os_checkin.data_saida)', $hoje);

        $query = $this->db->get();

        return $query ? $query->result() : [];
    }

    /**
     * Obtem estatisticas do tecnico
     */
    public function getEstatisticas($tecnico_id)
    {
        $mes_atual = date('Y-m-01');
        $mes_passado = date('Y-m-01', strtotime('-1 month'));

        // Total de OS no mes
        $this->db->where('tecnico_responsavel', $tecnico_id);
        $this->db->where('dataInicial >=', $mes_atual);
        $total_os_mes = $this->db->count_all_results('os');

        // OS finalizadas no mes
        $this->db->where('tecnico_responsavel', $tecnico_id);
        $this->db->where('dataInicial >=', $mes_atual);
        $this->db->where_in('status', ['Finalizado', 'Faturado']);
        $os_finalizadas_mes = $this->db->count_all_results('os');

        // OS em andamento
        $this->db->where('tecnico_responsavel', $tecnico_id);
        $this->db->where('status', 'Em Andamento');
        $os_andamento = $this->db->count_all_results('os');

        // OS pendentes
        $this->db->where('tecnico_responsavel', $tecnico_id);
        $this->db->where_in('status', ['Aberto', 'Orçamento']);
        $os_pendentes = $this->db->count_all_results('os');

        // Tempo medio de atendimento (em horas)
        $this->db->select('AVG(TIMESTAMPDIFF(HOUR, os_checkin.data_entrada, os_checkin.data_saida)) as tempo_medio');
        $this->db->from('os_checkin');
        $this->db->join('os', 'os.idOs = os_checkin.os_id');
        $this->db->where('os.tecnico_responsavel', $tecnico_id);
        $this->db->where('os_checkin.data_saida IS NOT NULL');
        $this->db->where('os_checkin.data_entrada >=', $mes_atual);
        $query = $this->db->get();
        $tempo_medio = ($query && $query->num_rows() > 0) ? round($query->row()->tempo_medio, 1) : 0;

        return [
            'total_os_mes' => $total_os_mes,
            'os_finalizadas_mes' => $os_finalizadas_mes,
            'os_andamento' => $os_andamento,
            'os_pendentes' => $os_pendentes,
            'tempo_medio_horas' => $tempo_medio
        ];
    }

    /**
     * Atribuir tecnico a uma OS
     */
    public function atribuirTecnico($os_id, $tecnico_id, $atribuido_por, $observacao = null)
    {
        // Verificar se ja existe atribuicao ativa
        $this->db->where('os_id', $os_id);
        $this->db->where('tecnico_id', $tecnico_id);
        $this->db->where('data_remocao IS NULL');
        $existe = $this->db->get('os_tecnico_atribuicao');

        if ($existe && $existe->num_rows() > 0) {
            return false; // Ja esta atribuido
        }

        // Registrar atribuicao no historico
        $this->db->insert('os_tecnico_atribuicao', [
            'os_id' => $os_id,
            'tecnico_id' => $tecnico_id,
            'atribuido_por' => $atribuido_por,
            'data_atribuicao' => date('Y-m-d H:i:s'),
            'observacao' => $observacao
        ]);

        // Atualizar campo na OS
        $this->db->where('idOs', $os_id);
        return $this->db->update('os', ['tecnico_responsavel' => $tecnico_id]);
    }

    /**
     * Remover tecnico de uma OS
     */
    public function removerTecnico($os_id, $motivo = null)
    {
        // Atualizar historico
        $this->db->where('os_id', $os_id);
        $this->db->where('data_remocao IS NULL');
        $this->db->update('os_tecnico_atribuicao', [
            'data_remocao' => date('Y-m-d H:i:s'),
            'motivo_remocao' => $motivo
        ]);

        // Limpar campo na OS
        $this->db->where('idOs', $os_id);
        return $this->db->update('os', ['tecnico_responsavel' => null]);
    }

    /**
     * Listar tecnicos disponiveis
     */
    public function getTecnicosDisponiveis()
    {
        // Buscar usuarios do grupo Tecnico (permissoes_id = 6 tipicamente)
        $this->db->select('usuarios.*');
        $this->db->from('usuarios');
        $this->db->where('usuarios.situacao', 1); // Ativo
        $this->db->order_by('usuarios.nome', 'ASC');

        $query = $this->db->get();

        return $query ? $query->result() : [];
    }

    /**
     * Obter historico de atribuicoes de uma OS
     */
    public function getHistoricoAtribuicoes($os_id)
    {
        $this->db->select('os_tecnico_atribuicao.*, usuarios.nome as nome_tecnico, u2.nome as nome_atribuidor');
        $this->db->from('os_tecnico_atribuicao');
        $this->db->join('usuarios', 'usuarios.idUsuarios = os_tecnico_atribuicao.tecnico_id');
        $this->db->join('usuarios u2', 'u2.idUsuarios = os_tecnico_atribuicao.atribuido_por');
        $this->db->where('os_tecnico_atribuicao.os_id', $os_id);
        $this->db->order_by('os_tecnico_atribuicao.data_atribuicao', 'DESC');

        $query = $this->db->get();

        return $query ? $query->result() : [];
    }

    /**
     * Obter OS sem técnico atribuído
     */
    public function getOsSemTecnico($limite = 50)
    {
        $this->db->select('os.*, clientes.nomeCliente, clientes.telefone');
        $this->db->from('os');
        $this->db->join('clientes', 'clientes.idClientes = os.clientes_id', 'left');
        $this->db->where('os.tecnico_responsavel IS NULL');
        $this->db->where_not_in('os.status', ['Finalizado', 'Cancelado', 'Faturado']);
        $this->db->order_by('os.dataInicial', 'ASC');
        $this->db->limit($limite);

        $query = $this->db->get();

        return $query ? $query->result() : [];
    }

    /**
     * Contar OS sem técnico atribuído
     */
    public function countOsSemTecnico()
    {
        $this->db->where('tecnico_responsavel IS NULL');
        $this->db->where_not_in('status', ['Finalizado', 'Cancelado', 'Faturado']);
        $result = $this->db->count_all_results('os');
        return ($result !== false) ? $result : 0;
    }

    /**
     * Contar OS com técnico atribuído
     */
    public function countOsComTecnico()
    {
        $this->db->where('tecnico_responsavel IS NOT NULL');
        $this->db->where_not_in('status', ['Finalizado', 'Cancelado', 'Faturado']);
        $result = $this->db->count_all_results('os');
        return ($result !== false) ? $result : 0;
    }

    /**
     * Contar todas as OS pendentes para atribuição
     */
    public function countOsParaAtribuicao()
    {
        $this->db->where_not_in('status', ['Finalizado', 'Cancelado', 'Faturado']);
        $result = $this->db->count_all_results('os');
        return ($result !== false) ? $result : 0;
    }

    /**
     * Buscar técnicos com filtro de permissão
     */
    public function getTecnicosAtivos($permissoes_ids = [])
    {
        $this->db->select('usuarios.*, permissoes.nome as nome_permissao');
        $this->db->from('usuarios');
        $this->db->join('permissoes', 'permissoes.idPermissao = usuarios.permissoes_id', 'left');
        $this->db->where('usuarios.situacao', 1); // Ativo

        if (!empty($permissoes_ids)) {
            $this->db->where_in('usuarios.permissoes_id', $permissoes_ids);
        }

        $this->db->order_by('usuarios.nome', 'ASC');

        $query = $this->db->get();

        return $query ? $query->result() : [];
    }
}
