<?php

use Libraries\Fiscal\NfeService;
use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/regime_normal_helper.php';
require_once APPPATH . 'libraries/Fiscal/NfeService.php';

class RegimeNormalTest extends TestCase
{
    private function cfg(array $o = []): object
    {
        return (object) ($o + ['icms_cst_padrao' => '00', 'icms_aliquota_interna' => 18, 'icms_aliquota_interestadual' => 12,
            'pis_cofins_regime' => 'cumulativo', 'pis_cofins_cst' => '01']);
    }

    public function testCrt(): void
    {
        $this->assertSame([3, 4, 1], [rn_crt(1), rn_crt(2), rn_crt(3)]);
    }

    public function testIcmsInternoEInterestadual(): void
    {
        $this->assertSame(['cst' => '00', 'pICMS' => 18.0, 'vBC' => 100.0, 'vICMS' => 18.0], rn_icms_item(['vProd' => 100], $this->cfg(), false));
        $this->assertSame(12.0, rn_icms_item(['vProd' => 100], $this->cfg(), true)['pICMS']);
    }

    public function testOverrideDoProduto(): void
    {
        $r = rn_icms_item(['vProd' => 200, 'icms_aliquota' => '7'], $this->cfg(), false);
        $this->assertSame(14.0, $r['vICMS']);
        $r = rn_icms_item(['vProd' => 200, 'icms_cst' => '40'], $this->cfg(), false);
        $this->assertSame(['cst' => '40', 'pICMS' => 0.0, 'vBC' => 0.0, 'vICMS' => 0.0], $r);
    }

    public function testCstNaoSuportado(): void
    {
        $this->expectException(Exception::class);
        rn_icms_item(['vProd' => 1, 'icms_cst' => '10'], $this->cfg(), false);
    }

    public function testPisCofins(): void
    {
        $c = rn_pis_cofins_item(1000, $this->cfg());
        $this->assertSame([6.5, 30.0], [$c['vPIS'], $c['vCOFINS']]);
        $n = rn_pis_cofins_item(1000, $this->cfg(['pis_cofins_regime' => 'nao_cumulativo']));
        $this->assertSame([16.5, 76.0], [$n['vPIS'], $n['vCOFINS']]);
        $this->assertSame(0.0, rn_pis_cofins_item(1000, $this->cfg(['pis_cofins_cst' => '49']))['vPIS']);
    }

    private function xml(array $cfg, array $venda = []): string
    {
        $config = (object) ($cfg + (array) $this->cfg() + [
            'ambiente' => 2, 'codigo_municipio' => '3550308', 'cfop_padrao' => '5102', 'csosn_padrao' => '102',
            'serie_nfe' => 1, 'op_simp_nac' => 1, 'reforma_ativa' => 0, 'reforma_cst' => '000', 'reforma_cclasstrib' => '000001',
            'reforma_ibs_uf' => 0.1, 'reforma_ibs_mun' => 0, 'reforma_cbs' => 0.9,
        ]);
        $emit = (object) ['nome' => 'Empresa Teste', 'cnpj' => '11222333000181', 'ie' => '110042490114',
            'rua' => 'Rua A', 'numero' => '1', 'bairro' => 'Centro', 'cidade' => 'Sao Paulo', 'uf' => 'SP', 'cep' => '01001000', 'telefone' => '1133334444', 'email' => 'e@x.com'];
        $venda = (object) ($venda + ['idVendas' => 1, 'desconto' => 0, 'nomeCliente' => 'Cliente', 'documento' => '11444777000161', 'ie' => '110042490114', 'estado' => 'SP', 'email' => 'a@b.com']);
        $item = (object) ['produtos_id' => 1, 'descricao' => 'Produto', 'ncm' => '85258929', 'cfop' => '5102',
            'quantidade' => 2, 'preco' => 500, 'unidade' => 'UN', 'cest' => null];

        return (new NfeService($config, $emit))->montarXmlRascunho($venda, [$item], 1);
    }

    public function testXmlRegimeNormal(): void
    {
        $xml = $this->xml([]);
        $this->assertStringContainsString('<CRT>3</CRT>', $xml);
        $this->assertStringContainsString('<ICMS00>', $xml);
        $this->assertStringNotContainsString('ICMSSN', $xml);
        $this->assertStringContainsString('<pICMS>18.0000</pICMS>', $xml);
        $this->assertMatchesRegularExpression('#<ICMSTot><vBC>1000.00</vBC><vICMS>180.00</vICMS>#', $xml);
        $this->assertMatchesRegularExpression('#<vPIS>6.50</vPIS><vCOFINS>30.00</vCOFINS>#', $xml);
        $this->assertStringContainsString('<pPIS>0.6500</pPIS>', $xml);
        $this->assertStringContainsString('<pCOFINS>3.0000</pCOFINS>', $xml);
    }

    public function testXmlSimplesInalterado(): void
    {
        $xml = $this->xml(['op_simp_nac' => 3]);
        $this->assertStringContainsString('<CRT>1</CRT>', $xml);
        $this->assertStringContainsString('<ICMSSN102>', $xml);
        $this->assertStringContainsString('<CST>49</CST>', $xml);
    }

    public function testDifalNaoSuportado(): void
    {
        $this->expectExceptionMessage('DIFAL ainda não suportado');
        $this->xml([], ['estado' => 'RJ', 'documento' => '52998224725']);
    }
}
