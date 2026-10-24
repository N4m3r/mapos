<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Recebe os relatorios de violacao da Content-Security-Policy (hooks/csp.php)
 * e grava em application/logs/csp-violacoes.log (uma linha JSON por violacao).
 * Publica de proposito (o navegador envia sem login nem token CSRF).
 */
class Csp extends CI_Controller
{
    private const LIMITE_LOG = 2 * 1024 * 1024;

    public function relatorio()
    {
        $corpo = (string) file_get_contents('php://input', false, null, 0, 16384);
        $dados = json_decode($corpo, true);
        $r = $dados['csp-report'] ?? null;

        // O jQuery 1.12 (area do tecnico) testa no inicio se o navegador aceita handler em atributo;
        // com a CSP o teste so da "nao" e nada quebra. Nao vale registrar.
        if (is_array($r) && strpos((string) ($r['violated-directive'] ?? ''), 'script-src-attr') === 0
            && preg_match('#/jquery-1\.12\.4\.min\.js$#', (string) ($r['source-file'] ?? ''))) {
            $r = null;
        }

        if (is_array($r)) {
            $arquivo = APPPATH . 'logs/csp-violacoes.log';
            if (! is_file($arquivo) || filesize($arquivo) < self::LIMITE_LOG) {
                $linha = [
                    'em' => date('Y-m-d H:i:s'),
                    'pagina' => mb_substr((string) ($r['document-uri'] ?? ''), 0, 300),
                    'diretiva' => mb_substr((string) ($r['violated-directive'] ?? $r['effective-directive'] ?? ''), 0, 60),
                    'bloqueado' => mb_substr((string) ($r['blocked-uri'] ?? ''), 0, 300),
                    'linha' => isset($r['line-number']) ? (int) $r['line-number'] : null,
                    'trecho' => mb_substr((string) ($r['script-sample'] ?? ''), 0, 120),
                ];
                file_put_contents($arquivo, json_encode($linha, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
            }
        }

        $this->output->set_status_header(204);
    }
}
