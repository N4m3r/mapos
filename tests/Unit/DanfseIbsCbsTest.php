<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/danfe_helper.php';

class DanfseIbsCbsTest extends TestCase
{
    private const XML_COM_IBSCBS = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<NFSe xmlns="http://www.sped.fazenda.gov.br/nfse">
  <infNFSe Id="NFS35123456789012345678901234567890123456789012">
    <nNFSe>12</nNFSe>
    <DPS>
      <infDPS Id="DPS1">
        <IBSCBS>
          <cIndOp>100301</cIndOp>
          <valores>
            <trib>
              <gIBSCBS>
                <CST>000</CST>
                <cClassTrib>000001</cClassTrib>
                <gIBSUF><pIBSUF>0.1000</pIBSUF><vIBSUF>0.10</vIBSUF></gIBSUF>
                <gIBSMun><pIBSMun>0.0000</pIBSMun><vIBSMun>0.00</vIBSMun></gIBSMun>
                <vIBS>0.10</vIBS>
                <gCBS><pCBS>0.9000</pCBS><vCBS>0.90</vCBS></gCBS>
              </gIBSCBS>
            </trib>
          </valores>
          <cLocIncid>3550308</cLocIncid>
        </IBSCBS>
      </infDPS>
    </DPS>
  </infNFSe>
</NFSe>
XML;

    private const XML_SEM_IBSCBS = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<NFSe xmlns="http://www.sped.fazenda.gov.br/nfse">
  <infNFSe Id="NFS1"><nNFSe>1</nNFSe></infNFSe>
</NFSe>
XML;

    public function testExtraiCamposDoGrupoIbsCbs(): void
    {
        $d = danfse_dados_ibscbs(self::XML_COM_IBSCBS);

        $this->assertSame('000', $d['cst']);
        $this->assertSame('000001', $d['cClassTrib']);
        $this->assertSame('100301', $d['cIndOp']);
        $this->assertSame('3550308', $d['cLocIncid']);
        $this->assertSame('0.1000', $d['pIBSUF']);
        $this->assertSame('0.10', $d['vIBSUF']);
        $this->assertSame('0.0000', $d['pIBSMun']);
        $this->assertSame('0.00', $d['vIBSMun']);
        $this->assertSame('0.10', $d['vIBS']);
        $this->assertSame('0.9000', $d['pCBS']);
        $this->assertSame('0.90', $d['vCBS']);
    }

    public function testSemGrupoRetornaVazio(): void
    {
        $this->assertSame([], danfse_dados_ibscbs(self::XML_SEM_IBSCBS));
    }

    public function testXmlInvalidoOuVazioRetornaVazio(): void
    {
        $this->assertSame([], danfse_dados_ibscbs(''));
        $this->assertSame([], danfse_dados_ibscbs('<nao-fechado'));
    }

    public function testGrupoSemCamposPreenchidosRetornaVazio(): void
    {
        $xml = '<NFSe><IBSCBS></IBSCBS></NFSe>';
        $this->assertSame([], danfse_dados_ibscbs($xml));
    }
}
