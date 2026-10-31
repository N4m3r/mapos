<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Relatorio de OS realizadas: diario, semanal ou mensal.
 */
class Relatorioos extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->data['menuOs'] = 'OS';
    }

    public function index()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'rOs')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para gerar relatórios de OS.');
            redirect(base_url());
        }

        $tipo = $this->input->get('tipo');
        $tipo = in_array($tipo, ['diario', 'semanal', 'mensal'], true) ? $tipo : 'semanal';

        $data = $this->input->get('data');
        $data = is_string($data) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)
            && checkdate((int) substr($data, 5, 2), (int) substr($data, 8, 2), (int) substr($data, 0, 4))
            ? $data
            : date('Y-m-d');

        $usuarioId = (int) $this->input->get('usuario_id');
        $clienteId = (int) $this->input->get('cliente_id');
        $status = $this->input->get('status');
        $status = is_string($status) ? trim($status) : '';
        if (mb_strlen($status) > 60) {
            $status = '';
        }

        $filtros = [];
        if ($usuarioId > 0) {
            $filtros['usuario_id'] = $usuarioId;
        }
        if ($clienteId > 0) {
            $filtros['cliente_id'] = $clienteId;
        }
        if ($status !== '') {
            $filtros['status'] = $status;
        }

        $this->load->model('relatorio_os_model');
        $p = Relatorio_os_model::periodo($tipo, $data);
        $relatorio = $this->relatorio_os_model->relatorio($p['inicio'], $p['fim'], $filtros);

        $fotosIn = $this->input->get('fotos');
        if ($fotosIn === 'todas') {
            $limiteFotos = null;
        } elseif ($fotosIn === '0') {
            $limiteFotos = 0;
        } else {
            $limiteFotos = 12;
        }
        $fotosModo = $limiteFotos === null ? 'todas' : ($limiteFotos === 0 ? '0' : (string) $limiteFotos);

        $fotosPeriodo = [];
        if ($limiteFotos !== 0) {
            try {
                $fotosPeriodo = $this->relatorio_os_model->fotosPeriodo($p['inicio'], $p['fim'], $filtros, $limiteFotos);
            } catch (Throwable $e) {
                log_message('error', 'Relatorioos fotos: ' . $e->getMessage());
                $fotosPeriodo = [];
            }
        }

        try {
            $this->data['dashboard'] = $this->relatorio_os_model->dashboardPeriodo($p['inicio'], $p['fim'], $filtros);
        } catch (Throwable $e) {
            log_message('error', 'Relatorioos dashboard: ' . $e->getMessage());
            $this->data['dashboard'] = [];
        }

        $usuarios = $this->db
            ->select('idUsuarios, nome')
            ->where('situacao', 1)
            ->order_by('nome', 'ASC')
            ->limit(2000)
            ->get('usuarios')
            ->result();

        $clientes = $this->db
            ->select('idClientes, nomeCliente')
            ->order_by('nomeCliente', 'ASC')
            ->limit(2000)
            ->get('clientes')
            ->result();

        $statusLista = [];
        foreach ($this->db->distinct()->select('status')->where('status IS NOT NULL', null, false)->order_by('status', 'ASC')->get('os')->result() as $s) {
            if (trim((string) $s->status) !== '') {
                $statusLista[] = $s->status;
            }
        }

        $this->data['periodo'] = $p;
        $this->data['relatorio'] = $relatorio;
        $this->data['filtro'] = [
            'tipo' => $p['tipo'],
            'data' => $data,
            'usuario_id' => $usuarioId > 0 ? $usuarioId : null,
            'cliente_id' => $clienteId > 0 ? $clienteId : null,
            'status' => $status !== '' ? $status : null,
        ];
        $this->data['fotosPeriodo'] = $fotosPeriodo;
        $this->data['fotosModo'] = $fotosModo;
        $this->data['usuarios'] = $usuarios;
        $this->data['clientes'] = $clientes;
        $this->data['statusLista'] = $statusLista;
        $this->data['view'] = 'relatorioos/index';

        return $this->layout();
    }
}
