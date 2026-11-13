<?php

defined('BASEPATH') or exit('No direct script access allowed');

/** Observações do técnico sobre a atividade realizada. Resiliente se a tabela não existir. */
class Os_observacoes_tecnico_model extends CI_Model
{
    public function disponivel()
    {
        return $this->db->table_exists('os_observacoes_tecnico');
    }

    public function adicionar($os_id, $usuarios_id, $texto, $checkin_id = null, $data_hora = null)
    {
        if (!$this->disponivel()) {
            return false;
        }
        $ok = $this->db->insert('os_observacoes_tecnico', [
            'os_id' => (int) $os_id,
            'checkin_id' => $checkin_id ? (int) $checkin_id : null,
            'usuarios_id' => (int) $usuarios_id,
            'texto' => $texto,
            'data_hora' => $data_hora ?: date('Y-m-d H:i:s'),
        ]);

        return $ok ? $this->db->insert_id() : false;
    }

    public function listarPorOs($os_id)
    {
        if (!$this->disponivel()) {
            return [];
        }
        $this->db->select('o.*, u.nome as nome_usuario');
        $this->db->from('os_observacoes_tecnico o');
        $this->db->join('usuarios u', 'u.idUsuarios = o.usuarios_id', 'left');
        $this->db->where('o.os_id', (int) $os_id);
        $this->db->order_by('o.data_hora', 'ASC');
        $this->db->order_by('o.id', 'ASC');
        $q = $this->db->get();

        return $q ? $q->result() : [];
    }
}
