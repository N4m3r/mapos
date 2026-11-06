<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Funcoes puras da Central de Notificacoes (tela unica de configuracao).
 *
 * O catalogo de eventos continua em Notification_triggers_model::eventosCatalogo();
 * aqui ficam apenas apresentacao e resumos, para a view nao repetir regra.
 * Testado em tests/Unit/NotificacoesHelperTest.php.
 */
if (! function_exists('notif_lista')) {
    /**
     * Converte uma string separada por virgula em lista limpa (mesma regra de
     * Notification_triggers_model::toList, sem depender do model nos testes).
     *
     * @return string[]
     */
    function notif_lista($valor)
    {
        if ($valor === null || $valor === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $valor)), function ($v) {
            return $v !== '';
        }));
    }
}

if (! function_exists('notif_ordem_grupos')) {
    /**
     * Ordem em que os grupos de eventos aparecem na central.
     *
     * @return string[]
     */
    function notif_ordem_grupos()
    {
        return ['Ordem de Serviço', 'Atendimento em campo', 'Projetos', 'Cobrança', 'Fiscal', 'Cliente'];
    }
}

if (! function_exists('notif_evento_icone')) {
    /**
     * Icone (Boxicons) de cada evento do catalogo.
     */
    function notif_evento_icone($evento)
    {
        $icones = [
            'os_aberta' => 'bx-file-blank',
            'os_editada' => 'bx-edit',
            'os_aprovada' => 'bx-check-circle',
            'os_finalizada' => 'bx-check-square',
            'os_aceite' => 'bx-pen',
            'os_tecnico_a_caminho' => 'bx-run',
            'os_tecnico_chegou' => 'bx-map-pin',
            'os_nao_realizada' => 'bx-block',
            'os_reagendada' => 'bx-calendar-edit',
            'os_tecnico_definido' => 'bx-user-check',
            'rdo_registrado' => 'bx-clipboard',
            'cobranca_gerada' => 'bx-receipt',
            'cobranca_enviada' => 'bx-mail-send',
            'pagamento_confirmado' => 'bx-dollar-circle',
            'nota_emitida' => 'bx-file',
            'cliente_novo' => 'bx-user-plus',
        ];

        return $icones[$evento] ?? 'bx-bell';
    }
}

if (! function_exists('notif_canal_icone')) {
    /** Icone de cada canal. */
    function notif_canal_icone($canal)
    {
        return $canal === 'whatsapp' ? 'bxl-whatsapp' : 'bx-envelope';
    }
}

if (! function_exists('notif_resumo_evento')) {
    /**
     * Resume os gatilhos de um evento para a lista da central.
     *
     * @param object[] $gatilhos gatilhos do mesmo evento
     *
     * @return array{canais: string[], ativo: bool, modelos: string[], destinos: string[]}
     */
    function notif_resumo_evento(array $gatilhos)
    {
        $canais = [];
        $modelos = [];
        $destinos = [];
        $ativo = false;

        foreach ($gatilhos as $g) {
            $canaisEvento = notif_lista($g->canais ?? null);
            $destinosEvento = notif_lista($g->destinatarios ?? null);

            if ((int) ($g->ativo ?? 0) === 1) {
                $ativo = true;
                foreach ($canaisEvento as $c) {
                    if (! in_array($c, $canais, true)) {
                        $canais[] = $c;
                    }
                }
                foreach ($destinosEvento as $d) {
                    if (! in_array($d, $destinos, true)) {
                        $destinos[] = $d;
                    }
                }
            }

            $modelo = trim((string) ($g->whatsapp_template ?? $g->template_slug ?? ''));
            if ($modelo !== '' && ! in_array($modelo, $modelos, true)) {
                $modelos[] = $modelo;
            }
        }

        return [
            'canais' => $canais,
            'ativo' => $ativo,
            'modelos' => $modelos,
            'destinos' => $destinos,
        ];
    }
}

