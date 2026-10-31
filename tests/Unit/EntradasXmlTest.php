<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/entradas_xml_helper.php';

class EntradasXmlTest extends TestCase
{
    private function xml(string $crt = '3'): string
    {
        $chave = str_repeat('1', 44);

        return '<?xml version="1.0"?><nfeProc xmlns="http://www.portalfiscal.inf.br/nfe"><NFe><infNFe Id="NFe' . $chave . '" versao="4.00">'
            . '<ide><mod>55</mod><serie>1</serie><nNF>77</nNF><dhEmi>2026-10-15T10:00:00-03:00</dhEmi></ide>'
            . '<emit><CNPJ>12345678000199</CNPJ><xNome>Fornecedor</xNome><CRT>' . $crt . '</CRT></emit>'
            . '<det nItem="1"><prod><cProd>A1</cProd><xProd>Cabo</xProd><NCM>85444900</NCM><CFOP>5102</CFOP><qCom>2.0000</qCom><vProd>100.00</vProd></prod>'
            . '<imposto><IBSCBS><CST>000</CST><cClassTrib>000001</cClassTrib><gIBSCBS><vBC>100.00</vBC>'
            . '<gIBSUF><pIBSUF>0.10</pIBSUF></gIBSUF><gIBSMun><pIBSMun>0.00</pIBSMun></gIBSMun><vIBS>0.10</vIBS>'
            . '<gCBS><pCBS>0.90</pCBS><vCBS>0.90</vCBS></gCBS></gIBSCBS></IBSCBS></imposto></det>'
            . '<det nItem="2"><prod><cProd>B2</cProd><xProd>Conector</xProd><NCM>85366990</NCM><CFOP>5102</CFOP><qCom>1.0000</qCom><vProd>50.00</vProd></prod>'
            . '<imposto><IBSCBS><CST>200</CST><cClassTrib>200003</cClassTrib></IBSCBS></imposto></det>'
            . '<total><ICMSTot><vICMS>18.00</vICMS><vPIS>1.65</vPIS><vCOFINS>7.60</vCOFINS><vNF>150.00</vNF></ICMSTot>'
            . '<IBSCBSTot><vBCIBSCBS>100.00</vBCIBSCBS><gIBS><vIBS>0.10</vIBS></gIBS><gCBS><vCBS>0.90</vCBS></gCBS></IBSCBSTot></total>'
            . '</infNFe></NFe></nfeProc>';
    }

    public function testCabecalhoTotaisEItens(): void
    {
        $r = entradas_parse_nfe($this->xml());
        $this->assertSame(str_repeat('1', 44), $r['chave']);
        $this->assertSame('77', $r['numero']);
        $this->assertSame('2026-10', $r['competencia']);
        $this->assertSame('12345678000199', $r['emitente_cnpj']);
        $this->assertSame(3, $r['emitente_crt']);
        $this->assertSame(150.0, $r['valor_total']);
        $this->assertSame(18.0, $r['v_icms']);
        $this->assertSame(7.6, $r['v_cofins']);
        $this->assertSame(0.10, $r['v_ibs']);
        $this->assertSame(0.90, $r['v_cbs']);
        $this->assertCount(2, $r['itens']);
        $this->assertSame('Cabo', $r['itens'][0]['descricao']);
        $this->assertSame('000001', $r['itens'][0]['cclasstrib']);
        $this->assertSame(0.9, $r['itens'][0]['p_cbs']);
        $this->assertSame(0.90, $r['itens'][0]['v_cbs']);
        $this->assertSame('200', $r['itens'][1]['cst_ibscbs']);
        $this->assertSame(0.0, $r['itens'][1]['v_ibs']);
    }

    public function testCrtSimplesEXmlInvalido(): void
    {
        $this->assertSame(1, entradas_parse_nfe($this->xml('1'))['emitente_crt']);
        $this->assertSame([], entradas_parse_nfe('nao e xml'));
        $this->assertSame([], entradas_parse_nfe('<a><b/></a>'));
    }
}
