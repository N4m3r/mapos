<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Notificações por WhatsApp via Evolution API.
 *
 * Endpoints AJAX (JSON) usados pelos botões "Enviar via WhatsApp" das telas de
 * OS, vendas/cobrança e link de aprovação, além do teste de conexão na tela de
 * configuração. Envio programático (server-side) — não abre o WhatsApp Web.
 */
class Whatsapp extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('evolution_api');
    }

    private function json($payload, $status = 200)
    {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($status)
            ->set_output(json_encode($payload));
    }

    /**
     * Testa a conexão com a instância Evolution (botão na tela Configurar).
     */
    public function testar()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema')) {
            return $this->json(['result' => false, 'mensagem' => 'Sem permissão.'], 403);
        }

        $numero = trim((string) $this->input->post('numero'));

        // 1) Testa a conexão (best-effort — não aborta o teste de envio, pois em
        // algumas instalações o endpoint de status difere/falha mesmo com o envio OK).
        $estado = null;
        $connErro = null;
        try {
            $estado = $this->evolution_api->testarConexao();
        } catch (\Exception $e) {
            $connErro = $e->getMessage();
        }
        $conectado = in_array($estado, ['open', 'connected'], true);
        $msgConn = $connErro !== null
            ? 'Conexão: falhou (' . $connErro . ').'
            : ($conectado
                ? 'Conexão OK (estado: ' . $estado . ').'
                : 'A instância respondeu, mas não está conectada (estado: ' . $estado . ').');

        // 2) Sem número: só reporta a conexão.
        if ($numero === '') {
            if ($connErro !== null) {
                return $this->json(['result' => false, 'mensagem' => $msgConn], 400);
            }

            return $this->json(['result' => $conectado, 'estado' => $estado, 'mensagem' => $msgConn]);
        }

        // 3) Com número: envia uma mensagem de teste (independente do status acima).
        try {
            $this->evolution_api->enviarTexto(
                $numero,
                'Mensagem de teste do Mapos (Evolution API). Se você recebeu isto, o envio de WhatsApp está funcionando! ✅'
            );

            return $this->json([
                'result' => true,
                'mensagem' => 'Mensagem de teste enviada para ' . $this->evolution_api->formatarNumero($numero) . ' — confira o WhatsApp. ' . $msgConn,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'result' => false,
                'mensagem' => 'Falha ao ENVIAR a mensagem de teste: ' . $e->getMessage() . ' | ' . $msgConn,
            ], 400);
        }
    }

    /**
     * Envia a notificação de uma OS ao celular do cliente.
     */
    public function enviarOs($idOs = null)
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            return $this->json(['result' => false, 'mensagem' => 'Sem permissão.'], 403);
        }

        $this->load->model('os_model');
        $os = is_numeric($idOs) ? $this->os_model->getById($idOs) : null;
        if (! $os) {
            return $this->json(['result' => false, 'mensagem' => 'OS não encontrada.'], 404);
        }

        if (empty($this->os_model->numeroNotificacao($os))) {
            return $this->json(['result' => false, 'mensagem' => 'Cliente sem número de notificação/celular cadastrado.'], 400);
        }

        $template = $this->data['configuration']['notifica_whats'] ?? '';
        $mensagem = $this->os_model->montarNotificacaoOs($idOs, $template);

        try {
            $this->evolution_api->enviarTexto($this->os_model->numeroNotificacao($os), $mensagem);
            log_info('Enviou notificação WhatsApp (Evolution) da OS #' . $idOs);

            return $this->json(['result' => true, 'mensagem' => 'Notificação enviada por WhatsApp!']);
        } catch (\Exception $e) {
            return $this->json(['result' => false, 'mensagem' => $e->getMessage()], 400);
        }
    }

    /**
     * Envia o link de pagamento/PIX de uma cobrança ao celular do cliente.
     */
    public function enviarCobranca($idCobranca = null)
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vCobranca')) {
            return $this->json(['result' => false, 'mensagem' => 'Sem permissão.'], 403);
        }

        $this->load->model('cobrancas_model');
        $this->load->model('os_model');
        $cobranca = is_numeric($idCobranca) ? $this->cobrancas_model->getById($idCobranca) : null;
        if (! $cobranca) {
            return $this->json(['result' => false, 'mensagem' => 'Cobrança não encontrada.'], 404);
        }

        $numero = $this->os_model->numeroNotificacao($cobranca);
        if (empty($numero)) {
            return $this->json(['result' => false, 'mensagem' => 'Cliente sem número de notificação/celular cadastrado.'], 400);
        }

        $link = $cobranca->payment_url ?: $cobranca->link;
        if (empty($link)) {
            return $this->json(['result' => false, 'mensagem' => 'Cobrança sem link de pagamento.'], 400);
        }

        $referencia = $cobranca->os_id ? ('OS #' . $cobranca->os_id) : ('Venda #' . $cobranca->vendas_id);
        $mensagem = 'Olá ' . $cobranca->nomeCliente . '! Segue o link para pagamento da ' . $referencia . ":\n" . $link;

        try {
            $this->evolution_api->enviarTexto($numero, $mensagem);
            log_info('Enviou cobrança por WhatsApp (Evolution). Cobrança #' . $idCobranca);

            return $this->json(['result' => true, 'mensagem' => 'Link de pagamento enviado por WhatsApp!']);
        } catch (\Exception $e) {
            return $this->json(['result' => false, 'mensagem' => $e->getMessage()], 400);
        }
    }

    /**
     * Envia o link público de aprovação de uma OS ao celular do cliente.
     * Gera o link se ainda não existir um ativo.
     */
    public function enviarLinkAprovacao($idOs = null)
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            return $this->json(['result' => false, 'mensagem' => 'Sem permissão.'], 403);
        }

        $this->load->model('os_model');
        $this->load->model('aprovacao_model');

        $os = is_numeric($idOs) ? $this->os_model->getById($idOs) : null;
        if (! $os) {
            return $this->json(['result' => false, 'mensagem' => 'OS não encontrada.'], 404);
        }
        if (! $this->aprovacao_model->suportado()) {
            return $this->json(['result' => false, 'mensagem' => 'Recurso indisponível: execute a atualização do banco (updates/update_os_aprovacao.sql).'], 400);
        }
        if (empty($this->os_model->numeroNotificacao($os))) {
            return $this->json(['result' => false, 'mensagem' => 'Cliente sem número de notificação/celular cadastrado.'], 400);
        }

        // Reaproveita o token ativo; só gera um novo se não houver/estiver expirado.
        $token = $os->aprovacao_token ?? null;
        $situacao = $this->aprovacao_model->situacao($os);
        if (empty($token) || in_array($situacao, ['invalido', 'expirado'], true)) {
            $info = $this->aprovacao_model->gerarLink($idOs, 7);
            if (! $info) {
                return $this->json(['result' => false, 'mensagem' => 'Erro ao gerar o link de aprovação.'], 500);
            }
            $token = $info['token'];
        }

        $url = site_url('aprovacao/' . $token);
        $mensagem = 'Olá ' . $os->nomeCliente . '! Para aprovar ou reprovar a OS #' . $idOs
            . ', acesse o link:' . "\n" . $url;

        try {
            $this->evolution_api->enviarTexto($this->os_model->numeroNotificacao($os), $mensagem);
            log_info('Enviou link de aprovação por WhatsApp (Evolution) da OS #' . $idOs);

            return $this->json(['result' => true, 'mensagem' => 'Link de aprovação enviado por WhatsApp!', 'url' => $url]);
        } catch (\Exception $e) {
            return $this->json(['result' => false, 'mensagem' => $e->getMessage()], 400);
        }
    }

    /**
     * Envia o link público de ACEITE do serviço realizado ao celular do cliente.
     * Gera o link se ainda não existir um ativo.
     */
    public function enviarLinkAceite($idOs = null)
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            return $this->json(['result' => false, 'mensagem' => 'Sem permissão.'], 403);
        }

        $this->load->model('os_model');
        $this->load->model('aceite_model');

        $os = is_numeric($idOs) ? $this->os_model->getById($idOs) : null;
        if (! $os) {
            return $this->json(['result' => false, 'mensagem' => 'OS não encontrada.'], 404);
        }
        if (! $this->aceite_model->suportado()) {
            return $this->json(['result' => false, 'mensagem' => 'Recurso indisponível: execute a atualização do banco (updates/update_os_aceite.sql).'], 400);
        }
        if (empty($this->os_model->numeroNotificacao($os))) {
            return $this->json(['result' => false, 'mensagem' => 'Cliente sem número de notificação/celular cadastrado.'], 400);
        }

        // Reaproveita o token ativo; só gera um novo se não houver/estiver expirado.
        $token = $os->aceite_token ?? null;
        $situacao = $this->aceite_model->situacao($os);
        if (empty($token) || in_array($situacao, ['invalido', 'expirado'], true)) {
            $info = $this->aceite_model->gerarLink($idOs, 7);
            if (! $info) {
                return $this->json(['result' => false, 'mensagem' => 'Erro ao gerar o link de aceite.'], 500);
            }
            $token = $info['token'];
        }

        $url = site_url('aceite/' . $token);
        $mensagem = 'Olá ' . $os->nomeCliente . '! Seu serviço (OS #' . $idOs . ') foi concluído. '
            . 'Confirme o aceite e assine pelo link:' . "\n" . $url;

        try {
            $this->evolution_api->enviarTexto($this->os_model->numeroNotificacao($os), $mensagem);
            log_info('Enviou link de aceite por WhatsApp (Evolution) da OS #' . $idOs);

            return $this->json(['result' => true, 'mensagem' => 'Link de aceite enviado por WhatsApp!', 'url' => $url]);
        } catch (\Exception $e) {
            return $this->json(['result' => false, 'mensagem' => $e->getMessage()], 400);
        }
    }
}
