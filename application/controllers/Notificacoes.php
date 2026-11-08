<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Central de Notificacoes — tela unica para eventos, canais, modelos e historico.
 *
 * Substitui as telas separadas (gatilhos, modelos de e-mail/WhatsApp, log de
 * envios e as abas de notificacao de Configuracoes). As URLs antigas continuam
 * respondendo por redirecionamento, para nao quebrar favoritos e testes.
 *
 * Credenciais do .env e avisos legados de OS ficam em Notificacoes_credenciais.
 */
class Notificacoes extends MY_Controller
{
    /** Abas validas da central. */
    private const ABAS = ['eventos', 'pendencias', 'canais', 'modelos', 'historico'];

    public function __construct()
    {
        parent::__construct();

        $permissao = $this->session->userdata('permissao');
        if (! $this->permission->checkPermission($permissao, 'cSistema')
            && ! $this->permission->checkPermission($permissao, 'cEmail')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar notificações.');
            redirect(base_url());
        }

        $this->load->helper('notificacoes');
        $this->load->library('notificacoes_credenciais');
        $this->load->model('notification_triggers_model');
        $this->load->model('notificacoes_central_model');
        $this->load->model('atividade_pendencias_model');
    }

    /* ------------------------------------------------------------------ */
    /* Tela unica                                                          */
    /* ------------------------------------------------------------------ */

    public function index()
    {
        $aba = (string) $this->input->get('aba');
        if (! in_array($aba, self::ABAS, true)) {
            $aba = 'eventos';
        }

        $canal = (string) $this->input->get('canal');
        $status = (string) $this->input->get('status');
        $porEvento = $this->notificacoes_central_model->eventosComGatilhos();

        $this->data['menuConfiguracoes'] = 'Notificacoes';
        $this->data['abaAtiva'] = $aba;
        $this->data['tabelaGatilhos'] = $this->db->table_exists('notification_triggers');
        $this->data['porEvento'] = $porEvento;
        $this->data['eventosSemGatilho'] = $this->notificacoes_central_model->eventosSemGatilho();
        $this->data['ordemGrupos'] = notif_ordem_grupos();

        // Canais: credenciais vem da biblioteca; o .env e lido, nunca criado.
        $this->data['intervalo'] = $this->notificacoes_credenciais->intervalo();
        $this->data['smtp'] = $this->notificacoes_credenciais->smtp();
        $this->data['whatsapp'] = $this->notificacoes_credenciais->whatsapp();
        $this->data['whatsappAtivo'] = $this->notificacoes_credenciais->whatsappLigado();
        $this->data['envDisponivel'] = $this->notificacoes_credenciais->envDisponivel();

        // Avisos automaticos de OS (antes na aba "Notificacoes" de Configuracoes).
        $this->data['osNotification'] = $this->data['configuration']['os_notification'] ?? 'cliente';
        $this->data['emailAutomatico'] = (string) ($this->data['configuration']['email_automatico'] ?? '1');
        $this->data['notificaWhats'] = (string) ($this->data['configuration']['notifica_whats'] ?? '');
        $this->data['podeSistema'] = $this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema');
        $this->data['podeEmail'] = $this->permission->checkPermission($this->session->userdata('permissao'), 'cEmail');

        // Modelos
        $this->load->model('whatsapp_templates_model');
        $this->data['modelosEmail'] = $this->notificacoes_central_model->modelosEmail();
        $this->data['modelosWhatsapp'] = $this->notificacoes_central_model->modelosWhatsapp();
        $this->data['whatsappSlugsCore'] = Whatsapp_templates_model::slugsCore();

        // Historico
        $this->data['canalFiltro'] = in_array($canal, ['email', 'whatsapp'], true) ? $canal : null;
        $this->data['statusFiltro'] = in_array($status, ['enviado', 'falha'], true) ? $status : null;
        $this->data['historico'] = $this->notificacoes_central_model->historico(
            $this->data['canalFiltro'],
            $this->data['statusFiltro'],
            max(1, (int) $this->input->get('pagina'))
        );
        $this->data['totais'] = $this->notificacoes_central_model->totaisHistorico();
        $this->data['filaPendente'] = $this->notificacoes_central_model->filaEmailPendente();

        // Atividade não programada sem assinatura ou sem Nº do chamado (aba Pendências).
        $this->data['pendencias'] = $this->atividade_pendencias_model->listar();

        $this->data['view'] = 'notificacoes/central';

        return $this->layout();
    }

    /* ------------------------------------------------------------------ */
    /* Gatilhos por evento                                                 */
    /* ------------------------------------------------------------------ */

