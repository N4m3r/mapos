<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Nivel de seguranca da senha (general_helper: senha_forca e senha_forca_rotulo).
 * Os mesmos casos rodam contra assets/js/forca-senha.js em tests/js.
 */
class SenhaForcaTest extends TestCase
{
    public static function casos(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/../js/forca-senha-casos.json'), true);
    }

    #[DataProvider('casos')]
    public function testNivel(string $senha, int $esperado): void
    {
        $this->assertSame($esperado, senha_forca($senha));
    }

    public function testRotulos(): void
    {
        $this->assertSame(['não avaliada', 'neutro'], senha_forca_rotulo(null));
        $this->assertSame(['Fraca', 'perigo'], senha_forca_rotulo('1'));
        $this->assertSame(['Forte', 'sucesso'], senha_forca_rotulo(4));
        $this->assertSame(['não avaliada', 'neutro'], senha_forca_rotulo(9));
    }
}
