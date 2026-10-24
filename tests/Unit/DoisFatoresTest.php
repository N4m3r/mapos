<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Dois_fatores.php';

/**
 * Verificacao em duas etapas: TOTP (RFC 6238), cifragem do segredo e codigos de recuperacao.
 */
class DoisFatoresTest extends TestCase
{
    /** Segredo dos vetores da RFC 6238 (ASCII "12345678901234567890") em base32. */
    private const SEGREDO_RFC = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    protected function setUp(): void
    {
        $_ENV['APP_ENCRYPTION_KEY'] = 'chave-de-teste';
    }

    public static function vetoresRfc(): array
    {
        // RFC 6238, apendice B (SHA-1); os 6 ultimos digitos dos codigos de 8
        return [
            [59, '287082'],
            [1111111109, '081804'],
            [1111111111, '050471'],
            [1234567890, '005924'],
            [2000000000, '279037'],
            [20000000000, '353130'],
        ];
    }

    #[DataProvider('vetoresRfc')]
    public function testCodigoConfereComARfc(int $tempo, string $esperado): void
    {
        $this->assertSame($esperado, Dois_fatores::codigoNoPasso(self::SEGREDO_RFC, Dois_fatores::passoAtual($tempo)));
    }

    public function testBase32IdaEVolta(): void
    {
        $this->assertSame(self::SEGREDO_RFC, Dois_fatores::base32Encode('12345678901234567890'));
        $bytes = random_bytes(20);
        $this->assertSame($bytes, Dois_fatores::base32Decode(Dois_fatores::base32Encode($bytes)));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', Dois_fatores::gerarSegredo());
    }

    public function testAceitaUmPassoDeDiferencaENaoAceitaReuso(): void
    {
        $agora = 1_800_000_000;
        $passo = Dois_fatores::passoAtual($agora);
        $atual = Dois_fatores::codigoNoPasso(self::SEGREDO_RFC, $passo);
        $anterior = Dois_fatores::codigoNoPasso(self::SEGREDO_RFC, $passo - 1);
        $velho = Dois_fatores::codigoNoPasso(self::SEGREDO_RFC, $passo - 3);

        $this->assertSame($passo, Dois_fatores::passoDoCodigo(self::SEGREDO_RFC, $atual, null, $agora));
        $this->assertSame($passo - 1, Dois_fatores::passoDoCodigo(self::SEGREDO_RFC, $anterior, null, $agora));
        $this->assertSame($passo, Dois_fatores::passoDoCodigo(self::SEGREDO_RFC, substr($atual, 0, 3) . ' ' . substr($atual, 3), null, $agora), 'aceita com espaco');
        $this->assertNull(Dois_fatores::passoDoCodigo(self::SEGREDO_RFC, $velho, null, $agora), 'codigo de 90s atras');
        $this->assertNull(Dois_fatores::passoDoCodigo(self::SEGREDO_RFC, $atual, $passo, $agora), 'mesmo codigo duas vezes');
        $this->assertNull(Dois_fatores::passoDoCodigo(self::SEGREDO_RFC, '12345', null, $agora));
    }

    public function testSegredoCifradoSoAbreComAMesmaChave(): void
    {
        $segredo = Dois_fatores::gerarSegredo();
        $cifrado = Dois_fatores::cifrar($segredo);

        $this->assertStringNotContainsString($segredo, $cifrado);
        $this->assertSame($segredo, Dois_fatores::decifrar($cifrado));

        $_ENV['APP_ENCRYPTION_KEY'] = 'outra-chave';
        $this->assertNull(Dois_fatores::decifrar($cifrado));
        $this->assertNull(Dois_fatores::decifrar('lixo'));
    }

    public function testCodigoDeRecuperacaoValeUmaVez(): void
    {
        [$codigos, $json] = Dois_fatores::gerarCodigosRecuperacao();

        $this->assertCount(10, $codigos);
        $this->assertSame(10, Dois_fatores::codigosRestantes($json));
        $this->assertStringNotContainsString($codigos[0], $json, 'banco guarda so o hash');

        $depois = Dois_fatores::consumirCodigoRecuperacao($json, strtoupper(str_replace('-', '', $codigos[3])));
        $this->assertNotNull($depois, 'aceita sem hifen e em maiusculas');
        $this->assertSame(9, Dois_fatores::codigosRestantes($depois));
        $this->assertNull(Dois_fatores::consumirCodigoRecuperacao($depois, $codigos[3]), 'nao vale de novo');
        $this->assertNull(Dois_fatores::consumirCodigoRecuperacao($depois, 'aaaa-aaaa'));
    }

    public function testUriEQrCode(): void
    {
        $uri = Dois_fatores::uri('ABC234', 'maria@exemplo.com', 'Map-OS');

        $this->assertSame('otpauth://totp/Map-OS:maria%40exemplo.com?secret=ABC234&issuer=Map-OS&algorithm=SHA1&digits=6&period=30', $uri);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', Dois_fatores::qrCode($uri));
    }
}
