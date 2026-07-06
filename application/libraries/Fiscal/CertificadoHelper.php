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
    public const DIR_CORA = APPPATH . 'arquivos_fiscais' . DIRECTORY_SEPARATOR . 'cora' . DIRECTORY_SEPARATOR;

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

        try {
            $certificado = Certificate::readPfx($conteudo, $senha);
        } catch (\Throwable $e) {
            throw new Exception(self::traduzErroCertificado($e->getMessage()));
        }

        if ($certificado->isExpired()) {
            throw new Exception('O certificado digital está VENCIDO (validade: ' . $certificado->getValidTo()->format('d/m/Y') . ')');
        }

        // Valida que a chave privada realmente assina sob o OpenSSL atual.
        // Certificados A1 ICP-Brasil usam algoritmos legados que o OpenSSL 3
        // (PHP 8.1+) desativa por padrão, causando "invalid digest" só na hora
        // de transmitir. Aqui o problema é detectado cedo, com mensagem clara.
        // A NF-e exige assinatura SHA1; testamos SHA1 e, na falha, SHA256 para
        // distinguir "chave/PFX legado" de "SHA1 bloqueado por política".
        try {
            $certificado->sign('mapos-teste-assinatura', OPENSSL_ALGO_SHA1);
        } catch (\Throwable $e) {
            $sha256ok = false;
            try {
                $certificado->sign('mapos-teste-assinatura', OPENSSL_ALGO_SHA256);
                $sha256ok = true;
            } catch (\Throwable $e2) {
                // nem SHA256 assina → problema é a chave/PFX
            }
            throw new Exception(self::traduzErroCertificado($e->getMessage(), $sha256ok));
        }

        return $certificado;
    }

    /**
     * Traduz erros comuns de OpenSSL/certificado em mensagens acionáveis.
     * $sha256ok = true indica que a chave assina em SHA256 mas falhou em SHA1
     * (aponta para SHA1 bloqueado por política, não para PFX legado).
     */
    public static function traduzErroCertificado(string $erro, bool $sha256ok = false): string
    {
        // Idempotente: se a mensagem já foi traduzida, devolve como está
        // (evita "Detalhe técnico" aninhado quando o erro passa por mais de um catch).
        if (str_contains($erro, 'não pôde ser usado para assinar')
            || str_contains($erro, 'SHA1 está bloqueada')
            || str_contains($erro, 'Senha do certificado incorreta')) {
            return $erro;
        }

        $baixo = strtolower($erro);

        if ($sha256ok && (str_contains($baixo, 'invalid digest') || str_contains($baixo, 'digital envelope') || str_contains($baixo, 'unsupported'))) {
            return 'A chave do certificado funciona (assina em SHA256), mas a assinatura SHA1 está bloqueada '
                . 'pela política de criptografia deste servidor — e a NF-e exige SHA1. '
                . 'Peça ao provedor/administrador para liberar assinaturas SHA1 no OpenSSL '
                . '(ex.: política de criptografia do sistema / openssl.cnf). '
                . 'Reconverter o certificado NÃO resolve este caso. Detalhe técnico: ' . $erro;
        }

        if (str_contains($baixo, 'invalid digest')
            || str_contains($baixo, 'unsupported')
            || str_contains($baixo, 'digital envelope')
            || str_contains($baixo, 'legacy')
            || str_contains($baixo, 'algorithm')) {
            return 'O certificado A1 não pôde ser usado para assinar neste servidor. '
                . 'Provável causa: o OpenSSL 3 (PHP 8.1+) desativa os algoritmos legados '
                . 'usados pelos certificados ICP-Brasil. Solução: reconverter o certificado com '
                . 'algoritmo moderno — openssl pkcs12 -in seu.pfx -nodes -legacy -out tmp.pem && '
                . 'openssl pkcs12 -in tmp.pem -export -out novo.pfx — e reenviar o novo.pfx; '
                . 'ou habilitar o "legacy provider" no openssl.cnf do servidor. '
                . 'Detalhe técnico: ' . $erro;
        }
        if (str_contains($baixo, 'mac verify') || str_contains($baixo, 'password')) {
            return 'Senha do certificado incorreta (falha ao abrir o .pfx). Reenvie o certificado com a senha correta em Configurações Fiscais. Detalhe: ' . $erro;
        }

        return 'Falha ao carregar o certificado digital: ' . $erro;
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

    /**
     * Salva o certificado (PEM) ou a chave privada (KEY) do mTLS da Cora em
     * caminho fixo dentro do diretório fiscal protegido. Devolve o caminho salvo.
     * $tipo: 'certificado' (.pem/.crt/.cer) ou 'chave' (.key/.pem).
     */
    public static function salvarArquivoCora(string $tmpFile, string $nomeOriginal, string $tipo): string
    {
        if (! is_dir(self::DIR_CORA)) {
            mkdir(self::DIR_CORA, 0750, true);
        }
        $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
        if ($tipo === 'certificado') {
            if (! in_array($ext, ['pem', 'crt', 'cer'])) {
                throw new Exception('O certificado da Cora deve ser um arquivo .pem');
            }
            $destino = self::DIR_CORA . 'certificate.pem';
        } else {
            if (! in_array($ext, ['key', 'pem'])) {
                throw new Exception('A chave privada da Cora deve ser um arquivo .key');
            }
            $destino = self::DIR_CORA . 'private-key.key';
        }
        if (! move_uploaded_file($tmpFile, $destino)) {
            throw new Exception('Falha ao salvar o arquivo da Cora (' . $tipo . ')');
        }
        @chmod($destino, 0640);

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
