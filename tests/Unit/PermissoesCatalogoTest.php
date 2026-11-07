<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/permissoes_helper.php';

/**
 * Catalogo unico das permissoes.
 *
 * A tela de perfis, o menu e checkPermission precisam falar das mesmas chaves.
 * Antes cada tela mantinha a sua lista (e o seed do banco tinha 53 chaves,
 * enquanto o sistema ja usava mais de cem). Aqui garante-se que o catalogo e a
 * fonte: sem chave repetida, sem chave orfa e com normalizacao que devolve
 * sempre o conjunto inteiro.
 */
class PermissoesCatalogoTest extends TestCase
{
    public function testCatalogoTemSecoesComChaves(): void
    {
        $secoes = permissoes_catalogo();

        $this->assertNotEmpty($secoes);

        foreach ($secoes as $secao) {
            $this->assertArrayHasKey('titulo', $secao);
            $this->assertArrayHasKey('chaves', $secao);
            $this->assertNotEmpty($secao['chaves'], 'Secao ' . $secao['titulo'] . ' sem chaves');

            foreach ($secao['chaves'] as $item) {
                $this->assertArrayHasKey('chave', $item);
                $this->assertArrayHasKey('rotulo', $item);
                $this->assertNotSame('', trim((string) $item['rotulo']));
            }
        }
    }

    public function testChavesNaoSeRepetem(): void
    {
        $chaves = permissoes_todas_as_chaves();

        $this->assertSame(
            count($chaves),
            count(array_unique($chaves)),
            'O catalogo tem chave repetida: duas telas gravariam a mesma permissao.'
        );
    }

    public function testIndiceCobreTodasAsChaves(): void
    {
        $indice = permissoes_indice();

        $this->assertSame(count(permissoes_todas_as_chaves()), count($indice));

        foreach (permissoes_todas_as_chaves() as $chave) {
            $this->assertArrayHasKey($chave, $indice);
        }
    }

    public function testNormalizarDevolveOCatalogoInteiroEmZeroOuUm(): void
    {
        $normalizadas = permissoes_normalizar(['vOs' => 1, 'eOs' => '1', 'dOs' => 0]);

        $this->assertSame(permissoes_todas_as_chaves(), array_keys($normalizadas));
        $this->assertSame(1, $normalizadas['vOs']);
        $this->assertSame(1, $normalizadas['eOs']);
        $this->assertSame(0, $normalizadas['dOs']);

        foreach ($normalizadas as $chave => $valor) {
            $this->assertContains($valor, [0, 1], 'Chave ' . $chave . ' saiu com valor inesperado');
        }
    }

    public function testNormalizarIgnoraChaveQueNaoExisteNoCatalogo(): void
    {
        $normalizadas = permissoes_normalizar(['chaveInventada' => 1]);

        $this->assertArrayNotHasKey('chaveInventada', $normalizadas);
        $this->assertEmpty(array_filter($normalizadas), 'Chave inventada nao pode ligar permissao');
    }

    /**
     * Entrada que nao e array (blob corrompido, null) nao pode ligar nada.
     * Quem deserializa o blob do banco e o controller, antes de normalizar.
     */
    public function testNormalizarDescartaEntradaQueNaoEArray(): void
    {
        $this->assertSame(0, permissoes_normalizar('isto nao e serializado')['vOs']);
        $this->assertSame(0, permissoes_normalizar(null)['vOs']);
        $this->assertSame(0, permissoes_normalizar(serialize(['vOs' => 1]))['vOs']);
    }

    /**
     * Os valores gravados variam entre "1"/1 do formulario e null de chave que
     * nunca existiu. So o numero 1 liga a permissao; o resto vira 0 — inclusive
     * true, que is_numeric() nao aceita.
     */
    public function testNormalizarConverteValoresLegados(): void
    {
        $normalizadas = permissoes_normalizar(['vOs' => '1', 'eOs' => null, 'dOs' => 1, 'aOs' => true]);

        $this->assertSame(1, $normalizadas['vOs']);
        $this->assertSame(0, $normalizadas['eOs']);
        $this->assertSame(1, $normalizadas['dOs']);
        $this->assertSame(0, $normalizadas['aOs'], 'true nao e valor numerico aceito');
    }

    public function testChavesPadraoExistemNoCatalogo(): void
    {
        $todas = permissoes_todas_as_chaves();

        foreach (permissoes_chaves_padrao() as $chave) {
            $this->assertContains($chave, $todas);
        }
    }

    public function testDoPostLeSomenteChavesDoCatalogo(): void
    {
        $enviadas = ['vOs' => '1', 'eOs' => '1', 'inventada' => '1'];

        $lidas = permissoes_do_post(static fn ($campo) => $enviadas[$campo] ?? null);

        $this->assertSame(permissoes_todas_as_chaves(), array_keys($lidas));
        $this->assertSame(1, $lidas['vOs']);
        $this->assertSame(1, $lidas['eOs']);
        $this->assertSame(0, $lidas['dOs'], 'Campo nao enviado nao pode virar permissao');
    }

    public function testResumoContaAsLiberadas(): void
    {
        $this->assertSame(2, permissoes_resumo(['vOs' => 1, 'eOs' => 1]));
        $this->assertSame(0, permissoes_resumo([]));
        $this->assertSame('2 de ' . count(permissoes_todas_as_chaves()) . ' permissões', permissoes_texto_resumo(['vOs' => 1, 'eOs' => 1]));
    }

    /**
     * A promessa do docblock do helper: chave usada no codigo e nao cadastrada no
     * catalogo quebra a suite. Sem esta varredura, a permissao nova so aparece
     * quando alguem abre a tela e nota que a caixa nao existe.
     */
    public function testChavesUsadasNoCodigoExistemNoCatalogo(): void
    {
        $catalogo = permissoes_todas_as_chaves();

        $padroes = [
            'checkPermission' => '/checkPermission\s*\([^,()]+,\s*[\'"](\w+)[\'"]\s*\)/',
            '$pode' => '/\$pode\s*\(\s*[\'"](\w+)[\'"]\s*\)/',
            '->exigir' => '/->exigir\s*\(\s*[\'"](\w+)[\'"]\s*\)/',
            "'permissao' =>" => '/[\'"]permissao[\'"]\s*=>\s*[\'"](\w+)[\'"]/',
        ];

        // No helper as chaves sao declaracoes, nao usos.
        $ignorados = ['helpers/permissoes_helper.php'];

        $fora = [];
        $lidos = 0;

        $iterador = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(APPPATH, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterador as $arquivo) {
            $caminho = str_replace('\\', '/', $arquivo->getPathname());
            $relativo = str_replace(str_replace('\\', '/', APPPATH), '', $caminho);

            if (strpos($caminho, '/vendor/') !== false || substr($caminho, -4) !== '.php') {
                continue;
            }

            if (in_array($relativo, $ignorados, true)) {
                continue;
            }

            $lidos++;
            $conteudo = (string) file_get_contents($caminho);

            foreach ($padroes as $nome => $regex) {
                preg_match_all($regex, $conteudo, $achados);

                foreach ($achados[1] as $chave) {
                    if (! in_array($chave, $catalogo, true)) {
                        $fora[] = $chave . ' (' . $nome . ' em ' . $relativo . ')';
                    }
                }
            }
        }

        $this->assertGreaterThan(400, $lidos, 'a varredura nao chegou nos fontes');
        $this->assertSame([], $fora, 'chave usada no codigo sem estar no catalogo de permissoes');
    }
}
