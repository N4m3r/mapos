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

    /* ===================== horasEntre (ADR-015 fase 4) ===================== */

    public function testHorasEntreCalculaDiferencaEm2Casas(): void
    {
        $this->assertSame(1.5, Projeto_sync::horasEntre('2026-10-29 08:00:00', '2026-10-29 09:30:00'));
        $this->assertSame(8.25, Projeto_sync::horasEntre('2026-10-29 08:00:00', '2026-10-29 16:15:00'));
    }

    public function testHorasEntreZeroSemDadosOuSaidaMenorQueEntrada(): void
    {
        $this->assertSame(0.0, Projeto_sync::horasEntre(null, '2026-10-29 09:00:00'));
        $this->assertSame(0.0, Projeto_sync::horasEntre('2026-10-29 09:00:00', null));
        $this->assertSame(0.0, Projeto_sync::horasEntre('2026-10-29 09:00:00', '2026-10-29 08:00:00'));
        $this->assertSame(0.0, Projeto_sync::horasEntre('2026-10-29 09:00:00', '2026-10-29 09:00:00'));
    }

    /* ============== decidirPontoNovoStatus (ADR-015 fase 4) ============== */

    public function testDecidirPontoNovoStatusAvancaDePrevistoParaInstalado(): void
    {
        $this->assertSame('instalado', Projeto_sync::decidirPontoNovoStatus('previsto', 'instalado'));
        $this->assertSame('instalado', Projeto_sync::decidirPontoNovoStatus('cabeado', 'instalado'));
    }

    public function testDecidirPontoNovoStatusNaoRecuaSeJaMaisAdiante(): void
    {
        $this->assertSame('configurado', Projeto_sync::decidirPontoNovoStatus('configurado', 'instalado'));
        $this->assertSame('testado', Projeto_sync::decidirPontoNovoStatus('testado', 'instalado'));
        $this->assertSame('instalado', Projeto_sync::decidirPontoNovoStatus('instalado', 'instalado'));
    }

    public function testDecidirPontoNovoStatusDesconhecidoNaoAvanca(): void
    {
        $this->assertSame('bugado', Projeto_sync::decidirPontoNovoStatus('bugado', 'instalado'));
    }

    /* ============== decidirOrigemMaterial (ADR-015 fase 4) ============== */

    public function testDecidirOrigemMaterialObraQuandoFaturaNoProjeto(): void
    {
        $this->assertSame('obra', Projeto_sync::decidirOrigemMaterial((object) ['fatura_no_projeto' => 1]));
        $this->assertSame('obra', Projeto_sync::decidirOrigemMaterial(['fatura_no_projeto' => '1']));
    }

    public function testDecidirOrigemMaterialEstoqueSemVinculoOuSemFatura(): void
    {
        $this->assertSame('estoque', Projeto_sync::decidirOrigemMaterial(null));
        $this->assertSame('estoque', Projeto_sync::decidirOrigemMaterial((object) ['fatura_no_projeto' => 0]));
        $this->assertSame('estoque', Projeto_sync::decidirOrigemMaterial([]));
    }
}
