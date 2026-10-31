<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Fiscal/NfseService.php';

use Libraries\Fiscal\NfseService;

/**
 * Grupo IBSCBS na DPS da NFS-e nacional (XSD oficial v1.01, TCRTCInfoIBSCBS).
 */
class NfseIbsCbsTest extends TestCase
{
    private const XSD = APPPATH . 'libraries/Fiscal/xsd/nfse/DPS_v1.01.xsd';

    private function dpsBase(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><DPS xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.01">'
            . '<infDPS Id="DPS130260021234567800019900001000000000000001"><tpAmb>2</tpAmb><dhEmi>2026-10-31T10:00:00-04:00</dhEmi>'
            . '<verAplic>1</verAplic><serie>00001</serie><nDPS>1</nDPS><dCompet>2026-10-31</dCompet><tpEmit>1</tpEmit><cLocEmi>1302603</cLocEmi>'
            . '<prest><CNPJ>12345678000199</CNPJ><regTrib><opSimpNac>1</opSimpNac><regEspTrib>0</regEspTrib></regTrib></prest>'
            . '<toma><CNPJ>11222333000181</CNPJ><xNome>Tomador</xNome></toma>'
            . '<serv><locPrest><cLocPrestacao>1302603</cLocPrestacao></locPrest><cServ><cTribNac>010701</cTribNac><xDescServ>Servico</xDescServ></cServ></serv>'
            . '<valores><vServPrest><vServ>100.00</vServ></vServPrest><trib><tribMun><tribISSQN>1</tribISSQN><tpRetISSQN>1</tpRetISSQN></tribMun>'
            . '<totTrib><indTotTrib>0</indTotTrib></totTrib></trib></valores></infDPS></DPS>';
    }

    private array $p = ['cIndOp' => '100301', 'CST' => '000', 'cClassTrib' => '000001'];

    public function testGrupoVaiDepoisDeValoresNaOrdemDoXsd(): void
    {
        $xml = NfseService::injetarIbsCbsXml($this->dpsBase(), $this->p);
        $d = new DOMDocument();
        $d->loadXML($xml);
        $inf = $d->getElementsByTagName('infDPS')->item(0);
        $this->assertSame('IBSCBS', $inf->lastChild->localName);
        $this->assertSame('valores', $inf->lastChild->previousSibling->localName);
        $filhos = [];
        foreach ($inf->lastChild->childNodes as $n) {
            $filhos[] = $n->localName;
        }
        $this->assertSame(['finNFSe', 'indFinal', 'cIndOp', 'indDest', 'valores'], $filhos);
        $this->assertStringContainsString('<cIndOp>100301</cIndOp>', $xml);
        $this->assertStringContainsString('<CST>000</CST><cClassTrib>000001</cClassTrib>', $xml);
    }

    public function testValidaNoXsdOficial(): void
    {
        $xml = NfseService::injetarIbsCbsXml($this->dpsBase(), $this->p);
        $d = new DOMDocument();
        $d->loadXML($xml);
        libxml_use_internal_errors(true);
        $ok = $d->schemaValidate($this->xsdSemAncoras());
        $erros = array_map(fn ($e) => trim($e->message), libxml_get_errors());
        libxml_clear_errors();
        $this->assertTrue($ok, implode(' | ', $erros));
    }

    /**
     * Os XSDs oficiais trazem patterns com ^ e $ (ex.: serie), que em XSD são
     * literais e o libxml rejeita. Valida numa cópia temporária sem as âncoras.
     */
    private function xsdSemAncoras(): string
    {
        $dir = sys_get_temp_dir() . '/nfse_xsd_' . md5(__FILE__);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        foreach (glob(dirname(self::XSD) . '/*.xsd') as $f) {
            $c = file_get_contents($f);
            $c = preg_replace_callback('/pattern value="([^"]*)"/', fn ($m) => 'pattern value="' . preg_replace('/^\^|\$$/', '', $m[1]) . '"', $c);
            file_put_contents($dir . '/' . basename($f), $c);
        }

        return $dir . '/DPS_v1.01.xsd';
    }

    public function testSemCindOpNaoAlteraXml(): void
    {
        $base = $this->dpsBase();
        $this->assertSame($base, NfseService::injetarIbsCbsXml($base, ['cIndOp' => '']));
    }

    public function testNaoDuplicaGrupo(): void
    {
        $uma = NfseService::injetarIbsCbsXml($this->dpsBase(), $this->p);
        $this->assertSame($uma, NfseService::injetarIbsCbsXml($uma, $this->p));
    }
}
