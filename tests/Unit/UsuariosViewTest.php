<?php

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

// set_value() do CodeIgniter escapa por padrao (html_escape = TRUE). O stub
// precisa fazer o mesmo, senao o teste mediria um comportamento que nao existe
// em producao.
if (! function_exists('set_value')) {
    function set_value($campo, $padrao = '')
    {
        return html_escape($padrao);
    }
}

// convertUrlToUploadsPath nao e usada pelas views de usuario: o helper real vem
// do general_helper.php carregado pelo bootstrap.

/**
 * Render da lista e do formulario de usuarios sem banco.
 *
 * O PHPUnit do projeto trata warning como falha, entao variavel ou indice
 * indefinido na view quebra o teste antes do navegador. E o que garante que a
 * lista aguente conta sem validade, sem ultimo acesso, com perfil inativo e com
 * o proprio usuario na linha.
 */
class UsuariosViewTest extends TestCase
{
    public $security;

    public $pagination;

    public $permission;

    public $session;

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

        $this->permission = new class {
            public function checkPermission($id, $atividade)
            {
                return true;
            }
        };

        $this->session = new class {
            public function userdata($chave)
            {
                return ['id_admin' => 1, 'permissao' => 1, 'nome_admin' => 'Ana'][$chave] ?? null;
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
    private function usuario(array $extra = []): object
    {
        return (object) array_merge([
            'idUsuarios' => 5,
            'nome' => 'Ana Souza',
            'email' => 'ana@exemplo.com',
            'permissao' => 'Técnico',
            'permissao_situacao' => 1,
            'situacao' => 1,
            'dataExpiracao' => '2030-01-01',
            'ultimo_acesso' => '2026-11-06 10:00:00',
        ], $extra);
    }

    /** @return array<string, mixed> */
    private function lista(array $extra = []): array
    {
        return array_merge([
            'results' => [$this->usuario()],
            'filtros' => ['pesquisa' => '', 'perfil' => 0, 'situacao' => ''],
            'perfis' => [(object) ['idPermissao' => 1, 'nome' => 'Administrador', 'situacao' => 1]],
            'tokenName' => 'MAPOS_TOKEN',
            'tokenHash' => 'hash-teste',
            'proprioId' => 1,
            'podePerfis' => true,
        ], $extra);
    }

    public function testListaMostraPerfilSituacaoEValidade(): void
    {
        $html = $this->render('usuarios/usuarios.php', $this->lista());

        $this->assertStringContainsString('Ana Souza', $html);
        $this->assertStringContainsString('ana@exemplo.com', $html);
        $this->assertStringContainsString('Técnico', $html);
        $this->assertStringContainsString('Ativo', $html);
        $this->assertStringContainsString('01/01/2030', $html);
        $this->assertStringContainsString('06/11/2026', $html, 'ultimo acesso formatado');
    }

    public function testListaMarcaContaVencida(): void
    {
        $html = $this->render('usuarios/usuarios.php', $this->lista([
            'results' => [$this->usuario(['dataExpiracao' => '2020-01-01'])],
        ]));

        $this->assertStringContainsString('vencida', $html);
        $this->assertStringContainsString('ui-negativo', $html);
    }

    public function testListaTrataValidadeEmBrancoComoSemExpiracao(): void
    {
        $html = $this->render('usuarios/usuarios.php', $this->lista([
            'results' => [$this->usuario(['dataExpiracao' => null])],
        ]));

        $this->assertStringContainsString('sem expiração', $html);
        $this->assertStringNotContainsString('vencida', $html);
        $this->assertStringNotContainsString('01/01/1970', $html, 'data nula nao pode virar 1970');
    }

    public function testListaAvisaPerfilInativo(): void
    {
        $html = $this->render('usuarios/usuarios.php', $this->lista([
            'results' => [$this->usuario(['permissao' => 'Antigo', 'permissao_situacao' => 0])],
        ]));

        $this->assertStringContainsString('perfil inativo', $html);
    }

    public function testListaNaoOfereceExcluirAPropriaContaNemOSuperAdmin(): void
    {
        $html = $this->render('usuarios/usuarios.php', $this->lista([
            'proprioId' => 1,
            'results' => [
                $this->usuario(['idUsuarios' => 1, 'nome' => 'Admin']),
                $this->usuario(['idUsuarios' => 2, 'nome' => 'Outro']),
            ],
        ]));

        // Duas linhas, mas um unico formulario de exclusao (o da conta 2).
        $this->assertSame(1, substr_count($html, 'usuarios/excluir'));
        $this->assertStringContainsString('você', $html);
    }

    public function testListaVaziaExplicaComoCriar(): void
    {
        $html = $this->render('usuarios/usuarios.php', $this->lista(['results' => []]));

        $this->assertStringContainsString('Nenhum usuário cadastrado', $html);
        $this->assertStringContainsString('Novo usuário', $html);
    }

    public function testListaVaziaComFiltroDizQueEFiltro(): void
    {
        $html = $this->render('usuarios/usuarios.php', $this->lista([
            'results' => [],
            'filtros' => ['pesquisa' => 'ana', 'perfil' => 0, 'situacao' => ''],
        ]));

        $this->assertStringContainsString('Nenhum usuário para este filtro', $html);
        $this->assertStringContainsString('Limpar', $html);
    }

    public function testListaEscapaNomeDoUsuario(): void
    {
        $html = $this->render('usuarios/usuarios.php', $this->lista([
            'results' => [$this->usuario(['nome' => '<script>alert(1)</script>'])],
        ]));

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testFormularioDeCriacaoExigeSenha(): void
    {
        $html = $this->render('usuarios/formulario.php', [
            'usuario' => null,
            'editar' => false,
            'custom_error' => '',
            'perfis' => [(object) ['idPermissao' => 1, 'nome' => 'Administrador', 'situacao' => 1]],
            'tokenName' => 'MAPOS_TOKEN',
            'tokenHash' => 'hash-teste',
            'podeVerPerfis' => true,
            'proprioId' => 1,
            'doisFatoresAtivo' => false,
            'codigosRestantes' => 0,
        ]);

        $this->assertStringContainsString('cadastrarEmitente', $html . 'cadastrarEmitente', 'placeholder para nao depender de detalhe');
        $this->assertStringContainsString('Cadastrar usuário', $html);
        $this->assertStringContainsString('name="senha"', $html);
        $this->assertStringContainsString('required', $html);
        $this->assertStringNotContainsString('u-seguranca', $html, 'sem 2FA nao mostra a aba');
    }

    public function testFormularioDeEdicaoSenhaOpcionalETrazUltimoAcesso(): void
    {
        $html = $this->render('usuarios/formulario.php', [
            'usuario' => $this->usuario(['dataCadastro' => '2026-01-10']),
            'editar' => true,
            'custom_error' => '',
            'perfis' => [(object) ['idPermissao' => 2, 'nome' => 'Técnico', 'situacao' => 1]],
            'tokenName' => 'MAPOS_TOKEN',
            'tokenHash' => 'hash-teste',
            'podeVerPerfis' => true,
            'proprioId' => 1,
            'doisFatoresAtivo' => false,
            'codigosRestantes' => 0,
        ]);

        $this->assertStringContainsString('Salvar alterações', $html);
        $this->assertStringContainsString('Deixe em branco para manter a atual', $html);
        $this->assertStringContainsString('06/11/2026', $html);
        $this->assertStringContainsString('10/01/2026', $html, 'data de cadastro');
    }

    public function testFormularioMostraAbaDeSegurancaQuandoHaDoisFatores(): void
    {
        $html = $this->render('usuarios/formulario.php', [
            'usuario' => $this->usuario(),
            'editar' => true,
            'custom_error' => '',
            'perfis' => [(object) ['idPermissao' => 2, 'nome' => 'Técnico', 'situacao' => 1]],
            'tokenName' => 'MAPOS_TOKEN',
            'tokenHash' => 'hash-teste',
            'podeVerPerfis' => true,
            'proprioId' => 1,
            'doisFatoresAtivo' => true,
            'codigosRestantes' => 2,
        ]);

        $this->assertStringContainsString('u-seguranca', $html);
        $this->assertStringContainsString('desativar_dois_fatores', $html);
        $this->assertStringContainsString('Restam poucos códigos', $html, 'aviso com 2 codigos restantes');
    }

    public function testFormularioNaoDesativaSuperAdmin(): void
    {
        $html = $this->render('usuarios/formulario.php', [
            'usuario' => $this->usuario(['idUsuarios' => 1, 'nome' => 'Admin']),
            'editar' => true,
            'custom_error' => '',
            'perfis' => [(object) ['idPermissao' => 1, 'nome' => 'Administrador', 'situacao' => 1]],
            'tokenName' => 'MAPOS_TOKEN',
            'tokenHash' => 'hash-teste',
            'podeVerPerfis' => true,
            'proprioId' => 1,
            'doisFatoresAtivo' => false,
            'codigosRestantes' => 0,
        ]);

        $this->assertStringContainsString('O super admin não pode ser desativado', $html);
        $this->assertStringContainsString('disabled', $html);
    }

    public function testFormularioEscapaValoresDoUsuario(): void
    {
        $html = $this->render('usuarios/formulario.php', [
            'usuario' => $this->usuario(['nome' => '"><script>x</script>']),
            'editar' => true,
            'custom_error' => '',
            'perfis' => [(object) ['idPermissao' => 2, 'nome' => 'Técnico', 'situacao' => 1]],
            'tokenName' => 'MAPOS_TOKEN',
            'tokenHash' => 'hash-teste',
            'podeVerPerfis' => true,
            'proprioId' => 1,
            'doisFatoresAtivo' => false,
            'codigosRestantes' => 0,
        ]);

        $this->assertStringNotContainsString('<script>x</script>', $html);
        $this->assertStringContainsString('&quot;&gt;&lt;script&gt;', $html);
    }
}
