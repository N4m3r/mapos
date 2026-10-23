<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Envio da OS por e-mail (fila email_queue), conforme gatilhos e modelos de e-mail.
 *
 * Extraido do controller Os (ADR-003) sem mudanca de comportamento; o controller
 * continua chamando enviarOsPorEmail(), que so delega para ca.
 */
class Os_email
{
    /** @var CI_Controller */
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->model('os_model');
    }

    /**
     * Coloca a OS na fila de e-mail para os remetentes (ou os do gatilho do evento).
     * Retorna false se faltar e-mail do cliente/emitente ou se o envio estiver desativado.
     */
    public function enviar($idOs, $remetentes, $assunto, $evento = null)
    {
        $dados = [];

        $this->ci->load->model('mapos_model');
        $dados['result'] = $this->ci->os_model->getById($idOs);
        if (! isset($dados['result']->email)) {
            return false;
        }

        $dados['produtos'] = $this->ci->os_model->getProdutos($idOs);
        $dados['servicos'] = $this->ci->os_model->getServicos($idOs);
        $dados['emitente'] = $this->ci->mapos_model->getEmitente();
        $emitente = $dados['emitente'];
        if (! isset($emitente->email)) {
            return false;
        }

        // Gatilho de notificação (se informado): controla ativo, canal de e-mail,
        // destinatários, blocos e modelo. Sem gatilho, mantém o comportamento anterior.
        $blocos = null;
        $slug = 'os';
        if ($evento) {
            $this->ci->load->model('notification_triggers_model');
            $trigger = $this->ci->notification_triggers_model->getByEvento($evento);
            if ($trigger) {
                $canais = Notification_triggers_model::toList($trigger->canais);
                if ((int) $trigger->ativo !== 1 || ! in_array('email', $canais, true)) {
                    return false; // gatilho desativado ou sem canal de e-mail
                }
                $slug = $trigger->template_slug ?: 'os';
                $blocosList = Notification_triggers_model::toList($trigger->blocos);
                $blocos = empty($blocosList) ? null : $blocosList;
                $remetentes = $this->destinatarios($trigger, $dados['result'], $emitente);
            }
        }

        $html = $this->ci->load->view('os/emails/os', $dados, true);

        // Modelo configurável de e-mail (fallback para a view padrão acima).
        $this->ci->load->library('emailtemplate');
        $render = $this->ci->emailtemplate->render($slug, [
            'emitente' => $emitente,
            'os' => $dados['result'],
            'cliente' => $dados['result'],
            'produtos' => $dados['produtos'],
            'servicos' => $dados['servicos'],
            'blocos' => $blocos,
        ]);
        if ($render !== null && ! $render['ativo']) {
            // Envio de e-mail de OS desativado em Configurações > Modelos de E-mail.
            return false;
        }
        if ($render !== null) {
            $html = $render['corpo'];
            $assunto = $render['assunto'];
        }

        $this->ci->load->model('email_model');

        $remetentes = array_unique($remetentes);
        foreach ($remetentes as $remetente) {
            if ($remetente) {
                $headers = ['From' => $emitente->email, 'Subject' => $assunto, 'Return-Path' => ''];
                $email = [
                    'to' => $remetente,
                    'message' => $html,
                    'status' => 'pending',
                    'date' => date('Y-m-d H:i:s'),
                    'headers' => serialize($headers),
                ];
                $this->ci->email_model->add('email_queue', $email);
            } else {
                log_info('Email não adicionado a Lista de envio de e-mails. Verifique se o remetente esta cadastrado. OS ID: ' . $idOs);
            }
        }

        return true;
    }

    /**
     * Monta os e-mails de destino de uma OS conforme os destinatários marcados
     * no gatilho (cliente, e-mail secundário, técnico, emitente).
     */
    private function destinatarios($trigger, $os, $emitente)
    {
        $dest = Notification_triggers_model::toList($trigger->destinatarios);
        $emails = [];

        if (in_array('cliente', $dest, true) && ! empty($os->email)) {
            $emails[] = $os->email;
        }
        if (in_array('cliente_secundario', $dest, true) && ! empty($os->email_secundario)) {
            $emails[] = $os->email_secundario;
        }
        if (in_array('emitente', $dest, true) && ! empty($emitente->email)) {
            $emails[] = $emitente->email;
        }
        if (in_array('tecnico', $dest, true) && ! empty($os->usuarios_id)) {
            $this->ci->load->model('usuarios_model');
            $tecnico = $this->ci->usuarios_model->getById($os->usuarios_id);
            if ($tecnico && ! empty($tecnico->email)) {
                $emails[] = $tecnico->email;
            }
        }

        return $emails;
    }
}
