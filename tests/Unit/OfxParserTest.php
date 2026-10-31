<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Ofx_parser.php';

class OfxParserTest extends TestCase
{
    public function testSgml1xLatin1(): void
    {
        $ofx = "OFXHEADER:100\r\nCHARSET:1252\r\n\r\n<OFX><BANKMSGSRSV1><STMTTRNRS><STMTRS><CURDEF>BRL\r\n<BANKACCTFROM><BANKID>0341\r\n<BRANCHID>1234\r\n<ACCTID>56789-0\r\n</BANKACCTFROM>\r\n<BANKTRANLIST>\r\n"
            . "<STMTTRN><TRNTYPE>CREDIT\r\n<DTPOSTED>20261025120000[-3:BRT]\r\n<TRNAMT>150.50\r\n<FITID>A1\r\n<MEMO>Pagamento " . mb_convert_encoding('ação', 'Windows-1252', 'UTF-8') . "\r\n"
            . "<STMTTRN><TRNTYPE>DEBIT\r\n<DTPOSTED>20261026\r\n<TRNAMT>-80,00\r\n<FITID>A2\r\n<NAME>Tarifa\r\n"
            . '</BANKTRANLIST></STMTRS></STMTTRNRS></BANKMSGSRSV1></OFX>';
        $r = (new Ofx_parser())->parse($ofx);
        $this->assertSame('56789-0', $r['conta']['conta']);
        $this->assertSame('0341', $r['conta']['banco']);
        $this->assertCount(2, $r['itens']);
        $this->assertSame(['fitid' => 'A1', 'data' => '2026-10-25', 'valor' => 150.5, 'descricao' => 'Pagamento ação', 'tipo' => 'credito'], $r['itens'][0]);
        $this->assertSame(-80.0, $r['itens'][1]['valor']);
        $this->assertSame('debito', $r['itens'][1]['tipo']);
        $this->assertSame('Tarifa', $r['itens'][1]['descricao']);
    }

    public function testXml2x(): void
    {
        $ofx = '<?xml version="1.0" encoding="UTF-8"?><OFX><BANKACCTFROM><BANKID>001</BANKID><ACCTID>99</ACCTID></BANKACCTFROM><BANKTRANLIST>'
            . '<STMTTRN><DTPOSTED>20261001000000</DTPOSTED><TRNAMT>-10.00</TRNAMT><FITID>X1</FITID><MEMO>Caf&amp;é</MEMO></STMTTRN></BANKTRANLIST></OFX>';
        $r = (new Ofx_parser())->parse($ofx);
        $this->assertCount(1, $r['itens']);
        $this->assertSame('Caf&é', $r['itens'][0]['descricao']);
        $this->assertSame('99', $r['conta']['conta']);
    }

    public function testVazio(): void
    {
        $this->assertSame([], (new Ofx_parser())->parse('lixo')['itens']);
    }
}
