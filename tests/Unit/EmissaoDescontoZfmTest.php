<?php

use Libraries\Fiscal\NfeService;
use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Fiscal/NfeService.php';

class EmissaoDescontoZfmTest extends TestCase
{
    private function xml(int $opSimp, array $venda = []): \SimpleXMLElement
    {
        $config = (object) ['ambiente' => 2, 'codigo_municipio' => '1302603', 'serie_nfe' => 1, 'cfop_padrao' => '5102',
            'csosn_padrao' => '102', 'op_simp_nac' => $opSimp, 'reforma_ativa' => 1, 'icms_cst_padrao' => '00',
            'icms_aliquota_interna' => 18, 'reforma_ibs_uf' => 0.1, 'reforma_ibs_mun' => 0, 'reforma_cbs' => 0.9,
            'reforma_cst' => '000', 'reforma_cclasstrib' => '000001', 'reforma_cindop' => '100301'];
        $emit = (object) ['nome' => 'Emit', 'cnpj' => '11222333000181', 'ie' => '123456789', 'rua' => 'Rua A', 'numero' => '1',
            'bairro' => 'Centro', 'cidade' => 'Manaus', 'uf' => 'AM', 'cep' => '69000000', 'email' => 'a@a.com', 'telefone' => '92999999999'];
        $v = (object) array_merge(['idVendas' => 1, 'documento' => '11222333000181', 'nomeCliente' => 'Cli', 'estado' => 'AM',
            'cidade' => 'Manaus', 'desconto' => 15], $venda);
        $itens = [
            (object) ['produtos_id' => 1, 'descricao' => 'A', 'ncm' => '85171231', 'quantidade' => 1, 'preco' => 100, 'unidade' => 'UN'],
            (object) ['produtos_id' => 2, 'descricao' => 'B', 'ncm' => '85171231', 'quantidade' => 1, 'preco' => 50, 'unidade' => 'UN'],
        ];
        $svc = new NfeService($config, $emit);

        return simplexml_load_string(preg_replace('/ xmlns="[^"]+"/', '', $svc->montarXmlRascunho($v, $itens, 1)));
    }

    public function testDescontoRateadoCrt3(): void
    {
        $x = $this->xml(1);
        $this->assertSame('10.00', (string) $x->infNFe->det[0]->prod->vDesc);
        $this->assertSame('5.00', (string) $x->infNFe->det[1]->prod->vDesc);
        $t = $x->infNFe->total->ICMSTot;
        $this->assertSame('15.00', (string) $t->vDesc);
        $this->assertSame('135.00', (string) $t->vNF);
        $this->assertSame('135.00', (string) $t->vBC);
        $this->assertSame('24.30', (string) $t->vICMS);
        $this->assertSame('90.00', (string) $x->infNFe->det[0]->imposto->ICMS->ICMS00->vBC);
        $this->assertSame('90.00', (string) $x->infNFe->det[0]->imposto->IBSCBS->gIBSCBS->vBC);
    }

    public function testDescontoRateadoCrt1(): void
    {
        $x = $this->xml(3);
        $t = $x->infNFe->total->ICMSTot;
        $this->assertSame('10.00', (string) $x->infNFe->det[0]->prod->vDesc);
        $this->assertSame('15.00', (string) $t->vDesc);
        $this->assertSame('135.00', (string) $t->vNF);
    }

    public function testIsufSoComSuframaValida(): void
    {
        $this->assertSame('123456789', (string) $this->xml(1, ['inscricao_suframa' => '123456789'])->infNFe->dest->ISUF);
        $this->assertCount(0, $this->xml(1, ['inscricao_suframa' => '12'])->infNFe->dest->ISUF);
    }
}
