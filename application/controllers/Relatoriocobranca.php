<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Relatorio de cobranca do mes por cliente (ADR-011).
 */
class Relatoriocobranca extends MY_Controller
{
    private $situacoes = [
        'nao_cobrado' => ['rotulo' => 'Não cobrado', 'classe' => 'perigo'],
        'pendente' => ['rotulo' => 'Pendente de pagamento', 'classe' => 'alerta'],
        'a_realizar' => ['rotulo' => 'Será realizado', 'classe' => 'info'],
        'faturado' => ['rotulo' => 'Faturado e pago', 'classe' => 'sucesso'],
        'nao_realizado' => ['rotulo' => 'Não realizado', 'classe' => 'neutro'],
    ];

    private $origens = [
        'os' => 'OS',
        'venda' => 'Venda',
        'contrato' => 'Contrato',
        'lancamento' => 'Lançamento',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->data['menuRelatorios'] = 'Relatórios';
    }

    public function index()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'rFinanceiro')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para gerar relatórios financeiros.');
            redirect(base_url());
        }

        $competencia = $this->input->get('competencia');
        $mes = is_string($competencia) && preg_match('/^\d{4}-\d{2}$/', $competencia)
            ? (int) substr($competencia, 5, 2)
            : 0;
        if ($mes < 1 || $mes > 12) {
            $competencia = date('Y-m');
        }

        $clienteId = (int) $this->input->get('clientes_id');

        $situacoesSel = array_values(array_intersect(
            array_filter((array) $this->input->get('situacao'), 'is_string'),
            array_keys($this->situacoes)
        ));

        $origem = $this->input->get('origem');
        $origem = is_string($origem) && isset($this->origens[$origem]) ? $origem : '';

        $filtros = [];
        if ($clienteId > 0) {
            $filtros['clientes_id'] = $clienteId;
        }
        if ($origem !== '') {
            $filtros['origem'] = $origem;
        }

        $this->load->model('relatorio_cobranca_model');
        // Cartoes e chips mostram o mes inteiro (com cliente/origem); o filtro de
        // situacao so recorta as tabelas, para as contagens nao zerarem ao filtrar.
        $todos = $this->relatorio_cobranca_model->itens($competencia, $filtros);
        $itens = $situacoesSel
            ? array_values(array_filter($todos, fn ($i) => in_array($i['situacao'], $situacoesSel, true)))
            : $todos;
        $resumo = $this->relatorio_cobranca_model->resumo($itens);
        $resumo['totais'] = $this->relatorio_cobranca_model->resumo($todos)['totais'];

        $clientes = $this->db
            ->select('idClientes, nomeCliente')
            ->order_by('nomeCliente', 'ASC')
            ->limit(2000)
            ->get('clientes')
            ->result();

        $this->data['competencia'] = $competencia;
        $this->data['filtro'] = [
            'clientes_id' => $clienteId > 0 ? $clienteId : null,
            'situacao' => $situacoesSel,
            'origem' => $origem,
        ];
        $this->data['resumo'] = $resumo;
        $this->data['clientes'] = $clientes;
        $this->data['situacoes'] = $this->situacoes;
        $this->data['origens'] = $this->origens;
        $this->data['view'] = 'relatoriocobranca/index';

        return $this->layout();
    }
}
