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
    public function enviarTexto($numero, $mensagem, $contexto = [])
    {
        if (! $this->estaAtivo()) {
            throw new \Exception('Notificação por WhatsApp (Evolution API) não está ativa/configurada.');
        }

        $destino = $this->formatarDestino($numero);
        if ($destino === '') {
            throw new \Exception('Número/grupo de WhatsApp do destinatário não informado.');
        }
        if (trim((string) $mensagem) === '') {
            throw new \Exception('Mensagem vazia.');
        }

        try {
            $resp = $this->request('POST', '/message/sendText/' . rawurlencode($this->config['instance']), [
                'number' => $destino,
                'text' => $mensagem,
            ]);
            $this->registrarEnvio($destino, $mensagem, 'enviado', null, $resp, $contexto);

            return true;
        } catch (\Exception $e) {
            $this->registrarEnvio($destino, $mensagem, 'falha', $e->getMessage(), null, $contexto);
            throw $e;
        }
    }

    /**
     * Registra a tentativa de envio no log (whatsapp_envios). Nunca lança —
     * falha de log não pode afetar o envio.
     */
    private function registrarEnvio($destino, $mensagem, $status, $erro, $resp, array $contexto)
    {
        try {
            $this->ci->load->model('whatsapp_envios_model');
            if (! $this->ci->whatsapp_envios_model->suportado()) {
                return;
            }

            $retorno = null;
            if (is_array($resp)) {
                $retorno = $resp['key']['id'] ?? ($resp['status'] ?? null);
                if (! is_string($retorno)) {
                    $retorno = null;
                }
            }

            $this->ci->whatsapp_envios_model->registrar([
                'destino' => mb_substr((string) $destino, 0, 120),
                'tipo' => isset($contexto['tipo']) ? mb_substr((string) $contexto['tipo'], 0, 30) : null,
                'os_id' => isset($contexto['os_id']) && is_numeric($contexto['os_id']) ? (int) $contexto['os_id'] : null,
                'evento' => isset($contexto['evento']) ? mb_substr((string) $contexto['evento'], 0, 80) : null,
                'status' => $status,
                'erro' => $erro !== null ? mb_substr((string) $erro, 0, 1000) : null,
                'retorno' => $retorno !== null ? mb_substr($retorno, 0, 120) : null,
                'mensagem' => mb_substr((string) $mensagem, 0, 500),
            ]);
        } catch (\Exception $e) {
            // silencioso
        }
    }

    /**
     * Normaliza o destino: um JID de grupo (contém "@", ex.: "12036...@g.us")
     * vai como está; um telefone comum é formatado com DDI 55.
     */
    public function formatarDestino($destino)
    {
        $destino = trim((string) $destino);
        if ($destino === '') {
            return '';
        }
        if (strpos($destino, '@') !== false) {
            return $destino; // JID de grupo/contato
        }

        return $this->formatarNumero($destino);
    }

    /**
     * Lista os grupos de WhatsApp da instância (para o gatilho escolher).
     * Retorna [ ['id' => '...@g.us', 'nome' => 'Assunto'], ... ].
     */
    public function listarGrupos()
    {
        if (! $this->estaConfigurado()) {
            throw new \Exception('Preencha URL, API Key e Instância antes de listar os grupos.');
        }

        $resp = $this->request('GET', '/group/fetchAllGroups/' . rawurlencode($this->config['instance']) . '?getParticipants=false');

        // A Evolution pode retornar um array direto ou { "groups": [...] }.
        $lista = (isset($resp['groups']) && is_array($resp['groups'])) ? $resp['groups'] : $resp;
        $grupos = [];
        if (is_array($lista)) {
            foreach ($lista as $g) {
                if (! is_array($g)) {
                    continue;
                }
                $id = $g['id'] ?? ($g['jid'] ?? null);
                if (! empty($id)) {
                    $grupos[] = ['id' => $id, 'nome' => $g['subject'] ?? ($g['name'] ?? $id)];
                }
            }
        }

        return $grupos;
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

        // Algumas hospedagens (ex.: KingHost) têm zona DNS interna que resolve
        // subdomínios do próprio domínio para o servidor local — que apresenta
        // um certificado *.kinghost.net e quebra o TLS com "no alternative
        // certificate subject name". Fixamos a resolução do host da Evolution
        // para o IP do Cloudflare (onde o cert do domínio é válido e o túnel
        // responde). Override opcional via WHATSAPP_EVOLUTION_RESOLVE_IP.
        $host = parse_url($this->config['url'], PHP_URL_HOST);
        $resolveIp = $_ENV['WHATSAPP_EVOLUTION_RESOLVE_IP'] ?? '';
        if ($resolveIp === '' && $host) {
            $suffixes = ['.jj-ferreiras.shop', '.jj-ferreiras.com.br'];
            foreach ($suffixes as $suffix) {
                if (substr($host, -strlen($suffix)) === $suffix) {
                    $resolveIp = '172.67.217.200';
                    break;
                }
            }
        }
        if ($resolveIp !== '' && $host) {
            $scheme = parse_url($this->config['url'], PHP_URL_SCHEME) ?: 'https';
            $port = parse_url($this->config['url'], PHP_URL_PORT) ?: ($scheme === 'https' ? 443 : 80);
            $opts[CURLOPT_RESOLVE] = [$host . ':' . $port . ':' . $resolveIp];
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
