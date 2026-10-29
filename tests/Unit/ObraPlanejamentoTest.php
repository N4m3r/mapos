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
}
