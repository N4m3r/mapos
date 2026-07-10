<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Cliente da Evolution API para notificações por WhatsApp.
 *
 * Envio de texto: POST {url}/message/sendText/{instance}
 *   Header: apikey: {apikey} | Body: { "number": "5592...", "text": "..." }
 * Estado da conexão: GET {url}/instance/connectionState/{instance}
 *
 * Configuração em application/config/whatsapp.php (lida do .env).
 */
class Evolution_api
{
    /** @var CI_Controller */
    private $ci;

    /** @var array */
    private $config;

    public function __construct()
    {
        $this->ci = &get_instance();

        // Carrega o config de forma resiliente: se o arquivo ainda não estiver
        // implantado, a biblioteca assume valores padrão (desativada) em vez de
        // derrubar a página.
        $evolution = [];
        if (file_exists(APPPATH . 'config/whatsapp.php')) {
            $this->ci->load->config('whatsapp', false);
            $whatsapp = $this->ci->config->item('whatsapp');
            if (is_array($whatsapp) && isset($whatsapp['evolution'])) {
                $evolution = $whatsapp['evolution'];
            }
        }

        $this->config = array_merge([
            'enabled' => false,
            'url' => '',
            'apikey' => '',
            'instance' => '',
            'timeout' => 30,
            'verify_ssl' => true,
            'auto_status' => [],
        ], $evolution);
    }

    /**
     * Credenciais mínimas presentes (url + apikey + instância).
     */
    public function estaConfigurado()
    {
        return ! empty($this->config['url'])
            && ! empty($this->config['apikey'])
            && ! empty($this->config['instance']);
    }

    /**
     * Integração habilitada (flag ligada E credenciais preenchidas).
     */
    public function estaAtivo()
    {
        return ! empty($this->config['enabled']) && $this->estaConfigurado();
    }

    /**
     * Normaliza o número no formato aceito pela Evolution: só dígitos, com DDI 55.
     * Aceita entradas como "(92) 99999-8888" e devolve "5592999998888".
     */
    public function formatarNumero($numero)
    {
        $numero = preg_replace('/[^0-9]/', '', (string) $numero);
        if ($numero === '') {
            return '';
        }
        // Já veio com DDI 55 (12 ou 13 dígitos: 55 + DDD + 8/9 dígitos).
        if (substr($numero, 0, 2) === '55' && strlen($numero) >= 12) {
            return $numero;
        }

        return '55' . $numero;
    }

    /**
     * Envia uma mensagem de texto. Retorna true em sucesso ou lança \Exception.
     */
    public function enviarTexto($numero, $mensagem)
    {
        if (! $this->estaAtivo()) {
            throw new \Exception('Notificação por WhatsApp (Evolution API) não está ativa/configurada.');
        }

        $numero = $this->formatarNumero($numero);
        if ($numero === '') {
            throw new \Exception('Número de WhatsApp do destinatário não informado.');
        }
        if (trim((string) $mensagem) === '') {
            throw new \Exception('Mensagem vazia.');
        }

        $this->request('POST', '/message/sendText/' . rawurlencode($this->config['instance']), [
            'number' => $numero,
            'text' => $mensagem,
        ]);

        return true;
    }

    /**
     * Consulta o estado da conexão da instância (para o botão "Testar conexão").
     * Retorna o estado textual (ex.: "open") ou lança \Exception.
     */
    public function testarConexao()
    {
        if (! $this->estaConfigurado()) {
            throw new \Exception('Preencha URL, API Key e Instância antes de testar.');
        }

        $resp = $this->request('GET', '/instance/connectionState/' . rawurlencode($this->config['instance']));
        // Evolution retorna { "instance": { "state": "open" } } ou { "state": "open" }.
        $state = $resp['instance']['state'] ?? $resp['state'] ?? null;
        if ($state === null) {
            throw new \Exception('Resposta inesperada da Evolution API.');
        }

        return $state;
    }

    /**
     * Decide se um status de OS dispara envio automático.
     */
    public function disparaAutomaticoPara($status)
    {
        $auto = $this->config['auto_status'] ?? [];
        if (empty($auto)) {
            return false;
        }

        return in_array(mb_strtolower(trim((string) $status)), $auto, true);
    }

    /* ------------------------------------------------------------------ */
    /* HTTP (cURL) — mesmo estilo do Gateways/Cora::request()             */
    /* ------------------------------------------------------------------ */

    private function request($method, $path, $body = null)
    {
        $headers = [
            'apikey: ' . $this->config['apikey'],
            'Accept: application/json',
        ];

        $ch = curl_init($this->config['url'] . $path);
        $opts = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int) ($this->config['timeout'] ?? 30),
        ];
        // Certificado SSL inválido/self-signed no servidor Evolution: permite
        // desligar a verificação (menos seguro) via WHATSAPP_EVOLUTION_VERIFY_SSL.
        if (isset($this->config['verify_ssl']) && $this->config['verify_ssl'] === false) {
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
            $opts[CURLOPT_SSL_VERIFYHOST] = 0;
        }
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
            throw new \Exception('Falha de conexão com a Evolution API: ' . $curlErr);
        }

        $data = json_decode($response, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $msg = '';
            if (is_array($data)) {
                $msg = $data['message'] ?? $data['error'] ?? '';
                if (is_array($msg)) {
                    $msg = implode('; ', $msg);
                }
            }
            $msg = $msg !== '' ? $msg : ('HTTP ' . $httpCode);
            throw new \Exception('Erro na Evolution API: ' . $msg);
        }

        return is_array($data) ? $data : [];
    }
}
