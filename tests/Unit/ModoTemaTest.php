<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Botao de modo claro/escuro do topo: tema aplicado conforme o tema do sistema
 * (Configuracoes > Sistema) e a escolha do usuario (cookie mapos_modo_<id>).
 */
class ModoTemaTest extends TestCase
{
    public static function temas(): array
    {
        // tema do sistema, modo do usuario, tema esperado
        return [
            'claro sem escolha' => ['white', null, 'white'],
            'claro -> escuro' => ['white', 'escuro', 'puredark'],
            'claro -> claro' => ['white', 'claro', 'white'],
            'white green -> escuro' => ['whitegreen', 'escuro', 'puredark'],
            'white green -> claro mantem' => ['whitegreen', 'claro', 'whitegreen'],
            'pure dark sem escolha' => ['puredark', null, 'puredark'],
            'pure dark -> claro' => ['puredark', 'claro', 'white'],
            'dark violet -> escuro mantem' => ['darkviolet', 'escuro', 'darkviolet'],
            'dark orange -> claro' => ['darkorange', 'claro', 'white'],
            'default sem escolha' => ['default', null, 'default'],
            'default -> claro' => ['default', 'claro', 'white'],
            'default -> escuro' => ['default', 'escuro', 'puredark'],
            'vazio vira white' => ['', null, 'white'],
            'modo invalido ignora' => ['white', '<script>', 'white'],
        ];
    }

    #[DataProvider('temas')]
    public function testTemaPorModo(string $sistema, ?string $modo, string $esperado): void
    {
        $this->assertSame($esperado, tema_por_modo($sistema, $modo));
    }

    public function testParSempreTemUmLadoEscuroEUmClaro(): void
    {
        foreach (['default', 'white', 'puredark', 'darkviolet', 'darkorange', 'whitegreen', 'whiteblack'] as $tema) {
            $par = tema_par_claro_escuro($tema);
            $this->assertFalse(tema_e_escuro($par['claro']), "lado claro de $tema");
            $this->assertTrue(tema_e_escuro($par['escuro']), "lado escuro de $tema");
            $this->assertNotSame('', tema_arquivo_css($par['claro']), "folha do lado claro de $tema");
            $this->assertNotSame('', tema_arquivo_css($par['escuro']), "folha do lado escuro de $tema");
        }
    }

    public function testArquivoCssDosTemasExiste(): void
    {
        foreach (['white', 'puredark', 'darkviolet', 'darkorange', 'whitegreen', 'whiteblack'] as $tema) {
            $arquivo = tema_arquivo_css($tema);
            $this->assertFileExists(FCPATH . $arquivo, $tema);
        }
        $this->assertSame('', tema_arquivo_css('default'));
        $this->assertSame('', tema_arquivo_css('../../etc/passwd'));
    }

    public function testTopoTemBotaoAcessivelEScriptComNonce(): void
    {
        $topo = file_get_contents(APPPATH . 'views/tema/topo.php');
        $this->assertStringContainsString('id="btn-modo-escuro"', $topo);
        $this->assertStringContainsString('aria-label=', $topo);
        $this->assertStringContainsString('aria-pressed=', $topo);
        $this->assertMatchesRegularExpression('/<script nonce="<\?= csp_nonce\(\) \?>" src="[^"]*assets\/js\/modo-tema\.js/', $topo);
        $this->assertStringContainsString('data-bs-theme=', $topo);
        $this->assertFileExists(FCPATH . 'assets/js/modo-tema.js');
    }
}
