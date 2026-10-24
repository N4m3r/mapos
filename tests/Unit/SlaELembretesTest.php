<?php

use PHPUnit\Framework\TestCase;

require_once BASEPATH . 'core/Model.php';
require_once APPPATH . 'models/Sla_model.php';
require_once APPPATH . 'libraries/Lembretes_cliente.php';

/**
 * Painel de SLA (classificacao dos prazos) e texto dos lembretes ao cliente.
 */
class SlaELembretesTest extends TestCase
{
    private const AGORA = 1_800_000_000;

    public function testSituacaoDoPrazo(): void
    {
        $data = static fn (int $segundos) => date('Y-m-d H:i:s', self::AGORA + $segundos);

        $this->assertNull(Sla_model::situacao(null, null, self::AGORA));
        $this->assertSame('estourado', Sla_model::situacao($data(-60), null, self::AGORA));
        $this->assertSame('risco', Sla_model::situacao($data(3 * 3600), null, self::AGORA));
        $this->assertSame('no_prazo', Sla_model::situacao($data(5 * 3600), null, self::AGORA));
        $this->assertSame('cumprido', Sla_model::situacao($data(-60), $data(-120), self::AGORA));
        $this->assertSame('atrasado', Sla_model::situacao($data(-120), $data(-60), self::AGORA));
    }

    public function testSituacaoGeralEhAPiorEntreRespostaESolucao(): void
    {
        $data = static fn (int $segundos) => date('Y-m-d H:i:s', self::AGORA + $segundos);
        $os = (object) [
            'sla_resposta_prazo' => $data(-7200), 'sla_resposta_em' => $data(-9000),   // cumprida
            'sla_solucao_prazo' => $data(3600), 'sla_solucao_em' => null,               // vence em 1h
        ];
        $this->assertSame('risco', Sla_model::situacaoGeral($os, self::AGORA));

        $os->sla_resposta_em = null;                                                    // resposta vencida
        $this->assertSame('estourado', Sla_model::situacaoGeral($os, self::AGORA));
    }

    public function testMensagensDosLembretes(): void
    {
        $GLOBALS['CI'] = new stdClass();
        $lembretes = new Lembretes_cliente();
        $emitente = (object) ['nome' => 'Assistência Exemplo', 'telefone' => '(11) 3333-4444'];
        $os = (object) [
            'idOs' => 42, 'nomeCliente' => 'Maria da Silva', 'data_agendamento' => '2026-10-25 14:30:00',
            'rua' => 'Rua A', 'numero' => '10', 'bairro' => 'Centro', 'cidade' => 'São Paulo',
            'aprovacao_token' => null, 'aprovacao_status' => null,
        ];

        $visita = $lembretes->mensagem('visita', $os, $emitente);
        $this->assertStringContainsString('Olá, Maria!', $visita);
        $this->assertStringContainsString('OS #42 está agendado para amanhã, 25/10/2026 às 14:30', $visita);
        $this->assertStringContainsString('Rua A, 10, Centro, São Paulo', $visita);
        $this->assertStringContainsString('(11) 3333-4444', $visita);
        $this->assertStringEndsWith('Assistência Exemplo', $visita);

        $orcamento = $lembretes->mensagem('orcamento', $os, $emitente);
        $this->assertStringContainsString('orçamento da OS #42 está aguardando', $orcamento);
        $this->assertStringContainsString('Responda esta mensagem', $orcamento, 'sem link de aprovacao valido');

        $this->assertStringContainsString('pronto para retirada', $lembretes->mensagem('retirada', $os, $emitente));
    }
}
