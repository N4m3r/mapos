<?php

use PHPUnit\Framework\TestCase;

require_once BASEPATH . 'core/Model.php';
require_once APPPATH . 'models/Relatorio_cobranca_model.php';

/**
 * Classificacao das situacoes do relatorio de cobranca (ADR-011).
 */
class RelatorioCobrancaClassificarTest extends TestCase
{
    private const HOJE = '2026-10-25';

    private function fatos(array $sobrescrever = []): array
    {
        return array_merge([
            'origem' => 'os',
            'status' => '',
            'faturado' => false,
            'tem_lancamento' => false,
            'lancamento_pago' => false,
            'data_pagamento' => null,
            'tem_cobranca_aberta' => false,
            'cobranca_paga' => false,
            'vencimento' => null,
            'data_ref' => '2026-10-10',
        ], $sobrescrever);
    }

    private function situacao(array $sobrescrever): array
    {
        return Relatorio_cobranca_model::classificar($this->fatos($sobrescrever), self::HOJE);
    }

    public function testLancamentoPagoEhFaturado(): void
    {
        $r = $this->situacao(['status' => 'Faturado', 'faturado' => true, 'tem_lancamento' => true, 'lancamento_pago' => true, 'data_pagamento' => '2026-10-12']);
        $this->assertSame('faturado', $r['situacao']);
        $this->assertIsString($r['detalhe']);
    }

    public function testCobrancaPagaEhFaturado(): void
    {
        $r = $this->situacao(['status' => 'Finalizado', 'cobranca_paga' => true]);
        $this->assertSame('faturado', $r['situacao']);
    }

    public function testPagoVenceOStatusDaOs(): void
    {
        foreach (['Faturado', 'Finalizado', 'Em Andamento'] as $status) {
            $r = $this->situacao(['status' => $status, 'faturado' => true, 'tem_lancamento' => true, 'lancamento_pago' => true, 'vencimento' => '2026-10-01']);
            $this->assertSame('faturado', $r['situacao'], $status);
        }
    }

    public function testLancamentoVencidoEhPendenteComDias(): void
    {
        $r = $this->situacao(['status' => 'Faturado', 'tem_lancamento' => true, 'vencimento' => '2026-10-20']);
        $this->assertSame('pendente', $r['situacao']);
        $this->assertStringContainsString('Vencido há 5 dias', $r['detalhe']);
    }

    public function testLancamentoAVencerEhPendenteComData(): void
    {
        $r = $this->situacao(['status' => 'Faturado', 'tem_lancamento' => true, 'vencimento' => '2026-10-28']);
        $this->assertSame('pendente', $r['situacao']);
        $this->assertStringContainsString('Vence 28/10', $r['detalhe']);
    }

    public function testCobrancaAbertaEhPendente(): void
    {
        $r = $this->situacao(['status' => 'Finalizado', 'tem_cobranca_aberta' => true, 'vencimento' => '2026-11-05']);
        $this->assertSame('pendente', $r['situacao']);
    }

    public function testOsFinalizadaSemLancamentoNaoCobrada(): void
    {
        $r = $this->situacao(['status' => 'Finalizado']);
        $this->assertSame('nao_cobrado', $r['situacao']);
    }

    public function testOsNaoRealizadaECanceladaSaoNaoRealizado(): void
    {
        foreach (['Não Realizado', 'Cancelado'] as $status) {
            $r = $this->situacao(['status' => $status]);
            $this->assertSame('nao_realizado', $r['situacao'], $status);
        }
    }

    public function testOsEmAbertoSeraRealizada(): void
    {
        foreach (['Em Andamento', 'Aberto', 'Orçamento', 'Aprovado'] as $status) {
            $r = $this->situacao(['status' => $status]);
            $this->assertSame('a_realizar', $r['situacao'], $status);
        }
    }

    public function testContratoVencidoSemLancamentoNaoCobrado(): void
    {
        foreach (['2026-10-25', '2026-10-05'] as $dataRef) {
            $r = $this->situacao(['origem' => 'contrato', 'data_ref' => $dataRef]);
            $this->assertSame('nao_cobrado', $r['situacao'], $dataRef);
        }
    }

    public function testContratoFuturoSeraRealizado(): void
    {
        $r = $this->situacao(['origem' => 'contrato', 'data_ref' => '2026-10-28']);
        $this->assertSame('a_realizar', $r['situacao']);
    }

    public function testVendaCanceladaNaoRealizada(): void
    {
        $r = $this->situacao(['origem' => 'venda', 'status' => 'Cancelado']);
        $this->assertSame('nao_realizado', $r['situacao']);
    }

    public function testVendaConcluidaSemLancamentoNaoCobrada(): void
    {
        $r = $this->situacao(['origem' => 'venda', 'status' => '']);
        $this->assertSame('nao_cobrado', $r['situacao']);
    }
}
