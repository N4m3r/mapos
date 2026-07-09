<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Notificacoes extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar notificações.');
            redirect(base_url());
        }

        $this->load->model('notification_triggers_model');
    }

    public function index()
    {
        $this->data['menuConfiguracoes'] = 'Notificacoes';
        $this->data['results'] = $this->notification_triggers_model->getAll();
        $this->data['intervalo'] = isset($this->data['configuration']['notif_intervalo_disparo'])
            ? (int) $this->data['configuration']['notif_intervalo_disparo']
            : 120;

        $this->data['view'] = 'notificacoes/notificacoes';

        return $this->layout();
    }

    public function editar($id = null)
    {
        if (! $id || ! is_numeric($id)) {
            $this->session->set_flashdata('error', 'Gatilho não encontrado.');
            redirect(site_url('notificacoes'));
        }

        $gatilho = $this->notification_triggers_model->getById($id);
        if (! $gatilho) {
            $this->session->set_flashdata('error', 'Gatilho não encontrado.');
            redirect(site_url('notificacoes'));
        }

        $this->data['menuConfiguracoes'] = 'Notificacoes';
        $this->data['gatilho'] = $gatilho;
        $this->data['templates'] = $this->templatesDisponiveis();

        $this->data['view'] = 'notificacoes/editarNotificacao';

        return $this->layout();
    }

    public function salvar()
    {
        $id = $this->input->post('id');
        $gatilho = $id && is_numeric($id) ? $this->notification_triggers_model->getById($id) : null;
        if (! $gatilho) {
            $this->session->set_flashdata('error', 'Gatilho não encontrado.');
            redirect(site_url('notificacoes'));
        }

        $data = [
            'ativo' => $this->input->post('ativo') ? 1 : 0,
            'canais' => $this->listaPost('canais'),
            'destinatarios' => $this->listaPost('destinatarios'),
            'blocos' => $this->listaPost('blocos'),
            'anexos' => $this->listaPost('anexos'),
            'template_slug' => $this->input->post('template_slug') ?: null,
        ];

        $this->notification_triggers_model->update($id, $data);

        log_info('Alterou o gatilho de notificação: ' . $gatilho->evento);
        $this->session->set_flashdata('success', 'Gatilho de notificação salvo com sucesso!');
        redirect(site_url('notificacoes/editar/' . $id));
    }

    public function salvarConfig()
    {
        $this->load->model('mapos_model');

        $intervalo = (int) $this->input->post('notif_intervalo_disparo');
        if ($intervalo < 30) {
            $intervalo = 30; // piso de segurança
        }

        $ok = $this->mapos_model->saveConfiguracao(['notif_intervalo_disparo' => $intervalo]);
        if ($ok) {
            log_info('Alterou o intervalo de disparo de notificações para ' . $intervalo . 's.');
            $this->session->set_flashdata('success', 'Intervalo de disparo salvo com sucesso!');
        } else {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao salvar o intervalo.');
        }
        redirect(site_url('notificacoes'));
    }

    /**
     * Junta os checkboxes marcados (array no POST) numa string por vírgula.
     */
    private function listaPost($campo)
    {
        $valores = $this->input->post($campo);
        if (! is_array($valores) || empty($valores)) {
            return null;
        }

        return implode(',', array_map('strval', $valores));
    }

    private function templatesDisponiveis()
    {
        if (! $this->db->table_exists('email_templates')) {
            return [];
        }
        $rows = $this->db->select('slug, nome')->order_by('nome', 'ASC')->get('email_templates')->result();
        $lista = [];
        foreach ($rows as $r) {
            $lista[$r->slug] = $r->nome;
        }

        return $lista;
    }
}
