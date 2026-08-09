<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Disparo de notificações por WhatsApp (Evolution API) a partir dos gatilhos.
 *
 * Centraliza a lógica que antes ficava só no Os controller, para poder ser
 * chamada também na aprovação (link público / portal), finalização etc.
 * Blindado: qualquer falha é registrada no log e nunca interrompe o fluxo.
 */
class Notificador
{
    /** @var CI_Controller */
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /**
     * Dispara a notificação WhatsApp de uma OS conforme os gatilhos ativos dos
     * eventos informados. Sem gatilho WhatsApp para o evento, cai no
     * comportamento por WHATSAPP_EVOLUTION_AUTO_STATUS (só ao cliente).
     *
     * @param int          $osId
     * @param string|array $eventos  ex.: 'os_aprovada' ou ['os_editada','os_finalizada']
     * @param object|null  $emitente
     */
    public function whatsappOs($osId, $eventos, $emitente = null)
    {
        try {
            $this->ci->load->library('evolution_api');
            if (! $this->ci->evolution_api->estaAtivo()) {
                return;
            }

            $this->ci->load->model('os_model');
            $this->ci->load->model('notification_triggers_model');
            $this->ci->load->model('mapos_model');

            $os = $this->ci->os_model->getById($osId);
            if (! $os) {
                return;
            }
            if ($emitente === null) {
                $emitente = $this->ci->mapos_model->getEmitente();
            }

            // Reúne os gatilhos WhatsApp ativos de todos os eventos informados.
            $triggers = [];
            foreach ((array) $eventos as $ev) {
                if (! $ev) {
                    continue;
                }
                foreach ($this->ci->notification_triggers_model->getActiveByEvento($ev, 'whatsapp') as $t) {
                    $triggers[] = $t;
                }
            }

            if (empty($triggers)) {
                if (! $this->ci->evolution_api->disparaAutomaticoPara($os->status)) {
                    return;
                }
                $triggers = [null]; // fallback: envio padrão só ao cliente
            }

            $this->ci->load->model('whatsapp_templates_model');
            $fallbackOs = $this->configNotificaWhats();
            $numeroCliente = $this->ci->os_model->numeroNotificacao($os);
            $ctx = ['os_id' => $os->idOs, 'evento' => implode(',', array_filter((array) $eventos))];

            // Cada gatilho pode ter seu próprio modelo de mensagem. Deduplica por
            // (destino + mensagem) para não enviar a mesma mensagem duas vezes.
            // Filtro opcional whatsapp_clientes: se o gatilho listar clientes, só
            // dispara (modelo + grupos + destinatários) para OS desses clientes.
            $enviados = [];
            $clienteOs = isset($os->clientes_id) ? (int) $os->clientes_id
                : (isset($os->idClientes) ? (int) $os->idClientes : 0);
            foreach ($triggers as $t) {
                if ($t && ! Notification_triggers_model::aplicaAoCliente($t, $clienteOs)) {
                    continue;
                }

                $slug = ($t && ! empty($t->whatsapp_template)) ? $t->whatsapp_template : 'os';
                $conteudo = $this->ci->whatsapp_templates_model->conteudo($slug, $fallbackOs);
                $mensagem = $this->ci->os_model->montarNotificacaoOs($os->idOs, $conteudo, $emitente);

                $destCliente = $t ? in_array('cliente', Notification_triggers_model::toList($t->destinatarios), true) : true;
                $destTecnico = $t ? in_array('tecnico', Notification_triggers_model::toList($t->destinatarios), true) : false;

                if ($destCliente && ! empty($numeroCliente)) {
                    $this->enviarUnico($numeroCliente, $mensagem, ['tipo' => 'cliente'] + $ctx, $enviados);
                }
                if ($destTecnico && ! empty($os->telefone_usuario)) {
                    $this->enviarUnico($os->telefone_usuario, $mensagem, ['tipo' => 'tecnico'] + $ctx, $enviados);
                }
                if ($t && ! empty($t->whatsapp_grupos)) {
                    foreach (Notification_triggers_model::toList($t->whatsapp_grupos) as $jid) {
                        $this->enviarUnico($jid, $mensagem, ['tipo' => 'grupo'] + $ctx, $enviados);
                    }
                }
            }
        } catch (\Exception $e) {
            log_info('Falha na notificação WhatsApp da OS #' . $osId . ': ' . $e->getMessage());
        }
    }

