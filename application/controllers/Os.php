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
            $this->data['configuration']['suffix'] = "?pesquisa={$pesquisa}&status={$status}&data={$inputDe}&data2={$inputAte}";
            $this->data['configuration']['first_url'] = base_url("index.php/os/gerenciar")."\?pesquisa={$pesquisa}&status={$status}&data={$inputDe}&data2={$inputAte}";
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

        $this->data['texto_de_notificacao'] = $this->data['configuration']['notifica_whats'];
        $this->data['emitente'] = $this->mapos_model->getEmitente();
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

            if (strtolower($this->input->post('status')) == 'cancelado' && strtolower($os->status) != 'cancelado') {
                $this->devolucaoEstoque($this->input->post('idOs'));
            }

            if (strtolower($os->status) == 'cancelado' && strtolower($this->input->post('status')) != 'cancelado') {
                $this->debitarEstoque($this->input->post('idOs'));
            }

            if ($this->os_model->edit('os', $data, 'idOs', $this->input->post('idOs')) == true) {
                $this->load->model('mapos_model');
                $this->load->model('usuarios_model');

                $idOs = $this->input->post('idOs');

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

        $this->data['view'] = 'os/editarOs';

        return $this->layout();
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
        $this->data['view'] = 'os/visualizarOs';
        $this->data['chaveFormatada'] = $this->formatarChave($this->data['configuration']['pix_key']);

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

    public function validarCPF($cpf)
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1+$/', $cpf)) {
            return false;
        }
        $soma1 = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma1 += $cpf[$i] * (10 - $i);
        }
        $resto1 = $soma1 % 11;
        $dv1 = ($resto1 < 2) ? 0 : 11 - $resto1;
        if ($dv1 != $cpf[9]) {
            return false;
        }
        $soma2 = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma2 += $cpf[$i] * (11 - $i);
        }
        $resto2 = $soma2 % 11;
        $dv2 = ($resto2 < 2) ? 0 : 11 - $resto2;

        return $dv2 == $cpf[10];
    }

    public function validarCNPJ($cnpj)
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1+$/', $cnpj)) {
            return false;
        }
        $soma1 = 0;
        for ($i = 0, $pos = 5; $i < 12; $i++, $pos--) {
            $pos = ($pos < 2) ? 9 : $pos;
            $soma1 += $cnpj[$i] * $pos;
        }
        $dv1 = ($soma1 % 11 < 2) ? 0 : 11 - ($soma1 % 11);
        if ($dv1 != $cnpj[12]) {
            return false;
        }
        $soma2 = 0;
        for ($i = 0, $pos = 6; $i < 13; $i++, $pos--) {
            $pos = ($pos < 2) ? 9 : $pos;
            $soma2 += $cnpj[$i] * $pos;
        }
        $dv2 = ($soma2 % 11 < 2) ? 0 : 11 - ($soma2 % 11);

        return $dv2 == $cnpj[13];
    }

    public function formatarChave($chave)
    {
        if ($this->validarCPF($chave)) {
            return substr($chave, 0, 3) . '.' . substr($chave, 3, 3) . '.' . substr($chave, 6, 3) . '-' . substr($chave, 9);
        } elseif ($this->validarCNPJ($chave)) {
            return substr($chave, 0, 2) . '.' . substr($chave, 2, 3) . '.' . substr($chave, 5, 3) . '/' . substr($chave, 8, 4) . '-' . substr($chave, 12);
        } elseif (strlen($chave) === 11) {
            return '(' . substr($chave, 0, 2) . ') ' . substr($chave, 2, 5) . '-' . substr($chave, 7);
        }

        return $chave;
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

        return $this->cobrancas_model->getByNotaIds($ids);
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
            $this->data['chaveFormatada'] = $this->formatarChave($this->data['configuration']['pix_key']);
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
        $this->data['chaveFormatada'] = $this->formatarChave($this->data['configuration']['pix_key']);
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

    private function devolucaoEstoque($id)
    {
        if ($produtos = $this->os_model->getProdutos($id)) {
            $this->load->model('produtos_model');
            if ($this->data['configuration']['control_estoque']) {
                foreach ($produtos as $p) {
                    $this->produtos_model->updateEstoque($p->produtos_id, $p->quantidade, '+');
                    log_info('ESTOQUE: Produto id ' . $p->produtos_id . ' voltou ao estoque. Quantidade: ' . $p->quantidade . '. Motivo: Cancelamento/Exclusão');
                }
            }
        }
    }

    private function debitarEstoque($id)
    {
        if ($produtos = $this->os_model->getProdutos($id)) {
            $this->load->model('produtos_model');
            if ($this->data['configuration']['control_estoque']) {
                foreach ($produtos as $p) {
                    $this->produtos_model->updateEstoque($p->produtos_id, $p->quantidade, '-');
                    log_info('ESTOQUE: Produto id ' . $p->produtos_id . ' baixa do estoque. Quantidade: ' . $p->quantidade . '. Motivo: Mudou status que já estava Cancelado para outro');
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
            $this->devolucaoEstoque($id);
        }

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

            if ($this->data['configuration']['control_estoque']) {
                $this->produtos_model->updateEstoque($produto, $quantidade, '-');
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
                ->set_output(json_encode(['result' => true]));
        } else {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['result' => false]));
        }
    }

    public function excluirProduto()
    {
        $id = $this->input->post('idProduto');
        $idOs = $this->input->post('idOs');

        $os = $this->os_model->getById($idOs);
        if ($os == null) {
            $this->session->set_flashdata('error', 'Erro ao tentar excluir produto na OS.');
            redirect(base_url() . 'index.php/os/gerenciar/');
        }

        if ($this->os_model->delete('produtos_os', 'idProdutos_os', $id) == true) {
            $quantidade = $this->input->post('quantidade');
            $produto = $this->input->post('produto');

            $this->load->model('produtos_model');

            if ($this->data['configuration']['control_estoque']) {
                $this->produtos_model->updateEstoque($produto, $quantidade, '+');
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

        $data = [
            'quantidade' => $quantidade,
            'preco' => $preco,
            'subTotal' => $subtotal,
        ];

        $this->db->where('idProdutos_os', $idProdutoOs);
        if ($this->db->update('produtos_os', $data)) {
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

    public function anexar()
    {
        $this->load->library('upload');
        $this->load->library('image_lib');

        $directory = FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'anexos' . DIRECTORY_SEPARATOR . date('m-Y') . DIRECTORY_SEPARATOR . 'OS-' . $this->input->post('idOsServico');

        // If it exist, check if it's a directory
        if (! is_dir($directory . DIRECTORY_SEPARATOR . 'thumbs')) {
            // make directory for images and thumbs
            try {
                mkdir($directory . DIRECTORY_SEPARATOR . 'thumbs', 0755, true);
            } catch (Exception $e) {
                echo json_encode(['result' => false, 'mensagem' => $e->getMessage()]);
                exit();
            }
        }

        $upload_conf = [
            'upload_path' => $directory,
            'allowed_types' => 'jpg|png|gif|jpeg|JPG|PNG|GIF|JPEG|pdf|PDF|cdr|CDR|docx|DOCX|txt', // formatos permitidos para anexos de os
            'max_size' => 0,
        ];

        $this->upload->initialize($upload_conf);

        foreach ($_FILES['userfile'] as $key => $val) {
            $i = 1;
            foreach ($val as $v) {
                $field_name = 'file_' . $i;
                $_FILES[$field_name][$key] = $v;
                $i++;
            }
        }
        unset($_FILES['userfile']);

        $error = [];
        $success = [];

        foreach ($_FILES as $field_name => $file) {
            if (! $this->upload->do_upload($field_name)) {
                $error['upload'][] = $this->upload->display_errors();
            } else {
                $upload_data = $this->upload->data();

                // Gera um nome de arquivo aleatório mantendo a extensão original
                $new_file_name = uniqid() . '.' . pathinfo($upload_data['file_name'], PATHINFO_EXTENSION);
                $new_file_path = $upload_data['file_path'] . $new_file_name;

                rename($upload_data['full_path'], $new_file_path);

                if ($upload_data['is_image'] == 1) {
                    $resize_conf = [
                        'source_image' => $new_file_path,
                        'new_image' => $upload_data['file_path'] . 'thumbs' . DIRECTORY_SEPARATOR . 'thumb_' . $new_file_name,
                        'width' => 200,
                        'height' => 125,
                    ];

                    $this->image_lib->initialize($resize_conf);

                    if (! $this->image_lib->resize()) {
                        $error['resize'][] = $this->image_lib->display_errors();
                    } else {
                        $success[] = $upload_data;
                        $this->load->model('Os_model');
                        $result = $this->Os_model->anexar($this->input->post('idOsServico'), $new_file_name, base_url('assets' . DIRECTORY_SEPARATOR . 'anexos' . DIRECTORY_SEPARATOR . date('m-Y') . DIRECTORY_SEPARATOR . 'OS-' . $this->input->post('idOsServico')), 'thumb_' . $new_file_name, $directory);
                        if (! $result) {
                            $error['db'][] = 'Erro ao inserir no banco de dados.';
                        }
                    }
                } else {
                    $success[] = $upload_data;

                    $this->load->model('Os_model');

                    $result = $this->Os_model->anexar($this->input->post('idOsServico'), $new_file_name, base_url('assets' . DIRECTORY_SEPARATOR . 'anexos' . DIRECTORY_SEPARATOR . date('m-Y') . DIRECTORY_SEPARATOR . 'OS-' . $this->input->post('idOsServico')), '', $directory);
                    if (! $result) {
                        $error['db'][] = 'Erro ao inserir no banco de dados.';
                    }
                }
            }
        }

        if (count($error) > 0) {
            echo json_encode(['result' => false, 'mensagem' => 'Ocorreu um erro ao processar os arquivos.', 'errors' => $error]);
        } else {
            log_info('Adicionou anexo(s) a uma OS. ID (OS): ' . $this->input->post('idOsServico'));
            echo json_encode(['result' => true, 'mensagem' => 'Arquivo(s) anexado(s) com sucesso.']);
        }
    }

    public function excluirAnexo($id = null)
    {
        if ($id == null || ! is_numeric($id)) {
            echo json_encode(['result' => false, 'mensagem' => 'Erro ao tentar excluir anexo.']);
        } else {
            $this->db->where('idAnexos', $id);
            $file = $this->db->get('anexos', 1)->row();
            $idOs = $this->input->post('idOs');

            unlink($file->path . DIRECTORY_SEPARATOR . $file->anexo);

            if ($file->thumb != null) {
                unlink($file->path . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR . $file->thumb);
            }

            if ($this->os_model->delete('anexos', 'idAnexos', $id) == true) {
                log_info('Removeu anexo de uma OS. ID (OS): ' . $idOs);
                echo json_encode(['result' => true, 'mensagem' => 'Anexo excluído com sucesso.']);
            } else {
                echo json_encode(['result' => false, 'mensagem' => 'Erro ao tentar excluir anexo.']);
            }
        }
    }

    public function downloadanexo($id = null)
    {
        if ($id != null && is_numeric($id)) {
            $this->db->where('idAnexos', $id);
            $file = $this->db->get('anexos', 1)->row();

            $this->load->library('zip');
            $path = $file->path;
            $this->zip->read_file($path . '/' . $file->anexo);
            $this->zip->download('file' . date('d-m-Y-H.i.s') . '.zip');
        }
    }

    public function adicionarDesconto()
    {
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
            $valorTotalData = $this->os_model->valorTotalOS($os_id);

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

    private function enviarOsPorEmail($idOs, $remetentes, $assunto, $evento = null)
    {
        $dados = [];

        $this->load->model('mapos_model');
        $dados['result'] = $this->os_model->getById($idOs);
        if (! isset($dados['result']->email)) {
            return false;
        }

        $dados['produtos'] = $this->os_model->getProdutos($idOs);
        $dados['servicos'] = $this->os_model->getServicos($idOs);
        $dados['emitente'] = $this->mapos_model->getEmitente();
        $emitente = $dados['emitente'];
        if (! isset($emitente->email)) {
            return false;
        }

        // Gatilho de notificação (se informado): controla ativo, canal de e-mail,
        // destinatários, blocos e modelo. Sem gatilho, mantém o comportamento anterior.
        $blocos = null;
        $slug = 'os';
        if ($evento) {
            $this->load->model('notification_triggers_model');
            $trigger = $this->notification_triggers_model->getByEvento($evento);
            if ($trigger) {
                $canais = Notification_triggers_model::toList($trigger->canais);
                if ((int) $trigger->ativo !== 1 || ! in_array('email', $canais, true)) {
                    return false; // gatilho desativado ou sem canal de e-mail
                }
                $slug = $trigger->template_slug ?: 'os';
                $blocosList = Notification_triggers_model::toList($trigger->blocos);
                $blocos = empty($blocosList) ? null : $blocosList;
                $remetentes = $this->destinatariosOs($trigger, $dados['result'], $emitente);
            }
        }

        $html = $this->load->view('os/emails/os', $dados, true);

        // Modelo configurável de e-mail (fallback para a view padrão acima).
        $this->load->library('emailtemplate');
        $render = $this->emailtemplate->render($slug, [
            'emitente' => $emitente,
            'os' => $dados['result'],
            'cliente' => $dados['result'],
            'produtos' => $dados['produtos'],
            'servicos' => $dados['servicos'],
            'blocos' => $blocos,
        ]);
        if ($render !== null && ! $render['ativo']) {
            // Envio de e-mail de OS desativado em Configurações > Modelos de E-mail.
            return false;
        }
        if ($render !== null) {
            $html = $render['corpo'];
            $assunto = $render['assunto'];
        }

        $this->load->model('email_model');

        $remetentes = array_unique($remetentes);
        foreach ($remetentes as $remetente) {
            if ($remetente) {
                $headers = ['From' => $emitente->email, 'Subject' => $assunto, 'Return-Path' => ''];
                $email = [
                    'to' => $remetente,
                    'message' => $html,
                    'status' => 'pending',
                    'date' => date('Y-m-d H:i:s'),
                    'headers' => serialize($headers),
                ];
                $this->email_model->add('email_queue', $email);
            } else {
                log_info('Email não adicionado a Lista de envio de e-mails. Verifique se o remetente esta cadastrado. OS ID: ' . $idOs);
            }
        }

        return true;
    }

    /**
     * Monta os e-mails de destino de uma OS conforme os destinatários marcados
     * no gatilho (cliente, e-mail secundário, técnico, emitente).
     */
    private function destinatariosOs($trigger, $os, $emitente)
    {
        $dest = Notification_triggers_model::toList($trigger->destinatarios);
        $emails = [];

        if (in_array('cliente', $dest, true) && ! empty($os->email)) {
            $emails[] = $os->email;
        }
        if (in_array('cliente_secundario', $dest, true) && ! empty($os->email_secundario)) {
            $emails[] = $os->email_secundario;
        }
        if (in_array('emitente', $dest, true) && ! empty($emitente->email)) {
            $emails[] = $emitente->email;
        }
        if (in_array('tecnico', $dest, true) && ! empty($os->usuarios_id)) {
            $this->load->model('usuarios_model');
            $tecnico = $this->usuarios_model->getById($os->usuarios_id);
            if ($tecnico && ! empty($tecnico->email)) {
                $emails[] = $tecnico->email;
            }
        }

        return $emails;
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

        if ($aba == 'sem_tecnico') {
            // OS sem técnico responsável
            $ordens = $this->os_model->getOsSemTecnico($config['per_page'], $page);
            $config['total_rows'] = $this->tecnico_model->countOsSemTecnico();
        } elseif ($aba == 'em_atendimento' || $aba == 'com_tecnico') {
            // OS já com técnico atribuído
            $ordens = $this->os_model->getOsComTecnico($config['per_page'], $page);
            $config['total_rows'] = $this->tecnico_model->countOsComTecnico();
        } elseif ($aba == 'nao_realizadas') {
            // Tratada à parte (lista de ocorrências), sem paginação nesta versão.
            $ordens = [];
            $config['total_rows'] = 0;
        } else {
            $aba = 'todos';
            // Todas as OS pendentes de atendimento
            $ordens = $this->os_model->getOsPendentesAtribuicao($config['per_page'], $page);
            $config['total_rows'] = $this->tecnico_model->countOsParaAtribuicao();
        }

        $this->data['ordens'] = $ordens ?: [];
        $this->data['aba'] = $aba;

        // Ocorrências de "Não Realizado" pendentes (visão de gestão, todos os técnicos)
        $this->data['naoRealizadas'] = $this->naorealizada_model->getPendentes(null, 100);

        // Indicadores (KPIs) do topo
        $this->data['kpis'] = [
            'total'          => $this->tecnico_model->countOsParaAtribuicao(),
            'sem_tecnico'    => $this->tecnico_model->countOsSemTecnico(),
            'em_atendimento' => $this->tecnico_model->countOsComTecnico(),
            'aguardando'     => $this->os_model->contarPorStatus(['Aguardando Peças', 'Aprovado', 'Orçamento', 'Negociação']),
            'nao_realizadas' => $this->naorealizada_model->contarPendentes(),
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
     * Ação AJAX: altera o status do chamado direto pela Central de Atendimento.
     * Restrita aos statuses de fluxo (statusChamadoPermitidos), evitando os
     * terminais que têm efeitos colaterais (estoque/faturamento).
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

        if ($this->os_model->edit('os', ['status' => $status], 'idOs', $os_id)) {
            log_info('Alterou status da OS #' . $os_id . ' para "' . $status . '" pela Central de Atendimento.');
            echo json_encode(['success' => true, 'message' => 'Status da OS #' . $os_id . ' atualizado para ' . $status . '.', 'status' => $status]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o status.']);
        }
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
        $usuario       = $this->session->userdata('idUsuarios');

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

        if (! $os_id || ! $tecnico_id) {
            $this->session->set_flashdata('error', 'Dados incompletos para atribuição.');
            redirect('os/atribuir');
        }

        $this->load->model('tecnico_model');

        if (! $this->tecnico_model->isTecnicoValido($tecnico_id)) {
            $this->session->set_flashdata('error', 'Usuário selecionado não é um técnico válido.');
            redirect('os/atribuir');
        }

        $atribuido_por = $this->session->userdata('idUsuarios');

        if ($this->tecnico_model->atribuirTecnico($os_id, $tecnico_id, $atribuido_por, $observacao)) {
            $this->notificarTecnicoAtribuicao($os_id, $tecnico_id);
            $this->session->set_flashdata('success', 'Técnico atribuído à OS #' . $os_id . ' com sucesso!');
            log_info('Atribuiu técnico ' . $tecnico_id . ' à OS #' . $os_id);
        } else {
            $this->session->set_flashdata('error', 'Erro ao atribuir técnico. Verifique se já não está atribuído.');
        }

        redirect('os/atribuir');
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
            $msg = "Olá {$tecnico->nome}! Você recebeu a OS #{$os_id}."
                . "\nCliente: " . ($os->nomeCliente ?? '')
                . "\nEquipamento/Defeito: " . strip_tags((string) ($os->defeito ?? ''))
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
