<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

/**
 * Tabela larga precisa de contêiner com rolagem, senão ela empurra o layout e
 * a página inteira rola de lado no celular (queixa original do ADR-009).
 *
 * Este teste é uma trava de regressão para as telas que já foram ajustadas:
 * se alguém reescrever a tabela e perder o invólucro, a suíte avisa. Ele não
 * tenta cobrir todas as views do sistema — as de impressão e e-mail ficam de
 * fora por natureza.
 */
class TabelasResponsivasTest extends TestCase
{
    /** Views administrativas já tratadas, com o contêiner de rolagem. */
    private const COM_ROLAGEM = [
        'automacao/automacao.php',
        'clientes/clientes.php',
        'produtos/produtos.php',
        'servicos/servicos.php',
        'os/os.php',
        'vendas/vendas.php',
        'financeiro/lancamentos.php',
        'garantias/garantias.php',
        'arquivos/arquivos.php',
        'usuarios/usuarios.php',
        'inventarios/lista.php',
        'inventarios/contar.php',
        'rh/colaboradores.php',
        'mapos/pesquisa.php',
        'entradas/gerenciar.php',
        'entradas/apuracao.php',
    ];

    public function testOsComponentesExistemNoCss(): void
    {
        $css = (string) file_get_contents(APPPATH . '../assets/css/moderno.css');

        $this->assertStringContainsString('.ui-tabela-rolagem', $css);
        $this->assertStringContainsString('.ui-tabela-empilha', $css);
        $this->assertStringContainsString('data-rotulo', $css, 'o modo cartão depende do rótulo por célula');
    }

    public function testTelasAjustadasMantemOContainerDeRolagem(): void
    {
        foreach (self::COM_ROLAGEM as $caminho) {
            $arquivo = APPPATH . 'views/' . $caminho;
            $this->assertFileExists($arquivo, 'view sumiu: ' . $caminho);

            $html = (string) file_get_contents($arquivo);

            $this->assertMatchesRegularExpression(
                '/ui-tabela-(rolagem|empilha)/',
                $html,
                $caminho . ': a tabela perdeu o contêiner responsivo'
            );
        }
    }

    /**
     * Impressão e e-mail não podem ganhar rolagem: o papel não rola, e o
     * layout de impressão depende da largura fixa.
     */
    public function testImpressaoFicaForaDaRegra(): void
    {
        $impressao = [
            'os/imprimirOs.php',
            'garantias/imprimirGarantia.php',
            'vendas/imprimirVendaTermica.php',
            'checkin/imprimirCheckin.php',
        ];

        foreach ($impressao as $caminho) {
            $arquivo = APPPATH . 'views/' . $caminho;
            if (! is_file($arquivo)) {
                continue;
            }
            $this->assertStringNotContainsString(
                'ui-tabela-rolagem',
                (string) file_get_contents($arquivo),
                $caminho . ': tela de impressão não deve ter rolagem de tela'
            );
        }
    }
}
