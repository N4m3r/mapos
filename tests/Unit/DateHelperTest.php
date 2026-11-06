<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/date_helper.php';

/**
 * dateInterval() do application/helpers/date_helper.php.
 */
class DateHelperTest extends TestCase
{
    public static function vencimentos(): array
    {
        return [
            'data e dias' => ['2024-05-10', '90', '08/08/2024'],
            'dias inteiros' => ['2024-05-10', 90, '08/08/2024'],
            'zero dias' => ['2024-05-10', 0, '10/05/2024'],
            'data com hora' => ['2024-05-10 13:45:00', '30', '09/06/2024'],
            'virada de ano' => ['2024-12-20', '20', '09/01/2025'],
            'ano bissexto' => ['2024-02-28', '1', '29/02/2024'],
            'data com barra' => ['2024/05/10', '90', '08/08/2024'],
        ];
    }

    #[DataProvider('vencimentos')]
    public function testSomaDiasNaDataInicial($data, $dias, string $esperado): void
    {
        $this->assertSame($esperado, dateInterval($data, $dias));
    }

    public static function semDataValida(): array
    {
        return [
            'data zerada' => ['0000-00-00'],
            'data zerada com hora' => ['0000-00-00 00:00:00'],
            'data nula' => [null],
            'data vazia' => [''],
            'texto qualquer' => ['sem data'],
            'data impossivel' => ['2024-02-30'],
        ];
    }

    #[DataProvider('semDataValida')]
    public function testDataInvalidaDevolveVazio($data): void
    {
        $this->assertSame('', dateInterval($data, '90'));
    }

    public static function garantiasInvalidas(): array
    {
        return [
            'dias negativos' => ['-5'],
            'texto' => ['abc'],
            'vazia' => [''],
            'nula' => [null],
            'texto de garantia' => ['30 dias'],
        ];
    }

    #[DataProvider('garantiasInvalidas')]
    public function testGarantiaInvalidaDevolveVazio($dias): void
    {
        $this->assertSame('', dateInterval('2024-05-10', $dias));
    }
}
