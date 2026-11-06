<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once APPPATH . 'helpers/menu_superior_helper.php';

/**
 * Menu superior de atalhos (tema/topo.php): filtro por permissao, ordem e a
 * marcacao do modulo aberto. A view em si e coberta pelo smoke test.
 */
class MenuSuperiorTest extends TestCase
{
    /** Catalogo na ordem em que aparece no menu: permissao => id do atalho. */
    private const CATALOGO = [
        'vTecnicoMapa' => 'mapa',
        'vRh' => 'rh',
        'vNfe' => 'nfe',
        'vEntrada' => 'entradas',
        'vContrato' => 'contratos',
        'vCobranca' => 'cobrancas',
        'rOs' => 'osrealizadas',
    ];

    /** Callback de permissao que libera so as atividades informadas. */
    private static function liberado(array $liberadas): callable
    {
        return fn ($atividade) => in_array($atividade, $liberadas, true);
    }

    private static function ids(array $atalhos): array
    {
        return array_column($atalhos, 'id');
    }

    public static function permissoes(): array
    {
        return [
            'admin' => [['vTecnicoMapa', 'vRh', 'vNfe', 'vEntrada', 'vContrato', 'vCobranca', 'rOs']],
            'nenhuma' => [[]],
            'somente rh e relatorio de os' => [['rOs', 'vRh']],
            'atendente sem financeiro' => [['vNfe', 'vEntrada']],
        ];
    }

    #[DataProvider('permissoes')]
    public function testMontaNaOrdemDoCatalogoSoComPermissao(array $liberadas): void
    {
        $esperado = [];
        foreach (self::CATALOGO as $permissao => $id) {
            if (in_array($permissao, $liberadas, true)) {
                $esperado[] = $id;
            }
        }

        $this->assertSame($esperado, self::ids(menu_superior_atalhos(self::liberado($liberadas))));
    }

    public function testSomenteRhERelatorioDeOsSaemNaOrdemDoMenu(): void
    {
        $atalhos = menu_superior_atalhos(self::liberado(['rOs', 'vRh']));

        $this->assertSame(['rh', 'osrealizadas'], self::ids($atalhos));
    }

    public function testCadaAtalhoTemRotuloIconeERota(): void
    {
        $atalhos = menu_superior_atalhos(self::liberado(['vRh', 'vCobranca']));

        $this->assertSame(
            [
                ['id' => 'rh', 'rotulo' => 'Recursos Humanos', 'icone' => 'bx-id-card', 'rota' => 'rh'],
                ['id' => 'cobrancas', 'rotulo' => 'Cobranças', 'icone' => 'bx-dollar-circle', 'rota' => 'cobrancas/cobrancas'],
            ],
            $atalhos
        );
        $this->assertArrayNotHasKey('permissao', $atalhos[0], 'a chave de permissao nao vai para a view');
    }

    public function testConsultaUmaPermissaoPorAtalho(): void
    {
        $pedidas = [];
        menu_superior_atalhos(function ($atividade) use (&$pedidas) {
            $pedidas[] = $atividade;

            return false;
        });

        $this->assertSame(array_keys(self::CATALOGO), $pedidas);
    }

    public static function rotas(): array
    {
        return [
            'lista de cobrancas' => ['cobrancas/cobrancas', 'cobrancas/cobrancas', true],
            'configuracao do gateway' => ['cobrancas/configCora', 'cobrancas/cobrancas', true],
            'com barra inicial' => ['/rh', 'rh', true],
            'com index.php' => ['index.php/relatorioos', 'relatorioos', true],
            'maiusculas' => ['RH/ponto', 'rh', true],
            'raiz do modulo' => ['nfe', 'nfe', true],
            'outro modulo' => ['os', 'relatorioos', false],
            'prefixo parecido' => ['relatorioatendimentos', 'relatorioos', false],
            'os nao pertence a cobrancas' => ['os', 'cobrancas/cobrancas', false],
            'vazio' => ['', 'rh', false],
        ];
    }

    #[DataProvider('rotas')]
    public function testMarcaOModuloAberto(string $atual, string $rota, bool $ativo): void
    {
        $this->assertSame($ativo, menu_superior_ativo($atual, $rota));
    }
}
