<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 *
 * @link		http://codeigniter.com
 * @since		Version 1.0
 *
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Security Helpers
 *
 * @category	Helpers
 *
 * @author		ExpressionEngine Dev Team
 *
 * @link		http://codeigniter.com/user_guide/helpers/security_helper.html
 */

// ------------------------------------------------------------------------

/**
 * XSS Filtering
 *
 * @param	string
 * @param	bool	whether or not the content is an image file
 * @return string
 */
if (! function_exists('xss_clean')) {
    function xss_clean($str, $is_image = false)
    {
        $CI = &get_instance();

        return $CI->security->xss_clean($str, $is_image);
    }
}

// ------------------------------------------------------------------------

/**
 * Sanitize Filename
 *
 * @param	string
 * @return string
 */
if (! function_exists('sanitize_filename')) {
    function sanitize_filename($filename)
    {
        $CI = &get_instance();

        return $CI->security->sanitize_filename($filename);
    }
}

// --------------------------------------------------------------------

/**
 * Hash encode a string
 *
 * @param	string
 * @return string
 */
if (! function_exists('do_hash')) {
    function do_hash($str, $type = 'sha1')
    {
        if ($type == 'sha1') {
            return sha1($str);
        } else {
            return md5($str);
        }
    }
}

// ------------------------------------------------------------------------

/**
 * Strip Image Tags
 *
 * @param	string
 * @return string
 */
if (! function_exists('strip_image_tags')) {
    function strip_image_tags($str)
    {
        $str = preg_replace("#<img\s+.*?src\s*=\s*[\"'](.+?)[\"'].*?\>#", '\\1', $str);
        $str = preg_replace("#<img\s+.*?src\s*=\s*(.+?).*?\>#", '\\1', $str);

        return $str;
    }
}

// ------------------------------------------------------------------------

/**
 * Convert PHP tags to entities
 *
 * @param	string
 * @return string
 */
if (! function_exists('encode_php_tags')) {
    function encode_php_tags($str)
    {
        return str_replace(['<?php', '<?PHP', '<?', '?>'], ['&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'], $str);
    }
}

// ------------------------------------------------------------------------

/**
 * Tags permitidas na saida de campos ricos, com os atributos de cada uma.
 */
function html_seguro_tags()
{
    return [
        'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [],
        'u' => [], 'ol' => [], 'ul' => [], 'li' => [], 'blockquote' => [],
        'span' => [], 'div' => [],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [],
        'td' => ['colspan', 'rowspan'], 'th' => ['colspan', 'rowspan'],
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height'],
    ];
}

/**
 * Tags removidas junto com o conteudo (nao adianta manter o texto delas).
 */
function html_seguro_tags_proibidas()
{
    return [
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'form',
        'input', 'textarea', 'button', 'select', 'option', 'link', 'meta',
        'base', 'applet', 'frame', 'frameset', 'noscript', 'template',
    ];
}

/**
 * Devolve a URL quando ela e segura (http, https, mailto, relativa) ou null.
 */
function html_seguro_url($url, $permitir_data_imagem = false)
{
    $teste = strtolower(preg_replace('/[\x00-\x20\x7F]+/', '', (string) $url));
    if ($permitir_data_imagem && preg_match('/^data:image\/(png|jpe?g|gif|webp);base64,/', $teste)) {
        return $url;
    }
    if (preg_match('/^(javascript|vbscript|data|file|jar):/', $teste)) {
        return null;
    }

    return $url;
}

/**
 * Percorre o DOM removendo tags/atributos fora da allowlist.
 */
function html_seguro_limpar(DOMNode $no, $tags, $proibidas)
{
    if (! $no->hasChildNodes()) {
        return;
    }
    foreach (iterator_to_array($no->childNodes) as $filho) {
        if ($filho instanceof DOMElement) {
            $tag = strtolower($filho->nodeName);
            if (in_array($tag, $proibidas, true)) {
                $no->removeChild($filho);
                continue;
            }
            if (! isset($tags[$tag])) {
                // Tag desconhecida: remove a tag e preserva o conteudo.
                while ($filho->firstChild) {
                    $no->insertBefore($filho->firstChild, $filho);
                }
                $no->removeChild($filho);
                continue;
            }
            foreach (iterator_to_array($filho->attributes) as $attr) {
                $nome = strtolower($attr->nodeName);
                if (strpos($nome, 'on') === 0 || ! in_array($nome, $tags[$tag], true)) {
                    $filho->removeAttribute($attr->nodeName);
                    continue;
                }
                if ($nome === 'href' || $nome === 'src') {
                    $url = html_seguro_url($attr->nodeValue, $tag === 'img' && $nome === 'src');
                    if ($url === null) {
                        $filho->removeAttribute($attr->nodeName);
                    } else {
                        $filho->setAttribute($attr->nodeName, $url);
                    }
                }
            }
            html_seguro_limpar($filho, $tags, $proibidas);
        } elseif (! ($filho instanceof DOMText)) {
            // Comentarios e instrucoes de processamento: fora.
            $no->removeChild($filho);
        }
    }
}

/**
 * Sanitiza HTML pelos allowlists acima. Devolve string sempre segura.
 */
function html_seguro_sanitizar($html)
{
    if (! class_exists('DOMDocument')) {
        return nl2br(html_escape($html));
    }

    $tags = html_seguro_tags();
    $proibidas = html_seguro_tags_proibidas();
    $html = preg_replace('/<\?xml[^>]*>/i', '', (string) $html);

    $anteriores = libxml_use_internal_errors(true);
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadHTML(
        '<?xml encoding="UTF-8"><div id="html-seguro-raiz">' . $html . '</div>',
        LIBXML_NODEFDTD
    );

    $xpath = new DOMXPath($dom);
    $nos = $xpath->query('//*[@id="html-seguro-raiz"]');
    $saida = '';
    if ($nos->length) {
        $raiz = $nos->item(0);
        html_seguro_limpar($raiz, $tags, $proibidas);
        foreach ($raiz->childNodes as $filho) {
            $saida .= $dom->saveHTML($filho);
        }
    }
    libxml_clear_errors();
    libxml_use_internal_errors($anteriores);

    return $saida;
}

/**
 * Saida segura para campo que pode conter HTML (editor WYSIWYG de OS/garantias).
 *
 * Sem tag nenhuma, cai no html_escape — que e o caso comum. Com tag, passa pela
 * allowlist: mantem a formatacao e remove script, evento e URL perigosa.
 *
 * @param   string
 * @return  string
 */
if (! function_exists('html_seguro')) {
    function html_seguro($valor)
    {
        $valor = (string) $valor;
        if (trim($valor) === '') {
            return '';
        }
        if (! preg_match('/<\s*(?:p|br|strong|b|em|i|u|ol|ul|li|blockquote|span|div|h[1-6]|table|thead|tbody|tr|td|th|a|img)\b/i', $valor)) {
            return html_escape($valor);
        }

        return html_seguro_sanitizar($valor);
    }
}

/* End of file security_helper.php */
/* Location: ./system/helpers/security_helper.php */
