<?php

use PHPUnit\Framework\TestCase;

require_once BASEPATH . 'core/Model.php';
require_once APPPATH . 'models/Projeto_acompanhamento_model.php';

/**
 * Periodo e saude dos projetos no acompanhamento (ADR-012).
 */
class ProjetoAcompanhamentoTest extends TestCase
{
    private const HOJE = '2026-10-29';

    private function periodo(string $tipo, string $ref): array
    {
        return Projeto_acompanhamento_model::periodo($tipo, $ref);
    }

    private function saude(array $sobrescrever): array
    {
        return Projeto_acompanhamento_model::saude(array_merge([
            'status' => 'em_execucao',
            'percentual_concluido' => 50.0,
            'data_inicio_prevista' => null,
            'data_fim_prevista' => null,
            'ultimo_registro' => self::HOJE,
            'etapas_atrasadas' => 0,
            'ocorrencias_7d' => 0,
        ], $sobrescrever), self::HOJE);
    }

    /* ---------------------------- periodo ---------------------------- */

    public function testDiario(): void
    {
        $p = $this->periodo('diario', '2026-10-29');
        $this->assertSame('diario', $p['tipo']);
        $this->assertSame('2026-10-29', $p['inicio']);
        $this->assertSame('2026-10-29', $p['fim']);
        $this->assertSame('29/10/2026', $p['rotulo']);
        $this->assertSame('2026-10-28', $p['anterior']);
        $this->assertSame('2026-10-30', $p['proximo']);
    }

    public function testSemanalComecaNaSegunda(): void
    {
        $p = $this->periodo('semanal', '2026-10-29'); // quinta
        $this->assertSame('2026-10-26', $p['inicio']);
        $this->assertSame('2026-11-01', $p['fim']);
        $this->assertSame('Semana de 26/10 a 01/11/2026', $p['rotulo']);
        $this->assertSame('2026-10-19', $p['anterior']);
        $this->assertSame('2026-11-02', $p['proximo']);
    }

    public function testSemanalNaSegundaEnoDomingo(): void
    {
        $this->assertSame('2026-10-26', $this->periodo('semanal', '2026-10-26')['inicio']);
        $domingo = $this->periodo('semanal', '2026-11-01');
        $this->assertSame('2026-10-26', $domingo['inicio']);
        $this->assertSame('2026-11-01', $domingo['fim']);
    }

    public function testSemanalComViradaDeAno(): void
    {
        $p = $this->periodo('semanal', '2026-12-31');
        $this->assertSame('2026-12-28', $p['inicio']);
        $this->assertSame('2027-01-03', $p['fim']);
        $this->assertSame('Semana de 28/12 a 03/01/2027', $p['rotulo']);
        $this->assertSame('2027-01-04', $p['proximo']);
    }

    public function testMensal(): void
    {
        $p = $this->periodo('mensal', '2026-10-29');
        $this->assertSame('2026-10-01', $p['inicio']);
        $this->assertSame('2026-10-31', $p['fim']);
        $this->assertSame('Outubro/2026', $p['rotulo']);
        $this->assertSame('2026-09-01', $p['anterior']);
        $this->assertSame('2026-11-01', $p['proximo']);
    }

    public function testMensalFevereiroBissextoEViradaDeAno(): void
    {
        $fev = $this->periodo('mensal', '2028-02-10');
        $this->assertSame('2028-02-29', $fev['fim']);
        $this->assertSame('Fevereiro/2028', $fev['rotulo']);

        $dez = $this->periodo('mensal', '2026-12-31');
        $this->assertSame('2027-01-01', $dez['proximo']);
        $this->assertSame('Dezembro/2026', $dez['rotulo']);

        $mar = $this->periodo('mensal', '2026-03-31');
        $this->assertSame('2026-02-01', $mar['anterior']);
    }

    public function testTipoInvalidoViraSemanal(): void
    {
        $p = $this->periodo('anual', '2026-10-29');
        $this->assertSame('semanal', $p['tipo']);
        $this->assertSame('2026-10-26', $p['inicio']);
    }

