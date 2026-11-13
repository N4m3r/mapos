<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Aviso do link de aceite (assinatura do cliente) ao finalizar o atendimento.
 *
 * Gera ou reaproveita o link público de aceite da OS, encurta e dispara por
 * WhatsApp aos destinos do gatilho. Um ponto só para a finalização pela Área
 * do Técnico web (Checkin) e pelo app (API v1).
 *
 * Evento: atividade não programada usa "atividade_np_finalizada" quando esse
 * gatilho está ativo; as demais OS (e a não programada sem esse gatilho) usam
 * "os_aceite". Se o gatilho tiver reenvio configurado, a OS entra no loop de
 * lembretes (Aceite_reenvio) até o cliente decidir.
 *
 * Tags do modelo: {CLIENTE_NOME}, {NUMERO_OS}, {LINK} e {CHAMADO} (Nº do
 * chamado da OS; vazio quando não há). Blindado: qualquer falha só vai para
 * o log e nunca derruba a finalização.
 */
class Aviso_aceite
{
    public const FALLBACK = "Olá {CLIENTE_NOME}! O serviço (OS #{NUMERO_OS}) foi concluído. Confirme o aceite e assine pelo link:\n{LINK}";

    public const FALLBACK_LEMBRETE = "Olá {CLIENTE_NOME}! Ainda falta o seu aceite do serviço da OS #{NUMERO_OS}. Confirme e assine pelo link:\n{LINK}";

    public const FALLBACK_DECISAO = [
        'aceite_concluido' => 'Aceite registrado na OS #{NUMERO_OS} por {NOME_ASSINANTE} ({CLIENTE_NOME}).',
        'aceite_recusado' => "A OS #{NUMERO_OS} foi recusada por {NOME_ASSINANTE} ({CLIENTE_NOME}).\nMotivo: {OBSERVACAO}",
        'aceite_esgotado' => 'A OS #{NUMERO_OS} ({CLIENTE_NOME}) continua sem aceite depois de {TENTATIVA} lembrete(s).',
    ];

    /** Dias de validade do link gerado ou renovado. */
    public const DIAS_LINK = 7;

    protected $ci;

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

    /**
     * Evento que dispara o link de uma OS finalizada.
     *
     * @param object $os
     * @param bool   $npAtivo gatilho "atividade_np_finalizada" está ativo
     */
    public static function eventoParaOs($os, $npAtivo)
    {
        return (! empty($os->nao_programada) && $npAtivo) ? 'atividade_np_finalizada' : 'os_aceite';
    }

