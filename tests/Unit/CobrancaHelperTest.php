<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/cobranca_helper.php';
require_once APPPATH . 'helpers/tributacao_helper.php';

class CobrancaHelperTest extends TestCase
{
    public function testResumoTotais(): void
    {
        $r = cobranca_resumo(
            [['descricao' => 'Cabo', 'quantidade' => 2, 'preco' => 10.01]],
            [['nome' => 'Instalacao', 'quantidade' => 1, 'preco' => 100, 'subTotal' => 100]],
            20
        );
        $this->assertSame(20.02, $r['subtotal_produtos']);
        $this->assertSame(100.0, $r['subtotal_servicos']);
        $this->assertSame(100.02, $r['total']);
        $this->assertCount(2, $r['itens']);
    }

    public function testImpostosSemConfig(): void
    {
        $r = cobranca_impostos_estimados(100, null);
        $this->assertSame([], $r['itens']);
        $this->assertSame(100.0, $r['liquido_estimado']);
    }

    public function testImpostosDasEReforma(): void
    {
        $cfg = (object) ['simples_aliquota_efetiva' => 6, 'reforma_ativa' => 1, 'reforma_cbs' => 0.9, 'reforma_ibs_uf' => 0.1, 'reforma_ibs_mun' => 0];
        $r = cobranca_impostos_estimados(1000, $cfg);
        $this->assertCount(3, $r['itens']);
        $this->assertSame(60.0, $r['itens'][0]['valor']);
        $this->assertSame(940.0, $r['liquido_estimado']);
        $this->assertStringContainsString('não é cobrado', $r['itens'][1]['nota']);
    }

    public function testImpostosAnoRegimeRegularCreditoIntegral(): void
    {
        $cfg = (object) ['op_simp_nac' => 1, 'simples_aliquota_efetiva' => 0, 'reforma_ativa' => 1];
        $param = ['ano' => 2027, 'cbs' => 0.9, 'ibs_uf' => 0.1, 'ibs_mun' => 0.0, 'confirmado' => true, 'estimado' => false];
        $r = cobranca_impostos_ano(1000, $cfg, $param, 'regular', 2027);
        $this->assertCount(2, $r['itens']);
        $this->assertSame('CBS', $r['itens'][0]['rotulo']);
        $this->assertSame('Recolhido pelo regime regular', $r['itens'][0]['nota']);
        $this->assertSame(9.0, $r['itens'][0]['valor']);
        $this->assertSame(1000.0, $r['liquido_estimado']);
        $this->assertSame(1.0, $r['credito_cliente']['percentual']);
        $this->assertStringContainsString('integral', $r['credito_cliente']['texto']);
    }

    public function testImpostosAno2026NotaEDasNoRegimeDas(): void
    {
        $cfg = (object) ['op_simp_nac' => 3, 'simples_aliquota_efetiva' => 6, 'reforma_ativa' => 1];
        $param = ['cbs' => 0.9, 'ibs_uf' => 0.1, 'ibs_mun' => 0, 'confirmado' => false, 'estimado' => true];
        $r = cobranca_impostos_ano(1000, $cfg, $param, 'das', 2026);
        $this->assertCount(3, $r['itens']);
        $this->assertSame('DAS (Simples Nacional)', $r['itens'][0]['rotulo']);
        $this->assertSame(940.0, $r['liquido_estimado']);
        $this->assertStringContainsString('não é cobrado', $r['itens'][1]['nota']);
        $this->assertSame('CBS (alíquota estimada)', $r['itens'][1]['rotulo']);
        $this->assertNull($r['credito_cliente']['percentual']);
    }

    public function testImpostosAnoRegimeDasOutroAno(): void
    {
        $cfg = (object) ['op_simp_nac' => 3, 'simples_aliquota_efetiva' => 0, 'reforma_ativa' => 1];
        $param = ['cbs' => 0.9, 'ibs_uf' => 0.1, 'ibs_mun' => 0, 'confirmado' => true, 'estimado' => false];
        $r = cobranca_impostos_ano(100, $cfg, $param, 'das', 2028);
        $this->assertSame('Incluído no DAS', $r['itens'][0]['nota']);
        $this->assertSame('CBS', $r['itens'][0]['rotulo']);
        $this->assertCount(2, $r['itens']);
        $this->assertStringContainsString('só aproveita', $r['credito_cliente']['texto']);
    }

    public function testKpis(): void
    {
        $l = [
            (object) ['status' => 'waiting', 'expire_at' => '2026-11-10', 'total' => 10000],
            (object) ['status' => 'waiting', 'expire_at' => '2026-10-01', 'total' => 5000],
            (object) ['status' => 'OVERDUE', 'expire_at' => '2026-11-30', 'total' => 2500],
            (object) ['status' => 'PAID', 'expire_at' => '2026-10-05', 'total' => 7000],
            (object) ['status' => 'CANCELLED', 'expire_at' => '2026-10-05', 'total' => 9900],
        ];
        $k = cobranca_kpis($l, '2026-10-31');
        $this->assertSame(['qtd' => 1, 'total' => 100.0], $k['a_receber']);
        $this->assertSame(['qtd' => 2, 'total' => 75.0], $k['vencidas']);
        $this->assertSame(['qtd' => 1, 'total' => 70.0], $k['pagas_mes']);
    }