    /**
     * Dispara um evento de link (aceite, RDO, etc.) por WhatsApp para o cliente
     * e/ou grupos configurados nos gatilhos ativos daquele evento.
     *
     * Diferente de whatsappOs (que monta a mensagem a partir dos dados da OS),
     * aqui a mensagem vem de um modelo simples com tags — em especial {LINK},
     * que o chamador já deve entregar ENCURTADO. Respeita o filtro de clientes
     * (whatsapp_clientes) do gatilho e deduplica destino+mensagem.
     *
     * @param string      $evento             ex.: 'os_aceite' | 'rdo_registrado'
     * @param array       $subs               tags -> valores, ex.: ['{LINK}' => 'https://...']
     * @param int         $clienteId          id do cliente (para o filtro whatsapp_clientes); 0 = sem filtro
     * @param string|null $numeroCliente      número do cliente (envia ao cliente se o gatilho pedir)
     * @param string      $templatePadrao     slug do modelo padrão quando o gatilho não define um
     * @param string      $fallback           texto usado quando o modelo não existe/está vazio
     */
    public function whatsappLinkEvento($evento, array $subs, $clienteId = 0, $numeroCliente = null, $templatePadrao = '', $fallback = '')
    {
        try {
            $this->ci->load->library('evolution_api');
            if (! $this->ci->evolution_api->estaAtivo()) {
                return;
            }

            $this->ci->load->model('notification_triggers_model');
            $this->ci->load->model('whatsapp_templates_model');

            $triggers = $this->ci->notification_triggers_model->getActiveByEvento($evento, 'whatsapp');
            if (empty($triggers)) {
                return;
            }

            $ctx = ['evento' => $evento];
            if (! empty($subs['{NUMERO_OS}'])) {
                $ctx['os_id'] = $subs['{NUMERO_OS}'];
            }

            $enviados = [];
            foreach ($triggers as $t) {
                if (! Notification_triggers_model::aplicaAoCliente($t, $clienteId)) {
                    continue;
                }

                $slug = ! empty($t->whatsapp_template) ? $t->whatsapp_template : $templatePadrao;
                $mensagem = $slug
                    ? $this->ci->whatsapp_templates_model->renderizar($slug, $subs, $fallback)
                    : str_replace(array_keys($subs), array_values($subs), $fallback);
                if (trim((string) $mensagem) === '') {
                    continue;
                }

                $dest = Notification_triggers_model::toList($t->destinatarios);
                if (in_array('cliente', $dest, true) && ! empty($numeroCliente)) {
                    $this->enviarUnico($numeroCliente, $mensagem, ['tipo' => 'cliente'] + $ctx, $enviados);
                }
                if (! empty($t->whatsapp_grupos)) {
                    foreach (Notification_triggers_model::toList($t->whatsapp_grupos) as $jid) {
                        $this->enviarUnico($jid, $mensagem, ['tipo' => 'grupo'] + $ctx, $enviados);
                    }
                }
            }
        } catch (\Exception $e) {
            log_info('Falha na notificação WhatsApp do evento ' . $evento . ': ' . $e->getMessage());
        }
    }

