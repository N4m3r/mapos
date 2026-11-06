<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once APPPATH . 'helpers/notificacoes_helper.php';

/**
 * Funcoes puras da Central de Notificacoes: lista CSV, resumo por evento,
 * agrupamento pelo catalogo, tempo relativo e chaves do .env.
 */
class NotificacoesHelperTest extends TestCase
{
    private const CATALOGO = [
        'os_aberta' => ['nome' => 'OS aberta', 'grupo' => 'Ordem de Serviço'],
        'os_finalizada' => ['nome' => 'OS finalizada', 'grupo' => 'Ordem de Serviço'],
        'rdo_registrado' => ['nome' => 'Registro de execução', 'grupo' => 'Projetos'],
        'cliente_novo' => ['nome' => 'Cliente cadastrado', 'grupo' => 'Cliente'],
    ];

    public function testListaNormalizaCsv(): void
    {
        $this->assertSame([], notif_lista(null));
        $this->assertSame([], notif_lista(''));
        $this->assertSame([], notif_lista(' , , '));
        $this->assertSame(['email', 'whatsapp'], notif_lista(' email , whatsapp '));
        $this->assertSame(['cliente'], notif_lista('cliente,,'));
    }

    public function testResumoConsideraSoGatilhosAtivos(): void
    {
        $ativos = (object) [
            'ativo' => 1,
            'canais' => 'email,whatsapp',
            'destinatarios' => 'cliente',
            'whatsapp_template' => 'os',
        ];
        $desligado = (object) [
            'ativo' => 0,
            'canais' => 'email',
            'destinatarios' => 'tecnico',
            'whatsapp_template' => 'outro',
        ];

        $resumo = notif_resumo_evento([$ativos, $desligado]);

        $this->assertTrue($resumo['ativo']);
        $this->assertSame(['email', 'whatsapp'], $resumo['canais']);
        $this->assertSame(['cliente'], $resumo['destinos']);
        $this->assertSame(['os', 'outro'], $resumo['modelos']);
    }

    public function testResumoDeEventoSemGatilho(): void
    {
        $resumo = notif_resumo_evento([]);

        $this->assertFalse($resumo['ativo']);
        $this->assertSame([], $resumo['canais']);
        $this->assertSame([], $resumo['destinos']);
        $this->assertSame([], $resumo['modelos']);
    }

    public function testAgrupaPelaOrdemDosGrupos(): void
    {
        $gatilhos = [
            (object) ['evento' => 'cliente_novo', 'nome' => 'Cliente', 'grupo' => 'Cliente', 'ativo' => 1, 'canais' => 'email'],
            (object) ['evento' => 'os_aberta', 'nome' => 'OS aberta', 'grupo' => 'Ordem de Serviço', 'ativo' => 1, 'canais' => 'email'],
            (object) ['evento' => 'rdo_registrado', 'nome' => 'RDO', 'grupo' => 'Projetos', 'ativo' => 0, 'canais' => 'email'],
        ];

        $agrupado = notif_agrupar_por_evento($gatilhos, self::CATALOGO);
        $ordem = array_keys($agrupado);

        $this->assertSame(['os_aberta', 'rdo_registrado', 'cliente_novo'], $ordem);
        $this->assertCount(1, $agrupado['os_aberta']['gatilhos']);
        $this->assertSame('Registro de execução', $agrupado['rdo_registrado']['evento']['nome']);
    }

    public function testEventoForaDoCatalogoNaoDesaparece(): void
    {
        $gatilhos = [
            (object) ['evento' => 'evento_novo', 'nome' => 'Evento novo', 'grupo' => 'Outros', 'ativo' => 1, 'canais' => 'email'],
        ];

        $agrupado = notif_agrupar_por_evento($gatilhos, self::CATALOGO);

        $this->assertArrayHasKey('evento_novo', $agrupado);
        $this->assertSame('Evento novo', $agrupado['evento_novo']['evento']['nome']);
    }

    public function testQuandoEmTempoRelativo(): void
    {
        $agora = strtotime('2026-11-06 12:00:00');

        $this->assertSame('agora', notif_quando('2026-11-06 11:59:30', $agora));
        $this->assertSame('5 min', notif_quando('2026-11-06 11:55:00', $agora));
        $this->assertSame('3 h', notif_quando('2026-11-06 09:00:00', $agora));
        $this->assertSame('2 d', notif_quando('2026-11-04 12:00:00', $agora));
        $this->assertSame('01/10/2026', notif_quando('2026-10-01 12:00:00', $agora));
        $this->assertSame('—', notif_quando(null, $agora));
        $this->assertSame('—', notif_quando('data invalida', $agora));
    }

    public function testChavesDoEnvBatemComATela(): void
    {
        $smtp = notif_env_chaves_smtp();
        $wpp = notif_env_chaves_whatsapp();

        $this->assertSame(
            ['EMAIL_PROTOCOL', 'EMAIL_SMTP_HOST', 'EMAIL_SMTP_CRYPTO', 'EMAIL_SMTP_PORT', 'EMAIL_SMTP_USER', 'EMAIL_SMTP_PASS'],
            array_keys($smtp)
        );
        $this->assertSame(
            ['WHATSAPP_EVOLUTION_ENABLED', 'WHATSAPP_EVOLUTION_URL', 'WHATSAPP_EVOLUTION_APIKEY', 'WHATSAPP_EVOLUTION_INSTANCE', 'WHATSAPP_EVOLUTION_VERIFY_SSL', 'WHATSAPP_EVOLUTION_AUTO_STATUS'],
            array_keys($wpp)
        );
    }

    public function testBooleanoAceitaOsValoresDoEnv(): void
    {
        $this->assertTrue(notif_env_booleano('true'));
        $this->assertTrue(notif_env_booleano('1'));
        $this->assertTrue(notif_env_booleano(true));
        $this->assertFalse(notif_env_booleano('false'));
        $this->assertFalse(notif_env_booleano('0'));
        $this->assertFalse(notif_env_booleano(''));
        $this->assertFalse(notif_env_booleano(null));
    }

    public function testIconesConhecidos(): void
    {
        $this->assertSame('bx-file-blank', notif_evento_icone('os_aberta'));
        $this->assertSame('bxl-whatsapp', notif_canal_icone('whatsapp'));
        $this->assertSame('bx-envelope', notif_canal_icone('email'));
        $this->assertSame('bx-bell', notif_evento_icone('evento_inexistente'));
    }
}
