<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Aviso do link de aceite (assinatura do cliente) ao finalizar o atendimento.
 *
 * Gera ou reaproveita o link público de aceite da OS, encurta e dispara o
 * evento "os_aceite" por WhatsApp aos destinos do gatilho. Um ponto só para
 * a finalização pela Área do Técnico web (Checkin) e pelo app (API v1).
 *
 * Tags do modelo: {CLIENTE_NOME}, {NUMERO_OS}, {LINK} e {CHAMADO} (Nº do
 * chamado da OS; vazio quando não há). Blindado: qualquer falha só vai para
 * o log e nunca derruba a finalização.
 */
class Aviso_aceite
{
    public const FALLBACK = "Olá {CLIENTE_NOME}! O serviço (OS #{NUMERO_OS}) foi concluído. Confirme o aceite e assine pelo link:\n{LINK}";

    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /** Tags do modelo "aceite" para uma OS e um link já encurtado. */
    public static function tags($os, $link)
    {
        $chamado = trim((string) ($os->chamado_id ?? ''));

        return [
            '{CLIENTE_NOME}' => (string) ($os->nomeCliente ?? ''),
            '{NUMERO_OS}' => (int) ($os->idOs ?? 0),
            '{LINK}' => (string) $link,
            '{CHAMADO}' => $chamado,
        ];
    }

    /** Envia o link de aceite da OS. Retorna true quando o disparo foi feito. */
    public function enviar($osId)
    {
        $osId = (int) $osId;
        try {
            $this->ci->load->model('aceite_model');
            $this->ci->load->model('os_model');
            if (! $osId || ! $this->ci->aceite_model->suportado()) {
                return false;
            }

            $os = $this->ci->os_model->getById($osId);
            if (! $os) {
                return false;
            }

            // Reaproveita o token pendente; senão, gera um novo (7 dias).
            $token = (! empty($os->aceite_token) && $this->ci->aceite_model->situacao($os) === 'pendente')
                ? $os->aceite_token
                : null;
            if (! $token) {
                $res = $this->ci->aceite_model->gerarLink($osId, 7);
                if (! $res) {
                    return false;
                }
                $token = $res['token'];
            }

            $this->ci->load->library('encurtador');
            $link = $this->ci->encurtador->encurtar(site_url('aceite/' . $token), 7);

            $this->ci->load->library('notificador');
            $this->ci->notificador->whatsappLinkEvento(
                'os_aceite',
                self::tags($os, $link),
                isset($os->clientes_id) ? (int) $os->clientes_id : 0,
                $this->ci->os_model->numeroNotificacao($os),
                'aceite',
                self::FALLBACK
            );

            return true;
        } catch (\Throwable $e) {
            log_info('Falha ao disparar aceite por WhatsApp (OS #' . $osId . '): ' . $e->getMessage());

            return false;
        }
    }
}
