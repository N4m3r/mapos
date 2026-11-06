<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once APPPATH . 'helpers/menu_navegacao_helper.php';

/**
 * Catalogo unico da navegacao (tema/menu.php): ordem dos grupos, filtro por
 * permissao e marcacao do item aberto. A renderizacao em si e coberta pelo
 * teste de navegador (tests/navegador/telas.mjs).
 */
class MenuNavegacaoTest extends TestCase
{
    private static function liberado(array $liberadas): callable
    {
        return fn ($atividade) => in_array($atividade, $liberadas, true);
    }

    /** @return string[] ids na ordem em que aparecem no menu */
    private static function ids(array $grupos): array
    {
        $ids = [];
        foreach ($grupos as $grupo) {
            foreach ($grupo['itens'] as $item) {
                $ids[] = $item['id'];
            }
        }

        return $ids;
    }

    public function testOrdemDosGrupos(): void
    {
        $this->assertSame(
            ['operacao', 'cadastros', 'comercial', 'financeiro', 'relatorios', 'sistema'],
            array_keys(menu_navegacao_catalogo())
        );
    }

    public function testCadaItemTemRotuloIconeEDestino(): void
    {
        foreach (menu_navegacao_catalogo() as $chave => $grupo) {
            $this->assertNotSame('', trim($grupo['titulo']), 'grupo sem titulo: ' . $chave);
            $this->assertNotEmpty($grupo['itens'], 'grupo vazio: ' . $chave);

            foreach ($grupo['itens'] as $item) {
                $this->assertArrayHasKey('rotulo', $item);
                $this->assertArrayHasKey('dica', $item);
                $this->assertArrayHasKey('rota', $item);
                $this->assertArrayHasKey('icone', $item);
                $this->assertStringStartsWith('bx-', $item['icone'], 'icone fora do padrao: ' . $item['id']);
            }
        }
    }

    public function testSemIdNemRotaRepetidos(): void
    {
        $ids = [];
        $rotas = [];

        foreach (menu_navegacao_catalogo() as $grupo) {
            foreach ($grupo['itens'] as $item) {
                $ids[] = $item['id'];
                $rotas[] = $item['rota'];
            }
        }

        $this->assertSame(array_values(array_unique($ids)), $ids, 'ids repetidos');
        $this->assertSame(array_values(array_unique($rotas)), $rotas, 'rotas repetidas');
    }

    public function testFiltraPorPermissaoEDescartaGrupoVazio(): void
    {
        $grupos = menu_navegacao_para(self::liberado(['vCliente']));

        $this->assertSame(['home', 'clientes'], self::ids($grupos));
        $this->assertSame(['operacao', 'cadastros'], array_keys($grupos));
        $this->assertArrayNotHasKey('permissoes', $grupos['cadastros']['itens'][0], 'a permissao nao vai para a view');
    }

    public function testSemPermissaoFicaSoOInicio(): void
    {
        $grupos = menu_navegacao_para(self::liberado([]));

        $this->assertSame(['home'], self::ids($grupos));
        $this->assertSame(['operacao'], array_keys($grupos));
    }

    public function testCatalogoTem48Itens(): void
    {
        $total = 0;
        foreach (menu_navegacao_catalogo() as $grupo) {
            $total += count($grupo['itens']);
        }

        $this->assertSame(48, $total, 'o catalogo mudou de tamanho; confira se e intencional');
    }

    /**
     * A configuracao da Cora morava no dropdown de Configuracoes do topo, que
     * saiu. Quem tem cNfe e nao tem vCobranca nem vNfe precisa continuar
     * chegando nela pelo menu, senao a tela fica so por URL.
     */
    public function testQuemSoTemCNfeAlcancaAConfiguracaoDaCora(): void
    {
        $grupos = menu_navegacao_para(fn ($atividade) => $atividade === 'cNfe');

        $this->assertContains('cobrancacora', self::ids($grupos));
        $this->assertSame(
            'cobrancacora',
            menu_navegacao_id_ativo('cobrancas/configCora', $grupos),
            'a tela da Cora marca o proprio item, nao o de Cobrancas'
        );
    }

    /**
     * isTecnico() troca o layout inteiro: quem tem vTecnicoDashboard recebe
     * tema/menu_tecnico e nunca ve o menu administrativo. Um item do catalogo
     * que exija essa permissao seria codigo morto (o item "tecnico" era).
     */
    public function testNenhumItemExigePermissaoQueTrocaOLayout(): void
    {
        foreach (menu_navegacao_catalogo() as $chave => $grupo) {
            foreach ($grupo['itens'] as $item) {
                $this->assertNotContains(
                    'vTecnicoDashboard',
                    $item['permissoes'] ?? [],
                    'item ' . $item['id'] . ' (' . $chave . ') nunca apareceria: isTecnico() troca o menu'
                );
            }
        }
    }

