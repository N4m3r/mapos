<?php

use PHPUnit\Framework\TestCase;

/**
 * Render da barra lateral (views/tema/menu.php) sem banco.
 *
 * O catalogo tem teste proprio (MenuNavegacaoTest), mas o ADR-023 trocou o
 * corpo da view: antes eram <li> escritos a mao, uma tela por vez; agora tudo
 * sai de menu_navegacao_para(). Erro de variavel, item duplicado como ativo e
 * perda de escape so aparecem renderizando.
 */

require_once APPPATH . 'helpers/menu_navegacao_helper.php';

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

class MenuViewPermissao
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

class MenuViewSessao
{
    public function userdata($chave)
    {
        return ['permissao' => 1][$chave] ?? null;
    }
}

class MenuViewUri
{
    private $uri;

    public function __construct($uri)
    {
        $this->uri = $uri;
    }

    public function uri_string()
    {
        return $this->uri;
    }
}

/** Controller falso: so o que a barra lateral consome. */
class MenuViewFalso
{
    public $permission;

    public $session;

    public $uri;

    public $load;

    public function __construct(array $liberadas, $uri)
    {
        $this->permission = new MenuViewPermissao($liberadas);
        $this->session = new MenuViewSessao();
        $this->uri = new MenuViewUri($uri);
        $this->load = new class {
            public function helper($nome)
            {
                return $this;
            }
        };
    }

    public function render(array $dados)
    {
        extract($dados, EXTR_OVERWRITE);
        ob_start();
        include APPPATH . 'views/tema/menu.php';

        return ob_get_clean();
    }
}

class MenuViewTest extends TestCase
{
    private function render(array $liberadas, $uri = 'mapos', $tema = 'default'): string
    {
        return (new MenuViewFalso($liberadas, $uri))->render([
            'configuration' => ['app_theme' => $tema],
        ]);
    }

    private function assertSemErroPhp(string $html): void
    {
        foreach (['Fatal error', 'Parse error', 'Warning:', 'Notice:', 'Deprecated:', 'Undefined variable', 'Undefined array key'] as $marca) {
            $this->assertStringNotContainsString($marca, $html, 'A barra lateral gerou: ' . $marca);
        }
    }

    public function testAdminVeOsGruposEORodape(): void
    {
        $html = $this->render(['vOs', 'eOs', 'rOs', 'vCliente', 'vProduto', 'vServico', 'cSistema']);

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('id="sidebar"', $html);
        $this->assertStringContainsString('menu-links', $html);
        $this->assertStringContainsString('nv-cat', $html, 'categoria do grupo nao renderizou');
        $this->assertStringContainsString('Ordens de Serviço', $html);
        $this->assertStringContainsString('title-tooltip', $html, 'modo recolhido depende da tooltip');
        $this->assertStringContainsString('login/sair', $html, 'rodape de saida sumiu');
        $this->assertStringContainsString('mapos/pesquisar', $html, 'busca sumiu');
    }

    /**
     * O menu tem 49 destinos e ficava longo demais numa lista so. Cada grupo do
     * catalogo virou uma categoria recolhivel: botao com aria-expanded e a lista
     * de itens logo abaixo, para o usuario abrir so o que interessa.
     */
    public function testCadaGrupoViraCategoriaRecolhivel(): void
    {
        $html = $this->render(['vOs', 'eOs', 'rOs', 'vCliente', 'vProduto', 'vServico', 'cSistema']);

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('nv-cat', $html, 'categoria nao renderizou');
        $this->assertStringContainsString('nv-cat-btn', $html, 'botao da categoria nao renderizou');
        $this->assertStringContainsString('nv-sub', $html, 'lista da categoria nao renderizou');
        $this->assertStringContainsString('aria-expanded=', $html, 'faltou o estado da categoria');

        // Uma categoria por grupo liberado, e cada uma com a sua lista.
        $this->assertSame(
            substr_count($html, 'class="nv-cat-btn"'),
            substr_count($html, 'class="nv-sub"'),
            'toda categoria precisa ter a sua lista de itens'
        );
        $this->assertGreaterThan(1, substr_count($html, 'class="nv-cat-btn"'), 'esperava mais de uma categoria');
    }

    /**
     * A categoria com a tela atual nasce aberta; as outras nascem fechadas. Sem
     * isso o item marcado pode ficar escondido atras de um clique.
     */
    public function testCategoriaDaTelaAtualNasceAberta(): void
    {
        $html = $this->render(['vOs', 'eOs', 'rOs', 'vCliente'], 'os/atribuir');

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('nv-cat-atual', $html, 'a categoria da tela atual precisa ser marcada');
        $this->assertSame(1, substr_count($html, 'nv-cat-atual'), 'so uma categoria deve ser a atual');

        // A lista da categoria atual nao leva o atributo hidden; as demais levam.
        $this->assertMatchesRegularExpression(
            '/id="nv-sub-operacao"(?!\s+hidden)/',
            $html,
            'a lista da categoria atual nao pode nascer escondida'
        );
        $this->assertMatchesRegularExpression(
            '/id="nv-sub-cadastros"\s+hidden/',
            $html,
            'categoria sem tela atual deve nascer fechada'
        );
    }

    public function testPerfilSemPermissaoNaoVeOsItens(): void
    {
        $html = $this->render(['vOs']);

        $this->assertSemErroPhp($html);
        $this->assertStringContainsString('Ordens de Serviço', $html);
        $this->assertStringNotContainsString('Clientes e Fornecedores', $html);
        $this->assertStringNotContainsString('Custo e margem por OS', $html);
    }

    public function testUmaTelaMarcaUmItemSo(): void
    {
        $html = $this->render(['vOs', 'eOs'], 'os/atribuir');

        $this->assertSemErroPhp($html);
        $this->assertSame(
            1,
            substr_count($html, 'aria-current="page"'),
            'uma tela deve marcar exatamente um item como atual'
        );
        $this->assertStringContainsString('Central de Atendimento', $html);
    }

    public function testTemaClaroUsaOLogoEscuro(): void
    {
        $claro = $this->render(['vOs'], 'mapos', 'white');
        $escuro = $this->render(['vOs'], 'mapos', 'default');

        $this->assertStringContainsString('logo-mapos.png', $claro);
        $this->assertStringNotContainsString('logo-mapos-branco.png', $claro);
        $this->assertStringContainsString('logo-mapos-branco.png', $escuro);
    }

    /**
     * O catalogo e codigo fixo, entao nao da para injetar um rotulo com "<" e
     * ver o escape em acao. Este teste varre a view: todo echo que imprime
     * campo do catalogo precisa passar por html_escape, e nenhuma saida pode
     * ficar de fora — a dica, por exemplo, aparece em dois lugares.
     */
    public function testTodaSaidaDoCatalogoPassaPorHtmlEscape(): void
    {
        $view = (string) file_get_contents(APPPATH . 'views/tema/menu.php');

        preg_match_all('/<\?=\s*(.+?)\s*\?>/s', $view, $achados);

        $doCatalogo = [];
        foreach ($achados[1] as $expressao) {
            if (strpos($expressao, '$item[') === false && strpos($expressao, '$grupo[') === false) {
                continue;
            }
            $doCatalogo[] = $expressao;
            $this->assertStringContainsString(
                'html_escape',
                $expressao,
                'saida do catalogo sem escape: ' . preg_replace('/\s+/', ' ', $expressao)
            );
        }

        $this->assertGreaterThanOrEqual(
            4,
            count($doCatalogo),
            'poucas saidas do catalogo encontradas: a varredura pode ter parado de casar'
        );
    }
}
