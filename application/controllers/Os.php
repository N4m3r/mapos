<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Os extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->model('os_model');
        $this->data['menuOs'] = 'OS';
    }

    public function index()
    {
        $this->gerenciar();
    }

    public function gerenciar()
    {
        $this->load->library('pagination');
        $this->load->model('mapos_model');

        $where_array = [];

        $pesquisa = $this->input->get('pesquisa');
        $status = $this->input->get('status');
        $inputDe = $this->input->get('data');
        $inputAte = $this->input->get('data2');
        $filtroObra = (int) $this->input->get('obra_id');

        if ($filtroObra) {
            $where_array['obra_id'] = $filtroObra;
        }
        if ($pesquisa) {
            $where_array['pesquisa'] = $pesquisa;
        }
        if ($status) {
            $where_array['status'] = $status;
        }
        if ($inputDe) {
            $de = explode('/', $inputDe);
            $de = $de[2] . '-' . $de[1] . '-' . $de[0];

            $where_array['de'] = $de;
        }
        if ($inputAte) {
            $ate = explode('/', $inputAte);
            $ate = $ate[2] . '-' . $ate[1] . '-' . $ate[0];

            $where_array['ate'] = $ate;
        }

        // Verificar se é técnico - se sim, filtrar apenas OS atribuídas a ele
        $permissao = $this->session->userdata('permissao');
        $idUsuario = $this->session->userdata('id_admin');

        // Se tem permissão de técnico específica mas NÃO tem permissão de OS geral (eOs)
        if ($this->permission->checkPermission($permissao, 'vTecnicoOS') &&
            !$this->permission->checkPermission($permissao, 'eOs')) {
            $where_array['tecnico_responsavel'] = $idUsuario;
        }

        $this->data['configuration']['base_url'] = site_url('os/gerenciar/');
        $this->data['configuration']['total_rows'] = $this->os_model->count('os');
        if(count($where_array) > 0) {
            $this->data['configuration']['suffix'] = "?pesquisa={$pesquisa}&status={$status}&data={$inputDe}&data2={$inputAte}&obra_id={$filtroObra}";
            $this->data['configuration']['first_url'] = base_url("index.php/os/gerenciar")."\?pesquisa={$pesquisa}&status={$status}&data={$inputDe}&data2={$inputAte}&obra_id={$filtroObra}";
        }

        $this->pagination->initialize($this->data['configuration']);

        $this->data['results'] = $this->os_model->getOs(
            'os',
            'os.*,
            COALESCE((SELECT SUM(produtos_os.preco * produtos_os.quantidade ) FROM produtos_os WHERE produtos_os.os_id = os.idOs), 0) totalProdutos,
            COALESCE((SELECT SUM(servicos_os.preco * servicos_os.quantidade ) FROM servicos_os WHERE servicos_os.os_id = os.idOs), 0) totalServicos',
            $where_array,
            $this->data['configuration']['per_page'],
            $this->uri->segment(3)
        );

        // Projetos para o filtro e vinculo de cada OS da pagina (1 query, sem N+1)
        $this->load->model('obras_model');
        $this->data['projetosFiltro'] = $this->db->table_exists('obras') ? $this->obras_model->getProjetosParaVincular() : [];
        $this->data['vinculosPorOs'] = $this->vinculosDasOs($this->data['results']);
        $this->data['texto_de_notificacao'] = $this->data['configuration']['notifica_whats'];
        $this->data['emitente'] = $this->mapos_model->getEmitente();
        // Status que a acao rapida da listagem pode aplicar (mesma regra do alterarStatusAction)
        $this->data['statusRapidos'] = $this->statusChamadoPermitidos();
        $this->data['view'] = 'os/os';

        return $this->layout();
    }

    public function adicionar()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'aOs')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para adicionar O.S.');
            redirect(base_url());
        }

        $this->load->library('form_validation');
        $this->data['custom_error'] = '';

        if ($this->form_validation->run('os') == false) {
            $this->data['custom_error'] = (validation_errors() ? true : false);
        } else {
            $dataInicial = $this->input->post('dataInicial');
            $dataFinal = $this->input->post('dataFinal');
            $termoGarantiaId = $this->input->post('termoGarantia');

            try {
                $dataInicial = explode('/', $dataInicial);
                $dataInicial = $dataInicial[2] . '-' . $dataInicial[1] . '-' . $dataInicial[0];

                if ($dataFinal) {
                    $dataFinal = explode('/', $dataFinal);
                    $dataFinal = $dataFinal[2] . '-' . $dataFinal[1] . '-' . $dataFinal[0];
                } else {
                    $dataFinal = date('Y/m/d');
                }

                $termoGarantiaId = (! $termoGarantiaId == null || ! $termoGarantiaId == '')
                    ? $this->input->post('garantias_id')
                    : null;
            } catch (Exception $e) {
                $dataInicial = date('Y/m/d');
                $dataFinal = date('Y/m/d');
            }

            $data = [
                'dataInicial' => $dataInicial,
                'clientes_id' => $this->input->post('clientes_id'), //set_value('idCliente'),
                'usuarios_id' => $this->input->post('usuarios_id'), //set_value('idUsuario'),
                'dataFinal' => $dataFinal,
                'garantia' => set_value('garantia'),
                'garantias_id' => $termoGarantiaId,
                'descricaoProduto' => $this->input->post('descricaoProduto'),
                'defeito' => $this->input->post('defeito'),
                'status' => set_value('status'),
                'observacoes' => $this->input->post('observacoes'),
                'laudoTecnico' => $this->input->post('laudoTecnico'),
                'faturado' => 0,
            ];

            if (is_numeric($id = $this->os_model->add('os', $data, true))) {
                $this->load->model('mapos_model');
                $this->load->model('usuarios_model');

                $idOs = $id;
                $os = $this->os_model->getById($idOs);
                $emitente = $this->mapos_model->getEmitente();

                $tecnico = $this->usuarios_model->getById($os->usuarios_id);

                // Verificar configuração de notificação
                if ($this->data['configuration']['os_notification'] != 'nenhum' && $this->data['configuration']['email_automatico'] == 1) {
                    $remetentes = [];
                    switch ($this->data['configuration']['os_notification']) {
                        case 'todos':
                            array_push($remetentes, $os->email);
                            array_push($remetentes, $tecnico->email);
                            array_push($remetentes, $emitente->email);
                            break;
                        case 'cliente':
                            array_push($remetentes, $os->email);
                            break;
                        case 'tecnico':
                            array_push($remetentes, $tecnico->email);
                            break;
                        case 'emitente':
                            array_push($remetentes, $emitente->email);
                            break;
                        default:
                            array_push($remetentes, $os->email);
                            break;
                    }
                    $this->enviarOsPorEmail($idOs, $remetentes, 'Ordem de Serviço - Criada', 'os_aberta');
                }

                // Notificação automática por WhatsApp quando o status inicial
                // já estiver na lista configurada (resiliente a falhas).
                $this->notificarWhatsAppAutomatico($os, $emitente, 'os_aberta');

                $this->session->set_flashdata('success', 'OS adicionada com sucesso, você pode adicionar produtos ou serviços a essa OS nas abas de Produtos e Serviços!');
                log_info('Adicionou uma OS. ID: ' . $id);
                redirect(site_url('os/editar/') . $id);
            } else {
                $this->data['custom_error'] = '<div class="alert">Ocorreu um erro.</div>';
            }
        }

        $this->data['view'] = 'os/adicionarOs';

        return $this->layout();
    }

    public function duplicar()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'aOs')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para adicionar O.S.');
            redirect(base_url());
        }

        $idOrigem = $this->input->post('idOs');
        $clientesId = $this->input->post('clientes_id');

        if (! is_numeric($idOrigem) || ! is_numeric($clientesId)) {
            $this->session->set_flashdata('error', 'Selecione a OS de origem e o cliente/CNPJ de destino.');
            redirect(site_url('os'));
        }

        $novoId = $this->os_model->duplicarOs($idOrigem, $clientesId);

        if (is_numeric($novoId)) {
            $this->session->set_flashdata('success', 'OS #' . $idOrigem . ' copiada com sucesso para o cliente selecionado. Revise os dados da nova OS.');
            log_info('Duplicou a OS #' . $idOrigem . ' para nova OS #' . $novoId . ' (cliente ' . $clientesId . ').');
            redirect(site_url('os/editar/') . $novoId);
        }

        $this->session->set_flashdata('error', 'Ocorreu um erro ao copiar a OS.');
        redirect(site_url('os'));
    }

    public function editar()
    {
        if (! $this->uri->segment(3) || ! is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
            redirect('mapos');
        }

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para editar O.S.');
            redirect(base_url());
        }

        $this->load->library('form_validation');
        $this->data['custom_error'] = '';
        $this->data['texto_de_notificacao'] = $this->data['configuration']['notifica_whats'];

        $this->data['editavel'] = $this->os_model->isEditable($this->input->post('idOs'));
        if (! $this->data['editavel']) {
            $this->session->set_flashdata('error', 'Esta OS já e seu status não pode ser alterado e nem suas informações atualizadas. Por favor abrir uma nova OS.');

            redirect(site_url('os'));
        }

        if ($this->form_validation->run('os') == false) {
            $this->data['custom_error'] = (validation_errors() ? '<div class="form_error">' . validation_errors() . '</div>' : false);
        } else {
            $dataInicial = $this->input->post('dataInicial');
            $dataFinal = $this->input->post('dataFinal');
            $termoGarantiaId = $this->input->post('garantias_id') ?: null;

            try {
                $dataInicial = explode('/', $dataInicial);
                $dataInicial = $dataInicial[2] . '-' . $dataInicial[1] . '-' . $dataInicial[0];

                $dataFinal = explode('/', $dataFinal);
                $dataFinal = $dataFinal[2] . '-' . $dataFinal[1] . '-' . $dataFinal[0];
            } catch (Exception $e) {
                $dataInicial = date('Y/m/d');
            }

            $data = [
                'dataInicial' => $dataInicial,
                'dataFinal' => $dataFinal,
                'garantia' => $this->input->post('garantia'),
                'garantias_id' => $termoGarantiaId,
                'descricaoProduto' => $this->input->post('descricaoProduto'),
                'defeito' => $this->input->post('defeito'),
                'status' => $this->input->post('status'),
                'observacoes' => $this->input->post('observacoes'),
                'laudoTecnico' => $this->input->post('laudoTecnico'),
                'usuarios_id' => $this->input->post('usuarios_id'),
                'clientes_id' => $this->input->post('clientes_id'),
            ];
            $os = $this->os_model->getById($this->input->post('idOs'));

            //Verifica para poder fazer a devolução do produto para o estoque caso OS seja cancelada.

            // Handle material stock based on project linkage
            $vinculo = $this->obras_model->getVinculoDaOs((int) $this->input->post('idOs'));
            $destino = \Projeto_sync::decidirDestinoEstoque($vinculo, 'cancelar');
            if (strtolower($this->input->post('status')) == 'cancelado' && strtolower($os->status) != 'cancelado') {
                if ($destino === 'projeto') {
                    $this->projeto_sync->devolverMaterialDaOs((int) $this->input->post('idOs'));
                } else {
                    $this->devolucaoEstoque($this->input->post('idOs'));
                }
            }

            $destinoReabrir = \Projeto_sync::decidirDestinoEstoque($vinculo, 'reabrir');
            if (strtolower($os->status) == 'cancelado' && strtolower($this->input->post('status')) != 'cancelado') {
                if ($destinoReabrir === 'projeto') {
                    $this->projeto_sync->debitarMaterialDaOs((int) $this->input->post('idOs'));
                } else {
                    $this->debitarEstoque($this->input->post('idOs'));
                }
            }

            // Contrato/SLA e equipe (só grava se as colunas existirem).
            $data = array_merge($data, $this->montarCamposContratoEquipe($os));

            if ($this->os_model->edit('os', $data, 'idOs', $this->input->post('idOs')) == true) {
                $this->os_model->notificarMudancaStatus((int) $this->input->post('idOs'), $os->status, (string) $this->input->post('status'));
                $this->notificarEdicaoOs($this->input->post('idOs'));

                $this->session->set_flashdata('success', 'Os editada com sucesso!');
                log_info('Alterou uma OS. ID: ' . $this->input->post('idOs'));
                redirect(site_url('os/editar/') . $this->input->post('idOs'));
            } else {
                $this->data['custom_error'] = '<div class="form_error"><p>Ocorreu um erro</p></div>';
            }
        }

        $this->data['result'] = $this->os_model->getById($this->uri->segment(3));
        $this->data['produtos'] = $this->os_model->getProdutos($this->uri->segment(3));
        $this->data['servicos'] = $this->os_model->getServicos($this->uri->segment(3));
        $this->data['anexos'] = $this->os_model->getAnexos($this->uri->segment(3));
        $this->data['anotacoes'] = $this->os_model->getAnotacoes($this->uri->segment(3));

        // Contratos e equipes para os seletores da OS (resiliente aos módulos).
        $this->data['contratosSelect'] = [];
        $this->data['equipesSelect'] = [];
        $this->data['prioridadesSla'] = [];
        if ($this->db->table_exists('contratos')) {
            $this->load->model('contratos_model');
            $this->data['contratosSelect'] = $this->contratos_model->getContratosSelect();
            $this->data['prioridadesSla'] = array_keys(Contratos_model::prioridadesPadrao());
        }
        if ($this->db->table_exists('equipes')) {
            $this->load->model('obra_equipe_model');
            $this->data['equipesSelect'] = $this->obra_equipe_model->getEquipesSelect();
        }

        if ($return = $this->os_model->valorTotalOS($this->uri->segment(3))) {
            $this->data['totalServico'] = $return['totalServico'];
            $this->data['totalProdutos'] = $return['totalProdutos'];
        }

        $this->load->model('mapos_model');
        $this->data['emitente'] = $this->mapos_model->getEmitente();

        // Notas fiscais ativas desta OS (produtos = NF-e, serviços = NFS-e).
        // Protegido: o módulo fiscal pode ainda não ter sido migrado neste ambiente.
        $this->data['notaFiscal'] = null;
        $this->data['notaFiscalNfe'] = null;
        $this->data['notasFiscais'] = [];
        $this->data['boletosPorNota'] = [];
        if ($this->db->table_exists('notas_fiscais')) {
            $this->load->model('nfe_model');
            $this->data['notaFiscal'] = $this->nfe_model->getNotaAtiva('nfse', 'os_id', $this->uri->segment(3));
            $this->data['notaFiscalNfe'] = $this->nfe_model->getNotaAtiva('nfe', 'os_id', $this->uri->segment(3));
            $this->data['notasFiscais'] = $this->nfe_model->getNotasByOrigem('os_id', $this->uri->segment(3));
            $this->data['boletosPorNota'] = $this->carregarBoletosPorNota($this->data['notasFiscais']);
            $this->data['coraStage'] = $this->coraStageAtivo();
        }

        $this->carregarProjetoDaOs($this->uri->segment(3), $this->data['result']);
        $this->data['view'] = 'os/editarOs';

        return $this->layout();
    }

    /** Vinculo da OS com projeto (ADR-015) e dados para o modal de vincular. */
    private function carregarProjetoDaOs($osId, $os)
    {
        $this->load->model('obras_model');
        $perm = $this->session->userdata('permissao');
        $this->data['vinculoProjeto'] = $this->obras_model->getVinculoDaOs($osId);
        $this->load->library('projeto_sync');
        $this->data['podeFaturarOs'] = Projeto_sync::podeFaturarOsSozinha($this->data['vinculoProjeto']);
        $this->data['podeVerProjeto'] = $this->permission->checkPermission($perm, 'vObras');
        $this->data['podeVincularProjeto'] = $this->permission->checkPermission($perm, 'eObras')
            && $this->permission->checkPermission($perm, 'eOs');
        $this->data['projetosVincular'] = [];
        if ($this->data['podeVincularProjeto'] && ! $this->data['vinculoProjeto'] && $os) {
            $this->data['projetosVincular'] = $this->obras_model->getProjetosParaVincular($os->clientes_id);
        }

        // converter OS em projeto (ADR-015 fase 3)
        $this->data['podeCriarProjeto'] = $this->permission->checkPermission($perm, 'cObras');
        $this->data['osElegivelConversao'] = false;
        $this->data['tiposProjeto'] = [];
        if ($this->data['podeCriarProjeto'] && ! $this->data['vinculoProjeto'] && $os) {
            $this->load->model('obra_planejamento_model');
            $this->data['osElegivelConversao'] = in_array($os->status, Obra_planejamento_model::statusConversao(), true)
                && $this->db->table_exists('obra_etapa_itens');
            $this->data['tiposProjeto'] = ['Rede estruturada', 'CFTV IP', 'Rede estruturada + CFTV IP', 'Cabeamento óptico / fibra',
                'Controle de acesso', 'Alarme', 'Interfonia / vídeo porteiro', 'Controle de acesso + CFTV IP',
                'Infraestrutura (eletrocalha/eletroduto)', 'Manutenção'];
        }
    }

    /** Mapa os_id => vinculo (projeto/etapa) para as OS da listagem. */
    private function vinculosDasOs($results)
    {
        $mapa = [];
        if (! $results || ! $this->db->table_exists('obra_os')) {
            return $mapa;
        }
        $ids = array_map(function ($r) {
            return (int) $r->idOs;
        }, $results);
        $rows = $this->db->select('obra_os.os_id, obras.idObra, obras.nome as obra_nome, etapa.nome as etapa_nome')
            ->from('obra_os')
            ->join('obras', 'obras.idObra = obra_os.obra_id')
            ->join('obra_etapas etapa', 'etapa.idEtapa = obra_os.etapa_id', 'left')
            ->where_in('obra_os.os_id', $ids)
            ->get()->result();
        foreach ($rows as $row) {
            $mapa[(int) $row->os_id] = $row;
        }
        return $mapa;
    }

    /** Campos de contrato/SLA/equipe da OS. Delegacao: logica em libraries/Os_contrato.php */
    private function montarCamposContratoEquipe($osAtual)
    {
        $this->load->library('os_contrato');

        return $this->os_contrato->camposContratoEquipe($osAtual);
    }

    /** Abre o link publico de acompanhamento da OS (ADR-007); gera o token na primeira vez. */
    public function acompanhamento($id = null)
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar O.S.');
            redirect(base_url());
        }
        $this->load->model('acompanhamento_model');
        $token = $this->acompanhamento_model->tokenDaOs($id);
        if (! $token) {
            show_404();
        }
        redirect('acompanhar/' . $token);
    }

    public function visualizar()
    {
        if (! $this->uri->segment(3) || ! is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
            redirect('mapos');
        }

        $os_id = $this->uri->segment(3);

        // Verificar permissão básica
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar O.S.');
            redirect(base_url());
        }

        // Carregar dados da OS primeiro para verificar atribuição ao técnico
        $this->load->model('mapos_model');
        $this->load->model('checkin_model');
        $this->load->model('assinaturas_model');
        $this->load->model('fotosatendimento_model');

        $result = $this->os_model->getById($os_id);

        // Verificar se é técnico com permissão específica - só pode ver OS atribuídas a ele
        $permissao = $this->session->userdata('permissao');
        $idUsuario = $this->session->userdata('id_admin');

        // Se tem permissão de técnico específica (vTecnicoOS) mas NÃO tem permissão de OS geral (vOs completo)
        if ($this->permission->checkPermission($permissao, 'vTecnicoOS') &&
            !$this->permission->checkPermission($permissao, 'eOs')) {

            // Verificar se a OS está atribuída a este técnico
            if (!$result || $result->tecnico_responsavel != $idUsuario) {
                $this->session->set_flashdata('error', 'Você só pode visualizar ordens de serviço atribuídas a você.');
                redirect('tecnico');
            }
        }

        $this->data['custom_error'] = '';
        $this->data['texto_de_notificacao'] = $this->data['configuration']['notifica_whats'];

        $this->data['result'] = $result;
        $this->data['produtos'] = $this->os_model->getProdutos($this->uri->segment(3));
        $this->data['servicos'] = $this->os_model->getServicos($this->uri->segment(3));
        $this->data['emitente'] = $this->mapos_model->getEmitente();
        $this->data['anexos'] = $this->os_model->getAnexos($this->uri->segment(3));
        $this->data['anotacoes'] = $this->os_model->getAnotacoes($this->uri->segment(3));
        $this->data['editavel'] = $this->os_model->isEditable($this->uri->segment(3));

        // Exibição de valores: o técnico não precisa ver os valores da OS,
        // então ficam ocultos para esse perfil mesmo que tenha edição (eOs).
        $this->data['permissao_eOs'] = $this->permission->checkPermission($permissao, 'eOs')
            && ! $this->permission->checkPermission($permissao, 'vTecnicoDashboard');

        // Dados do sistema de checkin
        $os_id = $this->uri->segment(3);
        $this->data['checkins'] = $this->checkin_model->getAllByOs($os_id);
        $this->data['checkinAtivo'] = $this->checkin_model->getCheckinAtivo($os_id);

        // Batidas de ponto (RH) vinculadas a esta OS — presença em campo.
        $this->load->model('rh_ponto_model');
        $this->data['batidasPonto'] = $this->rh_ponto_model->getByOs($os_id);

        // Linha do tempo ("onde estamos") da OS.
        $this->data['timeline'] = $this->os_model->getTimeline($os_id);
        $this->data['assinaturas'] = $this->assinaturas_model->getByOs($os_id);
        log_info('OS Visualizar - Assinaturas carregadas: ' . count($this->data['assinaturas']));
        $this->data['fotosAtendimento'] = $this->fotosatendimento_model->getByOs($os_id);

        // Respostas dos formulários de atendimento personalizados (agrupadas por etapa)
        $this->load->model('formularios_atendimento_model', 'formularios');
        $respostasPorEtapa = [];
        foreach ($this->formularios->getRespostasByOs($os_id) as $respostaFormulario) {
            $etapaResp = $respostaFormulario->etapa ?: 'outros';
            $respostasPorEtapa[$etapaResp][] = $respostaFormulario;
        }
        $this->data['respostasFormularios'] = $respostasPorEtapa;

        $this->data['qrCode'] = $this->os_model->getQrCode(
            $this->uri->segment(3),
            $this->data['configuration']['pix_key'],
            $this->data['emitente']
        );
        $this->data['modalGerarPagamento'] = $this->load->view(
            'cobrancas/modalGerarPagamento',
            [
                'id' => $this->uri->segment(3),
                'tipo' => 'os',
            ],
            true
        );
        $this->carregarProjetoDaOs($os_id, $result);
        $this->data['view'] = 'os/visualizarOs';
        $this->data['chaveFormatada'] = formatar_chave_pix($this->data['configuration']['pix_key']);

        if ($return = $this->os_model->valorTotalOS($this->uri->segment(3))) {
            $this->data['totalServico'] = $return['totalServico'];
            $this->data['totalProdutos'] = $return['totalProdutos'];
        }

        // Notas fiscais ativas desta OS (produtos = NF-e, serviços = NFS-e).
        // Protegido: o módulo fiscal pode ainda não ter sido migrado neste ambiente.
        $this->data['notaFiscal'] = null;
        $this->data['notaFiscalNfe'] = null;
        $this->data['notasFiscais'] = [];
        $this->data['boletosPorNota'] = [];
        if ($this->db->table_exists('notas_fiscais')) {
            $this->load->model('nfe_model');
            $this->data['notaFiscal'] = $this->nfe_model->getNotaAtiva('nfse', 'os_id', $os_id);
            $this->data['notaFiscalNfe'] = $this->nfe_model->getNotaAtiva('nfe', 'os_id', $os_id);
            $this->data['notasFiscais'] = $this->nfe_model->getNotasByOrigem('os_id', $os_id);
            $this->data['boletosPorNota'] = $this->carregarBoletosPorNota($this->data['notasFiscais']);
            $this->data['coraStage'] = $this->coraStageAtivo();
        }

        return $this->layout();
    }

    /**
     * Mapa de boletos (cobranças) indexado por nota_id, para exibir o boleto
     * Cora vinculado a cada nota fiscal na aba de Notas Fiscais da OS.
     */
    private function carregarBoletosPorNota($notas)
    {
        if (empty($notas) || ! $this->db->field_exists('nota_id', 'cobrancas')) {
            return [];
        }
        $ids = array_map(fn ($n) => $n->idNota, $notas);
        $this->load->model('cobrancas_model');

        // Lista (todas as parcelas) por nota — a aba de notas exibe cada boleto.
        return $this->cobrancas_model->getListByNotaIds($ids);
    }

    /**
     * Cora configurada, ativa e em ambiente de Stage (homologação) — habilita
     * o botão de simular pagamento na aba de notas.
     */
    private function coraStageAtivo()
    {
        if (! $this->db->table_exists('configuracoes_cora')) {
            return false;
        }
        $this->load->model('Cora_model');
        $cfg = $this->Cora_model->getConfig();

        return $cfg && $cfg->ativo && ! $cfg->producao;
    }

    public function imprimir()
    {
        if (! $this->uri->segment(3) || ! is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
            redirect('mapos');
        }

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar O.S.');
            redirect(base_url());
        }

        $this->data['custom_error'] = '';
        $this->load->model('mapos_model');
        $this->load->model('assinaturas_model');
        $this->data['result'] = $this->os_model->getById($this->uri->segment(3));
        $this->data['produtos'] = $this->os_model->getProdutos($this->uri->segment(3));
        $this->data['servicos'] = $this->os_model->getServicos($this->uri->segment(3));
        $this->data['anexos'] = $this->os_model->getAnexos($this->uri->segment(3));
        $this->data['emitente'] = $this->mapos_model->getEmitente();
        $this->data['assinaturas'] = $this->assinaturas_model->getByOs($this->uri->segment(3));
        log_info('OS Imprimir - OS ID: ' . $this->uri->segment(3) . ' - Assinaturas: ' . count($this->data['assinaturas']));
        if (!empty($this->data['assinaturas'])) {
            foreach ($this->data['assinaturas'] as $assinatura) {
                log_info('OS Imprimir - Assinatura tipo: ' . $assinatura->tipo . ' - Caminho: ' . $assinatura->assinatura);
            }
        }
        if ($this->data['configuration']['pix_key']) {
            $this->data['qrCode'] = $this->os_model->getQrCode(
                $this->uri->segment(3),
                $this->data['configuration']['pix_key'],
                $this->data['emitente']
            );
            $this->data['chaveFormatada'] = formatar_chave_pix($this->data['configuration']['pix_key']);
        }
        
        $this->data['imprimirAnexo'] = isset($_ENV['IMPRIMIR_ANEXOS']) ? (filter_var($_ENV['IMPRIMIR_ANEXOS'] ?? false, FILTER_VALIDATE_BOOLEAN)) : false;
        $this->data['permissao_eOs'] = $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')
            && ! $this->permission->checkPermission($this->session->userdata('permissao'), 'vTecnicoDashboard');

        $this->load->view('os/imprimirOs', $this->data);
    }

    public function imprimirTermica()
    {
        if (! $this->uri->segment(3) || ! is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
            redirect('mapos');
        }

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar O.S.');
            redirect(base_url());
        }

        $this->data['custom_error'] = '';
        $this->load->model('mapos_model');
        $this->data['result'] = $this->os_model->getById($this->uri->segment(3));
        $this->data['produtos'] = $this->os_model->getProdutos($this->uri->segment(3));
        $this->data['servicos'] = $this->os_model->getServicos($this->uri->segment(3));
        $this->data['emitente'] = $this->mapos_model->getEmitente();
        $this->data['qrCode'] = $this->os_model->getQrCode(
            $this->uri->segment(3),
            $this->data['configuration']['pix_key'],
            $this->data['emitente']
        );
        $this->data['chaveFormatada'] = formatar_chave_pix($this->data['configuration']['pix_key']);
        $this->data['permissao_eOs'] = $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')
            && ! $this->permission->checkPermission($this->session->userdata('permissao'), 'vTecnicoDashboard');

        $this->load->view('os/imprimirOsTermica', $this->data);
    }

    public function enviar_email()
    {
        if (! $this->uri->segment(3) || ! is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
            redirect('mapos');
        }

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para enviar O.S. por e-mail.');
            redirect(base_url());
        }

        $this->load->model('mapos_model');
        $this->load->model('usuarios_model');
        $this->data['result'] = $this->os_model->getById($this->uri->segment(3));
        if (! isset($this->data['result']->email)) {
            $this->session->set_flashdata('error', 'O cliente não tem e-mail cadastrado.');
            redirect(site_url('os'));
        }

        $this->data['produtos'] = $this->os_model->getProdutos($this->uri->segment(3));
        $this->data['servicos'] = $this->os_model->getServicos($this->uri->segment(3));
        $this->data['emitente'] = $this->mapos_model->getEmitente();

        if (! isset($this->data['emitente']->email)) {
            $this->session->set_flashdata('error', 'Efetue o cadastro dos dados de emitente');
            redirect(site_url('os'));
        }

        $idOs = $this->uri->segment(3);

        $emitente = $this->data['emitente'];
        $tecnico = $this->usuarios_model->getById($this->data['result']->usuarios_id);

        // Verificar configuração de notificação
        $ValidarEmail = false;
        if ($this->data['configuration']['os_notification'] != 'nenhum') {
            $remetentes = [];
            switch ($this->data['configuration']['os_notification']) {
                case 'todos':
                    array_push($remetentes, $this->data['result']->email);
                    array_push($remetentes, $tecnico->email);
                    array_push($remetentes, $emitente->email);
                    $ValidarEmail = true;
                    break;
                case 'cliente':
                    array_push($remetentes, $this->data['result']->email);
                    $ValidarEmail = true;
                    break;
                case 'tecnico':
                    array_push($remetentes, $tecnico->email);
                    break;
                case 'emitente':
                    array_push($remetentes, $emitente->email);
                    break;
                default:
                    array_push($remetentes, $this->data['result']->email);
                    $ValidarEmail = true;
                    break;
            }

            if ($ValidarEmail) {
                if (empty($this->data['result']->email) || ! filter_var($this->data['result']->email, FILTER_VALIDATE_EMAIL)) {
                    $this->session->set_flashdata('error', 'Por favor preencha o email do cliente');
                    redirect(site_url('os/visualizar/') . $this->uri->segment(3));
                }
            }

            $enviouEmail = $this->enviarOsPorEmail($idOs, $remetentes, 'Ordem de Serviço');

            if ($enviouEmail) {
                $this->session->set_flashdata('success', 'O email está sendo processado e será enviado em breve.');
                log_info('Enviou e-mail para o cliente: ' . $this->data['result']->nomeCliente . '. E-mail: ' . $this->data['result']->email);
                redirect(site_url('os'));
            } else {
                $this->session->set_flashdata('error', 'Ocorreu um erro ao enviar e-mail.');
                redirect(site_url('os'));
            }
        }

        $this->session->set_flashdata('success', 'O sistema está com uma configuração ativada para não notificar. Entre em contato com o administrador.');
        redirect(site_url('os'));
    }

    /**
     * Acoes que alteram itens da OS (chamadas por AJAX na tela de edicao) exigem permissao de editar OS.
     */
    private function exigirEdicaoOs()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')) {
            $this->output->set_status_header(403)->set_content_type('application/json')
                ->set_output(json_encode(['result' => false, 'mensagem' => 'Você não tem permissão para editar O.S.']))
                ->_display();
            exit;
        }
    }

    private function devolucaoEstoque($id)
    {
        // OS cobrada no projeto (ADR-015): devolve ao almoxarifado do projeto; ao
        // estoque geral so volta o que foi lancado antes do vinculo (sem consumo).
        $this->load->library('projeto_sync');
        $resProjeto = $this->projeto_sync->devolverMaterialDaOs((int) $id);
        if ($resProjeto['origem'] === 'obra') {
            if (! empty($resProjeto['sem_consumo']) && $this->data['configuration']['control_estoque']) {
                $this->load->model('produtos_model');
                foreach ($resProjeto['sem_consumo'] as $produtoId => $qtd) {
                    $this->produtos_model->updateEstoque($produtoId, $qtd, '+', ['tipo' => 'os', 'referencia' => $id, 'observacao' => 'OS cancelada ou excluída']);
                }
            }
            return;
        }

        if ($produtos = $this->os_model->getProdutos($id)) {
            $this->load->model('produtos_model');
            if ($this->data['configuration']['control_estoque']) {
                foreach ($produtos as $p) {
                    $this->produtos_model->updateEstoque($p->produtos_id, $p->quantidade, '+', ['tipo' => 'os', 'referencia' => $id, 'observacao' => 'OS cancelada ou excluída']);
                }
            }
        }
    }

    private function debitarEstoque($id)
    {
        // OS cobrada no projeto (ADR-015): debita do almoxarifado do projeto
        $this->load->library('projeto_sync');
        $resProjeto = $this->projeto_sync->debitarMaterialDaOs((int) $id);
        if ($resProjeto['origem'] === 'obra') {
            foreach ($resProjeto['avisos'] ?? [] as $aviso) {
                log_info('Os::editar - ' . $aviso);
            }
            return;
        }

        if ($produtos = $this->os_model->getProdutos($id)) {
            $this->load->model('produtos_model');
            if ($this->data['configuration']['control_estoque']) {
                foreach ($produtos as $p) {
                    $this->produtos_model->updateEstoque($p->produtos_id, $p->quantidade, '-', ['tipo' => 'os', 'referencia' => $id, 'observacao' => 'OS reaberta (saiu de Cancelado)']);
                }
            }
        }
    }

    public function excluir()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'dOs')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para excluir O.S.');
            redirect(base_url());
        }

        $id = $this->input->post('id');
        $os = $this->os_model->getByIdCobrancas($id);
        if ($os == null) {
            $os = $this->os_model->getById($id);
            if ($os == null) {
                $this->session->set_flashdata('error', 'Erro ao tentar excluir OS.');
                redirect(base_url() . 'index.php/os/gerenciar/');
            }
        }

        if (isset($os->idCobranca) != null) {
            if ($os->status == 'canceled') {
                $this->os_model->delete('cobrancas', 'os_id', $id);
            } else {
                $this->session->set_flashdata('error', 'Existe uma cobrança associada a esta OS, deve cancelar e/ou excluir a cobrança primeiro!');
                redirect(site_url('os/gerenciar/'));
            }
        }

        $osStockRefund = $this->os_model->getById($id);
        //Verifica para poder fazer a devolução do produto para o estoque caso OS seja excluida.
        if (strtolower($osStockRefund->status) != 'cancelado') {
            // Handle material based on project linkage
            $vinculo = $this->obras_model->getVinculoDaOs((int) $id);
            $destino = \Projeto_sync::decidirDestinoEstoque($vinculo, 'excluir');
            if ($destino === 'projeto') {
                $this->projeto_sync->devolverMaterialDaOs((int) $id);
            } else {
                $this->devolucaoEstoque($id);
            }
        }

        // tira o vinculo com projeto e pontos (historico dos pontos fica)
        $this->load->library('projeto_sync');
        $this->projeto_sync->removerVinculoDaOs((int) $id);

        $this->os_model->delete('servicos_os', 'os_id', $id);
        $this->os_model->delete('produtos_os', 'os_id', $id);
        $this->os_model->delete('anexos', 'os_id', $id);
        $this->os_model->delete('os', 'idOs', $id);
        if ((int) $os->faturado === 1) {
            $this->os_model->delete('lancamentos', 'descricao', "Fatura de OS - #${id}");
        }

        log_info('Removeu uma OS. ID: ' . $id);
        $this->session->set_flashdata('success', 'OS excluída com sucesso!');
        redirect(site_url('os/gerenciar/'));
    }

    public function autoCompleteProduto()
    {
        if (isset($_GET['term'])) {
            $q = strtolower($_GET['term']);
            $this->os_model->autoCompleteProduto($q);
        }
    }

    public function autoCompleteProdutoSaida()
    {
        if (isset($_GET['term'])) {
            $q = strtolower($_GET['term']);
            $this->os_model->autoCompleteProdutoSaida($q);
        }
    }

    public function autoCompleteCliente()
    {
        if (isset($_GET['term'])) {
            $q = strtolower($_GET['term']);
            $this->os_model->autoCompleteCliente($q);
        }
    }

    public function autoCompleteUsuario()
    {
        if (isset($_GET['term'])) {
            $q = strtolower($_GET['term']);
            $this->os_model->autoCompleteUsuario($q);
        }
    }

    public function autoCompleteTermoGarantia()
    {
        if (isset($_GET['term'])) {
            $q = strtolower($_GET['term']);
            $this->os_model->autoCompleteTermoGarantia($q);
        }
    }

    public function autoCompleteServico()
    {
        if (isset($_GET['term'])) {
            $q = strtolower($_GET['term']);
            $this->os_model->autoCompleteServico($q);
        }
    }

    public function adicionarProduto()
    {
        $this->exigirEdicaoOs();

        $this->load->library('form_validation');

        if ($this->form_validation->run('adicionar_produto_os') === false) {
            $errors = validation_errors();

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode($errors));
        }

        $preco = $this->input->post('preco');
        $quantidade = $this->input->post('quantidade');
        $subtotal = $preco * $quantidade;
        $produto = $this->input->post('idProduto');
        $data = [
            'quantidade' => $quantidade,
            'subTotal' => $subtotal,
            'produtos_id' => $produto,
            'preco' => $preco,
            'os_id' => $this->input->post('idOsProduto'),
        ];

        $id = $this->input->post('idOsProduto');
        $os = $this->os_model->getById($id);
        if ($os == null) {
            $this->session->set_flashdata('error', 'Erro ao tentar inserir produto na OS.');
            redirect(base_url() . 'index.php/os/gerenciar/');
        }

        if ($this->os_model->add('produtos_os', $data) == true) {
            $this->load->model('produtos_model');

            // Projeto (ADR-015): OS vinculada com fatura_no_projeto baixa do
            // almoxarifado do projeto, nao do estoque geral.
            $this->load->library('projeto_sync');
            $resProjeto = $this->projeto_sync->aplicarProdutoDaOs((int) $id, (int) $produto, (float) $quantidade, 'adicionar');

            if ($resProjeto['origem'] !== 'obra' && $this->data['configuration']['control_estoque']) {
                $this->produtos_model->updateEstoque($produto, $quantidade, '-', ['tipo' => 'os', 'referencia' => $id]);
            }
            if (! empty($resProjeto['aviso'])) {
                log_info('Os::adicionarProduto - ' . $resProjeto['aviso']);
            }

            $this->db->set('desconto', 0.00);
            $this->db->set('valor_desconto', 0.00);
            $this->db->set('tipo_desconto', null);
            $this->db->where('idOs', $id);
            $this->db->update('os');

            log_info('Adicionou produto a uma OS. ID (OS): ' . $this->input->post('idOsProduto'));

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode(['result' => true, 'aviso' => $resProjeto['aviso'] ?? null]));
        } else {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['result' => false]));
        }
    }

    public function excluirProduto()
    {
        $this->exigirEdicaoOs();

        $id = $this->input->post('idProduto');
        $idOs = $this->input->post('idOs');

        $os = $this->os_model->getById($idOs);
        if ($os == null) {
            $this->session->set_flashdata('error', 'Erro ao tentar excluir produto na OS.');
            redirect(base_url() . 'index.php/os/gerenciar/');
        }

        // Produto e quantidade vem do item gravado (nao do navegador) e o item tem de ser desta OS
        $item = $this->db->where('idProdutos_os', $id)->where('os_id', $idOs)->get('produtos_os')->row();
        if (! $item) {
            echo json_encode(['result' => false]);

            return;
        }

        if ($this->os_model->delete('produtos_os', 'idProdutos_os', $id) == true) {
            $this->load->model('produtos_model');

            // Projeto (ADR-015): se a OS vinculada baixava do almoxarifado do
            // projeto, devolve ali; senao, devolve ao estoque geral (como hoje).
            $this->load->library('projeto_sync');
            $resProjeto = $this->projeto_sync->aplicarProdutoDaOs((int) $idOs, (int) $item->produtos_id, (float) $item->quantidade, 'remover');

            if ($resProjeto['origem'] !== 'obra' && $this->data['configuration']['control_estoque']) {
                $this->produtos_model->updateEstoque($item->produtos_id, $item->quantidade, '+', ['tipo' => 'os', 'referencia' => $idOs, 'observacao' => 'Produto removido da OS']);
            }

            $this->db->set('desconto', 0.00);
            $this->db->set('valor_desconto', 0.00);
            $this->db->set('tipo_desconto', null);
            $this->db->where('idOs', $idOs);
            $this->db->update('os');

            log_info('Removeu produto de uma OS. ID (OS): ' . $idOs);

            echo json_encode(['result' => true]);
        } else {
            echo json_encode(['result' => false]);
        }
    }

    public function adicionarServico()
    {
        $this->exigirEdicaoOs();

        $this->load->library('form_validation');

        if ($this->form_validation->run('adicionar_servico_os') === false) {
            $errors = validation_errors();

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode($errors));
        }

        $data = [
            'servicos_id' => $this->input->post('idServico'),
            'quantidade' => $this->input->post('quantidade'),
            'preco' => $this->input->post('preco'),
            'os_id' => $this->input->post('idOsServico'),
            'subTotal' => $this->input->post('preco') * $this->input->post('quantidade'),
        ];

        if ($this->os_model->add('servicos_os', $data) == true) {
            log_info('Adicionou serviço a uma OS. ID (OS): ' . $this->input->post('idOsServico'));

            $this->db->set('desconto', 0.00);
            $this->db->set('valor_desconto', 0.00);
            $this->db->set('tipo_desconto', null);
            $this->db->where('idOs', $this->input->post('idOsServico'));
            $this->db->update('os');

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode(['result' => true]));
        } else {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['result' => false]));
        }
    }

    public function excluirServico()
    {
        $this->exigirEdicaoOs();

        $ID = $this->input->post('idServico');
        $idOs = $this->input->post('idOs');

        if ($this->os_model->delete('servicos_os', 'idServicos_os', $ID) == true) {
            log_info('Removeu serviço de uma OS. ID (OS): ' . $idOs);
            $this->db->set('desconto', 0.00);
            $this->db->set('valor_desconto', 0.00);
            $this->db->set('tipo_desconto', null);
            $this->db->where('idOs', $idOs);
            $this->db->update('os');
            echo json_encode(['result' => true]);
        } else {
            echo json_encode(['result' => false]);
        }
    }

    public function editarProduto()
    {
        $this->load->library('form_validation');

        $rules = [
            [
                'field' => 'idProdutoOs',
                'label' => 'ID Produto OS',
                'rules' => 'trim|required|numeric',
            ],
            [
                'field' => 'quantidade',
                'label' => 'quantidade',
                'rules' => 'trim|required|numeric|greater_than[0]',
            ],
            [
                'field' => 'preco',
                'label' => 'preco',
                'rules' => 'trim|required|numeric|greater_than[-1]',
            ],
        ];

        $this->form_validation->set_rules($rules);

        if ($this->form_validation->run() === false) {
            $errors = validation_errors();
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode($errors));
        }

        $idProdutoOs = $this->input->post('idProdutoOs');
        $quantidade = $this->input->post('quantidade');
        $preco = $this->input->post('preco');
        $subtotal = $preco * $quantidade;

        // Item como estava antes da edicao: a OS e o produto vem do banco (nao do
        // navegador) pra ajustar a fonte certa de material (ADR-015).
        $itemAntes = $this->db->where('idProdutos_os', $idProdutoOs)->get('produtos_os')->row();

        $data = [
            'quantidade' => $quantidade,
            'preco' => $preco,
            'subTotal' => $subtotal,
        ];

        $this->db->where('idProdutos_os', $idProdutoOs);
        if ($this->db->update('produtos_os', $data)) {
            if ($itemAntes) {
                $this->load->library('projeto_sync');
                $resProjeto = $this->projeto_sync->aplicarProdutoDaOs((int) $itemAntes->os_id, (int) $itemAntes->produtos_id, (float) $quantidade, 'ajustar');
                if (! empty($resProjeto['aviso'])) {
                    log_info('Os::editarProduto - ' . $resProjeto['aviso']);
                }
            }

            $this->db->set('desconto', 0.00);
            $this->db->set('valor_desconto', 0.00);
            $this->db->set('tipo_desconto', null);
            $this->db->where('idOs', $this->input->post('idOs'));
            $this->db->update('os');

            log_info('Editou produto da OS. ID (Produto OS): ' . $idProdutoOs);

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode(['result' => true]));
        } else {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['result' => false]));
        }
    }

    public function zerarProdutos()
    {
        $idOs = $this->input->post('idOs');

        if (! is_numeric($idOs)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['result' => false]));
        }

        $os = $this->os_model->getById($idOs);
        if ($os == null) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(404)
                ->set_output(json_encode(['result' => false]));
        }

        $this->db->set('preco', 0.00);
        $this->db->set('subTotal', 0.00);
        $this->db->where('os_id', $idOs);
        if ($this->db->update('produtos_os')) {
            $this->db->set('desconto', 0.00);
            $this->db->set('valor_desconto', 0.00);
            $this->db->set('tipo_desconto', null);
            $this->db->where('idOs', $idOs);
            $this->db->update('os');

            log_info('Zerou o valor dos produtos de uma OS. ID (OS): ' . $idOs);

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode(['result' => true]));
        } else {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['result' => false]));
        }
    }

    public function editarServico()
    {
        $this->load->library('form_validation');

        $rules = [
            [
                'field' => 'idServicoOs',
                'label' => 'ID Servico OS',
                'rules' => 'trim|required|numeric',
            ],
            [
                'field' => 'quantidade',
                'label' => 'quantidade',
                'rules' => 'trim|required|numeric|greater_than[0]',
            ],
            [
                'field' => 'preco',
                'label' => 'preco',
                'rules' => 'trim|required|numeric|greater_than[-1]',
            ],
        ];

        $this->form_validation->set_rules($rules);

        if ($this->form_validation->run() === false) {
            $errors = validation_errors();
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode($errors));
        }

        $idServicoOs = $this->input->post('idServicoOs');
        $quantidade = $this->input->post('quantidade');
        $preco = $this->input->post('preco');
        $subtotal = $preco * $quantidade;

        $data = [
            'quantidade' => $quantidade,
            'preco' => $preco,
            'subTotal' => $subtotal,
        ];

        $this->db->where('idServicos_os', $idServicoOs);
        if ($this->db->update('servicos_os', $data)) {
            $this->db->set('desconto', 0.00);
            $this->db->set('valor_desconto', 0.00);
            $this->db->set('tipo_desconto', null);
            $this->db->where('idOs', $this->input->post('idOs'));
            $this->db->update('os');

            log_info('Editou serviço da OS. ID (Servico OS): ' . $idServicoOs);

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode(['result' => true]));
        } else {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['result' => false]));
        }
    }

    /* Anexos: logica em libraries/Os_anexos.php (ADR-006) */

    public function anexar()
    {
        $this->exigirEdicaoOs();
        $this->load->library('os_anexos');
        echo json_encode($this->os_anexos->anexar($this->input->post('idOsServico')));
    }

    public function excluirAnexo($id = null)
    {
        $this->exigirEdicaoOs();
        $this->load->library('os_anexos');
        echo json_encode($this->os_anexos->excluir($id));
    }

    public function downloadanexo($id = null)
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar O.S.');
            redirect(base_url());
        }
        $this->load->library('os_anexos');
        $arquivo = $this->os_anexos->arquivo($id);
        if ($arquivo === null) {
            show_404();
        }

        $this->load->library('zip');
        $this->zip->read_file($arquivo);
        $this->zip->download('file' . date('d-m-Y-H.i.s') . '.zip');
    }

    public function adicionarDesconto()
    {
        $this->exigirEdicaoOs();

        if ($this->input->post('desconto') == '') {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['messages' => 'Campo desconto vazio']));
        } else {
            $idOs = $this->input->post('idOs');
            $data = [
                'tipo_desconto' => $this->input->post('tipoDesconto'),
                'desconto' => $this->input->post('desconto'),
                'valor_desconto' => $this->input->post('resultado'),
            ];
            $editavel = $this->os_model->isEditable($idOs);
            if (! $editavel) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(400)
                    ->set_output(json_encode(['result' => false, 'messages', 'Desconto não pode ser adiciona. Os não ja Faturada/Cancelada']));
            }
            if ($this->os_model->edit('os', $data, 'idOs', $idOs) == true) {
                log_info('Adicionou um desconto na OS. ID: ' . $idOs);

                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(201)
                    ->set_output(json_encode(['result' => true, 'messages' => 'Desconto adicionado com sucesso!']));
            } else {
                log_info('Ocorreu um erro ao tentar adiciona desconto a OS: ' . $idOs);

                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(400)
                    ->set_output(json_encode(['result' => false, 'messages', 'Ocorreu um erro ao tentar adiciona desconto a OS.']));
            }
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(400)
            ->set_output(json_encode(['result' => false, 'messages', 'Ocorreu um erro ao tentar adiciona desconto a OS.']));
    }

    public function faturar()
    {
        // OS cobrada pela medicao do projeto nao fatura sozinha (ADR-015 decisao 6)
        $osIdFaturar = (int) $this->input->post('os_id');
        if ($osIdFaturar) {
            $this->load->model('obras_model');
            $this->load->library('projeto_sync');
            $vinculo = $this->obras_model->getVinculoDaOs($osIdFaturar);
            if (! Projeto_sync::podeFaturarOsSozinha($vinculo)) {
                $msg = 'Esta OS é cobrada pela medição do projeto ' . $vinculo->obra_nome . '. Fature pela medição.';
                $this->session->set_flashdata('error', $msg);

                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['result' => false, 'mensagem' => $msg]));
            }
        }

        $this->load->library('form_validation');
        $this->data['custom_error'] = '';

        if ($this->form_validation->run('receita') == false) {
            $this->data['custom_error'] = (validation_errors() ? '<div class="form_error">' . validation_errors() . '</div>' : false);
        } else {
            $vencimento = $this->input->post('vencimento');
            $recebimento = $this->input->post('recebimento');

            try {
                $vencimento = DateTime::createFromFormat('d/m/Y', $vencimento)->format('Y-m-d');
                if ($recebimento != null) {
                    $recebimento = DateTime::createFromFormat('d/m/Y', $recebimento)->format('Y-m-d');
                }
            } catch (Exception $e) {
                $vencimento = date('Y-m-d');
            }

            $os_id = $this->input->post('os_id');
            $osAntesFaturar = $this->os_model->getById($os_id);
            $statusAnteriorFaturar = $osAntesFaturar ? $osAntesFaturar->status : null;
            $valorTotalData =$this->os_model->valorTotalOS($os_id);

            $valorTotalServico = $valorTotalData['totalServico'];
            $valorTotalProduto = $valorTotalData['totalProdutos'];
            $valorDesconto = $valorTotalData['valor_desconto'];

            $valorTotal = $valorTotalServico + $valorTotalProduto;
            $valorTotalComDesconto = $valorTotal - $valorDesconto;

            $data = [
                'descricao' => set_value('descricao'),
                'valor' => $valorTotal,
                'tipo_desconto' => 'real',
                'desconto' => ($valorDesconto > 0) ? $valorTotalComDesconto : 0,
                'valor_desconto' => ($valorDesconto > 0) ? $valorDesconto : $valorTotal,
                'clientes_id' => $this->input->post('clientes_id'),
                'data_vencimento' => $vencimento,
                'data_pagamento' => $recebimento,
                'baixado' => $this->input->post('recebido') ?: 0,
                'cliente_fornecedor' => set_value('cliente'),
                'forma_pgto' => $this->input->post('formaPgto'),
                'tipo' => $this->input->post('tipo'),
                'observacoes' => set_value('observacoes'),
                'usuarios_id' => $this->session->userdata('id_admin'),
            ];

            $this->db->trans_start();

            $editavel = $this->os_model->isEditable($os_id);
            if (!$editavel) {
                $this->db->trans_rollback();
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(400)
                    ->set_output(json_encode(['result' => false]));
            }

            if ($this->os_model->add('lancamentos', $data)) {
                $this->db->set('faturado', 1);
                $this->db->set('valorTotal', $valorTotal);

                if ($valorDesconto > 0) {
                    $this->db->set('desconto', $valorTotalComDesconto);
                    $this->db->set('valor_desconto', $valorDesconto);
                } else {
                    $this->db->set('desconto', 0);
                    $this->db->set('valor_desconto', $valorTotal);
                }

                $this->db->set('status', 'Faturado');
                $this->db->where('idOs', $os_id);
                $this->db->update('os');

                log_info('Faturou uma OS. ID: ' . $os_id);

                $this->db->trans_complete();

                if ($this->db->trans_status() === FALSE) {
                    $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar faturar OS.');
                    $json = ['result' => false];
                } else {
                    $this->os_model->notificarMudancaStatus((int) $os_id, $statusAnteriorFaturar, 'Faturado');
                    $this->session->set_flashdata('success', 'OS faturada com sucesso!');
                    $json = ['result' => true];
                }
            } else {
                $this->db->trans_rollback();
                $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar faturar OS.');
                $json = ['result' => false];
            }

            echo json_encode($json);
            exit();
        }

        $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar faturar OS.');
        $json = ['result' => false];
        echo json_encode($json);
    }

    /**
     * Notificacoes de OS editada: e-mail (conforme configuracao) e WhatsApp
     * (gatilhos os_editada / os_finalizada). Usado pela edicao da OS e pela
     * troca rapida de status (listagem e Central de Atendimento).
     */
    private function notificarEdicaoOs($idOs)
    {
        $this->load->model('mapos_model');
        $this->load->model('usuarios_model');

        $idOs = (int) $idOs;

        $os = $this->os_model->getById($idOs);
        $emitente = $this->mapos_model->getEmitente();
        $tecnico = $this->usuarios_model->getById($os->usuarios_id);

        // Verificar configuração de notificação
        if ($this->data['configuration']['os_notification'] != 'nenhum' && $this->data['configuration']['email_automatico'] == 1) {
            $remetentes = [];
            switch ($this->data['configuration']['os_notification']) {
                case 'todos':
                    array_push($remetentes, $os->email);
                    array_push($remetentes, $tecnico->email);
                    array_push($remetentes, $emitente->email);
                    break;
                case 'cliente':
                    array_push($remetentes, $os->email);
                    break;
                case 'tecnico':
                    array_push($remetentes, $tecnico->email);
                    break;
                case 'emitente':
                    array_push($remetentes, $emitente->email);
                    break;
                default:
                    array_push($remetentes, $os->email);
                    break;
            }
            $this->enviarOsPorEmail($idOs, $remetentes, 'Ordem de Serviço - Editada', 'os_editada');
        }

        // Notificação automática por WhatsApp (Evolution API). Falha aqui
        // NUNCA deve impedir a edição da OS — só registra no log e segue.
        // Ao finalizar, dispara também o evento os_finalizada.
        $eventosWpp = ['os_editada'];
        if (strtolower($os->status) === 'finalizado') {
            $eventosWpp[] = 'os_finalizada';
        }
        $this->notificarWhatsAppAutomatico($os, $emitente, $eventosWpp);
    }

    /** Delegacao: logica em libraries/Os_email.php */
    private function enviarOsPorEmail($idOs, $remetentes, $assunto, $evento = null)
    {
        $this->load->library('os_email');

        return $this->os_email->enviar($idOs, $remetentes, $assunto, $evento);
    }

    /**
     * Liga/desliga a automação de aprovação (NFS-e + boleto) apenas para esta OS.
     * POST: idOs, valor (0 = desativar nesta OS, 1 = ativar nesta OS). Retorna JSON.
     */
    public function toggleAutomacao()
    {
        $perm = $this->session->userdata('permissao');
        if (! $this->permission->checkPermission($perm, 'cAutomacao')
            && ! $this->permission->checkPermission($perm, 'cSistema')) {
            return $this->output->set_content_type('application/json')->set_status_header(403)
                ->set_output(json_encode(['success' => false, 'message' => 'Sem permissão para alterar a automação.']));
        }

        $idOs = $this->input->post('idOs');
        $valor = (string) $this->input->post('valor');
        if (! $idOs || ! is_numeric($idOs) || ! in_array($valor, ['0', '1'], true)) {
            return $this->output->set_content_type('application/json')->set_status_header(400)
                ->set_output(json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']));
        }

        $this->os_model->edit('os', ['automacao_override' => (int) $valor], 'idOs', $idOs);
        log_info('Alterou a automação da OS #' . $idOs . ' para ' . ($valor === '1' ? 'ativa' : 'desativada'));

        return $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'valor' => (int) $valor]));
    }

    public function adicionarAnotacao()
    {
        $this->load->library('form_validation');
        if ($this->form_validation->run('anotacoes_os') == false) {
            echo json_encode(validation_errors());
        } else {
            $data = [
                'anotacao' => '[' . $this->session->userdata('nome_admin') . '] ' . $this->input->post('anotacao'),
                'data_hora' => date('Y-m-d H:i:s'),
                'os_id' => $this->input->post('os_id'),
            ];

            if ($this->os_model->add('anotacoes_os', $data) == true) {
                log_info('Adicionou anotação a uma OS. ID (OS): ' . $this->input->post('os_id'));
                echo json_encode(['result' => true]);
            } else {
                echo json_encode(['result' => false]);
            }
        }
    }

    public function excluirAnotacao()
    {
        $id = $this->input->post('idAnotacao');
        $idOs = $this->input->post('idOs');

        if ($this->os_model->delete('anotacoes_os', 'idAnotacoes', $id) == true) {
            log_info('Removeu anotação de uma OS. ID (OS): ' . $idOs);
            echo json_encode(['result' => true]);
        } else {
            echo json_encode(['result' => false]);
        }
    }

    /**
     * Statuses que podem ser aplicados pela Central de Atendimento (mudança
     * inline). Exclui os terminais (Finalizado/Faturado/Cancelado) e o
     * "Não Realizado" porque têm efeitos colaterais (estoque, faturamento,
     * ocorrência de não realizado) que devem passar pela OS ou pelo fluxo próprio.
     */
    private function statusChamadoPermitidos()
    {
        return ['Aberto', 'Em Andamento', 'Orçamento', 'Negociação', 'Aprovado', 'Aguardando Peças'];
    }

    /**
     * CENTRAL DE ATENDIMENTO: gestão dos chamados (OS) + atribuição de técnicos.
     * Layout híbrido: faixa de indicadores (KPIs) + abas por situação do
     * atendimento (Todos / Sem Técnico / Em Atendimento / Não Realizadas).
     * Acesso pela permissão eOs (Editar OS).
     */
    public function atribuir()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para acessar a Central de Atendimento.');
            redirect(base_url());
        }

        $this->load->model('tecnico_model');
        $this->load->model('naorealizada_model');
        $this->load->library('pagination');
        $this->load->helper('text');
        $this->load->model('os_model');

        // Aba ativa (situação do atendimento). Mantém compatibilidade com o
        // parâmetro antigo ?filtro= usado por links/bookmarks.
        $aba = $this->input->get('aba') ?: $this->input->get('filtro') ?: 'todos';
        if ($aba === 'com_tecnico') {
            $aba = 'em_atendimento';
        }

        // Filtros de busca e período (aplicados às abas baseadas em OS).
        $busca    = trim((string) $this->input->get('busca'));
        $data_de  = trim((string) $this->input->get('data_de'));
        $data_ate = trim((string) $this->input->get('data_ate'));
        $tecnico  = (int) $this->input->get('tecnico');
        $filtros  = ['busca' => $busca, 'data_de' => $data_de, 'data_ate' => $data_ate, 'tecnico' => $tecnico];

        // Configuração da paginação
        $config['base_url'] = site_url('os/atribuir');
        $config['per_page'] = 20;
        $config['reuse_query_string'] = TRUE;
        $config['page_query_string'] = TRUE;
        $config['query_string_segment'] = 'page';
        $config['first_link'] = 'Primeira';
        $config['last_link'] = 'Última';
        $config['next_link'] = 'Próxima';
        $config['prev_link'] = 'Anterior';
        $config['full_tag_open'] = '<div class="pagination"><ul>';
        $config['full_tag_close'] = '</ul></div>';
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="active"><a href="#">';
        $config['cur_tag_close'] = '</a></li>';
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';

        // Offset baseado na query string
        $page = $this->input->get('page') ? (int)$this->input->get('page') : 0;

        if ($aba == 'nao_realizadas') {
            // Tratada à parte (lista de ocorrências), sem paginação nesta versão.
            $ordens = [];
            $config['total_rows'] = 0;
        } else {
            // Abas baseadas em OS (todos, sem_tecnico, em_atendimento, finalizados)
            // com filtros de busca e período aplicados de forma unificada.
            if (! in_array($aba, ['todos', 'sem_tecnico', 'em_atendimento', 'finalizados', 'atrasados', 'hoje'], true)) {
                $aba = 'todos';
            }
            $ordens = $this->os_model->getOsCentral($aba, $filtros, $config['per_page'], $page);
            $config['total_rows'] = $this->os_model->countOsCentral($aba, $filtros);
        }

        $this->data['ordens'] = $ordens ?: [];
        $this->data['aba'] = $aba;
        $this->data['busca'] = $busca;
        $this->data['data_de'] = $data_de;
        $this->data['data_ate'] = $data_ate;
        $this->data['tecnicoFiltro'] = $tecnico;

        // Ocorrências de "Não Realizado" pendentes (visão de gestão, todos os técnicos)
        $this->data['naoRealizadas'] = $this->naorealizada_model->getPendentes(null, 100);

        // Atrasados / agendados hoje / carga por tecnico (contagens simples)
        $concluidos = ['Finalizado', 'Cancelado', 'Faturado'];
        $agora = date('Y-m-d H:i:s');
        $this->db->where('data_agendamento IS NOT NULL')->where('data_agendamento <', $agora)->where_not_in('status', $concluidos);
        $qtdAtrasados = (int) $this->db->count_all_results('os');
        $this->db->where('data_agendamento >=', date('Y-m-d') . ' 00:00:00')->where('data_agendamento <=', date('Y-m-d') . ' 23:59:59')->where_not_in('status', $concluidos);
        $qtdHoje = (int) $this->db->count_all_results('os');

        $cargaTecnicos = [];
        $this->db->select('tecnico_responsavel, COUNT(*) AS abertas, SUM(CASE WHEN data_agendamento >= ' . $this->db->escape(date('Y-m-d') . ' 00:00:00') . ' AND data_agendamento <= ' . $this->db->escape(date('Y-m-d') . ' 23:59:59') . ' THEN 1 ELSE 0 END) AS hoje', false);
        $this->db->where('tecnico_responsavel IS NOT NULL')->where_not_in('status', $concluidos)->group_by('tecnico_responsavel');
        $qCarga = $this->db->get('os');
        if ($qCarga) {
            foreach ($qCarga->result() as $linha) {
                $cargaTecnicos[(int) $linha->tecnico_responsavel] = ['abertas' => (int) $linha->abertas, 'hoje' => (int) $linha->hoje];
            }
        }
        $this->data['cargaTecnicos'] = $cargaTecnicos;

        // Indicadores (KPIs) do topo
        $this->data['kpis'] = [
            'atrasados'      => $qtdAtrasados,
            'hoje'           => $qtdHoje,
            'total'          => $this->tecnico_model->countOsParaAtribuicao(),
            'sem_tecnico'    => $this->tecnico_model->countOsSemTecnico(),
            'em_atendimento' => $this->tecnico_model->countOsComTecnico(),
            'aguardando'     => $this->os_model->contarPorStatus(['Aguardando Peças', 'Aprovado', 'Orçamento', 'Negociação']),
            'nao_realizadas' => $this->naorealizada_model->contarPendentes(),
            'finalizados'    => $this->os_model->contarPorStatus(['Finalizado', 'Faturado']),
        ];

        // Inicializar paginação
        $this->pagination->initialize($config);
        $this->data['pagination'] = $this->pagination->create_links();

        // Carregar lista de técnicos (apenas usuários de grupos com permissão de técnico)
        $this->data['tecnicos'] = $this->tecnico_model->getTecnicosPorPermissao();

        // Statuses aplicáveis inline
        $this->data['statusDisponiveis'] = $this->statusChamadoPermitidos();

        // Ativar menu
        $this->data['menuAtribuir'] = 'Atribuir';
        $this->data['view'] = 'os/atribuir_tecnico';

        return $this->layout();
    }

    /**
     * Ação AJAX: altera o status do chamado direto pela Central de Atendimento
     * e pela listagem de OS. Restrita aos statuses de fluxo (statusChamadoPermitidos),
     * evitando os terminais que têm efeitos colaterais (estoque/faturamento).
     * Marca o cronômetro de SLA e dispara as notificações de OS editada.
     */
    public function alterarStatusAction()
    {
        if (! $this->input->is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
            return;
        }
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão para alterar o status.']);
            return;
        }

        $os_id  = (int) $this->input->post('os_id');
        $status = trim((string) $this->input->post('status'));

        if (! $os_id || $status === '') {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
            return;
        }
        if (! in_array($status, $this->statusChamadoPermitidos(), true)) {
            echo json_encode(['success' => false, 'message' => 'Status não permitido por aqui. Use a OS para cancelar, finalizar ou faturar.']);
            return;
        }

        $os = $this->os_model->getById($os_id);
        if (! $os) {
            echo json_encode(['success' => false, 'message' => 'OS não encontrada.']);
            return;
        }
        if (in_array($os->status, ['Finalizado', 'Faturado', 'Cancelado'], true)) {
            echo json_encode(['success' => false, 'message' => 'Esta OS está ' . $os->status . ' e não pode mudar de status por aqui.']);
            return;
        }
        if ($os->status === 'Não Realizado') {
            echo json_encode(['success' => false, 'message' => 'OS em espera. Use "Reagendar" ou "Reabrir" na aba Não Realizadas.']);
            return;
        }

        // Mesmo comportamento da edicao da OS: cronometro de SLA e notificacoes
        $this->load->library('os_contrato');
        $extra = $this->os_contrato->camposCronometroSla($os, $status);

        if ($this->os_model->alterarStatus($os_id, $status, $extra)) {
            log_info('Alterou status da OS #' . $os_id . ' para "' . $status . '" pela troca rapida de status.');
            $this->notificarEdicaoOs($os_id);
            echo json_encode(['success' => true, 'message' => 'Status da OS #' . $os_id . ' atualizado para ' . $status . '.', 'status' => $status]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o status.']);
        }
    }

    /** Valida AJAX + permissao de projeto/OS; responde JSON e retorna false se negar. */
    private function exigirVinculoProjeto()
    {
        if (! $this->input->is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
            return false;
        }
        $perm = $this->session->userdata('permissao');
        if (! $this->permission->checkPermission($perm, 'eObras') || ! $this->permission->checkPermission($perm, 'eOs')) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão para vincular OS a projetos.']);
            return false;
        }
        $this->load->model('obras_model');
        return true;
    }

    /** Ação AJAX: vincula a OS a um projeto (e etapa) do mesmo cliente. */
    public function vincularProjetoAction()
    {
        if (! $this->exigirVinculoProjeto()) {
            return;
        }
        $os_id = (int) $this->input->post('os_id');
        $obra_id = (int) $this->input->post('obra_id');
        $etapa_id = (int) $this->input->post('etapa_id');
        $tipo = (string) $this->input->post('tipo_vinculo');
        if (! in_array($tipo, ['origem', 'execucao', 'adicional', 'corretiva'], true)) {
            $tipo = 'execucao';
        }
        $fatura = $this->input->post('fatura_no_projeto') ? 1 : 0;

        $os = $os_id ? $this->os_model->getById($os_id) : null;
        if (! $os || ! $obra_id) {
            echo json_encode(['success' => false, 'message' => 'Selecione a OS e o projeto.']);
            return;
        }
        $existente = $this->obras_model->getVinculoDaOs($os_id);
        if ($existente) {
            echo json_encode(['success' => false, 'message' => 'Esta OS já está no projeto "' . $existente->obra_nome . '". Desvincule antes de vincular a outro.']);
            return;
        }
        $obra = $this->db->get_where('obras', ['idObra' => $obra_id])->row();
        if (! $obra || (int) $obra->clientes_id !== (int) $os->clientes_id) {
            echo json_encode(['success' => false, 'message' => 'O projeto precisa ser do mesmo cliente da OS.']);
            return;
        }
        if (in_array($obra->status, ['concluida', 'entregue', 'cancelada'], true)) {
            echo json_encode(['success' => false, 'message' => 'O projeto não está em aberto.']);
            return;
        }
        if ($etapa_id && ! $this->db->get_where('obra_etapas', ['idEtapa' => $etapa_id, 'obra_id' => $obra_id])->row()) {
            echo json_encode(['success' => false, 'message' => 'A etapa não pertence ao projeto.']);
            return;
        }
        if ($this->obras_model->vincularOs($obra_id, $os_id, $etapa_id ?: null, $tipo, $fatura)) {
            log_info('Vinculou a OS ' . $os_id . ' ao projeto ' . $obra_id . ' pela tela da OS');
            echo json_encode(['success' => true, 'message' => 'OS vinculada ao projeto.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Não foi possível vincular a OS ao projeto.']);
        }
    }

    /** Ação AJAX: remove o vínculo da OS com o projeto. */
    public function desvincularProjetoAction()
    {
        if (! $this->exigirVinculoProjeto()) {
            return;
        }
        $os_id = (int) $this->input->post('os_id');
        $vinculo = $os_id ? $this->obras_model->getVinculoDaOs($os_id) : null;
        if (! $vinculo) {
            echo json_encode(['success' => false, 'message' => 'Esta OS não está vinculada a um projeto.']);
            return;
        }
        $this->obras_model->desvincularOs($vinculo->idVinculo, $vinculo->idObra);
        log_info('Desvinculou a OS ' . $os_id . ' do projeto ' . $vinculo->idObra . ' pela tela da OS');
        echo json_encode(['success' => true, 'message' => 'OS desvinculada do projeto.']);
    }

    /** Ação AJAX (GET): etapas de um projeto para popular o select. */
    public function etapasProjetoAction()
    {
        if (! $this->exigirVinculoProjeto()) {
            return;
        }
        $etapas = [];
        foreach ($this->obras_model->getEtapasSelect((int) $this->input->get('obra_id')) as $e) {
            $etapas[] = ['id' => (int) $e->idEtapa, 'nome' => trim(($e->codigo_eap ? $e->codigo_eap . ' ' : '') . $e->nome)];
        }
        echo json_encode(['success' => true, 'etapas' => $etapas]);
    }

    /**
     * Ação AJAX: resolve uma ocorrência de "Não Realizado" pela Central
     * (perfil de gestão), reagendando para nova data ou reabrindo para refazer.
     */
    public function resolverNaoRealizadaAction()
    {
        if (! $this->input->is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
            return;
        }
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão.']);
            return;
        }

        $this->load->model('naorealizada_model');
        $ocorrencia_id = (int) $this->input->post('ocorrencia_id');
        $acao          = $this->input->post('acao');
        $usuario       = $this->session->userdata('id_admin');

        $oc = $this->naorealizada_model->getOcorrencia($ocorrencia_id);
        if (! $oc) {
            echo json_encode(['success' => false, 'message' => 'Ocorrência não encontrada.']);
            return;
        }

        if ($acao === 'reagendar') {
            $nova_data = trim((string) $this->input->post('nova_data'));
            if ($nova_data === '' || ! strtotime($nova_data)) {
                echo json_encode(['success' => false, 'message' => 'Informe uma data válida para o reagendamento.']);
                return;
            }
            if (! $this->naorealizada_model->reagendar($ocorrencia_id, $nova_data, $usuario)) {
                echo json_encode(['success' => false, 'message' => 'Não foi possível reagendar (talvez já resolvida).']);
                return;
            }
            log_info('Reagendou OS não realizada pela Central. OS ID: ' . $oc->os_id);
            echo json_encode(['success' => true, 'message' => 'OS #' . $oc->os_id . ' reagendada para ' . date('d/m/Y', strtotime($nova_data)) . '.']);
            return;
        }

        if ($acao === 'reabrir') {
            if (! $this->naorealizada_model->reabrir($ocorrencia_id, $usuario)) {
                echo json_encode(['success' => false, 'message' => 'Não foi possível reabrir (talvez já resolvida).']);
                return;
            }
            log_info('Reabriu OS não realizada pela Central. OS ID: ' . $oc->os_id);
            echo json_encode(['success' => true, 'message' => 'OS #' . $oc->os_id . ' reaberta para refazer.']);
            return;
        }

        echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
    }

    /**
     * Ação AJAX para atribuir técnico à OS
     */
    public function atribuirTecnicoAction()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para atribuir técnicos.');
            redirect('os/atribuir');
        }

        $os_id = $this->input->post('os_id');
        $tecnico_id = $this->input->post('tecnico_id');
        $observacao = $this->input->post('observacao');
        $agendamento = $this->input->post('data_agendamento');

        if (! $os_id || ! $tecnico_id) {
            $this->session->set_flashdata('error', 'Dados incompletos para atribuição.');
            redirect('os/atribuir');
        }

        $this->load->model('tecnico_model');

        if (! $this->tecnico_model->isTecnicoValido($tecnico_id)) {
            $this->session->set_flashdata('error', 'Usuário selecionado não é um técnico válido.');
            redirect('os/atribuir');
        }

        $atribuido_por = $this->session->userdata('id_admin');

        if ($this->tecnico_model->atribuirTecnico($os_id, $tecnico_id, $atribuido_por, $observacao)) {
            // Agendamento do atendimento (data + hora). O input datetime-local
            // manda "Y-m-d\TH:i"; normaliza para DATETIME do banco.
            $this->salvarAgendamentoOs($os_id, $agendamento);
            $this->notificarTecnicoAtribuicao($os_id, $tecnico_id);
            $this->session->set_flashdata('success', 'Técnico atribuído à OS #' . $os_id . ' com sucesso!');
            log_info('Atribuiu técnico ' . $tecnico_id . ' à OS #' . $os_id);
        } else {
            $this->session->set_flashdata('error', 'Erro ao atribuir técnico. Verifique se já não está atribuído.');
        }

        redirect('os/atribuir');
    }

    /**
     * Grava a data/hora agendada do atendimento na OS. O input datetime-local
     * envia "Y-m-d\TH:i"; normaliza para DATETIME. Valor vazio limpa o agendamento.
     */
    private function salvarAgendamentoOs($os_id, $agendamento)
    {
        if (! $this->db->field_exists('data_agendamento', 'os')) {
            return; // migration ainda não aplicada
        }
        $valor = null;
        if (! empty($agendamento)) {
            $ts = strtotime(str_replace('T', ' ', $agendamento));
            if ($ts) {
                $valor = date('Y-m-d H:i:s', $ts);
            }
        }
        $this->os_model->edit('os', ['data_agendamento' => $valor], 'idOs', $os_id);
    }

    /**
     * Notifica o técnico (WhatsApp via Evolution API + e-mail na fila) quando
     * recebe uma OS. Blindado: qualquer falha só é registrada e não impede a
     * atribuição.
     */
    private function notificarTecnicoAtribuicao($os_id, $tecnico_id)
    {
        try {
            $this->load->model('usuarios_model');
            $this->load->model('mapos_model');
            $tecnico = $this->usuarios_model->getById($tecnico_id);
            $os = $this->os_model->getById($os_id);
            if (! $tecnico || ! $os) {
                return;
            }

            $emitente = $this->mapos_model->getEmitente();
            $endereco = trim(($os->rua ?? '') . ' ' . ($os->numero ?? '') . ' ' . ($os->bairro ?? '') . ' ' . ($os->cidade ?? ''));
            $agendaTxt = ! empty($os->data_agendamento)
                ? "\nAgendado para: " . date('d/m/Y \à\s H:i', strtotime($os->data_agendamento))
                : '';
            $msg = "Olá {$tecnico->nome}! Você recebeu a OS #{$os_id}."
                . "\nCliente: " . ($os->nomeCliente ?? '')
                . "\nEquipamento/Defeito: " . strip_tags((string) ($os->defeito ?? ''))
                . $agendaTxt
                . ($endereco !== '' ? "\nEndereço: {$endereco}" : '')
                . ($emitente ? "\n\n{$emitente->nome}" : '');

            // WhatsApp (se a Evolution API estiver ativa)
            $zap = $tecnico->celular ?: $tecnico->telefone;
            if (! empty($zap)) {
                $this->load->library('evolution_api');
                if ($this->evolution_api->estaAtivo()) {
                    $this->evolution_api->enviarTexto($zap, $msg, ['tipo' => 'tecnico_atribuicao', 'os_id' => $os_id]);
                    log_info('Notificou técnico #' . $tecnico_id . ' por WhatsApp da OS #' . $os_id);
                }
            }

            // E-mail na fila
            if (! empty($tecnico->email) && $emitente) {
                $this->load->model('email_model');
                $headers = [
                    'From' => $emitente->email,
                    'Subject' => 'Nova OS atribuída a você - #' . $os_id,
                    'Return-Path' => '',
                ];
                $this->email_model->add('email_queue', [
                    'to' => $tecnico->email,
                    'message' => nl2br(htmlspecialchars($msg)),
                    'status' => 'pending',
                    'date' => date('Y-m-d H:i:s'),
                    'headers' => serialize($headers),
                ]);
            }
        } catch (\Exception $e) {
            log_info('Falha ao notificar técnico da OS #' . $os_id . ': ' . $e->getMessage());
        }
    }

    /**
     * Ação para remover técnico da OS
     */
    public function removerTecnicoAction()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para remover técnicos das OS.');
            redirect('os/atribuir');
        }

        $os_id = $this->input->post('os_id');
        $motivo = $this->input->post('motivo');

        if (! $os_id) {
            $this->session->set_flashdata('error', 'OS não informada.');
            redirect('os/atribuir');
        }

        $this->load->model('tecnico_model');

        if ($this->tecnico_model->removerTecnico($os_id, $motivo)) {
            $this->session->set_flashdata('success', 'Técnico removido da OS #' . $os_id . ' com sucesso!');
            log_info('Removeu técnico da OS #' . $os_id);
        } else {
            $this->session->set_flashdata('error', 'Erro ao remover técnico da OS.');
        }

        redirect('os/atribuir');
    }

    /**
     * Visualizar histórico de atribuições de uma OS
     */
    public function historicoAtribuicoes($os_id = null)
    {
        if (! $os_id || ! is_numeric($os_id)) {
            echo json_encode(['error' => 'OS inválida']);
            return;
        }

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            echo json_encode(['error' => 'Sem permissão']);
            return;
        }

        $this->load->model('tecnico_model');

        $historico = $this->tecnico_model->getHistoricoAtribuicoes($os_id);

        echo json_encode($historico);
    }

    /**
     * Gera (ou regenera) o link público e temporário de aprovação de uma OS.
     * Retorna JSON com a URL para ser enviada ao cliente.
     */
    public function gerarLinkAprovacao()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(403)
                ->set_output(json_encode(['result' => false, 'mensagem' => 'Sem permissão.']));
        }

        $osId = $this->input->post('idOs');
        if (! is_numeric($osId) || ! $this->os_model->getById($osId)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(404)
                ->set_output(json_encode(['result' => false, 'mensagem' => 'OS não encontrada.']));
        }

        $this->load->model('aprovacao_model');
        if (! $this->aprovacao_model->suportado()) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['result' => false, 'mensagem' => 'Recurso indisponível: execute as migrations (php index.php tools migrate).']));
        }

        $dias = (int) $this->input->post('dias') ?: 7;
        $info = $this->aprovacao_model->gerarLink($osId, $dias);

        if (! $info) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['result' => false, 'mensagem' => 'Erro ao gerar o link.']));
        }

        // Exigência de código de verificação direto na OS (checkbox do modal).
        if ($this->aprovacao_model->suportaVerificacao()) {
            $this->aprovacao_model->setExigeTokenOs($osId, (int) $this->input->post('exige_token') === 1);
            $this->aprovacao_model->setTokenNumerosOs($osId, $this->input->post('token_numeros'));
        }

        log_info('Gerou link de aprovação para a OS #' . $osId);

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode([
                'result' => true,
                'url' => site_url('aprovacao/' . $info['token']),
                'expira' => date('d/m/Y', strtotime($info['expira'])),
            ]));
    }

    /**
     * Revoga o link de aprovação ativo de uma OS.
     */
    public function revogarLinkAprovacao()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(403)
                ->set_output(json_encode(['result' => false, 'mensagem' => 'Sem permissão.']));
        }

        $osId = $this->input->post('idOs');
        if (! is_numeric($osId)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['result' => false]));
        }

        $this->load->model('aprovacao_model');
        $this->aprovacao_model->revogarLink($osId);
        log_info('Revogou link de aprovação da OS #' . $osId);

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode(['result' => true]));
    }

    /**
     * Gera (ou regenera) o link público de ACEITE do serviço realizado.
     * Retorna JSON com a URL para envio ao cliente.
     */
    public function gerarLinkAceite()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(403)
                ->set_output(json_encode(['result' => false, 'mensagem' => 'Sem permissão.']));
        }

        $osId = $this->input->post('idOs');
        if (! is_numeric($osId) || ! $this->os_model->getById($osId)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(404)
                ->set_output(json_encode(['result' => false, 'mensagem' => 'OS não encontrada.']));
        }

        $this->load->model('aceite_model');
        if (! $this->aceite_model->suportado()) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['result' => false, 'mensagem' => 'Recurso indisponível: execute a atualização do banco (updates/update_os_aceite.sql).']));
        }

        $dias = (int) $this->input->post('dias') ?: 7;
        $info = $this->aceite_model->gerarLink($osId, $dias);
        if (! $info) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['result' => false, 'mensagem' => 'Erro ao gerar o link.']));
        }

        log_info('Gerou link de aceite para a OS #' . $osId);

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode([
                'result' => true,
                'url' => site_url('aceite/' . $info['token']),
                'expira' => date('d/m/Y', strtotime($info['expira'])),
            ]));
    }

    /**
     * Revoga o link de aceite ativo de uma OS.
     */
    public function revogarLinkAceite()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(403)
                ->set_output(json_encode(['result' => false, 'mensagem' => 'Sem permissão.']));
        }

        $osId = $this->input->post('idOs');
        if (! is_numeric($osId)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['result' => false]));
        }

        $this->load->model('aceite_model');
        $this->aceite_model->revogarLink($osId);
        log_info('Revogou link de aceite da OS #' . $osId);

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode(['result' => true]));
    }

    /**
     * Dispara a notificação por WhatsApp (Evolution API) quando o status da OS
     * está na lista configurada em WHATSAPP_EVOLUTION_AUTO_STATUS.
     *
     * Blindado com try/catch: qualquer falha de WhatsApp é apenas registrada e
     * não interrompe o fluxo de criação/edição da OS.
     *
     * @param object $os       OS já carregada (getById), com celular_cliente e status
     * @param object $emitente Emitente para preencher as tags do template
     */
    private function notificarWhatsAppAutomatico($os, $emitente, $evento = null)
    {
        if (! $os) {
            return;
        }
        $this->load->library('notificador');
        $this->notificador->whatsappOs($os->idOs, $evento, $emitente);
    }
}
