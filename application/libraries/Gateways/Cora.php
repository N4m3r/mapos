<?php

use Libraries\Gateways\BasePaymentGateway;
use Libraries\Gateways\Contracts\PaymentGateway;

/**
 * Gateway de pagamento Cora — emite boleto registrado com PIX embutido
 * (boleto híbrido) a partir de uma nota fiscal autorizada (NF-e / NFS-e).
 *
 * Autenticação: mTLS (certificate.pem + private-key.key) + client_id,
 * OAuth2 client_credentials. Doc: https://developers.cora.com.br
 */
class Cora extends BasePaymentGateway
{
    private $coraConfig;

    private $baseUrl;

    private $tokenUrl;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->config('payment_gateways');
        $this->ci->load->helper('general');
        $this->ci->load->model('Os_model');
        $this->ci->load->model('vendas_model');
        $this->ci->load->model('cobrancas_model');
        $this->ci->load->model('mapos_model');
        $this->ci->load->model('email_model');
        $this->ci->load->model('clientes_model');
        $this->ci->load->model('nfe_model');
        $this->ci->load->model('Cora_model');

        $this->coraConfig = $this->resolverConfig();

        if ($this->coraConfig['production'] === true) {
            $this->tokenUrl = 'https://matls-clients.api.cora.com.br/token';
            $this->baseUrl = 'https://api.cora.com.br';
        } else {
            $this->tokenUrl = 'https://matls-clients.api.stage.cora.com.br/token';
            $this->baseUrl = 'https://api.stage.cora.com.br';
        }
    }

    /**
     * Mescla a configuração salva no banco (tela Configurar Cobrança Cora)
     * sobre a do .env (payment_gateways.php). O banco tem prioridade.
     */
    private function resolverConfig()
    {
        $config = $this->ci->config->item('payment_gateways')['Cora'];
        $config['ativo'] = true; // sem tela migrada, assume habilitado via .env

        $db = $this->ci->Cora_model->getConfig();
        if ($db) {
            $config['ativo'] = (bool) $db->ativo;
            $config['production'] = (bool) $db->producao;
            if (! empty($db->boleto_expiration)) {
                $config['boleto_expiration'] = $db->boleto_expiration;
            }
            if (! empty($db->client_id)) {
                $config['credentials']['client_id'] = $db->client_id;
            }
            if (! empty($db->certificado_path)) {
                $config['credentials']['certificate_path'] = $db->certificado_path;
            }
            if (! empty($db->chave_path)) {
                $config['credentials']['private_key_path'] = $db->chave_path;
            }
        }

        return $config;
    }

    /**
     * Verifica se a integração está habilitada antes de emitir.
     */
    private function garantirAtiva()
    {
        if (empty($this->coraConfig['ativo'])) {
            throw new \Exception('A cobrança Cora está desativada. Ative em Notas Fiscais > Configurar Cobrança Cora.');
        }
    }

    /**
     * Testa credenciais/conexão obtendo um token (usado na tela de config).
     */
    public function testarConexao()
    {
        $this->getAccessToken();

        return true;
    }

    /* ------------------------------------------------------------------ */
    /* Autenticação (mTLS + client_credentials, com cache de token)        */
    /* ------------------------------------------------------------------ */

    private function certPaths()
    {
        $cert = $this->coraConfig['credentials']['certificate_path'] ?? '';
        $key = $this->coraConfig['credentials']['private_key_path'] ?? '';
        if (empty($cert) || ! file_exists($cert)) {
            throw new \Exception('Certificado (.pem) da Cora não encontrado. Envie-o em Notas Fiscais > Configurar Cobrança Cora.');
        }
        if (empty($key) || ! file_exists($key)) {
            throw new \Exception('Chave privada (.key) da Cora não encontrada. Envie-a em Notas Fiscais > Configurar Cobrança Cora.');
        }

        return [$cert, $key];
    }

    private function tokenCacheFile()
    {
        $ambiente = $this->coraConfig['production'] === true ? 'prod' : 'stage';

        return APPPATH . 'cache/cora_token_' . $ambiente . '.json';
    }

    private function getAccessToken()
    {
        $cacheFile = $this->tokenCacheFile();
        if (is_file($cacheFile)) {
            $cache = json_decode(file_get_contents($cacheFile), true);
            // Renova 60s antes do vencimento para evitar corrida.
            if (is_array($cache) && ! empty($cache['access_token']) && ($cache['expires_at'] ?? 0) > (time() + 60)) {
                return $cache['access_token'];
            }
        }

        [$cert, $key] = $this->certPaths();
        $clientId = $this->coraConfig['credentials']['client_id'] ?? '';
        if (empty($clientId)) {
            throw new \Exception('client_id da Cora não configurado (PAYMENT_GATEWAYS_CORA_CLIENT_ID).');
        }

        $ch = curl_init($this->tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSLCERT => $cert,
            CURLOPT_SSLKEY => $key,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
            ]),
            CURLOPT_TIMEOUT => (int) ($this->coraConfig['timeout'] ?? 30),
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \Exception('Falha de conexão com a Cora (token): ' . $curlErr);
        }
        $data = json_decode($response, true);
        if ($httpCode < 200 || $httpCode >= 300 || empty($data['access_token'])) {
            $msg = $data['message'] ?? $data['error_description'] ?? $response;
            throw new \Exception('Erro ao autenticar na Cora: ' . $msg);
        }

        $expiresIn = (int) ($data['expires_in'] ?? 3600);
        @file_put_contents($cacheFile, json_encode([
            'access_token' => $data['access_token'],
            'expires_at' => time() + $expiresIn,
        ]));

        return $data['access_token'];
    }

    /**
     * Requisição autenticada (mTLS + Bearer) à API da Cora.
     */
    private function request($method, $path, $body = null, $extraHeaders = [])
    {
        [$cert, $key] = $this->certPaths();
        $token = $this->getAccessToken();

        $headers = array_merge([
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ], $extraHeaders);

        $ch = curl_init($this->baseUrl . $path);
        $opts = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSLCERT => $cert,
            CURLOPT_SSLKEY => $key,
            CURLOPT_TIMEOUT => (int) ($this->coraConfig['timeout'] ?? 30),
        ];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $opts[CURLOPT_POSTFIELDS] = json_encode($body);
        }
        $opts[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $opts);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \Exception('Falha de conexão com a Cora: ' . $curlErr);
        }
        $data = json_decode($response, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $msg = $data['message'] ?? ($data['errors'][0]['message'] ?? $response);
            throw new \Exception('Erro na API Cora (' . $httpCode . '): ' . $msg);
        }

        return $data;
    }

    /* ------------------------------------------------------------------ */
    /* Emissão de boleto/PIX a partir de uma nota fiscal                   */
    /* ------------------------------------------------------------------ */

    /**
     * Gera um boleto híbrido (boleto + PIX) para uma nota fiscal autorizada.
     * NFS-e: abate o ISS retido do valor quando a config indicar retenção
     * pelo tomador. NF-e (produtos): sem retenção.
     */
    public function gerarBoletoParaNota($idNota)
    {
        $this->garantirAtiva();

        $nota = $this->ci->nfe_model->getNotaById($idNota);
        if (! $nota) {
            throw new \Exception('Nota fiscal não encontrada!');
        }
        if ($nota->status !== 'autorizada') {
            throw new \Exception('Só é possível gerar boleto para nota fiscal autorizada.');
        }

        // Evita boleto duplicado para a mesma nota (ignora cancelados).
        if ($this->ci->cobrancas_model->getByNota($idNota)) {
            throw new \Exception('Já existe um boleto ativo para esta nota fiscal.');
        }

        $tipoOrigem = $nota->os_id ? PaymentGateway::PAYMENT_TYPE_OS : PaymentGateway::PAYMENT_TYPE_VENDAS;
        $origemId = $nota->os_id ?: $nota->vendas_id;
        if (! $origemId) {
            throw new \Exception('Nota fiscal sem OS ou venda de origem.');
        }
        $entity = $this->findEntity($origemId, $tipoOrigem);
        if (empty($entity)) {
            throw new \Exception('OS ou venda de origem não existe!');
        }
        if ($err = $this->errosCadastro($entity)) {
            throw new \Exception($err);
        }

        // Cálculo do valor com desconto de ISS retido (apenas NFS-e).
        $issRetido = 0.0;
        $config = $this->ci->nfe_model->getConfig();
        if ($nota->tipo === 'nfse' && $config && (int) $config->tp_ret_issqn === 2) {
            $issRetido = round(((float) $nota->valor_total) * ((float) $config->aliquota_iss) / 100, 2);
        }
        $valorBoleto = round(((float) $nota->valor_total) - $issRetido, 2);
        if ($valorBoleto <= 0) {
            throw new \Exception('Valor do boleto inválido (menor ou igual a zero após retenção de ISS).');
        }

        $tipoLabel = $nota->tipo === 'nfe' ? 'NF-e' : 'NFS-e';
        $descricao = $tipoLabel . ' nº ' . $nota->numero . ($tipoOrigem === PaymentGateway::PAYMENT_TYPE_OS ? " - OS #$origemId" : " - Venda #$origemId");

        $documento = preg_replace('/[^0-9]/', '', $entity->documento);
        $body = [
            'code' => 'NOTA-' . $idNota,
            'customer' => [
                'name' => $entity->nomeCliente,
                'email' => $entity->email,
                'document' => [
                    'identity' => $documento,
                    'type' => strlen($documento) > 11 ? 'CNPJ' : 'CPF',
                ],
                'address' => [
                    'street' => $entity->rua,
                    'number' => (string) $entity->numero,
                    'district' => $entity->bairro,
                    'city' => $entity->cidade,
                    'state' => $entity->estado,
                    'complement' => $entity->complemento ?: '',
                    'zip_code' => preg_replace('/[^0-9]/', '', $entity->cep),
                ],
            ],
            'services' => [
                [
                    'name' => $descricao,
                    'description' => $descricao . ($issRetido > 0 ? ' (líquido de ISS retido R$ ' . number_format($issRetido, 2, ',', '.') . ')' : ''),
                    'amount' => getMoneyAsCents($valorBoleto),
                ],
            ],
            'payment_terms' => [
                'due_date' => (new DateTime())->add(new DateInterval($this->coraConfig['boleto_expiration']))->format('Y-m-d'),
            ],
            'payment_forms' => ['BANK_SLIP', 'PIX'],
        ];

        // Idempotency-Key estável por nota evita boletos duplicados em reenvio.
        $result = $this->request('POST', '/v2/invoices', $body, [
            'Idempotency-Key: ' . $this->idempotencyKey('nota-' . $idNota),
        ]);

        $bankSlip = $result['payment_options']['bank_slip'] ?? [];
        $pixEmv = $result['pix']['emv'] ?? '';
        $urlBoleto = $bankSlip['url'] ?? '';

        $data = [
            'charge_id' => $result['id'] ?? '',
            'status' => $result['status'] ?? 'OPEN',
            'barcode' => $bankSlip['barcode'] ?? '',
            'link' => $urlBoleto,
            'payment_url' => $urlBoleto,
            'pdf' => $urlBoleto,
            'pix' => $pixEmv,
            'expire_at' => $body['payment_terms']['due_date'],
            'conditional_discount_date' => $body['payment_terms']['due_date'],
            'created_at' => date('Y-m-d H:i:s'),
            'total' => getMoneyAsCents($valorBoleto),
            'valor_iss_retido' => $issRetido,
            'payment' => 'BANK_SLIP',
            'payment_method' => 'boleto',
            'payment_gateway' => 'Cora',
            'clientes_id' => $entity->idClientes,
            'nota_id' => $idNota,
            'message' => 'Pagamento referente a ' . $descricao,
        ];
        if ($tipoOrigem === PaymentGateway::PAYMENT_TYPE_OS) {
            $data['os_id'] = $origemId;
        } else {
            $data['vendas_id'] = $origemId;
        }

        if ($novoId = $this->ci->cobrancas_model->add('cobrancas', $data, true)) {
            $data['idCobranca'] = $novoId;
            log_info('Boleto Cora criado com sucesso. Nota: ' . $idNota . ' / Invoice: ' . $data['charge_id']);
        } else {
            throw new \Exception('Erro ao salvar cobrança!');
        }

        return $data;
    }

    private function idempotencyKey($seed)
    {
        // UUID v4 determinístico a partir do seed (mesma nota -> mesma chave).
        $hash = md5($seed . '|cora|' . ($this->coraConfig['production'] ? 'prod' : 'stage'));

        return sprintf(
            '%08s-%04s-4%03s-%04x-%012s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 13, 3),
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
            substr($hash, 20, 12)
        );
    }

    /* ------------------------------------------------------------------ */
    /* Acompanhamento (usa o fluxo genérico de cobrancas_model)            */
    /* ------------------------------------------------------------------ */

    public function atualizarDados($id)
    {
        $cobranca = $this->ci->cobrancas_model->getById($id);
        if (! $cobranca) {
            throw new \Exception('Cobrança não existe!');
        }

        $result = $this->request('GET', '/v2/invoices/' . $cobranca->charge_id);
        $status = $result['status'] ?? $cobranca->status;

        $ok = $this->aplicarStatus($cobranca, $status);
        if ($ok) {
            $this->ci->session->set_flashdata('success', 'Cobrança atualizada com sucesso!');
            log_info('Alterou um status de cobrança Cora. ID ' . $id);
        } else {
            $this->ci->session->set_flashdata('error', 'Erro ao atualizar cobrança!');
            throw new \Exception('Erro ao atualizar cobrança!');
        }

        return $status;
    }

    /**
     * Grava o novo status e, se for pagamento (PAID), dá baixa automática no
     * lançamento financeiro — uma única vez (não repete se já estava PAID).
     */
    private function aplicarStatus($cobranca, $status)
    {
        $jaEstavaPago = $cobranca->status === 'PAID';

        $ok = $this->ci->cobrancas_model->edit('cobrancas', ['status' => $status], 'idCobranca', $cobranca->idCobranca);

        if ($ok && $status === 'PAID' && ! $jaEstavaPago) {
            try {
                $baixados = $this->ci->cobrancas_model->darBaixaLancamento($cobranca);
                log_info('Baixa automática Cora (cobrança ' . $cobranca->idCobranca . '): ' . $baixados . ' lançamento(s).');
            } catch (\Throwable $e) {
                log_info('Falha na baixa automática Cora (cobrança ' . $cobranca->idCobranca . '): ' . $e->getMessage());
            }
        }

        return $ok;
    }

    /**
     * Processa uma notificação de webhook da Cora. Não confia no corpo do POST:
     * reconsulta a fatura na API para confirmar o status real e então aplica.
     * Retorna o status final, ou null se a fatura não pertencer a este sistema.
     */
    public function processarWebhook($invoiceId)
    {
        if (empty($invoiceId)) {
            return null;
        }

        $cobranca = $this->ci->cobrancas_model->getByChargeId($invoiceId);
        if (! $cobranca) {
            // Fatura desconhecida (não é nossa) — nada a fazer.
            return null;
        }

        $result = $this->request('GET', '/v2/invoices/' . $invoiceId);
        $status = $result['status'] ?? $cobranca->status;

        $this->aplicarStatus($cobranca, $status);
        log_info('Webhook Cora processado. Invoice ' . $invoiceId . ' -> ' . $status);

        return $status;
    }

    /**
     * Registra na Cora o endpoint de webhook para o evento invoice.paid.
     * Salva o id do endpoint em configuracoes_cora. Retorna o id do endpoint.
     */
    public function registrarWebhook($url)
    {
        $body = [
            'url' => $url,
            'resource' => 'invoice',
            'trigger' => 'paid',
        ];

        $result = $this->request('POST', '/endpoints', $body, [
            'Idempotency-Key: ' . $this->idempotencyKey('webhook-' . $url),
        ]);

        $endpointId = $result['id'] ?? ($result['endpoint_id'] ?? '');
        if ($endpointId && $this->ci->Cora_model->getConfig()) {
            $this->ci->Cora_model->saveConfig(['webhook_endpoint_id' => $endpointId]);
        }

        return $endpointId;
    }

    public function confirmarPagamento($id)
    {
        // A Cora só confirma pagamento pela liquidação real do boleto/PIX;
        // aqui apenas sincronizamos o status.
        return $this->atualizarDados($id);
    }

    public function cancelar($id)
    {
        $cobranca = $this->ci->cobrancas_model->getById($id);
        if (! $cobranca) {
            throw new \Exception('Cobrança não existe!');
        }

        $this->request('DELETE', '/v2/invoices/' . $cobranca->charge_id);

        $ok = $this->ci->cobrancas_model->edit('cobrancas', ['status' => 'CANCELLED'], 'idCobranca', $id);
        if (! $ok) {
            throw new \Exception('Erro ao cancelar cobrança!');
        }
        log_info('Cancelou boleto Cora. ID ' . $id);

        return 'CANCELLED';
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
        } elseif ($cobranca->vendas_id) {
            $assunto .= ' - Venda #' . $cobranca->vendas_id;
        }

        $headers = [
            'From' => $emitente->email,
            'Subject' => $assunto,
            'Return-Path' => '',
        ];
        $this->ci->email_model->add('email_queue', [
            'to' => $cobranca->email,
            'message' => $html,
            'status' => 'pending',
            'date' => date('Y-m-d H:i:s'),
            'headers' => serialize($headers),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Contrato base: Cora é emitido por nota fiscal, não pelo total       */
    /* ------------------------------------------------------------------ */

    protected function gerarCobrancaBoleto($id, $tipo)
    {
        throw new \Exception('O boleto Cora deve ser gerado a partir de uma nota fiscal emitida (aba Notas Fiscais da OS).');
    }

    protected function gerarCobrancaLink($id, $tipo)
    {
        throw new \Exception('A Cora não emite link de pagamento avulso; gere o boleto/PIX a partir da nota fiscal.');
    }
}
