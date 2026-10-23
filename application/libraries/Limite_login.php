<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Limite de tentativas de login (ADR-005, S2).
 *
 * Bloqueia por 15 minutos quando, nos ultimos 15 minutos, houver 5 erros para o
 * mesmo e-mail vindos do mesmo IP, ou 20 erros vindos do mesmo IP (qualquer
 * e-mail). Login certo zera o contador daquele e-mail/IP.
 *
 * Se a tabela login_tentativas ainda nao existir (migration nao rodada), fica
 * inativo: nunca impede o login por falta de estrutura.
 *
 * Uso:
 *   if ($espera = $this->limite_login->bloqueado('painel', $email)) { ... aguarde $espera segundos ... }
 *   $this->limite_login->falhou('painel', $email);
 *   $this->limite_login->sucesso('painel', $email);
 */
class Limite_login
{
    const JANELA_SEGUNDOS = 900;
    const MAX_POR_EMAIL = 5;
    const MAX_POR_IP = 20;

    /** @var CI_Controller */
    private $ci;

    private $ativo;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ativo = $this->ci->db->table_exists('login_tentativas');
    }

    /** Segundos que faltam para liberar (0 = liberado). */
    public function bloqueado($contexto, $email)
    {
        if (! $this->ativo) {
            return 0;
        }
        $desde = date('Y-m-d H:i:s', time() - self::JANELA_SEGUNDOS);

        $porEmail = $this->ci->db->select('COUNT(*) AS total, MAX(criado_em) AS ultima', false)
            ->where('chave', $this->chave($contexto, $email))
            ->where('criado_em >=', $desde)
            ->get('login_tentativas')->row();
        $porIp = $this->ci->db->select('COUNT(*) AS total, MAX(criado_em) AS ultima', false)
            ->where('ip', $this->ip())
            ->where('contexto', $contexto)
            ->where('criado_em >=', $desde)
            ->get('login_tentativas')->row();

        $ultima = null;
        if ($porEmail && (int) $porEmail->total >= self::MAX_POR_EMAIL) {
            $ultima = $porEmail->ultima;
        }
        if ($porIp && (int) $porIp->total >= self::MAX_POR_IP) {
            $ultima = max((string) $ultima, (string) $porIp->ultima);
        }
        if (! $ultima) {
            return 0;
        }

        return max(1, strtotime($ultima) + self::JANELA_SEGUNDOS - time());
    }

    public function falhou($contexto, $email)
    {
        if (! $this->ativo) {
            return;
        }
        $this->ci->db->insert('login_tentativas', [
            'chave' => $this->chave($contexto, $email),
            'ip' => $this->ip(),
            'contexto' => $contexto,
            'criado_em' => date('Y-m-d H:i:s'),
        ]);
        log_message('info', "Login errado ({$contexto}) para " . $email . ' do IP ' . $this->ip());

        // Limpeza ocasional de registros antigos
        if (mt_rand(1, 50) === 1) {
            $this->ci->db->where('criado_em <', date('Y-m-d H:i:s', time() - 86400))->delete('login_tentativas');
        }
    }

    public function sucesso($contexto, $email)
    {
        if (! $this->ativo) {
            return;
        }
        $this->ci->db->where('chave', $this->chave($contexto, $email))->delete('login_tentativas');
    }

    /** Mensagem padrao para o usuario bloqueado. */
    public function mensagem($segundos)
    {
        $minutos = (int) ceil($segundos / 60);

        return 'Muitas tentativas de acesso. Aguarde ' . $minutos . ' minuto' . ($minutos > 1 ? 's' : '') . ' e tente novamente.';
    }

    private function chave($contexto, $email)
    {
        return sha1($contexto . '|' . mb_strtolower(trim((string) $email)) . '|' . $this->ip());
    }

    private function ip()
    {
        return (string) $this->ci->input->ip_address();
    }
}
