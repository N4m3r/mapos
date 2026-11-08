<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Cada controller da API v1 precisa carregar sozinho, como o roteador do
 * CodeIgniter faz: ele inclui so o arquivo do controller pedido.
 *
 * Os controllers das areas (tecnico, colaborador, ponto) herdam de
 * AreaApiController, que nenhum autoload carrega. Sem o require_once no
 * proprio arquivo, toda rota dessas areas respondia HTTP 500 em producao
 * ("Class AreaApiController not found") e nenhum teste unitario percebia,
 * porque os testes incluem a base na mao. O mesmo carregamento pega metodo
 * sobrescrito com assinatura incompativel, outro fatal so visto em runtime.
 *
 * Cada arquivo roda num processo PHP separado, com CI_Controller de mentira.
 */
class ApiControllersCarregamTest extends TestCase
{
    public static function controllers(): array
    {
        $casos = [];
        foreach (glob(APPPATH . 'controllers/api/v1/*.php') as $arquivo) {
            $casos[basename($arquivo)] = [$arquivo];
        }

        return $casos;
    }

    #[DataProvider('controllers')]
    public function testControllerCarregaSozinho(string $arquivo): void
    {
        $classe = basename($arquivo, '.php');
        $codigo = sprintf(
            "define('BASEPATH', %s); define('APPPATH', %s); class CI_Controller {} require %s; echo class_exists(%s, false) ? 'OK' : 'SEM CLASSE';",
            var_export(BASEPATH, true),
            var_export(APPPATH, true),
            var_export($arquivo, true),
            var_export($classe, true)
        );

        $comando = escapeshellarg(PHP_BINARY) . ' -d display_errors=1 -r ' . escapeshellarg($codigo) . ' 2>&1';
        $saida = trim((string) shell_exec($comando));

        $this->assertStringEndsWith('OK', $saida, $classe . ' nao carrega sozinho: ' . $saida);
    }
}
