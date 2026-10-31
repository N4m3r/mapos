<?php

use Libraries\Fiscal\CertificadoHelper;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Cobrancas extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper('form');
        $this->load->model('cobrancas_model');
        $this->data['menuCobrancas'] = 'financeiro';
    }

    public function index()
    {
        $this->cobrancas();
    }

    public function adicionar()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'aCobranca')) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(403)
                ->set_output(json_encode(['message' => 'Você não tem permissão para adicionar cobrança!']));
        }

        $this->load->library('form_validation');
        if ($this->form_validation->run('cobrancas') == false) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['message' => validation_errors()]));
        } else {
            $id = $this->input->post('id');
            $tipo = $this->input->post('tipo');
            $formaPagamento = $this->input->post('forma_pagamento');
            $gatewayDePagamento = $this->input->post('gateway_de_pagamento');

            $doc = $this->itensDocumento($tipo, (int) $id);
            if (! $doc['doc']) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(404)
                    ->set_output(json_encode(['message' => 'OS ou venda não encontrada.']));
            }
            if (! $doc['disponiveis']) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(400)
                    ->set_output(json_encode(['message' => 'Já existe cobrança!']));
            }

            // O que cobrar: itens[] escolhidos no modal (vazio = tudo o que falta)
            // e agrupar = junto (uma cobrança) | separado (uma por item).
            $escolhidos = array_filter((array) $this->input->post('itens'), 'is_string');
            $agrupar = $this->input->post('agrupar') === 'separado' ? 'separado' : 'junto';
            $lotes = cobranca_montar_lotes($escolhidos ?: $doc['disponiveis'], $doc['disponiveis'], $agrupar);
            if (! $lotes) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(400)
                    ->set_output(json_encode(['message' => 'Escolha pelo menos um item que ainda não foi cobrado.']));
            }

            $this->load->library("Gateways/$gatewayDePagamento", null, 'PaymentGateway');
            $temItensRef = $this->db->field_exists('itens_ref', 'cobrancas');
            // IVA-T15: estimativa de IBS/CBS por cobrança (informativa). Config nula não quebra o fluxo.
            $temSplit = $this->db->field_exists('v_ibs_estimado', 'cobrancas');
            $configNfe = null;
            try {
                $this->load->model('Nfe_model');
                $configNfe = $this->Nfe_model->getConfig();
            } catch (\Throwable $e) {
                $configNfe = null;
            }
            $criadas = [];

            try {
                foreach ($lotes as $lote) {
                    // Documento inteiro, sem nada cobrado antes: cobrança "clássica" (itens_ref NULL).
                    $inteiro = ! $doc['cobrados'] && ! array_diff($doc['todas'], $lote);
                    if (! $inteiro && ! $temItensRef) {
                        throw new \Exception('Rode as migrações do sistema para cobrar itens separadamente.');
                    }
                    $this->PaymentGateway->definirSelecao(
                        $inteiro ? null : $lote,
                        cobranca_rotulo_lote($lote, $doc['todas'], $doc['nomes'])
                    );

                    $cobranca = $this->PaymentGateway->gerarCobranca($id, $tipo, $formaPagamento);

                    if (! $inteiro && ! empty($cobranca['idCobranca'])) {
                        $this->db->where('idCobranca', $cobranca['idCobranca'])
                            ->update('cobrancas', ['itens_ref' => implode(',', $lote)]);
                    }
                    if ($temSplit && ! empty($cobranca['idCobranca'])) {
                        $this->load->helper('cobranca');
                        $linha = $this->db->select('total')->get_where('cobrancas', ['idCobranca' => $cobranca['idCobranca']])->row();
                        if ($linha) {
                            $split = cobranca_split_estimado((int) $linha->total, $configNfe);
                            $this->db->where('idCobranca', $cobranca['idCobranca'])->update('cobrancas', [
                                'v_ibs_estimado' => $split['v_ibs'],
                                'v_cbs_estimado' => $split['v_cbs'],
                                'v_liquido_estimado' => $split['v_liquido'],
                            ]);
                        }
                    }
                    $criadas[] = $cobranca;
                }

                $resposta = $criadas[0];
                $resposta['total_cobrancas'] = count($criadas);

                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode($resposta));
            } catch (\Exception $e) {
                $expMsg = $e->getMessage();
                if ($expMsg == 'unauthorized: Must provide your access_token to proceed' || $expMsg == 'Unauthorized') {
                    $expMsg = 'Por favor configurar os dados da API em Config/payment_gatways.php';
                }
                if ($criadas) {
                    $expMsg = count($criadas) . ' de ' . count($lotes) . ' cobranças foram criadas; a seguinte falhou: ' . $expMsg;
                }

                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(500)
                    ->set_output(json_encode(['message' => $expMsg]));
            }
        }
    }

    /**
     * Tela de configuração do banco Cora (credenciais mTLS + ambiente).
     * Guarda em configuracoes_cora e sobe o certificado/chave por upload.
     */
    public function configCora()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cNfe')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar a cobrança Cora.');
            redirect(base_url());
        }

        $this->load->model('Cora_model');
        $this->load->library('form_validation');
        $this->data['custom_error'] = '';

        if ($this->input->post()) {
            $this->form_validation->set_rules('boleto_expiration', 'Vencimento do boleto', 'required|trim|max_length[10]');

            if ($this->form_validation->run() == true) {
                try {
                    $data = [
                        'ativo' => $this->input->post('ativo') ? 1 : 0,
                        'producao' => $this->input->post('producao') ? 1 : 0,
                        'client_id' => trim((string) $this->input->post('client_id')),
                        'boleto_expiration' => $this->input->post('boleto_expiration') ?: 'P3D',
                    ];

                    $enviados = [];

                    // Upload do certificado (.pem) — só quando enviado.
                    if (! empty($_FILES['certificado']['name'])) {
                        if ($_FILES['certificado']['error'] !== UPLOAD_ERR_OK) {
                            throw new Exception('Falha no upload do certificado (código ' . $_FILES['certificado']['error'] . ')');
                        }
                        $data['certificado_path'] = CertificadoHelper::salvarArquivoCora(
                            $_FILES['certificado']['tmp_name'],
                            $_FILES['certificado']['name'],
                            'certificado'
                        );
                        $enviados[] = 'certificado (.pem)';
                    }

                    // Upload da chave privada (.key) — só quando enviada.
                    if (! empty($_FILES['chave']['name'])) {
                        if ($_FILES['chave']['error'] !== UPLOAD_ERR_OK) {
                            throw new Exception('Falha no upload da chave privada (código ' . $_FILES['chave']['error'] . ')');
                        }
                        $data['chave_path'] = CertificadoHelper::salvarArquivoCora(
                            $_FILES['chave']['tmp_name'],
                            $_FILES['chave']['name'],
                            'chave'
                        );
                        $enviados[] = 'chave (.key)';
                    }

                    $this->Cora_model->saveConfig($data);
                    log_info('Atualizou a configuração da cobrança Cora.');
                    $msg = 'Configurações da Cora salvas com sucesso!';
                    if ($enviados) {
                        $msg .= ' Upload concluído: ' . implode(' e ', $enviados) . '.';
                    }
                    $this->session->set_flashdata('success', $msg);
                    redirect('cobrancas/configCora');
                } catch (Exception $e) {
                    $this->data['custom_error'] = '<div class="alert alert-danger">' . html_escape($e->getMessage()) . '</div>';
                }
            } else {
                $this->data['custom_error'] = (validation_errors() ? '<div class="alert alert-danger">' . validation_errors() . '</div>' : '');
            }
        }

        $this->data['configCora'] = $this->Cora_model->getConfig();
        $this->data['view'] = 'cobrancas/configCora';

        return $this->layout();
    }

    /**
     * Registra na Cora o webhook (invoice.paid) apontando para este sistema,
     * habilitando a baixa automática do pagamento. Retorna JSON.
     */
    public function registrarWebhookCora()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cNfe')) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(403)
                ->set_output(json_encode(['message' => 'Sem permissão.']));
        }

        try {
            $url = site_url('webhook/cora');
            $this->load->library('Gateways/Cora', null, 'PaymentGateway');
            $endpointId = $this->PaymentGateway->registrarWebhook($url);

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode([
                    'message' => 'Webhook registrado na Cora com sucesso! Baixa automática ativada.',
                    'endpoint_id' => $endpointId,
                    'url' => $url,
                ]));
        } catch (\Exception $e) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['message' => $e->getMessage()]));
        }
    }

    /**
     * Simula o pagamento de um boleto no ambiente de Stage (homologação),
     * para testar o fluxo de baixa automática. Retorna JSON.
     */
    public function simularPagamentoCora()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eCobranca')) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(403)
                ->set_output(json_encode(['message' => 'Sem permissão.']));
        }

        $id = $this->input->post('idCobranca');
        if (! $id || ! is_numeric($id)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['message' => 'Cobrança inválida.']));
        }

        try {
            $this->load->library('Gateways/Cora', null, 'PaymentGateway');
            $status = $this->PaymentGateway->simularPagamento($id);

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode(['message' => 'Pagamento simulado enviado à Cora.', 'status' => $status]));
        } catch (\Exception $e) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['message' => $e->getMessage()]));
        }
    }

    /**
     * Diagnóstico: mostra o que o sistema envia à Cora e a resposta crua dela.
     */
    public function diagnosticarCora()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cNfe')) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(403)
                ->set_output(json_encode(['message' => 'Sem permissão.']));
        }

        try {
            $this->load->library('Gateways/Cora', null, 'PaymentGateway');
            $info = $this->PaymentGateway->diagnostico();

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode($info, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['message' => $e->getMessage()]));
        }
    }

    /**
     * Testa a conexão/credenciais com a Cora (obtém um token). Retorna JSON.
     */
    public function testarCora()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cNfe')) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(403)
                ->set_output(json_encode(['message' => 'Sem permissão.']));
        }

        try {
            $this->load->library('Gateways/Cora', null, 'PaymentGateway');
            $this->PaymentGateway->testarConexao();

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode(['message' => 'Conexão com a Cora estabelecida com sucesso!']));
        } catch (\Exception $e) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['message' => $e->getMessage()]));
        }
    }

    /**
     * Gera um boleto híbrido (boleto + PIX) da Cora a partir de uma nota
     * fiscal autorizada (NF-e = produtos, NFS-e = serviços). O valor já sai
     * líquido do ISS retido quando a configuração fiscal indicar retenção.
     */
    public function gerarPorNota()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'aCobranca')) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(403)
                ->set_output(json_encode(['message' => 'Você não tem permissão para gerar cobrança!']));
        }

        $notaId = $this->input->post('nota_id');
        if (! $notaId || ! is_numeric($notaId)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['message' => 'Nota fiscal inválida.']));
        }

        $opcoes = [
            'parcelas' => (int) $this->input->post('parcelas') ?: 1,
            'vencimento' => trim((string) $this->input->post('vencimento')),
            'intervalo_tipo' => $this->input->post('intervalo_tipo') === 'dias' ? 'dias' : 'mensal',
            'intervalo_dias' => (int) $this->input->post('intervalo_dias') ?: 30,
            // Um boleto para cada item da nota (valor rateado), em vez de um só/parcelas.
            'por_item' => (int) $this->input->post('por_item') === 1,
        ];

        $this->load->library('Gateways/Cora', null, 'PaymentGateway');

        try {
            $cobranca = $this->PaymentGateway->gerarBoletoParaNota($notaId, $opcoes);

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode($cobranca));
        } catch (\Exception $e) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['message' => $e->getMessage()]));
        }
    }

    /**
     * Sincroniza o status de uma cobrança com o gateway e devolve JSON,
     * para atualizar o acompanhamento do boleto sem sair da tela da OS.
     */
    public function verificarPagamento()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eCobranca')) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(403)
                ->set_output(json_encode(['message' => 'Você não tem permissão para atualizar cobrança!']));
        }

        $id = $this->input->post('idCobranca');
        if (! $id || ! is_numeric($id)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['message' => 'Cobrança inválida.']));
        }

        if (! $this->cobrancas_model->getById($id)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(404)
                ->set_output(json_encode(['message' => 'Cobrança não encontrada.']));
        }

        try {
            $this->cobrancas_model->atualizarStatus($id);
            $cobranca = $this->cobrancas_model->getById($id);
            $this->load->config('payment_gateways');
            $this->load->helper('general');
            $label = $cobranca->status;
            try {
                $label = getCobrancaTransactionStatus(
                    $this->config->item('payment_gateways'),
                    $cobranca->payment_gateway,
                    $cobranca->status
                );
            } catch (\Throwable $ignored) {
            }

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode(['status' => $cobranca->status, 'label' => $label]));
        } catch (\Exception $e) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['message' => $e->getMessage()]));
        }
    }

    public function cobrancas()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vCobranca')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar cobrancas.');
            redirect(base_url());
        }

        $this->load->library('pagination');
        $this->load->config('payment_gateways');
        $this->load->helper(['general', 'cobranca']);

        $hoje = date('Y-m-d');
        $busca = trim((string) $this->input->get('q'));
        $status = (string) $this->input->get('status');
        $aba = $this->input->get('aba') === 'acobrar' ? 'acobrar' : 'cobrancas';
        if (! in_array($status, ['pendente', 'vencida', 'paga', 'cancelada'], true)) {
            $status = '';
        }

        // KPIs sempre sobre todas as cobranças (sem filtro de busca/status).
        $this->data['kpis'] = cobranca_kpis($this->cobrancas_model->listarComCliente(''), $hoje);

        $perPage = (int) ($this->data['configuration']['per_page'] ?? 10) ?: 10;
        $offset = max(0, (int) $this->uri->segment(3));

        if ($aba === 'acobrar') {
            $lista = $this->cobrancas_model->aCobrar($busca);
            $this->data['totalACobrar'] = count($lista);
        } else {
            $lista = $this->cobrancas_model->listarComCliente($busca);
            foreach ($lista as $c) {
                $c->classe = cobranca_classificar_status($c->status, $c->expire_at, $hoje);
            }
            if ($status !== '') {
                $lista = array_values(array_filter($lista, static function ($c) use ($status) {
                    return $c->classe === $status;
                }));
            }
            $this->data['totalACobrar'] = count($this->cobrancas_model->aCobrar(''));
        }

        $cfg = $this->data['configuration'];
        $cfg['base_url'] = site_url('cobrancas/cobrancas/');
        $cfg['total_rows'] = count($lista);
        $cfg['per_page'] = $perPage;
        $cfg['suffix'] = '?' . http_build_query(array_filter(['aba' => $aba === 'acobrar' ? 'acobrar' : '', 'status' => $status, 'q' => $busca]));
        $cfg['first_url'] = $cfg['base_url'] . $cfg['suffix'];
        $this->pagination->initialize($cfg);

        $this->data['results'] = array_slice($lista, $offset, $perPage);
        $this->data['aba'] = $aba;
        $this->data['filtroStatus'] = $status;
        $this->data['busca'] = $busca;
        $this->data['hoje'] = $hoje;
        $this->data['view'] = 'cobrancas/cobrancas';

        return $this->layout();
    }

    /** Atalho: cobrancas/acobrar abre a aba "A cobrar". */
    public function acobrar()
    {
        redirect(site_url('cobrancas/cobrancas?aba=acobrar'));
    }

    /**
     * JSON com o que será cobrado (itens, desconto, total) + impostos estimados,
     * usado pelo modal "Gerar cobrança". GET cobrancas/resumo/<os|venda>/<id>
     */
    public function resumo()
    {
        $json = function ($status, $payload) {
            return $this->output->set_content_type('application/json')->set_status_header($status)
                ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE));
        };

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'aCobranca')) {
            return $json(403, ['message' => 'Você não tem permissão para gerar cobrança!']);
        }

        $tipo = $this->uri->segment(3);
        $id = (int) $this->uri->segment(4);
        if (! in_array($tipo, ['os', 'venda'], true) || $id <= 0) {
            return $json(400, ['message' => 'Parâmetros inválidos.']);
        }

        $d = $this->itensDocumento($tipo, $id);
        if (! $d['doc']) {
            return $json(404, ['message' => 'Documento não encontrado.']);
        }
        $doc = $d['doc'];

        $bruto = cobranca_resumo($d['itensP'], $d['itensS'], 0)['total'];
        $desconto = $d['comDesconto'] ? max(0, $bruto - (float) $doc->valor_desconto) : 0.0;
        $resumo = cobranca_resumo($d['itensP'], $d['itensS'], $desconto);

        // Para o modal escolher o que cobrar: o que já foi cobrado fica bloqueado.
        $cobrados = array_flip($d['cobrados']);
        foreach ($resumo['itens'] as &$item) {
            $item['cobrado'] = isset($cobrados[$item['chave']]);
        }
        unset($item);
        $resumo['tudo_cobrado'] = ! $d['disponiveis'];

        $cliente = $this->db->select('nomeCliente')->get_where('clientes', ['idClientes' => $doc->clientes_id])->row();
        $resumo['cliente'] = $cliente->nomeCliente ?? '';
        $resumo['referencia'] = ($tipo === 'os' ? 'OS #' : 'Venda #') . $id;

        $config = null;
        try {
            $this->load->model('Nfe_model');
            $config = $this->Nfe_model->getConfig();
        } catch (\Throwable $e) {
            $config = null;
        }
        $config = is_object($config) ? $config : null;
        $impostos = null;
        try {
            if ($this->db->table_exists('tributos_parametros')) {
                $this->load->helper('tributacao');
                $this->load->model('Tributos_parametros_model');
                $this->load->model('Simples_opcao_model');
                $ano = (int) date('Y');
                $regime = tributacao_regime_ibscbs(
                    $this->Simples_opcao_model->listar(),
                    date('Y-m-d'),
                    (int) ($config->op_simp_nac ?? 1)
                );
                $impostos = cobranca_impostos_ano(
                    (float) $resumo['total'],
                    $config,
                    $this->Tributos_parametros_model->doAno($ano),
                    $regime,
                    $ano
                );
            }
        } catch (\Throwable $e) {
            $impostos = null;
        }
        $resumo['impostos'] = $impostos ?? cobranca_impostos_estimados($resumo['total'], $config);

        return $json(200, $resumo);
    }

    public function excluir()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'dCobranca')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para excluir cobranças');
            redirect(site_url('cobrancas/cobrancas/'));
        }
        try {
            $this->cobrancas_model->cancelarPagamento($this->input->post('excluir_id'));

            if ($this->cobrancas_model->delete('cobrancas', 'idCobranca', $this->input->post('excluir_id')) == true) {
                log_info('Removeu uma cobrança. ID' . $this->input->post('excluir_id'));
                $this->session->set_flashdata('success', 'Cobrança excluida com sucesso!');
            } else {
                $this->data['custom_error'] = '<div class="form_error"><p>Ocorreu um erro</p></div>';
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
        }
        redirect(site_url('cobrancas/cobrancas/'));
    }

    public function atualizar()
    {
        if (! $this->uri->segment(3) || ! is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
            redirect('mapos');
        }

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eCobranca')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para atualizar cobrança.');
            redirect(base_url());
        }
        try {
            $this->load->model('cobrancas_model');
            $this->cobrancas_model->atualizarStatus($this->uri->segment(3));
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
        }
        redirect(site_url('cobrancas/cobrancas/'));
    }

    public function confirmarPagamento()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eCobranca')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para confirmar pagamento da cobrança.');
            redirect(base_url());
        }
        try {
            $this->load->model('cobrancas_model');
            $this->cobrancas_model->confirmarPagamento($this->input->post('confirma_id'));
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
        }
        redirect(site_url('cobrancas/cobrancas/'));
    }

    public function cancelar()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eCobranca')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para cancelar cobrança.');
            redirect(base_url());
        }
        try {
            $this->load->model('cobrancas_model');
            $this->cobrancas_model->cancelarPagamento($this->input->post('cancela_id'));
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
        }
        redirect(site_url('cobrancas/cobrancas/'));
    }

    public function visualizar()
    {
        if (! $this->uri->segment(3) || ! is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
            redirect('cobrancas');
        }

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vCobranca')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar cobranças.');
            redirect(base_url());
        }
        $this->load->model('cobrancas_model');
        $this->load->config('payment_gateways');

        $this->data['result'] = $this->cobrancas_model->getById($this->uri->segment(3));
        if ($this->data['result'] == null) {
            $this->session->set_flashdata('error', 'Cobrança não encontrada.');
            redirect(site_url('cobrancas/'));
        }

        $this->data['view'] = 'cobrancas/visualizarCobranca';

        return $this->layout();
    }

    public function enviarEmail()
    {
        if (! $this->uri->segment(3) || ! is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
            redirect('cobrancas');
        }

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vCobranca')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar cobranças.');
            redirect(base_url());
        }

        $this->load->model('cobrancas_model');
        $this->cobrancas_model->enviarEmail($this->uri->segment(3));
        $this->session->set_flashdata('success', 'Email adicionado na fila.');

        redirect(site_url('cobrancas/cobrancas/'));
    }

    /**
     * Itens de uma OS/venda com chave ('p:<id>'/'s:<id>') e o que ainda falta
     * cobrar, considerando as cobranças já geradas (canceladas não contam).
     */
    private function itensDocumento($tipo, $id)
    {
        $this->load->helper('cobranca');
        $this->load->model('Os_model');
        $this->load->model('vendas_model');

        if ($tipo === 'os') {
            $doc = $this->Os_model->getById($id);
            $produtos = $this->Os_model->getProdutos($id);
            $servicos = array_map(static function ($s) {
                $s->preco = $s->preco ?: $s->precoVenda;
                $s->subTotal = round($s->preco * ($s->quantidade ?: 1), 2);

                return $s;
            }, $this->Os_model->getServicos($id));
            $comDesconto = $doc && $doc->desconto != 0 && $doc->valor_desconto != 0;
            $cobrancas = $this->Os_model->getCobrancas($id);
        } else {
            $doc = $this->vendas_model->getById($id);
            $produtos = $this->vendas_model->getProdutos($id);
            $servicos = [];
            $comDesconto = $doc && $doc->valor_desconto != 0;
            $cobrancas = $this->vendas_model->getCobrancas($id);
        }

        $itensP = array_map(static function ($p) {
            return [
                'chave' => cobranca_chave_item('produto', $p->idProdutos_os ?? $p->idItens ?? 0),
                'descricao' => $p->descricao, 'quantidade' => $p->quantidade, 'preco' => $p->preco, 'subTotal' => $p->subTotal ?? null,
            ];
        }, $produtos);
        $itensS = array_map(static function ($s) {
            return [
                'chave' => cobranca_chave_item('servico', $s->idServicos_os ?? 0),
                'nome' => $s->nome, 'quantidade' => $s->quantidade, 'preco' => $s->preco, 'subTotal' => $s->subTotal,
            ];
        }, $servicos);

        $todas = array_merge(array_column($itensP, 'chave'), array_column($itensS, 'chave'));
        $nomes = array_combine($todas, array_merge(array_column($itensP, 'descricao'), array_column($itensS, 'nome'))) ?: [];
        $ja = cobranca_itens_cobrados($cobrancas ?: []);
        $cobrados = $ja['inteiro'] ? $todas : array_values(array_intersect($todas, $ja['chaves']));

        return [
            'doc' => $doc,
            'comDesconto' => $comDesconto,
            'itensP' => $itensP,
            'itensS' => $itensS,
            'todas' => $todas,
            'nomes' => $nomes,
            'cobrados' => $cobrados,
            'disponiveis' => array_values(array_diff($todas, $cobrados)),
        ];
    }
}
