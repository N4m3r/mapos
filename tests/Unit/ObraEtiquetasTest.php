<?php

use PHPUnit\Framework\TestCase;

require_once BASEPATH . 'core/Model.php';
require_once APPPATH . 'models/Obra_pontos_model.php';
require_once APPPATH . 'libraries/Projeto_csv.php';
require_once APPPATH . 'libraries/Obra_etiquetas.php';

class ObraEtiquetasTest extends TestCase
{
    public function testUrlPonto(): void
    {
        $this->assertSame('http://x/mapos/tecnico/projeto/5#ponto-9', Obra_etiquetas::urlPonto('http://x/mapos/', 5, 9));
    }

    public function testFiltrar(): void
    {
        $ps = [
            (object) ['idPonto' => 1, 'tipo' => 'camera'],
            (object) ['idPonto' => 2, 'tipo' => 'rack'],
            (object) ['idPonto' => 3, 'tipo' => 'camera'],
        ];
        $this->assertCount(3, Obra_etiquetas::filtrar($ps));
        $this->assertCount(2, Obra_etiquetas::filtrar($ps, 'camera'));
        $this->assertCount(1, Obra_etiquetas::filtrar($ps, 'camera', ['3', 2]));
    }

    public function testLinhasECsv(): void
    {
        $p = (object) ['idPonto' => 1, 'identificacao' => '=CAM-01', 'tipo' => 'camera', 'local' => 'Hall',
            'ip' => '10.0.0.5', 'canal' => '5', 'porta' => '', 'modelo' => 'X', 'serie' => 'S1',
            'metragem_cabo' => 12.5, 'status' => 'testado', 'responsavel_nome' => 'Ana', 'atualizado_em' => '2026-10-02 10:00:00'];
        $l = Obra_etiquetas::linhasAsbuilt([$p], Obra_pontos_model::tipos(), Obra_pontos_model::statusLista());
        $this->assertSame('Câmera', $l[0][1]);
        $this->assertSame('12,50', $l[0][8]);
        $this->assertSame('Testado / certificado', $l[0][9]);
        $this->assertSame('02/10/2026', $l[0][11]);
        $csv = Obra_etiquetas::csv($l);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString("'=CAM-01;", $csv);
    }

    public function testQrDataUri(): void
    {
        $this->assertStringStartsWith('data:image/svg+xml', Obra_etiquetas::qrDataUri('http://x/a'));
    }
}
