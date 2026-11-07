<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/atendimento_regras_helper.php';

class AtendimentoRegrasTest extends TestCase
{
    public function testGestorRemoveQualquerFoto(): void
    {
        $this->assertTrue(AtendimentoRegras::podeRemoverFoto(true, false, 1, 99, false));
    }

    public function testTecnicoSoRemoveSuaFotoEmOsVinculada(): void
    {
        $this->assertTrue(AtendimentoRegras::podeRemoverFoto(false, true, 5, 5, true));
        $this->assertFalse(AtendimentoRegras::podeRemoverFoto(false, true, 5, 6, true));
        $this->assertFalse(AtendimentoRegras::podeRemoverFoto(false, true, 5, 5, false));
        $this->assertFalse(AtendimentoRegras::podeRemoverFoto(false, false, 5, 5, true));
        $this->assertFalse(AtendimentoRegras::podeRemoverFoto(false, true, 0, 0, true));
    }

    public function testCheckinDaFoto(): void
    {
        $this->assertTrue(AtendimentoRegras::checkinValidoParaFoto(10, 5, 10, 5, false));
        $this->assertFalse(AtendimentoRegras::checkinValidoParaFoto(11, 5, 10, 5, false));
        $this->assertFalse(AtendimentoRegras::checkinValidoParaFoto(10, 6, 10, 5, false));
        $this->assertTrue(AtendimentoRegras::checkinValidoParaFoto(10, 6, 10, 5, true));
        $this->assertFalse(AtendimentoRegras::checkinValidoParaFoto(null, null, 10, 5, true));
    }

    public function testDataReagendada(): void
    {
        $this->assertSame('2026-11-05 08:00:00', AtendimentoRegras::dataAgendamentoReagendada('2026-11-05', null));
        $this->assertSame('2026-11-05 08:00:00', AtendimentoRegras::dataAgendamentoReagendada('2026-11-05', '2026-10-01 00:00:00'));
        $this->assertSame('2026-11-05 14:30:00', AtendimentoRegras::dataAgendamentoReagendada('2026-11-05', '2026-10-01 14:30:00'));
    }

    public function testStatusBloqueiaAtendimento(): void
    {
        $this->assertTrue(AtendimentoRegras::statusBloqueiaAtendimento('Finalizado'));
        $this->assertTrue(AtendimentoRegras::statusBloqueiaAtendimento('Faturado'));
        $this->assertTrue(AtendimentoRegras::statusBloqueiaAtendimento('Cancelado'));

        $this->assertFalse(AtendimentoRegras::statusBloqueiaAtendimento('Aberto'));
        $this->assertFalse(AtendimentoRegras::statusBloqueiaAtendimento('Em Andamento'));
        $this->assertFalse(AtendimentoRegras::statusBloqueiaAtendimento('Aprovado'));
        $this->assertFalse(AtendimentoRegras::statusBloqueiaAtendimento('Aguardando Peças'));
        $this->assertFalse(AtendimentoRegras::statusBloqueiaAtendimento(null));
    }

    public function testStatusPermiteIniciarAtendimento(): void
    {
        $this->assertTrue(AtendimentoRegras::statusPermiteIniciarAtendimento('Aberto'));
        $this->assertTrue(AtendimentoRegras::statusPermiteIniciarAtendimento('Em Andamento'));

        // Reabertura feita pelo administrativo: o tecnico volta a poder atender.
        $this->assertTrue(AtendimentoRegras::statusPermiteIniciarAtendimento('Aprovado'));

        // Estados que nao liberam atendimento de campo.
        $this->assertFalse(AtendimentoRegras::statusPermiteIniciarAtendimento('Finalizado'));
        $this->assertFalse(AtendimentoRegras::statusPermiteIniciarAtendimento('Faturado'));
        $this->assertFalse(AtendimentoRegras::statusPermiteIniciarAtendimento('Cancelado'));
        $this->assertFalse(AtendimentoRegras::statusPermiteIniciarAtendimento('Orçamento'));
        $this->assertFalse(AtendimentoRegras::statusPermiteIniciarAtendimento('Aguardando Peças'));
        $this->assertFalse(AtendimentoRegras::statusPermiteIniciarAtendimento(null));
    }

    public function testAssinaturaAntigaNaoBloqueiaOsReaberta(): void
    {
        // OS comum: assinatura coletada encerra o fluxo de inicio.
        $this->assertTrue(AtendimentoRegras::assinaturaBloqueiaInicio('Aberto', true));
        $this->assertFalse(AtendimentoRegras::assinaturaBloqueiaInicio('Aberto', false));

        // OS reaberta: as assinaturas antigas ficam no historico e nao bloqueiam.
        $this->assertFalse(AtendimentoRegras::assinaturaBloqueiaInicio('Aprovado', true));
    }

    public function testStatusReabreAtendimento(): void
    {
        $this->assertTrue(AtendimentoRegras::statusReabreAtendimento('Aprovado'));
        $this->assertFalse(AtendimentoRegras::statusReabreAtendimento('Aberto'));
        $this->assertFalse(AtendimentoRegras::statusReabreAtendimento(null));
    }
}
