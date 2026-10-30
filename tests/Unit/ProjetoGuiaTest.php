<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Projeto_guia.php';

/**
 * Proximo passo e prazo do projeto (ADR-016), sem banco.
 */
class ProjetoGuiaTest extends TestCase
{
    private function base(array $o = []): array
    {
        return $o + [
            'status' => 'em_execucao', 'etapas' => 5, 'etapas_sem_os' => 0, 'plan_ativo' => true,
            'exige_pontos' => true, 'pontos' => 8, 'pontos_testados' => 8, 'tem_equipe' => true,
            'os_abertas' => 0, 'pode_executar' => true, 'pode_editar' => true,
        ];
    }

    public function testSemEtapasPedeCriarEtapas(): void
    {
        $r = Projeto_guia::proximoPasso($this->base(['etapas' => 0]));
        $this->assertSame(1, $r['fase']);
        $this->assertSame('#aba-planejamento', $r['alvo']);
    }

    public function testSemPontosEmProjetoDeSegurancaPedePontos(): void
    {
        $r = Projeto_guia::proximoPasso($this->base(['pontos' => 0, 'pontos_testados' => 0]));
        $this->assertSame(1, $r['fase']);
        $this->assertSame('#modal-lote', $r['alvo']);
    }

    public function testSemPontosEmProjetoSemPontosSegue(): void
    {
        $r = Projeto_guia::proximoPasso($this->base(['exige_pontos' => false, 'pontos' => 0, 'pontos_testados' => 0]));
        $this->assertSame(4, $r['fase']);
    }

    public function testSemEquipePedeVincular(): void
    {
        $r = Projeto_guia::proximoPasso($this->base(['tem_equipe' => false]));
        $this->assertSame(1, $r['fase']);
        $this->assertSame('Vincular equipe', $r['botao_rotulo']);
    }

    public function testEtapasSemOsPedeGerarOs(): void
    {
        $r = Projeto_guia::proximoPasso($this->base(['etapas_sem_os' => 3]));
        $this->assertSame(2, $r['fase']);
        $this->assertSame('#modal-os-lote', $r['alvo']);
    }

    public function testOsAbertasPedeRegistrarExecucao(): void
    {
        $r = Projeto_guia::proximoPasso($this->base(['os_abertas' => 2]));
        $this->assertSame(3, $r['fase']);
        $this->assertSame('#modal-registro', $r['alvo']);
        $r = Projeto_guia::proximoPasso($this->base(['os_abertas' => 2, 'pode_executar' => false]));
        $this->assertSame('#aba-execucao', $r['alvo']);
    }

    public function testPontosPendentesPedeAtualizarPontos(): void
    {
        $r = Projeto_guia::proximoPasso($this->base(['pontos_testados' => 1]));
        $this->assertSame(3, $r['fase']);
        $this->assertSame('#aba-pontos', $r['alvo']);
        $this->assertStringContainsString('7', $r['texto']);
    }

    public function testTudoTestadoPedeDocumentacaoEMedicao(): void
    {
        $r = Projeto_guia::proximoPasso($this->base());
        $this->assertSame(4, $r['fase']);
        $this->assertSame('#aba-financeiro', $r['alvo']);
    }

    public function testConcluidaNaoTemBotao(): void
    {
        foreach (['concluida', 'entregue'] as $s) {
            $r = Projeto_guia::proximoPasso($this->base(['status' => $s, 'etapas' => 0]));
            $this->assertSame(4, $r['fase']);
            $this->assertSame('', $r['botao_rotulo']);
            $this->assertSame('', $r['alvo']);
        }
        $this->assertSame('', Projeto_guia::proximoPasso($this->base(['status' => 'cancelada']))['botao_rotulo']);
    }

    public function testTipoExigePontos(): void
    {
        $this->assertTrue(Projeto_guia::tipoExigePontos('CFTV IP'));
        $this->assertTrue(Projeto_guia::tipoExigePontos('Controle de Acesso'));
        $this->assertTrue(Projeto_guia::tipoExigePontos('Rede estruturada'));
        $this->assertFalse(Projeto_guia::tipoExigePontos(''));
        $this->assertFalse(Projeto_guia::tipoExigePontos('Pintura'));
    }

    public function testContarOsAbertas(): void
    {
        $this->assertSame(2, Projeto_guia::contarOsAbertas(['Aberto', 'Finalizado', 'Em Andamento', 'Cancelado', 'Faturado']));
    }

    public function testPrazo(): void
    {
        $this->assertSame('Faltam 12 dias', Projeto_guia::prazo('2026-11-10', '2026-10-29')['texto']);
        $this->assertSame('Faltam 1 dia', Projeto_guia::prazo('2026-10-30', '2026-10-29')['texto']);
        $a = Projeto_guia::prazo('2026-10-26', '2026-10-29');
        $this->assertSame('Atrasado 3 dias', $a['texto']);
        $this->assertSame('perigo', $a['classe']);
        $this->assertSame('Prazo termina hoje', Projeto_guia::prazo('2026-10-29', '2026-10-29')['texto']);
        $this->assertSame('Sem prazo definido', Projeto_guia::prazo('', '2026-10-29')['texto']);
        $this->assertSame('Encerrado', Projeto_guia::prazo('2026-01-01', '2026-10-29', true)['texto']);
    }
}