    public function testDataInvalidaViraHoje(): void
    {
        $hoje = date('Y-m-d');
        foreach (['', 'abc', '2026-02-30', '29/10/2026'] as $ruim) {
            $p = $this->periodo('diario', $ruim);
            $this->assertSame($hoje, $p['inicio'], 'data: ' . $ruim);
            $this->assertSame($hoje, $p['fim']);
        }
    }

    /* ----------------------------- saude ----------------------------- */

    public function testStatusNeutros(): void
    {
        foreach (['concluida' => 'Concluído', 'entregue' => 'Entregue', 'cancelada' => 'Cancelado'] as $status => $rotulo) {
            $r = $this->saude(['status' => $status, 'ultimo_registro' => null, 'data_fim_prevista' => '2020-01-01']);
            $this->assertSame('neutro', $r['nivel']);
            $this->assertSame($rotulo, $r['rotulo']);
            $this->assertSame([], $r['motivos']);
        }
    }

    public function testParalisado(): void
    {
        $r = $this->saude(['status' => 'paralisada', 'ultimo_registro' => null]);
        $this->assertSame('parado', $r['nivel']);
        $this->assertSame('Paralisado', $r['rotulo']);
        $this->assertSame(['Projeto paralisado'], $r['motivos']);
    }

    public function testPlanejamentoComInicioPassado(): void
    {
        $r = $this->saude(['status' => 'planejamento', 'data_inicio_prevista' => '2026-10-15']);
        $this->assertSame('atencao', $r['nivel']);
        $this->assertSame(['Início previsto em 15/10 não aconteceu'], $r['motivos']);
    }

    public function testPlanejamentoSemAtraso(): void
    {
        foreach ([null, '2026-10-29', '2026-12-01'] as $inicio) {
            $r = $this->saude(['status' => 'planejamento', 'data_inicio_prevista' => $inicio, 'ultimo_registro' => null]);
            $this->assertSame('ok', $r['nivel']);
            $this->assertSame('Em planejamento', $r['rotulo']);
            $this->assertSame([], $r['motivos']);
        }
    }

    public function testExecucaoEmDia(): void
    {
        $r = $this->saude([]);
        $this->assertSame('ok', $r['nivel']);
        $this->assertSame('Em dia', $r['rotulo']);
        $this->assertSame([], $r['motivos']);
        $this->assertNull($r['esperado']);
    }

    public function testPrazoVencido(): void
    {
        $r = $this->saude(['data_fim_prevista' => '2026-10-19']);
        $this->assertSame('critico', $r['nivel']);
        $this->assertSame('Crítico', $r['rotulo']);
        $this->assertContains('Prazo vencido há 10 dias', $r['motivos']);
    }

    public function testFimPrevistoHojeNaoVenceu(): void
    {
        $r = $this->saude(['data_fim_prevista' => self::HOJE, 'percentual_concluido' => 100]);
        $this->assertSame('ok', $r['nivel']);
    }

    public function testAvancoAbaixoDoPrevisto(): void
    {
        // 2026-10-01 a 2026-11-10 = 40 dias; em 29/10 passaram 28 (70%)
        $base = ['data_inicio_prevista' => '2026-10-01', 'data_fim_prevista' => '2026-11-10'];

        $critico = $this->saude($base + ['percentual_concluido' => 40]);
        $this->assertSame('critico', $critico['nivel']);
        $this->assertSame(70.0, $critico['esperado']);
        $this->assertSame(['Avanço 30 pontos abaixo do previsto'], $critico['motivos']);

        $atencao = $this->saude($base + ['percentual_concluido' => 55]);
        $this->assertSame('atencao', $atencao['nivel']);
        $this->assertSame(['Avanço 15 pontos abaixo do previsto'], $atencao['motivos']);

        $ok = $this->saude($base + ['percentual_concluido' => 65]);
        $this->assertSame('ok', $ok['nivel']);
    }

