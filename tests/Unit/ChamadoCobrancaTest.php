<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once APPPATH . 'helpers/chamado_cobranca_helper.php';

/**
 * Regras do painel de cobranca dos chamados: situacoes, KPIs, totais,
 * selecao multipla e normalizacao do numero do chamado.
 */
class ChamadoCobrancaTest extends TestCase
{
    private static function item(string $status, float $valor, string $cliente = 'Cliente A'): array
    {
        return ['status' => $status, 'valor' => $valor, 'cliente' => $cliente];
    }

    public function testCatalogoNaOrdemEsperada(): void
    {
        $this->assertSame(
            ['pendente_aprovacao', 'a_cobrar', 'pendente', 'vencida', 'paga', 'cancelada'],
            array_keys(Chamado_cobranca::STATUS)
        );
        $this->assertSame('Pendente de aprovação', Chamado_cobranca::rotulo('pendente_aprovacao'));
        $this->assertSame('perigo', Chamado_cobranca::classe('vencida'));
        $this->assertSame('sucesso', Chamado_cobranca::classe('paga'));
        $this->assertSame('neutro', Chamado_cobranca::classe('situacao-desconhecida'));
        $this->assertSame('bx-receipt', Chamado_cobranca::icone('situacao-desconhecida'));
    }

    public function testFiltroMantemSoSituacoesValidasENaOrdemDoCatalogo(): void
    {
        $this->assertSame(
            ['pendente_aprovacao', 'vencida', 'paga'],
            Chamado_cobranca::filtro(['paga', 'inventada', 'vencida', 'pendente_aprovacao'])
        );
        $this->assertSame([], Chamado_cobranca::filtro([]));
        $this->assertSame([], Chamado_cobranca::filtro(['nada']));
    }

    public function testKpisSomaQuantidadeEValorPorSituacao(): void
    {
        $k = Chamado_cobranca::kpis([
            self::item('vencida', 100.5),
            self::item('vencida', 49.5),
            self::item('a_cobrar', 300),
            self::item('desconhecida', 999),
            self::item('paga', 0),
        ]);

        $this->assertSame(['qtd' => 2, 'valor' => 150.0], $k['vencida']);
        $this->assertSame(['qtd' => 1, 'valor' => 300.0], $k['a_cobrar']);
        $this->assertSame(['qtd' => 1, 'valor' => 0.0], $k['paga']);
        $this->assertSame(['qtd' => 0, 'valor' => 0.0], $k['pendente_aprovacao']);
    }

    public function testTotalEmAbertoSomaSoOQueAindaNaoFoiRecebido(): void
    {
        $itens = [
            self::item('pendente_aprovacao', 100),
            self::item('a_cobrar', 200),
            self::item('pendente', 300),
            self::item('vencida', 400),
            self::item('paga', 5000),
            self::item('cancelada', 6000),
        ];

        $this->assertSame(1000.0, Chamado_cobranca::totalEmAberto($itens));
        $this->assertSame(0.0, Chamado_cobranca::totalEmAberto([]));
    }

    public function testPontosStatusSoTrazSituacoesComItens(): void
    {
        $pontos = Chamado_cobranca::pontosStatus([
            self::item('vencida', 250),
            self::item('paga', 10),
        ]);

        $this->assertSame(['Vencida', 'Paga'], array_column($pontos, 'rotulo'));
        $this->assertSame([250.0, 10.0], array_column($pontos, 'valor'));
        $this->assertStringContainsString('Vencida', $pontos[0]['dica']);
        $this->assertStringContainsString('250,00', $pontos[0]['dica']);
    }

    public function testTopClientesSomaAbertoEDescartaPagos(): void
    {
        $pontos = Chamado_cobranca::topClientes([
            self::item('vencida', 100, 'Beta'),
            self::item('a_cobrar', 150, 'Alfa'),
            self::item('pendente', 50, 'Beta'),
            self::item('paga', 9000, 'Gama'),
        ], 2);

        $this->assertSame(['Beta', 'Alfa'], array_column($pontos, 'rotulo'));
        $this->assertSame([150.0, 150.0], array_column($pontos, 'valor'));
    }

    public function testTopClientesUsaRotuloQuandoFaltaNome(): void
    {
        $pontos = Chamado_cobranca::topClientes([self::item('vencida', 10, '')]);

        $this->assertSame('Sem cliente', $pontos[0]['rotulo']);
    }

    public static function selecoes(): array
    {
        return [
            'os e cobranca' => [['os:12', 'cob:8'], ['os:12', 'cob:8']],
            'descarta invalidos' => [['os:12', 'xx:1', 'os:', 'cob:abc', 12], ['os:12']],
            'remove duplicados' => [['os:12', 'os:12', 'cob:8'], ['os:12', 'cob:8']],
            'vazio' => [[], []],
        ];
    }

    #[DataProvider('selecoes')]
    public function testLerSelecaoAceitaSoOsFormatosConhecidos(array $brutos, array $esperado): void
    {
        $this->assertSame($esperado, Chamado_cobranca::lerSelecao($brutos));
    }

    public function testNormalizaChamado(): void
    {
        $this->assertSame('CH-2026/0091', chamado_normalizar('  CH-2026/0091  '));
        $this->assertSame('a b', chamado_normalizar("a\t\t b"));
        $this->assertNull(chamado_normalizar(''));
        $this->assertNull(chamado_normalizar('   '));
        $this->assertNull(chamado_normalizar(null));
        $this->assertSame('ab', chamado_normalizar('<b>a</b>b'));
        $this->assertSame('123456', chamado_normalizar('123456789', 6), 'respeita o limite da coluna');
    }

    public function testMoedaEmPtBr(): void
    {
        $this->assertSame('R$ 1.234,50', Chamado_cobranca::moeda(1234.5));
        $this->assertSame('R$ 0,00', Chamado_cobranca::moeda(0));
    }    public function testDigitosIgnoramPontuacao(): void
    {
        $this->assertSame('12345678000199', chamado_digitos('12.345.678/0001-99'));
        $this->assertSame('12345678909', chamado_digitos('123.456.789-09'));
        $this->assertSame('', chamado_digitos(''));
        $this->assertSame('', chamado_digitos(null));
    }

    public function testDocumentoConfereIgnorandoMascara(): void
    {
        $this->assertTrue(chamado_documento_confere('12.345.678/0001-99', '12345678000199'));
        $this->assertTrue(chamado_documento_confere('12345678000199', '12.345.678/0001-99'));
        $this->assertFalse(chamado_documento_confere('11122233344', '12345678000199'));
    }

    public function testDocumentoVazioNuncaConfere(): void
    {
        // Sem documento dos dois lados nao ha prova de identidade: a pagina
        // publica nao pode casar chamado com qualquer pessoa.
        $this->assertFalse(chamado_documento_confere('', '12345678000199'));
        $this->assertFalse(chamado_documento_confere('12345678000199', ''));
        $this->assertFalse(chamado_documento_confere('', ''));
        $this->assertFalse(chamado_documento_confere('   ', '   '));
        $this->assertFalse(chamado_documento_confere(null, null));
    }
}
