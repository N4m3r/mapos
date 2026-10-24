<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Verificacao em duas etapas (ADR-005, F3).
 *
 * Codigo de 6 digitos de aplicativo autenticador (TOTP, RFC 6238: SHA-1, 30s),
 * aceitando 1 passo de diferenca no relogio e recusando reuso do mesmo codigo.
 * O segredo fica cifrado no banco (AES-256-GCM com chave derivada do
 * APP_ENCRYPTION_KEY). Codigos de recuperacao sao guardados so como hash e
 * valem uma vez cada.
 */
class Dois_fatores
{
    public const DIGITOS = 6;

    public const PERIODO = 30;

    public const QTD_RECUPERACAO = 10;

    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /* ======================= TOTP ======================= */

    /** Segredo novo em base32 (160 bits). */
    public static function gerarSegredo()
    {
        return self::base32Encode(random_bytes(20));
    }

    /** Codigo de 6 digitos para um passo de 30s. */
    public static function codigoNoPasso($segredo, $passo)
    {
        $chave = self::base32Decode($segredo);
        $mensagem = pack('N2', ($passo >> 32) & 0xFFFFFFFF, $passo & 0xFFFFFFFF);
        $hash = hash_hmac('sha1', $mensagem, $chave, true);
        $offset = ord($hash[19]) & 0x0F;
        $numero = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($numero % (10 ** self::DIGITOS)), self::DIGITOS, '0', STR_PAD_LEFT);
    }

    public static function passoAtual($agora = null)
    {
        return intdiv($agora ?? time(), self::PERIODO);
    }

    /**
     * Passo em que o codigo confere (atual, anterior ou seguinte) ou null.
     * Passos ate $ultimoPasso sao recusados para o mesmo codigo nao valer duas vezes.
     */
    public static function passoDoCodigo($segredo, $codigo, $ultimoPasso = null, $agora = null)
    {
        $codigo = preg_replace('/\D/', '', (string) $codigo);
        if (strlen($codigo) !== self::DIGITOS) {
            return null;
        }
        $atual = self::passoAtual($agora);
        foreach ([$atual, $atual - 1, $atual + 1] as $passo) {
            if ($ultimoPasso !== null && $passo <= (int) $ultimoPasso) {
                continue;
            }
            if (hash_equals(self::codigoNoPasso($segredo, $passo), $codigo)) {
                return $passo;
            }
        }

        return null;
    }

    /** URI otpauth:// que o aplicativo autenticador le pelo QR code. */
    public static function uri($segredo, $conta, $emissor)
    {
        $rotulo = rawurlencode($emissor) . ':' . rawurlencode($conta);

        return 'otpauth://totp/' . $rotulo . '?secret=' . $segredo
            . '&issuer=' . rawurlencode($emissor) . '&algorithm=SHA1&digits=' . self::DIGITOS . '&period=' . self::PERIODO;
    }

    /** QR code (data URI SVG) da URI otpauth. */
    public static function qrCode($uri)
    {
        return (new QRCode(new QROptions(['outputBase64' => true])))->render($uri);
    }

    /* ================== Codigos de recuperacao ================== */

    /**
     * @return array{0: string[], 1: string} [codigos para mostrar ao usuario, JSON com os hashes para o banco]
     */
    public static function gerarCodigosRecuperacao()
    {
        $codigos = [];
        $hashes = [];
        for ($i = 0; $i < self::QTD_RECUPERACAO; $i++) {
            $bruto = strtolower(self::base32Encode(random_bytes(5)));
            $codigo = substr($bruto, 0, 4) . '-' . substr($bruto, 4, 4);
            $codigos[] = $codigo;
            $hashes[] = password_hash($codigo, PASSWORD_DEFAULT);
        }

        return [$codigos, json_encode($hashes)];
    }

    /**
     * JSON atualizado (sem o codigo usado) se o codigo de recuperacao confere, ou null.
     */
    public static function consumirCodigoRecuperacao($json, $codigo)
    {
        $codigo = strtolower(trim((string) $codigo));
        if (! preg_match('/^[a-z2-7]{4}-?[a-z2-7]{4}$/', $codigo)) {
            return null;
        }
        if (strpos($codigo, '-') === false) {
            $codigo = substr($codigo, 0, 4) . '-' . substr($codigo, 4);
        }
        $hashes = json_decode((string) $json, true) ?: [];
        foreach ($hashes as $i => $hash) {
            if (password_verify($codigo, $hash)) {
                unset($hashes[$i]);

                return json_encode(array_values($hashes));
            }
        }

        return null;
    }

    public static function codigosRestantes($json)
    {
        return count(json_decode((string) $json, true) ?: []);
    }

    /* ==================== Segredo cifrado ==================== */

    public static function cifrar($segredo)
    {
        $iv = random_bytes(12);
        $tag = '';
        $cifrado = openssl_encrypt($segredo, 'aes-256-gcm', self::chave(), OPENSSL_RAW_DATA, $iv, $tag);

        return base64_encode($iv . $tag . $cifrado);
    }

    public static function decifrar($valor)
    {
        $bruto = base64_decode((string) $valor, true);
        if ($bruto === false || strlen($bruto) < 29) {
            return null;
        }
        $texto = openssl_decrypt(substr($bruto, 28), 'aes-256-gcm', self::chave(), OPENSSL_RAW_DATA, substr($bruto, 0, 12), substr($bruto, 12, 16));

        return $texto === false ? null : $texto;
    }

    private static function chave()
    {
        $base = (string) ($_ENV['APP_ENCRYPTION_KEY'] ?? '');
        if ($base === '') {
            throw new RuntimeException('APP_ENCRYPTION_KEY nao configurada no .env.');
        }

        return hash('sha256', 'mapos-2fa|' . $base, true);
    }

    /* ===================== Usuario no banco ===================== */

    /** Grava o segredo confirmado e devolve os codigos de recuperacao para mostrar uma unica vez. */
    public function ativar($idUsuario, $segredo, $passo)
    {
        [$codigos, $json] = self::gerarCodigosRecuperacao();
        $this->ci->db->where('idUsuarios', (int) $idUsuario)->update('usuarios', [
            'totp_segredo' => self::cifrar($segredo),
            'totp_ativo' => 1,
            'totp_recuperacao' => $json,
            'totp_ultimo_passo' => (int) $passo,
        ]);

        return $codigos;
    }

    public function desativar($idUsuario)
    {
        $this->ci->db->where('idUsuarios', (int) $idUsuario)->update('usuarios', [
            'totp_segredo' => null,
            'totp_ativo' => 0,
            'totp_recuperacao' => null,
            'totp_ultimo_passo' => null,
        ]);
    }

    public function novosCodigosRecuperacao($idUsuario)
    {
        [$codigos, $json] = self::gerarCodigosRecuperacao();
        $this->ci->db->where('idUsuarios', (int) $idUsuario)->update('usuarios', ['totp_recuperacao' => $json]);

        return $codigos;
    }

    /**
     * Confere o codigo do aplicativo ou um codigo de recuperacao do usuario e registra o uso.
     *
     * @param object $usuario linha de usuarios (com as colunas totp_*)
     * @return string|null 'totp', 'recuperacao' ou null se nao conferir
     */
    public function verificar($usuario, $codigo)
    {
        if (empty($usuario->totp_ativo)) {
            return null;
        }
        $segredo = self::decifrar($usuario->totp_segredo);
        if ($segredo !== null) {
            $passo = self::passoDoCodigo($segredo, $codigo, $usuario->totp_ultimo_passo);
            if ($passo !== null) {
                $this->ci->db->where('idUsuarios', (int) $usuario->idUsuarios)->update('usuarios', ['totp_ultimo_passo' => $passo]);

                return 'totp';
            }
        }

        $restantes = self::consumirCodigoRecuperacao($usuario->totp_recuperacao, $codigo);
        if ($restantes !== null) {
            $this->ci->db->where('idUsuarios', (int) $usuario->idUsuarios)->update('usuarios', ['totp_recuperacao' => $restantes]);
            log_info('Usou um código de recuperação da verificação em duas etapas');

            return 'recuperacao';
        }

        return null;
    }

    /* ======================== Base32 ======================== */

    public static function base32Encode($dados)
    {
        $bits = '';
        foreach (str_split($dados) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $saida = '';
        foreach (str_split($bits, 5) as $grupo) {
            $saida .= self::BASE32[bindec(str_pad($grupo, 5, '0'))];
        }

        return $saida;
    }

    public static function base32Decode($texto)
    {
        $texto = strtoupper(preg_replace('/[\s=-]/', '', (string) $texto));
        $bits = '';
        foreach (str_split($texto) as $c) {
            $valor = strpos(self::BASE32, $c);
            if ($valor === false) {
                throw new InvalidArgumentException('Segredo base32 invalido.');
            }
            $bits .= str_pad(decbin($valor), 5, '0', STR_PAD_LEFT);
        }
        $saida = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $saida .= chr(bindec($byte));
            }
        }

        return $saida;
    }
}
