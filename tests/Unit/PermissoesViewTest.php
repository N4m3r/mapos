<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/permissoes_helper.php';

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
    function html_escape($var)
    {
        return htmlspecialchars((string) $var, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('csp_nonce')) {
    function csp_nonce()
    {
        return 'nonce-teste';
    }
}

/**
 * Render da lista e do formulario de perfis.
 *
 * O formulario desenha o catalogo inteiro (helpers/permissoes_helper.php): o
 * teste garante que toda chave do catalogo vira um checkbox, que as marcadas
 * vem com checked e que o nome do perfil e escapado.
 */
class PermissoesViewTest extends TestCase
{
    public $security;

    public $pagination;

    public function __construct(string $name)
    {
        parent::__construct($name);

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

        $this->pagination = new class {
            public function create_links()
            {
                return '<div class="pagination"></div>';
            }
        };
    }

    /** @param array<string, mixed> $vars */
    private function render(string $view, array $vars): string
    {
        extract($vars);

        ob_start();
        include APPPATH . 'views/' . $view;

        return ob_get_clean();
    }

    /** @return array<string, mixed> */
    private function perfil(array $extra = []): object
    {
        return (object) array_merge([
            'idPermissao' => 2,
            'nome' => 'Técnico',
            'situacao' => 1,
            'liberadas' => 12,
            'total_chaves' => 104,
            'usuarios' => 3,
            'data' => '2026-01-10',
        ], $extra);
    }

    public function testListaMostraCoberturaDePermissoes(): void
    {
        $html = $this->render('permissoes/permissoes.php', [
            'results' => [$this->perfil()],
            'tokenName' => 'MAPOS_TOKEN',
            'tokenHash' => 'hash-teste',
        ]);

        $this->assertStringContainsString('Técnico', $html);
        $this->assertStringContainsString('12 de 104', $html);
        $this->assertStringContainsString('12%', $html, 'percentual arredondado');
        $this->assertStringContainsString('10/01/2026', $html);
        $this->assertStringContainsString('Ativo', $html);
    }

    public function testListaMarcaPerfilAdministrador(): void
    {
        $html = $this->render('permissoes/permissoes.php', [
            'results' => [$this->perfil(['idPermissao' => 1, 'nome' => 'Administrador'])],
            'tokenName' => 'MAPOS_TOKEN',
            'tokenHash' => 'hash-teste',
        ]);

        $this->assertStringContainsString('admin', $html);
    }

    public function testListaIndicaPerfilSemUsoEForaDoAr(): void
    {
        $html = $this->render('permissoes/permissoes.php', [
            'results' => [$this->perfil(['usuarios' => 0, 'situacao' => 0])],
            'tokenName' => 'MAPOS_TOKEN',
            'tokenHash' => 'hash-teste',
        ]);

        $this->assertStringContainsString('nenhum', $html);
        $this->assertStringContainsString('Inativo', $html);
    }

    public function testListaVaziaExplicaOProximoPasso(): void
    {
        $html = $this->render('permissoes/permissoes.php', [
            'results' => [],
            'tokenName' => 'MAPOS_TOKEN',
            'tokenHash' => 'hash-teste',
        ]);

        $this->assertStringContainsString('Nenhum perfil cadastrado', $html);
        $this->assertStringContainsString('Novo perfil', $html);
    }

    public function testListaEscapaNomeDoPerfil(): void
    {
        $html = $this->render('permissoes/permissoes.php', [
            'results' => [$this->perfil(['nome' => '<img src=x onerror=alert(1)>'])],
            'tokenName' => 'MAPOS_TOKEN',
            'tokenHash' => 'hash-teste',
        ]);

        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
        $this->assertStringContainsString('&lt;img', $html);
    }

    public function testFormularioDesenhaOCatalogoInteiro(): void
    {
        $secoes = permissoes_catalogo();
        $salvas = permissoes_normalizar([]);

        $html = $this->render('permissoes/formulario.php', [
            'secoes' => $secoes,
            'salvas' => $salvas,
            'nome' => 'Novo perfil',
            'situacao' => 1,
            'idPermissao' => null,
            'acao' => 'http://localhost/index.php/permissoes/adicionar',
            'titulo' => 'Novo perfil',
            'subtitulo' => 'Escolha o que este perfil pode fazer.',
            'tokenName' => 'MAPOS_TOKEN',
            'tokenHash' => 'hash-teste',
        ]);

        foreach (permissoes_todas_as_chaves() as $chave) {
            $this->assertStringContainsString('name="' . $chave . '"', $html, 'Chave sem checkbox: ' . $chave);
        }

        // Conta so os checkboxes com atributo checked: o texto do JS desta
        // mesma tela tambem contem a palavra "checked".
        $this->assertSame(0, substr_count($html, 'class="marcar" checked'), 'Perfil novo nao nasce com permissao');
        $this->assertStringContainsString('0 de ' . count(permissoes_todas_as_chaves()), $html);
    }

    public function testFormularioMarcaAsPermissoesSalvas(): void
    {
        $marcadas = ['vOs', 'eOs', 'dOs'];
        $salvas = permissoes_normalizar(array_fill_keys($marcadas, 1));

        $html = $this->render('permissoes/formulario.php', [
            'secoes' => permissoes_catalogo(),
            'salvas' => $salvas,
            'nome' => 'Técnico',
            'situacao' => 1,
            'idPermissao' => 2,
            'acao' => 'http://localhost/index.php/permissoes/editar/2',
            'titulo' => 'Editar perfil',
            'subtitulo' => 'texto',
            'tokenName' => 'MAPOS_TOKEN',
            'tokenHash' => 'hash-teste',
        ]);

        $this->assertSame(count($marcadas), substr_count($html, 'class="marcar" checked'));
        $this->assertStringContainsString('name="idPermissao" value="2"', $html);
        $this->assertStringContainsString('3 de ' . count(permissoes_todas_as_chaves()), $html);
    }

    public function testFormularioEscapaNomeDoPerfil(): void
    {
        $html = $this->render('permissoes/formulario.php', [
            'secoes' => permissoes_catalogo(),
            'salvas' => permissoes_normalizar([]),
            'nome' => '"><script>x</script>',
            'situacao' => 0,
            'idPermissao' => null,
            'acao' => 'http://localhost/index.php/permissoes/adicionar',
            'titulo' => 'Novo perfil',
            'subtitulo' => 'texto',
            'tokenName' => 'MAPOS_TOKEN',
            'tokenHash' => 'hash-teste',
        ]);

        $this->assertStringNotContainsString('<script>x</script>', $html);
        $this->assertStringContainsString('&quot;&gt;&lt;script&gt;', $html);
    }
}
