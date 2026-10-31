<?php

use PHPUnit\Framework\TestCase;

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once APPPATH . 'helpers/tecnico_inicio_helper.php';

class TecnicoInicioTest extends TestCase
{
    private function os($status, $data, $extra = [])
    {
        return (object) array_merge(['idOs' => 1, 'status' => $status, 'dataInicial' => $data, 'nomeCliente' => 'Ana'], $extra);
    }

    public function testClassificar(): void
    {
        $h = '2026-10-31';
        $this->assertSame('hoje', TecnicoInicio::classificar($this->os('Aberto', '2026-10-31'), $h));
        $this->assertSame('proximos', TecnicoInicio::classificar($this->os('Aberto', '2026-11-02'), $h));
        $this->assertSame('atrasadas', TecnicoInicio::classificar($this->os('Aberto', '2026-10-30'), $h));
        $this->assertSame('concluidas', TecnicoInicio::classificar($this->os('Finalizado', '2026-10-01'), $h));
        $this->assertSame('hoje', TecnicoInicio::classificar($this->os('Em Andamento', '2026-10-01'), $h));
        $this->assertSame('atrasadas', TecnicoInicio::classificar($this->os('Não Realizado', '2026-10-31'), $h));
        $this->assertNull(TecnicoInicio::classificar($this->os('Cancelado', '2026-10-31'), $h));
    }

    public function testDataAgendamentoTemPrioridade(): void
    {
        $o = $this->os('Aberto', '2026-10-20', ['data_agendamento' => '2026-10-31 14:30:00']);
        $this->assertSame('hoje', TecnicoInicio::classificar($o, '2026-10-31'));
        $this->assertSame('14:30', TecnicoInicio::horaVisita($o));
        $this->assertNull(TecnicoInicio::horaVisita($this->os('Aberto', '2026-10-31')));
        $this->assertSame('2026-10-20', TecnicoInicio::dataEfetiva($this->os('Aberto', '2026-10-20', ['data_agendamento' => '0000-00-00 00:00:00'])));
    }

    public function testRotulosSimples(): void
    {
        $this->assertSame('Agendada', TecnicoInicio::rotuloStatus('Aberto')[0]);
        $this->assertSame('Atrasada', TecnicoInicio::rotuloStatus('Aberto', true)[0]);
        $this->assertSame('Em atendimento', TecnicoInicio::rotuloStatus('Em Andamento')[0]);
        $this->assertSame('Concluída', TecnicoInicio::rotuloStatus('Faturado')[0]);
        $this->assertSame('Não deu para fazer', TecnicoInicio::rotuloStatus('Não Realizado')[0]);
    }

    public function testProximaVisita(): void
    {
        $lista = [
            $this->os('Aberto', '2026-10-31 09:00:00', ['idOs' => 1]),
            $this->os('Aberto', '2026-10-31 15:00:00', ['idOs' => 2]),
            $this->os('Aberto', '2026-11-03 08:00:00', ['idOs' => 3]),
        ];
        $p = TecnicoInicio::proximaVisita($lista, '2026-10-31 10:00:00');
        $this->assertSame(2, $p['os']->idOs);
        $this->assertFalse($p['continuar']);
        $lista[] = $this->os('Em Andamento', '2026-10-30', ['idOs' => 9]);
        $p = TecnicoInicio::proximaVisita($lista, '2026-10-31 10:00:00');
        $this->assertSame(9, $p['os']->idOs);
        $this->assertTrue($p['continuar']);
        $this->assertSame(1, TecnicoInicio::proximaVisita([$this->os('Aberto', '2026-10-31 09:00:00')], '2026-10-31 18:00:00')['os']->idOs);
        $this->assertNull(TecnicoInicio::proximaVisita([], '2026-10-31 10:00:00'));
    }

    public function testEnderecoERota(): void
    {
        $o = $this->os('Aberto', '2026-10-31', ['rua' => 'Rua A', 'numero' => '10', 'bairro' => 'Centro', 'cidade' => 'Belém', 'estado' => 'PA']);
        $this->assertSame('Rua A, 10 - Centro', TecnicoInicio::enderecoCurto($o));
        $this->assertStringStartsWith('https://www.google.com/maps/dir/?api=1&destination=', TecnicoInicio::urlRota($o));
        $this->assertSame('', TecnicoInicio::urlRota($this->os('Aberto', '2026-10-31')));
        $this->assertSame('5591988887777', TecnicoInicio::foneWhats('(91) 98888-7777'));
    }

    public function testAgruparOrdena(): void
    {
        $g = TecnicoInicio::agrupar([
            $this->os('Aberto', '2026-10-31 15:00:00', ['idOs' => 2]),
            $this->os('Aberto', '2026-10-31 09:00:00', ['idOs' => 1]),
            $this->os('Finalizado', '2026-10-29', ['idOs' => 5]),
            $this->os('Finalizado', '2026-10-30', ['idOs' => 6]),
        ], '2026-10-31');
        $this->assertSame([1, 2], array_column($g['hoje'], 'idOs'));
        $this->assertSame([6, 5], array_column($g['concluidas'], 'idOs'));
    }
}
