<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Cobrancas_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($table, $fields, $where = '', $perpage = 0, $start = 0, $one = false, $array = 'array')
    {
        $this->db->select($fields, 'vendas.*,os.*');
        $this->db->from($table);
        $this->db->limit($perpage, $start);
        $this->db->order_by('idCobranca', 'desc');
        if ($where) {
            $this->db->where($where);
        }

        $query = $this->db->get();
        $result = ! $one ? $query->result() : $query->row();

        return $result;
    }

    public function getById($id)
    {
        $this->db->select('cobrancas.*, clientes.*');
        $this->db->from('cobrancas');
        $this->db->where('cobrancas.idCobranca', $id);
        $this->db->join('clientes', 'clientes.idClientes = cobrancas.clientes_id');
        $this->db->limit(1);

        return $this->db->get()->row();
    }

    public function getByOs($id)
    {
        return $this->db->query("SELECT DISTINCT `cobrancas`.*,`clientes`.*,`os`.* FROM `cobrancas`,`clientes`,`os` WHERE `charge_id` = $id AND `os`.`idOs` = `cobrancas`.`os_id`")->row();
    }

    public function getByVendas($id)
    {
        return $this->db->query("SELECT DISTINCT `cobrancas`.*,`clientes`.*,`vendas`.* FROM `cobrancas`,`clientes`,`vendas` WHERE `charge_id` = $id AND `vendas`.`idVendas` = `cobrancas`.`vendas_id`")->row();
    }

    /**
     * Cobrança ativa (não cancelada) vinculada a uma nota fiscal.
     * Usada para bloquear boleto duplicado e exibir o boleto na OS.
     */
    public function getByNota($idNota)
    {
        return $this->db
            ->where('nota_id', $idNota)
            ->where_not_in('status', ['CANCELLED', 'cancelada'])
            ->order_by('idCobranca', 'DESC')
            ->limit(1)
            ->get('cobrancas')
            ->row();
    }

    /**
     * Mapa de cobranças indexado por nota_id, para a aba de notas da OS.
     */
    public function getByNotaIds(array $ids)
    {
        $mapa = [];
        if (empty($ids)) {
            return $mapa;
        }
        $rows = $this->db
            ->where_in('nota_id', $ids)
            ->order_by('idCobranca', 'ASC')
            ->get('cobrancas')
            ->result();
        foreach ($rows as $row) {
            // A última cobrança (maior id) prevalece por nota.
            $mapa[$row->nota_id] = $row;
        }

        return $mapa;
    }

    public function add($table, $data, $returnId = false)
    {
        $this->db->insert($table, $data);
        if ($this->db->affected_rows() == '1') {
            if ($returnId == true) {
                return $this->db->insert_id($table);
            }

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

    public function atualizarStatus($idCobranca)
    {
        $cobranca = $this->getById($idCobranca);
        if (empty($cobranca)) {
            return $this->session->set_flashdata('error', 'Cobrança não existe!');
        }

        $gatewayDePagamento = $cobranca->payment_gateway;
        $this->load->library("Gateways/$gatewayDePagamento", null, 'PaymentGateway');

        $result = $this->PaymentGateway->atualizarDados($cobranca->idCobranca);

        return $result;
    }

    public function confirmarPagamento($idCobranca)
    {
        $cobranca = $this->getById($idCobranca);
        if (empty($cobranca)) {
            return $this->session->set_flashdata('error', 'Cobrança não existe!');
        }

        $gatewayDePagamento = $cobranca->payment_gateway;
        $this->load->library("Gateways/$gatewayDePagamento", null, 'PaymentGateway');

        $result = $this->PaymentGateway->confirmarPagamento($cobranca->idCobranca);

        return $result;
    }

    public function cancelarPagamento($idCobranca)
    {
        $cobranca = $this->getById($idCobranca);
        if (empty($cobranca)) {
            return $this->session->set_flashdata('error', 'Cobrança não existe!');
        }

        $gatewayDePagamento = $cobranca->payment_gateway;
        $this->load->library("Gateways/$gatewayDePagamento", null, 'PaymentGateway');

        $result = $this->PaymentGateway->cancelar($cobranca->idCobranca);

        return $result;
    }

    public function enviarEmail($idCobranca)
    {
        $cobranca = $this->getById($idCobranca);
        if (empty($cobranca)) {
            return $this->session->set_flashdata('error', 'Cobrança não existe!');
        }

        $gatewayDePagamento = $cobranca->payment_gateway;
        $this->load->library("Gateways/$gatewayDePagamento", null, 'PaymentGateway');

        $result = $this->PaymentGateway->enviarPorEmail($cobranca->idCobranca);

        return $result;
    }
}
