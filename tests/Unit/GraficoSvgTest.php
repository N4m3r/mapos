<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Grafico_svg.php';

class GraficoSvgTest extends TestCase
{
    private function xml(string $svg): SimpleXMLElement
    {
        $x = simplexml_load_string($svg);
        $this->assertNotFalse($x, 'SVG mal formado');
        $x->registerXPathNamespace('s', 'http://www.w3.org/2000/svg');

        return $x;
    }

    private function marcas(SimpleXMLElement $x): array
    {
        return $x->xpath('//s:g[@class="gsvg-marca"]');
    }

    public function testColunasBemFormadoEAcessivel(): void
    {
        $svg = Grafico_svg::colunas([
            ['rotulo' => 'seg 01', 'valor' => 3, 'dica' => "01/01\n3 avanços"],
            ['rotulo' => 'ter 02', 'valor' => 12, 'dica' => "02/01\n12 avanços"],
            ['rotulo' => 'qua 03', 'valor' => 0, 'dica' => "03/01\n0 avanços"],
        ], ['titulo' => 'Avanços', 'descricao' => 'Desc']);
        $x = $this->xml($svg);
        $this->assertSame('img', (string) $x['role']);
        $this->assertSame('100%', (string) $x['width']);
        $this->assertNotEmpty((string) $x['viewBox']);
        $this->assertSame('Avanços', (string) $x->title);
        $this->assertSame('Desc', (string) $x->desc);
        $this->assertCount(3, $this->marcas($x));
        $this->assertCount(2, $x->xpath('//s:path[@class="gsvg-barra"]'), 'valor zero nao desenha barra');
        $this->assertStringContainsString('data-dica="02/01&#10;', str_replace("\n", '&#10;', $svg) ?: '');
    }

    public function testVazioNaoQuebra(): void
    {
        $x = $this->xml(Grafico_svg::colunas([]));
        $this->assertCount(0, $this->marcas($x));
        $x = $this->xml(Grafico_svg::colunas([['rotulo' => 'a', 'valor' => 0], ['rotulo' => 'b', 'valor' => 0]]));
        $this->assertCount(2, $this->marcas($x));
        $this->assertCount(0, $x->xpath('//s:path[@class="gsvg-barra"]'));
        $this->xml(Grafico_svg::barrasHorizontais([]));
        $this->xml(Grafico_svg::empilhada100([]));
    }

    public function testEscapeDeTexto(): void
    {
        $mal = '<script>alert("x")</script> & "aspas"';
        $svg = Grafico_svg::barrasHorizontais([['rotulo' => $mal, 'valor' => 2]], ['titulo' => $mal, 'descricao' => $mal]);
        $this->assertStringNotContainsString('<script>', $svg);
        $x = $this->xml($svg);
        $this->assertSame($mal, (string) $x->title);
        $g = $this->marcas($x)[0];
        $this->assertStringContainsString('<script>', (string) $g['data-dica']);
    }

    public function testNumerosPtBr(): void
    {
        $this->assertSame('1.234,5', Grafico_svg::num(1234.5, 1));
        $x = $this->xml(Grafico_svg::barrasHorizontais([['rotulo' => 'Ana', 'valor' => 12.5], ['rotulo' => 'Bia', 'valor' => 3]], ['decimais' => 1, 'sufixo' => ' h']));
        $this->assertStringContainsString('12,5 h', $x->asXML());
        $this->assertCount(2, $this->marcas($x));
        $y = $this->xml(Grafico_svg::colunas([['rotulo' => 'a', 'valor' => 1500]]));
        $this->assertStringContainsString('1.500', $y->asXML());
    }

    public function testEmpilhada100(): void
    {
        $cores = ['infra' => '#86b6ef', 'testado' => '#104281'];
        $segs = [];
        foreach ($cores as $k => $c) {
            $segs[] = ['rotulo' => $k, 'valor' => $k === 'infra' ? 0 : 3, 'cor' => $c];
        }
        $svg = Grafico_svg::empilhada100([
            ['rotulo' => 'Loja A', 'total' => 10, 'texto' => '30% testado · 3 de 10', 'segmentos' => $segs],
            ['rotulo' => 'Loja B', 'total' => 0, 'texto' => 'sem pontos', 'segmentos' => $segs],
        ], ['legenda' => [['rotulo' => 'Previsto', 'cor' => null], ['rotulo' => 'Testado', 'cor' => '#104281']]]);
        $x = $this->xml($svg);
        $this->assertCount(1, $this->marcas($x), 'segmento zero e total zero nao desenham');
        $this->assertStringContainsString('30% testado · 3 de 10', $svg);
        $this->assertStringContainsString('Previsto', $svg);
    }

    public function testEscala(): void
    {
        [$teto, $passo] = Grafico_svg::escala(7);
        $this->assertSame(8.0, $teto);
        $this->assertGreaterThanOrEqual(1.0, $passo);
        $this->assertSame([1.0, 1.0], Grafico_svg::escala(0));
    }
}
