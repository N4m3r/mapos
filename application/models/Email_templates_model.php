<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Email_templates_model extends CI_Model
{
    protected $table = 'email_templates';

    public function getAll()
    {
        if (! $this->db->table_exists($this->table)) {
            return [];
        }

        return $this->db->order_by('nome', 'ASC')->get($this->table)->result();
    }

    public function getById($id)
    {
        if (! $this->db->table_exists($this->table)) {
            return null;
        }

        return $this->db->where('id', $id)->limit(1)->get($this->table)->row();
    }

    public function getBySlug($slug)
    {
        if (! $this->db->table_exists($this->table)) {
            return null;
        }

        return $this->db->where('slug', $slug)->limit(1)->get($this->table)->row();
    }

    public function update($id, array $data)
    {
        if (! $this->db->table_exists($this->table)) {
            return false;
        }

        $data['data_atualizacao'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update($this->table, $data);

        return $this->db->affected_rows() >= 0;
    }
}
