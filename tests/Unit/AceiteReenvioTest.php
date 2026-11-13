<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Aviso_aceite.php';
require_once APPPATH . 'libraries/Aceite_reenvio.php';

/** Aviso_aceite sem banco: grava o que seria disparado/agendado. */
class AvisoAceiteFake extends Aviso_aceite
{
    public $os;
    public $gatilhos = [];
    public $numero = '5592999990000';
    public $disparos = [];
    public $agendados = [];
    public $encerrados = [];
    public $lembretes = [];

    protected function carregarOs($osId)
    {
        return $this->os;
    }

    protected function gatilhosAtivos($evento)
    {
        return $this->gatilhos[$evento] ?? [];
    }

    protected function configReenvio($evento)
    {
        foreach ($this->gatilhosAtivos($evento) as $t) {
            if ($c = Aceite_reenvio::configDoGatilho($t)) {
                return $c;
            }
        }

        return null;
    }

    protected function agendarReenvio($osId, $evento, array $config)
    {
        $this->agendados[] = [$osId, $evento, $config];
    }

    protected function encerrarReenvio($osId, $situacao)
    {
        $this->encerrados[] = [$osId, $situacao];
    }

    protected function numeroCliente($os)
    {
        return $this->numero;
    }

    protected function link($os)
    {
        return 'https://l/' . $os->idOs;
    }

    protected function disparar($evento, $os, array $tags, $slugPadrao, $fallback)
    {
        $this->disparos[] = ['evento' => $evento, 'slug' => $slugPadrao, 'tags' => $tags];
    }

    protected function enviarLembrete($numero, array $tags, $osId)
    {
        $this->lembretes[] = [$numero, $tags['{TENTATIVA}'], $tags['{LINK}']];

        return true;
    }
}

/** Aceite_reenvio com a fila em memória. */
class AceiteReenvioFake extends Aceite_reenvio
{
    public $fila = [];
    public $os = [];
    public $situacoes = [];
    public $reenvios = [];
    public $esgotados = [];

    protected function ativos($agora)
    {
        return array_values(array_filter($this->fila, static function ($i) use ($agora) {
            return $i->situacao === 'ativo' && strtotime($i->proximo_envio) <= $agora;
        }));
    }

    protected function itemDaOs($osId)
    {
        return $this->fila[$osId] ?? null;
    }

    protected function gravar($osId, array $dados, $agora)
    {
        $this->fila[$osId] = (object) ($dados + ['id' => $osId, 'os_id' => $osId]);

        return true;
    }

    protected function atualizar($id, array $dados)
    {
        foreach ($dados as $k => $v) {
            $this->fila[$id]->{$k} = $v;
        }

        return true;
    }

    protected function carregarOs($osId)
    {
        return $this->os[$osId] ?? null;
    }

    protected function situacaoAceite($os)
    {
        return $this->situacoes[$os->idOs] ?? 'pendente';
    }

    protected function reenviarLink($os, $tentativa)
    {
        $this->reenvios[] = [(int) $os->idOs, $tentativa];

        return true;
    }

    protected function avisarEsgotado($os, $tentativas)
    {
        $this->esgotados[] = [(int) $os->idOs, $tentativas];
    }
}

class AceiteReenvioTest extends TestCase
{
    private const T0 = 1_800_000_000;

    protected function setUp(): void
    {
        $GLOBALS['CI'] = new stdClass();
    }

    private function gatilho($horas, $max)
    {
        return (object) ['reenvio_intervalo_horas' => $horas, 'reenvio_max_tentativas' => $max];
    }

    private function osNp($id = 10, array $extra = [])
    {
        return (object) ($extra + ['idOs' => $id, 'nomeCliente' => 'TG Foods', 'nao_programada' => 1, 'clientes_id' => 3, 'status' => 'Finalizado']);
    }

    /* --------------------------- configuração --------------------------- */

    public function testConfigVaziaOuZeroDesligaOReenvio(): void
    {
        $this->assertSame([null, null], Aceite_reenvio::normalizarConfig('', '5'));
        $this->assertSame([null, null], Aceite_reenvio::normalizarConfig('24', '0'));
        $this->assertNull(Aceite_reenvio::configDoGatilho((object) []));
        $this->assertNull(Aceite_reenvio::configDoGatilho(null));
    }

    public function testConfigLimitaIntervaloETentativas(): void
    {
        $this->assertSame([720, 30], Aceite_reenvio::normalizarConfig('9999', '500'));
        $this->assertSame(['intervalo_horas' => 24, 'max_tentativas' => 3], Aceite_reenvio::configDoGatilho($this->gatilho('24', '3')));
    }

