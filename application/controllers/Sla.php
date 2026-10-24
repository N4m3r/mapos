<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Painel de SLA das OS (ADR-005, F6). Permissao: vOs.
 */
class Sla extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('sla_model');
        $this->data['menuOs'] = 'OS';
    }

    public function index()
    {
        $this->precisa('vOs', 'Você não tem permissão para visualizar O.S.');

        $situacao = (string) $this->input->get('situacao');
        $filtros = [
            'tecnico' => (int) $this->input->get('tecnico') ?: null,
            'contrato' => (int) $this->input->get('contrato') ?: null,
            'prioridade' => (string) $this->input->get('prioridade') ?: null,
            'situacao' => in_array($situacao, ['estourado', 'risco', 'no_prazo'], true) ? $situacao : null,
        ];

        $todas = $this->sla_model->abertas(array_merge($filtros, ['situacao' => null]));
        $this->data['kpis'] = $this->sla_model->indicadores($todas);
        $this->data['lista'] = $filtros['situacao']
            ? array_values(array_filter($todas, static fn ($os) => $os->situacao === $filtros['situacao']))
            : $todas;
        $this->data['filtros'] = $filtros;
        $this->data['disponivel'] = $this->sla_model->disponivel();

        $this->load->model('tecnico_model');
        $this->data['tecnicos'] = $this->tecnico_model->getTecnicosPorPermissao();
        $this->data['contratos'] = $this->db->table_exists('contratos')
            ? $this->db->select('idContrato AS id, codigo, descricao')->where('status', 'ativo')->order_by('descricao')->get('contratos')->result()
            : [];
        $this->load->model('contratos_model');
        $this->data['prioridades'] = array_keys(Contratos_model::prioridadesPadrao());
        $this->data['view'] = 'sla/painel';

        return $this->layout();
    }
}
