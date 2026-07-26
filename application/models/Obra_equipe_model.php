<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Equipes de obra: cadastro da equipe, membros e alocação às obras.
 * Resiliente a tabelas ausentes (migration não aplicada).
 */
class Obra_equipe_model extends CI_Model
{
    public function disponivel()
    {
        return $this->db->table_exists('equipes');
    }

    /* ============================ Equipes ============================ */

    public function getEquipes($somenteAtivas = false)
    {
        if (! $this->db->table_exists('equipes')) {
            return [];
        }
        $this->db->select('equipes.*, usuarios.nome as encarregado,
            (SELECT COUNT(*) FROM equipe_membros WHERE equipe_membros.equipe_id = equipes.idEquipe) as total_membros', false);
        $this->db->from('equipes');
        $this->db->join('usuarios', 'usuarios.idUsuarios = equipes.encarregado_id', 'left');
        if ($somenteAtivas) {
            $this->db->where('equipes.ativo', 1);
        }
        $this->db->order_by('equipes.nome', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Seletor enxuto de equipes ativas para telas fora do módulo de Projetos
     * (ex.: atribuição de equipe na OS). Resiliente: não depende de
     * `equipe_membros` e só inclui `tipo` se a coluna existir.
     */
    public function getEquipesSelect()
    {
        if (! $this->db->table_exists('equipes')) {
            return [];
        }
        $cols = 'idEquipe, nome';
        if ($this->db->field_exists('tipo', 'equipes')) {
            $cols .= ', tipo';
        }
        return $this->db->select($cols)
            ->where('ativo', 1)
            ->order_by('nome', 'ASC')
            ->get('equipes')->result();
    }

    public function getEquipe($id)
    {
        if (! $this->db->table_exists('equipes')) {
            return null;
        }
        return $this->db->where('idEquipe', (int) $id)->get('equipes')->row();
    }

    public function add($data)
    {
        $this->db->insert('equipes', $data);
        return $this->db->insert_id();
    }

    public function edit($id, $data)
    {
        $this->db->where('idEquipe', (int) $id)->update('equipes', $data);
        return true;
    }

    public function delete($id)
    {
        $id = (int) $id;
        if ($this->db->table_exists('equipe_membros')) {
            $this->db->where('equipe_id', $id)->delete('equipe_membros');
        }
        if ($this->db->table_exists('obra_equipe_alocacao')) {
            $this->db->where('equipe_id', $id)->delete('obra_equipe_alocacao');
        }
        $this->db->where('idEquipe', $id)->delete('equipes');
        return true;
    }

    /* ============================ Membros ============================ */

    public function getMembros($equipe_id)
    {
        if (! $this->db->table_exists('equipe_membros')) {
            return [];
        }
        $this->db->select('equipe_membros.*, usuarios.nome as nome_usuario');
        $this->db->from('equipe_membros');
        $this->db->join('usuarios', 'usuarios.idUsuarios = equipe_membros.colaborador_id', 'left');
        $this->db->where('equipe_id', (int) $equipe_id);
        $this->db->order_by('equipe_membros.idMembro', 'ASC');
        return $this->db->get()->result();
    }

    public function addMembro($data)
    {
        $this->db->insert('equipe_membros', $data);
        return $this->db->insert_id();
    }

    public function deleteMembro($id)
    {
        $this->db->where('idMembro', (int) $id)->delete('equipe_membros');
        return true;
    }

    /* ============================ Alocação =========================== */

    /** Obras onde a equipe está (ou esteve) alocada. */
    public function getAlocacoes($equipe_id)
    {
        if (! $this->db->table_exists('obra_equipe_alocacao')) {
            return [];
        }
        $this->db->select('a.*, obras.nome as obra_nome, obras.status as obra_status');
        $this->db->from('obra_equipe_alocacao a');
        $this->db->join('obras', 'obras.idObra = a.obra_id', 'left');
        $this->db->where('a.equipe_id', (int) $equipe_id);
        $this->db->order_by('a.idAlocacao', 'DESC');
        return $this->db->get()->result();
    }

    public function alocar($obra_id, $equipe_id, $data_inicio = null, $data_fim = null)
    {
        if (! $this->db->table_exists('obra_equipe_alocacao')) {
            return false;
        }
        $this->db->insert('obra_equipe_alocacao', [
            'obra_id' => (int) $obra_id,
            'equipe_id' => (int) $equipe_id,
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'ativo' => 1,
        ]);
        return $this->db->insert_id();
    }

    public function desalocar($idAlocacao)
    {
        $this->db->where('idAlocacao', (int) $idAlocacao)->delete('obra_equipe_alocacao');
        return true;
    }

    /** Lista de obras para o seletor de alocação. */
    public function getObrasSelect()
    {
        if (! $this->db->table_exists('obras')) {
            return [];
        }
        $this->db->select('idObra, nome');
        $this->db->where_in('status', ['planejamento', 'em_execucao', 'paralisada']);
        $this->db->order_by('nome', 'ASC');
        return $this->db->get('obras')->result();
    }
}
