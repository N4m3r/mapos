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

        // Integração Direta (mTLS): TODAS as chamadas passam pelo host
        // matls-clients — é lá que o certificado do cliente autentica cada
        // requisição. (O host api[.stage].cora.com.br é o da modalidade Parceria.)
        $host = $this->coraConfig['production'] === true
            ? 'https://matls-clients.api.cora.com.br'
            : 'https://matls-clients.api.stage.cora.com.br';
        $this->tokenUrl = $host . '/token';
        $this->baseUrl = $host;
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

    /**
     * Coleta tudo que o sistema envia para a Cora e a resposta crua dela,
     * sem lançar exceção. Serve para o usuário validar (na tela) se as
     * credenciais estão sendo enviadas corretamente.
     */
    public function diagnostico()
    {
        $clientId = $this->coraConfig['credentials']['client_id'] ?? '';
        $cert = $this->coraConfig['credentials']['certificate_path'] ?? '';
        $key = $this->coraConfig['credentials']['private_key_path'] ?? '';

        $info = [
            'ambiente' => $this->coraConfig['production'] === true ? 'Produção' : 'Stage (homologação)',
            'token_url' => $this->tokenUrl,
            'client_id_enviado' => $clientId,
            'client_id_tamanho' => strlen($clientId),
            'certificado_path' => $cert,
            'chave_path' => $key,
            'certificado_existe' => is_file($cert),
            'chave_existe' => is_file($key),
        ];

        if (is_file($cert) && function_exists('openssl_x509_parse')) {
            $parsed = @openssl_x509_parse((string) file_get_contents($cert));
            if (is_array($parsed)) {
                $info['certificado_subject'] = isset($parsed['subject']) ? json_encode($parsed['subject'], JSON_UNESCAPED_UNICODE) : '';
                $info['certificado_issuer'] = isset($parsed['issuer']) ? json_encode($parsed['issuer'], JSON_UNESCAPED_UNICODE) : '';
                $info['certificado_valido_ate'] = isset($parsed['validTo_time_t']) ? date('d/m/Y', $parsed['validTo_time_t']) : '';
            }
        }

        if (is_file($cert) && is_file($key) && function_exists('openssl_x509_check_private_key')) {
            $c = @openssl_x509_read((string) file_get_contents($cert));
            $k = @openssl_pkey_get_private((string) file_get_contents($key));
            $info['par_certificado_chave_confere'] = ($c && $k && @openssl_x509_check_private_key($c, $k)) ? 'SIM' : 'NÃO';
        }

        // Chamada real ao /token (sem cache), capturando tudo.
        if (is_file($cert) && is_file($key)) {
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
            $info['http_status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $info['curl_errno'] = curl_errno($ch);
            $info['curl_error'] = curl_error($ch);
            curl_close($ch);

            $info['resposta_cora'] = is_string($response) ? mb_substr($response, 0, 600) : '(sem corpo)';
            $d = is_string($response) ? json_decode($response, true) : null;
            $info['autenticou'] = ! empty($d['access_token']) ? 'SIM' : 'NÃO';
        } else {
            $info['resposta_cora'] = 'Certificado ou chave ausente em disco — não foi possível chamar a Cora.';
            $info['autenticou'] = 'NÃO';
        }

        return $info;
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

    /**
     * Valida, antes de chamar a Cora, se o par certificado/chave é utilizável
     * para mTLS. Dá mensagens claras para os erros mais comuns.
     */
    private function validarCertificados($certPath, $keyPath)
    {
        $certPem = @file_get_contents($certPath);
        $keyPem = @file_get_contents($keyPath);
        if ($certPem === false || trim($certPem) === '') {
            throw new \Exception('Não foi possível ler o arquivo do certificado (.pem). Reenvie o arquivo.');
        }
        if ($keyPem === false || trim($keyPem) === '') {
            throw new \Exception('Não foi possível ler o arquivo da chave privada (.key). Reenvie o arquivo.');
        }
        if (! function_exists('openssl_x509_read')) {
            return; // OpenSSL indisponível: deixa o cURL validar.
        }

        // Detecta arquivos trocados (.key enviado no campo do .pem, etc.)
        $ehCert = strpos($certPem, 'BEGIN CERTIFICATE') !== false;
        $keyTemCert = strpos($keyPem, 'BEGIN CERTIFICATE') !== false;
        $keyTemChave = strpos($keyPem, 'PRIVATE KEY') !== false;
        $certTemChave = strpos($certPem, 'PRIVATE KEY') !== false;

        if (! $ehCert) {
            $dica = $certTemChave ? ' Parece que você enviou a CHAVE no lugar do certificado.' : '';
            throw new \Exception('O arquivo enviado como Certificado (.pem) não contém um certificado PEM válido.' . $dica);
        }
        if (! $keyTemChave) {
            $dica = $keyTemCert ? ' Parece que você enviou o CERTIFICADO no lugar da chave.' : '';
            throw new \Exception('O arquivo enviado como Chave privada (.key) não contém uma chave PEM válida.' . $dica);
        }

        $cert = @openssl_x509_read($certPem);
        if ($cert === false) {
            throw new \Exception('O certificado (.pem) não pôde ser lido pelo OpenSSL. Confirme que é o arquivo gerado pela Cora, em formato PEM.');
        }
        $priv = @openssl_pkey_get_private($keyPem);
        if ($priv === false) {
            throw new \Exception('A chave privada (.key) é inválida ou está protegida por senha. Envie a chave PEM sem senha, exatamente como a Cora gerou.');
        }
        if (@openssl_x509_check_private_key($cert, $priv) !== true) {
            throw new \Exception('O certificado (.pem) e a chave (.key) não formam um par. Reenvie os DOIS arquivos da mesma integração e do mesmo ambiente (Stage/Produção).');
        }
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
            throw new \Exception('client_id da Cora não configurado. Preencha em Configurar Cobrança Cora.');
        }

        // Valida o par cert/chave antes de tentar a conexão (erros mais claros).
        $this->validarCertificados($cert, $key);

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
        $curlErrNo = curl_errno($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $dica = '';
            if (in_array($curlErrNo, [58, 35, 83])) {
                $dica = ' (problema no certificado/chave cliente — confira se o .pem e o .key são do mesmo par e ambiente)';
            } elseif (in_array($curlErrNo, [6, 7, 28])) {
                $dica = ' (o servidor não conseguiu alcançar a Cora — verifique internet/firewall de saída HTTPS)';
            } elseif ($curlErrNo === 60) {
                $dica = ' (falha ao validar o certificado do servidor da Cora — cadeia de CA do servidor)';
            }
            throw new \Exception('Falha de conexão com a Cora (cURL ' . $curlErrNo . '): ' . $curlErr . $dica);
        }
        $data = json_decode($response, true);
        if ($httpCode < 200 || $httpCode >= 300 || empty($data['access_token'])) {
            $msg = $data['message'] ?? $data['error_description'] ?? $response;
            $dica = '';
            if ($httpCode === 401 || $httpCode === 403 || stripos((string) $msg, 'authorized') !== false || stripos((string) $msg, 'unauthorized') !== false) {
                $amb = $this->coraConfig['production'] === true ? 'Produção' : 'Stage (homologação)';
                $cidMask = strlen($clientId) > 10
                    ? substr($clientId, 0, 6) . '…' . substr($clientId, -4)
                    : $clientId;
                $dica = ' — O certificado foi aceito, mas a Cora recusou as credenciais.'
                    . ' Confira se o client_id, o certificado (.pem) e a chave (.key) são TODOS da MESMA integração e do ambiente selecionado (' . $amb . ').'
                    . ' client_id em uso: ' . $cidMask . '.';
            }
            throw new \Exception('Erro ao autenticar na Cora (HTTP ' . $httpCode . '): ' . $msg . $dica);
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
        // A Cora exige valor mínimo de R$ 5,00 por boleto.
        if ($valorBoleto < 5) {
            throw new \Exception('Valor do boleto abaixo do mínimo da Cora (R$ 5,00)' . ($issRetido > 0 ? ' após abater o ISS retido' : '') . '.');
        }

        $tipoLabel = $nota->tipo === 'nfe' ? 'NF-e' : 'NFS-e';
        $descricao = $tipoLabel . ' nº ' . $nota->numero . ($tipoOrigem === PaymentGateway::PAYMENT_TYPE_OS ? " - OS #$origemId" : " - Venda #$origemId");
        // Descrição do serviço no boleto: usa a MESMA descrição enviada na NF
        // (xDescServ persistido na nota); se não houver, cai no rótulo do documento.
        $descricaoNota = ! empty($nota->descricao_servico) ? trim((string) $nota->descricao_servico) : '';
        $descricaoBase = $descricaoNota !== '' ? $descricaoNota : $descricao;
        $descricaoServico = $descricaoBase . ($issRetido > 0 ? ' (líquido de ISS retido R$ ' . number_format($issRetido, 2, ',', '.') . ')' : '');

        $documento = preg_replace('/[^0-9]/', '', $entity->documento);
        $body = [
            'code' => 'NOTA-' . $idNota,
            'customer' => [
                // A Cora limita name/description; truncamos para não rejeitar.
                'name' => mb_substr((string) $entity->nomeCliente, 0, 60),
                'email' => mb_substr((string) $entity->email, 0, 60),
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
                    'name' => mb_substr($descricao, 0, 60),
                    'description' => mb_substr($descricaoServico, 0, 100),
                    'amount' => getMoneyAsCents($valorBoleto),
                ],
            ],
            'payment_terms' => [
                'due_date' => (new DateTime())->add(new DateInterval($this->coraConfig['boleto_expiration']))->format('Y-m-d'),
                // interest.rate é obrigatório na v2; 0 = sem juros.
                'interest' => ['rate' => 0],
            ],
            'payment_forms' => ['BANK_SLIP', 'PIX'],
        ];

        // Idempotency-Key estável por nota evita boletos duplicados em reenvio.
        $result = $this->request('POST', '/v2/invoices/', $body, [
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
            'message' => 'Pagamento referente a ' . $descricaoBase,
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

        // Envio automático do boleto por e-mail conforme o gatilho "cobranca_gerada".
        // Falha aqui nunca deve impedir a geração do boleto.
        try {
            $this->enviarPorEmail($novoId, 'cobranca_gerada');
        } catch (\Throwable $e) {
            log_info('Falha no envio automático do boleto (cobrança ' . $novoId . '): ' . $e->getMessage());
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

            // Notifica o cliente que o pagamento foi confirmado (gatilho pagamento_confirmado).
            $this->notificarPagamento($cobranca);
        }

        return $ok;
    }

    /**
     * Notifica o cliente do pagamento confirmado por e-mail e/ou WhatsApp,
     * conforme o gatilho "pagamento_confirmado". Best-effort (nunca lança).
     */
    private function notificarPagamento($cobranca)
    {
        try {
            $this->ci->load->model('notification_triggers_model');
            $trigger = $this->ci->notification_triggers_model->getByEvento('pagamento_confirmado');
            if (! $trigger || (int) $trigger->ativo !== 1) {
                return;
            }
            $canais = Notification_triggers_model::toList($trigger->canais);
            $dest = Notification_triggers_model::toList($trigger->destinatarios);
            $paraCliente = in_array('cliente', $dest, true);

            $nome = $cobranca->nomeCliente ?? '';
            $valor = 'R$ ' . number_format(((float) ($cobranca->total ?? 0)) / 100, 2, ',', '.');
            $texto = 'Olá' . ($nome ? ', ' . $nome : '') . '! Confirmamos o recebimento do pagamento de ' . $valor . '. Obrigado!';

            if ($paraCliente && in_array('email', $canais, true) && ! empty($cobranca->email)) {
                $this->ci->load->model('email_model');
                $emitente = $this->ci->mapos_model->getEmitente();
                $assunto = 'Pagamento confirmado' . (! empty($emitente->nome) ? ' - ' . $emitente->nome : '');
                $headers = ['From' => $emitente->email ?? '', 'Subject' => $assunto, 'Return-Path' => ''];
                $this->ci->email_model->add('email_queue', [
                    'to' => $cobranca->email,
                    'message' => '<p>' . htmlspecialchars($texto) . '</p>',
                    'status' => 'pending',
                    'date' => date('Y-m-d H:i:s'),
                    'headers' => serialize($headers),
                ]);
            }

            if ($paraCliente && in_array('whatsapp', $canais, true) && ! empty($cobranca->celular)) {
                $this->ci->load->library('evolution_api');
                if ($this->ci->evolution_api->estaAtivo()) {
                    $this->ci->evolution_api->enviarTexto($cobranca->celular, $texto);
                }
            }
        } catch (\Throwable $e) {
            log_info('Falha ao notificar pagamento (cobrança ' . ($cobranca->idCobranca ?? '?') . '): ' . $e->getMessage());
        }
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

        $result = $this->request('POST', '/endpoints/', $body, [
            'Idempotency-Key: ' . $this->idempotencyKey('webhook-' . $url),
        ]);

        $endpointId = $result['id'] ?? ($result['endpoint_id'] ?? '');
        if ($endpointId && $this->ci->Cora_model->getConfig()) {
            $this->ci->Cora_model->saveConfig(['webhook_endpoint_id' => $endpointId]);
        }

        return $endpointId;
    }

    /**
     * Simula o pagamento de um boleto no ambiente de Stage (homologação),
     * usando o endpoint POST /v2/invoices/pay. Só funciona em Stage — nunca
     * em Produção. Depois sincroniza o status/baixa.
     */
    public function simularPagamento($idCobranca)
    {
        if ($this->coraConfig['production'] === true) {
            throw new \Exception('A simulação de pagamento só é permitida no ambiente de Stage (homologação).');
        }

        $cobranca = $this->ci->cobrancas_model->getById($idCobranca);
        if (! $cobranca) {
            throw new \Exception('Cobrança não existe!');
        }
        if (empty($cobranca->charge_id)) {
            throw new \Exception('Cobrança sem id de fatura na Cora.');
        }

        $this->request('POST', '/v2/invoices/pay', ['id' => $cobranca->charge_id], [
            'Idempotency-Key: ' . $this->idempotencyKey('pay-' . $cobranca->charge_id . '-' . time()),
        ]);
        log_info('Simulou pagamento (Stage) da cobrança Cora ' . $idCobranca . ' / Invoice ' . $cobranca->charge_id);

        // Sincroniza o status resultante (pode virar IN_PAYMENT e depois PAID).
        return $this->atualizarDados($idCobranca);
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

    public function enviarPorEmail($id, $evento = 'cobranca_enviada')
    {
        $cobranca = $this->ci->cobrancas_model->getById($id);
        if (! $cobranca) {
            throw new \Exception('Cobrança não existe!');
        }

        $emitente = $this->ci->mapos_model->getEmitente();
        if (! $emitente) {
            throw new \Exception('Emitente não configurado!');
        }

        // Gatilho de cobrança: se desativado ou sem canal de e-mail, não envia.
        $this->ci->load->model('notification_triggers_model');
        $trigger = $this->ci->notification_triggers_model->getByEvento($evento);
        if ($trigger && ((int) $trigger->ativo !== 1 || ! in_array('email', Notification_triggers_model::toList($trigger->canais), true))) {
            return;
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

        // Modelo configurável de e-mail (fallback para a view/assunto acima).
        // Quando a cobrança vem de uma OS, carrega a OS + itens para liberar as
        // tags dos blocos da ordem de serviço no modelo de Cobrança/Boleto.
        $contextoEmail = [
            'emitente' => $emitente,
            'cobranca' => $cobranca,
        ];
        if (! empty($cobranca->os_id)) {
            $this->ci->load->model('os_model');
            $os = $this->ci->os_model->getById($cobranca->os_id);
            if ($os) {
                $contextoEmail['os'] = $os;
                $contextoEmail['produtos'] = $this->ci->os_model->getProdutos($cobranca->os_id);
                $contextoEmail['servicos'] = $this->ci->os_model->getServicos($cobranca->os_id);
            }
        }
        $this->ci->load->library('emailtemplate');
        $render = $this->ci->emailtemplate->render('cobranca', $contextoEmail);
        if ($render !== null && ! $render['ativo']) {
            return; // Envio de e-mail de cobrança desativado nas configurações.
        }
        if ($render !== null) {
            $html = $render['corpo'];
            $assunto = $render['assunto'];
        }

        $headers = [
            'From' => $emitente->email,
            'Subject' => $assunto,
            'Return-Path' => '',
        ];
        $destinatarios = emails_cobranca($cobranca);
        if (empty($destinatarios)) {
            throw new \Exception('Cliente sem e-mail válido para envio da cobrança.');
        }

        $emailData = [
            'to' => implode(', ', $destinatarios),
            'message' => $html,
            'status' => 'pending',
            'date' => date('Y-m-d H:i:s'),
            'headers' => serialize($headers),
        ];

        // Anexos conforme o gatilho de cobrança (boleto e/ou nota fiscal).
        $anexos = $this->anexosCobranca($cobranca, $evento);
        if (! empty($anexos) && $this->ci->db->field_exists('attachments', 'email_queue')) {
            $emailData['attachments'] = json_encode($anexos);
        }

        $this->ci->email_model->add('email_queue', $emailData);
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

    /* ------------------------------------------------------------------ */
    /* Anexos do e-mail de cobrança                                        */
    /* ------------------------------------------------------------------ */

    /**
     * Monta a lista de anexos do e-mail conforme o gatilho "cobranca_enviada"
     * (default: boleto). Boleto vai como URL pública (baixada no envio); a nota
     * fiscal é gerada em PDF a partir do XML autorizado.
     */
    private function anexosCobranca($cobranca, $evento = 'cobranca_enviada')
    {
        $tipos = ['boleto'];
        $this->ci->load->model('notification_triggers_model');
        $trigger = $this->ci->notification_triggers_model->getByEvento($evento);
        if ($trigger) {
            $tipos = Notification_triggers_model::toList($trigger->anexos);
        }

        $anexos = [];

        if (in_array('boleto', $tipos, true)) {
            $url = ! empty($cobranca->pdf) ? $cobranca->pdf : (! empty($cobranca->link) ? $cobranca->link : '');
            if ($url) {
                $anexos[] = ['url' => $url, 'nome' => 'boleto.pdf'];
            }
        }

        if (in_array('nota_fiscal', $tipos, true) && ! empty($cobranca->nota_id)) {
            $pdfPath = $this->anexoNotaFiscalPdf($cobranca->nota_id);
            if ($pdfPath) {
                $anexos[] = ['path' => $pdfPath, 'nome' => 'nota-fiscal.pdf'];
            }
        }

        return $anexos;
    }

    /**
     * Gera o PDF da nota fiscal (DANFE) a partir do XML autorizado e devolve o
     * caminho de um arquivo temporário, ou null se não for possível.
     * NFS-e (serviço) fica para etapa futura — depende do PDF do provedor.
     */
    private function anexoNotaFiscalPdf($notaId)
    {
        try {
            $this->ci->load->model('nfe_model');
            $nota = $this->ci->nfe_model->getNotaById($notaId);
            if (! $nota || empty($nota->xml_path) || ! file_exists($nota->xml_path)) {
                return null;
            }

            if ($nota->tipo === 'nfe' && class_exists('\NFePHP\DA\NFe\Danfe')) {
                $danfe = new \NFePHP\DA\NFe\Danfe(file_get_contents($nota->xml_path));
                $pdf = $danfe->render();
                $dest = tempnam(sys_get_temp_dir(), 'mapnf_');
                if ($dest !== false && file_put_contents($dest, $pdf) !== false) {
                    return $dest;
                }
            }

            return null;
        } catch (\Throwable $e) {
            log_message('error', 'Falha ao gerar PDF da NF para anexo (nota ' . $notaId . '): ' . $e->getMessage());

            return null;
        }
    }
}
