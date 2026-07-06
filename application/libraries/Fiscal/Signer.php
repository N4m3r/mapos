<?php

namespace Libraries\Fiscal;

use DOMDocument;
use DOMElement;
use Exception;

/**
 * Assinador XMLDSig RSA-SHA1 para NF-e e eventos.
 *
 * Gera a assinatura via openssl_private_encrypt (operação RSA "crua" com padding
 * PKCS#1 v1.5 sobre o DigestInfo SHA1), produzindo EXATAMENTE a mesma assinatura
 * RSA-SHA1 que a SEFAZ exige, porém SEM passar por openssl_sign()/EVP — que alguns
 * servidores (OpenSSL 3 com política endurecida, comum em hospedagem compartilhada)
 * bloqueiam com "invalid digest". O SHA1 é obrigatório no padrão NF-e.
 *
 * Estrutura gerada: Signature (padrão) irmã do elemento assinado, Reference por Id,
 * transforms enveloped-signature + C14N inclusivo, DigestMethod/SignatureMethod SHA1.
 */
class Signer
{
    private const NS = 'http://www.w3.org/2000/09/xmldsig#';
    private const C14N = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private const TRANSF_ENV = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';
    private const SIG_METHOD = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
    private const DIGEST_METHOD = 'http://www.w3.org/2000/09/xmldsig#sha1';

    // Prefixo ASN.1 DigestInfo do SHA1 (RFC 8017 / PKCS#1 v1.5).
    private const SHA1_DIGESTINFO_PREFIX = '3021300906052b0e03021a05000414';

    /**
     * Assina o XML no elemento $tag (ex.: 'infNFe', 'infEvento') identificado por $idAttr ('Id').
     * $privateKeyPem/$certPem: material extraído do .pfx (openssl_pkcs12_read).
     * Retorna o XML assinado (string).
     */
    public static function sign(string $xml, string $tag, string $privateKeyPem, string $certPem, string $idAttr = 'Id'): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (!$dom->loadXML($xml)) {
            throw new Exception('XML inválido para assinatura.');
        }

        $node = $dom->getElementsByTagName($tag)->item(0);
        if (!$node instanceof DOMElement) {
            throw new Exception("Elemento <$tag> não encontrado para assinar.");
        }
        $id = $node->getAttribute($idAttr);
        if ($id === '') {
            throw new Exception("Elemento <$tag> sem atributo $idAttr para referência da assinatura.");
        }

        // 1) DigestValue = base64( SHA1( C14N(node) ) )
        $c14nNode = $node->C14N(false, false); // c14n inclusivo, sem comentários
        $digestValue = base64_encode(sha1($c14nNode, true));

        // 2) SignedInfo com a Reference ao elemento
        $signedInfo = self::montarSignedInfo($dom, $id, $digestValue);

        // 3) Signature (irmã do elemento assinado) para o SignedInfo herdar o namespace
        $signature = $dom->createElementNS(self::NS, 'Signature');
        $signature->appendChild($signedInfo);
        $node->parentNode->appendChild($signature);

        // 4) SignatureValue = base64( RSA-SHA1( C14N(SignedInfo) ) ), via RSA cru
        $c14nSignedInfo = $signedInfo->C14N(false, false);
        $signatureValue = self::assinarRsaSha1($c14nSignedInfo, $privateKeyPem);
        $signature->appendChild($dom->createElementNS(self::NS, 'SignatureValue', $signatureValue));

        // 5) KeyInfo com o certificado X509
        $keyInfo = $dom->createElementNS(self::NS, 'KeyInfo');
        $x509Data = $dom->createElementNS(self::NS, 'X509Data');
        $x509Data->appendChild($dom->createElementNS(self::NS, 'X509Certificate', self::certBase64($certPem)));
        $keyInfo->appendChild($x509Data);
        $signature->appendChild($keyInfo);

        return $dom->saveXML($dom->documentElement);
    }

    private static function montarSignedInfo(DOMDocument $dom, string $id, string $digestValue): DOMElement
    {
        $signedInfo = $dom->createElementNS(self::NS, 'SignedInfo');

        $c14nMethod = $dom->createElementNS(self::NS, 'CanonicalizationMethod');
        $c14nMethod->setAttribute('Algorithm', self::C14N);
        $signedInfo->appendChild($c14nMethod);

        $sigMethod = $dom->createElementNS(self::NS, 'SignatureMethod');
        $sigMethod->setAttribute('Algorithm', self::SIG_METHOD);
        $signedInfo->appendChild($sigMethod);

        $reference = $dom->createElementNS(self::NS, 'Reference');
        $reference->setAttribute('URI', '#' . $id);

        $transforms = $dom->createElementNS(self::NS, 'Transforms');
        $t1 = $dom->createElementNS(self::NS, 'Transform');
        $t1->setAttribute('Algorithm', self::TRANSF_ENV);
        $t2 = $dom->createElementNS(self::NS, 'Transform');
        $t2->setAttribute('Algorithm', self::C14N);
        $transforms->appendChild($t1);
        $transforms->appendChild($t2);
        $reference->appendChild($transforms);

        $digestMethod = $dom->createElementNS(self::NS, 'DigestMethod');
        $digestMethod->setAttribute('Algorithm', self::DIGEST_METHOD);
        $reference->appendChild($digestMethod);

        $reference->appendChild($dom->createElementNS(self::NS, 'DigestValue', $digestValue));
        $signedInfo->appendChild($reference);

        return $signedInfo;
    }

    /**
     * Assina os dados com RSA-SHA1 usando openssl_private_encrypt (contorna o
     * bloqueio de SHA1 em openssl_sign). Devolve a assinatura em base64.
     */
    private static function assinarRsaSha1(string $data, string $privateKeyPem): string
    {
        $pkey = openssl_pkey_get_private($privateKeyPem);
        if ($pkey === false) {
            throw new Exception('Chave privada inválida para assinatura: ' . openssl_error_string());
        }

        $digestInfo = hex2bin(self::SHA1_DIGESTINFO_PREFIX) . sha1($data, true);

        $assinatura = '';
        if (!openssl_private_encrypt($digestInfo, $assinatura, $pkey, OPENSSL_PKCS1_PADDING)) {
            throw new Exception('Falha ao gerar a assinatura RSA-SHA1: ' . openssl_error_string());
        }

        return base64_encode($assinatura);
    }

    private static function certBase64(string $certPem): string
    {
        return preg_replace('/-----(BEGIN|END) CERTIFICATE-----|\s+/', '', $certPem);
    }
}
