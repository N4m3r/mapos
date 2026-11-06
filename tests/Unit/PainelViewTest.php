<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

require_once APPPATH . 'helpers/painel_inicio_helper.php';

if (! function_exists('html_escape')) {
    function html_escape($v)
    {
        return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
if (! function_exists('base_url')) {
    function base_url($p = '')
    {
        return 'http://localhost/' . $p;
    }
}
if (! function_exists('site_url')) {
    function site_url($p = '')
    {
        return 'http://localhost/index.php/' . $p;
    }
}

/** Controller falso: so o que a view do painel inicial usa. */
class PainelViewFalso
{
    public $permission;
    public $session;

    public function __construct(array $permissoes)
    {
        $this->permission = new PainelViewPermissao($permissoes);
        $this->session = new PainelViewSessao();
    }

    public function render(array $dados)
    {
        extract($dados, EXTR_OVERWRITE);
        ob_start();
        include APPPATH . 'views/mapos/painel.php';

        return ob_get_clean();
    }
}

class PainelViewPermissao
{
    private $liberadas;

    public function __construct(array $liberadas)
    {
        $this->liberadas = $liberadas;
    }

    public function checkPermission($id, $atividade)
    {
        return in_array($atividade, $this->liberadas, true);
    }
}

class PainelViewSessao
{
    public function userdata($chave)
    {
        return ['permissao' => 1, 'nome_admin' => 'Maria da Silva'][$chave] ?? null;
    }
}

/**
 * Render da view do painel inicial sem banco: pega erro de PHP, variavel
 * faltando e HTML nao escapado antes do smoke test.
 */
class PainelViewTest extends TestCase
{
    private function os($id, $status, $agendamento, $tecnico)
    {
        return (object) [
            'idOs' => $id,
            'status' => $status,
            'dataInicial' => '2026-11-01',
            'dataFinal' => '2026-11-10',
            'data_agendamento' => $agendamento,
            'tecnico_responsavel' => $tecnico,
            'nomeCliente' => 'Cliente <b>X</b> & Cia',
            'celular' => '(91) 98888-7777',
            'telefone' => '',
            'nome_tecnico' => $tecnico ? 'João' : null,
        ];
    }

    private function lancamento($tipo, $valor, $descricao, $vencimento, $tipoDesconto)
    {
        return (object) [
            'idLancamentos' => 1,
            'tipo' => $tipo,
            'cliente_fornecedor' => 'Fornecedor <i>Y</i>',
            'descricao' => $descricao,
            'data_vencimento' => $vencimento,
            'valor' => $valor,
            'valor_desconto' => 0,
            'desconto' => 10,
            'tipo_desconto' => $tipoDesconto,
        ];
    }

    private function dados(): array
    {
        $meses = [];
        for ($m = 1; $m <= 12; $m++) {
            $meses[] = ['mes' => $m, 'receita' => 1000 * $m, 'despesa' => 1200 * $m, 'liquido' => -200 * $m];
        }
        $contagens = ['Aberto' => 3, 'Em Andamento' => 2, 'Finalizado' => 7, 'Cancelado' => 1, 'Legado' => 2];

        return [
            'pi_status' => Painel_inicio::resumoStatus($contagens),
            'pi_em_aberto' => Painel_inicio::totalEmAberto($contagens),
            'pi_agenda' => ['atrasadas' => 2, 'hoje' => 1],
            'pi_movimento' => ['os' => 4, 'vendas' => 2],
            'pi_os' => [
                $this->os(1, 'Aberto', '2026-11-03 09:00:00', null),
                $this->os(2, 'Aguardando Peças', null, 5),
            ],
            'pi_serie' => Painel_inicio::serieDias([['dia' => date('Y-m-d'), 'os' => 3, 'vendas' => 1]]),
            'pi_status_pontos' => Painel_inicio::pontosStatus($contagens),
            'pi_nome' => 'Maria da Silva',
            'pi_iniciais' => Painel_inicio::iniciais('Maria da Silva'),
            'pi_pendentes' => [
                'receita_vencida' => 1500.5, 'receita_avencer' => 800,
                'despesa_vencida' => 320, 'despesa_avencer' => 0, 'itens' => 9,
            ],
            'pi_cobrar' => [
                $this->lancamento('receita', 100, 'Serviço de instalação', '2026-10-01', 'porcento'),
                $this->lancamento('despesa', 50, '', '2026-09-15', null),
            ],
            'pi_mes' => ['receita' => 10000, 'despesa' => 12000, 'liquido' => -2000],
            'pi_meses' => $meses,
            'pi_ano' => 2026,
            'pi_estoque' => [
                'itens' => [(object) [
                    'idProdutos' => 1, 'descricao' => 'Cabo <b>HDMI</b>',
                    'estoque' => 1, 'estoqueMinimo' => 4, 'precoVenda' => 25,
                ]],
                'total' => 6,
            ],
            'pi_vendas_mes' => ['quantidade' => 12, 'total' => 3400.9],
            'avisoJanelaIbscbs' => [
                'rotulo' => 'Janela IBS/CBS', 'aberta' => true,
                'inicio' => '2026-11-01', 'fim' => '2026-11-30',
            ],
        ];
    }

    /** Dados de um banco recem-instalado, sem nenhum registro. */
    private function dadosVazios(): array
    {
        $meses = [];
        for ($m = 1; $m <= 12; $m++) {
            $meses[] = ['mes' => $m, 'receita' => 0.0, 'despesa' => 0.0, 'liquido' => 0.0];
        }

        return array_merge($this->dados(), [
            'pi_status' => Painel_inicio::resumoStatus([]),
            'pi_em_aberto' => 0,
            'pi_agenda' => ['atrasadas' => 0, 'hoje' => 0],
            'pi_movimento' => ['os' => 0, 'vendas' => 0],
            'pi_os' => [],
            'pi_serie' => Painel_inicio::serieDias([]),
            'pi_status_pontos' => [],
            'pi_pendentes' => [
                'receita_vencida' => 0.0, 'receita_avencer' => 0.0,
                'despesa_vencida' => 0.0, 'despesa_avencer' => 0.0, 'itens' => 0,
            ],
            'pi_cobrar' => [],
            'pi_mes' => ['receita' => 0.0, 'despesa' => 0.0, 'liquido' => 0.0],
            'pi_meses' => $meses,
            'pi_estoque' => ['itens' => [], 'total' => 0],
            'pi_vendas_mes' => ['quantidade' => 0, 'total' => 0.0],
            'avisoJanelaIbscbs' => null,
        ]);
    }

    private function render(array $permissoes, array $dados): string
    {
        return (new PainelViewFalso($permissoes))->render($dados);
    }

    private function assertSemErroPhp(string $html): void
    {
        foreach (['Fatal error', 'Parse error', 'Warning:', 'Notice:', 'Deprecated:', 'Undefined variable', 'Undefined property', 'Undefined array key'] as $marca) {
            $this->assertStringNotContainsString($marca, $html, 'A view do painel gerou: ' . $marca);
        }
        $this->assertStringNotContainsString('Cliente <b>X</b>', $html, 'Nome do cliente entrou como HTML');
        $this->assertStringNotContainsString('Cabo <b>HDMI</b>', $html, 'Descricao do produto entrou como HTML');
        $this->assertStringNotContainsString('Fornecedor <i>Y</i>', $html, 'Fornecedor entrou como HTML');
    }

    public function testAdminRecebeTodosOsBlocos(): void
    {
        $html = $this->render(
            ['vOs', 'eOs', 'aOs', 'vLancamento', 'rFinanceiro', 'vProduto', 'rProduto', 'aVenda', 'aCliente', 'vDashboard', 'rOs'],
            $this->dados()
        );

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('OS em aberto', $html);
        $this->assertStringContainsString('Cobrança atrasada', $html);
        $this->assertStringContainsString('Resultado mês a mês', $html);
        $this->assertStringContainsString('Repor estoque', $html);
        $this->assertStringContainsString('Atalhos', $html);
        $this->assertStringContainsString('<svg ', $html, 'graficos SVG nao renderizaram');
        $this->assertStringContainsString('Cliente &lt;b&gt;X&lt;/b&gt; &amp; Cia', $html);
    }

    public function testPerfilSoComOsNaoVeBlocosFinanceiros(): void
    {
        $dados = array_merge($this->dados(), [
            'pi_mes' => null, 'pi_meses' => [], 'pi_pendentes' => null,
            'pi_cobrar' => [], 'pi_estoque' => null, 'pi_vendas_mes' => null,
        ]);
        $html = $this->render(['vOs', 'aOs'], $dados);

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('OS em aberto', $html);
        $this->assertStringNotContainsString('Cobrança atrasada', $html);
        $this->assertStringNotContainsString('Resultado mês a mês', $html);
        $this->assertStringNotContainsString('Repor estoque', $html);
        $this->assertStringContainsString('Atalhos', $html);
    }

    public function testSemPermissaoExplicaEmVezDePaginaVazia(): void
    {
        $dados = array_merge($this->dados(), [
            'pi_mes' => null, 'pi_meses' => [], 'pi_pendentes' => null,
            'pi_cobrar' => [], 'pi_estoque' => null, 'pi_vendas_mes' => null, 'pi_os' => [],
        ]);
        $html = $this->render([], $dados);

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('ainda não tem acesso aos indicadores', $html);
        $this->assertStringNotContainsString('pi-grade', $html, 'grade nao deve aparecer sem conteudo');
        $this->assertStringNotContainsString('gsvg-marca', $html);
    }

    public function testBancoVazioNaoQuebra(): void
    {
        $html = $this->render(['vOs', 'vLancamento', 'rFinanceiro', 'vProduto', 'vVenda'], $this->dadosVazios());

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('Nada pendente', $html);
        $this->assertStringContainsString('Nada vencido', $html);
        $this->assertStringContainsString('Estoque em dia', $html);
    }

    public function testFinanceiroSemRelatorioMostraFilaMasNaoOGraficoAnual(): void
    {
        $dados = array_merge($this->dados(), ['pi_mes' => null, 'pi_meses' => []]);
        $html = $this->render(['vOs', 'vLancamento'], $dados);

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('Cobrança atrasada', $html);
        $this->assertStringNotContainsString('Resultado mês a mês', $html);
    }
}