    public function testLimitesDoAvanco(): void
    {
        // 100 dias; em 29/10 passaram 50 (50%)
        $base = ['data_inicio_prevista' => '2026-09-09', 'data_fim_prevista' => '2026-12-18'];
        $this->assertSame(50.0, $this->saude($base)['esperado']);
        $this->assertSame('critico', $this->saude($base + ['percentual_concluido' => 30])['nivel']);
        $this->assertSame('atencao', $this->saude($base + ['percentual_concluido' => 31])['nivel']);
        $this->assertSame('atencao', $this->saude($base + ['percentual_concluido' => 40])['nivel']);
        $this->assertSame('ok', $this->saude($base + ['percentual_concluido' => 41])['nivel']);
    }

    public function testEsperadoFicaEntreZeroECem(): void
    {
        $antes = $this->saude(['data_inicio_prevista' => '2026-12-01', 'data_fim_prevista' => '2026-12-31']);
        $this->assertSame(0.0, $antes['esperado']);
        $depois = $this->saude(['data_inicio_prevista' => '2026-01-01', 'data_fim_prevista' => '2026-02-01']);
        $this->assertSame(100.0, $depois['esperado']);
    }

    public function testNenhumRegistro(): void
    {
        $r = $this->saude(['ultimo_registro' => null]);
        $this->assertSame('critico', $r['nivel']);
        $this->assertSame(['Nenhum registro de execução'], $r['motivos']);
    }

    public function testSemRegistroHaMaisDeSeteDias(): void
    {
        $r = $this->saude(['ultimo_registro' => '2026-10-21']); // 8 dias
        $this->assertSame('critico', $r['nivel']);
        $this->assertSame(['Sem registro há 8 dias'], $r['motivos']);
        $this->assertSame('atencao', $this->saude(['ultimo_registro' => '2026-10-22'])['nivel']); // 7 dias
    }

    public function testSemRegistroHaMaisDeDoisDias(): void
    {
        $r = $this->saude(['ultimo_registro' => '2026-10-26']); // 3 dias
        $this->assertSame('atencao', $r['nivel']);
        $this->assertSame(['Sem registro há 3 dias'], $r['motivos']);
        $this->assertSame('ok', $this->saude(['ultimo_registro' => '2026-10-27'])['nivel']); // 2 dias
    }

    public function testEtapasAtrasadas(): void
    {
        $r = $this->saude(['etapas_atrasadas' => 2]);
        $this->assertSame('atencao', $r['nivel']);
        $this->assertSame(['2 etapa(s) atrasada(s)'], $r['motivos']);
    }

    public function testOcorrencias(): void
    {
        $r = $this->saude(['ocorrencias_7d' => 3]);
        $this->assertSame('atencao', $r['nivel']);
        $this->assertSame(['3 ocorrência(s) nos últimos 7 dias'], $r['motivos']);
    }

    public function testPiorNivelVenceESomaMotivos(): void
    {
        $r = $this->saude(['etapas_atrasadas' => 1, 'ocorrencias_7d' => 1, 'ultimo_registro' => null]);
        $this->assertSame('critico', $r['nivel']);
        $this->assertSame('Crítico', $r['rotulo']);
        $this->assertSame([
            'Nenhum registro de execução',
            '1 etapa(s) atrasada(s)',
            '1 ocorrência(s) nos últimos 7 dias',
        ], $r['motivos']);
    }

    public function testStatusDesconhecidoSeguePelasRegrasDeExecucao(): void
    {
        $r = $this->saude(['status' => 'outro', 'ultimo_registro' => null]);
        $this->assertSame('critico', $r['nivel']);
    }

    /* ------------------------- tempoMedioHoras ------------------------- */

    public function testTempoMedioVazioEhNulo(): void
    {
        $this->assertNull(Projeto_acompanhamento_model::tempoMedioHoras([]));
    }

