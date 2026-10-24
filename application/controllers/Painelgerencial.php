<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/** Painel gerencial (ADR-008, item 1): rota painelgerencial. */
class Painelgerencial extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('painelgerencial_model');
    }

    public function index()
    {
        $perm = $this->session->userdata('permissao');
        if (! $this->permission->checkPermission($perm, 'vDashboard') || ! $this->permission->checkPermission($perm, 'rOs')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para ver o painel gerencial.');
            redirect(base_url());
        }
        $valida = fn ($d) => (is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false) ? $d : null;
        $filtro = [
            'dataInicial' => $valida($this->input->get('dataInicial')) ?: date('Y-m-01'),
            'dataFinal' => $valida($this->input->get('dataFinal')) ?: date('Y-m-d'),
        ];
        $this->data['filtro'] = $filtro;
        $this->data['ind'] = $this->painelgerencial_model->indicadores($filtro['dataInicial'], $filtro['dataFinal']);
        $this->data['view'] = 'painelgerencial/index';

        return $this->layout();
    }
}
