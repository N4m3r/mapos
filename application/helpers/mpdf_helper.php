<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Gera PDF via mPDF.
 *
 * Fotos em data URI (base64) incham o HTML e estouram pcre.backtrack_limit
 * (padrão 1.000.000). Antes de WriteHTML, as imagens inline viram arquivos
 * temporários e o limite PCRE é ajustado se ainda for necessário.
 */
function pdf_create($html, $filename, $stream = true, $landscape = false)
{
    $tempDir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, FCPATH . 'assets/uploads/temp'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (! is_dir($tempDir)) {
        @mkdir($tempDir, 0755, true);
    }

    $tempFiles = [];
    $html = mpdf_materialize_data_uri_images((string) $html, $tempDir, $tempFiles);

    // Segurança: se o HTML ainda for grande, sobe o backtrack_limit só nesta request.
    $htmlLen = strlen($html);
    $currentLimit = (int) ini_get('pcre.backtrack_limit');
    if ($currentLimit > 0 && $htmlLen >= $currentLimit) {
        @ini_set('pcre.backtrack_limit', (string) ($htmlLen + 500000));
    }

    try {
        $config = [
            'mode' => 'utf-8',
            'format' => $landscape ? 'A4-L' : 'A4',
            'tempDir' => $tempDir,
        ];
        $mpdf = new \Mpdf\Mpdf($config);
        $mpdf->showImageErrors = true;

        // Preferível: CSS + corpo em chamadas separadas (strings menores).
        mpdf_write_html_safe($mpdf, $html);

        if ($stream) {
            $mpdf->Output(preg_replace('/\.pdf$/i', '', $filename) . '.pdf', 'I');
        } else {
            $outName = preg_replace('/\.pdf$/i', '', $filename) . '.pdf';
            $path = $tempDir . $outName;
            $mpdf->Output($path, 'F');

            return $path;
        }
    } finally {
        foreach ($tempFiles as $file) {
            if (is_string($file) && is_file($file)) {
                @unlink($file);
            }
        }
    }
}

/**
 * WriteHTML em pedaços quando o HTML ainda for grande (recomendação do mPDF).
 */
function mpdf_write_html_safe(\Mpdf\Mpdf $mpdf, $html)
{
    $limit = (int) ini_get('pcre.backtrack_limit');
    if ($limit <= 0) {
        $limit = 1000000;
    }

    // Folga para o AdjustHTML do mPDF.
    $maxChunk = max(200000, $limit - 100000);

    if (strlen($html) <= $maxChunk) {
        $mpdf->WriteHTML($html);

        return;
    }

    // Tenta separar <style> do restante (comum nas views de relatório/RH).
    if (preg_match('/^(.*?<style\b[^>]*>.*?<\/style>)(.*)$/is', $html, $m)) {
        if (strlen($m[1]) <= $maxChunk) {
            $mpdf->WriteHTML($m[1], \Mpdf\HTMLParserMode::HEADER_CSS);
            mpdf_write_html_chunks($mpdf, $m[2], $maxChunk);

            return;
        }
    }

    mpdf_write_html_chunks($mpdf, $html, $maxChunk);
}

/**
 * Divide o HTML em pedaços em limites de tags (evita cortar no meio de atributos).
 */
function mpdf_write_html_chunks(\Mpdf\Mpdf $mpdf, $html, $maxChunk)
{
    $len = strlen($html);
    $offset = 0;

    while ($offset < $len) {
        if ($len - $offset <= $maxChunk) {
            $mpdf->WriteHTML(substr($html, $offset));
            break;
        }

        $sliceEnd = $offset + $maxChunk;
        // Prefere fechar o pedaço após um '>' próximo do limite.
        $gt = strrpos(substr($html, $offset, $maxChunk), '>');
        if ($gt !== false && $gt > (int) ($maxChunk * 0.5)) {
            $sliceEnd = $offset + $gt + 1;
        }

        $mpdf->WriteHTML(substr($html, $offset, $sliceEnd - $offset));
        $offset = $sliceEnd;
    }
}

/**
 * Converte src="data:image/...;base64,..." em arquivos temporários (sem regex no HTML inteiro).
 *
 * @param  string $html
 * @param  string $tempDir
 * @param  array  $tempFiles
 * @return string
 */
