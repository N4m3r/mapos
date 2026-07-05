<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Dashboard extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Verificar permissão - permite vDashboard OU cPermissao (administradores)
        $permissao = $this->session->userdata('permissao');
        if (!$this->permission->checkPermission($permissao, 'vDashboard') &&
            !$this->permission->checkPermission($permissao, 'cPermissao')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para acessar o Dashboard.');
            redirect(base_url());
        }

        // Carregar models
        $this->load->model('dashboard_model');
        $this->load->model('mapos_model');

        $this->data['menuDashboard'] = 'Dashboard';
    }

    public function index()
    {
        $this->data['view'] = 'dashboard/index';
        return $this->layout();
    }

    /**
     * Retorna dados para os gráficos via AJAX
     */
    public function dadosGraficos()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $periodo = $this->input->get('periodo') ?: 'mes';
        $data_inicio = $this->input->get('data_inicio');
        $data_fim = $this->input->get('data_fim');

        // Se não passar datas, usa o período padrão
        if (!$data_inicio || !$data_fim) {
            switch ($periodo) {
                case 'hoje':
                    $data_inicio = date('Y-m-d');
                    $data_fim = date('Y-m-d');
                    break;
                case 'semana':
                    $data_inicio = date('Y-m-d', strtotime('monday this week'));
                    $data_fim = date('Y-m-d');
                    break;
                case 'mes':
                    $data_inicio = date('Y-m-01');
                    $data_fim = date('Y-m-d');
                    break;
                case 'ano':
                    $data_inicio = date('Y-01-01');
                    $data_fim = date('Y-m-d');
                    break;
                default:
                    $data_inicio = date('Y-m-01');
                    $data_fim = date('Y-m-d');
            }
        }

        $dados = [
            'kpi' => $this->dashboard_model->getKPIs($data_inicio, $data_fim),
            'os_por_status' => $this->dashboard_model->getOsPorStatus($data_inicio, $data_fim),
            'os_por_mes' => $this->dashboard_model->getOsPorMes($data_inicio, $data_fim),
            'faturamento_mensal' => $this->dashboard_model->getFaturamentoMensal($data_inicio, $data_fim),
            'por_tecnico' => $this->dashboard_model->getOsPorTecnico($data_inicio, $data_fim),
            'top_produtos' => $this->dashboard_model->getTopProdutos($data_inicio, $data_fim),
            'top_servicos' => $this->dashboard_model->getTopServicos($data_inicio, $data_fim),
            'clientes_novos' => $this->dashboard_model->getClientesNovosRecorrentes($data_inicio, $data_fim),
        ];

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $dados]);
    }

    /**
     * Relatório detalhado de atendimentos
     */
    public function relatorio_atendimentos()
    {
        $data_inicio = $this->input->get('data_inicio') ?: date('Y-m-01');
        $data_fim = $this->input->get('data_fim') ?: date('Y-m-d');
        $tecnico_id = $this->input->get('tecnico_id');

        $this->data['atendimentos'] = $this->dashboard_model->getRelatorioAtendimentos($data_inicio, $data_fim, $tecnico_id);
        $this->data['tecnicos'] = $this->dashboard_model->getTecnicosAtivos();
        $this->data['data_inicio'] = $data_inicio;
        $this->data['data_fim'] = $data_fim;
        $this->data['tecnico_id'] = $tecnico_id;

        $this->data['view'] = 'dashboard/relatorio_atendimentos';
        return $this->layout();
    }

    /**
     * Relatório financeiro
     */
    public function relatorio_financeiro()
    {
        $data_inicio = $this->input->get('data_inicio') ?: date('Y-m-01');
        $data_fim = $this->input->get('data_fim') ?: date('Y-m-d');

        $this->data['financeiro'] = $this->dashboard_model->getRelatorioFinanceiro($data_inicio, $data_fim);
        $this->data['lancamentos'] = $this->dashboard_model->getLancamentosPorCategoria($data_inicio, $data_fim);
        $this->data['data_inicio'] = $data_inicio;
        $this->data['data_fim'] = $data_fim;

        $this->data['view'] = 'dashboard/relatorio_financeiro';
        return $this->layout();
    }

    /**
     * Relatório de produtos e serviços
     */
    public function relatorio_produtos()
    {
        $data_inicio = $this->input->get('data_inicio') ?: date('Y-m-01');
        $data_fim = $this->input->get('data_fim') ?: date('Y-m-d');

        $this->data['produtos'] = $this->dashboard_model->getRelatorioProdutos($data_inicio, $data_fim);
        $this->data['servicos'] = $this->dashboard_model->getRelatorioServicos($data_inicio, $data_fim);
        $this->data['estoque_critico'] = $this->dashboard_model->getEstoqueCritico();
        $this->data['data_inicio'] = $data_inicio;
        $this->data['data_fim'] = $data_fim;

        $this->data['view'] = 'dashboard/relatorio_produtos';
        return $this->layout();
    }

    /**
     * Relatório de clientes
     */
    public function relatorio_clientes()
    {
        $data_inicio = $this->input->get('data_inicio') ?: date('Y-m-01');
        $data_fim = $this->input->get('data_fim') ?: date('Y-m-d');

        $this->data['clientes'] = $this->dashboard_model->getRelatorioClientes($data_inicio, $data_fim);
        $this->data['top_clientes'] = $this->dashboard_model->getTopClientes($data_inicio, $data_fim);
        $this->data['data_inicio'] = $data_inicio;
        $this->data['data_fim'] = $data_fim;

        $this->data['view'] = 'dashboard/relatorio_clientes';
        return $this->layout();
    }

    /**
     * Exportar dados para Excel/CSV
     */
    public function exportar()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vExportarDados')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para exportar dados.');
            redirect('dashboard');
        }

        $tipo = $this->input->get('tipo');
        $data_inicio = $this->input->get('data_inicio') ?: date('Y-m-01');
        $data_fim = $this->input->get('data_fim') ?: date('Y-m-d');

        // Define o nome do arquivo
        $filename = 'relatorio_' . $tipo . '_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para UTF-8

        switch ($tipo) {
            case 'atendimentos':
                $dados = $this->dashboard_model->getRelatorioAtendimentos($data_inicio, $data_fim);
                fputcsv($output, ['OS', 'Cliente', 'Técnico', 'Status', 'Data', 'Valor']);
                foreach ($dados as $row) {
                    fputcsv($output, [
                        $row->idOs,
                        $row->nomeCliente,
                        $row->nome_tecnico,
                        $row->status,
                        date('d/m/Y', strtotime($row->dataInicial)),
                        number_format($row->valorTotal, 2, ',', '.')
                    ]);
                }
                break;

            case 'financeiro':
                $dados = $this->dashboard_model->getRelatorioFinanceiro($data_inicio, $data_fim);
                fputcsv($output, ['Data', 'Descrição', 'Tipo', 'Valor']);
                foreach ($dados as $row) {
                    fputcsv($output, [
                        date('d/m/Y', strtotime($row->data_vencimento)),
                        $row->descricao,
                        $row->tipo,
                        number_format($row->valor, 2, ',', '.')
                    ]);
                }
                break;

            case 'clientes':
                $dados = $this->dashboard_model->getRelatorioClientes($data_inicio, $data_fim);
                fputcsv($output, ['Cliente', 'Total OS', 'Valor Total', 'Ticket Médio']);
                foreach ($dados as $row) {
                    fputcsv($output, [
                        $row->nomeCliente,
                        $row->total_os,
                        number_format($row->valor_total, 2, ',', '.'),
                        number_format($row->ticket_medio, 2, ',', '.')
                    ]);
                }
                break;
        }

        fclose($output);
    }
}
