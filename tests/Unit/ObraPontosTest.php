<?php

use PHPUnit\Framework\TestCase;

require_once BASEPATH . 'core/Model.php';
require_once APPPATH . 'models/Obra_pontos_model.php';

/**
 * Pontos de projetos de seguranca eletronica (ADR-013).
 */
class ObraPontosTest extends TestCase
{
    private function ponto(string $tipo, string $status, float $cabo = 0): array
    {
        return ['tipo' => $tipo, 'status' => $status, 'metragem_cabo' => $cabo];
    }

    public function testTiposOrdemEPrefixos(): void
    {
        $t = Obra_pontos_model::tipos();
        $this->assertSame([
            'camera', 'ponto_rede', 'leitor_acesso', 'fechadura', 'botoeira', 'sensor',
            'interfone', 'ap_wifi', 'rack', 'switch', 'nvr', 'outro',
        ], array_keys($t));
        $this->assertSame('CAM', $t['camera']['prefixo']);
        $this->assertSame('PT', $t['outro']['prefixo']);
        $this->assertSame('Câmera', $t['camera']['rotulo']);
        $this->assertArrayHasKey('icone', $t['rack']);
    }

    public function testStatusListaOrdemEPesos(): void
    {
        $s = Obra_pontos_model::statusLista();
        $this->assertSame(['previsto', 'infra', 'cabeado', 'instalado', 'configurado', 'testado'], array_keys($s));
        $this->assertSame([0, 20, 40, 70, 90, 100], array_column($s, 'peso'));
        $this->assertSame('sucesso', $s['testado']['classe']);
        $this->assertSame('Instalei', $s['instalado']['acao']);
    }

    public function testProximoStatus(): void
    {
        $this->assertSame('infra', Obra_pontos_model::proximoStatus('previsto'));
        $this->assertSame('testado', Obra_pontos_model::proximoStatus('configurado'));
        $this->assertNull(Obra_pontos_model::proximoStatus('testado'));
        $this->assertNull(Obra_pontos_model::proximoStatus('xyz'));
    }

    public function testProgresso(): void
    {
        $this->assertNull(Obra_pontos_model::progresso([]));
        $this->assertSame(50.0, Obra_pontos_model::progresso([['status' => 'previsto'], ['status' => 'testado']]));
        $this->assertSame(
            36.7,
            Obra_pontos_model::progresso([(object) ['status' => 'previsto'], (object) ['status' => 'instalado'], (object) ['status' => 'cabeado']])
        );
    }

    public function testResumo(): void
    {
        $r = Obra_pontos_model::resumo([
            $this->ponto('switch', 'testado', 0),
            $this->ponto('camera', 'testado', 30.5),
            (object) ['tipo' => 'camera', 'status' => 'cabeado', 'metragem_cabo' => '20'],
            $this->ponto('camera', 'previsto'),
        ]);
        $this->assertSame(4, $r['total']);
        $this->assertSame(['camera', 'switch'], array_keys($r['por_tipo']));
        $this->assertSame(3, $r['por_tipo']['camera']['total']);
        $this->assertSame(1, $r['por_tipo']['camera']['testados']);
        $this->assertSame('Câmera', $r['por_tipo']['camera']['rotulo']);
        $this->assertSame(['previsto', 'infra', 'cabeado', 'instalado', 'configurado', 'testado'], array_keys($r['por_status']));
        $this->assertSame(2, $r['por_status']['testado']);
        $this->assertSame(1, $r['por_status']['cabeado']);
        $this->assertSame(0, $r['por_status']['infra']);
        $this->assertSame(1, $r['por_tipo']['camera']['por_status']['cabeado']);
        $this->assertSame(50.5, $r['cabo_m']);
        $this->assertSame(60.0, $r['progresso']);
    }

    public function testResumoVazio(): void
    {
        $r = Obra_pontos_model::resumo([]);
        $this->assertSame(0, $r['total']);
        $this->assertSame([], $r['por_tipo']);
        $this->assertNull($r['progresso']);
        $this->assertSame(0.0, $r['cabo_m']);
        $this->assertCount(6, $r['por_status']);
    }

    public function testGerarIdentificacoes(): void
    {
        $this->assertSame(['CAM-01', 'CAM-02', 'CAM-03'], Obra_pontos_model::gerarIdentificacoes('CAM', 1, 3));
        $this->assertSame(['PR-09', 'PR-10'], Obra_pontos_model::gerarIdentificacoes('PR', 9, 2));
        $this->assertSame(['CAM-099', 'CAM-100'], Obra_pontos_model::gerarIdentificacoes('CAM', 99, 2));
    }

    public function testGerarIdentificacoesLimites(): void
    {
        $this->assertCount(1, Obra_pontos_model::gerarIdentificacoes('X', 1, 0));
        $this->assertCount(1, Obra_pontos_model::gerarIdentificacoes('X', 1, -5));
        $this->assertCount(500, Obra_pontos_model::gerarIdentificacoes('X', 1, 9999));
    }

