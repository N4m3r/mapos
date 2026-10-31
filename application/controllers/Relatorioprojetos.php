<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Relatorio de acompanhamento de projetos: diario, semanal ou mensal (ADR-012).
 */
class Relatorioprojetos extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->data['menuObras'] = 'Obras';
        $this->data['obra_menu'] = 'relatorio';
    }

    public function index()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vObras')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar Projetos.');
            redirect(base_url());
        }

        $tipo = $this->input->get('tipo');
        $tipo = in_array($tipo, ['diario', 'semanal', 'mensal'], true) ? $tipo : 'semanal';

        $data = $this->input->get('data');
        $data = is_string($data) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)
            && checkdate((int) substr($data, 5, 2), (int) substr($data, 8, 2), (int) substr($data, 0, 4))
            ? $data
            : date('Y-m-d');

        $obraId = (int) $this->input->get('obra_id');
        $usuarioId = (int) $this->input->get('usuario_id');

        $filtros = [];
        if ($obraId > 0) {
            $filtros['obra_id'] = $obraId;
        }
        if ($usuarioId > 0) {
            $filtros['usuario_id'] = $usuarioId;
        }

        $this->load->model('projeto_acompanhamento_model');
        $p = Projeto_acompanhamento_model::periodo($tipo, $data);
        $relatorio = $this->projeto_acompanhamento_model->relatorio($p['inicio'], $p['fim'], $filtros);

        $obras = $this->db
            ->select('idObra, nome')
            ->order_by('nome', 'ASC')
            ->limit(2000)
            ->get('obras')
            ->result();

        $usuarios = $this->db
            ->select('idUsuarios, nome')
            ->where('situacao', 1)
            ->order_by('nome', 'ASC')
            ->limit(2000)
            ->get('usuarios')
            ->result();

        $this->data['periodo'] = $p;
        $this->data['relatorio'] = $relatorio;
        $this->data['filtro'] = [
            'tipo' => $p['tipo'],
            'data' => $data,
            'obra_id' => $obraId > 0 ? $obraId : null,
            'usuario_id' => $usuarioId > 0 ? $usuarioId : null,
        ];
        $this->data['obras'] = $obras;
        $this->data['usuarios'] = $usuarios;
        $this->data['view'] = 'relatorioprojetos/index';

        return $this->layout();
    }
}
