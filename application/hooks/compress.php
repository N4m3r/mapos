<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

function compress()
{
    $CI = &get_instance();
    $buffer = $CI->output->get_output();

    // <script>, <pre> e <textarea> ficam intactos. Antes isso era feito com uma regex
    // de lookahead que se confundia quando o JavaScript tinha '<pre' ou '<textarea'
    // dentro de uma string: juntava as linhas do script e os comentarios // passavam
    // a engolir o codigo seguinte (ex.: tela de NF-e).
    $protegidos = [];
    $semBlocos = preg_replace_callback(
        '#<(script|pre|textarea)\b[^>]*>.*?</\1\s*>#is',
        function ($m) use (&$protegidos) {
            $protegidos[] = $m[0];

            return "\x1AMAPOSKEEP" . (count($protegidos) - 1) . "\x1A";
        },
        $buffer
    );

    // Mesmo resultado de antes fora dos blocos: qualquer espaco que nao seja um
    // unico ' ' (quebra de linha, tab, varios espacos) vira um espaco.
    $comprimido = $semBlocos === null ? null : preg_replace('/(?:[^\S ]\s*|\s{2,})/', ' ', $semBlocos);

    if ($comprimido === null) {
        // Falha de PCRE (pagina muito grande etc.): entrega o HTML original
        $new_buffer = $buffer;
    } else {
        $new_buffer = preg_replace_callback('/\x1AMAPOSKEEP(\d+)\x1A/', function ($m) use ($protegidos) {
            return $protegidos[(int) $m[1]];
        }, $comprimido);
        if ($new_buffer === null) {
            $new_buffer = $buffer;
        }
    }

    $CI->output->set_output($new_buffer);
    $CI->output->_display();
}

/* End of file compress.php */
/* Location: ./system/application/hooks/compress.php */
