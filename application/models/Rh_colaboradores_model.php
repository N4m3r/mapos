<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model do cadastro de RH: colaboradores, unidades, jornadas e biometria facial.
 *
 * Segue o padrão dos demais models do Mapos: query builder direto e guarda
 * `suportado()` (table_exists) para não quebrar em bases sem as migrations.
 */
class Rh_colaboradores_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /** As tabelas de RH existem? (migrations aplicadas) */
    public function suportado()
    {
        return $this->db->table_exists('rh_colaboradores');
    }

    // =====================================================================
    // Colaboradores
    // =====================================================================

    public function listar($filtros = [])
    {
        if (! $this->suportado()) {
            return [];
        }

        $this->db->select('c.*, u.nome AS nome_unidade, j.nome AS nome_jornada');
        $this->db->from('rh_colaboradores c');
        $this->db->join('rh_unidades u', 'u.id = c.unidade_id', 'left');
        $this->db->join('rh_jornadas j', 'j.id = c.jornada_id', 'left');

        if (isset($filtros['situacao']) && $filtros['situacao'] !== '') {
            $this->db->where('c.situacao', $filtros['situacao']);
        }
        if (! empty($filtros['busca'])) {
            $this->db->group_start()
                ->like('c.nome', $filtros['busca'])
                ->or_like('c.cpf', $filtros['busca'])
                ->or_like('c.cargo', $filtros['busca'])
                ->group_end();
        }
        if (! empty($filtros['unidade_id'])) {
            $this->db->where('c.unidade_id', $filtros['unidade_id']);
        }

        $this->db->order_by('c.nome', 'ASC');
        $query = $this->db->get();

        return $query ? $query->result() : [];
    }

    public function getById($id)
    {
        if (! $this->suportado()) {
            return null;
        }
        $query = $this->db->get_where('rh_colaboradores', ['id' => $id]);
        return $query ? $query->row() : null;
    }

    /** Colaborador vinculado a um usuário do sistema (login). */
    public function getByUsuario($usuarios_id)
    {
        if (! $this->suportado() || empty($usuarios_id)) {
            return null;
        }
        $query = $this->db->get_where('rh_colaboradores', [
            'usuarios_id' => $usuarios_id,
            'situacao' => 1,
        ]);
        return $query ? $query->row() : null;
    }

    /** Casa um número de celular (WhatsApp) a um colaborador ativo. */
    public function getByCelular($celular)
    {
        if (! $this->suportado() || empty($celular)) {
            return null;
        }
        // Compara só os dígitos, ignorando máscara/DDI
        $digitos = preg_replace('/\D/', '', $celular);
        $sufixo = substr($digitos, -8); // últimos 8 dígitos (número sem DDD/DDI)
        if (strlen($sufixo) < 8) {
            return null;
        }
        $this->db->where('situacao', 1);
        $this->db->where("REPLACE(REPLACE(REPLACE(REPLACE(celular,'(',''),')',''),'-',''),' ','') LIKE", '%' . $sufixo);
        $query = $this->db->get('rh_colaboradores', 1);
        return $query ? $query->row() : null;
    }

    public function add($data, $returnId = false)
    {
        if (! $this->suportado()) {
            return false;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('rh_colaboradores', $data);
        if ($this->db->affected_rows() > 0) {
            return $returnId ? $this->db->insert_id() : true;
        }
        return false;
    }

    public function edit($data, $id)
    {
        if (! $this->suportado()) {
            return false;
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('rh_colaboradores', $data);
    }

    public function delete($id)
    {
        if (! $this->suportado()) {
            return false;
        }
        $this->db->where('id', $id);
        return $this->db->delete('rh_colaboradores');
    }

    public function contarAtivos()
    {
        if (! $this->suportado()) {
            return 0;
        }
        return $this->db->where('situacao', 1)->count_all_results('rh_colaboradores');
    }

    /** Aniversariantes do mês (para o dashboard). */
    public function aniversariantesDoMes($mes = null)
    {
        if (! $this->suportado()) {
            return [];
        }
        $mes = $mes ?: date('m');
        $this->db->where('situacao', 1);
        $this->db->where('data_nascimento IS NOT NULL');
        $this->db->where('MONTH(data_nascimento)', (int) $mes);
        $this->db->order_by('DAY(data_nascimento)', 'ASC');
        $query = $this->db->get('rh_colaboradores');
        return $query ? $query->result() : [];
    }

    // =====================================================================
    // Unidades
    // =====================================================================

    public function listarUnidades($somenteAtivas = false)
    {
        if (! $this->db->table_exists('rh_unidades')) {
            return [];
        }
        if ($somenteAtivas) {
            $this->db->where('situacao', 1);
        }
        $this->db->order_by('nome', 'ASC');
        $query = $this->db->get('rh_unidades');
        return $query ? $query->result() : [];
    }

    public function getUnidade($id)
    {
        if (! $this->db->table_exists('rh_unidades')) {
            return null;
        }
        $query = $this->db->get_where('rh_unidades', ['id' => $id]);
        return $query ? $query->row() : null;
    }

    public function addUnidade($data, $returnId = false)
    {
        if (! $this->db->table_exists('rh_unidades')) {
            return false;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('rh_unidades', $data);
        if ($this->db->affected_rows() > 0) {
            return $returnId ? $this->db->insert_id() : true;
        }
        return false;
    }

    public function editUnidade($data, $id)
    {
        if (! $this->db->table_exists('rh_unidades')) {
            return false;
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('rh_unidades', $data);
    }

    public function deleteUnidade($id)
    {
        if (! $this->db->table_exists('rh_unidades')) {
            return false;
        }
        $this->db->where('id', $id);
        return $this->db->delete('rh_unidades');
    }

    // =====================================================================
    // Jornadas
    // =====================================================================

    public function listarJornadas($somenteAtivas = false)
    {
        if (! $this->db->table_exists('rh_jornadas')) {
            return [];
        }
        if ($somenteAtivas) {
            $this->db->where('situacao', 1);
        }
        $this->db->order_by('nome', 'ASC');
        $query = $this->db->get('rh_jornadas');
        return $query ? $query->result() : [];
    }

    public function getJornada($id)
    {
        if (! $this->db->table_exists('rh_jornadas')) {
            return null;
        }
        $query = $this->db->get_where('rh_jornadas', ['id' => $id]);
        return $query ? $query->row() : null;
    }

    public function addJornada($data, $returnId = false)
    {
        if (! $this->db->table_exists('rh_jornadas')) {
            return false;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('rh_jornadas', $data);
        if ($this->db->affected_rows() > 0) {
            return $returnId ? $this->db->insert_id() : true;
        }
        return false;
    }

    public function editJornada($data, $id)
    {
        if (! $this->db->table_exists('rh_jornadas')) {
            return false;
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('rh_jornadas', $data);
    }

    public function deleteJornada($id)
    {
        if (! $this->db->table_exists('rh_jornadas')) {
            return false;
        }
        $this->db->where('id', $id);
        return $this->db->delete('rh_jornadas');
    }

    // =====================================================================
    // Biometria facial (descriptor de referência)
    // =====================================================================

    public function getBiometria($colaborador_id)
    {
        if (! $this->db->table_exists('rh_face_biometria')) {
            return null;
        }
        $this->db->where('colaborador_id', $colaborador_id);
        $this->db->where('situacao', 1);
        $this->db->order_by('id', 'DESC');
        $query = $this->db->get('rh_face_biometria', 1);
        return $query ? $query->row() : null;
    }

    /** Salva/atualiza o descriptor facial (desativa os anteriores). */
    public function salvarBiometria($colaborador_id, $descriptor, $foto_ref = null, $foto_mime = null, $modelo = null)
    {
        if (! $this->db->table_exists('rh_face_biometria')) {
            return false;
        }
        // Desativa biometrias anteriores do colaborador
        $this->db->where('colaborador_id', $colaborador_id)
                 ->update('rh_face_biometria', ['situacao' => 0, 'updated_at' => date('Y-m-d H:i:s')]);

        $this->db->insert('rh_face_biometria', [
            'colaborador_id' => $colaborador_id,
            'descriptor' => $descriptor,
            'foto_ref' => $foto_ref,
            'foto_mime' => $foto_mime,
            'modelo' => $modelo,
            'situacao' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->affected_rows() > 0;
    }

    public function temBiometria($colaborador_id)
    {
        if (! $this->db->table_exists('rh_face_biometria')) {
            return false;
        }
        return $this->db->where('colaborador_id', $colaborador_id)
                        ->where('situacao', 1)
                        ->count_all_results('rh_face_biometria') > 0;
    }
}
