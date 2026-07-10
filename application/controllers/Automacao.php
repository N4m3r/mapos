<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Automacao extends MY_Controller
{
    private $chaves = [
        'automacao_aprovacao_ativa',
        'automacao_desc_servico',
        'automacao_info_complementar',
        'automacao_ctribnac',
        'automacao_ctribmun',
        'automacao_aliquota_iss',
        'automacao_tp_ret_issqn',
        'automacao_faturamento_dia',
    ];

    public function __construct()
    {
        parent::__construct();

        // Aceita a permissão específica de automação OU a de sistema (admin),
        // para não trancar quem já é admin mas ainda não recebeu cAutomacao.
        $perm = $this->session->userdata('permissao');
        if (! $this->permission->checkPermission($perm, 'cAutomacao')
            && ! $this->permission->checkPermission($perm, 'cSistema')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar automações.');
            redirect(base_url());
        }
    }

    public function index()
    {
        $this->data['menuConfiguracoes'] = 'Automacao';
        foreach ($this->chaves as $c) {
            $this->data[$c] = $this->data['configuration'][$c] ?? '';
        }
        if ((string) $this->data['automacao_faturamento_dia'] === '') {
            $this->data['automacao_faturamento_dia'] = '1';
        }

        $this->data['agendados'] = $this->listarAgendados();

        $this->data['view'] = 'automacao/automacao';

        return $this->layout();
    }

    /**
     * Faturamentos ainda em espera (aguardando o dia de emissão) e os que
     * ficaram com erro, para acompanhamento.
     */
    private function listarAgendados()
    {
        if (! $this->db->table_exists('faturamentos_agendados')) {
            return [];
        }

        return $this->db
            ->select('fa.*, clientes.nomeCliente')
            ->from('faturamentos_agendados fa')
            ->join('clientes', 'clientes.idClientes = fa.cliente_id', 'left')
            ->where_in('fa.status', ['aguardando', 'erro'])
            ->order_by('fa.data_agendada', 'ASC')
            ->limit(100)
            ->get()
            ->result();
    }

    public function salvar()
    {
        $this->load->model('mapos_model');

        // Dia do faturamento agendado: aceita 1..28 (evita meses curtos), padrão 1.
        $dia = (int) $this->input->post('automacao_faturamento_dia');
        if ($dia < 1 || $dia > 28) {
            $dia = 1;
        }

        $data = [
            'automacao_aprovacao_ativa' => $this->input->post('automacao_aprovacao_ativa') ? '1' : '0',
            'automacao_desc_servico' => (string) $this->input->post('automacao_desc_servico'),
            'automacao_info_complementar' => (string) $this->input->post('automacao_info_complementar'),
            'automacao_ctribnac' => (string) $this->input->post('automacao_ctribnac'),
            'automacao_ctribmun' => (string) $this->input->post('automacao_ctribmun'),
            'automacao_aliquota_iss' => (string) $this->input->post('automacao_aliquota_iss'),
            'automacao_tp_ret_issqn' => (string) $this->input->post('automacao_tp_ret_issqn'),
            'automacao_faturamento_dia' => (string) $dia,
        ];

        if ($this->mapos_model->saveConfiguracao($data)) {
            log_info('Alterou a configuração da automação de aprovação.');
            $this->session->set_flashdata('success', 'Automação salva com sucesso!');
        } else {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao salvar a automação.');
        }
        redirect(site_url('automacao'));
    }

    /**
     * Dispara agora a fila de faturamento agendado que já venceu (não força
     * itens ainda no futuro — só emite os que passaram do dia de faturamento).
     */
    public function processarAgendados()
    {
        $this->load->library('autoaprovacao');
        $this->autoaprovacao->processarPendentes(100);
        log_info('Disparou manualmente a fila de faturamento agendado.');
        $this->session->set_flashdata('success', 'Fila de faturamento processada. Itens vencidos foram emitidos.');
        redirect(site_url('automacao'));
    }

    /**
     * Cancela um item da fila de faturamento agendado (não emite).
     */
    public function cancelarAgendado($id = null)
    {
        if (! $id || ! is_numeric($id) || ! $this->db->table_exists('faturamentos_agendados')) {
            redirect(site_url('automacao'));
        }
        $this->db->where('id', (int) $id)
            ->where_in('status', ['aguardando', 'erro'])
            ->update('faturamentos_agendados', [
                'status' => 'cancelado',
                'motivo' => 'Cancelado manualmente.',
                'processed_at' => date('Y-m-d H:i:s'),
            ]);
        log_info('Cancelou o faturamento agendado #' . (int) $id);
        $this->session->set_flashdata('success', 'Agendamento cancelado.');
        redirect(site_url('automacao'));
    }
}
