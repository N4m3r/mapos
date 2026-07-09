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

        $ok = $this->mapos_model->saveConfiguracao($data);

        // DIAGNÓSTICO TEMPORÁRIO: mostra o que chegou no POST e o que ficou no
        // banco, para identificar se o problema é envio, gravação ou leitura.
        $recebidoDesc = (string) $this->input->post('automacao_desc_servico');
        $recebidoAtiva = $this->input->post('automacao_aprovacao_ativa') ? '1' : '0';
        $noBanco = $this->db->where('config', 'automacao_desc_servico')->get('configuracoes')->row();
        $noBancoDesc = $noBanco ? (string) $noBanco->valor : 'SEM LINHA';
        $debug = ' [DEBUG recebido: ativa=' . $recebidoAtiva
            . ' desc=\'' . mb_substr($recebidoDesc, 0, 25) . '\''
            . ' | no banco: desc=\'' . mb_substr($noBancoDesc, 0, 25) . '\']';

        if ($ok) {
            log_info('Alterou a configuração da automação de aprovação.' . $debug);
            $this->session->set_flashdata('success', 'Automação salva.' . $debug);
        } else {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao salvar a automação.' . $debug);
        }
        redirect(site_url('automacao'));
    }
}