    /* ------------------------------ disparo ----------------------------- */

    public function testEventoDaOs(): void
    {
        $this->assertSame('atividade_np_finalizada', Aviso_aceite::eventoParaOs($this->osNp(), true));
        $this->assertSame('os_aceite', Aviso_aceite::eventoParaOs($this->osNp(), false), 'sem o gatilho novo, mantém o comportamento antigo');
        $this->assertSame('os_aceite', Aviso_aceite::eventoParaOs((object) ['nao_programada' => 0], true));
    }

    public function testAtividadeNaoProgramadaFinalizadaDisparaEAgendaOReenvio(): void
    {
        $aviso = new AvisoAceiteFake();
        $aviso->os = $this->osNp();
        $aviso->gatilhos['atividade_np_finalizada'] = [$this->gatilho(12, 4)];

        $this->assertTrue($aviso->enviar(10));
        $this->assertCount(1, $aviso->disparos);
        $this->assertSame('atividade_np_finalizada', $aviso->disparos[0]['evento']);
        $this->assertSame('aceite_np', $aviso->disparos[0]['slug']);
        $this->assertSame('https://l/10', $aviso->disparos[0]['tags']['{LINK}']);
        $this->assertSame([[10, 'atividade_np_finalizada', ['intervalo_horas' => 12, 'max_tentativas' => 4]]], $aviso->agendados);
    }

    public function testOsComumUsaOsAceiteESemReenvioNaoAgenda(): void
    {
        $aviso = new AvisoAceiteFake();
        $aviso->os = $this->osNp(11, ['nao_programada' => 0]);
        $aviso->gatilhos['atividade_np_finalizada'] = [$this->gatilho(12, 4)];
        $aviso->gatilhos['os_aceite'] = [(object) []];

        $this->assertTrue($aviso->enviar(11));
        $this->assertSame('os_aceite', $aviso->disparos[0]['evento']);
        $this->assertSame('aceite', $aviso->disparos[0]['slug']);
        $this->assertSame([], $aviso->agendados);
    }

    public function testSemNumeroDoClienteNaoEntraNoLoop(): void
    {
        $aviso = new AvisoAceiteFake();
        $aviso->os = $this->osNp();
        $aviso->numero = '';
        $aviso->gatilhos['atividade_np_finalizada'] = [$this->gatilho(12, 4)];

        $aviso->enviar(10);
        $this->assertSame([], $aviso->agendados);
    }

    public function testReenvioMandaLembreteComONumeroDaTentativa(): void
    {
        $aviso = new AvisoAceiteFake();
        $this->assertTrue($aviso->reenviar($this->osNp(), 2));
        $this->assertSame([['5592999990000', 2, 'https://l/10']], $aviso->lembretes);
    }

    public function testDecisaoEncerraOLoopEAvisa(): void
    {
        $aviso = new AvisoAceiteFake();
        $aviso->os = $this->osNp();

        $aviso->aoDecidir(10, 'aceito', 'Maria', '');
        $aviso->aoDecidir(10, 'recusado', 'João', 'Serviço incompleto');

        $this->assertSame([[10, 'aceito'], [10, 'recusado']], $aviso->encerrados);
        $this->assertSame('aceite_concluido', $aviso->disparos[0]['evento']);
        $this->assertSame('Maria', $aviso->disparos[0]['tags']['{NOME_ASSINANTE}']);
        $this->assertSame('aceite_recusado', $aviso->disparos[1]['evento']);
        $this->assertSame('Serviço incompleto', $aviso->disparos[1]['tags']['{OBSERVACAO}']);
    }

    /* ------------------------------- loop ------------------------------- */

    public function testDecidir(): void
    {
        $item = (object) ['tentativas' => 1, 'max_tentativas' => 3, 'proximo_envio' => date('Y-m-d H:i:s', self::T0 - 60)];
        $os = $this->osNp();

        $this->assertSame('reenviar', Aceite_reenvio::decidir($item, $os, 'pendente', self::T0));
        $this->assertSame('reenviar', Aceite_reenvio::decidir($item, $os, 'expirado', self::T0), 'link vencido é renovado no reenvio');
        $this->assertSame('aceito', Aceite_reenvio::decidir($item, $os, 'aceito', self::T0));
        $this->assertSame('recusado', Aceite_reenvio::decidir($item, $os, 'recusado', self::T0));
        $this->assertSame('cancelado', Aceite_reenvio::decidir($item, $os, 'invalido', self::T0), 'link revogado');
        $this->assertSame('cancelado', Aceite_reenvio::decidir($item, $this->osNp(10, ['status' => 'Cancelado']), 'pendente', self::T0));
        $this->assertSame('cancelado', Aceite_reenvio::decidir($item, null, 'pendente', self::T0));
        $this->assertSame('aguardar', Aceite_reenvio::decidir($item, $os, 'pendente', self::T0 - 3600));

        $item->tentativas = 3;
        $this->assertSame('esgotado', Aceite_reenvio::decidir($item, $os, 'pendente', self::T0));
    }

