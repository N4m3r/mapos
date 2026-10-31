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
}
