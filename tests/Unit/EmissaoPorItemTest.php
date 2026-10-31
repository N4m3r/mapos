<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/classificacao_fiscal_helper.php';
require_once APPPATH . 'helpers/cliente_fiscal_helper.php';
require_once APPPATH . 'helpers/tributacao_helper.php';
require_once APPPATH . 'libraries/Fiscal/NfseService.php';

use Libraries\Fiscal\NfseService;

class EmissaoPorItemTest extends TestCase
{
    private function cfg(): object
    {
        return (object) ['reforma_cst' => '000', 'reforma_cclasstrib' => '000001', 'reforma_cindop' => '100301',
            'reforma_indfinal' => '0', 'codigo_municipio' => '1302603'];
    }

    private function svc(): NfseService
    {
        $r = new ReflectionClass(NfseService::class);
        $svc = $r->newInstanceWithoutConstructor();
        $prop = $r->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue($svc, $this->cfg());

        return $svc;
    }

    public function testItemComClassificacaoPropria(): void
    {
        $c = classificacao_efetiva((object) ['ibscbs_cst' => '200', 'ibscbs_cclasstrib' => '200003', 'origem' => 1], $this->cfg(), 'produto');
        $this->assertSame(['200', '200003', 1, 'item'], [$c['cst'], $c['cclasstrib'], $c['origem'], $c['fonte']]);
    }

    public function testItemHerdaPadrao(): void
    {
        $c = classificacao_efetiva((object) ['descricao' => 'X'], $this->cfg(), 'produto');
        $this->assertSame(['000', '000001', 'padrao'], [$c['cst'], $c['cclasstrib'], $c['fonte']]);
        $this->assertSame([], fiscal_prevalidar_itens([(object) ['descricao' => 'X']], $this->cfg(), 55));
    }

    public function testItemInvalidoBloqueiaComNomeDoItem(): void
    {
        $cfg = $this->cfg();
        $cfg->reforma_cclasstrib = '12';
        $erros = fiscal_prevalidar_itens([(object) ['descricao' => 'Parafuso']], $cfg, 55);
        $this->assertCount(1, $erros);
        $this->assertStringContainsString('Item 1 (Parafuso)', $erros[0]);
    }

    public function testNfseComClassificacoesDivergentesLancaErro(): void
    {
        $svc = $this->svc();
        $m = new ReflectionMethod($svc, 'paramsIbsCbs');
        $m->setAccessible(true);
        $this->expectExceptionMessage('precisam ir em notas separadas');
        $m->invoke($svc, [
            (object) ['ibscbs_cst' => '200', 'ibscbs_cclasstrib' => '200003'],
            (object) ['nome' => 'padrao'],
        ], ['ie' => ''], '12345678000199');
    }

    public function testNfseUsaClassificacaoDoServicoEIndFinalPf(): void
    {
        $svc = $this->svc();
        $m = new ReflectionMethod($svc, 'paramsIbsCbs');
        $m->setAccessible(true);
        $p = $m->invoke($svc, [(object) ['ibscbs_cst' => '200', 'ibscbs_cclasstrib' => '200003', 'cindop' => '100302']], ['ie' => ''], '123.456.789-09');
        $this->assertSame(['200', '200003', '100302', '1'], [$p['CST'], $p['cClassTrib'], $p['cIndOp'], $p['indFinal']]);
        $p = $m->invoke($svc, [(object) []], ['ie' => '123'], '12345678000199');
        $this->assertSame('0', $p['indFinal']);
    }
}
