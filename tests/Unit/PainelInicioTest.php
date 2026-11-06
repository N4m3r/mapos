<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once APPPATH . 'helpers/painel_inicio_helper.php';

/**
 * Logica pura do painel inicial: tons/rotulos de status, prazos e series.
 */
class PainelInicioTest extends TestCase
{
    public function testTomEIconePorStatus(): void
    {
        $this->assertSame('alerta', Painel_inicio::tom('Aguardando Peças'));
        $this->assertSame('perigo', Painel_inicio::tom('Cancelado'));
        $this->assertSame('neutro', Painel_inicio::tom('Status Desconhecido'));
        $this->assertSame('bx-file', Painel_inicio::icone('Status Desconhecido'));
        $this->assertSame('bx-check-double', Painel_inicio::icone('Finalizado'));
    }

    public function testResumoStatusOrdenaEZeraOsAusentes(): void
    {
        $r = Painel_inicio::resumoStatus(['Cancelado' => 2, 'Em Andamento' => 5, 'Legado' => 1]);
        $this->assertSame('Aberto', $r[0]['status']);
        $this->assertSame(0, $r[0]['total']);
        $status = array_column($r, 'status');
        $this->assertSame(['Em Andamento', 'Cancelado', 'Legado'], array_values(array_intersect($status, ['Em Andamento', 'Cancelado', 'Legado'])));
        $this->assertSame('Legado', end($status), 'status fora da lista vai para o fim');
        $this->assertCount(11, $r);
    }

    public function testTotalEmAbertoSomaSomenteStatusAtivos(): void
    {
        $contagens = ['Aberto' => 2, 'Em Andamento' => 3, 'Aguardando Peças' => 1, 'Finalizado' => 9, 'Cancelado' => 4];
        $this->assertSame(6, Painel_inicio::totalEmAberto($contagens));
        $this->assertSame(0, Painel_inicio::totalEmAberto([]));
    }

    public function testPrazo(): void
    {
        $hoje = '2026-11-05';
        $this->assertSame(['Sem data', ''], Painel_inicio::prazo(null, $hoje));
        $this->assertSame(['Sem data', ''], Painel_inicio::prazo('0000-00-00', $hoje));
        $this->assertSame(['Hoje', 'alerta'], Painel_inicio::prazo('2026-11-05', $hoje));
        $this->assertSame(['Hoje 14:30', 'alerta'], Painel_inicio::prazo('2026-11-05 14:30:00', $hoje));
        $this->assertSame(['Amanhã', 'alerta'], Painel_inicio::prazo('2026-11-06', $hoje));
        $this->assertSame(['Atrasado 2 dias · 03/11', 'perigo'], Painel_inicio::prazo('2026-11-03', $hoje));
        $this->assertSame(['Atrasado 1 dia · 04/11', 'perigo'], Painel_inicio::prazo('2026-11-04', $hoje));
        $this->assertSame(['10/11', 'alerta'], Painel_inicio::prazo('2026-11-10', $hoje));
        $this->assertSame(['30/11', ''], Painel_inicio::prazo('2026-11-30', $hoje));
    }

    public function testMoedaENumero(): void
    {
        $this->assertSame('R$ 1.234,50', Painel_inicio::moeda(1234.5));
        $this->assertSame('R$ 0,00', Painel_inicio::moeda(null));
        $this->assertSame('1.234', Painel_inicio::numero(1234.4));
        $this->assertSame('12,5', Painel_inicio::numero(12.46, 1));
    }

    public function testSerieDiasPreencheBuracoComZero(): void
    {
        $pontos = Painel_inicio::serieDias([
            ['dia' => '2026-11-05', 'os' => 3, 'vendas' => 1],
            ['dia' => '2026-11-03', 'os' => 7, 'vendas' => 0],
        ], '2026-11-05');

        $this->assertCount(14, $pontos);
        $this->assertSame(['rotulo', 'valor', 'dica'], array_keys($pontos[0]));
        $this->assertSame(7, $pontos[11]['valor'], '2026-11-03 fica no indice 11');
        $this->assertSame(3, $pontos[13]['valor']);
        $this->assertSame(0, $pontos[12]['valor'], 'dia sem movimento entra com zero');
        $this->assertStringContainsString('1 vendas', $pontos[13]['dica']);
    }

    public function testPontosStatusIgnoraZero(): void
    {
        $pontos = Painel_inicio::pontosStatus(['Aberto' => 0, 'Em Andamento' => 4, 'Finalizado' => 2]);
        $this->assertCount(2, $pontos);
        $this->assertSame('Em Andamento', $pontos[0]['rotulo']);
        $this->assertSame('Finalizado', $pontos[1]['rotulo']);
        $this->assertSame([], Painel_inicio::pontosStatus([]));
    }

    public function testValorLancamentoAplicaDesconto(): void
    {
        $this->assertSame(100.0, Painel_inicio::valorLancamento((object) ['valor' => 100, 'desconto' => 0, 'tipo_desconto' => null]));
        $this->assertSame(90.0, Painel_inicio::valorLancamento((object) ['valor' => 100, 'desconto' => 10, 'tipo_desconto' => 'real']));
        $this->assertSame(85.0, Painel_inicio::valorLancamento((object) ['valor' => 100, 'desconto' => 15, 'tipo_desconto' => 'porcento']));
        $this->assertSame(0.0, Painel_inicio::valorLancamento((object) ['valor' => 100, 'desconto' => 500, 'tipo_desconto' => 'real']), 'desconto maior que o valor nao fica negativo');
    }

    public function testIniciais(): void
    {
        $this->assertSame('MS', Painel_inicio::iniciais('maria da Silva'));
        $this->assertSame('J', Painel_inicio::iniciais(' João '));
        $this->assertSame('?', Painel_inicio::iniciais(''));
        $this->assertSame('ÁC', Painel_inicio::iniciais('Átila Cardoso'));
    }
}
