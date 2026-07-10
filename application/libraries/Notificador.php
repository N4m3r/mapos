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

            $template = $this->configNotificaWhats();
            $mensagem = $this->ci->os_model->montarNotificacaoOs($os->idOs, $template, $emitente);
            $numeroCliente = $this->ci->os_model->numeroNotificacao($os);

            // Deduplica por destinatário (a mensagem é a mesma para todos os gatilhos).
            $enviouCliente = false;
            $enviouTecnico = false;
            $gruposEnviados = [];
            foreach ($triggers as $t) {
                $destCliente = $t ? in_array('cliente', Notification_triggers_model::toList($t->destinatarios), true) : true;
                $destTecnico = $t ? in_array('tecnico', Notification_triggers_model::toList($t->destinatarios), true) : false;

                if ($destCliente && ! $enviouCliente && ! empty($numeroCliente)) {
                    $this->ci->evolution_api->enviarTexto($numeroCliente, $mensagem);
                    log_info('Notificação WhatsApp (cliente) enviada. OS #' . $os->idOs);
                    $enviouCliente = true;
                }
                if ($destTecnico && ! $enviouTecnico && ! empty($os->telefone_usuario)) {
                    $this->ci->evolution_api->enviarTexto($os->telefone_usuario, $mensagem);
                    log_info('Notificação WhatsApp (técnico) enviada. OS #' . $os->idOs);
                    $enviouTecnico = true;
                }

                if ($t && ! empty($t->whatsapp_grupos)) {
                    foreach (Notification_triggers_model::toList($t->whatsapp_grupos) as $jid) {
                        if (! in_array($jid, $gruposEnviados, true)) {
                            $this->ci->evolution_api->enviarTexto($jid, $mensagem);
                            log_info('Notificação WhatsApp (grupo ' . $jid . ') enviada. OS #' . $os->idOs);
                            $gruposEnviados[] = $jid;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            log_info('Falha na notificação WhatsApp da OS #' . $osId . ': ' . $e->getMessage());
        }
    }

    private function configNotificaWhats()
    {
        $this->ci->load->database();
        $row = $this->ci->db->get_where('configuracoes', ['config' => 'notifica_whats'])->row();

        return $row ? $row->valor : '';
    }
}
