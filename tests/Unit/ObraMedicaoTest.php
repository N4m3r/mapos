<?php

use PHPUnit\Framework\TestCase;

require_once BASEPATH . 'core/Model.php';
require_once APPPATH . 'models/Obra_medicao_model.php';

/**
 * Faturamento por medicao (ADR-015 fase 5) - regras puras.
 */
class ObraMedicaoTest extends TestCase
{
    public function testEntraNoValorSoAdicional(): void
    {
        $this->assertTrue(Obra_medicao_model::entraNoValor('adicional'));
        foreach (['origem', 'execucao', 'corretiva', ''] as $t) {
            $this->assertFalse(Obra_medicao_model::entraNoValor($t), $t);
        }
    }

    public function testValorOsComDesconto(): void
    {
        $this->assertSame(350.0, Obra_medicao_model::valorOsComDesconto(200, 150, 0));
        $this->assertSame(300.0, Obra_medicao_model::valorOsComDesconto(200, 150, 300));
        $this->assertSame(0.0, Obra_medicao_model::valorOsComDesconto(0, 0, null));
    }

    public function testSomaValorOsIgnoraReferencia(): void
    {
        $os = [
            (object) ['tipo_vinculo' => 'adicional', 'valor' => 300],
            ['tipo_vinculo' => 'execucao', 'valor' => 1000],
            ['tipo_vinculo' => 'adicional', 'valor' => 49.995],
            ['tipo_vinculo' => 'corretiva', 'valor' => 80, 'soma_valor' => false],
        ];
        $this->assertSame(350.0, Obra_medicao_model::somaValorOs($os));
        $this->assertSame(0.0, Obra_medicao_model::somaValorOs([]));
        // soma_valor explicito prevalece
        $this->assertSame(80.0, Obra_medicao_model::somaValorOs([['tipo_vinculo' => 'corretiva', 'valor' => 80, 'soma_valor' => true]]));
    }

    public function testValorTotalEtapasMaisAdicionais(): void
    {
        $etapas = [['valor' => 1400], ['valor' => 600.5]];
        $os = [['tipo_vinculo' => 'adicional', 'valor' => 300], ['tipo_vinculo' => 'execucao', 'valor' => 900]];
        $this->assertSame(2300.5, Obra_medicao_model::valorTotal($etapas, $os));
        $this->assertSame(1300.0, Obra_medicao_model::valorTotal(1000, $os));
        $this->assertSame(0.0, Obra_medicao_model::valorTotal([], []));
    }

    public function testCalcularEtapasNaoRecuaNemPassaDeCem(): void
    {
        $etapas = [
            (object) ['idEtapa' => 1, 'nome' => 'A', 'valor_previsto' => 4000, 'peso_percentual' => 50, 'percentual_concluido' => 40],
            (object) ['idEtapa' => 2, 'nome' => 'B', 'valor_previsto' => 2000, 'peso_percentual' => 50, 'percentual_concluido' => 50],
            (object) ['idEtapa' => 3, 'nome' => 'C', 'valor_previsto' => 1000, 'peso_percentual' => 0, 'percentual_concluido' => 10],
        ];
        $r = Obra_medicao_model::calcularEtapas($etapas, [1 => 60, 2 => 30, 3 => 150]);
        $this->assertSame(60.0, $r[0]['percentual_atual']);
        $this->assertSame(800.0, $r[0]['valor']);
        $this->assertSame(50.0, $r[1]['percentual_atual']);
        $this->assertSame(0.0, $r[1]['valor']);
        $this->assertSame(100.0, $r[2]['percentual_atual']);
        $this->assertSame(900.0, $r[2]['valor']);

        // sem percentual informado, etapa fica como esta
        $r = Obra_medicao_model::calcularEtapas($etapas, []);
        $this->assertSame(40.0, $r[0]['percentual_atual']);
        $this->assertSame(0.0, $r[0]['valor']);
    }

    public function testPercentualPonderado(): void
    {
        $e = [
            ['peso' => 25, 'percentual_atual' => 100],
            ['peso' => 75, 'percentual_atual' => 0],
        ];
        $this->assertSame(25.0, Obra_medicao_model::percentualPonderado($e, 'percentual_atual'));
        $semPeso = [['peso' => 0, 'percentual_atual' => 30], ['peso' => 0, 'percentual_atual' => 50]];
        $this->assertSame(40.0, Obra_medicao_model::percentualPonderado($semPeso, 'percentual_atual'));
        $this->assertSame(0.0, Obra_medicao_model::percentualPonderado([], 'percentual_atual'));
    }

    public function testStatusMedicaoRotulo(): void
    {
        $this->assertSame('Em aberto', Obra_medicao_model::statusMedicaoRotulo('aberta'));
        $this->assertSame('Aprovada', Obra_medicao_model::statusMedicaoRotulo('aprovada'));
        $this->assertSame('Faturada', Obra_medicao_model::statusMedicaoRotulo('faturada'));
    }
}
