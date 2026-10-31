<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/classificacao_fiscal_helper.php';

class ClassificacaoFiscalTest extends TestCase
{
    private $cfg = ['reforma_cst' => '000', 'reforma_cclasstrib' => '000001', 'reforma_cindop' => '020101'];

    public function testUsaPadraoQuandoItemVazio(): void
    {
        $r = classificacao_efetiva(['ibscbs_cst' => null], $this->cfg, 'produto');
        $this->assertSame(['cst' => '000', 'cclasstrib' => '000001', 'cindop' => '020101', 'origem' => 0, 'fonte' => 'padrao'], $r);
    }

    public function testUsaItemQuandoValido(): void
    {
        $item = (object) ['ibscbs_cst' => '200', 'ibscbs_cclasstrib' => '200003', 'cindop' => '030101', 'origem' => '1'];
        $r = classificacao_efetiva($item, (object) $this->cfg, 'servico');
        $this->assertSame('item', $r['fonte']);
        $this->assertSame('200003', $r['cclasstrib']);
        $this->assertSame('030101', $r['cindop']);
        $this->assertSame(1, $r['origem']);
    }

    public function testItemInvalidoCaiNoPadrao(): void
    {
        $r = classificacao_efetiva(['ibscbs_cst' => '200', 'ibscbs_cclasstrib' => '000001'], $this->cfg, 'produto');
        $this->assertSame('padrao', $r['fonte']);
        $this->assertSame('000', $r['cst']);
    }

    public function testCindopDoItemIgnoradoParaProduto(): void
    {
        $r = classificacao_efetiva(['cindop' => '030101'], $this->cfg, 'produto');
        $this->assertSame('020101', $r['cindop']);
    }

    public function testDadosPost(): void
    {
        $r = classificacao_dados_post(['ibscbs_cst' => '200', 'ibscbs_cclasstrib' => '200003', 'origem' => '2', 'p_red_ibscbs' => '60,5'], 'produto');
        $this->assertSame([], $r['erros']);
        $this->assertSame(2, $r['dados']['origem']);
        $this->assertSame(60.5, $r['dados']['p_red_ibscbs']);
        $r = classificacao_dados_post(['ibscbs_cst' => '200', 'ibscbs_cclasstrib' => '000001', 'nbs' => '123'], 'servico');
        $this->assertCount(2, $r['erros']);
        $this->assertNull($r['dados']['ibscbs_cst']);
        $this->assertNull($r['dados']['nbs']);
    }
}
