<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

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

if (! function_exists('asset_versao')) {
    function asset_versao($caminho, $padrao = '1')
    {
        return $padrao;
    }
}

/**
 * Render da tela de automação sem banco.
 *
 * O PHPUnit deste projeto trata warning/notice como falha, então variável ou
 * índice indefinido na view quebra o teste antes de chegar ao navegador.
 *
 * O render é feito num método de instância (como em NotificacoesViewsTest):
 * dentro da view, `$this->security` resolve para este teste, que é o mesmo
 * contrato que o CodeIgniter oferece.
 */
class AutomacaoViewTest extends TestCase
{
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
    }

    /** @param array<string, mixed> $dados */
    private function render(array $dados): string
    {
        extract($dados, EXTR_OVERWRITE);

        ob_start();
        include APPPATH . 'views/automacao/automacao.php';

        return ob_get_clean();
    }

    /** @return array<string, mixed> */
    private function base(array $extra = []): array
    {
        $padrao = [
            'abaAtiva' => 'configuracao',
            'hoje' => '2026-11-07',
            'agendados' => [],
            'resumo' => ['total' => 0, 'aguardando' => 0, 'vencidos' => 0, 'futuros' => 0, 'erro' => 0],
            'proxima_data' => '2026-11-15',
            'automacao_ativa' => true,
            'resultado_fila' => null,
            'automacao_aprovacao_ativa' => '1',
            'automacao_desc_servico' => 'Serviço prestado',
            'automacao_info_complementar' => '',
            'automacao_ctribnac' => '010701',
            'automacao_ctribmun' => '100',
            'automacao_aliquota_iss' => '5',
            'automacao_tp_ret_issqn' => '1',
            'automacao_faturamento_dia' => '15',
        ];

        return array_merge($padrao, $extra);
    }

    private function item(array $extra = []): object
    {
        return (object) array_merge([
            'id' => 7,
            'os_id' => 123,
            'cliente_id' => 3,
            'nomeCliente' => 'Maria de Souza',
            'data_aprovacao' => '2026-10-20 09:15:00',
            'data_agendada' => '2026-11-15',
            'status' => 'aguardando',
            'tentativas' => 0,
            'nota_id' => null,
            'motivo' => null,
        ], $extra);
    }

    private function assertSemErroPhp(string $html): void
    {
        foreach (['Fatal error', 'Parse error', 'Warning:', 'Notice:', 'Deprecated:', 'Undefined variable', 'Undefined array key', 'Undefined property'] as $marca) {
            $this->assertStringNotContainsString($marca, $html, 'A tela gerou: ' . $marca);
        }
    }

    public function testAbaDeConfiguracaoRenderizaOFormulario(): void
    {
        $html = $this->render($this->base());

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('Automação de faturamento', $html);
        $this->assertStringContainsString('automacao/salvar', $html);
        $this->assertStringContainsString('MAPOS_TOKEN', $html, 'o formulário precisa do token CSRF');
        $this->assertStringContainsString('automacao_aprovacao_ativa', $html);
        $this->assertStringContainsString('automacao_faturamento_dia', $html);
        $this->assertStringContainsString('ui-grade', $html, 'os campos precisam da grade responsiva');
    }

    public function testIndicadoresEOEstadoAparecem(): void
    {
        $html = $this->render($this->base([
            'resumo' => ['total' => 4, 'aguardando' => 3, 'vencidos' => 2, 'futuros' => 1, 'erro' => 1],
            'automacao_ativa' => false,
        ]));

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('ui-kpis', $html);
        $this->assertStringContainsString('Desligada', $html);
        $this->assertStringContainsString('ui-kpi', $html);
    }

    public function testAbaDaFilaListaItensComRotuloPorCelula(): void
    {
        $html = $this->render($this->base([
            'abaAtiva' => 'fila',
            'agendados' => [
                $this->item(),
                $this->item(['id' => 8, 'os_id' => 124, 'status' => 'erro', 'tentativas' => 5, 'motivo' => 'Rejeitada pela prefeitura']),
            ],
            'resumo' => ['total' => 2, 'aguardando' => 1, 'vencidos' => 1, 'futuros' => 0, 'erro' => 1],
        ]));

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('ui-tabela-empilha', $html, 'a tabela precisa empilhar no celular');
        $this->assertStringContainsString('data-rotulo="Cliente"', $html);
        $this->assertStringContainsString('data-rotulo="Emite em"', $html);
        $this->assertStringContainsString('Maria de Souza', $html);
        $this->assertStringContainsString('Rejeitada pela prefeitura', $html, 'o motivo do erro precisa aparecer, não só no title');
        $this->assertStringContainsString('Tentar de novo', $html);
    }

    /**
     * Emissão fiscal e cancelamento não podem ser links: o POST é o que impede
     * disparo por prefetch, por link colado ou por clique acidental.
     */
    public function testNenhumaAcaoDaFilaUsaLink(): void
    {
        $html = $this->render($this->base([
            'abaAtiva' => 'fila',
            'agendados' => [$this->item(['status' => 'erro', 'tentativas' => 5])],
            'resumo' => ['total' => 1, 'aguardando' => 0, 'vencidos' => 0, 'futuros' => 0, 'erro' => 1],
        ]));

        $this->assertSemErroPhp($html);

        $this->assertStringNotContainsString(
            'href="http://localhost/index.php/automacao/processarAgendados"',
            $html,
            'processar não pode ser link'
        );
        $this->assertStringNotContainsString(
            'href="http://localhost/index.php/automacao/cancelarAgendado',
            $html,
            'cancelar não pode ser link'
        );
        $this->assertStringNotContainsString(
            'href="http://localhost/index.php/automacao/retentarAgendado',
            $html,
            'retentar não pode ser link'
        );

        $this->assertStringContainsString('action="http://localhost/index.php/automacao/processarAgendados"', $html);
        $this->assertStringContainsString('action="http://localhost/index.php/automacao/cancelarAgendado"', $html);
        $this->assertStringContainsString('action="http://localhost/index.php/automacao/retentarAgendado"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    public function testFilaVaziaMostraEstadoVazio(): void
    {
        $html = $this->render($this->base(['abaAtiva' => 'fila']));

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('ui-vazio', $html);
        $this->assertStringContainsString('Nenhum faturamento em espera', $html);
    }

    public function testResultadoDoProcessamentoAparece(): void
    {
        $html = $this->render($this->base([
            'abaAtiva' => 'fila',
            'resultado_fila' => ['processados' => 3, 'sucesso' => 2, 'falhas' => 1, 'cancelados' => 0],
        ]));

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('aut-resultado', $html);
        $this->assertStringContainsString('emitido(s)', $html);
    }

    /**
     * Toda saída de dado do banco tem de passar por escape. Varre a view: os
     * echos que imprimem campos do item ($a->) ou configuração ($automacao_)
     * precisam de html_escape (aqui via helper $e).
     */
    public function testSaidaDeDadosPassaPorEscape(): void
    {
        $view = (string) file_get_contents(APPPATH . 'views/automacao/automacao.php');

        preg_match_all('/<\?=\s*(.+?)\s*\?>/s', $view, $achados);

        $verificadas = 0;
        foreach ($achados[1] as $expressao) {
            if (strpos($expressao, '$a->') === false && strpos($expressao, '$automacao_') === false) {
                continue;
            }
            // Os casts (int)/(float) são seguros por natureza e não precisam de escape.
            if (preg_match('/^\(\s*(int|float)\s*\)/', trim($expressao))) {
                continue;
            }
            // Ternário de atributo booleano ($flag ? 'checked' : '') emite literal
            // fixo, não dado do banco: não há o que escapar.
            if (preg_match("/\\?\\s*'(checked|selected|disabled|hidden)?'\\s*:\\s*''/", $expressao)) {
                continue;
            }
            $verificadas++;
            $this->assertMatchesRegularExpression(
                '/\$(e|fmtData)\(|html_escape|\(int\)|\(float\)|date\(/',
                $expressao,
                'saida sem escape: ' . preg_replace('/\s+/', ' ', $expressao)
            );
        }

        $this->assertGreaterThanOrEqual(5, $verificadas, 'a varredura não encontrou as saídas esperadas');
    }
}
