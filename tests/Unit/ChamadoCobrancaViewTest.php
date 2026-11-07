<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

require_once APPPATH . 'helpers/chamado_cobranca_helper.php';

if (! function_exists('html_escape')) {
    function html_escape($v)
    {
        return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
if (! function_exists('base_url')) {
    function base_url($p = '')
    {
        return 'http://localhost/' . ltrim((string) $p, '/');
    }
}
if (! function_exists('site_url')) {
    function site_url($p = '')
    {
        return 'http://localhost/index.php/' . ltrim((string) $p, '/');
    }
}

/**
 * Render do painel de cobranca dos chamados (views/chamadoscobranca/index.php).
 *
 * O PHPUnit deste projeto trata warning/notice como falha, então variavel ou
 * indice indefinido na view quebra aqui antes de chegar ao navegador. O render
 * roda num metodo de instancia: dentro da view, `$this->permission`,
 * `$this->session` e `$this->security` resolvem para este teste, o mesmo
 * contrato que o CodeIgniter oferece.
 */
class ChamadoCobrancaViewTest extends TestCase
{
    public $permission;

    public $session;

    public $security;

    protected function setUp(): void
    {
        $this->security = new class {
            public function get_csrf_token_name()
            {
                return 'MAPOS_TOKEN';
            }

            public function get_csrf_hash()
            {
                return 'hash-teste';
            }
        };
        $this->session = new class {
            public function userdata($chave)
            {
                return $chave === 'permissao' ? 1 : null;
            }
        };
    }

    private function libera(array $permissoes): void
    {
        $this->permission = new class($permissoes) {
            private $liberadas;

            public function __construct(array $liberadas)
            {
                $this->liberadas = $liberadas;
            }

            public function checkPermission($id, $atividade)
            {
                return in_array($atividade, $this->liberadas, true);
            }
        };
    }

    private function item(array $extra = []): array
    {
        return array_merge([
            'chave' => 'os:12',
            'tipo' => 'os',
            'ref_id' => 12,
            'idOs' => 12,
            'chamado' => 'CH-2026/0042',
            'cliente' => 'Maria de Souza',
            'clientes_id' => 3,
            'data_ref' => '2026-11-01',
            'valor' => 1250.5,
            'status' => 'pendente_aprovacao',
            'detalhe' => 'Aguardando aprovação do cliente',
            'link' => 'os/visualizar/12',
        ], $extra);
    }

    private function dados(array $extra = []): array
    {
        $itens = $extra['itens'] ?? [$this->item()];

        return array_merge([
            'filtro' => ['busca' => '', 'chamado' => '', 'status' => [], 'hoje' => '2026-11-07'],
            'itens' => $itens,
            'kpis' => Chamado_cobranca::kpis($itens),
            'emAberto' => Chamado_cobranca::totalEmAberto($itens),
            'pontosStatus' => Chamado_cobranca::pontosStatus($itens),
            'topClientes' => Chamado_cobranca::topClientes($itens),
            'disponivel' => true,
        ], $extra);
    }

    /** @param array<string, mixed> $dados */
    private function render(array $dados): string
    {
        extract($dados, EXTR_OVERWRITE);

        ob_start();
        include APPPATH . 'views/chamadoscobranca/index.php';

        return ob_get_clean();
    }

    private function assertSemErroPhp(string $html): void
    {
        foreach (['Fatal error', 'Parse error', 'Warning:', 'Notice:', 'Deprecated:', 'Undefined variable', 'Undefined array key', 'Undefined property'] as $marca) {
            $this->assertStringNotContainsString($marca, $html, 'O painel gerou: ' . $marca);
        }
    }

    public function testAdminVeKpisTabelaEAcoes(): void
    {
        $this->libera(['vCobranca', 'vOs', 'eCobranca']);
        $html = $this->render($this->dados());

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('Cobrança dos chamados', $html);
        $this->assertStringContainsString('CH-2026/0042', $html);
        $this->assertStringContainsString('OS #12', $html);
        $this->assertStringContainsString('R$ 1.250,50', $html);
        $this->assertStringContainsString('Pendente de aprovação', $html);
        $this->assertStringContainsString('chamadoscobranca/solicitarAprovacao', $html);
        $this->assertStringContainsString('chamadoscobranca/cobrancasAcao', $html);
        $this->assertStringContainsString('MAPOS_TOKEN', $html, 'o formulário em lote precisa do token CSRF');
        $this->assertStringContainsString('<svg ', $html, 'os gráficos não renderizaram');
    }

    public function testSemPermissoesDeAcaoNaoMostraBotoesDeLote(): void
    {
        $this->libera(['vCobranca']);
        $html = $this->render($this->dados());

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('CH-2026/0042', $html, 'a leitura continua liberada');
        $this->assertStringNotContainsString('Solicitar aprovação', $html);
        $this->assertStringNotContainsString('Confirmar pagamento', $html);
        $this->assertStringNotContainsString('Cancelar cobrança', $html);
    }

    public function testListaVaziaExplicaEmVezDeTabelaVazia(): void
    {
        $this->libera(['vCobranca', 'vOs']);
        $html = $this->render($this->dados(['itens' => []]));

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('Nada pendente com estes filtros', $html);
        $this->assertStringNotContainsString('<tbody>', $html, 'sem itens nao deve montar a tabela');
        $this->assertStringNotContainsString('<svg ', $html, 'sem dados nao ha grafico');
    }

    public function testAmbienteSemMigrationAvisa(): void
    {
        $this->libera(['vCobranca']);
        $html = $this->render($this->dados(['disponivel' => false]));

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('tools migrate', $html);
    }

    public function testEscapaCamposDoBanco(): void
    {
        $this->libera(['vCobranca', 'vOs', 'eCobranca']);
        $html = $this->render($this->dados([
            'itens' => [
                $this->item(['cliente' => 'Cliente <b>X</b> & Cia', 'chamado' => '<script>alert(1)</script>']),
            ],
        ]));

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('Cliente &lt;b&gt;X&lt;/b&gt; &amp; Cia', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html, 'chamado entrou como HTML');
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function testFiltroDeStatusMarcaOsChipsSemTrocaraSituacaoPorIndice(): void
    {
        $this->libera(['vCobranca']);
        $html = $this->render($this->dados([
            'filtro' => ['busca' => '', 'chamado' => '', 'status' => ['vencida', 'paga'], 'hoje' => '2026-11-07'],
        ]));

        $this->assertSemErroPhp($html);
        $this->assertMatchesRegularExpression('/value="vencida"\s+checked/', $html, 'chip vencida deveria vir marcado');
        $this->assertMatchesRegularExpression('/value="paga"\s+checked/', $html, 'chip paga deveria vir marcado');
        $this->assertStringNotContainsString('Situações: 0, 1', $html, 'os indices vazaram para o cabecalho de impressao');
        $this->assertStringContainsString('Situações: Vencida, Paga', $html);
    }

    public function testChamadoEEstatisticasDeAbertoAparecem(): void
    {
        $this->libera(['vCobranca']);
        $html = $this->render($this->dados([
            'itens' => [
                $this->item(['chave' => 'os:12', 'valor' => 100]),
                $this->item(['chave' => 'cob:8', 'tipo' => 'cob', 'ref_id' => 8, 'status' => 'paga', 'valor' => 900]),
            ],
        ]));

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('Cobrança #8', $html);
        $this->assertStringContainsString('Em aberto', $html);
        $this->assertStringContainsString('R$ 100,00', $html, 'o total em aberto ignora a cobranca paga');
    }

    public function testLinhaDeVendaNaoGanhaCheckbox(): void
    {
        $this->libera(['vCobranca', 'vOs']);
        $html = $this->render($this->dados([
            'itens' => [
                $this->item(['chave' => 'vend:7', 'tipo' => 'vend', 'ref_id' => 7, 'link' => 'vendas/visualizar/7']),
                $this->item(['chave' => 'os:12']),
            ],
        ]));

        $this->assertSemErroPhp($html);
        // LerSelecao so aceita 'os:' e 'cob:'; venda com checkbox seria descartada em silencio.
        $this->assertStringNotContainsString('value="vend:7"', $html, 'linha de venda nao tem acao em lote');
        $this->assertStringContainsString('value="os:12"', $html, 'a OS continua selecionavel');
        $this->assertStringContainsString('vendas/visualizar/7', $html, 'a venda continua acessivel pela coluna de acoes');
    }

}
