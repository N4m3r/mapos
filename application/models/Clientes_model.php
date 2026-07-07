<?php

class Clientes_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($table, $fields, $where = '', $perpage = 0, $start = 0, $one = false, $array = 'array')
    {
        $this->db->select($fields);
        $this->db->from($table);
        $this->db->order_by('idClientes', 'desc');
        $this->db->limit($perpage, $start);
        if ($where) {
            $this->db->like('nomeCliente', $where);
            $this->db->or_like('documento', $where);
            $this->db->or_like('email', $where);
            $this->db->or_like('telefone', $where);
        }

        $query = $this->db->get();

        $result = ! $one ? $query->result() : $query->row();

        return $result;
    }

    public function getById($id)
    {
        $this->db->where('idClientes', $id);
        $this->db->limit(1);

        return $this->db->get('clientes')->row();
    }

    /**
     * O recurso de vínculos multi-CNPJ só funciona se a tabela já existir.
     * Evita erro fatal em ambientes ainda não migrados (mesmo padrão de
     * Aprovacao_model::suportado()).
     */
    public function vinculosSuportado()
    {
        return $this->db->table_exists('clientes_vinculos');
    }

    /**
     * Ids dos clientes vinculados a um login principal (apenas os vínculos,
     * sem incluir o próprio master).
     *
     * @return int[]
     */
    public function getVinculos($masterId)
    {
        if (! $this->vinculosSuportado() || empty($masterId)) {
            return [];
        }

        $this->db->select('cliente_id');
        $this->db->where('cliente_master_id', $masterId);
        $rows = $this->db->get('clientes_vinculos')->result();

        return array_map(function ($r) {
            return (int) $r->cliente_id;
        }, $rows);
    }

    /**
     * Substitui o conjunto de vínculos de um login principal (delete + reinsert).
     * Ignora o próprio master e valores inválidos/duplicados.
     */
    public function setVinculos($masterId, array $clienteIds)
    {
        if (! $this->vinculosSuportado() || empty($masterId)) {
            return false;
        }

        $this->db->where('cliente_master_id', $masterId);
        $this->db->delete('clientes_vinculos');

        $limpos = [];
        foreach ($clienteIds as $cid) {
            $cid = (int) $cid;
            if ($cid > 0 && $cid != $masterId && ! in_array($cid, $limpos, true)) {
                $limpos[] = $cid;
            }
        }

        foreach ($limpos as $cid) {
            $this->db->insert('clientes_vinculos', [
                'cliente_master_id' => $masterId,
                'cliente_id' => $cid,
            ]);
        }

        return true;
    }

    /**
     * Lista de clientes (id + nome + documento) exceto o informado, para o
     * multi-select de vínculos na tela de edição.
     */
    public function getAllExceto($id)
    {
        $this->db->select('idClientes, nomeCliente, documento');
        if ($id) {
            $this->db->where('idClientes !=', $id);
        }
        $this->db->order_by('nomeCliente', 'asc');

        return $this->db->get('clientes')->result();
    }

    public function add($table, $data)
    {
        $this->db->insert($table, $data);
        if ($this->db->affected_rows() == '1') {
            return $this->db->insert_id($table);
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

    public function getOsByCliente($id)
    {
        $this->db->where('clientes_id', $id);
        $this->db->order_by('idOs', 'desc');
        $this->db->limit(10);

        return $this->db->get('os')->result();
    }

    /**
     * Retorna todas as OS vinculados ao cliente
     *
     * @param  int  $id
     * @return array
     */
    public function getAllOsByClient($id)
    {
        $this->db->where('clientes_id', $id);

        return $this->db->get('os')->result();
    }

    /**
     * Remover todas as OS por cliente
     *
     * @param  array  $os
     * @return bool
     */
    public function removeClientOs($os)
    {
        try {
            foreach ($os as $o) {
                $this->db->where('os_id', $o->idOs);
                $this->db->delete('servicos_os');

                $this->db->where('os_id', $o->idOs);
                $this->db->delete('produtos_os');

                $this->db->where('idOs', $o->idOs);
                $this->db->delete('os');
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Retorna todas as Vendas vinculados ao cliente
     *
     * @param  int  $id
     * @return array
     */
    public function getAllVendasByClient($id)
    {
        $this->db->where('clientes_id', $id);

        return $this->db->get('vendas')->result();
    }

    /**
     * Remover todas as Vendas por cliente
     *
     * @param  array  $vendas
     * @return bool
     */
    public function removeClientVendas($vendas)
    {
        try {
            foreach ($vendas as $v) {
                $this->db->where('vendas_id', $v->idVendas);
                $this->db->delete('itens_de_vendas');

                $this->db->where('idVendas', $v->idVendas);
                $this->db->delete('vendas');
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
