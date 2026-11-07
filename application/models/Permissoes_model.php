<?php

class Permissoes_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($table, $fields, $where = '', $perpage = 0, $start = 0, $one = false, $array = 'array')
    {
        $this->db->select($fields);
        $this->db->from($table);
        $this->db->order_by('idPermissao', 'desc');
        $this->db->limit($perpage, $start);
        if ($where) {
            $this->db->where($where);
        }

        $query = $this->db->get();

        $result = ! $one ? $query->result() : $query->row();

        return $result;
    }

    public function getActive($table, $fields)
    {
        $this->db->select($fields);
        $this->db->from($table);
        $this->db->where('situacao', 1);
        $query = $this->db->get();

        return $query->result();

    }

    public function getById($id)
    {
        $this->db->where('idPermissao', $id);
        $this->db->limit(1);

        return $this->db->get('permissoes')->row();
    }

    /**
     * Todos os perfis, sem paginação.
     *
     * Não use get(..., perpage: 0): o query builder do CodeIgniter trata
     * zero como "LIMIT 0" e devolve lista vazia — o select de perfil da tela
     * de usuário ficaria sem opções.
     *
     * @return array<int, object>
     */
    public function todos()
    {
        $this->db->select('idPermissao,nome,situacao');
        $this->db->from('permissoes');
        $this->db->order_by('situacao', 'DESC');
        $this->db->order_by('nome', 'ASC');

        return $this->db->get()->result();
    }

    public function add($table, $data)
    {
        $this->db->insert($table, $data);
        if ($this->db->affected_rows() == '1') {
            return true;
        }

        return false;
    }

    public function edit($table, $data, $fieldID, $ID)
    {
        $this->db->where($fieldID, $ID);
        $this->db->update($table, $data);

        if ($this->db->affected_rows() >= 0) {
            return true;
        }

        return false;
    }

    public function delete($table, $fieldID, $ID)
    {
        $this->db->where($fieldID, $ID);
        $this->db->delete($table);
        if ($this->db->affected_rows() == '1') {
            return true;
        }

        return false;
    }

    public function count($table)
    {
        return $this->db->count_all($table);
    }

    /**
     * Quantos usuários ativos (situacao = 1) usam cada perfil, por idPermissao.
     *
     * A lista de perfis mostra o uso e o desativar recusa perfil em uso: sem
     * isso, desativar um perfil deixava as contas nele e a próxima edição de
     * usuário trocava o acesso sem ninguém notar.
     *
     * @return array<int, int>
     */
    public function usuariosAtivosPorPerfil()
    {
        $this->db->select('permissoes_id, COUNT(*) AS total');
        $this->db->from('usuarios');
        $this->db->where('situacao', 1);
        $this->db->group_by('permissoes_id');

        $mapa = [];
        foreach ($this->db->get()->result() as $linha) {
            $mapa[(int) $linha->permissoes_id] = (int) $linha->total;
        }

        return $mapa;
    }
}

/* End of file permissoes_model.php */
/* Location: ./application/models/permissoes_model.php */
