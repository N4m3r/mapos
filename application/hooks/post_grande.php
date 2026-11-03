<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Envio maior que o post_max_size do PHP: o PHP descarta $_POST e $_FILES inteiros,
 * o token CSRF some e o CodeIgniter responde "The action you have requested is not allowed".
 * Roda em pre_system (antes da verificacao do CSRF) e devolve uma mensagem clara.
 */
if (! function_exists('verificar_post_grande')) {
    function bytes_ini($valor)
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return 0;
        }
        $n = (float) $valor;
        switch (strtolower(substr($valor, -1))) {
            case 'g': $n *= 1024;
                // no break
            case 'm': $n *= 1024;
                // no break
            case 'k': $n *= 1024;
        }

        return (int) $n;
    }

    function verificar_post_grande()
    {
        if (PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return;
        }
        $limite = bytes_ini(ini_get('post_max_size'));
        $tamanho = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($limite <= 0 || $tamanho <= $limite || ! empty($_POST) || ! empty($_FILES)) {
            return;
        }

        $mb = function ($b) { return number_format($b / 1048576, 1, ',', '.') . ' MB'; };
        $msg = 'Arquivo grande demais: o envio tem ' . $mb($tamanho) . ' e o limite do servidor é '
            . $mb($limite) . '. Envie menos fotos por vez ou arquivos menores.';

        $ajax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

        $hs = headers_sent();
        if (! $hs) {
            http_response_code(413);
        }
        if ($ajax) {
            if (! $hs) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(['success' => false, 'result' => false, 'message' => $msg]);
        } else {
            if (! $hs) {
                header('Content-Type: text/html; charset=utf-8');
            }
            echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="utf-8">'
                . '<meta name="viewport" content="width=device-width, initial-scale=1">'
                . '<title>Arquivo grande demais</title></head>'
                . '<body style="font-family:system-ui,sans-serif;max-width:520px;margin:40px auto;padding:0 16px;line-height:1.5">'
                . '<h2>Arquivo grande demais</h2><p>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p><a href="javascript:history.back()" style="display:inline-block;padding:12px 20px;background:#2d6cdf;color:#fff;border-radius:8px;text-decoration:none">Voltar</a></p>'
                . '</body></html>';
        }
        exit;
    }
}
