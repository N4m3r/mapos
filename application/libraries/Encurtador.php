<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Encurtador de links interno (sem serviço externo).
 *
 * Guarda a URL real em `short_links` e devolve um link curto no próprio domínio
 * do Mapos (ex.: https://seudominio/l/ab12cd) que redireciona pela rota `l/`.
 * Idempotente: a mesma URL reaproveita o slug já existente (via url_hash), então
 * reenviar o mesmo link não cria linhas novas nem quebra links antigos.
 *
 * Best-effort: se a tabela não existir ou algo falhar, devolve a URL original
 * para nunca impedir o envio da mensagem.
 */
class Encurtador
{
    /** @var CI_Controller */
    private $ci;

    private $table = 'short_links';

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->database();
    }

    /**
     * Devolve um link curto (site_url('l/<slug>')) para a URL informada.
     * Em qualquer falha, retorna a própria $url (fallback seguro).
     *
     * @param string   $url            URL absoluta a encurtar
     * @param int|null $diasValidade   validade opcional em dias (null = sem expiração)
     */
    public function encurtar($url, $diasValidade = null)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return $url;
        }

        try {
            if (! $this->ci->db->table_exists($this->table)) {
                return $url;
            }

            $hash = md5($url);

            // Reaproveita um slug ainda válido para a mesma URL.
            $existente = $this->ci->db
                ->where('url_hash', $hash)
                ->order_by('idLink', 'DESC')
                ->limit(1)
                ->get($this->table)
                ->row();
            if ($existente && (empty($existente->expira) || strtotime($existente->expira) > time())) {
                return $this->montarUrl($existente->slug);
            }

            $expira = ($diasValidade && (int) $diasValidade > 0)
                ? date('Y-m-d H:i:s', strtotime('+' . (int) $diasValidade . ' days'))
                : null;

            $slug = $this->gerarSlug();
            $this->ci->db->insert($this->table, [
                'slug' => $slug,
                'url' => $url,
                'url_hash' => $hash,
                'clicks' => 0,
                'expira' => $expira,
                'criado_em' => date('Y-m-d H:i:s'),
            ]);

            return $this->montarUrl($slug);
        } catch (\Throwable $e) {
            log_message('error', 'Encurtador falhou para ' . $url . ': ' . $e->getMessage());

            return $url;
        }
    }

    /**
     * Resolve um slug para a URL de destino, contando o clique. Retorna null
     * quando o slug não existe ou o link expirou.
     */
    public function resolver($slug)
    {
        $slug = preg_replace('/[^a-zA-Z0-9]/', '', (string) $slug);
        if ($slug === '' || ! $this->ci->db->table_exists($this->table)) {
            return null;
        }

        $row = $this->ci->db->where('slug', $slug)->limit(1)->get($this->table)->row();
        if (! $row) {
            return null;
        }
        if (! empty($row->expira) && strtotime($row->expira) < time()) {
            return null;
        }

        // Conta o clique (best-effort).
        $this->ci->db->where('idLink', $row->idLink)->set('clicks', 'clicks+1', false)->update($this->table);

        return $row->url;
    }

    private function montarUrl($slug)
    {
        return site_url('l/' . $slug);
    }

    /**
     * Gera um slug base62 curto e único (6 chars, cresce se colidir).
     */
    private function gerarSlug()
    {
        $alfabeto = '23456789abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ'; // sem 0/O/1/l/I
        $tamanho = 6;
        for ($tentativa = 0; $tentativa < 12; $tentativa++) {
            $slug = '';
            for ($i = 0; $i < $tamanho; $i++) {
                $slug .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
            }
            if ($this->ci->db->where('slug', $slug)->count_all_results($this->table) == 0) {
                return $slug;
            }
            if ($tentativa === 6) {
                $tamanho++; // aumenta o espaço se estiver colidindo muito
            }
        }

        // Fallback improvável: usa um sufixo do uniqid.
        return substr(str_replace('.', '', uniqid('', true)), -8);
    }
}