    /**
     * Dispara o evento "rdo_registrado" por WhatsApp: gera (ou reaproveita) o
     * link público do RDO, encurta-o e envia ao(s) grupo(s)/cliente do gatilho.
     * Ponto único chamado tanto pela área do técnico quanto pelo admin.
     * Best-effort: nunca lança.
     *
     * @param int $rdoId id do RDO recém-criado (obra_rdo.idRdo)
     */
    public function whatsappRdo($rdoId)
    {
        try {
            $this->ci->load->model('obras_model');
            $rdo = $this->ci->obras_model->getRdo($rdoId);
            if (! $rdo) {
                return;
            }
            $obra = $this->ci->obras_model->getObra($rdo->obra_id);
            if (! $obra) {
                $obra = (object) ['nome' => '', 'clientes_id' => null, 'celular' => null];
            }

            // Link público do RDO (encurtado). Se a coluna token não existir
            // (migration não aplicada), segue sem link.
            $link = '';
            $token = $this->ci->obras_model->gerarTokenRdo($rdoId, 30);
            if ($token) {
                $this->ci->load->library('encurtador');
                $link = $this->ci->encurtador->encurtar(site_url('rdo/' . $token), 30);
            }

            $respRow = null;
            if (! empty($rdo->responsavel_id)) {
                $respRow = $this->ci->db->select('nome')->where('idUsuarios', (int) $rdo->responsavel_id)
                    ->get('usuarios')->row();
            }
            $responsavel = $respRow ? $respRow->nome : (isset($rdo->responsavel) ? $rdo->responsavel : '');

            $atividades = trim((string) ($rdo->atividades ?? ''));
            if (mb_strlen($atividades) > 400) {
                $atividades = mb_substr($atividades, 0, 400) . '…';
            }

            $subs = [
                '{PROJETO}' => $obra->nome ?? '',
                '{NUMERO_RDO}' => (int) $rdo->numero,
                '{RESPONSAVEL}' => $responsavel,
                '{DATA}' => ! empty($rdo->data) ? date('d/m/Y', strtotime($rdo->data)) : date('d/m/Y'),
                '{ATIVIDADES}' => $atividades !== '' ? $atividades : '(sem descrição)',
                '{LINK}' => $link,
            ];

            $clienteId = isset($obra->clientes_id) ? (int) $obra->clientes_id : 0;
            $numeroCliente = ! empty($obra->celular) ? $obra->celular : null;

            $this->whatsappLinkEvento(
                'rdo_registrado',
                $subs,
                $clienteId,
                $numeroCliente,
                'rdo',
                "📋 RDO Nº {NUMERO_RDO} — {PROJETO}\nResponsável: {RESPONSAVEL}\nData: {DATA}\n\n*O que foi feito:*\n{ATIVIDADES}\n\nDetalhes e fotos:\n{LINK}"
            );
        } catch (\Exception $e) {
            log_info('Falha na notificação WhatsApp do RDO #' . $rdoId . ': ' . $e->getMessage());
        }
    }

    /**
     * Envia uma mensagem evitando duplicar o par (destino + mensagem). Cada
     * falha é registrada (o whatsapp_envios já loga em enviarTexto) e não
     * interrompe os demais envios do lote.
     */
    private function enviarUnico($destino, $mensagem, array $ctx, array &$enviados)
    {
        $chave = $destino . '|' . md5($mensagem);
        if (isset($enviados[$chave])) {
            return;
        }
        $enviados[$chave] = true;

        try {
            $this->ci->evolution_api->enviarTexto($destino, $mensagem, $ctx);
            log_info('Notificação WhatsApp (' . ($ctx['tipo'] ?? '-') . ') enviada para ' . $destino . '. OS #' . ($ctx['os_id'] ?? '?'));
        } catch (\Exception $e) {
            log_info('Falha WhatsApp para ' . $destino . ' (OS #' . ($ctx['os_id'] ?? '?') . '): ' . $e->getMessage());
        }
    }

    private function configNotificaWhats()
    {
        $this->ci->load->database();
        $row = $this->ci->db->get_where('configuracoes', ['config' => 'notifica_whats'])->row();
        $fallback = $row ? $row->valor : '';

        // Prefere o modelo configurável 'os' (Configurações > Modelos de WhatsApp);
        // se a tabela/modelo não existir, mantém o texto de notifica_whats.
        $this->ci->load->model('whatsapp_templates_model');

        return $this->ci->whatsapp_templates_model->conteudo('os', $fallback);
    }
}
