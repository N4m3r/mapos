<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Log de envios de e-mail (fila e testes) — auditoria.
 */
class Email_envios_model extends CI_Model
{
    protected $table = 'email_envios';

    public function suportado()
    {
        return $this->db->table_exists($this->table);
    }

    public function registrar(array $data)
    {
        if (! $this->suportado()) {
            return false;
        }

        if (empty($data['data_envio'])) {
            $data['data_envio'] = date('Y-m-d H:i:s');
        }

        $this->db->insert($this->table, $data);

        return $this->db->insert_id();
    }

    public function getUltimos($limite = 100, $offset = 0, $status = null)
    {
        if (! $this->suportado()) {
            return [];
        }

        if ($status) {
            $this->db->where('status', $status);
        }
        $this->db->order_by('id', 'DESC');
        $this->db->limit($limite, $offset);

        return $this->db->get($this->table)->result();
    }

    public function getById($id)
    {
        if (! $this->suportado()) {
            return null;
        }

        return $this->db->where('id', (int) $id)->limit(1)->get($this->table)->row();
    }

    public function count($status = null)
    {
        if (! $this->suportado()) {
            return 0;
        }

        if ($status) {
            $this->db->where('status', $status);
        }

        return $this->db->count_all_results($this->table);
    }
}
