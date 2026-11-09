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

    /** Slug a partir do nome, sem acento e único na tabela. */
    public static function slugBase($nome)
    {
        $base = strtolower(trim((string) $nome));
        $base = strtr($base, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ï' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ú' => 'u', 'ü' => 'u', 'ç' => 'c',
            'Á' => 'a', 'À' => 'a', 'Â' => 'a', 'Ã' => 'a', 'É' => 'e', 'Ê' => 'e', 'Í' => 'i', 'Ó' => 'o',
            'Ô' => 'o', 'Õ' => 'o', 'Ú' => 'u', 'Ç' => 'c',
        ]);
        $base = trim(preg_replace('/[^a-z0-9]+/', '_', $base), '_');

        return $base !== '' ? $base : 'modelo';
    }

    public function gerarSlug($nome)
    {
        $base = self::slugBase($nome);
        $slug = $base;
        $i = 2;
        while ($this->getBySlug($slug)) {
            $slug = $base . '_' . $i;
            $i++;
        }

        return $slug;
    }

    public function create(array $data)
    {
        if (! $this->db->table_exists($this->table)) {
            return false;
        }

        $data['data_criacao'] = date('Y-m-d H:i:s');
        $data['data_atualizacao'] = $data['data_criacao'];
        $this->db->insert($this->table, $data);

        return $this->db->insert_id();
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
