<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Funcoes do application/helpers/general_helper.php.
 */
class GeneralHelperTest extends TestCase
{
    public static function documentos(): array
    {
        return [
            'cpf valido' => ['12345678909', true, false],
            'cpf com mascara' => ['123.456.789-09', true, false],
            'cpf digito errado' => ['12345678900', false, false],
            'cpf repetido' => ['11111111111', false, false],
            'cnpj valido' => ['54518217000117', false, true],
            'cnpj com mascara' => ['54.518.217/0001-17', false, true],
            'cnpj digito errado' => ['54518217000118', false, false],
            'cnpj zerado' => ['00000000000000', false, false],
            'vazio' => ['', false, false],
        ];
    }

    #[DataProvider('documentos')]
    public function testCpfECnpj(string $documento, bool $cpf, bool $cnpj): void
    {
        $this->assertSame($cpf, cpf_valido($documento));
        $this->assertSame($cnpj, cnpj_valido($documento));
    }

    public static function chavesPix(): array
    {
        return [
            'cpf' => ['12345678909', '123.456.789-09'],
            'cpf ja formatado' => ['123.456.789-09', '123.456.789-09'],
            'cnpj' => ['54518217000117', '54.518.217/0001-17'],
            'celular' => ['11999998888', '(11) 99999-8888'],
            'email' => ['financeiro@exemplo.com', 'financeiro@exemplo.com'],
            'aleatoria' => ['e3c1b6f8-1c3a-4c7e-9a0b-2f3d4e5f6a7b', 'e3c1b6f8-1c3a-4c7e-9a0b-2f3d4e5f6a7b'],
        ];
    }

    #[DataProvider('chavesPix')]
    public function testFormatarChavePix(string $chave, string $esperado): void
    {
        $this->assertSame($esperado, formatar_chave_pix($chave));
    }

    public function testAssetVersaoUsaDataDoArquivoOuPadrao(): void
    {
        $this->assertSame((string) filemtime(FCPATH . 'assets/js/csrf.js'), asset_versao('assets/js/csrf.js'));
        $this->assertSame('7', asset_versao('assets/js/nao-existe.js', '7'));
    }

    public function testArquivoEImagemOlhaOConteudo(): void
    {
        $png = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
        $texto = tempnam(sys_get_temp_dir(), 'txt');
        file_put_contents($texto, '<?php echo 1;');

        $this->assertTrue(arquivo_e_imagem($png));
        $this->assertFalse(arquivo_e_imagem($texto));
        $this->assertFalse(arquivo_e_imagem('/caminho/que/nao/existe.png'));
        $this->assertFalse(arquivo_e_imagem(null));

        unlink($png);
        unlink($texto);
    }
}
