<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

require_once APPPATH . 'helpers/automacao_regras_helper.php';

/**
 * Regras puras da automação de faturamento.
 *
 * O cálculo de "próxima ocorrência do dia N" e a decisão de segurar a emissão
 * são justamente o que erra em fim de mês, então valem teste sem banco.
 */
class AutomacaoRegrasTest extends TestCase
{
    public function testDiaFaturamentoNormaliza(): void
    {
        $this->assertSame(1, AutomacaoRegras::diaFaturamento(''));
        $this->assertSame(1, AutomacaoRegras::diaFaturamento(null));
        $this->assertSame(1, AutomacaoRegras::diaFaturamento(0));
        $this->assertSame(1, AutomacaoRegras::diaFaturamento(-3));
        $this->assertSame(1, AutomacaoRegras::diaFaturamento(29));
        $this->assertSame(1, AutomacaoRegras::diaFaturamento(31));
        $this->assertSame(5, AutomacaoRegras::diaFaturamento('5'));
        $this->assertSame(28, AutomacaoRegras::diaFaturamento(28));
    }

    public function testProximaDataNoMesCorrenteQuandoODiaAindaNaoChegou(): void
    {
        $this->assertSame(
            '2026-11-05',
            AutomacaoRegras::proximaDataFaturamento(5, '2026-11-01')
        );
    }

    public function testProximaDataViraMesSeguinteQuandoODiaJaPassou(): void
    {
        $this->assertSame(
            '2026-12-05',
            AutomacaoRegras::proximaDataFaturamento(5, '2026-11-20')
        );
    }

    public function testProximaDataNoProprioDiaPulaParaOMesSeguinte(): void
    {
        // No dia do lote, a emissão sai na hora; a "próxima" já é a do mês que vem.
        $this->assertSame(
            '2026-12-05',
            AutomacaoRegras::proximaDataFaturamento(5, '2026-11-05')
        );
    }

    public function testProximaDataEmFevereiroComDia28(): void
    {
        // 28 existe em fevereiro; é o limite que o sistema aceita de propósito.
        $this->assertSame(
            '2026-02-28',
            AutomacaoRegras::proximaDataFaturamento(28, '2026-02-10')
        );
    }

    public function testProximaDataCruzaAno(): void
    {
        $this->assertSame(
            '2027-01-05',
            AutomacaoRegras::proximaDataFaturamento(5, '2026-12-20')
        );
    }

    public function testProximaDataAceitaDataHora(): void
    {
        $this->assertSame(
            '2026-11-05',
            AutomacaoRegras::proximaDataFaturamento(5, '2026-11-02 14:30:00')
        );
    }

    public function testDeveAgendarSoParaClienteMarcado(): void
    {
        $this->assertFalse(AutomacaoRegras::deveAgendar(null, 10, 5));
        $this->assertFalse(AutomacaoRegras::deveAgendar('', 10, 5));
        $this->assertFalse(AutomacaoRegras::deveAgendar(0, 10, 5));

        $this->assertTrue(AutomacaoRegras::deveAgendar(1, 10, 5));
        $this->assertTrue(AutomacaoRegras::deveAgendar('1', 10, 5));
    }

    public function testNoDiaDoLoteNaoSegura(): void
    {
        $this->assertFalse(AutomacaoRegras::deveAgendar(1, 5, 5));
        // Dia inválido cai no padrão 1: no dia 1 não segura.
        $this->assertFalse(AutomacaoRegras::deveAgendar(1, 1, 0));
    }

    public function testSituacaoDoItem(): void
    {
        $hoje = '2026-11-07';

        $this->assertSame('erro', AutomacaoRegras::situacaoItem('erro', '2026-11-01', $hoje));
        $this->assertSame('vencido', AutomacaoRegras::situacaoItem('aguardando', '2026-11-01', $hoje));
        $this->assertSame('vencido', AutomacaoRegras::situacaoItem('aguardando', $hoje, $hoje), 'vence hoje conta como vencido');
        $this->assertSame('futuro', AutomacaoRegras::situacaoItem('aguardando', '2026-12-01', $hoje));
        $this->assertSame('resolvido', AutomacaoRegras::situacaoItem('processado', '2026-11-01', $hoje));
        $this->assertSame('resolvido', AutomacaoRegras::situacaoItem('cancelado', '2026-11-01', $hoje));
    }

    public function testSituacaoComDataAcompanhaHora(): void
    {
        $this->assertSame('vencido', AutomacaoRegras::situacaoItem('aguardando', '2026-11-01 00:00:00', '2026-11-07'));
    }

    public function testPermissoesDoItem(): void
    {
        $this->assertTrue(AutomacaoRegras::podeCancelar('aguardando'));
        $this->assertTrue(AutomacaoRegras::podeCancelar('erro'));
        $this->assertFalse(AutomacaoRegras::podeCancelar('processado'));
        $this->assertFalse(AutomacaoRegras::podeCancelar('cancelado'));

        $this->assertTrue(AutomacaoRegras::podeRetentar('erro'));
        $this->assertFalse(AutomacaoRegras::podeRetentar('aguardando'));
        $this->assertFalse(AutomacaoRegras::podeRetentar('processado'));
    }

    public function testTentativasRestantes(): void
    {
        $this->assertSame(5, AutomacaoRegras::tentativasRestantes(0));
        $this->assertSame(1, AutomacaoRegras::tentativasRestantes(4));
        $this->assertSame(0, AutomacaoRegras::tentativasRestantes(5));
        $this->assertSame(0, AutomacaoRegras::tentativasRestantes(9), 'nunca fica negativo');
    }

    public function testResumoDaFila(): void
    {
        $hoje = '2026-11-07';
        $itens = [
            (object) ['status' => 'aguardando', 'data_agendada' => '2026-11-01'],
            (object) ['status' => 'aguardando', 'data_agendada' => '2026-11-07'],
            (object) ['status' => 'aguardando', 'data_agendada' => '2026-12-01'],
            (object) ['status' => 'erro', 'data_agendada' => '2026-11-03'],
        ];

        $r = AutomacaoRegras::resumoFila($itens, $hoje);

        $this->assertSame(4, $r['total']);
        $this->assertSame(2, $r['vencidos']);
        $this->assertSame(1, $r['futuros']);
        $this->assertSame(3, $r['aguardando'], 'em erro nao entra em aguardando');
        $this->assertSame(1, $r['erro']);
    }

    public function testResumoDaFilaAceitaArrayEVazio(): void
    {
        $vazio = AutomacaoRegras::resumoFila([], '2026-11-07');
        $this->assertSame(0, $vazio['total']);
        $this->assertSame(0, $vazio['vencidos']);

        $r = AutomacaoRegras::resumoFila([['status' => 'aguardando', 'data_agendada' => '2026-10-01']], '2026-11-07');
        $this->assertSame(1, $r['vencidos']);
    }
}
