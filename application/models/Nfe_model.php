<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Nfe_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ---------------- Configurações ---------------- */

    public function getConfig()
    {
        return $this->db->get_where('configuracoes_nfe', ['id' => 1])->row();
    }

    public function saveConfig(array $data)
    {
        $this->db->where('id', 1);

        return $this->db->update('configuracoes_nfe', $data);
    }

    /**
     * Reserva o próximo número de forma atômica (evita numeração duplicada
     * quando dois usuários emitem ao mesmo tempo).
     * $campo: 'proximo_numero_nfe' ou 'proximo_numero_dps'
     */
    public function reservarNumero($campo)
    {
        if (!in_array($campo, ['proximo_numero_nfe', 'proximo_numero_dps'])) {
            return false;
        }
        $this->db->query("UPDATE configuracoes_nfe SET {$campo} = LAST_INSERT_ID({$campo}) + 1 WHERE id = 1");

        return (int) $this->db->query('SELECT LAST_INSERT_ID() as numero')->row()->numero;
    }

    /* ---------------- Notas fiscais ---------------- */

    public function addNota(array $data)
    {
        $this->db->insert('notas_fiscais', $data);

        return $this->db->insert_id();
    }

    public function updateNota($idNota, array $data)
    {
        $this->db->where('idNota', $idNota);

        return $this->db->update('notas_fiscais', $data);
    }

    public function getNotaById($idNota)
    {
        return $this->db->get_where('notas_fiscais', ['idNota' => $idNota])->row();
    }

    /**
     * Última nota não-cancelada de uma venda ou OS (para bloquear emissão duplicada).
     */
    public function getNotaAtiva($tipo, $campo, $id)
    {
        return $this->db
            ->where('tipo', $tipo)
            ->where($campo, $id)
            ->where_not_in('status', ['cancelada', 'rejeitada', 'erro'])
            ->order_by('idNota', 'DESC')
            ->get('notas_fiscais')
            ->row();
    }

    public function getNotas($porPagina = 0, $inicio = 0, $status = null)
    {
        $this->db->select('notas_fiscais.*, clientes.nomeCliente');
        $this->db->from('notas_fiscais');
        $this->db->join('vendas', 'vendas.idVendas = notas_fiscais.vendas_id', 'left');
        $this->db->join('os', 'os.idOs = notas_fiscais.os_id', 'left');
        $this->db->join('clientes', 'clientes.idClientes = COALESCE(vendas.clientes_id, os.clientes_id)', 'left', false);
        if ($status) {
            $this->db->where('notas_fiscais.status', $status);
        }
        $this->db->order_by('notas_fiscais.idNota', 'DESC');
        if ($porPagina > 0) {
            $this->db->limit($porPagina, $inicio);
        }

        return $this->db->get()->result();
    }

    public function countNotas($status = null)
    {
        if ($status) {
            $this->db->where('status', $status);
        }

        return $this->db->count_all_results('notas_fiscais');
    }
}
