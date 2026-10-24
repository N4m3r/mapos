<?php

namespace Libraries\Fiscal;

use DOMDocument;
use NFePHP\NFe\Tools;

/**
 * Tools da NFePHP com o acesso ao QR Code da NFC-e (ADR-005, F5).
 *
 * O Map-OS assina com o Signer proprio (em vez de Tools::signNFe), entao o
 * grupo infNFeSupl (QR Code + URL de consulta) precisa ser inserido depois,
 * com a mesma rotina que a biblioteca usa internamente.
 */
class NfceTools extends Tools
{
    public function inserirQRCode(string $xmlAssinado): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($xmlAssinado);

        return $this->addQRCode($dom);
    }
}