    /** Envia o link de aceite da OS finalizada. Retorna true quando o disparo foi feito. */
    public function enviar($osId)
    {
        $osId = (int) $osId;
        try {
            $os = $osId ? $this->carregarOs($osId) : null;
            if (! $os) {
                return false;
            }

            $evento = self::eventoParaOs($os, $this->gatilhosAtivos('atividade_np_finalizada') !== []);
            $link = $this->link($os);
            if (! $link) {
                return false;
            }

            $slug = $evento === 'atividade_np_finalizada' ? 'aceite_np' : 'aceite';
            $this->disparar($evento, $os, self::tags($os, $link), $slug, self::FALLBACK);

            $config = $this->configReenvio($evento);
            if ($config && $this->numeroCliente($os) !== '') {
                $this->agendarReenvio($osId, $evento, $config);
            }

            return true;
        } catch (\Throwable $e) {
            log_info('Falha ao disparar aceite por WhatsApp (OS #' . $osId . '): ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Reenvio do loop (chamado pelo Aceite_reenvio): renova o link se venceu e
     * manda o lembrete só ao cliente. Retorna true quando a mensagem saiu.
     *
     * @param object $os
     * @param int    $tentativa número deste reenvio (1, 2, ...)
     */
    public function reenviar($os, $tentativa)
    {
        try {
            $numero = $this->numeroCliente($os);
            $link = $numero !== '' ? $this->link($os) : null;
            if (! $link) {
                return false;
            }

            $tags = self::tags($os, $link) + ['{TENTATIVA}' => (int) $tentativa];

            return $this->enviarLembrete($numero, $tags, (int) $os->idOs);
        } catch (\Throwable $e) {
            log_info('Falha no reenvio do aceite (OS #' . (int) ($os->idOs ?? 0) . '): ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Cliente decidiu pelo link: encerra o loop de reenvio e avisa pelo
     * gatilho "aceite_concluido" ou "aceite_recusado".
     */
    public function aoDecidir($osId, $decisao, $nome, $obs)
    {
        $osId = (int) $osId;
        $decisao = $decisao === 'aceito' ? 'aceito' : 'recusado';
        try {
            $this->encerrarReenvio($osId, $decisao);

            $os = $this->carregarOs($osId);
            if ($os) {
                $evento = $decisao === 'aceito' ? 'aceite_concluido' : 'aceite_recusado';
                $tags = self::tags($os, '') + ['{NOME_ASSINANTE}' => (string) $nome, '{OBSERVACAO}' => (string) $obs, '{TENTATIVA}' => ''];
                $this->disparar($evento, $os, $tags, $evento, self::FALLBACK_DECISAO[$evento]);
            }
        } catch (\Throwable $e) {
            log_info('Falha ao avisar a decisão do aceite (OS #' . $osId . '): ' . $e->getMessage());
        }
    }

    /** Lembretes esgotados sem assinatura: avisa pelo gatilho "aceite_esgotado". */
    public function avisarEsgotado($os, $tentativas)
    {
        try {
            $tags = self::tags($os, '') + ['{TENTATIVA}' => (int) $tentativas, '{NOME_ASSINANTE}' => '', '{OBSERVACAO}' => ''];
            $this->disparar('aceite_esgotado', $os, $tags, 'aceite_esgotado', self::FALLBACK_DECISAO['aceite_esgotado']);
        } catch (\Throwable $e) {
            log_info('Falha ao avisar aceite esgotado (OS #' . (int) ($os->idOs ?? 0) . '): ' . $e->getMessage());
        }
    }

    /* ------------------------------------------------------------------ */
    /* Acesso a banco e envio (sobrescritos nos testes)                    */
    /* ------------------------------------------------------------------ */

    protected function carregarOs($osId)
    {
        $this->ci->load->model('aceite_model');
        $this->ci->load->model('os_model');
        if (! $this->ci->aceite_model->suportado()) {
            return null;
        }

        return $this->ci->os_model->getById((int) $osId) ?: null;
    }

    /** Gatilhos ativos (WhatsApp) de um evento. */
    protected function gatilhosAtivos($evento)
    {
        $this->ci->load->model('notification_triggers_model');

        return $this->ci->notification_triggers_model->getActiveByEvento($evento, 'whatsapp');
    }

    /** Reenvio do primeiro gatilho ativo do evento que o configura; null = sem loop. */
    protected function configReenvio($evento)
    {
        $this->ci->load->library('aceite_reenvio');
        foreach ($this->gatilhosAtivos($evento) as $t) {
            $config = Aceite_reenvio::configDoGatilho($t);
            if ($config) {
                return $config;
            }
        }

        return null;
    }

    protected function agendarReenvio($osId, $evento, array $config)
    {
        $this->ci->load->library('aceite_reenvio');
        $this->ci->aceite_reenvio->agendar($osId, $evento, $config);
    }

    protected function encerrarReenvio($osId, $situacao)
    {
        $this->ci->load->library('aceite_reenvio');
        $this->ci->aceite_reenvio->encerrar($osId, $situacao);
    }

    protected function numeroCliente($os)
    {
        $this->ci->load->model('os_model');

        return (string) $this->ci->os_model->numeroNotificacao($os);
    }

    /** Link encurtado: reaproveita o token pendente e válido; senão gera um novo. */
    protected function link($os)
    {
        $this->ci->load->model('aceite_model');
        $token = (! empty($os->aceite_token) && $this->ci->aceite_model->situacao($os) === 'pendente')
            ? $os->aceite_token
            : null;
        if (! $token) {
            $res = $this->ci->aceite_model->gerarLink((int) $os->idOs, self::DIAS_LINK);
            if (! $res) {
                return null;
            }
            $token = $res['token'];
        }

        $this->ci->load->library('encurtador');

        return $this->ci->encurtador->encurtar(site_url('aceite/' . $token), self::DIAS_LINK);
    }

    protected function disparar($evento, $os, array $tags, $slugPadrao, $fallback)
    {
        $this->ci->load->library('notificador');
        $this->ci->notificador->whatsappLinkEvento(
            $evento,
            $tags,
            isset($os->clientes_id) ? (int) $os->clientes_id : 0,
            $this->numeroCliente($os),
            $slugPadrao,
            $fallback
        );
    }

    /** Lembrete só ao cliente, com o modelo "aceite_lembrete". */
    protected function enviarLembrete($numero, array $tags, $osId)
    {
        $this->ci->load->library('evolution_api');
        if (! $this->ci->evolution_api->estaAtivo()) {
            return false;
        }

        $this->ci->load->model('whatsapp_templates_model');
        $mensagem = $this->ci->whatsapp_templates_model->renderizar('aceite_lembrete', $tags, self::FALLBACK_LEMBRETE);
        if (trim((string) $mensagem) === '') {
            return false;
        }

        $this->ci->evolution_api->enviarTexto($numero, $mensagem, ['tipo' => 'cliente', 'evento' => 'aceite_lembrete', 'os_id' => $osId]);

        return true;
    }
}
