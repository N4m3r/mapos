<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/cliente_fiscal_helper.php';

class ClienteFiscalHelperTest extends TestCase
{
    public function testIndIeDestDeduzDaIeQuandoNaoPreenchido(): void
    {
        $this->assertSame(1, cliente_ind_ie_dest(['ind_ie_dest' => null, 'ie' => '123456789']));
        $this->assertSame(2, cliente_ind_ie_dest(['ind_ie_dest' => null, 'ie' => 'ISENTO']));
        $this->assertSame(2, cliente_ind_ie_dest(['ie' => ' isento ']));
        $this->assertSame(9, cliente_ind_ie_dest(['ie' => '']));
        $this->assertSame(9, cliente_ind_ie_dest((object) ['ie' => null]));
    }

    public function testIndIeDestExplicitoTemPrioridade(): void
    {
        $this->assertSame(9, cliente_ind_ie_dest(['ind_ie_dest' => '9', 'ie' => '123']));
        $this->assertSame(2, cliente_ind_ie_dest(['ind_ie_dest' => 2, 'ie' => '']));
        $this->assertSame(1, cliente_ind_ie_dest((object) ['ind_ie_dest' => '1', 'ie' => 'ISENTO']));
    }

    public function testIndIeDestValorInvalidoCaiNaRegraDaIe(): void
    {
        $this->assertSame(9, cliente_ind_ie_dest(['ind_ie_dest' => 5, 'ie' => '']));
        $this->assertSame(1, cliente_ind_ie_dest(['ind_ie_dest' => '', 'ie' => '999']));
    }

    public function testSuframa(): void
    {
        $this->assertTrue(cliente_suframa_valida('123456789'));
        $this->assertFalse(cliente_suframa_valida('12345678'));
        $this->assertFalse(cliente_suframa_valida('1234567890'));
        $this->assertFalse(cliente_suframa_valida('12345678A'));
        $this->assertFalse(cliente_suframa_valida(''));
        $this->assertFalse(cliente_suframa_valida(null));
    }

    public function testRotuloEmpresaDoSimples(): void
    {
        $this->assertSame(
            'Empresa do Simples · contribuinte de ICMS',
            cliente_perfil_rotulo(['regime_tributario' => 'simples', 'ind_ie_dest' => 1, 'ie' => '1'])
        );
    }

    public function testRotuloOrgaoPublicoENaoContribuinte(): void
    {
        $this->assertSame(
            'MEI · órgão público · não contribuinte de ICMS',
            cliente_perfil_rotulo(['regime_tributario' => 'mei', 'orgao_publico' => 1, 'ie' => ''])
        );
    }

    public function testRotuloSemDadosFiscais(): void
    {
        $this->assertSame('não contribuinte de ICMS', cliente_perfil_rotulo(['ie' => '']));
    }
}
