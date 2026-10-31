<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/apuracao_helper.php';

class ApuracaoTest extends TestCase
{
    public function testMontaTotaisESaldoPorTributo(): void
    {
        $deb = [['v_ibs_uf' => 10, 'v_ibs_mun' => 5, 'v_cbs' => 20], ['v_ibs_uf' => 2.5, 'v_cbs' => 1]];
        $cred = [['v_ibs_uf' => 4, 'v_ibs_mun' => 9, 'v_cbs' => 6]];
        $a = apuracao_montar($deb, $cred, [['v_cbs' => 3]]);
        $this->assertSame(12.5, $a['debitos']['ibs_uf']);
        $this->assertSame(2, $a['debitos']['qtd']);
        $this->assertSame(8.5, $a['saldo']['ibs_uf']['a_recolher']);
        $this->assertSame(4.0, $a['saldo']['ibs_mun']['credor']);
        $this->assertSame(15.0, $a['saldo']['cbs']['a_recolher']);
        $this->assertSame(3.0, $a['pendentes']['total']);
        $this->assertSame(1, $a['pendentes']['qtd']);
    }

    public function testCsvUsaPontoEVirgulaEDecimalVirgula(): void
    {
        $a = apuracao_montar([['v_ibs_uf' => 1.5]], [], []);
        $csv = apuracao_csv($a, [['tipo' => 'Débito', 'documento' => '=NF;e', 'numero' => '1', 'data' => '01/01/2026', 'v_ibs_uf' => 1.5]]);
        $this->assertStringStartsWith("Tipo;Documento;Número;Data;IBS estadual;IBS municipal;CBS;Crédito presumido ZFM\r\n", $csv);
        $this->assertStringContainsString("Débito;\"'=NF;e\";1;01/01/2026;1,50;0,00;0,00;0,00", $csv);
        $this->assertStringContainsString('Saldo a recolher;;;;1,50;0,00;0,00;0,00', $csv);
    }

    public function testTotaisXmlNfe(): void
    {
        $xml = '<NFe><total><IBSCBSTot><gIBS><gIBSUF><vIBSUF>1.10</vIBSUF></gIBSUF><gIBSMun><vIBSMun>0.40</vIBSMun></gIBSMun><vIBS>1.50</vIBS></gIBS><gCBS><vCBS>2.00</vCBS></gCBS></IBSCBSTot></total></NFe>';
        $this->assertSame(['v_ibs_uf' => 1.1, 'v_ibs_mun' => 0.4, 'v_cbs' => 2.0], apuracao_totais_xml_nfe($xml));
        $this->assertSame(0.0, apuracao_totais_xml_nfe('lixo')['v_cbs']);
    }

    public function testCreditoZfmEntraNoTotalSemMexerNoSaldoPorTributo(): void
    {
        $a = apuracao_montar([['v_cbs' => 10]], [['v_cbs' => 4], ['v_zfm' => 3]], []);
        $this->assertSame(3.0, $a['creditos']['zfm']);
        $this->assertSame(7.0, $a['creditos']['total']);
        $this->assertSame(1, $a['creditos']['qtd']);
        $this->assertSame(6.0, $a['saldo']['cbs']['a_recolher']);
        $this->assertSame(3.0, $a['saldo']['total']['a_recolher']);
    }

    public function testCreditoNotaSeparaUfMunicipioPeloXml(): void
    {
        $xml = '<NFe><IBSCBSTot><vIBSUF>3.00</vIBSUF><vIBSMun>1.00</vIBSMun><vCBS>2.00</vCBS></IBSCBSTot></NFe>';
        $r = apuracao_credito_nota(['xml' => $xml, 'v_ibs' => 4, 'v_cbs' => 2, 'v_cred_pres_zfm' => 0.5]);
        $this->assertSame(3.0, $r['v_ibs_uf']);
        $this->assertSame(1.0, $r['v_ibs_mun']);
        $this->assertSame(0.5, $r['v_zfm']);
        $this->assertSame('xml', $r['origem']);
    }

    public function testCreditoNotaManualFicaTodoEmUf(): void
    {
        $r = apuracao_credito_nota(['xml' => '', 'v_ibs' => 5, 'v_cbs' => 1]);
        $this->assertSame(5.0, $r['v_ibs_uf']);
        $this->assertSame(0.0, $r['v_ibs_mun']);
        $this->assertSame('manual', $r['origem']);
    }
}
