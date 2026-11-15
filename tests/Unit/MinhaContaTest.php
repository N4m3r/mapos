<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

if (! function_exists('base_url')) {
    function base_url($uri = '')
    {
        return 'http://localhost/' . ltrim((string) $uri, '/');
    }
}

if (! function_exists('site_url')) {
    function site_url($uri = '')
    {
        return 'http://localhost/index.php/' . ltrim((string) $uri, '/');
    }
}

if (! function_exists('html_escape')) {
    function html_escape($var, $double = true)
    {
        return empty($var) ? $var : htmlspecialchars((string) $var, ENT_QUOTES, 'UTF-8', $double);
    }
}

/**
 * Tela Minha Conta (views/mapos/minhaConta.php) e a validacao da troca de senha.
 */
class MinhaContaTest extends TestCase
{
    public static function senhas(): array
    {
        return [
            'vazia' => ['', '', 'Informe a nova senha.'],
            'curta' => ['abc12', 'abc12', 'A nova senha precisa ter pelo menos 6 caracteres.'],
            'curta com acento conta caracteres' => ['ação1', 'ação1', 'A nova senha precisa ter pelo menos 6 caracteres.'],
            'confirmacao diferente' => ['segura123', 'segura124', 'A confirmação não é igual à nova senha.'],
            'confirmacao ausente' => ['segura123', null, 'A confirmação não é igual à nova senha.'],
            'ok com 6' => ['abc123', 'abc123', null],
            'ok longa' => ['Uma frase-Senha 2026!', 'Uma frase-Senha 2026!', null],
        ];
    }

    #[DataProvider('senhas')]
    public function testSenhaNovaErro(string $nova, ?string $confirmacao, ?string $esperado): void
    {
        $this->assertSame($esperado, senha_nova_erro($nova, $confirmacao));
    }

    public function testControllerValidaNoServidorEMostraAcessos(): void
    {
        $fonte = file_get_contents(APPPATH . 'controllers/Mapos.php');
        $this->assertStringContainsString("senha_nova_erro(\$senha, \$this->input->post('confirmarSenha'))", $fonte);
        $this->assertStringContainsString("log_info('Alterou a própria senha')", $fonte);
        $this->assertStringContainsString("'Efetuou login no sistema'", $fonte);
        $this->assertStringContainsString("\$this->data['modoTema']", $fonte);
    }

    private function render(array $vars): string
    {
        $vars += ['codigosRestantes' => 0, 'modoTema' => 'sistema', 'acessosRecentes' => []];
        extract($vars);
        ob_start();
        include APPPATH . 'views/mapos/minhaConta.php';

        return ob_get_clean();
    }

    private function usuario(array $extra = []): object
    {
        return (object) ($extra + [
            'nome' => 'Ana <b>Teste</b>',
            'email' => 'ana@exemplo.test',
            'telefone' => '(92) 3333-0000',
            'celular' => '',
            'permissao' => 'Administrador',
            'url_image_user' => '',
            'dataExpiracao' => '',
            'totp_ativo' => 0,
        ]);
    }

    public function testTelaTemSecoesEEscapaDados(): void
    {
        $html = $this->render(['usuario' => $this->usuario()]);

        foreach (['Aparência', 'Alterar senha', 'Verificação em duas etapas', 'Acessos recentes'] as $secao) {
            $this->assertStringContainsString($secao, $html);
        }
        $this->assertStringContainsString('Ana &lt;b&gt;Teste&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<b>Teste</b>', $html);
        $this->assertStringContainsString('Sem expiração', $html);
        $this->assertStringContainsString('id="acessosVazio"', $html);
        // campos que o controller e o jquery.validate esperam
        foreach (['id="formSenha"', 'name="oldSenha"', 'name="novaSenha"', 'name="confirmarSenha"', 'id="formImageUser"', 'name="userfile"'] as $campo) {
            $this->assertStringContainsString($campo, $html);
        }
        $this->assertStringContainsString('autocomplete="new-password"', $html);
        $this->assertStringContainsString('minlength="6"', $html);
        $this->assertMatchesRegularExpression('/<script nonce="[^"]+" src="[^"]*assets\/js\/minha-conta\.js/', $html);
    }

    public function testModoAtualMarcadoNaAparencia(): void
    {
        $html = $this->render(['usuario' => $this->usuario(), 'modoTema' => 'escuro']);
        $this->assertMatchesRegularExpression('/aria-checked="true" data-modo="escuro"/', $html);
        $this->assertMatchesRegularExpression('/aria-checked="false" data-modo="sistema"/', $html);
    }

    public function testAcessosRecentesEDoisFatoresAtivo(): void
    {
        $html = $this->render([
            'usuario' => $this->usuario(['totp_ativo' => 1, 'dataExpiracao' => '2030-05-01']),
            'codigosRestantes' => 2,
            'acessosRecentes' => [(object) ['data' => '2026-11-14', 'hora' => '13:59:12', 'ip' => '10.0.0.<5>']],
        ]);
        $this->assertStringContainsString('14/11/2026', $html);
        $this->assertStringContainsString('13:59', $html);
        $this->assertStringContainsString('10.0.0.&lt;5&gt;', $html);
        $this->assertStringContainsString('01/05/2030', $html);
        $this->assertStringContainsString('Restam poucos códigos de recuperação', $html);
        $this->assertStringContainsString('doisFatoresDesativar', $html);
    }

    public function testTopoMenuDoPerfilAbreParaADireita(): void
    {
        $topo = file_get_contents(APPPATH . 'views/tema/topo.php');
        $this->assertStringContainsString('class="dropdown-menu menu-perfil"', $topo);
        $this->assertStringNotContainsString('dropdown-menu-end', $topo);
        $this->assertStringContainsString('Minha Conta', $topo);
        $this->assertStringContainsString("html_escape(\$this->session->userdata('nome_admin'))", $topo);
    }
}