    /** Formulario de um evento (cria o gatilho no primeiro salvamento). */
    public function evento($evento = null)
    {
        if (! $this->db->table_exists('notification_triggers')) {
            $this->session->set_flashdata('error', 'A tabela de gatilhos ainda não existe neste banco. Rode a atualização do banco para configurar notificações.');
            redirect(site_url('notificacoes'));
        }

        $catalogo = Notification_triggers_model::eventosCatalogo();
        if (! $evento || ! isset($catalogo[$evento])) {
            $this->session->set_flashdata('error', 'Evento de notificação inválido.');
            redirect(site_url('notificacoes'));
        }

        $todos = $this->gatilhosDoEvento($evento);
        $principal = $todos[0] ?? null;

        $this->load->model('whatsapp_templates_model');
        $this->load->model('email_templates_model');

        $this->data['menuConfiguracoes'] = 'Notificacoes';
        $this->data['eventoSlug'] = $evento;
        $this->data['eventoInfo'] = $catalogo[$evento];
        $this->data['podeSistema'] = $this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema');
        $this->data['gatilho'] = $principal;
        $this->data['gatilhosEvento'] = $todos;
        $this->data['canais'] = Notification_triggers_model::canaisDisponiveis();
        $this->data['destinatarios'] = Notification_triggers_model::destinatariosDisponiveis();
        $this->data['blocos'] = Notification_triggers_model::blocosDisponiveis();
        $this->data['anexos'] = Notification_triggers_model::anexosDisponiveis();
        $this->data['whatsappTemplates'] = $this->whatsapp_templates_model->getAll();
        $this->data['emailTemplates'] = $this->email_templates_model->getAll();
        $this->data['clientesSelecionados'] = $this->clientesDoGatilho($principal);
        $this->data['projetosSelecionados'] = $this->projetosDoGatilho($principal);
        $this->data['ehOs'] = ($catalogo[$evento]['grupo'] === 'Ordem de Serviço');
        $this->data['ehProjeto'] = ($catalogo[$evento]['grupo'] === 'Projetos');

        $this->data['view'] = 'notificacoes/evento';

        return $this->layout();
    }

    /** Grava o gatilho principal do evento (cria quando ainda nao existe). */
    public function salvarEvento()
    {
        $this->exigirSistema();
        if (! $this->db->table_exists('notification_triggers')) {
            $this->session->set_flashdata('error', 'A tabela de gatilhos ainda não existe neste banco. Rode a atualização do banco e tente de novo.');
            redirect(site_url('notificacoes'));
        }

        $evento = (string) $this->input->post('evento');
        $catalogo = Notification_triggers_model::eventosCatalogo();
        if (! isset($catalogo[$evento])) {
            $this->session->set_flashdata('error', 'Evento de notificação inválido.');
            redirect(site_url('notificacoes'));
        }

        $gatilho = $this->gatilhoDoEvento($evento);
        $data = [
            'ativo' => $this->input->post('ativo') ? 1 : 0,
            'canais' => $this->listaPost('canais'),
            'destinatarios' => $this->listaPost('destinatarios'),
            'blocos' => $this->listaPost('blocos'),
            'anexos' => $this->listaPost('anexos'),
            'template_slug' => $this->input->post('template_slug') ?: null,
            'nome' => trim((string) $this->input->post('nome')) ?: $catalogo[$evento]['nome'],
            'descricao' => trim((string) $this->input->post('descricao')) ?: null,
        ];
        foreach (['whatsapp_grupos', 'whatsapp_clientes', 'projetos_id'] as $campoLista) {
            if ($this->db->field_exists($campoLista, 'notification_triggers')) {
                $data[$campoLista] = $this->listaPost($campoLista);
            }
        }
        if ($this->db->field_exists('whatsapp_template', 'notification_triggers')) {
            $data['whatsapp_template'] = $this->input->post('whatsapp_template') ?: null;
        }
        if ($this->db->field_exists('email_destinatarios', 'notification_triggers')) {
            $data['email_destinatarios'] = $this->emailListaPost('email_destinatarios_raw');
        }
        if ($this->db->field_exists('email_conversa', 'notification_triggers')) {
            $data['email_conversa'] = $this->input->post('email_conversa') ? 1 : 0;
        }

        if ($gatilho) {
            $this->notification_triggers_model->update($gatilho->id, $data);
            log_info('Alterou o gatilho de notificação: ' . $evento);
        } else {
            $data['evento'] = $evento;
            $data['grupo'] = $catalogo[$evento]['grupo'];
            $this->notification_triggers_model->create($data);
            log_info('Criou o gatilho de notificação: ' . $evento);
        }

        $this->session->set_flashdata('success', 'Configuração do evento salva.');
        redirect(site_url('notificacoes/evento/' . rawurlencode($evento)));
    }