    public function testTempoMedioUmParDeDuasHoras(): void
    {
        $this->assertSame(2.0, Projeto_acompanhamento_model::tempoMedioHoras([
            ['2026-10-29 08:00:00', '2026-10-29 10:00:00'],
        ]));
    }

    public function testTempoMedioIgnoraFimAntesDoInicio(): void
    {
        $this->assertSame(1.0, Projeto_acompanhamento_model::tempoMedioHoras([
            ['2026-10-29 10:00:00', '2026-10-29 08:00:00'], // invertido
            ['2026-10-29 08:00:00', '2026-10-29 09:00:00'],
        ]));
        $this->assertNull(Projeto_acompanhamento_model::tempoMedioHoras([
            ['2026-10-29 10:00:00', '2026-10-29 08:00:00'],
        ]));
    }

    public function testTempoMedioIgnoraDatasInvalidas(): void
    {
        $this->assertSame(3.0, Projeto_acompanhamento_model::tempoMedioHoras([
            ['', '2026-10-29 10:00:00'],
            ['2026-10-29', '2026-10-29 10:00:00'],   // sem hora
            ['2026-02-30 08:00:00', '2026-03-01 08:00:00'],
            ['abc', 'def'],
            [null, null],
            ['2026-10-29 07:00:00', '2026-10-29 10:00:00'],
        ]));
        $this->assertNull(Projeto_acompanhamento_model::tempoMedioHoras([['x', 'y']]));
    }

    public function testTempoMedioArredondaUmaCasa(): void
    {
        // 1h, 1h e 2h: média 1,333... -> 1,3
        $this->assertSame(1.3, Projeto_acompanhamento_model::tempoMedioHoras([
            ['2026-10-29 08:00:00', '2026-10-29 09:00:00'],
            ['2026-10-29 08:00:00', '2026-10-29 09:00:00'],
            ['2026-10-29 08:00:00', '2026-10-29 10:00:00'],
        ]));
        // 1h e 2h: média 1,5
        $this->assertSame(1.5, Projeto_acompanhamento_model::tempoMedioHoras([
            ['2026-10-29 08:00:00', '2026-10-29 09:00:00'],
            ['2026-10-29 08:00:00', '2026-10-29 10:00:00'],
        ]));
    }

    public function testTempoMedioAtravessaVirada(): void
    {
        $this->assertSame(5.5, Projeto_acompanhamento_model::tempoMedioHoras([
            ['2026-10-29 22:00:00', '2026-10-30 03:30:00'],
        ]));
    }

    /* ----------------------- classificarOrigemAtividade ----------------------- */

    public function testOrigemSemOsEhRegistroDoDia(): void
    {
        $r = Projeto_acompanhamento_model::classificarOrigemAtividade(null);
        $this->assertSame('rdo', $r['origem']);
        $this->assertSame('Registro do dia', $r['rotulo']);
        $this->assertNull($r['os_id']);
    }

    public function testOrigemComZeroEhRegistroDoDia(): void
    {
        $r = Projeto_acompanhamento_model::classificarOrigemAtividade(0);
        $this->assertSame('rdo', $r['origem']);
        $this->assertSame('Registro do dia', $r['rotulo']);
    }

    public function testOrigemComOsIdentificaAOs(): void
    {
        $r = Projeto_acompanhamento_model::classificarOrigemAtividade(123);
        $this->assertSame('os', $r['origem']);
        $this->assertSame('OS #123', $r['rotulo']);
        $this->assertSame(123, $r['os_id']);
    }

    public function testOrigemAceitaStringNumerica(): void
    {
        $r = Projeto_acompanhamento_model::classificarOrigemAtividade('45');
        $this->assertSame('os', $r['origem']);
        $this->assertSame('OS #45', $r['rotulo']);
    }

    /* ------------------ alertas da ponte OS x projeto ------------------ */

