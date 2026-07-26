<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model da localização em tempo real do técnico.
 *
 * Armazena o histórico de pings de GPS (tabela tecnico_localizacao). O ping mais
 * recente de cada técnico é a posição atual; a sequência de pings de um check-in
 * é o trajeto do atendimento.
 */
class Localizacao_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    private function tabelaExiste()
    {
        return $this->db->table_exists('tecnico_localizacao');
    }

    /**
     * Registra um ping de localização.
     *
     * @param array $data usuarios_id, latitude, longitude e opcionais
     * @return int|false id inserido ou false
     */
    public function registrarPing($data)
    {
        if (! $this->tabelaExiste()) {
            return false;
        }

        if (empty($data['data_hora'])) {
            $data['data_hora'] = date('Y-m-d H:i:s');
        }

        $this->db->insert('tecnico_localizacao', $data);

        return $this->db->affected_rows() >= 1 ? $this->db->insert_id() : false;
    }

    /**
     * Retorna a última posição de cada técnico que enviou um ping recente
     * (dentro de $minutos). Como os pings só ocorrem durante atendimento ativo,
     * um ping recente significa que o técnico está em campo agora.
     *
     * @param int $minutos janela de "atividade" (padrão 10 min)
     * @return array de objetos com técnico, OS e cliente
     */
    public function getUltimasPorTecnico($minutos = 10)
    {
        if (! $this->tabelaExiste()) {
            return [];
        }

        $limite = date('Y-m-d H:i:s', strtotime('-' . (int) $minutos . ' minutes'));

        // Subquery: o maior idLocalizacao (ping mais novo) por técnico dentro da janela.
        $sub = $this->db->query(
            'SELECT MAX(idLocalizacao) AS max_id
               FROM tecnico_localizacao
              WHERE data_hora >= ?
           GROUP BY usuarios_id',
            [$limite]
        );

        $ids = array_map(static function ($r) {
            return (int) $r->max_id;
        }, $sub->result());

        if (empty($ids)) {
            return [];
        }

        $this->db->select('tl.*, u.nome AS nome_tecnico, o.idOs, o.status AS os_status, c.nomeCliente, c.celular');
        $this->db->from('tecnico_localizacao tl');
        $this->db->join('usuarios u', 'u.idUsuarios = tl.usuarios_id', 'left');
        $this->db->join('os o', 'o.idOs = tl.os_id', 'left');
        $this->db->join('clientes c', 'c.idClientes = o.clientes_id', 'left');
        $this->db->where_in('tl.idLocalizacao', $ids);
        $this->db->order_by('u.nome', 'ASC');

        $query = $this->db->get();

        return $query ? $query->result() : [];
    }

    /**
     * Trajeto (ordenado) de pings de um check-in específico.
     */
    public function getTrajetoByCheckin($checkin_id)
    {
        if (! $this->tabelaExiste()) {
            return [];
        }

        $this->db->select('latitude, longitude, precisao, velocidade, data_hora');
        $this->db->from('tecnico_localizacao');
        $this->db->where('checkin_id', $checkin_id);
        $this->db->order_by('data_hora', 'ASC');

        $query = $this->db->get();

        return $query ? $query->result() : [];
    }

    /**
     * Lista os técnicos que possuem algum registro de localização (para popular
     * o seletor da tela de histórico de percurso).
     */
    public function getTecnicosComRegistro()
    {
        if (! $this->tabelaExiste()) {
            return [];
        }

        $this->db->distinct();
        $this->db->select('tl.usuarios_id, u.nome');
        $this->db->from('tecnico_localizacao tl');
        $this->db->join('usuarios u', 'u.idUsuarios = tl.usuarios_id', 'left');
        $this->db->order_by('u.nome', 'ASC');

        $query = $this->db->get();

        return $query ? $query->result() : [];
    }

    /**
     * Percurso de um técnico em um intervalo de datas/horas (pings ordenados).
     * Inclui checkin_id e os_id para permitir segmentar por atendimento.
     *
     * @param int      $usuario_id
     * @param string   $inicio datetime 'Y-m-d H:i:s'
     * @param string   $fim    datetime 'Y-m-d H:i:s'
     * @param int|null $os_id  filtra por uma OS específica (opcional)
     */
    public function getTrajetoPorPeriodo($usuario_id, $inicio, $fim, $os_id = null)
    {
        if (! $this->tabelaExiste()) {
            return [];
        }

        $this->db->select('tl.idLocalizacao, tl.checkin_id, tl.os_id, tl.latitude, tl.longitude, tl.precisao, tl.velocidade, tl.data_hora, o.status AS os_status, c.nomeCliente');
        $this->db->from('tecnico_localizacao tl');
        $this->db->join('os o', 'o.idOs = tl.os_id', 'left');
        $this->db->join('clientes c', 'c.idClientes = o.clientes_id', 'left');
        $this->db->where('tl.usuarios_id', $usuario_id);
        $this->db->where('tl.data_hora >=', $inicio);
        $this->db->where('tl.data_hora <=', $fim);
        if ($os_id) {
            $this->db->where('tl.os_id', $os_id);
        }
        $this->db->order_by('tl.data_hora', 'ASC');

        $query = $this->db->get();

        return $query ? $query->result() : [];
    }

    /**
     * OS atribuídas a um técnico cujo agendamento (ou, na falta, a dataInicial)
     * cai dentro do período informado. Usado para mostrar, ao lado do percurso,
     * quais atendimentos o técnico tinha para o dia — com endereço e coordenadas
     * (quando o local já foi aprendido em algum check-in).
     *
     * @param int    $usuario_id  técnico responsável (usuarios.idUsuarios)
     * @param string $data_ini    'Y-m-d'
     * @param string $data_fim    'Y-m-d'
     */
    public function getOsDoDiaPorTecnico($usuario_id, $data_ini, $data_fim)
    {
        if (! $this->db->table_exists('os')) {
            return [];
        }

        $usuario_id = (int) $usuario_id;
        $di = $this->db->escape($data_ini);
        $df = $this->db->escape($data_fim);

        $temAgendamento = $this->db->field_exists('data_agendamento', 'os');
        $temCoords      = $this->db->field_exists('latitude', 'os') && $this->db->field_exists('longitude', 'os');

        $cols = 'o.idOs, o.status, o.dataInicial, o.dataFinal, '
              . 'c.nomeCliente, c.rua, c.numero, c.bairro, c.cidade, c.estado, c.celular';
        if ($temAgendamento) {
            $cols .= ', o.data_agendamento';
        }
        if ($temCoords) {
            $cols .= ', o.latitude, o.longitude';
        }

        $this->db->select($cols);
        $this->db->from('os o');
        $this->db->join('clientes c', 'c.idClientes = o.clientes_id', 'left');
        $this->db->where('o.tecnico_responsavel', $usuario_id);

        if ($temAgendamento) {
            // Prioriza o agendamento; sem ele, cai no dataInicial do ciclo.
            $this->db->where(
                "( (o.data_agendamento IS NOT NULL AND DATE(o.data_agendamento) BETWEEN $di AND $df)"
                . " OR (o.data_agendamento IS NULL AND DATE(o.dataInicial) BETWEEN $di AND $df) )"
            );
            $this->db->order_by('COALESCE(o.data_agendamento, o.dataInicial)', 'ASC');
        } else {
            $this->db->where("DATE(o.dataInicial) BETWEEN $di AND $df");
            $this->db->order_by('o.dataInicial', 'ASC');
        }

        $query = $this->db->get();

        return $query ? $query->result() : [];
    }

    /**
     * Remove pings antigos (higiene da tabela). Chamável por rotina/cron.
     */
    public function limparAntigos($dias = 30)
    {
        if (! $this->tabelaExiste()) {
            return false;
        }

        $limite = date('Y-m-d H:i:s', strtotime('-' . (int) $dias . ' days'));
        $this->db->where('data_hora <', $limite);
        $this->db->delete('tecnico_localizacao');

        return $this->db->affected_rows();
    }
}