    /** Ligar/desligar um evento (todos os gatilhos dele andam juntos). */
    public function alternar($evento = null)
    {
        $this->exigirSistema();
        $gatilhos = $evento ? $this->gatilhosDoEvento((string) $evento) : [];

        if ($gatilhos) {
            $algumAtivo = false;
            foreach ($gatilhos as $g) {
                if ((int) $g->ativo === 1) {
                    $algumAtivo = true;
                    break;
                }
            }
            $novo = $algumAtivo ? 0 : 1;
            foreach ($gatilhos as $g) {
                $this->notification_triggers_model->update($g->id, ['ativo' => $novo]);
            }
            log_info('Alternou os gatilhos de notificação do evento ' . $evento . ' para ' . $novo);
            $this->session->set_flashdata('success', 'Evento ' . ($novo === 1 ? 'ativado' : 'desativado') . '.');
        }
        redirect(site_url('notificacoes'));
    }

    /** Cria uma copia do gatilho do evento, desativada. */
    public function clonar($evento = null)
    {
        $this->exigirSistema();
        $gatilho = $evento ? $this->gatilhoDoEvento((string) $evento) : null;
        if (! $gatilho) {
            $this->session->set_flashdata('error', 'Nenhum gatilho configurado nesse evento.');
            redirect(site_url('notificacoes'));
        }

        $data = (array) $gatilho;
        unset($data['id'], $data['data_criacao'], $data['data_atualizacao']);
        $data['nome'] = $gatilho->nome . ' (cópia)';
        $data['ativo'] = 0;

        if ($this->notification_triggers_model->create($data)) {
            log_info('Clonou o gatilho de notificação ' . $evento . ' como "' . $data['nome'] . '"');
            $this->session->set_flashdata('success', 'Cópia criada e desativada. Ajuste e ative quando estiver pronta.');
            redirect(site_url('notificacoes/evento/' . rawurlencode($evento)));
        }

        $this->session->set_flashdata('error', 'Não foi possível clonar o gatilho.');
        redirect(site_url('notificacoes'));
    }

    /** Remove todos os gatilhos do evento. */
    public function excluir($evento = null)
    {
        $this->exigirSistema();
        $gatilhos = $evento ? $this->gatilhosDoEvento((string) $evento) : [];

        if ($gatilhos) {
            foreach ($gatilhos as $g) {
                $this->notification_triggers_model->delete($g->id);
            }
            log_info('Excluiu os gatilhos de notificação do evento ' . $evento);
            $this->session->set_flashdata('success', 'Configuração excluída. O evento volta ao padrão do sistema.');
        }
        redirect(site_url('notificacoes'));
    }

    /* ------------------------------------------------------------------ */
    /* Canais                                                              */
    /* ------------------------------------------------------------------ */

    public function salvarEmail()
    {
        $this->exigirSistema();
        $this->flashResultado($this->notificacoes_credenciais->salvarEmail($this->input->post()));
        redirect(site_url('notificacoes?aba=canais'));
    }

    public function salvarWhatsapp()
    {
        $this->exigirSistema();
        $this->flashResultado($this->notificacoes_credenciais->salvarWhatsapp($this->input->post()));
        redirect(site_url('notificacoes?aba=canais'));
    }

    public function salvarDespacho()
    {
        $this->exigirSistema();
        $this->flashResultado($this->notificacoes_credenciais->salvarIntervalo($this->input->post('notif_intervalo_disparo')));
        redirect(site_url('notificacoes?aba=canais'));
    }

    public function salvarLegado()
    {
        $this->exigirSistema();
        $this->flashResultado($this->notificacoes_credenciais->salvarAvisosOs($this->input->post()));
        redirect(site_url('notificacoes?aba=canais'));
    }

    /* ------------------------------------------------------------------ */
    /* Compatibilidade com URLs antigas                                    */
    /* ------------------------------------------------------------------ */

    public function envios()
    {
        redirect(site_url('notificacoes?aba=historico'));
    }

    public function novo()
    {
        redirect(site_url('notificacoes'));
    }

    public function editar($id = null)
    {
        $gatilho = $id && is_numeric($id) ? $this->notification_triggers_model->getById($id) : null;
        redirect($gatilho ? site_url('notificacoes/evento/' . rawurlencode($gatilho->evento)) : site_url('notificacoes'));
    }

    public function salvar()
    {
        $id = $this->input->post('id');
        $gatilho = $id && is_numeric($id) ? $this->notification_triggers_model->getById($id) : null;
        if ($gatilho) {
            $_POST['evento'] = $gatilho->evento;
            return $this->salvarEvento();
        }
        redirect(site_url('notificacoes'));
    }

