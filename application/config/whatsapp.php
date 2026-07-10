<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Configuração da notificação por WhatsApp via Evolution API.
 *
 * Todos os valores vêm do .env (editáveis pela aba "Configurar Sistema").
 * Segue o mesmo padrão de application/config/payment_gateways.php.
 */

// Lista de status da OS (separados por vírgula no .env) que disparam envio
// automático. Normalizada para minúsculas/sem espaços para comparação.
$autoStatus = array_filter(array_map(
    function ($s) {
        return mb_strtolower(trim($s));
    },
    explode(',', $_ENV['WHATSAPP_EVOLUTION_AUTO_STATUS'] ?? '')
));

$config['whatsapp'] = [
    'evolution' => [
        'enabled' => isset($_ENV['WHATSAPP_EVOLUTION_ENABLED'])
            ? filter_var($_ENV['WHATSAPP_EVOLUTION_ENABLED'], FILTER_VALIDATE_BOOLEAN)
            : false,
        // URL base da instância Evolution (ex.: https://evo.suaempresa.com). Sem barra final.
        'url' => rtrim($_ENV['WHATSAPP_EVOLUTION_URL'] ?? '', '/'),
        'apikey' => $_ENV['WHATSAPP_EVOLUTION_APIKEY'] ?? '',
        'instance' => $_ENV['WHATSAPP_EVOLUTION_INSTANCE'] ?? '',
        'timeout' => (int) ($_ENV['WHATSAPP_EVOLUTION_TIMEOUT'] ?? 30),
        // Verificação do certificado SSL. Padrão: true (verifica). Desligue
        // (false) apenas se o servidor Evolution usar certificado inválido/
        // self-signed — funciona, mas reduz a segurança da conexão.
        'verify_ssl' => isset($_ENV['WHATSAPP_EVOLUTION_VERIFY_SSL'])
            ? filter_var($_ENV['WHATSAPP_EVOLUTION_VERIFY_SSL'], FILTER_VALIDATE_BOOLEAN)
            : true,
        'auto_status' => array_values($autoStatus),
    ],
];
