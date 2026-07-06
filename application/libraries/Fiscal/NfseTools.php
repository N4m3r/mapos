<?php

namespace Libraries\Fiscal;

use Hadder\NfseNacional\Tools as HadderTools;

/**
 * Tools do hadder/nfse-nacional com a assinatura sobrescrita.
 *
 * O enviaDps()/cancelaNfse() da biblioteca chamam $this->sign(), que assina a
 * DPS em RSA-SHA1 via openssl_sign — bloqueado em servidores com política de
 * criptografia endurecida (mesmo problema da NF-e). Aqui o sign() é substituído
 * pelo assinador próprio (Signer, RSA cru via openssl_private_encrypt), que gera
 * a mesma assinatura RSA-SHA1 sem passar pela função bloqueada. Todo o resto
 * (endpoint do Sefin Nacional, gzip, base64, POST mTLS) continua da biblioteca.
 */
class NfseTools extends HadderTools
{
    private string $pkeyPem = '';
    private string $certPem = '';

    /**
     * Define o material do certificado (extraído do .pfx) usado pelo assinador próprio.
     */
    public function definirMaterial(string $pkeyPem, string $certPem): void
    {
        $this->pkeyPem = $pkeyPem;
        $this->certPem = $certPem;
    }

    /**
     * Sobrescreve a assinatura da biblioteca. Assinatura compatível com o pai
     * (RestCurl::sign). O elemento assinado é referenciado pelo atributo Id.
     */
    public function sign(string $content, string $tagname, ?string $mark, $rootname): string
    {
        if ($this->pkeyPem === '' || $this->certPem === '') {
            throw new \Exception('Material do certificado não definido para a assinatura da NFS-e.');
        }

        return Signer::sign($content, $tagname, $this->pkeyPem, $this->certPem, 'Id');
    }
}
