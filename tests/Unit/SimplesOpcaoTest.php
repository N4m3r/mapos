<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/tributacao_helper.php';

class SimplesOpcaoTest extends TestCase
{
    public function testSemestre(): void
    {
        $this->assertSame([2027, 1], tributacao_semestre('2027-06-30'));
        $this->assertSame([2027, 2], tributacao_semestre('2027-07-01'));
    }

    public function testRegime(): void
    {
        $op = [['ano' => 2027, 'semestre' => 2, 'regime' => 'regular']];
        $this->assertSame('regular', tributacao_regime_ibscbs([], '2027-01-10', 1));
        $this->assertSame('das', tributacao_regime_ibscbs($op, '2027-03-10', 3));
        $this->assertSame('regular', tributacao_regime_ibscbs($op, '2027-08-10', 2));
        $this->assertSame('das', tributacao_regime_ibscbs($op, '2028-08-10', 3));
    }

    public function testTextos(): void
    {
        $this->assertStringContainsString('só aproveita', tributacao_texto_credito_cliente('das'));
        $this->assertStringContainsString('crédito integral', tributacao_texto_credito_cliente('regular'));
    }

    public function testJanela(): void
    {
        $j = tributacao_janela_opcao('2026-10-31');
        $this->assertFalse($j['aberta']);
        $this->assertSame(3, $j['dias_para_abrir']);
        $j = tributacao_janela_opcao('2026-11-10');
        $this->assertTrue($j['aberta']);
        $this->assertSame(40, $j['dias_restantes']);
        $this->assertSame('2027-03-01', tributacao_janela_opcao('2026-12-21')['inicio']);
        $this->assertNull(tributacao_janela_opcao('2027-04-01'));
    }
}