    public function testTextoEtapaAtrasada(): void
    {
        $t = Projeto_acompanhamento_model::textoAlerta('etapa_atrasada', ['qtd' => 1, 'nome' => 'Cabos', 'dias' => 3]);
        $this->assertSame('A etapa "Cabos" passou do fim previsto há 3 dias sem OS concluída.', $t);
        $this->assertSame('2 etapas passaram do fim previsto sem OS concluída.', Projeto_acompanhamento_model::textoAlerta('etapa_atrasada', ['qtd' => 2]));
    }

    public function testTextoEtapaSemOs(): void
    {
        $this->assertSame('A etapa "NVR" começa em 1 dia e ainda não tem OS.', Projeto_acompanhamento_model::textoAlerta('etapa_sem_os', ['qtd' => 1, 'nome' => 'NVR', 'dias' => 1]));
        $this->assertSame('A etapa "NVR" começa hoje e ainda não tem OS.', Projeto_acompanhamento_model::textoAlerta('etapa_sem_os', ['nome' => 'NVR', 'dias' => 0]));
        $this->assertSame('A etapa "NVR" deveria ter começado há 4 dias e ainda não tem OS.', Projeto_acompanhamento_model::textoAlerta('etapa_sem_os', ['nome' => 'NVR', 'dias' => -4]));
        $this->assertStringStartsWith('3 etapas começam', Projeto_acompanhamento_model::textoAlerta('etapa_sem_os', ['qtd' => 3]));
    }

    public function testTextoOsSemEtapaEAguardandoMedicao(): void
    {
        $this->assertStringStartsWith('A OS #12 é de execução e está sem etapa.', Projeto_acompanhamento_model::textoAlerta('os_sem_etapa', ['qtd' => 1, 'os_id' => 12]));
        $this->assertSame('A OS #7 foi finalizada há 9 dias e aguarda medição.', Projeto_acompanhamento_model::textoAlerta('os_aguardando_medicao', ['qtd' => 1, 'os_id' => 7, 'dias' => 9]));
        $this->assertSame('4 OS finalizadas há mais de 7 dias aguardam medição.', Projeto_acompanhamento_model::textoAlerta('os_aguardando_medicao', ['qtd' => 4]));
    }

    public function testTextoMedicaoNaoFaturada(): void
    {
        $this->assertSame('A medição nº 3 foi aprovada e ainda não foi faturada (R$ 1.234,50).', Projeto_acompanhamento_model::textoAlerta('medicao_nao_faturada', ['qtd' => 1, 'numero' => 3, 'valor' => 1234.5]));
        $this->assertSame('2 medições aprovadas ainda não foram faturadas (R$ 10,00).', Projeto_acompanhamento_model::textoAlerta('medicao_nao_faturada', ['qtd' => 2, 'valor' => 10]));
        $this->assertSame('', Projeto_acompanhamento_model::textoAlerta('desconhecido', []));
    }

    public function testAlertaMontaLinkEAcao(): void
    {
        $a = Projeto_acompanhamento_model::alerta('medicao_nao_faturada', 9903, 'Demo', ['qtd' => 1, 'numero' => 1, 'valor' => 5], 'medicao');
        $this->assertSame('obras/visualizar/9903#medicao', $a['link']);
        $this->assertSame('Faturar', $a['acao']);
        $this->assertSame('perigo', $a['nivel']);
        $this->assertSame(9903, $a['obra_id']);
        // uma OS sem etapa: vai direto para a OS, onde a etapa é escolhida
        $b = Projeto_acompanhamento_model::alerta('os_sem_etapa', 9903, 'Demo', ['qtd' => 1, 'os_id' => 44], 'os');
        $this->assertSame('os/visualizar/44', $b['link']);
        $c = Projeto_acompanhamento_model::alerta('os_sem_etapa', 9903, 'Demo', ['qtd' => 2, 'os_id' => 44], 'os');
        $this->assertSame('obras/visualizar/9903#os', $c['link']);
        $this->assertSame('alerta', $c['nivel']);
    }
}
