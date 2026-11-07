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

/**
 * Render da pagina PUBLICA de pedido de link de aprovacao pelo chamado
 * (views/aprovacao/solicitar.php), sem banco.
 *
 * A pagina existe para o cliente que perdeu o link. Se ela quebrar, o cliente
 * nao tem por onde pedir outro — por isso o render e coberto: erro de variavel
 * indefinida vira tela em branco em producao.
 *
 * O render roda num metodo de instancia: dentro da view, `$this->config`,
 * `$this->security` e `$this->input` resolvem para este teste, o mesmo contrato
 * que o CodeIgniter oferece.
 */
class AprovacaoSolicitarViewTest extends TestCase
{
    public $config;

    public $security;

    public $input;

    protected function setUp(): void
    {
        $this->config = new class {
            public function item($chave)
            {
                return $chave === 'app_name' ? 'Map-OS Teste' : null;
            }
        };
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
        $this->input = new class {
            private $valores = [];

            public function post($chave = null)
            {
                return $chave === null ? $this->valores : ($this->valores[$chave] ?? null);
            }

            public function definir(array $valores): void
            {
                $this->valores = $valores;
            }
        };
    }

    /** @param array<string, mixed> $dados */
    private function render(array $dados): string
    {
        extract($dados, EXTR_OVERWRITE);

        ob_start();
        include APPPATH . 'views/aprovacao/solicitar.php';

        return ob_get_clean();
    }

    private function assertSemErroPhp(string $html): void
    {
        foreach (['Fatal error', 'Parse error', 'Warning:', 'Notice:', 'Deprecated:', 'Undefined variable', 'Undefined array key', 'Undefined property'] as $marca) {
            $this->assertStringNotContainsString($marca, $html, 'A página gerou: ' . $marca);
        }
    }

    private function emitente($nome = 'Empresa Teste'): object
    {
        return (object) ['nome' => $nome, 'telefone' => '(11) 90000-0000', 'logo' => 'logo.png'];
    }

    public function testFormularioPedindoChamadoEDocumento(): void
    {
        $html = $this->render([
            'erro' => null,
            'info' => null,
            'emitente' => $this->emitente(),
        ]);

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('Receber o link de aprovação', $html);
        $this->assertStringContainsString('name="chamado"', $html);
        $this->assertStringContainsString('name="documento"', $html);
        $this->assertStringContainsString('aprovacao/enviarLink', $html);
        $this->assertStringContainsString('MAPOS_TOKEN', $html, 'o formulário público precisa do token CSRF');
        $this->assertStringContainsString('Empresa Teste', $html);
        $this->assertStringContainsString('noindex, nofollow', $html, 'página pública não deve ser indexada');
    }

    public function testMostraErroEInfoDoFlashdata(): void
    {
        $html = $this->render([
            'erro' => 'Informe o número do chamado e o CPF/CNPJ do titular.',
            'info' => null,
            'emitente' => null,
        ]);

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('Informe o número do chamado e o CPF/CNPJ do titular.', $html);
        $this->assertStringNotContainsString('alert-info', $html);

        $html = $this->render([
            'erro' => null,
            'info' => 'Se houver orçamento aguardando aprovação para este chamado, enviamos o link.',
            'emitente' => null,
        ]);

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('Se houver orçamento aguardando aprovação', $html);
        $this->assertStringNotContainsString('alert-danger', $html);
    }

    /** A pagina funciona sem emitente cadastrado e sem logo. */
    public function testSemEmitenteNaoQuebra(): void
    {
        $html = $this->render([
            'erro' => null,
            'info' => null,
            'emitente' => null,
        ]);

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('Map-OS Teste', $html, 'cai no nome do sistema quando não há emitente');
    }

    /** O que o usuario digitou volta no campo, mas escapado. */
    public function testRepovoaOsCamposComEscape(): void
    {
        $this->input->definir([
            'chamado' => 'CH-2026/0042',
            'documento' => '12.345.678/0001-99',
        ]);

        $html = $this->render([
            'erro' => 'Confira os dados.',
            'info' => null,
            'emitente' => null,
        ]);

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('value="CH-2026/0042"', $html);
        $this->assertStringContainsString('value="12.345.678/0001-99"', $html);
    }

    public function testEntradaMaliciosaNaoViraHtml(): void
    {
        $this->input->definir([
            'chamado' => '"><script>alert(1)</script>',
            'documento' => '123',
        ]);

        $html = $this->render([
            'erro' => null,
            'info' => null,
            'emitente' => null,
        ]);

        $this->assertSemErroPhp($html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html, 'o valor voltou como HTML');
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }
}
