<?php

class Relatorioatendimentos_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Obtém atendimentos com filtros
     *
     * @param string $data_inicio Data inicial (Y-m-d)
     * @param string $data_fim Data final (Y-m-d)
     * @param int|null $usuario_id ID do usuário (opcional)
     * @param int $limit Limite de registros
     * @param int $offset Offset para paginação
     * @return array Lista de atendimentos
     */
    public function getAtendimentosComFiltros($data_inicio, $data_fim, $usuario_id = null, $limit = 25, $offset = 0)
    {
        $this->db->select('os_checkin.*, usuarios.nome as nome_tecnico');
        $this->db->from('os_checkin');
        $this->db->join('usuarios', 'usuarios.idUsuarios = os_checkin.usuarios_id');
        $this->db->where('DATE(os_checkin.data_entrada) >=', $data_inicio);
        $this->db->where('DATE(os_checkin.data_entrada) <=', $data_fim);

        if ($usuario_id) {
            $this->db->where('os_checkin.usuarios_id', $usuario_id);
        }

        $this->db->order_by('os_checkin.data_entrada', 'DESC');
        $this->db->limit($limit, $offset);

        return $this->db->get()->result();
    }

    /**
     * Conta total de atendimentos com filtros
     *
     * @param string $data_inicio Data inicial
     * @param string $data_fim Data final
     * @param int|null $usuario_id ID do usuário
     * @return int Total de registros
     */
    public function countAtendimentos($data_inicio, $data_fim, $usuario_id = null)
    {
        $this->db->where('DATE(data_entrada) >=', $data_inicio);
        $this->db->where('DATE(data_entrada) <=', $data_fim);

        if ($usuario_id) {
            $this->db->where('usuarios_id', $usuario_id);
        }

        return $this->db->count_all_results('os_checkin');
    }

    /**
     * Obtém estatísticas gerais de atendimentos
     *
     * @param string $data_inicio Data inicial
     * @param string $data_fim Data final
     * @param int|null $usuario_id ID do usuário
     * @return object Objeto com estatísticas
     */
    public function getEstatisticas($data_inicio, $data_fim, $usuario_id = null)
    {
        $this->db->select('
            COUNT(*) as total_atendimentos,
            SUM(CASE WHEN data_saida IS NOT NULL THEN 1 ELSE 0 END) as finalizados,
            SUM(CASE WHEN data_saida IS NULL THEN 1 ELSE 0 END) as em_andamento,
            AVG(TIMESTAMPDIFF(MINUTE, data_entrada, data_saida)) as tempo_medio_minutos
        ');
        $this->db->from('os_checkin');
        $this->db->where('DATE(data_entrada) >=', $data_inicio);
        $this->db->where('DATE(data_entrada) <=', $data_fim);

        if ($usuario_id) {
            $this->db->where('usuarios_id', $usuario_id);
        }

        $result = $this->db->get()->row();

        // Converte minutos para horas decimais
        if ($result->tempo_medio_minutos) {
            $result->tempo_medio_horas = round($result->tempo_medio_minutos / 60, 2);
        } else {
            $result->tempo_medio_horas = 0;
        }

        return $result;
    }

    /**
     * Obtém atendimentos agrupados por dia
     *
     * @param string $data_inicio Data inicial
     * @param string $data_fim Data final
     * @param int|null $usuario_id ID do usuário
     * @return array Lista com data e quantidade
     */
    public function getAtendimentosPorDia($data_inicio, $data_fim, $usuario_id = null)
    {
        $this->db->select('DATE(data_entrada) as data, COUNT(*) as quantidade');
        $this->db->from('os_checkin');
        $this->db->where('DATE(data_entrada) >=', $data_inicio);
        $this->db->where('DATE(data_entrada) <=', $data_fim);

        if ($usuario_id) {
            $this->db->where('usuarios_id', $usuario_id);
        }

        $this->db->group_by('DATE(data_entrada)');
        $this->db->order_by('data', 'ASC');

        return $this->db->get()->result();
    }

    /**
     * Obtém atendimentos agrupados por técnico
     *
     * @param string $data_inicio Data inicial
     * @param string $data_fim Data final
     * @return array Lista com técnico e quantidade
     */
    public function getAtendimentosPorTecnico($data_inicio, $data_fim)
    {
        $this->db->select('usuarios.nome as tecnico, COUNT(*) as quantidade');
        $this->db->from('os_checkin');
        $this->db->join('usuarios', 'usuarios.idUsuarios = os_checkin.usuarios_id');
        $this->db->where('DATE(os_checkin.data_entrada) >=', $data_inicio);
        $this->db->where('DATE(os_checkin.data_entrada) <=', $data_fim);
        $this->db->group_by('os_checkin.usuarios_id');
        $this->db->order_by('quantidade', 'DESC');

        return $this->db->get()->result();
    }

    /**
     * Obtém atendimentos agrupados por status
     *
     * @param string $data_inicio Data inicial
     * @param string $data_fim Data final
     * @param int|null $usuario_id ID do usuário
     * @return array Lista com status e quantidade
     */
    public function getAtendimentosPorStatus($data_inicio, $data_fim, $usuario_id = null)
    {
        $this->db->select('
            CASE WHEN data_saida IS NOT NULL THEN "Finalizado" ELSE "Em Andamento" END as status,
            COUNT(*) as quantidade
        ');
        $this->db->from('os_checkin');
        $this->db->where('DATE(data_entrada) >=', $data_inicio);
        $this->db->where('DATE(data_entrada) <=', $data_fim);

        if ($usuario_id) {
            $this->db->where('usuarios_id', $usuario_id);
        }

        $this->db->group_by('status');

        return $this->db->get()->result();
    }

    /**
     * Obtém tempo médio de atendimento por técnico
     *
     * @param string $data_inicio Data inicial
     * @param string $data_fim Data final
     * @return array Lista com técnico e tempo médio
     */
    public function getTempoMedioPorTecnico($data_inicio, $data_fim)
    {
        $this->db->select('
            usuarios.nome as tecnico,
            AVG(TIMESTAMPDIFF(MINUTE, os_checkin.data_entrada, os_checkin.data_saida)) as tempo_medio_minutos
        ');
        $this->db->from('os_checkin');
        $this->db->join('usuarios', 'usuarios.idUsuarios = os_checkin.usuarios_id');
        $this->db->where('DATE(os_checkin.data_entrada) >=', $data_inicio);
        $this->db->where('DATE(os_checkin.data_entrada) <=', $data_fim);
        $this->db->where('os_checkin.data_saida IS NOT NULL');
        $this->db->group_by('os_checkin.usuarios_id');
        $this->db->order_by('tempo_medio_minutos', 'ASC');

        $resultados = $this->db->get()->result();

        // Converte minutos para horas
        foreach ($resultados as $resultado) {
            $resultado->tempo_medio_horas = round($resultado->tempo_medio_minutos / 60, 2);
        }

        return $resultados;
    }

    /**
     * Obtém ranking de técnicos
     *
     * @param string $data_inicio Data inicial
     * @param string $data_fim Data final
     * @return array Lista com ranking
     */
    public function getRankingTecnicos($data_inicio, $data_fim)
    {
        $this->db->select('
            usuarios.nome as tecnico,
            COUNT(*) as total_atendimentos,
            SUM(CASE WHEN data_saida IS NOT NULL THEN 1 ELSE 0 END) as atendimentos_finalizados,
            AVG(TIMESTAMPDIFF(MINUTE, data_entrada, data_saida)) as tempo_medio_minutos
        ');
        $this->db->from('os_checkin');
        $this->db->join('usuarios', 'usuarios.idUsuarios = os_checkin.usuarios_id');
        $this->db->where('DATE(os_checkin.data_entrada) >=', $data_inicio);
        $this->db->where('DATE(os_checkin.data_entrada) <=', $data_fim);
        $this->db->group_by('os_checkin.usuarios_id');
        $this->db->order_by('total_atendimentos', 'DESC');

        $resultados = $this->db->get()->result();

        // Adiciona taxa de conclusão e converte tempo
        foreach ($resultados as $key => $resultado) {
            $resultado->rank = $key + 1;
            $resultado->taxa_conclusao = $resultado->total_atendimentos > 0
                ? round(($resultado->atendimentos_finalizados / $resultado->total_atendimentos) * 100, 2)
                : 0;
            $resultado->tempo_medio_horas = $resultado->tempo_medio_minutos
                ? round($resultado->tempo_medio_minutos / 60, 2)
                : 0;
        }

        return $resultados;
    }

    /**
     * Obtém estatísticas mensais para comparativo
     *
     * @param int $meses Número de meses para análise
     * @return array Estatísticas por mês
     */
    public function getEstatisticasMensais($meses = 6)
    {
        $this->db->select('
            DATE_FORMAT(data_entrada, "%Y-%m") as mes,
            DATE_FORMAT(data_entrada, "%m/%Y") as mes_formatado,
            COUNT(*) as total_atendimentos,
            SUM(CASE WHEN data_saida IS NOT NULL THEN 1 ELSE 0 END) as finalizados
        ');
        $this->db->from('os_checkin');
        $this->db->where('data_entrada >=', date('Y-m-01', strtotime("-$meses months")));
        $this->db->group_by('DATE_FORMAT(data_entrada, "%Y-%m")');
        $this->db->order_by('mes', 'ASC');

        return $this->db->get()->result();
    }

    /**
     * Obtém detalhes de um atendimento específico
     *
     * @param int $checkin_id ID do checkin
     * @return object|null Dados do atendimento
     */
    public function getDetalhesAtendimento($checkin_id)
    {
        $this->db->select('os_checkin.*, usuarios.nome as nome_tecnico, usuarios.telefone as telefone_tecnico');
        $this->db->from('os_checkin');
        $this->db->join('usuarios', 'usuarios.idUsuarios = os_checkin.usuarios_id');
        $this->db->where('os_checkin.idCheckin', $checkin_id);

        return $this->db->get()->row();
    }
}
