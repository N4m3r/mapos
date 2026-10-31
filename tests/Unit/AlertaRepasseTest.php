<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/');
}
require_once APPPATH . 'libraries/Alerta_repasse.php';

class AlertaRepasseTest extends TestCase
{
    private const AGORA = 1800000000;

    private function ponto(array $o = [])
    {
        return $o + [
            'idPonto' => 1, 'obra_id' => 10, 'obra_nome' => 'Loja X', 'obra_status' => 'em_execucao', 'obra_responsavel_id' => 5,
            'identificacao' => 'CAM-01', 'responsavel_id' => 7, 'aguardando' => 'instalacao', 'status' => 'planejado',
            'repassado_em' => date('Y-m-d H:i:s', self::AGORA - 30 * 3600), 'atualizado_em' => null,
            'alertado_em' => null, 'alertado_gestor_em' => null,
        ];
    }

    public function testRespeitaLimiteDeHoras()
    {
        $novo = $this->ponto(['repassado_em' => date('Y-m-d H:i:s', self::AGORA - 3600)]);
        $this->assertCount(1, Alerta_repasse::paraTecnico([$this->ponto()], self::AGORA, 24));
        $this->assertCount(0, Alerta_repasse::paraTecnico([$novo], self::AGORA, 24));
    }

    public function testUsaAtualizadoEmQuandoSemRepasse()
    {
        $p = $this->ponto(['repassado_em' => null, 'atualizado_em' => date('Y-m-d H:i:s', self::AGORA - 48 * 3600)]);
        $this->assertCount(1, Alerta_repasse::paraTecnico([$p], self::AGORA, 24));
        $this->assertCount(0, Alerta_repasse::paraTecnico([$this->ponto(['repassado_em' => null])], self::AGORA, 24));
    }

    public function testIgnoraSemResponsavelTestadoEProjetoEncerrado()
    {
        $this->assertCount(0, Alerta_repasse::paraTecnico([
            $this->ponto(['responsavel_id' => null]),
            $this->ponto(['status' => 'testado']),
            $this->ponto(['aguardando' => null]),
            $this->ponto(['obra_status' => 'cancelada']),
        ], self::AGORA, 24));
    }

    public function testNaoRepeteDentroDaJanela()
    {
        $recente = $this->ponto(['alertado_em' => date('Y-m-d H:i:s', self::AGORA - 5 * 3600)]);
        $velho = $this->ponto(['alertado_em' => date('Y-m-d H:i:s', self::AGORA - 25 * 3600)]);
        $this->assertCount(0, Alerta_repasse::paraTecnico([$recente], self::AGORA, 24));
        $this->assertCount(1, Alerta_repasse::paraTecnico([$velho], self::AGORA, 24));
    }

    public function testGestorSoApos2N()
    {
        $this->assertCount(0, Alerta_repasse::paraGestor([$this->ponto()], self::AGORA, 24));
        $p = $this->ponto(['repassado_em' => date('Y-m-d H:i:s', self::AGORA - 50 * 3600)]);
        $this->assertCount(1, Alerta_repasse::paraGestor([$p], self::AGORA, 24));
        $p['alertado_gestor_em'] = date('Y-m-d H:i:s', self::AGORA - 3600);
        $this->assertCount(0, Alerta_repasse::paraGestor([$p], self::AGORA, 24));
        $semGestor = $this->ponto(['obra_responsavel_id' => null, 'repassado_em' => '2000-01-01 00:00:00']);
        $this->assertCount(0, Alerta_repasse::paraGestor([$semGestor], self::AGORA, 24));
    }

    public function testAgrupaEMontaMensagem()
    {
        $pontos = [$this->ponto(), $this->ponto(['idPonto' => 2, 'identificacao' => 'CAM-04']), $this->ponto(['idPonto' => 3, 'responsavel_id' => 8])];
        $g = Alerta_repasse::agrupar($pontos, 'responsavel_id');
        $this->assertSame([7, 8], array_keys($g));
        $msg = Alerta_repasse::mensagemTecnico('Joao Silva', $g[7], 'http://x/tecnico/fila', 24);
        $this->assertStringContainsString('Olá, Joao!', $msg);
        $this->assertStringContainsString('2 pontos parados no projeto Loja X: CAM-01, CAM-04.', $msg);
        $this->assertStringContainsString('http://x/tecnico/fila', $msg);
        $this->assertStringContainsString('1 ponto parado', Alerta_repasse::mensagemGestor('Ana', $g[8], 24));
    }

    public function testHorasConfig()
    {
        $this->assertSame(24, Alerta_repasse::horasConfig(null));
        $this->assertSame(24, Alerta_repasse::horasConfig('0'));
        $this->assertSame(12, Alerta_repasse::horasConfig('12'));
    }
}
