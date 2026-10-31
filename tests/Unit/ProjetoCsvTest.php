<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Projeto_csv.php';

class ProjetoCsvTest extends TestCase
{
    public function testNeutralizaFormula(): void
    {
        foreach (['=1+1', '+1', '-1', '@x'] as $v) {
            $this->assertSame("'" . $v, Projeto_csv::celula($v));
        }
        $this->assertSame('abc', Projeto_csv::celula('abc'));
    }

    public function testEscapaSeparadorAspasEQuebra(): void
    {
        $this->assertSame('"a;b"', Projeto_csv::celula('a;b'));
        $this->assertSame('"di ""x"""', Projeto_csv::celula('di "x"'));
        $this->assertSame('a b', Projeto_csv::celula("a\nb"));
    }

    public function testNumeroEMLinha(): void
    {
        $this->assertSame('1234,50', Projeto_csv::numero(1234.5));
        $this->assertSame("a;b;\"c;d\"\r\n", Projeto_csv::linha(['a', 'b', 'c;d']));
    }

    public function testOrdemListaBranca(): void
    {
        $this->assertSame(['obras.nome', 'desc'], Projeto_csv::colunaOrdem('nome', 'DESC'));
        $this->assertSame(['obras.idObra', 'desc'], Projeto_csv::colunaOrdem('x; drop', 'asc'));
        $this->assertSame(['obras.data_fim_prevista', 'asc'], Projeto_csv::colunaOrdem('fim', 'zz'));
    }
}
