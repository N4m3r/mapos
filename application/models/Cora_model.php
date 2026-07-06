<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Configuração do gateway Cora (boleto híbrido + PIX). Registro único (id=1).
 */
class Cora_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getConfig()
    {
        if (! $this->db->table_exists('configuracoes_cora')) {
            return null;
        }

        return $this->db->get_where('configuracoes_cora', ['id' => 1])->row();
    }

    public function saveConfig(array $data)
    {
        $this->db->where('id', 1);

        return $this->db->update('configuracoes_cora', $data);
    }
}