    /**
     * Admin = conjunto de todas as permissoes citadas no catalogo. Derivar daqui
     * evita a lista fixa divergir quando um item muda de permissao.
     */
    public function testComTodasAsPermissoesDoCatalogoTudoAparece(): void
    {
        $todas = [];
        $total = 0;
        foreach (menu_navegacao_catalogo() as $grupo) {
            foreach ($grupo['itens'] as $item) {
                $total++;
                foreach ($item['permissoes'] ?? [] as $atividade) {
                    $todas[$atividade] = true;
                }
            }
        }

        $grupos = menu_navegacao_para(self::liberado(array_keys($todas)));

        $this->assertCount(6, $grupos);
        $this->assertCount($total, self::ids($grupos));
    }

    public function testUmaPermissaoAlternativaJaLibera(): void
    {
        // Notificacoes aceita cSistema OU cEmail; Automacao aceita cAutomacao OU cSistema.
        $grupos = menu_navegacao_para(self::liberado(['cEmail']));
        $this->assertContains('notificacoes', self::ids($grupos));
        $this->assertNotContains('automacao', self::ids($grupos));

        $grupos = menu_navegacao_para(self::liberado(['cAutomacao']));
        $this->assertContains('automacao', self::ids($grupos));
    }

    public static function rotas(): array
    {
        return [
            'raiz casa com uri vazia' => ['', '', [], true],
            'raiz nao casa com tela' => ['os', '', [], false],
            'rota exata' => ['clientes', 'clientes', [], true],
            'subrota' => ['clientes/adicionar', 'clientes', [], true],
            'apelido' => ['os/adicionar', 'os', ['os/adicionar'], true],
            'com index.php' => ['index.php/rh', 'rh', [], true],
            'maiusculas' => ['RH/ponto', 'rh', [], true],
            'prefixo parecido' => ['relatorioatendimentos', 'relatorioos', [], false],
            'outro modulo' => ['os', 'relatoriocobranca', [], false],
            'barra inicial' => ['/nfe', 'nfe', [], true],
        ];
    }

    #[DataProvider('rotas')]
    public function testMarcaOItemAberto(string $atual, string $rota, array $aliases, bool $ativo): void
    {
        $this->assertSame($ativo, menu_navegacao_ativo($atual, $rota, $aliases));
    }

    /**
     * A tela marca um item so. Medido no navegador antes da correcao: /os/atribuir
     * marcava dois (Central de Atendimento e Ordens de Servico) e /mapos marcava
     * nenhum, porque o Inicio so casava com a URI vazia.
     */
    public function testUmaTelaMarcaUmItemSo(): void
    {
        $grupos = menu_navegacao_para(static fn () => true);

        $this->assertSame('atendimento', menu_navegacao_id_ativo('os/atribuir', $grupos));
        $this->assertSame('os', menu_navegacao_id_ativo('os', $grupos));
        $this->assertSame('os', menu_navegacao_id_ativo('os/adicionar', $grupos));
        $this->assertSame('home', menu_navegacao_id_ativo('mapos', $grupos));
        $this->assertSame('configuracoes', menu_navegacao_id_ativo('mapos/configurar', $grupos));
        $this->assertNull(menu_navegacao_id_ativo('tela-que-nao-existe', $grupos));
    }

    public function testTodaRotaDoCatalogoTemUmVencedor(): void
    {
        $grupos = menu_navegacao_para(static fn () => true);

        // Rotas tiradas do proprio catalogo (destinos e apelidos), mais uma subrota
        // que nao esta declarada em item nenhum: todas precisam resolver em um item.
        $rotas = ['mapos', 'mapos/configurar', 'clientes/adicionar', 'os/adicionar'];
        foreach (menu_navegacao_catalogo() as $grupo) {
            foreach ($grupo['itens'] as $item) {
                if ($item['rota'] !== '') {
                    $rotas[] = $item['rota'];
                }
                foreach ($item['aliases'] ?? [] as $apelido) {
                    $rotas[] = $apelido;
                }
            }
        }

        foreach (array_unique($rotas) as $rota) {
            $this->assertNotNull(menu_navegacao_id_ativo($rota, $grupos), 'rota sem item: ' . $rota);
        }
    }

    public static function pontuacoes(): array
    {
        return [
            'exata soma mil' => ['os', 'os', [], 1002],
            'exata longa' => ['os/atribuir', 'os/atribuir', [], 1011],
            'prefixo soma o tamanho' => ['os/atribuir', 'os', [], 2],
            'prefixo nao casa ao contrario' => ['os', 'os/atribuir', [], null],
            'apelido exato' => ['os/adicionar', 'os', ['os/adicionar'], 1012],
            'apelido por prefixo' => ['mapos/configurar', 'mapos', ['mapos'], 5],
            'raiz casa com uri vazia' => ['', '', [], 1],
            'raiz nao casa com tela' => ['rh', '', [], null],
        ];
    }

    #[DataProvider('pontuacoes')]
    public function testPontuaOQuaoEspecificoEOMatch(string $atual, string $rota, array $aliases, ?int $esperado): void
    {
        $this->assertSame($esperado, menu_navegacao_pontuacao($atual, $rota, $aliases));
    }
}
