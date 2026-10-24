<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Custo real e margem por OS (ADR-008).
 */
class Margem extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('margem_model');
    }

    public function index()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'rFinanceiro')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para ver a margem por OS.');
            redirect(base_url());
        }

        $valida = fn ($d) => (is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d)) ? $d : null;
        $filtro = [
            'dataInicial' => $valida($this->input->get('dataInicial')) ?: date('Y-m-01'),
            'dataFinal' => $valida($this->input->get('dataFinal')) ?: date('Y-m-t'),
        ];

        $custoHora = (float) ($this->data['configuration']['custo_hora_tecnico'] ?? 0);
        $this->data['filtro'] = $filtro;
        $this->data['custoHora'] = $custoHora;
        $this->data['podeConfigurar'] = (bool) $this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema');
        $this->data['res'] = $this->margem_model->calcular($filtro['dataInicial'], $filtro['dataFinal'], $custoHora);
        $this->data['view'] = 'margem/index';

        return $this->layout();
    }

    public function salvarCustoHora()
    {
        if ($this->input->method() !== 'post' || ! $this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para alterar o custo-hora.');
            redirect(site_url('margem'));
        }
        $valor = str_replace(',', '.', (string) $this->input->post('custo_hora_tecnico'));
        if (! is_numeric($valor) || (float) $valor < 0) {
            $this->session->set_flashdata('error', 'Informe um custo-hora válido.');
            redirect(site_url('margem'));
        }
        $this->margem_model->salvarCustoHora($valor);
        $this->session->set_flashdata('success', 'Custo-hora do técnico salvo.');
        redirect(site_url('margem'));
    }
}
