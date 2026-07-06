<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

$config['payment_gateways'] = [
    'GerencianetSdk' => [
        'name' => 'GerenciaNet (Efí)',
        'library_name' => 'GerencianetSdk',
        'production' => isset($_ENV['PAYMENT_GATEWAYS_EFI_PRODUCTION']) ? filter_var($_ENV['PAYMENT_GATEWAYS_EFI_PRODUCTION'], FILTER_VALIDATE_BOOLEAN) : false,
        'credentials' => [
            'client_id' => $_ENV['PAYMENT_GATEWAYS_EFI_CREDENTIAIS_CLIENT_ID'] ?? '',
            'client_secret' => $_ENV['PAYMENT_GATEWAYS_EFI_CREDENTIAIS_CLIENT_SECRET'] ?? '',
        ],
        'timeout' => $_ENV['PAYMENT_GATEWAYS_EFI_TIMEOUT'] ?? 30,
        'boleto_expiration' => $_ENV['PAYMENT_GATEWAYS_EFI_BOLETO_EXPIRATION'] ?? 'P3D',
        'payment_methods' => [
            [
                'name' => 'Boleto',
                'value' => 'boleto',
            ],
            [
                'name' => 'Link',
                'value' => 'link',
            ],
        ],
        'transaction_status' => [
            'new' => 'Cobrança / Assinatura gerada',
            'waiting' => 'Aguardando a confirmação do pagamento',
            'paid' => 'Pagamento confirmado',
            'identified' => 'Pagamento identificado',
            'unpaid' => 'Não foi possível confirmar o pagamento da cobrança',
            'refunded' => 'Pagamento devolvido pelo lojista ou pelo intermediador Gerencianet',
            'contested' => 'Pagamento em processo de contestação',
            'canceled' => 'Cobrança/Assinatura cancelada pelo vendedor ou pelo pagador ',
            'settled' => 'Cobrança/Pagamento foi confirmada manualmente ',
            'link' => 'Link de pagamento',
            'expired' => 'Link/Assinatura de pagamento expirado',
            'active' => 'Assinatura ativa Todas as cobranças estão sendo geradas',
            'finished' => 'Carnê está finalizado',
            'up_to_date' => 'Carnê encontra-se em dia',
        ],
    ],
    'MercadoPago' => [
        'name' => 'MercadoPago',
        'library_name' => 'MercadoPago',
        'credentials' => [
            'access_token' => $_ENV['PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_ACCESS_TOKEN'] ?? '',
            'public_key' => $_ENV['PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_PUBLIC_KEY'] ?? '',
            'client_secret' => $_ENV['PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_CLIENT_ID'] ?? '',
            'client_id' => $_ENV['PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_CLIENT_SECRET'] ?? '',
            'integrator_id' => $_ENV['PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_INTEGRATOR_ID'] ?? '',
            'platform_id' => $_ENV['PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_PLATFORM_ID'] ?? '',
            'corporation_id' => $_ENV['PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_CORPORATION_ID'] ?? '',
        ],
        'boleto_expiration' => $_ENV['PAYMENT_GATEWAYS_MERCADO_PAGO_BOLETO_EXPIRATION'] ?? 'P3D',
        'payment_methods' => [
            [
                'name' => 'Boleto',
                'value' => 'boleto',
            ],
        ],
        'transaction_status' => [
            'pending' => 'O usuário ainda não concluiu o processo de pagamento',
            'approved' => 'O pagamento foi aprovado e credenciado',
            'authorized' => 'O pagamento foi autorizado, mas ainda não foi capturado',
            'in_process' => 'O pagamento está sendo revisado',
            'in_mediation' => 'Os usuários iniciaram uma disputa',
            'rejected' => 'O pagamento foi rejeitado, o usuário pode tentar o pagamento novamente',
            'cancelled' => 'O pagamento foi cancelado por uma das partes ou porque o prazo para pagamento expirou',
            'refunded' => 'O pagamento foi reembolsado ao usuário',
            'charged_back' => 'Foi feito um estorno no cartão de crédito do comprador',
        ],
    ],
    'Cora' => [
        'name' => 'Cora (Boleto + PIX)',
        'library_name' => 'Cora',
        // Emitido exclusivamente a partir de uma nota fiscal (NF-e/NFS-e);
        // fica fora do seletor genérico "Gerar Pagamento".
        'fiscal_only' => true,
        'production' => isset($_ENV['PAYMENT_GATEWAYS_CORA_PRODUCTION']) ? filter_var($_ENV['PAYMENT_GATEWAYS_CORA_PRODUCTION'], FILTER_VALIDATE_BOOLEAN) : false,
        'credentials' => [
            'client_id' => $_ENV['PAYMENT_GATEWAYS_CORA_CLIENT_ID'] ?? '',
            // Certificado (PEM) e chave privada emitidos pela Cora (mTLS).
            'certificate_path' => $_ENV['PAYMENT_GATEWAYS_CORA_CERT_PATH'] ?? (APPPATH . 'arquivos_fiscais/cora/certificate.pem'),
            'private_key_path' => $_ENV['PAYMENT_GATEWAYS_CORA_KEY_PATH'] ?? (APPPATH . 'arquivos_fiscais/cora/private-key.key'),
        ],
        'timeout' => $_ENV['PAYMENT_GATEWAYS_CORA_TIMEOUT'] ?? 30,
        'boleto_expiration' => $_ENV['PAYMENT_GATEWAYS_CORA_BOLETO_EXPIRATION'] ?? 'P3D',
        'payment_methods' => [
            [
                'name' => 'Boleto + PIX',
                'value' => 'boleto',
            ],
        ],
        'transaction_status' => [
            'OPEN' => 'Aguardando pagamento',
            'IN_PAYMENT' => 'Pagamento em processamento',
            'PAID' => 'Pago',
            'LATE' => 'Vencido',
            'CANCELLED' => 'Cancelado',
            'DRAFT' => 'Rascunho',
        ],
    ],
    'Asaas' => [
        'name' => 'Asaas',
        'library_name' => 'Asaas',
        'production' => isset($_ENV['PAYMENT_GATEWAYS_ASAAS_PRODUCTION']) ? filter_var($_ENV['PAYMENT_GATEWAYS_ASAAS_PRODUCTION'], FILTER_VALIDATE_BOOLEAN) : false,
        'notify' => isset($_ENV['PAYMENT_GATEWAYS_ASAAS_NOTIFY']) ? filter_var($_ENV['PAYMENT_GATEWAYS_ASAAS_NOTIFY'], FILTER_VALIDATE_BOOLEAN) : false,
        'credentials' => [
            'api_key' => $_ENV['PAYMENT_GATEWAYS_ASAAS_CREDENTIAIS_API_KEY'] ?? '',
        ],
        'boleto_expiration' => $_ENV['PAYMENT_GATEWAYS_ASAAS_BOLETO_EXPIRATION'] ?? 'P3D',
        'payment_methods' => [
            [
                'name' => 'Boleto',
                'value' => 'boleto',
            ],
            [
                'name' => 'Link',
                'value' => 'link',
            ],
        ],
        'transaction_status' => [
            'PENDING' => 'Aguardando pagamento',
            'RECEIVED' => 'Recebida (saldo já creditado na conta)',
            'CONFIRMED' => 'Pagamento confirmado (saldo ainda não creditado)',
            'OVERDUE' => 'Vencida',
            'REFUNDED' => 'Estornada',
            'RECEIVED_IN_CASH' => 'Recebida em dinheiro (não gera saldo na conta)',
            'REFUND_REQUESTED' => 'Estorno Solicitado',
            'CHARGEBACK_REQUESTED' => 'Recebido chargeback',
            'CHARGEBACK_DISPUTE' => 'Em disputa de chargeback (caso sejam apresentados documentos para contestação)',
            'AWAITING_CHARGEBACK_REVERSAL' => 'Disputa vencida, aguardando repasse da adquirente',
            'DUNNING_REQUESTED' => 'Em processo de recuperação',
            'DUNNING_RECEIVED' => 'Recuperada',
            'AWAITING_RISK_ANALYSIS' => 'Pagamento em análise',
        ],
    ],
];
