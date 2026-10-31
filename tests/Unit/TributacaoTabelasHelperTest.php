<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/tributacao_tabelas_helper.php';

class TributacaoTabelasHelperTest extends TestCase
{
    public function testCclasstribPontoEVirgulaComCabecalhoFlexivel(): void
    {
        $csv = "Código cClassTrib;CST;Descrição;pRedIBS;NFe;NFCe;NFSe\n000001;000;Integral;;S;S;N\n200003;200;Redução;60,00;1;0;0\n";
        $r = trib_parse_csv($csv, 'cclasstrib');
        $this->assertSame([], $r['erros']);
        $this->assertCount(2, $r['linhas']);
        $this->assertSame(60.0, $r['linhas'][1]['p_red_ibs']);
        $this->assertSame(1, $r['linhas'][0]['ind_nfe']);
        $this->assertSame(0, $r['linhas'][0]['ind_nfse']);
    }

    public function testCstDeveTerTresDigitos(): void
    {
        $r = trib_parse_csv("cst,descricao\n0000,x\n000,ok\n", 'cst');
        $this->assertCount(1, $r['linhas']);
        $this->assertCount(1, $r['erros']);
    }

    public function testCclasstribPrefixoDiferenteDoCst(): void
    {
        $r = trib_parse_csv("codigo,cst\n200003,000\n", 'cclasstrib');
        $this->assertCount(1, $r['erros']);
        $this->assertSame([], $r['linhas']);
    }

    public function testColunaObrigatoriaAusenteEData(): void
    {
        $r = trib_parse_csv("foo;bar\n1;2\n", 'cst');
        $this->assertStringContainsString('cst', $r['erros'][0]);
        $this->assertSame('2026-01-05', trib_data('05/01/2026'));
        $this->assertNull(trib_data('x'));
    }

    public function testNfseCorrelacao(): void
    {
        $r = trib_parse_csv("Item LC 116;NBS;cIndOp;cClassTrib\n14.01;1.2345.67.00;100301;000001\n", 'nfse_correlacao');
        $this->assertSame([], $r['erros']);
        $this->assertSame('14.01', $r['linhas'][0]['item_lc116']);
    }
}
