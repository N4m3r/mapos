<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Projeto_sync.php';

/**
 * Decisao de sincronizacao OS x projeto (ADR-015), sem banco.
 */
class ProjetoSyncTest extends TestCase
{
    public function testEmAndamentoInicia(): void
    {
        $r = Projeto_sync::decidir('Em Andamento', [], 0);
        $this->assertTrue($r['iniciar']);
        $this->assertFalse($r['concluir_etapa']);
    }

    public function testUltimaOsFinalizadaConcluiEtapa(): void
    {
        $r = Projeto_sync::decidir('Finalizado', ['Finalizado', 'Faturado', 'Cancelado'], 0);
        $this->assertTrue($r['concluir_etapa']);
        $this->assertFalse($r['pendencia_pontos']);
    }

    public function testOsAindaAbertaNaEtapaNaoConclui(): void
    {
        $r = Projeto_sync::decidir('Finalizado', ['Finalizado', 'Em Andamento'], 0);
        $this->assertFalse($r['concluir_etapa']);
        $this->assertFalse($r['pendencia_pontos']);
    }

    public function testPontosPendentesImpedemConclusao(): void
    {
        $r = Projeto_sync::decidir('Faturado', ['Faturado'], 3);
        $this->assertFalse($r['concluir_etapa']);
        $this->assertTrue($r['pendencia_pontos']);
    }

    public function testSoCanceladasOuOutrosStatusNaoFazemNada(): void
    {
        $this->assertSame(
            ['iniciar' => false, 'concluir_etapa' => false, 'pendencia_pontos' => false],
            Projeto_sync::decidir('Finalizado', ['Cancelado'], 0)
        );
        $this->assertFalse(Projeto_sync::decidir('Aberto', ['Aberto'], 0)['iniciar']);
        $this->assertFalse(Projeto_sync::decidir('Cancelado', ['Cancelado'], 0)['concluir_etapa']);
    }
}
