<?php

use Libraries\Gateways\BasePaymentGateway;
use Libraries\Gateways\Contracts\PaymentGateway;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

class MercadoPago extends BasePaymentGateway
{
    /** @var PaymentClient */
    private $mercadoPagoApi;

    private $mercadoPagoConfig;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->config('payment_gateways');
        $this->ci->load->model('Os_model');
        $this->ci->load->model('vendas_model');
        $this->ci->load->model('cobrancas_model');
        $this->ci->load->model('mapos_model');
        $this->ci->load->model('email_model');

        $mercadoPagoConfig = $this->ci->config->item('payment_gateways')['MercadoPago'];
        $this->mercadoPagoConfig = $mercadoPagoConfig;

        // SDK v3: so o access token autentica; public key, client id e client secret
        // nao sao usados pela API de pagamentos.
        $credentials = $mercadoPagoConfig['credentials'];
        MercadoPagoConfig::setAccessToken((string) $credentials['access_token']);
        if (! empty($credentials['integrator_id'])) {
            MercadoPagoConfig::setIntegratorId((string) $credentials['integrator_id']);
        }
        if (! empty($credentials['platform_id'])) {
            MercadoPagoConfig::setPlatformId((string) $credentials['platform_id']);
        }
        if (! empty($credentials['corporation_id'])) {
            MercadoPagoConfig::setCorporationId((string) $credentials['corporation_id']);
        }

