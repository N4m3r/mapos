<?php

use PHPUnit\Framework\TestCase;

require_once BASEPATH . 'core/Model.php';
require_once APPPATH . 'models/Obra_planejamento_model.php';

/**
 * Planejamento do projeto e geracao de OS (ADR-015 fase 2) - regras puras.
 */
class ObraPlanejamentoTest extends TestCase
{
    public function testMontarDescricaoOsSemPontos(): void
    {
        $this->assertSame(
            'Projeto Loja X - Etapa 1.2 Cabeamento',
            Obra_planejamento_model::montarDescricaoOs('Loja X', '1.2', 'Cabeamento')
        );
        $this->assertSame(
            'Projeto Loja X - Etapa Cabeamento',
            Obra_planejamento_model::montarDescricaoOs('Loja X', '', 'Cabeamento')
        );
    }

    public function testMontarDescricaoOsComPontosEObservacao(): void
    {
        $t = Obra_planejamento_model::montarDescricaoOs('Loja X', '2', 'Cameras', ['CAM-01', ' ', 'CAM-02'], 'Levar escada');
        $this->assertSame("Projeto Loja X - Etapa 2 Cameras\nPontos: CAM-01, CAM-02\nLevar escada", $t);
    }

    public function testCalcularTotal(): void
    {
        $this->assertSame(0.0, Obra_planejamento_model::calcularTotal([]));
        $itens = [
            ['quantidade' => 2, 'preco' => 10.5],
            (object) ['quantidade' => 1.5, 'preco' => 100],
            ['quantidade' => 3, 'preco' => 0.333],
        ];
        $this->assertSame(171.0 + 1.0, Obra_planejamento_model::calcularTotal($itens));
    }

    public function testTemOsDeExecucao(): void
    {
        $this->assertFalse(Obra_planejamento_model::temOsDeExecucao([]));
        $this->assertFalse(Obra_planejamento_model::temOsDeExecucao([['status' => 'Cancelado', 'tipo_vinculo' => 'execucao']]));
        $this->assertFalse(Obra_planejamento_model::temOsDeExecucao([['status' => 'Aberto', 'tipo_vinculo' => 'adicional']]));
        $this->assertTrue(Obra_planejamento_model::temOsDeExecucao([(object) ['status' => 'Aberto', 'tipo_vinculo' => 'execucao']]));
    }

    public function testFiltrarEtapasParaLote(): void
    {
        $etapas = [(object) ['idEtapa' => 1], (object) ['idEtapa' => 2], ['idEtapa' => 3]];
        $os = [
            1 => [['status' => 'Aberto', 'tipo_vinculo' => 'execucao']],
            2 => [['status' => 'Cancelado', 'tipo_vinculo' => 'execucao']],
        ];
        $this->assertSame([1 => false, 2 => true, 3 => true], Obra_planejamento_model::filtrarEtapasParaLote($etapas, $os, false));
        $this->assertSame([1 => true, 2 => true, 3 => true], Obra_planejamento_model::filtrarEtapasParaLote($etapas, $os, true));
    }

    public function testDistribuirPesosSomaCem(): void
    {
        $this->assertSame([], Obra_planejamento_model::distribuirPesos([]));
        $this->assertSame([100.0], Obra_planejamento_model::distribuirPesos([250]));
        $p = Obra_planejamento_model::distribuirPesos([100, 100, 100]);
        $this->assertSame(33.33, $p[0]);
        $this->assertSame(33.34, $p[2]);
        $this->assertEquals(100, round(array_sum($p), 2));
        $p = Obra_planejamento_model::distribuirPesos([1, 2, 7, 13.37]);
        $this->assertEquals(100, round(array_sum($p), 2));
        // sem valores: divide igualmente
        $p = Obra_planejamento_model::distribuirPesos([0, 0, 0, 0]);
        $this->assertSame([25.0, 25.0, 25.0, 25.0], $p);
    }