    public function testEtapasPadraoCftv(): void
    {
        $e = Obra_pontos_model::etapasPadrao('CFTV IP');
        $this->assertCount(7, $e);
        $this->assertSame('Infraestrutura (eletroduto/eletrocalha)', $e[0]);
        $this->assertContains('Configuração do NVR e acesso remoto', $e);
        $this->assertSame($e, Obra_pontos_model::etapasPadrao('Instalação de Câmeras'));
    }

    public function testEtapasPadraoAcessoComAcento(): void
    {
        $e = Obra_pontos_model::etapasPadrao('Controle de Acesso');
        $this->assertCount(7, $e);
        $this->assertContains('Instalação e configuração da controladora', $e);
        // Maiusculas acentuadas tambem casam com "camera"
        $this->assertContains('Instalação das câmeras', Obra_pontos_model::etapasPadrao('CÂMERAS'));
    }

    public function testEtapasPadraoRedeAlarmeEGenerico(): void
    {
        $rede = Obra_pontos_model::etapasPadrao('Rede estruturada');
        $this->assertContains('Certificação dos pontos', $rede);
        $this->assertSame('Entrega', end($rede));
        $this->assertContains('Instalação de sensores e central', Obra_pontos_model::etapasPadrao('ALARME'));
        $this->assertSame(
            ['Infraestrutura', 'Passagem de cabos', 'Instalação', 'Configuração', 'Testes', 'Entrega'],
            Obra_pontos_model::etapasPadrao('Interfonia')
        );
        $this->assertSame(Obra_pontos_model::etapasPadrao(''), Obra_pontos_model::etapasPadrao('xyz'));
    }

    public function testEtapasPadraoCombinadoSemRepeticao(): void
    {
        $e = Obra_pontos_model::etapasPadrao('Rede estruturada + CFTV IP');
        $this->assertSame($e, array_values(array_unique($e)));
        $this->assertSame(1, count(array_keys($e, 'Passagem de cabos', true)));
        $this->assertSame('Infraestrutura (eletroduto/eletrocalha)', $e[0]);
        $this->assertContains('Certificação dos pontos', $e);
        $this->assertContains('Entrega', $e);
        $this->assertContains('Entrega e treinamento', $e);
    }

    public function testAguardandoApos(): void
    {
        $this->assertSame('infra', Obra_pontos_model::aguardandoApos('previsto'));
        $this->assertSame('configurado', Obra_pontos_model::aguardandoApos('instalado'));
        $this->assertSame('testado', Obra_pontos_model::aguardandoApos('configurado'));
        $this->assertNull(Obra_pontos_model::aguardandoApos('testado'));
        $this->assertNull(Obra_pontos_model::aguardandoApos('xyz'));
    }

    public function testRotuloAguardando(): void
    {
        $this->assertSame('Aguardando infraestrutura', Obra_pontos_model::rotuloAguardando('infra'));
        $this->assertSame('Aguardando passagem de cabo', Obra_pontos_model::rotuloAguardando('cabeado'));
        $this->assertSame('Aguardando instalação', Obra_pontos_model::rotuloAguardando('instalado'));
        $this->assertSame('Aguardando configuração', Obra_pontos_model::rotuloAguardando('configurado'));
        $this->assertSame('Aguardando teste', Obra_pontos_model::rotuloAguardando('testado'));
        $this->assertSame('', Obra_pontos_model::rotuloAguardando(null));
        $this->assertSame('', Obra_pontos_model::rotuloAguardando('outro'));
        $this->assertSame('', Obra_pontos_model::rotuloAguardando(''));
    }

    public function testExigeFoto(): void
    {
        $this->assertTrue(Obra_pontos_model::exigeFoto('instalado'));
        foreach (['previsto', 'infra', 'cabeado', 'configurado', 'testado', ''] as $st) {
            $this->assertFalse(Obra_pontos_model::exigeFoto($st), $st);
        }
    }

    public function testFotoValidaFormatos(): void
    {
        $this->assertSame('image/jpeg', Obra_pontos_model::fotoValida("\xFF\xD8\xFF\xE0" . str_repeat("\0", 20)));
        $this->assertSame('image/png', Obra_pontos_model::fotoValida("\x89PNG\r\n\x1A\n" . str_repeat("\0", 20)));
        $this->assertSame('image/webp', Obra_pontos_model::fotoValida('RIFF' . pack('V', 20) . 'WEBP' . str_repeat("\0", 20)));
    }

    public function testFotoValidaRecusa(): void
    {
        $this->assertNull(Obra_pontos_model::fotoValida('isto nao e uma imagem, apenas texto'));
        $this->assertNull(Obra_pontos_model::fotoValida(''));
        $this->assertNull(Obra_pontos_model::fotoValida('RIFF' . pack('V', 20) . 'WAVE' . str_repeat("\0", 20)));
        $this->assertNull(Obra_pontos_model::fotoValida("\x89PNG" . str_repeat("\0", 20)));
    }

    public function testFotoValidaLimiteDe4Mb(): void
    {
        $limite = 4 * 1024 * 1024;
        $cab = "\xFF\xD8\xFF\xE0";
        $this->assertSame('image/jpeg', Obra_pontos_model::fotoValida($cab . str_repeat('a', $limite - 4)));
        $this->assertNull(Obra_pontos_model::fotoValida($cab . str_repeat('a', $limite - 3)));
    }
}
