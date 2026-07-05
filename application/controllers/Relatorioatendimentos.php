<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Relatorioatendimentos Controller
 *
 * Dashboard e relatórios de atendimentos técnicos
 *
 * @version 1.0
 */

class Relatorioatendimentos extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Verifica se está logado
        if (!$this->session->userdata('id_usuario')) {
            redirect('login');
        }

        // Carrega models
        $this->load->model('relatorioatendimentos_model');
        $this->load->model('checkin_model');
        $this->load->model('usuarios_model');

        // Carrega helpers
        $this->load->helper('date');
    }

    /**
     * Página principal - Dashboard de atendimentos
     */
    public function index()
    {
        // Verifica permissão
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vRelatorioAtendimentos')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar relatórios de atendimentos.');
            redirect(base_url());
        }

        // Parâmetros de filtro
        $data_inicio = $this->input->get('data_inicio') ?: date('Y-m-01'); // Primeiro dia do mês
        $data_fim = $this->input->get('data_fim') ?: date('Y-m-d'); // Hoje
        $usuario_id = $this->input->get('usuario_id') ?: null;

        // Dados para a view
        $dados = [
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'usuario_id' => $usuario_id,
            'tecnicos' => $this->usuarios_model->getAll(),
        ];

        $this->data['results'] = $dados;
        $this->data['view'] = 'relatorioatendimentos/relatorio';

        $this->load->view('tema/topo', $this->data);
        $this->load->view($this->data['view'], $this->data);
        $this->load->view('tema/rodape');
    }

    /**
     * Lista de atendimentos para DataTable (JSON)
     */
    public function listar()
    {
        if (!$this->input->is_ajax_request()) {
            redirect(base_url());
        }

        // Verifica permissão
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vRelatorioAtendimentos')) {
            echo json_encode(['error' => 'Sem permissão']);
            return;
        }

        // Parâmetros
        $data_inicio = $this->input->post('data_inicio') ?: date('Y-m-01');
        $data_fim = $this->input->post('data_fim') ?: date('Y-m-d');
        $usuario_id = $this->input->post('usuario_id') ?: null;
        $draw = intval($this->input->post('draw'));
        $start = intval($this->input->post('start'));
        $length = intval($this->input->post('length')) ?: 25;

        // Busca dados
        $atendimentos = $this->relatorioatendimentos_model->getAtendimentosComFiltros(
            $data_inicio,
            $data_fim,
            $usuario_id,
            $length,
            $start
        );

        $total = $this->relatorioatendimentos_model->countAtendimentos($data_inicio, $data_fim, $usuario_id);

        // Formata dados para DataTable
        $data = [];
        foreach ($atendimentos as $atendimento) {
            // Calcula tempo de atendimento
            $tempo = '-';
            if ($atendimento->data_saida) {
                $entrada = new DateTime($atendimento->data_entrada);
                $saida = new DateTime($atendimento->data_saida);
                $intervalo = $entrada->diff($saida);
                $tempo = $intervalo->format('%h:%i');
            }

            $data[] = [
                'idCheckin' => $atendimento->idCheckin,
                'os_id' => $atendimento->os_id,
                'nome_tecnico' => $atendimento->nome_tecnico,
                'data_entrada' => date('d/m/Y H:i', strtotime($atendimento->data_entrada)),
                'data_saida' => $atendimento->data_saida ? date('d/m/Y H:i', strtotime($atendimento->data_saida)) : '-',
                'tempo' => $tempo,
                'status' => $atendimento->data_saida ?
                    '<span class="label label-success">Finalizado</span>' :
                    '<span class="label label-warning">Em Andamento</span>',
                'acoes' => '<a href="' . site_url('os/visualizar/' . $atendimento->os_id) . '" class="btn btn-mini" title="Ver OS"><i class="bx bx-show"></i></a>'
            ];
        }

        $output = [
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data
        ];

        echo json_encode($output);
    }

    /**
     * Estatísticas para os gráficos (JSON)
     */
    public function estatisticas()
    {
        if (!$this->input->is_ajax_request()) {
            redirect(base_url());
        }

        // Verifica permissão
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vRelatorioAtendimentos')) {
            echo json_encode(['error' => 'Sem permissão']);
            return;
        }

        // Parâmetros
        $data_inicio = $this->input->post('data_inicio') ?: date('Y-m-01');
        $data_fim = $this->input->post('data_fim') ?: date('Y-m-d');
        $usuario_id = $this->input->post('usuario_id') ?: null;

        // Estatísticas gerais
        $estatisticas = $this->relatorioatendimentos_model->getEstatisticas($data_inicio, $data_fim, $usuario_id);

        // Atendimentos por dia
        $atendimentosPorDia = $this->relatorioatendimentos_model->getAtendimentosPorDia($data_inicio, $data_fim, $usuario_id);

        // Atendimentos por técnico
        $atendimentosPorTecnico = $this->relatorioatendimentos_model->getAtendimentosPorTecnico($data_inicio, $data_fim);

        // Atendimentos por status
        $atendimentosPorStatus = $this->relatorioatendimentos_model->getAtendimentosPorStatus($data_inicio, $data_fim, $usuario_id);

        // Tempo médio por técnico
        $tempoMedioPorTecnico = $this->relatorioatendimentos_model->getTempoMedioPorTecnico($data_inicio, $data_fim);

        echo json_encode([
            'success' => true,
            'estatisticas' => $estatisticas,
            'atendimentosPorDia' => $atendimentosPorDia,
            'atendimentosPorTecnico' => $atendimentosPorTecnico,
            'atendimentosPorStatus' => $atendimentosPorStatus,
            'tempoMedioPorTecnico' => $tempoMedioPorTecnico
        ]);
    }

    /**
     * Exportar relatório para Excel
     */
    public function exportar()
    {
        // Verifica permissão
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vRelatorioAtendimentos')) {
            $this->session->set_flashdata('error', 'Sem permissão.');
            redirect(base_url());
        }

        $data_inicio = $this->input->get('data_inicio') ?: date('Y-m-01');
        $data_fim = $this->input->get('data_fim') ?: date('Y-m-d');
        $usuario_id = $this->input->get('usuario_id') ?: null;

        $atendimentos = $this->relatorioatendimentos_model->getAtendimentosComFiltros(
            $data_inicio,
            $data_fim,
            $usuario_id,
            10000, // Sem limite prático
            0
        );

        // Gera CSV
        $filename = 'relatorio_atendimentos_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // BOM para Excel reconhecer UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeçalho
        fputcsv($output, [
            'ID',
            'OS',
            'Técnico',
            'Data Entrada',
            'Data Saída',
            'Tempo (horas)',
            'Status',
            'Observação Entrada',
            'Observação Saída'
        ]);

        foreach ($atendimentos as $atendimento) {
            // Calcula tempo
            $tempoHoras = '';
            if ($atendimento->data_saida) {
                $entrada = new DateTime($atendimento->data_entrada);
                $saida = new DateTime($atendimento->data_saida);
                $intervalo = $entrada->diff($saida);
                $tempoHoras = $intervalo->h + ($intervalo->i / 60);
            }

            fputcsv($output, [
                $atendimento->idCheckin,
                $atendimento->os_id,
                $atendimento->nome_tecnico,
                $atendimento->data_entrada,
                $atendimento->data_saida ?: '',
                $tempoHoras,
                $atendimento->data_saida ? 'Finalizado' : 'Em Andamento',
                $atendimento->observacao_entrada ?: '',
                $atendimento->observacao_saida ?: ''
            ]);
        }

        fclose($output);
    }

    /**
     * Visualizar detalhes de um atendimento específico
     */
    public function visualizar($checkin_id)
    {
        // Verifica permissão
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vRelatorioAtendimentos')) {
            $this->session->set_flashdata('error', 'Sem permissão.');
            redirect(base_url());
        }

        $checkin = $this->checkin_model->getById($checkin_id);

        if (!$checkin) {
            $this->session->set_flashdata('error', 'Atendimento não encontrado.');
            redirect('relatorioatendimentos');
        }

        $this->data['checkin'] = $checkin;
        $this->data['view'] = 'relatorioatendimentos/visualizar';

        $this->load->view('tema/topo', $this->data);
        $this->load->view($this->data['view'], $this->data);
        $this->load->view('tema/rodape');
    }
}