        $this->mercadoPagoApi = new PaymentClient();
    }

    /**
     * Executa uma chamada do SDK convertendo erros da API em Exception com a mensagem do Mercado Pago.
     */
    private function chamarApi(callable $chamada)
    {
        try {
            return $chamada($this->mercadoPagoApi);
        } catch (MPApiException $e) {
            $conteudo = $e->getApiResponse()->getContent();
            $mensagem = $conteudo['message'] ?? $e->getMessage();
            if (! empty($conteudo['cause'][0]['description'])) {
                $mensagem .= ' - ' . $conteudo['cause'][0]['description'];
            }

            throw new \Exception('Mercado Pago: ' . $mensagem);
        }
    }

    public function cancelar($id)
    {
        $cobranca = $this->ci->cobrancas_model->getById($id);
        if (! $cobranca) {
            throw new \Exception('Cobrança não existe!');
        }

        $this->chamarApi(fn (PaymentClient $api) => $api->cancel((int) $cobranca->charge_id));

        return $this->atualizarDados($id);
    }

    public function enviarPorEmail($id)
    {
        $cobranca = $this->ci->cobrancas_model->getById($id);
        if (! $cobranca) {
            throw new \Exception('Cobrança não existe!');
        }

        $emitente = $this->ci->mapos_model->getEmitente();
        if (! $emitente) {
            throw new \Exception('Emitente não configurado!');
        }

        $html = $this->ci->load->view(
            'cobrancas/emails/cobranca',
            [
                'cobranca' => $cobranca,
                'emitente' => $emitente,
                'paymentGatewaysConfig' => $this->ci->config->item('payment_gateways'),
            ],
            true
        );

        $assunto = 'Cobrança - ' . $emitente->nome;
        if ($cobranca->os_id) {
            $assunto .= ' - OS #' . $cobranca->os_id;
        } else {
            $assunto .= ' - Venda #' . $cobranca->vendas_id;
        }

        // Modelo configurável de e-mail (fallback para a view/assunto acima).
        $this->ci->load->library('emailtemplate');
        $render = $this->ci->emailtemplate->render('cobranca', [
            'emitente' => $emitente,
            'cobranca' => $cobranca,
        ]);
        if ($render !== null && ! $render['ativo']) {
            return;
        }
        if ($render !== null) {
            $html = $render['corpo'];
            $assunto = $render['assunto'];
        }

        $remetentes = emails_cobranca($cobranca);
        foreach ($remetentes as $remetente) {
            $headers = [
                'From' => $emitente->email,
                'Subject' => $assunto,
                'Return-Path' => '',
            ];
            $email = [
                'to' => $remetente,
                'message' => $html,
                'status' => 'pending',
                'date' => date('Y-m-d H:i:s'),
                'headers' => serialize($headers),
            ];
            $this->ci->email_model->add('email_queue', $email);
        }
    }

    public function atualizarDados($id)
    {
        $cobranca = $this->ci->cobrancas_model->getById($id);
        if (! $cobranca) {
            throw new \Exception('Cobrança não existe!');
        }

        $payment = $this->chamarApi(fn (PaymentClient $api) => $api->get((int) $cobranca->charge_id));

        // Cobrança foi paga ou foi confirmada de forma manual, então damos baixa
        if (($payment->status ?? null) === 'approved') {
            // TODO: dar baixa no lançamento caso exista
        }

        $databaseResult = $this->ci->cobrancas_model->edit(
            'cobrancas',
            [
                'status' => $payment->status ?? null,
            ],
            'idCobranca',
            $id
        );

        if ($databaseResult == true) {
            $this->ci->session->set_flashdata('success', 'Cobrança atualizada com sucesso!');
            log_info('Alterou um status de cobrança. ID' . $id);
        } else {
            $this->ci->session->set_flashdata('error', 'Erro ao atualizar cobrança!');
            throw new \Exception('Erro ao atualizar cobrança!');
        }
    }

    public function confirmarPagamento($id)
    {
        $cobranca = $this->ci->cobrancas_model->getById($id);
        if (! $cobranca) {
            throw new \Exception('Cobrança não existe!');
        }

        $this->chamarApi(fn (PaymentClient $api) => $api->capture((int) $cobranca->charge_id, null));

        return $this->atualizarDados($id);
    }

    protected function gerarCobrancaBoleto($id, $tipo)
    {
        $entity = $this->findEntity($id, $tipo);
        $produtos = $tipo === PaymentGateway::PAYMENT_TYPE_OS
            ? $this->ci->Os_model->getProdutos($id)
            : $this->ci->vendas_model->getProdutos($id);
        $servicos = $tipo === PaymentGateway::PAYMENT_TYPE_OS
            ? $this->ci->Os_model->getServicos($id)
            : [];
        [$produtos, $servicos] = $this->aplicarSelecao($produtos, $servicos);
        $desconto = [$tipo === PaymentGateway::PAYMENT_TYPE_OS
            ? $this->ci->Os_model->getById($id)
            : $this->ci->vendas_model->getById($id)];
        $tipo_desconto = [$tipo === PaymentGateway::PAYMENT_TYPE_OS
            ? $this->ci->Os_model->getById($id)
            : $this->ci->vendas_model->getById($id)];

        $totalProdutos = array_reduce(
            $produtos,
            function ($total, $item) {
                return $total + (floatval($item->preco) * intval($item->quantidade));
            },
            0
        );
        $totalServicos = array_reduce(
            $servicos,
            function ($total, $item) {
                return $total + (floatval($item->preco) * intval($item->quantidade));
            },
            0
        );
        $tipoDesconto = array_reduce(
            $tipo_desconto,
            function ($total, $item) {
                return $item->tipo_desconto;
            },
            0
        );
        $totalDesconto = array_reduce(
            $desconto,
            function ($total, $item) {
                return $item->desconto;
            },
            0
        );

        if (empty($entity)) {
            throw new \Exception('OS ou venda não existe!');
        }

        if (($totalProdutos + $totalServicos) <= 0) {
            throw new \Exception('OS ou venda com valor negativo ou zero!');
        }

        if ($err = $this->errosCadastro($entity)) {
            throw new \Exception($err);
        }

        $clientNameParts = explode(' ', $entity->nomeCliente);
        $documento = preg_replace('/[^0-9]/', '', $entity->documento);
        $expirationDate = (new DateTime())->add(new DateInterval($this->mercadoPagoConfig['boleto_expiration']));
        $expirationDate = ($expirationDate->format(DateTime::RFC3339_EXTENDED));

        $request = [
            'transaction_amount' => floatval($this->valorTotal($totalProdutos, $totalServicos, $totalDesconto, $tipoDesconto)),
            'description' => ($tipo === PaymentGateway::PAYMENT_TYPE_OS ? "OS #$id" : "Venda #$id") . $this->rotuloSelecao,
            'payment_method_id' => 'bolbradesco',
            'date_of_expiration' => $expirationDate,
            'payer' => [
                'email' => $entity->email,
                'first_name' => $clientNameParts[0],
                'last_name' => $clientNameParts[count($clientNameParts) - 1],
                'identification' => [
                    'type' => strlen($documento) == 11 ? 'CPF' : 'CNPJ',
                    'number' => $documento,
                ],
                'address' => [
                    'zip_code' => preg_replace('/[^0-9]/', '', $entity->cep),
                    'street_name' => $entity->rua,
                    'street_number' => $entity->numero,
                    'neighborhood' => $entity->bairro,
                    'city' => $entity->cidade,
                    'federal_unit' => $entity->estado,
                ],
            ],
        ];

        $payment = $this->chamarApi(fn (PaymentClient $api) => $api->create($request));
        $detalhes = (object) ($payment->transaction_details ?? []);
        $codigoBarras = isset($detalhes->barcode) ? ((object) $detalhes->barcode)->content ?? null : null;

        $data = [
            'barcode' => $codigoBarras ?? ($detalhes->digitable_line ?? null),
            'link' => $detalhes->external_resource_url ?? null,
            'pdf' => $detalhes->external_resource_url ?? null,
            'expire_at' => $payment->date_of_expiration ?? null,
            'charge_id' => $payment->id,
            'status' => $payment->status ?? null,
            'total' => getMoneyAsCents($this->valorTotal($totalProdutos, $totalServicos, $totalDesconto, $tipoDesconto)),
            'clientes_id' => $entity->idClientes,
            'payment_method' => 'boleto',
            'payment_gateway' => 'MercadoPago',
        ];

        if ($tipo === PaymentGateway::PAYMENT_TYPE_OS) {
            $data['os_id'] = $id;
        } else {
            $data['vendas_id'] = $id;
        }

        if ($id = $this->ci->cobrancas_model->add('cobrancas', $data, true)) {
            $data['idCobranca'] = $id;
            log_info('Cobrança criada com successo. ID: ' . $payment->id);
        } else {
            throw new \Exception('Erro ao salvar cobrança!');
        }

        return $data;
    }

    protected function gerarCobrancaLink($id, $tipo)
    {
        throw new Exception('MercadoPago não suporta gerar link pela API, somente pelo painel!');
    }

    private function valorTotal($produtosValor, $servicosValor, $desconto, $tipo_desconto)
    {
        if ($tipo_desconto == 'porcento') {
            $def_desconto = $desconto * ($produtosValor + $servicosValor) / 100;
        } elseif ($tipo_desconto == 'real') {
            $def_desconto = $desconto * $this->fatorDescontoReal;
        } else {
            $def_desconto = 0;
        }

        return ($produtosValor + $servicosValor) - $def_desconto;
    }
}
