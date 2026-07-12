<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model dos extras de RH: lançamentos financeiros, consolidação de horas,
 * ocorrências (correções/justificativas) e ausências (férias/folga/atestado).
 */
class Rh_extras_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function suportado()
    {
        return $this->db->table_exists('rh_lancamentos');
    }

    // =====================================================================
    // Lançamentos financeiros (proventos/descontos por competência)
    // =====================================================================

    public function listarLancamentos($colaborador_id = null, $competencia = null)
    {
        if (! $this->suportado()) {
            return [];
        }
        $this->db->select('l.*, c.nome AS nome_colaborador');
        $this->db->from('rh_lancamentos l');
        $this->db->join('rh_colaboradores c', 'c.id = l.colaborador_id', 'left');
        if ($colaborador_id) {
            $this->db->where('l.colaborador_id', $colaborador_id);
        }
        if ($competencia) {
            $this->db->where('l.competencia', $competencia);
        }
        $this->db->order_by('l.created_at', 'DESC');
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    public function getLancamento($id)
    {
        if (! $this->suportado()) {
            return null;
        }
        $query = $this->db->get_where('rh_lancamentos', ['id' => $id]);
        return $query ? $query->row() : null;
    }

    public function addLancamento($data, $returnId = false)
    {
        if (! $this->suportado()) {
            return false;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('rh_lancamentos', $data);
        if ($this->db->affected_rows() > 0) {
            return $returnId ? $this->db->insert_id() : true;
        }
        return false;
    }

    public function editLancamento($data, $id)
    {
        if (! $this->suportado()) {
            return false;
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('rh_lancamentos', $data);
    }

    public function deleteLancamento($id)
    {
        if (! $this->suportado()) {
            return false;
        }
        $this->db->where('id', $id);
        return $this->db->delete('rh_lancamentos');
    }

    /**
     * Resumo de proventos/descontos de uma competência (para o holerite/demonstrativo).
     * Considera apenas lançamentos aprovados.
     */
    public function resumoCompetencia($colaborador_id, $competencia)
    {
        $resumo = ['proventos' => 0.0, 'descontos' => 0.0, 'liquido' => 0.0, 'itens' => []];
        if (! $this->suportado()) {
            return $resumo;
        }
        $this->db->where('colaborador_id', $colaborador_id);
        $this->db->where('competencia', $competencia);
        $this->db->where('aprovado', 1);
        $this->db->order_by('natureza', 'ASC');
        $query = $this->db->get('rh_lancamentos');
        $itens = $query ? $query->result() : [];

        foreach ($itens as $item) {
            if ($item->natureza === 'desconto') {
                $resumo['descontos'] += (float) $item->valor;
            } else {
                $resumo['proventos'] += (float) $item->valor;
            }
        }
        $resumo['liquido'] = $resumo['proventos'] - $resumo['descontos'];
        $resumo['itens'] = $itens;
        return $resumo;
    }

    // =====================================================================
    // Consolidação de horas (rh_horas)
    // =====================================================================

    public function getHoras($colaborador_id, $competencia)
    {
        if (! $this->db->table_exists('rh_horas')) {
            return null;
        }
        $query = $this->db->get_where('rh_horas', [
            'colaborador_id' => $colaborador_id,
            'competencia' => $competencia,
        ]);
        return $query ? $query->row() : null;
    }

    /** Insere ou atualiza a consolidação de horas da competência. */
    public function salvarHoras($colaborador_id, $competencia, $dados)
    {
        if (! $this->db->table_exists('rh_horas')) {
            return false;
        }
        $existente = $this->getHoras($colaborador_id, $competencia);
        if ($existente) {
            if (! empty($existente->fechado)) {
                return false; // competência fechada não é reprocessada
            }
            $dados['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', $existente->id);
            return $this->db->update('rh_horas', $dados);
        }
        $dados['colaborador_id'] = $colaborador_id;
        $dados['competencia'] = $competencia;
        $dados['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('rh_horas', $dados);
        return $this->db->affected_rows() > 0;
    }

    public function fecharCompetencia($colaborador_id, $competencia)
    {
        if (! $this->db->table_exists('rh_horas')) {
            return false;
        }
        $this->db->where('colaborador_id', $colaborador_id);
        $this->db->where('competencia', $competencia);
        return $this->db->update('rh_horas', [
            'fechado' => 1,
            'data_fechamento' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // =====================================================================
    // Ocorrências (correção de ponto / justificativa) — com aprovação
    // =====================================================================

    public function listarOcorrencias($filtros = [])
    {
        if (! $this->db->table_exists('rh_ocorrencias')) {
            return [];
        }
        $this->db->select('o.*, c.nome AS nome_colaborador');
        $this->db->from('rh_ocorrencias o');
        $this->db->join('rh_colaboradores c', 'c.id = o.colaborador_id', 'left');
        if (! empty($filtros['colaborador_id'])) {
            $this->db->where('o.colaborador_id', $filtros['colaborador_id']);
        }
        if (! empty($filtros['status'])) {
            $this->db->where('o.status', $filtros['status']);
        }
        $this->db->order_by('o.created_at', 'DESC');
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    public function getOcorrencia($id)
    {
        if (! $this->db->table_exists('rh_ocorrencias')) {
            return null;
        }
        $query = $this->db->get_where('rh_ocorrencias', ['id' => $id]);
        return $query ? $query->row() : null;
    }

    public function addOcorrencia($data, $returnId = false)
    {
        if (! $this->db->table_exists('rh_ocorrencias')) {
            return false;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('rh_ocorrencias', $data);
        if ($this->db->affected_rows() > 0) {
            return $returnId ? $this->db->insert_id() : true;
        }
        return false;
    }

    public function analisarOcorrencia($id, $status, $aprovador_id, $resposta = null)
    {
        if (! $this->db->table_exists('rh_ocorrencias')) {
            return false;
        }
        $this->db->where('id', $id);
        return $this->db->update('rh_ocorrencias', [
            'status' => $status,
            'aprovador_id' => $aprovador_id,
            'resposta' => $resposta,
            'data_analise' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // =====================================================================
    // Ausências (férias/folga/atestado/licença) — com aprovação
    // =====================================================================

    public function listarAusencias($filtros = [])
    {
        if (! $this->db->table_exists('rh_ausencias')) {
            return [];
        }
        $this->db->select('a.*, c.nome AS nome_colaborador');
        $this->db->from('rh_ausencias a');
        $this->db->join('rh_colaboradores c', 'c.id = a.colaborador_id', 'left');
        if (! empty($filtros['colaborador_id'])) {
            $this->db->where('a.colaborador_id', $filtros['colaborador_id']);
        }
        if (! empty($filtros['status'])) {
            $this->db->where('a.status', $filtros['status']);
        }
        $this->db->order_by('a.data_inicio', 'DESC');
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    public function getAusencia($id)
    {
        if (! $this->db->table_exists('rh_ausencias')) {
            return null;
        }
        $query = $this->db->get_where('rh_ausencias', ['id' => $id]);
        return $query ? $query->row() : null;
    }

    public function addAusencia($data, $returnId = false)
    {
        if (! $this->db->table_exists('rh_ausencias')) {
            return false;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('rh_ausencias', $data);
        if ($this->db->affected_rows() > 0) {
            return $returnId ? $this->db->insert_id() : true;
        }
        return false;
    }

    public function analisarAusencia($id, $status, $aprovador_id, $resposta = null)
    {
        if (! $this->db->table_exists('rh_ausencias')) {
            return false;
        }
        $this->db->where('id', $id);
        return $this->db->update('rh_ausencias', [
            'status' => $status,
            'aprovador_id' => $aprovador_id,
            'resposta' => $resposta,
            'data_analise' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // =====================================================================
    // Holerites (PDF oficial por colaborador/competência)
    // =====================================================================

    public function getHolerite($colaborador_id, $competencia)
    {
        if (! $this->db->table_exists('rh_holerites')) {
            return null;
        }
        $query = $this->db->get_where('rh_holerites', [
            'colaborador_id' => $colaborador_id,
            'competencia' => $competencia,
        ]);
        return $query ? $query->row() : null;
    }

    /** Insere ou atualiza o holerite (arquivo/valores) da competência. */
    public function salvarHolerite($colaborador_id, $competencia, $dados)
    {
        if (! $this->db->table_exists('rh_holerites')) {
            return false;
        }
        $existente = $this->getHolerite($colaborador_id, $competencia);
        if ($existente) {
            $dados['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', $existente->id);
            return $this->db->update('rh_holerites', $dados);
        }
        $dados['colaborador_id'] = $colaborador_id;
        $dados['competencia'] = $competencia;
        $dados['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('rh_holerites', $dados);
        return $this->db->affected_rows() > 0;
    }

    public function deleteHolerite($colaborador_id, $competencia)
    {
        if (! $this->db->table_exists('rh_holerites')) {
            return false;
        }
        return $this->db->where('colaborador_id', $colaborador_id)
                        ->where('competencia', $competencia)
                        ->delete('rh_holerites');
    }

    /** Pendências de aprovação (ocorrências + ausências) para o dashboard. */
    public function contarPendencias()
    {
        $total = 0;
        if ($this->db->table_exists('rh_ocorrencias')) {
            $total += $this->db->where('status', 'pendente')->count_all_results('rh_ocorrencias');
        }
        if ($this->db->table_exists('rh_ausencias')) {
            $total += $this->db->where('status', 'pendente')->count_all_results('rh_ausencias');
        }
        return $total;
    }
}
