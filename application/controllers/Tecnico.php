<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Tecnico extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Carregar models necessarios
        $this->load->model('os_model');
        $this->load->model('clientes_model');
        $this->load->model('checkin_model');
        $this->load->model('assinaturas_model');
        $this->load->model('fotosatendimento_model');
        $this->load->model('tecnico_model');
        $this->load->model('mapos_model');

        // Helper 'text' (character_limiter) usado nas views do tecnico; nao esta no autoload
        $this->load->helper('text');

        // Verificar se usuario esta logado
        if (!$this->session->userdata('id_admin')) {
            redirect('login');
        }

        // Verificar se tem permissao para acessar area do tecnico
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vTecnicoDashboard')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para acessar a Área do Técnico.');
            redirect(base_url());
        }
    }

    /**
     * Dashboard do tecnico
     */
    public function index()
    {
        $tecnico_id = $this->session->userdata('id_admin');

        // Dados para o dashboard
        $data['os_hoje'] = $this->tecnico_model->getOsHoje($tecnico_id);
        $data['os_pendentes'] = $this->tecnico_model->getOsPendentes($tecnico_id);
        $data['os_em_andamento'] = $this->tecnico_model->getOsEmAndamento($tecnico_id);
        $data['os_finalizadas_hoje'] = $this->tecnico_model->getOsFinalizadasHoje($tecnico_id);
        $data['estatisticas'] = $this->tecnico_model->getEstatisticas($tecnico_id);

        // Dados do emitente
        $data['emitente'] = $this->mapos_model->getEmitente();

        // Se o usuario tambem tem acesso ao painel principal, exibimos o atalho "Sistema"
        $data['pode_ver_sistema'] = $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs');
        $data['nome_tecnico'] = $this->session->userdata('nome_admin');

        // Titulo da pagina
        $data['titulo'] = 'Área do Técnico - Dashboard';

        $this->load->view('tecnico/dashboard', $data);
    }

    /**
     * Lista de OS designadas ao tecnico
     */
    public function os()
    {
        // Verificar permissao especifica para visualizar OS
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vTecnicoOS')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar Ordens de Serviço.');
            redirect('tecnico');
        }

        $tecnico_id = $this->session->userdata('id_admin');

        // Parametros de filtro
        $status = $this->input->get('status') ?: 'todos';
        $data_inicio = $this->input->get('data_inicio');
        $data_fim = $this->input->get('data_fim');

        $data['status'] = $status;
        $data['data_inicio'] = $data_inicio;
        $data['data_fim'] = $data_fim;

        // Buscar OS
        $data['ordens'] = $this->tecnico_model->getMinhasOs(
            $tecnico_id,
            $status,
            $data_inicio,
            $data_fim
        );

        // Dados do emitente
        $data['emitente'] = $this->mapos_model->getEmitente();

        // Atalho para o painel principal (se tiver acesso)
        $data['pode_ver_sistema'] = $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs');

        // Titulo
        $data['titulo'] = 'Minhas Ordens de Serviço';

        $this->load->view('tecnico/minhas_os', $data);
    }

    /**
     * Visualizar OS especifica (somente se designada ao tecnico)
     */
    public function visualizar($os_id = null)
    {
        if (!$os_id) {
            $this->session->set_flashdata('error', 'OS não informada.');
            redirect('tecnico/os');
        }

        $tecnico_id = $this->session->userdata('id_admin');

        // Verificar se a OS existe e se esta designada ao tecnico logado
        $os = $this->tecnico_model->getOsById($os_id, $tecnico_id);

        if (!$os) {
            $this->session->set_flashdata('error', 'OS não encontrada ou não está designada a você.');
            redirect('tecnico/os');
        }

        // Carregar dados do cliente
        $data['cliente'] = $this->clientes_model->getById($os->clientes_id);

        // Verificar se existe check-in ativo
        $data['checkin_ativo'] = $this->checkin_model->getCheckinAtivo($os_id);

        // Carregar assinaturas
        $data['assinaturas'] = $this->assinaturas_model->getByOs($os_id);

        // Organizar assinaturas por tipo
        $data['assinaturas_tipo'] = [];
        foreach ($data['assinaturas'] as $assinatura) {
            $data['assinaturas_tipo'][$assinatura->tipo] = $assinatura;
        }

        // Carregar fotos
        $data['fotos'] = $this->fotosatendimento_model->getByOs($os_id);

        // Organizar fotos por etapa
        $data['fotos_etapa'] = [
            'entrada' => [],
            'durante' => [],
            'saida' => []
        ];
        foreach ($data['fotos'] as $foto) {
            // Garante que a URL aponta para o endpoint correto se imagem está em base64
            if (!empty($foto->imagem_base64)) {
                $foto->url = base_url('index.php/checkin/verFotoDB/' . $foto->idFoto);
                $foto->url_visualizacao = $foto->url;
            }
            $data['fotos_etapa'][$foto->etapa][] = $foto;
        }

        // Dados da OS
        $data['os'] = $os;

        // Verificar permissoes
        $data['permissao_checkin'] = $this->permission->checkPermission(
            $this->session->userdata('permissao'),
            'eTecnicoCheckin'
        );
        $data['permissao_checkout'] = $this->permission->checkPermission(
            $this->session->userdata('permissao'),
            'eTecnicoCheckout'
        );

        // Atalho para o painel principal (se tiver acesso)
        $data['pode_ver_sistema'] = $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs');

        // Emitente (usado por parciais de assinatura/checkin)
        $data['emitente'] = $this->mapos_model->getEmitente();

        // Titulo
        $data['titulo'] = 'OS #' . sprintf('%04d', $os_id);

        $this->load->view('tecnico/visualizar_os', $data);
    }

    /**
     * Iniciar atendimento (check-in) via AJAX
     */
    public function iniciar_atendimento()
    {
        if (!$this->input->is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
            return;
        }

        // Verificar permissao
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'eTecnicoCheckin')) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão para iniciar atendimento']);
            return;
        }

        $os_id = $this->input->post('os_id');
        $tecnico_id = $this->session->userdata('id_admin');

        // Verificar se a OS pertence ao tecnico
        $os = $this->tecnico_model->getOsById($os_id, $tecnico_id);
        if (!$os) {
            echo json_encode(['success' => false, 'message' => 'OS não encontrada ou não designada a você']);
            return;
        }

        // Verifica se já existe check-in ativo
        if ($this->checkin_model->hasCheckinAtivo($os_id)) {
            echo json_encode(['success' => false, 'message' => 'Já existe um atendimento em andamento para esta OS']);
            return;
        }

        // Verifica se existe check-in "preso" (antigo sem data de saída)
        $ultimo_checkin = $this->checkin_model->getUltimoCheckin($os_id);
        if ($ultimo_checkin && empty($ultimo_checkin->data_saida)) {
            // Auto-finaliza checkins presos com mais de 24 horas
            if (strtotime($ultimo_checkin->data_entrada) < strtotime('-24 hours')) {
                $this->checkin_model->finalizarAtendimento($ultimo_checkin->idCheckin, [
                    'data_saida' => date('Y-m-d H:i:s'),
                    'observacao_saida' => 'Finalizado automaticamente (atendimento anterior não concluído)',
                    'status' => 'Finalizado'
                ]);
                log_info('Auto-finalizado checkin preso da OS: ' . $os_id);
            } else {
                echo json_encode(['success' => false, 'message' => 'Existe um atendimento iniciado recentemente. Finalize-o ou aguarde 24h para iniciar um novo.']);
                return;
            }
        }

        // Chama o método do Checkin diretamente
        // Nota: Não usar redirect em AJAX - chama o método internamente
        echo json_encode(['success' => true, 'message' => 'Pronto para iniciar atendimento', 'redirect' => 'checkin/iniciar']);
        return;
    }

    /**
     * Finalizar atendimento (check-out) via AJAX
     */
    public function finalizar_atendimento()
    {
        if (!$this->input->is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
            return;
        }

        // Verificar permissao
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'eTecnicoCheckout')) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão para finalizar atendimento']);
            return;
        }

        $os_id = $this->input->post('os_id');
        $tecnico_id = $this->session->userdata('id_admin');

        // Verificar se a OS pertence ao tecnico
        $os = $this->tecnico_model->getOsById($os_id, $tecnico_id);
        if (!$os) {
            echo json_encode(['success' => false, 'message' => 'OS não encontrada ou não designada a você']);
            return;
        }

        // Obtém check-in ativo (com auto-finalização de checkins expirados)
        $checkin = $this->checkin_model->getCheckinAtivoComAutoFinalizacao($os_id);

        if (!$checkin) {
            // Verifica se existe checkin mais antigo sem data de saída ("preso")
            $ultimo_checkin = $this->checkin_model->getUltimoCheckin($os_id);
            if ($ultimo_checkin && empty($ultimo_checkin->data_saida)) {
                // Se chegou aqui, o checkin tem menos de 24h (senão teria sido auto-finalizado)
                if (strtotime($ultimo_checkin->data_entrada) > strtotime('-24 hours')) {
                    $checkin = $ultimo_checkin;
                    log_info('Usando checkin preso para finalização da OS: ' . $os_id);
                } else {
                    // Isso não deveria acontecer devido à auto-finalização, mas mantemos por segurança
                    echo json_encode(['success' => false, 'message' => 'O atendimento anterior expirou (mais de 24h). Inicie um novo atendimento.']);
                    return;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Não existe atendimento em andamento para esta OS. Verifique se já foi finalizado.']);
                return;
            }
        }

        // Chama o método do Checkin diretamente
        // Nota: Não usar redirect em AJAX - retorna sucesso para o cliente chamar
        echo json_encode(['success' => true, 'message' => 'Pronto para finalizar atendimento', 'redirect' => 'checkin/finalizar']);
        return;
    }

    /**
     * API para listar OS (para uso em apps mobile)
     */
    public function api_listar_os()
    {
        // Verificar token/autenticacao
        $tecnico_id = $this->session->userdata('id_admin');

        $os = $this->tecnico_model->getMinhasOs($tecnico_id, 'todos');

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $os
        ]);
    }

    /**
     * API para obter detalhes da OS
     */
    public function api_os_detalhes($os_id = null)
    {
        if (!$os_id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'OS não informada']);
            return;
        }

        $tecnico_id = $this->session->userdata('id_admin');
        $os = $this->tecnico_model->getOsById($os_id, $tecnico_id);

        if (!$os) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'OS não encontrada']);
            return;
        }

        // Carregar dados adicionais
        $cliente = $this->clientes_model->getById($os->clientes_id);
        $checkin = $this->checkin_model->getCheckinAtivo($os_id);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'os' => $os,
                'cliente' => $cliente,
                'checkin_ativo' => $checkin
            ]
        ]);
    }
}