    public function testPrazo(): void
    {
        $this->assertSame('vence em 3 dias', cobranca_prazo_texto('2026-11-03', '2026-10-31'));
        $this->assertSame('vencida há 1 dia', cobranca_prazo_texto('2026-10-30', '2026-10-31'));
    }

    public function testItensCobradosIgnoraCanceladaEDetectaInteiro(): void
    {
        $this->assertSame(['inteiro' => false, 'chaves' => []], cobranca_itens_cobrados([]));
        $this->assertSame(
            ['inteiro' => false, 'chaves' => ['s:1', 'p:2']],
            cobranca_itens_cobrados([
                ['status' => 'PENDING', 'itens_ref' => 's:1'],
                ['status' => 'CANCELED', 'itens_ref' => 'p:9'],
                ['status' => 'paid', 'itens_ref' => 'p:2,s:1'],
            ])
        );
        $this->assertTrue(cobranca_itens_cobrados([['status' => 'waiting', 'itens_ref' => null]])['inteiro']);
        $this->assertFalse(cobranca_itens_cobrados([['status' => 'canceled', 'itens_ref' => null]])['inteiro']);
    }

    public function testMontarLotes(): void
    {
        $disp = ['p:1', 's:2', 's:3'];
        $this->assertSame([['s:2', 's:3']], cobranca_montar_lotes(['s:3', 's:2'], $disp, 'junto'));
        $this->assertSame([['p:1'], ['s:3']], cobranca_montar_lotes(['s:3', 'p:1', 'x:9'], $disp, 'separado'));
        $this->assertSame([], cobranca_montar_lotes(['s:9'], $disp, 'junto'));
    }

    public function testRotuloLote(): void
    {
        $todas = ['p:1', 's:2', 's:3'];
        $nomes = ['p:1' => 'Câmera', 's:2' => 'Instalação', 's:3' => 'Configuração'];
        $this->assertSame('', cobranca_rotulo_lote($todas, $todas, $nomes));
        $this->assertSame(' - Serviços', cobranca_rotulo_lote(['s:2', 's:3'], $todas, $nomes));
        $this->assertSame(' - Câmera', cobranca_rotulo_lote(['p:1'], $todas, $nomes));
        $this->assertSame(' - Produtos', cobranca_rotulo_lote(['p:1', 'p:4'], ['p:1', 'p:4', 's:2'], $nomes));
    }

    public function testRatearCentavosFechaOTotal(): void
    {
        $this->assertSame([10000, 5000], cobranca_ratear_centavos([100, 50], 15000));
        // 100 dividido em 3 partes iguais: sobra de 1 centavo vai para o 1º.
        $this->assertSame([3334, 3333, 3333], cobranca_ratear_centavos([1, 1, 1], 10000));
        // Nota com ISS retido: 175 bruto, 166,25 líquido.
        $r = cobranca_ratear_centavos([100, 50, 25], 16625);
        $this->assertSame(16625, array_sum($r));
        $this->assertSame([9500, 4750, 2375], $r);
        $this->assertSame([0, 0], cobranca_ratear_centavos([0, 0], 500));
    }

    public function testSplitEstimadoSemReformaNaoTirafNada(): void
    {
        $this->assertSame(['v_ibs' => 0.0, 'v_cbs' => 0.0, 'v_liquido' => 1000.0], cobranca_split_estimado(100000, null));
        $cfg = (object) ['reforma_ativa' => 0, 'reforma_cbs' => 9, 'reforma_ibs_uf' => 1, 'reforma_ibs_mun' => 0];
        $this->assertSame(['v_ibs' => 0.0, 'v_cbs' => 0.0, 'v_liquido' => 1000.0], cobranca_split_estimado(100000, $cfg));
    }

    public function testSplitEstimadoComReforma(): void
    {
        $cfg = (object) ['reforma_ativa' => 1, 'reforma_cbs' => 0.9, 'reforma_ibs_uf' => 0.1, 'reforma_ibs_mun' => 0.1];
        // 1000,00: IBS 0,2% = 2,00; CBS 0,9% = 9,00; líquido = 989,00
        $this->assertSame(['v_ibs' => 2.0, 'v_cbs' => 9.0, 'v_liquido' => 989.0], cobranca_split_estimado(100000, $cfg));
    }

    public function testSplitEstimadoArredondaEmDuasCasas(): void
    {
        $cfg = (object) ['reforma_ativa' => 1, 'reforma_cbs' => 0.9, 'reforma_ibs_uf' => 0.1, 'reforma_ibs_mun' => 0];
        // 33,33: IBS 0,1% = 0,03; CBS 0,9% = 0,30; líquido = 33,00
        $this->assertSame(['v_ibs' => 0.03, 'v_cbs' => 0.3, 'v_liquido' => 33.0], cobranca_split_estimado(3333, $cfg));
        $this->assertSame(['v_ibs' => 0.0, 'v_cbs' => 0.0, 'v_liquido' => 0.0], cobranca_split_estimado(0, $cfg));
    }
}
