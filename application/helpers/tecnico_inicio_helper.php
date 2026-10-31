<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Logica pura da tela inicial / lista de OS do tecnico (sem acesso a banco).
 */
class TecnicoInicio
{
    const ABAS = ['hoje' => 'Hoje', 'proximos' => 'Próximos dias', 'atrasadas' => 'Atrasadas', 'concluidas' => 'Concluídas'];

    private static function campo($os, $nome)
    {
        if (is_array($os)) {
            return $os[$nome] ?? null;
        }

        return $os->$nome ?? null;
    }

    private static function dataValida($v)
    {
        $v = trim((string) $v);

        return ($v !== '' && strpos($v, '0000-00-00') !== 0) ? $v : null;
    }

    /** Data/hora da visita: data_agendamento quando existe, senao dataInicial. */
    public static function dataEfetiva($os)
    {
        $d = self::dataValida(self::campo($os, 'data_agendamento'));
        if ($d === null) {
            $d = self::dataValida(self::campo($os, 'dataInicial'));
        }

        return $d;
    }

    /** "HH:MM" ou null quando so ha data (00:00). */
    public static function horaVisita($os)
    {
        $d = self::dataEfetiva($os);
        if ($d === null || strlen($d) < 16) {
            return null;
        }
        $h = substr($d, 11, 5);

        return $h === '00:00' ? null : $h;
    }

    public static function statusConcluido($status)
    {
        return in_array($status, ['Finalizado', 'Faturado'], true);
    }

    /** hoje | proximos | atrasadas | concluidas | null (cancelada). */
    public static function classificar($os, $hoje)
    {
        $status = (string) self::campo($os, 'status');
        if ($status === 'Cancelado') {
            return null;
        }
        if (self::statusConcluido($status)) {
            return 'concluidas';
        }
        if ($status === 'Em Andamento') {
            return 'hoje';
        }
        if ($status === 'Não Realizado') {
            return 'atrasadas';
        }
        $d = self::dataEfetiva($os);
        if ($d === null) {
            return 'proximos';
        }
        $dia = substr($d, 0, 10);
        if ($dia < $hoje) {
            return 'atrasadas';
        }

        return $dia === $hoje ? 'hoje' : 'proximos';
    }

    /** Agrupa e ordena as OS nas quatro abas. */
    public static function agrupar(array $lista, $hoje)
    {
        $g = ['hoje' => [], 'proximos' => [], 'atrasadas' => [], 'concluidas' => []];
        foreach ($lista as $os) {
            $c = self::classificar($os, $hoje);
            if ($c !== null) {
                $g[$c][] = $os;
            }
        }
        foreach ($g as $k => $itens) {
            usort($itens, static function ($a, $b) use ($k) {
                $r = strcmp((string) self::dataEfetiva($a), (string) self::dataEfetiva($b));

                return $k === 'concluidas' ? -$r : $r;
            });
            $g[$k] = $itens;
        }

        return $g;
    }

    /** Visitas do dia para a linha do tempo (qualquer status, exceto cancelada). */
    public static function doDia(array $lista, $hoje)
    {
        $r = [];
        foreach ($lista as $os) {
            $status = (string) self::campo($os, 'status');
            if ($status === 'Cancelado') {
                continue;
            }
            $d = self::dataEfetiva($os);
            if ($status === 'Em Andamento' || ($d !== null && substr($d, 0, 10) === $hoje)) {
                $r[] = $os;
            }
        }
        usort($r, static function ($a, $b) {
            return strcmp((string) self::dataEfetiva($a), (string) self::dataEfetiva($b));
        });

        return $r;
    }

    /** Rotulo simples: [texto, classe, icone]. Nunca depende so de cor. */
    public static function rotuloStatus($status, $atrasada = false)
    {
        switch ((string) $status) {
            case 'Em Andamento':
                return ['Em atendimento', 'andamento', 'bx-play-circle'];
            case 'Finalizado':
            case 'Faturado':
                return ['Concluída', 'concluida', 'bx-check-circle'];
            case 'Não Realizado':
                return ['Não deu para fazer', 'nao-feita', 'bx-x-circle'];
            default:
                return $atrasada ? ['Atrasada', 'atrasada', 'bx-error'] : ['Agendada', 'agendada', 'bx-calendar'];
        }
    }

    /** Rotulo para uma OS, considerando atraso. */
    public static function rotuloOs($os, $hoje)
    {
        return self::rotuloStatus(self::campo($os, 'status'), self::classificar($os, $hoje) === 'atrasadas');
    }

    /** Em andamento > proxima de hoje a partir de agora > primeira de hoje > primeira dos proximos dias. */
    public static function proximaVisita(array $lista, $agora)
    {
        $hoje = substr($agora, 0, 10);
        $g = self::agrupar($lista, $hoje);
        foreach ($g['hoje'] as $os) {
            if (self::campo($os, 'status') === 'Em Andamento') {
                return ['os' => $os, 'continuar' => true];
            }
        }
        foreach ($g['hoje'] as $os) {
            if (strcmp((string) self::dataEfetiva($os), $agora) >= 0) {
                return ['os' => $os, 'continuar' => false];
            }
        }
        if ($g['hoje']) {
            return ['os' => $g['hoje'][0], 'continuar' => false];
        }
        if ($g['proximos']) {
            return ['os' => $g['proximos'][0], 'continuar' => false];
        }

        return null;
    }

    /** "Rua X, 10 - Bairro" (curto, sem cidade). */
    public static function enderecoCurto($os)
    {
        $rua = trim((string) self::campo($os, 'rua'));
        $num = trim((string) self::campo($os, 'numero'));
        $bairro = trim((string) self::campo($os, 'bairro'));
        $p = $rua . ($rua !== '' && $num !== '' ? ', ' . $num : '');
        if ($bairro !== '') {
            $p .= ($p !== '' ? ' - ' : '') . $bairro;
        }

        return $p;
    }

    /** URL de navegacao do Google Maps ('' sem endereco). */
    public static function urlRota($os)
    {
        $partes = array_filter([
            trim(self::campo($os, 'rua') . ' ' . self::campo($os, 'numero')),
            trim((string) self::campo($os, 'bairro')),
            trim((string) self::campo($os, 'cidade')),
            trim((string) self::campo($os, 'estado')),
        ], static function ($v) {
            return $v !== '';
        });
        if (!$partes) {
            return '';
        }

        return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode(implode(', ', $partes));
    }

    public static function foneWhats($fone)
    {
        $d = preg_replace('/\D/', '', (string) $fone);
        if ($d === '') {
            return '';
        }

        return strlen($d) <= 11 ? '55' . $d : $d;
    }

    /** Texto de busca (minusculo) por numero, cliente e endereco. */
    public static function textoBusca($os)
    {
        return mb_strtolower(sprintf('%04d', (int) self::campo($os, 'idOs')) . ' ' . self::campo($os, 'nomeCliente') . ' ' . self::enderecoCurto($os) . ' ' . self::campo($os, 'cidade'));
    }
}
