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
    ];

    public function __construct()
    {
        parent::__construct();

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema')) {
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

        $this->data['view'] = 'automacao/automacao';

        return $this->layout();
    }

    public function salvar()
    {
        $this->load->model('mapos_model');

        $data = [
            'automacao_aprovacao_ativa' => $this->input->post('automacao_aprovacao_ativa') ? '1' : '0',
            'automacao_desc_servico' => (string) $this->input->post('automacao_desc_servico'),
            'automacao_info_complementar' => (string) $this->input->post('automacao_info_complementar'),
            'automacao_ctribnac' => (string) $this->input->post('automacao_ctribnac'),
            'automacao_ctribmun' => (string) $this->input->post('automacao_ctribmun'),
            'automacao_aliquota_iss' => (string) $this->input->post('automacao_aliquota_iss'),
            'automacao_tp_ret_issqn' => (string) $this->input->post('automacao_tp_ret_issqn'),
        ];

        if ($this->mapos_model->saveConfiguracao($data)) {
            log_info('Alterou a configuração da automação de aprovação.');
            $this->session->set_flashdata('success', 'Automação salva com sucesso!');
        } else {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao salvar a automação.');
        }
        redirect(site_url('automacao'));
    }
}
