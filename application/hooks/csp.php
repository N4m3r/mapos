<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Content-Security-Policy (ADR-005).
 *
 * Scripts so executam com o nonce da requisicao (csp_nonce()), entao um script
 * injetado por XSS nao roda. Handlers inline (onclick=...) nao sao permitidos:
 * as telas usam assets/js/acoes.js (atributos data-*).
 *
 * APP_CSP no .env:
 *   relatorio (padrao) - so registra violacoes (Content-Security-Policy-Report-Only)
 *   bloquear           - aplica a politica
 *   desligado          - nao envia o cabecalho
 * Violacoes vao para application/logs/csp-violacoes.log (rota csp/relatorio).
 */
function enviar_csp()
{
    if (is_cli() || headers_sent() || ! function_exists('csp_nonce')) {
        return;
    }
    $modo = strtolower(trim((string) ($_ENV['APP_CSP'] ?? 'relatorio')));
    if ($modo === 'desligado') {
        return;
    }

    $nonce = csp_nonce();
    $politica = implode('; ', [
        "default-src 'self'",
        // strict-dynamic: scripts carregados por um script confiavel tambem valem; 'self' https: e so p/ navegador antigo
        "script-src 'nonce-{$nonce}' 'strict-dynamic' 'self' https:",
        "script-src-attr 'none'",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://unpkg.com",
        "img-src 'self' data: blob: https:",
        "font-src 'self' data: https://fonts.gstatic.com",
        "connect-src 'self' https:",
        "frame-src 'self' https:",
        "worker-src 'self' blob:",
        "object-src 'none'",
        "base-uri 'self'",
        "frame-ancestors 'self'",
        'report-uri ' . site_url('csp/relatorio'),
    ]);

    header(($modo === 'bloquear' ? 'Content-Security-Policy: ' : 'Content-Security-Policy-Report-Only: ') . $politica);
}
