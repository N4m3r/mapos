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

            $this->load->model('Os_model');
            $this->load->model('vendas_model');
            $cobranca = $tipo === 'os'
                ? $this->Os_model->getCobrancas($this->input->post('id'))
                : $this->vendas_model->getCobrancas($this->input->post('id'));
            if ($cobranca) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(400)
                    ->set_output(json_encode(['message' => 'Já existe cobrança!']));
            }

            $this->load->library("Gateways/$gatewayDePagamento", null, 'PaymentGateway');

            try {
                $cobranca = $this->PaymentGateway->gerarCobranca(
                    $id,
                    $tipo,
                    $formaPagamento
                );

                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode($cobranca));
            } catch (\Exception $e) {
                $expMsg = $e->getMessage();
                if ($expMsg == 'unauthorized: Must provide your access_token to proceed' || $expMsg == 'Unauthorized') {
                    $expMsg = 'Por favor configurar os dados da API em Config/payment_gatways.php';
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

        $this->load->library('Gateways/Cora', null, 'PaymentGateway');

        try {
            $cobranca = $this->PaymentGateway->gerarBoletoParaNota($notaId);

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

        $this->data['configuration']['base_url'] = site_url('cobrancas/cobrancas/');
        $this->data['configuration']['total_rows'] = $this->cobrancas_model->count('cobrancas');

        $this->pagination->initialize($this->data['configuration']);

        $this->data['results'] = $this->cobrancas_model->get('cobrancas', '*', '', $this->data['configuration']['per_page'], $this->uri->segment(3));

        $this->data['view'] = 'cobrancas/cobrancas';

        return $this->layout();
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
}
