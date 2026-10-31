<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/avisos_atendimento_helper.php';

class AvisosAtendimentoTest extends TestCase
{
    public function testJanelaPorEvento(): void
    {
        $this->assertSame(30, AvisosAtendimento::janelaMinutos('os_tecnico_a_caminho'));
        $this->assertSame(30, AvisosAtendimento::janelaMinutos('os_tecnico_chegou'));
        $this->assertSame(5, AvisosAtendimento::janelaMinutos('os_reagendada'));
        $this->assertSame(5, AvisosAtendimento::janelaMinutos('os_nao_realizada'));
        $this->assertSame(5, AvisosAtendimento::janelaMinutos('os_tecnico_definido'));
    }

    public function testAntiRepeticao(): void
    {
        $agora = 1_800_000_000;
        $this->assertFalse(AvisosAtendimento::repetido(null, $agora, 'os_tecnico_a_caminho'));
        $this->assertTrue(AvisosAtendimento::repetido($agora - 29 * 60, $agora, 'os_tecnico_a_caminho'));
        $this->assertFalse(AvisosAtendimento::repetido($agora - 30 * 60, $agora, 'os_tecnico_a_caminho'));
        $this->assertTrue(AvisosAtendimento::repetido($agora - 4 * 60, $agora, 'os_reagendada'));
        $this->assertFalse(AvisosAtendimento::repetido($agora - 6 * 60, $agora, 'os_reagendada'));
    }

    public function testFormatarAgendamento(): void
    {
        $this->assertSame('05/11/2026 às 14:30', AvisosAtendimento::formatarAgendamento('2026-11-05 14:30:00'));
        $this->assertSame('', AvisosAtendimento::formatarAgendamento(null));
        $this->assertSame('', AvisosAtendimento::formatarAgendamento(''));
        $this->assertSame('', AvisosAtendimento::formatarAgendamento('0000-00-00 00:00:00'));
    }

    public function testPrimeiroNome(): void
    {
        $this->assertSame('Carlos', AvisosAtendimento::primeiroNome('  Carlos  Alberto Silva '));
        $this->assertSame('Ana', AvisosAtendimento::primeiroNome('Ana'));
        $this->assertSame('', AvisosAtendimento::primeiroNome(null));
    }

    public function testNaoAvisaChegadaAtrasada(): void
    {
        $agora = strtotime('2026-11-05 10:00:00');
        $this->assertTrue(AvisosAtendimento::deveAvisarChegada('', $agora));
        $this->assertTrue(AvisosAtendimento::deveAvisarChegada('2026-11-05 09:45:00', $agora));
        $this->assertTrue(AvisosAtendimento::deveAvisarChegada('2026-11-05 09:30:00', $agora));
        $this->assertFalse(AvisosAtendimento::deveAvisarChegada('2026-11-05 09:29:00', $agora));
        $this->assertFalse(AvisosAtendimento::deveAvisarChegada('2026-11-04 10:00:00', $agora));
    }

    public function testMudouTecnicoOuData(): void
    {
        $this->assertFalse(AvisosAtendimento::mudouTecnicoOuData(3, '2026-11-05 08:00:00', '3', '2026-11-05T08:00'));
        $this->assertTrue(AvisosAtendimento::mudouTecnicoOuData(3, '2026-11-05 08:00:00', 4, '2026-11-05 08:00:00'));
        $this->assertTrue(AvisosAtendimento::mudouTecnicoOuData(3, '2026-11-05 08:00:00', 3, '2026-11-06 08:00:00'));
        $this->assertTrue(AvisosAtendimento::mudouTecnicoOuData(null, null, 3, null));
        $this->assertFalse(AvisosAtendimento::mudouTecnicoOuData(null, '0000-00-00 00:00:00', 0, ''));
    }

    public function testTelefoneSoServeSeForCelular(): void
    {
        require_once BASEPATH . 'core/Model.php';
        require_once APPPATH . 'models/Os_model.php';
        $this->assertTrue(Os_model::pareceCelular('(92)99215-0107'));
        $this->assertTrue(Os_model::pareceCelular('+55 68 99988-7766'));
        $this->assertFalse(Os_model::pareceCelular('3499990000'));
        $this->assertFalse(Os_model::pareceCelular('(68) 3224-1000'));
        $this->assertFalse(Os_model::pareceCelular(''));
    }
}