    public function testLoopReenviaNoIntervaloEParaQuandoOClienteAssina(): void
    {
        $loop = new AceiteReenvioFake();
        $loop->os[10] = $this->osNp();
        $loop->agendar(10, 'atividade_np_finalizada', ['intervalo_horas' => 24, 'max_tentativas' => 5], self::T0);

        $this->assertSame([], $loop->executar(self::T0 + 3600), 'antes do intervalo não reenvia');

        $r = $loop->executar(self::T0 + 24 * 3600);
        $this->assertSame([['os' => 10, 'acao' => 'reenviar', 'tentativa' => 1, 'enviado' => true]], $r);
        $this->assertSame(date('Y-m-d H:i:s', self::T0 + 48 * 3600), $loop->fila[10]->proximo_envio);

        $loop->executar(self::T0 + 48 * 3600);
        $this->assertSame([[10, 1], [10, 2]], $loop->reenvios);

        $loop->situacoes[10] = 'aceito';
        $r = $loop->executar(self::T0 + 72 * 3600);
        $this->assertSame('aceito', $r[0]['acao']);
        $this->assertSame('aceito', $loop->fila[10]->situacao);
        $this->assertNull($loop->fila[10]->proximo_envio);

        $this->assertSame([], $loop->executar(self::T0 + 999 * 3600), 'encerrado não volta a reenviar');
        $this->assertCount(2, $loop->reenvios);
        $this->assertSame([], $loop->esgotados);
    }

    public function testLoopParaAoRecusar(): void
    {
        $loop = new AceiteReenvioFake();
        $loop->os[10] = $this->osNp();
        $loop->agendar(10, 'os_aceite', ['intervalo_horas' => 1, 'max_tentativas' => 5], self::T0);
        $loop->situacoes[10] = 'recusado';

        $loop->executar(self::T0 + 3600);
        $this->assertSame('recusado', $loop->fila[10]->situacao);
        $this->assertSame([], $loop->reenvios);
    }

    public function testLoopEsgotaEAvisa(): void
    {
        $loop = new AceiteReenvioFake();
        $loop->os[10] = $this->osNp();
        $loop->agendar(10, 'atividade_np_finalizada', ['intervalo_horas' => 2, 'max_tentativas' => 2], self::T0);

        for ($h = 2; $h <= 10; $h += 2) {
            $loop->executar(self::T0 + $h * 3600);
        }

        $this->assertSame([[10, 1], [10, 2]], $loop->reenvios);
        $this->assertSame('esgotado', $loop->fila[10]->situacao);
        $this->assertSame([[10, 2]], $loop->esgotados);
    }

    public function testDecisaoPeloLinkEncerraNaHoraEReagendarReabre(): void
    {
        $loop = new AceiteReenvioFake();
        $loop->os[10] = $this->osNp();
        $config = ['intervalo_horas' => 1, 'max_tentativas' => 3];
        $loop->agendar(10, 'os_aceite', $config, self::T0);

        $this->assertTrue($loop->encerrar(10, 'aceito', self::T0 + 60));
        $this->assertFalse($loop->encerrar(10, 'aceito', self::T0 + 120), 'já encerrado');
        $this->assertSame([], $loop->executar(self::T0 + 3600));

        // Nova finalização (OS reaberta) recoloca a OS no loop do zero.
        $loop->agendar(10, 'os_aceite', $config, self::T0 + 7200);
        $this->assertSame('ativo', $loop->fila[10]->situacao);
        $this->assertSame(0, $loop->fila[10]->tentativas);
    }

    public function testOsCanceladaEncerraSemReenviar(): void
    {
        $loop = new AceiteReenvioFake();
        $loop->os[10] = $this->osNp(10, ['status' => 'Cancelado']);
        $loop->agendar(10, 'os_aceite', ['intervalo_horas' => 1, 'max_tentativas' => 3], self::T0);

        $r = $loop->executar(self::T0 + 3600);
        $this->assertSame('cancelado', $r[0]['acao']);
        $this->assertSame([], $loop->reenvios);
    }
}
