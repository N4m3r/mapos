<?php

use PHPUnit\Framework\TestCase;

require_once BASEPATH . 'core/Model.php';
require_once APPPATH . 'models/Obra_pontos_model.php';
require_once APPPATH . 'libraries/Pontos_importacao.php';

/**
 * Importacao de pontos por planilha: logica pura.
 */
class PontosImportacaoTest extends TestCase
{
    public function testMapearCabecalhoFlexivel(): void
    {
        $m = Pontos_importacao::mapearCabecalho(['Nome', 'TIPO', 'Série', 'Observação', 'IP', 'xyz']);
        $this->assertSame(['identificacao' => 0, 'tipo' => 1, 'serie' => 2, 'observacao' => 3, 'ip' => 4], $m);
    }

    public function testSeparadorEEncoding(): void
    {
        $this->assertSame(';', Pontos_importacao::detectarSeparador("a;b;c\n1;2;3"));
        $this->assertSame(',', Pontos_importacao::detectarSeparador("a,b,c\n1,2,3"));
        $this->assertSame("Câmera", Pontos_importacao::paraUtf8("\xEF\xBB\xBFCâmera"));
        $this->assertSame("Câmera", Pontos_importacao::paraUtf8("C\xE2mera"));
    }

    public function testNormalizarTipo(): void
    {
        $t = Obra_pontos_model::tipos();
        $this->assertSame('camera', Pontos_importacao::normalizarTipo('', $t));
        $this->assertSame('camera', Pontos_importacao::normalizarTipo('Câmera', $t));
        $this->assertSame('ponto_rede', Pontos_importacao::normalizarTipo('ponto_rede', $t));
        $this->assertSame('nvr', Pontos_importacao::normalizarTipo('NVR / DVR', $t));
        $this->assertNull(Pontos_importacao::normalizarTipo('foguete', $t));
    }

    public function testLerCsvLatin1ComPontoEVirgula(): void
    {
        $csv = "Identifica\xE7\xE3o;Tipo;IP\r\nCAM-01;C\xE2mera;10.0.0.1\r\n\r\nCAM-02;;\r\n";
        $r = Pontos_importacao::lerCsv($csv);
        $this->assertNull($r['erro']);
        $this->assertCount(2, $r['linhas']);
        $this->assertSame('CAM-01', $r['linhas'][0]['identificacao']);
        $this->assertSame('Câmera', $r['linhas'][0]['tipo']);
        $this->assertSame(4, $r['linhas'][1]['_n']);
    }

    public function testCabecalhoSemIdentificacao(): void
    {
        $r = Pontos_importacao::lerCsv("tipo;ip\ncamera;1.1.1.1");
        $this->assertNotNull($r['erro']);
    }

    public function testLimiteDeLinhas(): void
    {
        $csv = "id\n" . implode("\n", range(1, 1001));
        $this->assertNotNull(Pontos_importacao::lerCsv($csv)['erro']);
    }

    public function testValidarLinha(): void
    {
        $t = Obra_pontos_model::tipos();
        $ok = Pontos_importacao::validarLinha(['identificacao' => ' CAM-01 ', 'tipo' => '', 'ip' => '10.0.0.1'], $t);
        $this->assertNull($ok['erro']);
        $this->assertSame('camera', $ok['dados']['tipo']);
        $this->assertTrue($ok['tipo_vazio']);
        $this->assertSame('CAM-01', $ok['dados']['identificacao']);
        $this->assertNotNull(Pontos_importacao::validarLinha(['identificacao' => ''], $t)['erro']);
        $this->assertNotNull(Pontos_importacao::validarLinha(['identificacao' => 'A', 'tipo' => 'x'], $t)['erro']);
        $this->assertNotNull(Pontos_importacao::validarLinha(['identificacao' => 'A', 'ip' => '999.1.1.1'], $t)['erro']);
        $long = Pontos_importacao::validarLinha(['identificacao' => str_repeat('a', 50)], $t);
        $this->assertSame(30, mb_strlen($long['dados']['identificacao']));
    }

    private function linha(int $n, string $ident, string $ip = ''): array
    {
        $v = Pontos_importacao::validarLinha(['identificacao' => $ident, 'ip' => $ip], Obra_pontos_model::tipos());
        $v['_n'] = $n;

        return $v;
    }

    public function testClassificar(): void
    {
        $exist = [['idPonto' => 5, 'identificacao' => 'CAM-01', 'ip' => '10.0.0.1'], ['idPonto' => 6, 'identificacao' => 'CAM-02', 'ip' => '10.0.0.2']];
        $linhas = [
            $this->linha(2, 'cam-01', '10.0.0.1'),   // existe, mesmo IP: atualizar
            $this->linha(3, 'CAM-10', '10.0.0.2'),   // IP de outro ponto: erro
            $this->linha(4, 'CAM-11', '10.0.0.9'),   // novo
            $this->linha(5, 'CAM-12', '10.0.0.9'),   // IP repetido na planilha
            $this->linha(6, 'CAM-11'),               // identificacao repetida
            $this->linha(7, ''),                     // erro de validacao
        ];
        $r = Pontos_importacao::classificar($linhas, $exist, 'atualizar');
        $this->assertSame(['atualizar', 'erro', 'novo', 'erro', 'erro', 'erro'], array_column($r, 'status'));
        $this->assertSame(5, $r[0]['idPonto']);
        $p = Pontos_importacao::classificar($linhas, $exist, 'pular');
        $this->assertSame('pular', $p[0]['status']);
        $this->assertSame(['novo' => 1, 'atualizar' => 0, 'pular' => 1, 'erro' => 4], Pontos_importacao::contar($p));
    }

    public function testModeloCsvTemCabecalhoEDuasLinhas(): void
    {
        $r = Pontos_importacao::lerCsv(Pontos_importacao::modeloCsv());
        $this->assertNull($r['erro']);
        $this->assertCount(2, $r['linhas']);
    }
}
