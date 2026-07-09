<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Receptor de webhooks externos (server-to-server). Estende CI_Controller
 * direto (NÃO MY_Controller) para não exigir login/sessão. A URI é isenta
 * de CSRF em config.php (csrf_exclude_uris: 'webhook/cora').
 */
class Webhook extends CI_Controller
{
    /**
     * Notificação da Cora (evento invoice.paid, etc). A Cora envia o id da
     * fatura no header `webhook-resource-id`. Reconsultamos a fatura na API
     * para confirmar o pagamento e dar baixa automática.
     */
    public function cora()
    {
        $resourceId = $this->input->get_request_header('webhook-resource-id');
        $eventType = $this->input->get_request_header('webhook-event-type');

        // Fallback: alguns envios trazem o id no corpo JSON.
        if (empty($resourceId)) {
            $raw = file_get_contents('php://input');
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $resourceId = $json['resource_id'] ?? ($json['id'] ?? ($json['resource']['id'] ?? null));
            }
        }

        try {
            if (! empty($resourceId)) {
                $this->load->library('Gateways/Cora', null, 'PaymentGateway');
                $this->PaymentGateway->processarWebhook($resourceId);
            } else {
                log_info('Webhook Cora recebido sem resource-id. Evento: ' . $eventType);
            }
        } catch (\Throwable $e) {
            log_info('Erro ao processar webhook Cora: ' . $e->getMessage());
        }

        // Sempre 200: evita reenvios infinitos da Cora.
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode(['received' => true]));
    }
}
