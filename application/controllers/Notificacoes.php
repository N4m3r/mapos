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

        $this->load->model('whatsapp_templates_model');

        $this->data['menuConfiguracoes'] = 'Notificacoes';
        $this->data['gatilho'] = $gatilho;
        $this->data['templates'] = $this->templatesDisponiveis();
        $this->data['whatsappTemplates'] = $this->whatsapp_templates_model->getAll();
        $this->data['clientesSelecionados'] = $this->clientesDoGatilho($gatilho);
        $this->data['projetosSelecionados'] = $this->projetosDoGatilho($gatilho);

        $this->data['view'] = 'notificacoes/editarNotificacao';

        return $this->layout();
    }

    /**
     * Autocomplete de clientes para o filtro do gatilho WhatsApp.
     */
    public function autoCompleteCliente()
    {
        $q = trim((string) $this->input->get('term'));
        if ($q === '') {
            echo json_encode([]);

            return;
        }

        $this->db->select('idClientes, nomeCliente, documento, celular, telefone');
        $this->db->group_start();
        $this->db->like('nomeCliente', $q);
        $this->db->or_like('documento', $q);
        $this->db->or_like('celular', $q);
        $this->db->or_like('telefone', $q);
        $this->db->group_end();
        $this->db->order_by('nomeCliente', 'ASC');
        $this->db->limit(25);
        $rows = $this->db->get('clientes')->result();

        $out = [];
        foreach ($rows as $r) {
            $doc = trim((string) $r->documento);
            $label = $r->nomeCliente . ($doc !== '' ? ' — ' . $doc : '');
            $out[] = [
                'id' => (int) $r->idClientes,
                'label' => $label,
                'value' => $label,
            ];
        }

        echo json_encode($out);
    }

    /**
     * Autocomplete de projetos para o filtro do gatilho (RDO).
     */
    public function autoCompleteProjeto()
    {
        $q = trim((string) $this->input->get('term'));
        if ($q === '' || ! $this->db->table_exists('obras')) {
            echo json_encode([]);

            return;
        }

        $this->db->select('idObra, nome, codigo');
        $this->db->group_start();
        $this->db->like('nome', $q);
        $this->db->or_like('codigo', $q);
        $this->db->group_end();
        $this->db->order_by('nome', 'ASC');
        $this->db->limit(25);
        $rows = $this->db->get('obras')->result();

        $out = [];
        foreach ($rows as $r) {
            $cod = trim((string) $r->codigo);
            $label = $r->nome . ($cod !== '' ? ' — ' . $cod : '');
            $out[] = [
                'id' => (int) $r->idObra,
                'label' => $label,
                'value' => $label,
            ];
        }

        echo json_encode($out);
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
        if ($this->db->field_exists('whatsapp_grupos', 'notification_triggers')) {
            $data['whatsapp_grupos'] = $this->listaPost('whatsapp_grupos');
        }
        if ($this->db->field_exists('whatsapp_template', 'notification_triggers')) {
            $data['whatsapp_template'] = $this->input->post('whatsapp_template') ?: null;
        }
        if ($this->db->field_exists('whatsapp_clientes', 'notification_triggers')) {
            $data['whatsapp_clientes'] = $this->listaPost('whatsapp_clientes');
        }
        if ($this->db->field_exists('email_destinatarios', 'notification_triggers')) {
            $data['email_destinatarios'] = $this->emailListaPost('email_destinatarios_raw');
        }
        if ($this->db->field_exists('email_conversa', 'notification_triggers')) {
            $data['email_conversa'] = $this->input->post('email_conversa') ? 1 : 0;
        }
        if ($this->db->field_exists('projetos_id', 'notification_triggers')) {
            $data['projetos_id'] = $this->listaPost('projetos_id');
        }

        $this->notification_triggers_model->update($id, $data);

        log_info('Alterou o gatilho de notificação: ' . $gatilho->evento);
        $this->session->set_flashdata('success', 'Gatilho de notificação salvo com sucesso!');
        redirect(site_url('notificacoes/editar/' . $id));
    }

    public function envios()
    {
        $this->load->model('whatsapp_envios_model');
        $status = $this->input->get('status');
        $status = in_array($status, ['enviado', 'falha'], true) ? $status : null;

        $this->data['menuConfiguracoes'] = 'Notificacoes';
        $this->data['statusFiltro'] = $status;
        $this->data['totalEnviados'] = $this->whatsapp_envios_model->count('enviado');
        $this->data['totalFalhas'] = $this->whatsapp_envios_model->count('falha');
        $this->data['envios'] = $this->whatsapp_envios_model->getUltimos(100, 0, $status);
        $this->data['view'] = 'notificacoes/envios';

        return $this->layout();
    }

    public function novo()
    {
        $this->load->model('whatsapp_templates_model');

        $this->data['menuConfiguracoes'] = 'Notificacoes';
        $this->data['eventos'] = Notification_triggers_model::eventosCatalogo();
        $this->data['templates'] = $this->templatesDisponiveis();
        $this->data['whatsappTemplates'] = $this->whatsapp_templates_model->getAll();
        $this->data['view'] = 'notificacoes/novoNotificacao';

        return $this->layout();
    }

    public function criar()
    {
        $evento = $this->input->post('evento');
        $catalogo = Notification_triggers_model::eventosCatalogo();
        if (! isset($catalogo[$evento])) {
            $this->session->set_flashdata('error', 'Selecione um evento válido.');
            redirect(site_url('notificacoes/novo'));
        }

        $data = [
            'evento' => $evento,
            'nome' => trim((string) $this->input->post('nome')) ?: $catalogo[$evento]['nome'],
            'grupo' => $catalogo[$evento]['grupo'],
            'descricao' => trim((string) $this->input->post('descricao')) ?: null,
            'ativo' => $this->input->post('ativo') ? 1 : 0,
            'canais' => $this->listaPost('canais'),
            'destinatarios' => $this->listaPost('destinatarios'),
            'blocos' => $this->listaPost('blocos'),
            'anexos' => $this->listaPost('anexos'),
            'template_slug' => $this->input->post('template_slug') ?: null,
        ];
        if ($this->db->field_exists('whatsapp_grupos', 'notification_triggers')) {
            $data['whatsapp_grupos'] = $this->listaPost('whatsapp_grupos');
        }
        if ($this->db->field_exists('whatsapp_template', 'notification_triggers')) {
            $data['whatsapp_template'] = $this->input->post('whatsapp_template') ?: null;
        }
        if ($this->db->field_exists('whatsapp_clientes', 'notification_triggers')) {
            $data['whatsapp_clientes'] = $this->listaPost('whatsapp_clientes');
        }

        $id = $this->notification_triggers_model->create($data);
        if ($id) {
            log_info('Criou um gatilho de notificação: ' . $evento);
            $this->session->set_flashdata('success', 'Gatilho criado com sucesso!');
            redirect(site_url('notificacoes/editar/' . $id));
        }

        $this->session->set_flashdata('error', 'Não foi possível criar o gatilho.');
        redirect(site_url('notificacoes/novo'));
    }

    /**
     * Clona um gatilho existente (todos os campos) com um novo nome, desativado
     * por padrão — o usuário ajusta o que for preciso e ativa. Serve tanto pra
     * criar variações do gatilho de WhatsApp quanto, no caso do RDO, pra
     * reaproveitar a lista de e-mails responsáveis num gatilho separado.
     */
    public function clonar($id = null)
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

        $nome = trim((string) $this->input->post('nome'));
        if ($nome === '') {
            $nome = $gatilho->nome . ' (cópia)';
        }

        $data = (array) $gatilho;
        unset($data['id'], $data['data_criacao'], $data['data_atualizacao']);
        $data['nome'] = $nome;
        $data['ativo'] = 0;

        $novoId = $this->notification_triggers_model->create($data);
        if ($novoId) {
            log_info('Clonou o gatilho de notificação #' . $id . ' (' . $gatilho->evento . ') como "' . $nome . '"');
            $this->session->set_flashdata('success', 'Gatilho clonado com sucesso! Ajuste o que for preciso e ative.');
            redirect(site_url('notificacoes/editar/' . $novoId));
        }

        $this->session->set_flashdata('error', 'Não foi possível clonar o gatilho.');
        redirect(site_url('notificacoes'));
    }

    public function excluir($id = null)
    {
        if ($id && is_numeric($id)) {
            $gatilho = $this->notification_triggers_model->getById($id);
            if ($gatilho) {
                $this->notification_triggers_model->delete($id);
                log_info('Excluiu o gatilho de notificação: ' . $gatilho->evento);
                $this->session->set_flashdata('success', 'Gatilho excluído.');
            }
        }
        redirect(site_url('notificacoes'));
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

        // IDs de clientes/projetos: só números positivos, únicos.
        if ($campo === 'whatsapp_clientes' || $campo === 'projetos_id') {
            $ids = [];
            foreach ($valores as $v) {
                $id = (int) $v;
                if ($id > 0 && ! in_array($id, $ids, true)) {
                    $ids[] = $id;
                }
            }

            return empty($ids) ? null : implode(',', $ids);
        }

        return implode(',', array_map('strval', $valores));
    }

    /**
     * Normaliza a textarea de e-mails "responsáveis" (um por linha, ou separados
     * por vírgula/ponto-e-vírgula) numa string CSV só com e-mails válidos.
     */
    private function emailListaPost($campo)
    {
        $bruto = (string) $this->input->post($campo);
        if (trim($bruto) === '') {
            return null;
        }

        $partes = preg_split('/[,;\r\n]+/', $bruto);
        $emails = [];
        foreach ($partes as $p) {
            $p = trim($p);
            if ($p !== '' && filter_var($p, FILTER_VALIDATE_EMAIL) && ! in_array($p, $emails, true)) {
                $emails[] = $p;
            }
        }

        return empty($emails) ? null : implode(',', $emails);
    }

    /**
     * Carrega dados dos clientes salvos no gatilho (para a UI de edição).
     *
     * @return object[]
     */
    private function clientesDoGatilho($gatilho)
    {
        if (! $gatilho || empty($gatilho->whatsapp_clientes) || ! $this->db->table_exists('clientes')) {
            return [];
        }

        $ids = Notification_triggers_model::clientesIds($gatilho->whatsapp_clientes);
        if (empty($ids)) {
            return [];
        }

        $this->db->select('idClientes, nomeCliente, documento');
        $this->db->where_in('idClientes', $ids);
        $this->db->order_by('nomeCliente', 'ASC');

        return $this->db->get('clientes')->result();
    }

    /**
     * Carrega dados dos projetos salvos no filtro do gatilho (para a UI de edição).
     *
     * @return object[]
     */
    private function projetosDoGatilho($gatilho)
    {
        if (! $gatilho || empty($gatilho->projetos_id) || ! $this->db->table_exists('obras')) {
            return [];
        }

        $ids = Notification_triggers_model::clientesIds($gatilho->projetos_id);
        if (empty($ids)) {
            return [];
        }

        $this->db->select('idObra, nome, codigo');
        $this->db->where_in('idObra', $ids);
        $this->db->order_by('nome', 'ASC');

        return $this->db->get('obras')->result();
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
