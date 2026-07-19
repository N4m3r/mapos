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
