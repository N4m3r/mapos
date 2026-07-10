<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Log de envios de WhatsApp (Evolution API) — auditoria.
 */
class Whatsapp_envios_model extends CI_Model
{
    protected $table = 'whatsapp_envios';

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

    public function getUltimos($limite = 30, $offset = 0, $status = null)
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
