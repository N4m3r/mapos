<?php

use Piggly\Pix\Parser;
use Piggly\Pix\StaticPayload;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Chave PIX (getPixKeyType) e codigo "copia e cola" gerado como em Os_model/Vendas_model::getQrCode.
 */
class PixTest extends TestCase
{
    public static function chaves(): array
    {
        return [
            'cpf com mascara' => ['123.456.789-09', Parser::KEY_TYPE_DOCUMENT],
            'cpf sem mascara' => ['12345678909', Parser::KEY_TYPE_DOCUMENT],
            'cnpj' => ['54.518.217/0001-17', Parser::KEY_TYPE_DOCUMENT],
            'email' => ['financeiro@exemplo.com', Parser::KEY_TYPE_EMAIL],
            'telefone com +55' => ['+5511999998888', Parser::KEY_TYPE_PHONE],
            'telefone sem +55' => ['11999998888', Parser::KEY_TYPE_PHONE],
            'aleatoria' => ['e3c1b6f8-1c3a-4c7e-9a0b-2f3d4e5f6a7b', Parser::KEY_TYPE_RANDOM],
            'invalida' => ['xx', null],
        ];
    }

    #[DataProvider('chaves')]
    public function testTipoDaChave(string $chave, ?string $tipoEsperado): void
    {
        $this->assertSame($tipoEsperado, getPixKeyType($chave));
    }

    public function testCodigoCopiaEColaDaOs(): void
    {
        $chave = '54.518.217/0001-17';
        $pix = (new StaticPayload())
            ->setAmount(123.45)
            ->setTid(42)
            ->setDescription(sprintf('%s OS %s', substr('Jj Tecnologias Ltda', 0, 18), 42), true)
            ->setPixKey(getPixKeyType($chave), $chave)
            ->setMerchantName('Jj Tecnologias Ltda')
            ->setMerchantCity('Manaus');

        // Codigo gerado pela php-pix 2.0.2; a 3.0 deve produzir exatamente o mesmo.
        $this->assertSame(
            '00020101021126640014br.gov.bcb.pix0114545182170001170224JJ TECNOLOGIAS LTD OS 425204000053039865406123.455802BR5919JJ TECNOLOGIAS LTDA6006MANAUS62060502426304A55C',
            $pix->getPixCode()
        );
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $pix->getQRCode());
    }
}