if (! function_exists('notif_agrupar_por_evento')) {
    /**
     * Agrupa gatilhos por evento preservando a ordem do catalogo.
     *
     * @param object[]         $gatilhos
     * @param array|null       $catalogo catalogo de eventos (null = usa o model)
     *
     * @return array<string, array{evento: array, gatilhos: object[]}>
     */
    function notif_agrupar_por_evento(array $gatilhos, $catalogo = null)
    {
        if ($catalogo === null) {
            $catalogo = class_exists('Notification_triggers_model')
                ? Notification_triggers_model::eventosCatalogo()
                : [];
        }

        $porEvento = [];

        foreach ($gatilhos as $g) {
            $slug = (string) $g->evento;
            if (! isset($porEvento[$slug])) {
                $porEvento[$slug] = [
                    'evento' => $catalogo[$slug] ?? ['nome' => $g->nome ?: $slug, 'grupo' => $g->grupo ?: 'Outros'],
                    'gatilhos' => [],
                ];
            }
            $porEvento[$slug]['gatilhos'][] = $g;
        }

        // Ordena pelos grupos conhecidos e, dentro do grupo, pelo nome do evento.
        $ordem = notif_ordem_grupos();
        uasort($porEvento, function ($a, $b) use ($ordem) {
            $grupoA = array_search($a['evento']['grupo'], $ordem, true);
            $grupoB = array_search($b['evento']['grupo'], $ordem, true);
            $grupoA = $grupoA === false ? 99 : $grupoA;
            $grupoB = $grupoB === false ? 99 : $grupoB;
            if ($grupoA !== $grupoB) {
                return $grupoA <=> $grupoB;
            }

            return strcasecmp($a['evento']['nome'], $b['evento']['nome']);
        });

        return $porEvento;
    }
}

if (! function_exists('notif_quando')) {
    /**
     * Tempo relativo curto para o historico ("agora", "5 min", "3 h", "2 d").
     */
    function notif_quando($dataHora, $agora = null)
    {
        $ts = $dataHora ? strtotime((string) $dataHora) : false;
        if ($ts === false) {
            return '—';
        }

        $agora = $agora === null ? time() : (int) $agora;
        $diff = $agora - $ts;
        if ($diff < 0) {
            $diff = 0;
        }
        if ($diff < 60) {
            return 'agora';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' min';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . ' h';
        }
        if ($diff < 604800) {
            return floor($diff / 86400) . ' d';
        }

        return date('d/m/Y', $ts);
    }
}

if (! function_exists('notif_env_chaves_smtp')) {
    /**
     * Chaves do .env editadas na aba Canais > E-mail (na ordem da tela).
     */
    function notif_env_chaves_smtp()
    {
        return [
            'EMAIL_PROTOCOL' => 'smtp',
            'EMAIL_SMTP_HOST' => '',
            'EMAIL_SMTP_CRYPTO' => 'tls',
            'EMAIL_SMTP_PORT' => '587',
            'EMAIL_SMTP_USER' => '',
            'EMAIL_SMTP_PASS' => '',
        ];
    }
}

if (! function_exists('notif_env_chaves_whatsapp')) {
    /**
     * Chaves do .env editadas na aba Canais > WhatsApp (na ordem da tela).
     */
    function notif_env_chaves_whatsapp()
    {
        return [
            'WHATSAPP_EVOLUTION_ENABLED' => 'false',
            'WHATSAPP_EVOLUTION_URL' => '',
            'WHATSAPP_EVOLUTION_APIKEY' => '',
            'WHATSAPP_EVOLUTION_INSTANCE' => '',
            'WHATSAPP_EVOLUTION_VERIFY_SSL' => 'true',
            'WHATSAPP_EVOLUTION_AUTO_STATUS' => '',
        ];
    }
}

if (! function_exists('notif_env_booleano')) {
    /** Normaliza um valor de .env para sim/nao (linhas "true"/"1"/"sim"). */
    function notif_env_booleano($valor)
    {
        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }
}
