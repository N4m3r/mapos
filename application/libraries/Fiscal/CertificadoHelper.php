<?php

namespace Libraries\Fiscal;

use Exception;
use NFePHP\Common\Certificate;

/**
 * Guarda e recupera o certificado digital A1 e sua senha.
 * A senha é criptografada com AES-256-CBC usando a APP_ENCRYPTION_KEY do .env.
 */
class CertificadoHelper
{
    public const DIR_CERTIFICADO = APPPATH . 'arquivos_fiscais' . DIRECTORY_SEPARATOR . 'certificado' . DIRECTORY_SEPARATOR;
    public const DIR_XML = APPPATH . 'arquivos_fiscais' . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR;

    private static function chave(): string
    {
        $chave = $_ENV['APP_ENCRYPTION_KEY'] ?? '';
        if ($chave === '') {
            throw new Exception('APP_ENCRYPTION_KEY não configurada no arquivo .env');
        }

        return hash('sha256', $chave, true);
    }

    public static function criptografar(string $senha): string
    {
        $iv = random_bytes(16);
        $cifrado = openssl_encrypt($senha, 'aes-256-cbc', self::chave(), OPENSSL_RAW_DATA, $iv);
        if ($cifrado === false) {
            throw new Exception('Falha ao criptografar a senha do certificado');
        }

        return base64_encode($iv . $cifrado);
    }

    public static function descriptografar(string $blob): string
    {
        $bruto = base64_decode($blob, true);
        if ($bruto === false || strlen($bruto) <= 16) {
            throw new Exception('Senha do certificado armazenada em formato inválido');
        }
        $iv = substr($bruto, 0, 16);
        $senha = openssl_decrypt(substr($bruto, 16), 'aes-256-cbc', self::chave(), OPENSSL_RAW_DATA, $iv);
        if ($senha === false) {
            throw new Exception('Falha ao descriptografar a senha do certificado. A APP_ENCRYPTION_KEY mudou?');
        }

        return $senha;
    }

    /**
     * Carrega o objeto Certificate da NFePHP a partir da configuração salva.
     */
    public static function carregar(?string $path, ?string $senhaCriptografada): Certificate
    {
        if (empty($path) || empty($senhaCriptografada)) {
            throw new Exception('Certificado digital não configurado. Acesse Notas Fiscais > Configurações.');
        }
        if (!file_exists($path)) {
            throw new Exception('Arquivo do certificado não encontrado em disco: ' . basename($path));
        }
        $conteudo = file_get_contents($path);
        $senha = self::descriptografar($senhaCriptografada);

        $certificado = Certificate::readPfx($conteudo, $senha);

        if ($certificado->isExpired()) {
            throw new Exception('O certificado digital está VENCIDO (validade: ' . $certificado->getValidTo()->format('d/m/Y') . ')');
        }

        return $certificado;
    }

    /**
     * Garante que os diretórios protegidos existem e devolve o caminho do certificado salvo.
     */
    public static function salvarPfx(string $tmpFile, string $nomeOriginal): string
    {
        foreach ([self::DIR_CERTIFICADO, self::DIR_XML] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0750, true);
            }
        }
        $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
        if (!in_array($ext, ['pfx', 'p12'])) {
            throw new Exception('O certificado deve ser um arquivo .pfx ou .p12 (modelo A1)');
        }
        $destino = self::DIR_CERTIFICADO . 'certificado.' . $ext;
        if (!move_uploaded_file($tmpFile, $destino)) {
            throw new Exception('Falha ao salvar o arquivo do certificado');
        }

        return $destino;
    }

    public static function salvarXml(string $nomeArquivo, string $conteudo): string
    {
        if (!is_dir(self::DIR_XML)) {
            mkdir(self::DIR_XML, 0750, true);
        }
        $path = self::DIR_XML . $nomeArquivo;
        file_put_contents($path, $conteudo);

        return $path;
    }
}
