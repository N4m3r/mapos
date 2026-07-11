<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model do registro de ponto.
 *
 * Guarda cada batida (entrada/saída/intervalo) e oferece as consultas usadas
 * pela tela de ponto, pelo espelho e pelo motor de cálculo de horas.
 */
class Rh_ponto_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function suportado()
    {
        return $this->db->table_exists('rh_ponto_registros');
    }

    public function registrar($data, $returnId = false)
    {
        if (! $this->suportado()) {
            return false;
        }
        if (empty($data['data_hora'])) {
            $data['data_hora'] = date('Y-m-d H:i:s');
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('rh_ponto_registros', $data);
        if ($this->db->affected_rows() > 0) {
            return $returnId ? $this->db->insert_id() : true;
        }
        return false;
    }

    public function getById($id)
    {
        if (! $this->suportado()) {
            return null;
        }
        $query = $this->db->get_where('rh_ponto_registros', ['id' => $id]);
        return $query ? $query->row() : null;
    }

    public function edit($data, $id)
    {
        if (! $this->suportado()) {
            return false;
        }
        $this->db->where('id', $id);
        return $this->db->update('rh_ponto_registros', $data);
    }

    public function delete($id)
    {
        if (! $this->suportado()) {
            return false;
        }
        $this->db->where('id', $id);
        return $this->db->delete('rh_ponto_registros');
    }

    /** Batidas de um colaborador num período (inclusive), ordenadas no tempo. */
    public function getPorPeriodo($colaborador_id, $inicio, $fim, $incluirRejeitadas = false)
    {
        if (! $this->suportado()) {
            return [];
        }
        $this->db->where('colaborador_id', $colaborador_id);
        $this->db->where('data_hora >=', $inicio . ' 00:00:00');
        $this->db->where('data_hora <=', $fim . ' 23:59:59');
        if (! $incluirRejeitadas) {
            $this->db->where('status !=', 'rejeitado');
        }
        $this->db->order_by('data_hora', 'ASC');
        $query = $this->db->get('rh_ponto_registros');
        return $query ? $query->result() : [];
    }

    /** Batidas de um dia específico. */
    public function getDoDia($colaborador_id, $data = null)
    {
        $data = $data ?: date('Y-m-d');
        return $this->getPorPeriodo($colaborador_id, $data, $data);
    }

    /** Última batida válida do colaborador. */
    public function ultimaBatida($colaborador_id, $data = null)
    {
        if (! $this->suportado()) {
            return null;
        }
        $this->db->where('colaborador_id', $colaborador_id);
        $this->db->where('status !=', 'rejeitado');
        if ($data) {
            $this->db->where('DATE(data_hora)', $data);
        }
        $this->db->order_by('data_hora', 'DESC');
        $query = $this->db->get('rh_ponto_registros', 1);
        return $query ? $query->row() : null;
    }

    /**
     * Sugere o próximo tipo de batida com base nas batidas do dia.
     * Sequência: entrada -> inicio_intervalo -> fim_intervalo -> saida.
     */
    public function proximoTipo($colaborador_id, $data = null)
    {
        $batidas = $this->getDoDia($colaborador_id, $data);
        $tipos = array_map(function ($b) {
            return $b->tipo;
        }, $batidas);

        if (! in_array('entrada', $tipos, true)) {
            return 'entrada';
        }
        if (! in_array('inicio_intervalo', $tipos, true) && ! in_array('saida', $tipos, true)) {
            return 'inicio_intervalo';
        }
        if (in_array('inicio_intervalo', $tipos, true) && ! in_array('fim_intervalo', $tipos, true)) {
            return 'fim_intervalo';
        }
        if (! in_array('saida', $tipos, true)) {
            return 'saida';
        }
        // Dia já fechado: uma nova entrada inicia outro turno
        return 'entrada';
    }

    /**
     * Colaboradores presentes hoje (última batida é entrada ou fim_intervalo).
     * Retorna a contagem distinta de colaboradores "dentro".
     */
    public function contarPresentesHoje()
    {
        if (! $this->suportado()) {
            return 0;
        }
        $hoje = date('Y-m-d');
        $sql = "SELECT COUNT(*) AS total FROM (
                    SELECT r.colaborador_id, SUBSTRING_INDEX(GROUP_CONCAT(r.tipo ORDER BY r.data_hora DESC), ',', 1) AS ultimo
                    FROM rh_ponto_registros r
                    WHERE DATE(r.data_hora) = ? AND r.status != 'rejeitado'
                    GROUP BY r.colaborador_id
                ) t WHERE t.ultimo IN ('entrada','fim_intervalo')";
        $query = $this->db->query($sql, [$hoje]);
        $row = $query ? $query->row() : null;
        return $row ? (int) $row->total : 0;
    }

    /** Registros mais recentes de todos os colaboradores (feed do dashboard). */
    public function ultimosRegistros($limite = 15)
    {
        if (! $this->suportado()) {
            return [];
        }
        $this->db->select('r.*, c.nome AS nome_colaborador');
        $this->db->from('rh_ponto_registros r');
        $this->db->join('rh_colaboradores c', 'c.id = r.colaborador_id', 'left');
        $this->db->where('r.status !=', 'rejeitado');
        $this->db->order_by('r.data_hora', 'DESC');
        $this->db->limit($limite);
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    /** Registros pendentes de análise (ajustes solicitados). */
    public function contarPendentes()
    {
        if (! $this->suportado()) {
            return 0;
        }
        return $this->db->where('status', 'pendente')->count_all_results('rh_ponto_registros');
    }
}
