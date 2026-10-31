<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Pagina PUBLICA de acompanhamento da OS + avaliacao NPS (ADR-007).
 *
 * Estende CI_Controller (como Aprovacao e Aceite): o cliente abre o link
 * recebido por WhatsApp/e-mail sem login. O acesso e protegido pelo token
 * aleatorio de 64 caracteres da URL.
 */
class Acompanhar extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('acompanhamento_model');
        $this->load->model('os_model');
        $this->load->model('mapos_model');
    }

    public function index($token = null)
    {
        $os = $this->acompanhamento_model->getByToken($token);
        if (! $os) {
            show_404();
        }

        $this->output->set_header('X-Robots-Tag: noindex, nofollow');
        $this->load->view('acompanhar/publico', [
            'os' => $os,
            'token' => $token,
            'timeline' => $this->os_model->getTimeline($os->idOs),
            'emitente' => $this->mapos_model->getEmitente(),
            'avaliacao' => $this->acompanhamento_model->avaliacao($os->idOs),
            'podeAvaliar' => in_array($os->status, Acompanhamento_model::STATUS_AVALIAVEIS, true),
        ]);
    }

    public function avaliar()
    {
        $token = $this->input->post('token');
        $os = $this->acompanhamento_model->getByToken($token);
        if (! $os) {
            show_404();
        }

        if (! in_array($os->status, Acompanhamento_model::STATUS_AVALIAVEIS, true)) {
            $this->session->set_flashdata('error', 'A avaliação fica disponível quando o serviço for concluído.');
        } elseif ($this->acompanhamento_model->avaliar($os->idOs, $this->input->post('nota'), $this->input->post('comentario'), $this->input->ip_address())) {
            log_info('Cliente avaliou a OS #' . $os->idOs . ' (nota ' . (int) $this->input->post('nota') . ').');
            $this->session->set_flashdata('success', 'Obrigado pela sua avaliação!');
        } else {
            $this->session->set_flashdata('error', 'Não foi possível registrar a avaliação (escolha uma nota de 0 a 10).');
        }
        redirect('acompanhar/' . $token);
    }
}
