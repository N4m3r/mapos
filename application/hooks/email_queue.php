<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Disparo automático da fila de e-mails.
 *
 * Roda no hook post_system (depois que a página já foi enviada ao navegador),
 * limitado a uma execução a cada INTERVALO segundos. Assim a fila de e-mails
 * (cobranças, boletos, OS, etc.) é enviada pelo próprio sistema a cada ~2 min,
 * sem depender de cron externo. O throttle usa o mtime de um arquivo de lock
 * e um flock não-bloqueante evita que duas requisições simultâneas processem a
 * fila ao mesmo tempo.
 */
function dispatch_email_queue()
{
    // Em CLI o envio continua sendo feito por "php index.php email/process".
    if (is_cli()) {
        return;
    }

    $lockFile = APPPATH . 'cache/email_queue.lock';

    // Curto-circuito barato: se disparou há menos que o piso (30s), nem consulta
    // o banco para descobrir o intervalo configurado.
    if (is_file($lockFile) && (time() - filemtime($lockFile)) < 30) {
        return;
    }

    // Intervalo configurável em Configurações > Notificações (piso de 30s).
    $intervalo = 120;
    try {
        $CI = &get_instance();
        if (! isset($CI->db)) {
            $CI->load->database();
        }
        $row = $CI->db->where('config', 'notif_intervalo_disparo')->limit(1)->get('configuracoes')->row();
        if ($row && (int) $row->valor >= 30) {
            $intervalo = (int) $row->valor;
        }
    } catch (\Throwable $e) {
        $intervalo = 120;
    }

    // Se o último disparo foi há menos que o intervalo, não faz nada.
    if (is_file($lockFile) && (time() - filemtime($lockFile)) < $intervalo) {
        return;
    }

    // Trava exclusiva não-bloqueante: só um processo por vez.
    $handle = @fopen($lockFile, 'c');
    if ($handle === false) {
        return;
    }
    if (! flock($handle, LOCK_EX | LOCK_NB)) {
        fclose($handle);

        return;
    }

    // Revalida o intervalo já com a trava adquirida (evita corrida).
    clearstatcache(true, $lockFile);
    if ((time() - filemtime($lockFile)) < $intervalo && filesize($lockFile) > 0) {
        flock($handle, LOCK_UN);
        fclose($handle);

        return;
    }

    // Marca o instante deste disparo.
    ftruncate($handle, 0);
    fwrite($handle, (string) time());
    fflush($handle);
    touch($lockFile);

    // Devolve a resposta ao usuário antes de processar a fila, quando possível.
    if (function_exists('fastcgi_finish_request')) {
        @fastcgi_finish_request();
    }
    @ignore_user_abort(true);

    try {
        $CI = &get_instance();
        $CI->load->library('email');
        $CI->email->send_queue();
    } catch (\Throwable $e) {
        log_message('error', 'Falha ao processar fila de e-mails: ' . $e->getMessage());
    }

    flock($handle, LOCK_UN);
    fclose($handle);
}

/* End of file email_queue.php */
/* Location: ./application/hooks/email_queue.php */
