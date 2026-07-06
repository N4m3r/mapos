<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Página PÚBLICA de aprovação de Ordem de Serviço.
 *
 * Estende CI_Controller (e não MY_Controller) de propósito: o cliente acessa
 * pelo link temporário sem precisar estar logado no sistema. O acesso é
 * protegido pelo token aleatório de 64 caracteres presente na URL.
 */
class Aprovacao extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->model('aprovacao_model');
    }

    /**
     * Exibe a OS referente ao token e as opções de aprovar/reprovar.
     */
    public function index($token = null)
    {
        $os = $this->aprovacao_model->getByToken($token);
        $situacao = $this->aprovacao_model->situacao($os);

        $data = [
            'token' => $token,
            'situacao' => $situacao,
            'os' => $os,
            'erro' => $this->session->flashdata('erro'),
        ];

        // Só carrega detalhes (itens, valores, emitente) quando há uma OS válida.
        if ($os && $situacao !== 'invalido') {
            $this->load->model('os_model');
            $this->load->model('mapos_model');

            $data['produtos'] = $this->os_model->getProdutos($os->idOs);
            $data['servicos'] = $this->os_model->getServicos($os->idOs);
            $data['emitente'] = $this->mapos_model->getEmitente();

            $totais = $this->os_model->valorTotalOS($os->idOs);
            $data['totalProdutos'] = $totais['totalProdutos'];
            $data['totalServico'] = $totais['totalServico'];
            $data['valorDesconto'] = $totais['valor_desconto'];
        }

        $this->load->view('aprovacao/publico', $data);
    }

    /**
     * Recebe a decisão do cliente (POST) e registra na OS.
     */
    public function confirmar()
    {
        $token = $this->input->post('token');
        $decisao = $this->input->post('decisao'); // 'aprovado' | 'reprovado'
        $nome = trim((string) $this->input->post('nome'));
        $obs = trim((string) $this->input->post('obs'));

        $os = $this->aprovacao_model->getByToken($token);
        $situacao = $this->aprovacao_model->situacao($os);

        // Só é possível decidir enquanto o link está pendente e válido.
        if (! $os || $situacao !== 'pendente') {
            redirect('aprovacao/' . $token);
        }

        if (! in_array($decisao, ['aprovado', 'reprovado'], true)) {
            $this->session->set_flashdata('erro', 'Selecione aprovar ou reprovar.');
            redirect('aprovacao/' . $token);
        }

        if ($nome === '') {
            $this->session->set_flashdata('erro', 'Por favor, informe seu nome para confirmar a decisão.');
            redirect('aprovacao/' . $token);
        }

        if ($decisao === 'reprovado' && $obs === '') {
            $this->session->set_flashdata('erro', 'Para reprovar, descreva o motivo no campo de observação.');
            redirect('aprovacao/' . $token);
        }

        $this->aprovacao_model->registrarDecisao(
            $os->idOs,
            $decisao,
            $nome,
            $obs,
            $this->input->ip_address()
        );

        log_info('OS #' . $os->idOs . ' teve aprovação "' . $decisao . '" registrada por ' . $nome . ' via link público.');

        redirect('aprovacao/' . $token);
    }
}