function mpdf_materialize_data_uri_images($html, $tempDir, array &$tempFiles)
{
    $needle = 'data:image/';
    $offset = 0;
    $out = '';
    $len = strlen($html);

    while (($pos = stripos($html, $needle, $offset)) !== false) {
        // Exige aspas imediatamente antes (src="data:image/...")
        if ($pos === 0) {
            $out .= substr($html, $offset, strlen($needle));
            $offset = $pos + strlen($needle);
            continue;
        }

        $quote = $html[$pos - 1];
        if ($quote !== '"' && $quote !== "'") {
            $out .= substr($html, $offset, $pos - $offset + strlen($needle));
            $offset = $pos + strlen($needle);
            continue;
        }

        $end = strpos($html, $quote, $pos);
        if ($end === false) {
            break;
        }

        $dataUri = substr($html, $pos, $end - $pos);
        $filePath = mpdf_data_uri_to_temp_file($dataUri, $tempDir, $tempFiles);

        $out .= substr($html, $offset, $pos - $offset);
        $out .= $filePath !== null ? $filePath : $dataUri;
        $offset = $end;
    }

    $out .= substr($html, $offset);

    return $out;
}

/**
 * Grava data URI em arquivo temp; redimensiona se GD estiver disponível (PDF leve).
 *
 * @return string|null caminho do arquivo ou null se falhar
 */
function mpdf_data_uri_to_temp_file($dataUri, $tempDir, array &$tempFiles)
{
    // Evita preg_* em strings multi-MB (também sujeitas a pcre.backtrack_limit).
    if (stripos($dataUri, 'data:image/') !== 0) {
        return null;
    }
    $marker = ';base64,';
    $semi = stripos($dataUri, $marker);
    if ($semi === false) {
        return null;
    }

    $subtype = strtolower(substr($dataUri, strlen('data:image/'), $semi - strlen('data:image/')));
    $payload = substr($dataUri, $semi + strlen($marker));
    $bin = base64_decode($payload, true);
    if ($bin === false || $bin === '') {
        return null;
    }

    $extMap = [
        'jpeg' => 'jpg',
        'jpg' => 'jpg',
        'png' => 'png',
        'gif' => 'gif',
        'webp' => 'webp',
        'svg+xml' => 'svg',
        'x-icon' => 'ico',
        'vnd.microsoft.icon' => 'ico',
    ];
    $ext = isset($extMap[$subtype]) ? $extMap[$subtype] : preg_replace('/[^a-z0-9]/', '', $subtype);
    if ($ext === '') {
        $ext = 'img';
    }

    // Reduz fotos grandes (ex.: 3MB) para o PDF — ficha/crachá não precisam de full-res.
    if ($ext !== 'svg' && $ext !== 'ico' && function_exists('imagecreatefromstring')) {
        $resized = mpdf_resize_image_binary($bin, 360, 480);
        if ($resized !== null) {
            $bin = $resized['data'];
            $ext = $resized['ext'];
        }
    }

    $path = $tempDir . 'mpdf_img_' . str_replace('.', '', uniqid('', true)) . '.' . $ext;
    if (@file_put_contents($path, $bin) === false) {
        return null;
    }

    $tempFiles[] = $path;

    return $path;
}

/**
 * Redimensiona imagem binária mantendo proporção (máx. $maxW x $maxH).
 * Retorna JPEG (qualidade 82) ou null se não for possível.
 *
 * @return array{data:string,ext:string}|null
 */
function mpdf_resize_image_binary($bin, $maxW = 360, $maxH = 480)
{
    $src = @imagecreatefromstring($bin);
    if ($src === false) {
        return null;
    }

    $w = imagesx($src);
    $h = imagesy($src);
    if ($w < 1 || $h < 1) {
        imagedestroy($src);

        return null;
    }

    $scale = min($maxW / $w, $maxH / $h, 1.0);
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));

    $dst = imagecreatetruecolor($nw, $nh);
    if ($dst === false) {
        imagedestroy($src);

        return null;
    }

    // Fundo branco (evita transparência preta em JPEG)
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $white);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    ob_start();
    imagejpeg($dst, null, 82);
    $data = ob_get_clean();

    imagedestroy($src);
    imagedestroy($dst);

    if ($data === false || $data === '') {
        return null;
    }

    return ['data' => $data, 'ext' => 'jpg'];
}
