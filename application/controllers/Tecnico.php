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
        $data['pode_criar_atividade'] = $this->podeCriarAtividade();
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
        $data['pode_criar_atividade'] = $this->podeCriarAtividade();

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

    /* ================================================================= *
     *  ATIVIDADE NÃO PROGRAMADA (aberta pelo técnico em campo)
     * ================================================================= */

    /**
     * Verifica se o técnico logado pode abrir atividades não programadas.
     */
    private function podeCriarAtividade()
    {
        return $this->permission->checkPermission(
            $this->session->userdata('permissao'),
            'aTecnicoAtividade'
        );
    }

    /**
     * Formulário para abrir uma atividade não programada.
     */
    public function nova_atividade()
    {
        if (!$this->podeCriarAtividade()) {
            $this->session->set_flashdata('error', 'Você não tem permissão para abrir atividades não programadas.');
            redirect('tecnico');
        }

        $data['emitente'] = $this->mapos_model->getEmitente();
        $data['pode_ver_sistema'] = $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs');
        $data['titulo'] = 'Nova Atividade';

        $this->load->view('tecnico/nova_atividade', $data);
    }

    /**
     * Persiste a atividade não programada como uma OS (status "Aberto"),
     * já vinculada ao técnico logado, e anexa serviços/produtos com o preço
     * resolvido no servidor (o técnico nunca informa valor).
     */
    public function salvar_atividade()
    {
        if (!$this->podeCriarAtividade()) {
            $this->session->set_flashdata('error', 'Você não tem permissão para abrir atividades não programadas.');
            redirect('tecnico');
        }

        $tecnico_id = $this->session->userdata('id_admin');
        $clientes_id = (int) $this->input->post('clientes_id');

        // Cliente é obrigatório.
        if (!$clientes_id) {
            $this->session->set_flashdata('error', 'Selecione um cliente para abrir a atividade.');
            redirect('tecnico/nova_atividade');
        }

        // Confirma que o cliente existe.
        if (!$this->clientes_model->getById($clientes_id)) {
            $this->session->set_flashdata('error', 'Cliente inválido.');
            redirect('tecnico/nova_atividade');
        }

        $hoje = date('Y-m-d');

        $osData = [
            'dataInicial'      => $hoje,
            'dataFinal'        => $hoje,
            'clientes_id'      => $clientes_id,
            'usuarios_id'      => $tecnico_id,
            'tecnico_responsavel' => $tecnico_id,
            'garantia'         => 0,
            'descricaoProduto' => (string) $this->input->post('descricaoProduto'),
            'defeito'          => (string) $this->input->post('defeito'),
            'observacoes'      => (string) $this->input->post('observacoes'),
            'status'           => 'Aberto',
            'faturado'         => 0,
            'nao_programada'   => 1,
        ];

        $idOs = $this->tecnico_model->criarAtividade($osData);

        if (!$idOs) {
            $this->session->set_flashdata('error', 'Não foi possível abrir a atividade. Tente novamente.');
            redirect('tecnico/nova_atividade');
        }

        $this->load->model('produtos_model');
        $controlaEstoque = !empty($this->data['configuration']['control_estoque']);

        // Serviços selecionados (preço resolvido no servidor).
        $servicos = $this->input->post('servicos');
        if (is_array($servicos)) {
            foreach ($servicos as $s) {
                $servicoId = (int) (is_array($s) ? ($s['id'] ?? 0) : $s);
                if (!$servicoId) {
                    continue;
                }
                $qtd = (int) (is_array($s) ? ($s['quantidade'] ?? 1) : 1);
                $qtd = $qtd > 0 ? $qtd : 1;
                $preco = $this->tecnico_model->getPrecoServico($servicoId);

                $this->os_model->add('servicos_os', [
                    'servicos_id' => $servicoId,
                    'quantidade'  => $qtd,
                    'preco'       => $preco,
                    'os_id'       => $idOs,
                    'subTotal'    => $preco * $qtd,
                ]);
            }
        }

        // Produtos selecionados (preço/estoque resolvidos no servidor).
        $produtos = $this->input->post('produtos');
        if (is_array($produtos)) {
            foreach ($produtos as $p) {
                $produtoId = (int) (is_array($p) ? ($p['id'] ?? 0) : $p);
                if (!$produtoId) {
                    continue;
                }
                $qtd = (int) (is_array($p) ? ($p['quantidade'] ?? 1) : 1);
                $qtd = $qtd > 0 ? $qtd : 1;

                $produto = $this->tecnico_model->getProduto($produtoId);
                if (!$produto) {
                    continue;
                }
                $preco = (float) $produto->precoVenda;

                $this->os_model->add('produtos_os', [
                    'quantidade'  => $qtd,
                    'subTotal'    => $preco * $qtd,
                    'produtos_id' => $produtoId,
                    'preco'       => $preco,
                    'os_id'       => $idOs,
                ]);

                if ($controlaEstoque) {
                    $this->produtos_model->updateEstoque($produtoId, $qtd, '-');
                }
            }
        }

        log_info('Técnico abriu atividade não programada. OS ID: ' . $idOs);
        $this->session->set_flashdata('success', 'Atividade aberta com sucesso!');
        redirect('tecnico/visualizar/' . $idOs);
    }

    /**
     * Autocomplete de clientes (Área do Técnico).
     */
    public function buscar_clientes()
    {
        $q = (string) $this->input->get('q');
        $lista = $this->tecnico_model->buscarClientes($q);

        $out = [];
        foreach ($lista as $c) {
            $fone = $c->celular ?: $c->telefone;
            $doc = $this->formatarDocumento($c->documento);
            $partes = array_filter([$c->nomeCliente, $doc, $fone]);
            $out[] = [
                'id'        => $c->idClientes,
                'label'     => implode(' · ', $partes),
                'nome'      => $c->nomeCliente,
                'documento' => $doc,
            ];
        }

        $this->output->set_content_type('application/json')->set_output(json_encode($out));
    }

    /**
     * Formata CPF (11 dígitos) ou CNPJ (14 dígitos); devolve como veio se não
     * bater o tamanho (já formatado ou vazio).
     */
    private function formatarDocumento($doc)
    {
        $num = preg_replace('/\D/', '', (string) $doc);
        if (strlen($num) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $num);
        }
        if (strlen($num) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $num);
        }

        return (string) $doc;
    }

    /**
     * Autocomplete de serviços SEM valor.
     */
    public function buscar_servicos()
    {
        $q = (string) $this->input->get('q');
        $lista = $this->tecnico_model->buscarServicos($q);

        $out = [];
        foreach ($lista as $s) {
            $out[] = [
                'id'    => $s->idServicos,
                'label' => $s->nome,
                'nome'  => $s->nome,
            ];
        }

        $this->output->set_content_type('application/json')->set_output(json_encode($out));
    }

    /**
     * Autocomplete de produtos SEM valor (mostra só estoque).
     */
    public function buscar_produtos()
    {
        $q = (string) $this->input->get('q');
        $lista = $this->tecnico_model->buscarProdutos($q);

        $out = [];
        foreach ($lista as $p) {
            $out[] = [
                'id'      => $p->idProdutos,
                'label'   => $p->descricao . ' · Estoque: ' . $p->estoque,
                'nome'    => $p->descricao,
                'estoque' => $p->estoque,
            ];
        }

        $this->output->set_content_type('application/json')->set_output(json_encode($out));
    }

    /**
     * Página para coletar a assinatura do solicitante: assinar no próprio
     * aparelho (canvas) ou gerar um link para o solicitante assinar no
     * celular dele (fluxo público de aceite).
     */
    public function assinatura_solicitante($os_id = null)
    {
        $tecnico_id = $this->session->userdata('id_admin');
        $os = $this->tecnico_model->getOsById($os_id, $tecnico_id);

        if (!$os) {
            $this->session->set_flashdata('error', 'OS não encontrada ou não está designada a você.');
            redirect('tecnico/os');
        }

        // Situação atual do link público (se existir).
        $this->load->model('aceite_model');
        $data['aceite_suportado'] = $this->aceite_model->suportado();
        $data['aceite_situacao'] = $this->aceite_model->situacao($os);
        $data['aceite_link'] = (!empty($os->aceite_token))
            ? site_url('aceite/' . $os->aceite_token)
            : '';

        $data['os'] = $os;
        $data['cliente'] = $this->clientes_model->getById($os->clientes_id);
        $data['assinaturas'] = $this->assinaturas_model->getByOs($os_id);
        $data['pode_ver_sistema'] = $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs');
        $data['titulo'] = 'Assinatura do Solicitante';

        $this->load->view('tecnico/assinatura_solicitante', $data);
    }

    /**
     * Salva a assinatura do solicitante feita no aparelho do técnico.
     */
    public function salvar_assinatura_solicitante()
    {
        if (!$this->input->is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
            return;
        }

        $os_id = (int) $this->input->post('os_id');
        $tecnico_id = $this->session->userdata('id_admin');

        $os = $this->tecnico_model->getOsById($os_id, $tecnico_id);
        if (!$os) {
            echo json_encode(['success' => false, 'message' => 'OS não encontrada ou não designada a você']);
            return;
        }

        $assinaturaBase64 = (string) $this->input->post('assinatura');
        if (empty($assinaturaBase64) || strlen($assinaturaBase64) < 100) {
            echo json_encode(['success' => false, 'message' => 'Assinatura em branco. Peça para o solicitante assinar no quadro.']);
            return;
        }

        $nome = trim((string) $this->input->post('nome'));
        $documento = trim((string) $this->input->post('documento'));

        $imagem = $this->assinaturas_model->salvarImagem($assinaturaBase64, $os_id, 'solicitante');

        if (!$imagem) {
            echo json_encode(['success' => false, 'message' => 'Falha ao salvar a assinatura.']);
            return;
        }

        // salvarImagem já pode ter gravado no banco (modo alternativo). Só
        // insere a linha em os_assinaturas quando salvou em arquivo.
        if (isset($imagem['modo']) && $imagem['modo'] === 'arquivo') {
            $this->assinaturas_model->add([
                'os_id' => $os_id,
                'tipo' => 'solicitante',
                'assinatura' => $imagem['path'],
                'nome_assinante' => $nome ?: null,
                'documento_assinante' => $documento ?: null,
                'data_assinatura' => date('Y-m-d H:i:s'),
                'ip_address' => $this->input->ip_address(),
            ]);
        }

        log_info('Assinatura do solicitante coletada na OS: ' . $os_id);
        echo json_encode(['success' => true, 'message' => 'Assinatura registrada com sucesso!']);
    }

    /**
     * Gera (ou regenera) o link público para o solicitante assinar no
     * próprio celular. Reaproveita o fluxo de aceite existente.
     */
    public function gerar_link_solicitante()
    {
        if (!$this->input->is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
            return;
        }

        $os_id = (int) $this->input->post('os_id');
        $tecnico_id = $this->session->userdata('id_admin');

        $os = $this->tecnico_model->getOsById($os_id, $tecnico_id);
        if (!$os) {
            echo json_encode(['success' => false, 'message' => 'OS não encontrada ou não designada a você']);
            return;
        }

        $this->load->model('aceite_model');
        if (!$this->aceite_model->suportado()) {
            echo json_encode(['success' => false, 'message' => 'Recurso de link de assinatura não está disponível neste sistema.']);
            return;
        }

        $res = $this->aceite_model->gerarLink($os_id);
        if (!$res) {
            echo json_encode(['success' => false, 'message' => 'Não foi possível gerar o link.']);
            return;
        }

        $link = site_url('aceite/' . $res['token']);
        log_info('Técnico gerou link de assinatura do solicitante para a OS: ' . $os_id);

        echo json_encode([
            'success' => true,
            'message' => 'Link gerado com sucesso!',
            'link' => $link,
            'expira' => date('d/m/Y', strtotime($res['expira'])),
        ]);
    }

    /**
     * Envia o link de aprovação/assinatura por e-mail para um endereço
     * informado manualmente pelo técnico. Reaproveita o token de aceite
     * existente (gera um novo se ainda não houver) e enfileira o e-mail.
     */
    public function enviar_link_solicitante_email()
    {
        if (!$this->input->is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
            return;
        }

        $os_id = (int) $this->input->post('os_id');
        $email = trim((string) $this->input->post('email'));
        $tecnico_id = $this->session->userdata('id_admin');

        $os = $this->tecnico_model->getOsById($os_id, $tecnico_id);
        if (!$os) {
            echo json_encode(['success' => false, 'message' => 'OS não encontrada ou não designada a você']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Informe um e-mail válido.']);
            return;
        }

        $this->load->model('aceite_model');
        if (!$this->aceite_model->suportado()) {
            echo json_encode(['success' => false, 'message' => 'Recurso de link de assinatura não está disponível neste sistema.']);
            return;
        }

        // Reaproveita o token pendente; senão, gera um novo.
        $token = (!empty($os->aceite_token) && $this->aceite_model->situacao($os) === 'pendente')
            ? $os->aceite_token
            : null;

        if (!$token) {
            $res = $this->aceite_model->gerarLink($os_id);
            if (!$res) {
                echo json_encode(['success' => false, 'message' => 'Não foi possível gerar o link.']);
                return;
            }
            $token = $res['token'];
        }

        $link = site_url('aceite/' . $token);
        $emitente = $this->mapos_model->getEmitente();
        if (!$emitente || empty($emitente->email)) {
            echo json_encode(['success' => false, 'message' => 'E-mail do emitente não configurado. Configure em Emitente.']);
            return;
        }

        $nomeCliente = html_escape($os->nomeCliente ?: 'Cliente');
        $nomeEmitente = html_escape($emitente->nome ?: 'Equipe');
        $assunto = 'Assinatura do serviço - OS #' . sprintf('%04d', $os_id);
        $html =
            '<p>Olá, ' . $nomeCliente . '!</p>' .
            '<p>Por favor, confirme e assine o serviço realizado (OS #' . sprintf('%04d', $os_id) . ') no link abaixo:</p>' .
            '<p><a href="' . $link . '" style="display:inline-block;padding:12px 20px;background:#667eea;color:#fff;text-decoration:none;border-radius:6px">Abrir e assinar</a></p>' .
            '<p>Ou copie e cole no navegador:<br>' . $link . '</p>' .
            '<p>Atenciosamente,<br>' . $nomeEmitente . '</p>';

        $this->load->model('email_model');
        $headers = ['From' => $emitente->email, 'Subject' => $assunto, 'Return-Path' => ''];
        $ok = $this->email_model->add('email_queue', [
            'to' => $email,
            'message' => $html,
            'status' => 'pending',
            'date' => date('Y-m-d H:i:s'),
            'headers' => serialize($headers),
        ]);

        if (!$ok) {
            echo json_encode(['success' => false, 'message' => 'Falha ao enfileirar o e-mail.']);
            return;
        }

        log_info('Técnico enviou link de assinatura por e-mail (' . $email . ') da OS: ' . $os_id);
        echo json_encode([
            'success' => true,
            'message' => 'E-mail enfileirado para ' . $email . '. O envio ocorre automaticamente em instantes.',
            'link' => $link,
        ]);
    }
}
