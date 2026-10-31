<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Regras puras (sem banco/sessao) dos avisos de atendimento ao cliente por
 * WhatsApp (tecnico a caminho, chegou, nao realizado, reagendada, tecnico definido).
 */
class AvisosAtendimento
{
    public const EVENTOS = [
        'os_tecnico_a_caminho',
        'os_tecnico_chegou',
        'os_nao_realizada',
        'os_reagendada',
        'os_tecnico_definido',
    ];

    /** Atraso maximo (minutos) de um check-in vindo da fila offline para ainda avisar "chegou". */
    public const CHEGADA_ATRASO_MAX_MIN = 30;

    /** Janela anti-repeticao (minutos) por evento. */
    public static function janelaMinutos(string $evento): int
    {
        return in_array($evento, ['os_tecnico_a_caminho', 'os_tecnico_chegou'], true) ? 30 : 5;
    }

    /** true se o ultimo envio do mesmo evento/OS ainda esta dentro da janela (nao deve reenviar). */
    public static function repetido(?int $ultimoEnvioTs, int $agoraTs, string $evento): bool
    {
        if (! $ultimoEnvioTs) {
            return false;
        }

        return ($agoraTs - $ultimoEnvioTs) < self::janelaMinutos($evento) * 60;
    }

    /** "dd/mm/aaaa às HH:MM"; vazio quando nao ha agendamento valido. */
    public static function formatarAgendamento($dataHora): string
    {
        $dataHora = trim((string) $dataHora);
        if ($dataHora === '' || strpos($dataHora, '0000-00-00') === 0) {
            return '';
        }
        $ts = strtotime($dataHora);
        if (! $ts) {
            return '';
        }

        return date('d/m/Y', $ts) . ' às ' . date('H:i', $ts);
    }

    /** Primeiro nome ("Joao Silva" -> "Joao"); vazio se nao houver. */
    public static function primeiroNome($nome): string
    {
        $partes = preg_split('/\s+/u', trim((string) $nome));

        return $partes && $partes[0] !== null ? (string) $partes[0] : '';
    }

    /**
     * Check-in que chega atrasado pela fila offline nao deve avisar "chegou":
     * sem horario informado = agora (avisa); informado ha mais de 30 min = nao avisa.
     */
    public static function deveAvisarChegada($registradoEm, int $agoraTs): bool
    {
        $registradoEm = trim((string) $registradoEm);
        if ($registradoEm === '') {
            return true;
        }
        $ts = strtotime($registradoEm);
        if (! $ts) {
            return true;
        }

        return ($agoraTs - $ts) <= self::CHEGADA_ATRASO_MAX_MIN * 60;
    }

    /** Tecnico ou data realmente mudaram? (vazio/0/data zerada contam como "sem valor") */
    public static function mudouTecnicoOuData($tecAnt, $dataAnt, $tecNovo, $dataNovo): bool
    {
        $t1 = (int) $tecAnt;
        $t2 = (int) $tecNovo;
        $d1 = self::normalizarData($dataAnt);
        $d2 = self::normalizarData($dataNovo);

        return $t1 !== $t2 || $d1 !== $d2;
    }

    private static function normalizarData($v): string
    {
        $v = trim((string) $v);
        if ($v === '' || strpos($v, '0000-00-00') === 0) {
            return '';
        }
        $ts = strtotime(str_replace('T', ' ', $v));

        return $ts ? date('Y-m-d H:i', $ts) : '';
    }
}