    public function criar()
    {
        return $this->salvarEvento();
    }

    public function salvarConfig()
    {
        return $this->salvarDespacho();
    }

    /* ------------------------------------------------------------------ */
    /* Autocomplete dos filtros do evento                                  */
    /* ------------------------------------------------------------------ */

    public function autoCompleteCliente()
    {
        $q = trim((string) $this->input->get('term'));
        if ($q === '') {
            return $this->json([]);
        }

        $rows = $this->db->select('idClientes, nomeCliente, documento, celular, telefone')
            ->group_start()->like('nomeCliente', $q)->or_like('documento', $q)
            ->or_like('celular', $q)->or_like('telefone', $q)->group_end()
            ->order_by('nomeCliente', 'ASC')->limit(25)->get('clientes')->result();

        return $this->json(array_map(function ($r) {
            $doc = trim((string) $r->documento);

            return ['id' => (int) $r->idClientes, 'label' => $r->nomeCliente . ($doc !== '' ? ' — ' . $doc : '')];
        }, $rows));
    }

    public function autoCompleteProjeto()
    {
        $q = trim((string) $this->input->get('term'));
        if ($q === '' || ! $this->db->table_exists('obras')) {
            return $this->json([]);
        }

        $rows = $this->db->select('idObra, nome, codigo')
            ->group_start()->like('nome', $q)->or_like('codigo', $q)->group_end()
            ->order_by('nome', 'ASC')->limit(25)->get('obras')->result();

        return $this->json(array_map(function ($r) {
            $cod = trim((string) $r->codigo);

            return ['id' => (int) $r->idObra, 'label' => $r->nome . ($cod !== '' ? ' — ' . $cod : '')];
        }, $rows));
    }

    /* ------------------------------------------------------------------ */
    /* Apoio                                                               */
    /* ------------------------------------------------------------------ */

    private function json($payload)
    {
        return $this->output->set_content_type('application/json')->set_output(json_encode($payload));
    }

    private function exigirSistema()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema')) {
            $this->session->set_flashdata('error', 'Apenas quem configura o sistema pode alterar as notificações.');
            redirect(base_url());
        }
    }

    private function flashResultado(array $resultado)
    {
        $this->session->set_flashdata($resultado['ok'] ? 'success' : 'error', $resultado['mensagem']);
    }

    private function gatilhoDoEvento($evento)
    {
        $gatilhos = $this->gatilhosDoEvento($evento);

        return $gatilhos[0] ?? null;
    }

    /** @return object[] gatilhos do evento, na ordem de criacao */
    private function gatilhosDoEvento($evento)
    {
        $lista = [];
        foreach ($this->notification_triggers_model->getAll() as $g) {
            if ($g->evento === $evento) {
                $lista[] = $g;
            }
        }

        return $lista;
    }

    private function listaPost($campo)
    {
        $valores = $this->input->post($campo);
        if (! is_array($valores) || empty($valores)) {
            return null;
        }

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

    private function emailListaPost($campo)
    {
        $bruto = (string) $this->input->post($campo);
        if (trim($bruto) === '') {
            return null;
        }

        $emails = [];
        foreach (preg_split('/[,;\r\n]+/', $bruto) as $p) {
            $p = trim($p);
            if ($p !== '' && filter_var($p, FILTER_VALIDATE_EMAIL) && ! in_array($p, $emails, true)) {
                $emails[] = $p;
            }
        }

        return empty($emails) ? null : implode(',', $emails);
    }

    private function clientesDoGatilho($gatilho)
    {
        if (! $gatilho || empty($gatilho->whatsapp_clientes) || ! $this->db->table_exists('clientes')) {
            return [];
        }

        $ids = Notification_triggers_model::clientesIds($gatilho->whatsapp_clientes);
        if (empty($ids)) {
            return [];
        }

        return $this->db->select('idClientes, nomeCliente, documento')
            ->where_in('idClientes', $ids)->order_by('nomeCliente', 'ASC')
            ->get('clientes')->result();
    }

    private function projetosDoGatilho($gatilho)
    {
        if (! $gatilho || empty($gatilho->projetos_id) || ! $this->db->table_exists('obras')) {
            return [];
        }

        $ids = Notification_triggers_model::clientesIds($gatilho->projetos_id);
        if (empty($ids)) {
            return [];
        }

        return $this->db->select('idObra, nome, codigo')
            ->where_in('idObra', $ids)->order_by('nome', 'ASC')
            ->get('obras')->result();
    }
}
