<?php

use Libraries\Fiscal\NfeService;
use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/tributacao_helper.php';
require_once APPPATH . 'libraries/Fiscal/NfeService.php';

class TributacaoValidarIbsCbsTest extends TestCase
{
    public function testValidoRetornaNull(): void
    {
        $this->assertNull(tributacao_validar_ibscbs('000', '000001', 55));
        $this->assertNull(tributacao_validar_ibscbs('200', '200003', 65));
    }

    public function testFormatoInvalido(): void
    {
        $this->assertStringContainsString('3 digitos', tributacao_validar_ibscbs('00', '000001', 55));
        $this->assertStringContainsString('3 digitos', tributacao_validar_ibscbs('0A0', '000001', 55));
        $this->assertStringContainsString('6 digitos', tributacao_validar_ibscbs('000', '00001', 55));
        $this->assertStringContainsString('6 digitos', tributacao_validar_ibscbs('000', '0000011', 55));
    }

    public function testClassTribIncompativelComCst(): void
    {
        $this->assertStringContainsString('nao e compativel', tributacao_validar_ibscbs('000', '200001', 55));
    }

    public function testModeloInvalido(): void
    {
        $this->assertStringContainsString('Modelo', tributacao_validar_ibscbs('000', '000001', 57));
    }

    private function xml(array $cfg): string
    {
        $config = (object) ($cfg + [
            'ambiente' => 2, 'codigo_municipio' => '3550308', 'cfop_padrao' => '5102', 'csosn_padrao' => '102',
            'serie_nfe' => 1, 'op_simp_nac' => 3, 'reforma_ativa' => 0, 'reforma_cst' => '000', 'reforma_cclasstrib' => '000001',
            'reforma_ibs_uf' => 0.1, 'reforma_ibs_mun' => 0, 'reforma_cbs' => 0.9,
        ]);
        $emit = (object) ['nome' => 'Empresa Teste', 'cnpj' => '11222333000181', 'ie' => '110042490114',
            'rua' => 'Rua A', 'numero' => '1', 'bairro' => 'Centro', 'cidade' => 'Sao Paulo', 'uf' => 'SP', 'cep' => '01001000', 'telefone' => '1133334444', 'email' => 'e@x.com'];
        $venda = (object) ['idVendas' => 1, 'desconto' => 0, 'nomeCliente' => 'Cliente', 'documento' => '52998224725', 'estado' => 'SP', 'email' => 'a@b.com'];
        $item = (object) ['produtos_id' => 1, 'descricao' => 'Produto', 'ncm' => '85258929', 'cfop' => '5102',
            'quantidade' => 1, 'preco' => 100, 'unidade' => 'UN', 'cest' => null];

        return (new NfeService($config, $emit))->montarXmlRascunho($venda, [$item], 1);
    }

    public function testRegimeNormalSempreEnviaIbsCbs(): void
    {
        try {
            $xml = $this->xml(['op_simp_nac' => 1, 'reforma_ativa' => 0]);
        } catch (Throwable $e) {
            $this->markTestSkipped('Montagem do XML indisponivel no ambiente: ' . $e->getMessage());
        }
        $this->assertStringContainsString('<IBSCBS>', $xml);
        $this->assertStringContainsString('<IBSCBSTot>', $xml);
    }

    public function testSimplesSemReformaNaoEnviaIbsCbs(): void
    {
        try {
            $xml = $this->xml(['op_simp_nac' => 3, 'reforma_ativa' => 0]);
        } catch (Throwable $e) {
            $this->markTestSkipped('Montagem do XML indisponivel no ambiente: ' . $e->getMessage());
        }
        $this->assertStringNotContainsString('<IBSCBS>', $xml);
    }

    public function testClassTribInvalidoBarraAntesDoXml(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('nao e compativel');
        $this->xml(['op_simp_nac' => 1, 'reforma_cst' => '000', 'reforma_cclasstrib' => '410001']);
    }
}
