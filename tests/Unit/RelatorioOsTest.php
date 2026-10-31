<?php

use PHPUnit\Framework\TestCase;

require_once BASEPATH . 'core/Model.php';
require_once APPPATH . 'models/Relatorio_os_model.php';

/**
 * Consolidacao pura do relatorio de OS realizadas.
 */
class RelatorioOsTest extends TestCase
{
    private function l(int $os, string $ent, ?string $sai, int $u = 1, string $status = 'Aberto'): array
    {
        return ['idOs' => $os, 'cliente' => 'Cli ' . $os, 'status' => $status, 'descricao' => 'd', 'entrada' => $ent, 'saida' => $sai,
            'usuario_id' => $u, 'tecnico' => 'Tec ' . $u, 'observacao' => null];
    }

    public function testPeriodoDelegaAoProjetos(): void
    {
        $this->assertSame(Projeto_acompanhamento_model::periodo('semanal', '2026-10-29'), Relatorio_os_model::periodo('semanal', '2026-10-29'));
    }

    public function testHoras(): void
    {
        $this->assertSame(1.5, Relatorio_os_model::horasEntre('2026-10-26 08:00:00', '2026-10-26 09:30:00'));
        $this->assertSame(0.0, Relatorio_os_model::horasEntre('2026-10-26 08:00:00', null));
        $this->assertSame(0.0, Relatorio_os_model::horasEntre('2026-10-26 10:00:00', '2026-10-26 09:00:00'));
    }

    public function testDescricaoCurta(): void
    {
        $this->assertSame('defeito x', Relatorio_os_model::descricaoCurta('  ', "defeito\n x"));
        $this->assertSame(120, mb_strlen(Relatorio_os_model::descricaoCurta(str_repeat('a', 300), null), 'UTF-8'));
    }

    public function testAgruparOsEConcluida(): void
    {
        $linhas = [
            $this->l(2, '2026-10-27 10:00:00', null),
            $this->l(1, '2026-10-26 08:00:00', '2026-10-26 10:00:00', 1, 'Finalizado'),
            $this->l(1, '2026-10-27 08:00:00', '2026-10-27 09:00:00', 2, 'Finalizado'),
        ];
        $os = Relatorio_os_model::agruparOs($linhas, [1 => 3], '2026-10-26', '2026-10-30');
        $this->assertCount(2, $os);
        $this->assertSame(1, $os[0]['idOs']);
        $this->assertSame(3.0, $os[0]['horas']);
        $this->assertSame(3, $os[0]['fotos']);
        $this->assertTrue($os[0]['concluida']);
        $this->assertTrue($os[0]['finalizada']);
        $this->assertFalse($os[1]['concluida']);
        $this->assertNull($os[1]['atendimentos'][0]['saida']);
    }

    public function testSaidaForaDoPeriodoNaoConclui(): void
    {
        $linhas = [$this->l(1, '2026-10-30 22:00:00', '2026-10-31 01:00:00')];
        $os = Relatorio_os_model::agruparOs($linhas, [], '2026-10-26', '2026-10-30');
        $this->assertFalse($os[0]['concluida']);
        $t = Relatorio_os_model::totais($linhas, $os, 0, 0, '2026-10-26', '2026-10-30');
        $this->assertSame(0, $t['concluidos']);
        $this->assertSame(3.0, $t['horas']);
    }

    public function testTotaisETecnicos(): void
    {
        $linhas = [
            $this->l(1, '2026-10-26 08:00:00', '2026-10-26 10:00:00', 1, 'Faturado'),
            $this->l(2, '2026-10-26 08:00:00', '2026-10-26 09:00:00', 2),
            $this->l(3, '2026-10-27 08:00:00', null, 2),
        ];
        $os = Relatorio_os_model::agruparOs($linhas, [], '2026-10-26', '2026-10-30');
        $t = Relatorio_os_model::totais($linhas, $os, 5, 2, '2026-10-26', '2026-10-30');
        $this->assertSame(['os_atendidas' => 3, 'atendimentos' => 3, 'concluidos' => 2, 'horas' => 3.0, 'fotos' => 5,
            'finalizadas' => 1, 'nao_realizadas' => 2, 'tecnicos' => 2], $t);
        $tec = Relatorio_os_model::resumoTecnicos($linhas);
        $this->assertSame(1, $tec[0]['usuario_id']);
        $this->assertSame(2, $tec[1]['atendimentos']);
    }

    public function testPorDiaPreencheZeros(): void
    {
        $linhas = [$this->l(1, '2026-10-27 08:00:00', '2026-10-27 09:00:00')];
        $d = Relatorio_os_model::porDia($linhas, ['2026-10-28' => 2], '2026-10-26', '2026-10-28');
        $this->assertCount(3, $d);
        $this->assertSame(0, $d[0]['atendimentos']);
        $this->assertSame(['dia' => '2026-10-27', 'atendimentos' => 1, 'concluidos' => 1, 'horas' => 1.0, 'fotos' => 0], $d[1]);
        $this->assertSame(2, $d[2]['fotos']);
    }

    public function testStatusOsOrdenaPorTotal(): void
    {
        $os = [['status' => 'Aberto'], ['status' => 'Finalizado'], ['status' => 'Finalizado']];
        $s = Relatorio_os_model::statusOs($os);
        $this->assertSame(['status' => 'Finalizado', 'total' => 2], $s[0]);
    }

    public function testHorasPorTecnicoTop10ComOutros(): void
    {
        $tec = [];
        for ($i = 1; $i <= 12; $i++) {
            $tec[] = ['usuario_id' => $i, 'nome' => 'T' . $i, 'atendimentos' => 1, 'horas' => (float) $i];
        }
        $r = Relatorio_os_model::horasPorTecnico($tec);
        $this->assertCount(11, $r);
        $this->assertSame(12, $r[0]['usuario_id']);
        $this->assertSame(0, $r[10]['usuario_id']);
        $this->assertSame(3.0, $r[10]['horas']);
        $this->assertSame(2, $r[10]['atendimentos']);
    }

    public function testAgruparFotosLimiteERestantes(): void
    {
        $fotos = [];
        for ($i = 1; $i <= 5; $i++) {
            $fotos[] = ['idFoto' => $i, 'idOs' => 7, 'cliente' => 'C', 'etapa' => 'durante', 'descricao' => '', 'tecnico' => 'Ana', 'data' => '2026-10-2' . $i . ' 10:00:00'];
        }
        $url = function (int $id) {
            return 'f/' . $id;
        };
        $g = Relatorio_os_model::agruparFotos($fotos, 2, $url);
        $this->assertSame(5, $g[0]['total']);
        $this->assertSame(3, $g[0]['restantes']);
        $this->assertSame('f/5', $g[0]['itens'][0]['url']);
        $this->assertSame('Durante · Ana · 25/10/2026 10:00', $g[0]['itens'][0]['legenda']);
        $this->assertCount(5, Relatorio_os_model::agruparFotos($fotos, null, $url)[0]['itens']);
    }
}