    public function testClassificarProduto(): void
    {
        $this->assertSame('camera', Obra_planejamento_model::classificarProduto('Camera bullet IP 2MP'));
        $this->assertSame('camera', Obra_planejamento_model::classificarProduto('Câmera Dome 4MP'));
        $this->assertSame('leitor_acesso', Obra_planejamento_model::classificarProduto('Leitor facial'));
        $this->assertSame('leitor_acesso', Obra_planejamento_model::classificarProduto('Controladora de acesso 2 portas'));
        $this->assertSame('fechadura', Obra_planejamento_model::classificarProduto('Fechadura eletromagnética'));
        $this->assertSame('ponto_rede', Obra_planejamento_model::classificarProduto('Keystone RJ45 Cat6'));
        $this->assertSame('nvr', Obra_planejamento_model::classificarProduto('NVR 16 canais'));
        $this->assertNull(Obra_planejamento_model::classificarProduto('Cabo UTP Cat5e'));
        $this->assertNull(Obra_planejamento_model::classificarProduto('Conector RJ45'));
        $this->assertNull(Obra_planejamento_model::classificarProduto('Fonte 12V para camera'));
        $this->assertNull(Obra_planejamento_model::classificarProduto('Caixa de passagem'));
        $this->assertNull(Obra_planejamento_model::classificarProduto(''));
    }

    public function testMontarEtapasDosServicos(): void
    {
        $servicos = [
            ['servicos_id' => 1, 'nome' => 'Instalacao', 'quantidade' => 1, 'preco' => 300, 'subtotal' => 300],
            ['servicos_id' => 2, 'nome' => 'Configuracao', 'quantidade' => 2, 'preco' => 100, 'subtotal' => 200],
        ];
        $produtos = [['produtos_id' => 9, 'descricao' => 'Camera', 'quantidade' => 4, 'preco' => 50]];
        $e = Obra_planejamento_model::montarEtapasDosServicos($servicos, $produtos);
        $this->assertCount(3, $e);
        $this->assertSame('1', $e[0]['codigo_eap']);
        $this->assertSame(60.0, $e[0]['peso_percentual']);
        $this->assertSame(40.0, $e[1]['peso_percentual']);
        $this->assertSame('Fornecimento de materiais', $e[2]['nome']);
        $this->assertSame(0.0, $e[2]['peso_percentual']);
        $this->assertSame(200.0, $e[2]['valor_previsto']);
        $this->assertSame('servico', $e[1]['itens'][0]['tipo']);
        $this->assertSame(2.0, $e[1]['itens'][0]['quantidade']);
    }

    public function testMontarEtapasSemServicos(): void
    {
        $e = Obra_planejamento_model::montarEtapasDosServicos([], [['produtos_id' => 9, 'descricao' => 'X', 'quantidade' => 1, 'preco' => 10]]);
        $this->assertCount(1, $e);
        $this->assertSame('Execucao', $e[0]['nome']);
        $this->assertSame(100.0, $e[0]['peso_percentual']);
        $this->assertSame([], Obra_planejamento_model::montarEtapasDosServicos([], []));
    }

    public function testStatusConversao(): void
    {
        $this->assertContains('Aprovado', Obra_planejamento_model::statusConversao());
        $this->assertNotContains('Aberto', Obra_planejamento_model::statusConversao());
    }

    public function testNormalizarEtapasIniciais(): void
    {
        $r = Obra_planejamento_model::normalizarEtapasIniciais([' Infra  ', '', 'Infra', 'Testes']);
        $this->assertSame(['Infra', 'Testes'], $r);
    }

    public function testNormalizarPontosIniciais(): void
    {
        $r = Obra_planejamento_model::normalizarPontosIniciais(['camera' => '6', 'nvr' => 0, 'x' => 3, 'rack' => 900], ['camera' => 1, 'nvr' => 1, 'rack' => 1]);
        $this->assertSame(['camera' => 6, 'rack' => 500], $r);
    }
}
