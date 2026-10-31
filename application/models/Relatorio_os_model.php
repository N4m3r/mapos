<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'models/Projeto_acompanhamento_model.php';

/**
 * Relatório de OS realizadas por período. Somente leitura.
 *
 * OS realizada = OS com atendimento (os_checkin) no período: entrada ou saída dentro das
 * datas. Fotos vêm de os_fotos_atendimento (data_upload no período), sem nunca selecionar
 * o blob (imagem_base64). Serviços não realizados vêm de os_nao_realizada (data_registro).
 * A lógica de consolidação é estática (sem banco) e coberta por testes unitários.
 */
class Relatorio_os_model extends CI_Model
{
    public const FOTOS_LIMITE_PADRAO = 12;

    private const ETAPAS = ['entrada' => 'Entrada', 'durante' => 'Durante', 'saida' => 'Saída'];

    /* ========================= Funções puras ========================= */

    public static function periodo(string $tipo, string $data): array
    {
        return Projeto_acompanhamento_model::periodo($tipo, $data);
    }

    /** Horas (2 casas) entre entrada e saída (Y-m-d H:i:s). Sem saída ou inválido: 0. */
    public static function horasEntre(?string $entrada, ?string $saida): float
    {
        if (! $entrada || ! $saida) {
            return 0.0;
        }
        $e = strtotime($entrada);
        $s = strtotime($saida);
        if ($e === false || $s === false || $s < $e) {
            return 0.0;
        }

        return round(($s - $e) / 3600, 2);
    }

    /** O instante (Y-m-d H:i:s) cai entre os dias $inicio e $fim (Y-m-d, inclusive)? */
    public static function noPeriodo(?string $dt, string $inicio, string $fim): bool
    {
        if (! $dt) {
            return false;
        }
        $dia = substr($dt, 0, 10);

        return $dia >= $inicio && $dia <= $fim;
    }

    /** Dia de referência do atendimento: o da entrada se estiver no período, senão o da saída. */
    private static function diaRef(array $r, string $inicio, string $fim): string
    {
        if (self::noPeriodo($r['entrada'] ?? null, $inicio, $fim)) {
            return substr((string) $r['entrada'], 0, 10);
        }

        return substr((string) ($r['saida'] ?? $r['entrada'] ?? ''), 0, 10);
    }

    /** Uma linha de atendimento concluída (check-out) dentro do período? */
    private static function concluido(array $r, string $inicio, string $fim): bool
    {
        return self::noPeriodo($r['saida'] ?? null, $inicio, $fim)
            && strtotime((string) $r['saida']) >= strtotime((string) ($r['entrada'] ?: $r['saida']));
    }

    public static function statusFinalizado(string $status): bool
    {
        return in_array($status, ['Finalizado', 'Faturado'], true);
    }

    /** Descrição curta (120 caracteres) da OS: descricaoProduto, ou defeito se vazio. */
    public static function descricaoCurta(?string $descricao, ?string $defeito): string
    {
        $t = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $descricao)));
        if ($t === '') {
            $t = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $defeito)));
        }

        return mb_strlen($t, 'UTF-8') > 120 ? mb_substr($t, 0, 119, 'UTF-8') . '…' : $t;
    }

    /**
     * Agrupa atendimentos (linhas: idOs, cliente, status, descricao, entrada, saida,
     * usuario_id, tecnico, observacao) por OS, ordenadas pela primeira entrada.
     * $fotosPorOs: [idOs => quantidade].
     */
    public static function agruparOs(array $linhas, array $fotosPorOs, string $inicio, string $fim): array
    {
        usort($linhas, function ($a, $b) {
            return [(string) ($a['entrada'] ?? $a['saida']), (int) $a['idOs']] <=> [(string) ($b['entrada'] ?? $b['saida']), (int) $b['idOs']];
        });
        $os = [];
        foreach ($linhas as $r) {
            $id = (int) $r['idOs'];
            if (! isset($os[$id])) {
                $os[$id] = [
                    'idOs' => $id, 'cliente' => (string) ($r['cliente'] ?? ''), 'status' => (string) ($r['status'] ?? ''),
                    'descricao' => (string) ($r['descricao'] ?? ''), 'atendimentos' => [], 'horas' => 0.0,
                    'fotos' => (int) ($fotosPorOs[$id] ?? 0), 'concluida' => false,
                    'finalizada' => self::statusFinalizado((string) ($r['status'] ?? '')),
                ];
            }
            $h = self::horasEntre($r['entrada'] ?? null, $r['saida'] ?? null);
            $os[$id]['atendimentos'][] = [
                'entrada' => (string) ($r['entrada'] ?? ''),
                'saida' => ! empty($r['saida']) ? (string) $r['saida'] : null,
                'tecnico' => (string) ($r['tecnico'] ?? ''),
                'horas' => $h,
                'observacao' => isset($r['observacao']) && trim((string) $r['observacao']) !== '' ? trim((string) $r['observacao']) : null,
            ];
            $os[$id]['horas'] = round($os[$id]['horas'] + $h, 2);
            if (self::concluido($r, $inicio, $fim)) {
                $os[$id]['concluida'] = true;
            }
        }

        return array_values($os);
    }

    /** Resumo por técnico (check-in): usuario_id, nome, atendimentos, horas; mais horas primeiro. */
    public static function resumoTecnicos(array $linhas): array
    {
        $t = [];
        foreach ($linhas as $r) {
            $u = (int) ($r['usuario_id'] ?? 0);
            $t[$u] = $t[$u] ?? ['usuario_id' => $u, 'nome' => (string) ($r['tecnico'] ?? ''), 'atendimentos' => 0, 'horas' => 0.0];
            $t[$u]['atendimentos']++;
            $t[$u]['horas'] = round($t[$u]['horas'] + self::horasEntre($r['entrada'] ?? null, $r['saida'] ?? null), 2);
        }
        usort($t, function ($a, $b) {
            return ($b['horas'] <=> $a['horas']) ?: ($b['atendimentos'] <=> $a['atendimentos']) ?: strcasecmp($a['nome'], $b['nome']);
        });

        return array_values($t);
    }

    /** Totais/KPIs a partir das linhas de atendimento, das OS agrupadas e dos contadores de fotos/não realizadas. */
    public static function totais(array $linhas, array $os, int $fotos, int $naoRealizadas, string $inicio, string $fim): array
    {
        $horas = 0.0;
        $concl = 0;
        foreach ($linhas as $r) {
            $horas += self::horasEntre($r['entrada'] ?? null, $r['saida'] ?? null);
            if (self::concluido($r, $inicio, $fim)) {
                $concl++;
            }
        }
        $final = 0;
        foreach ($os as $o) {
            if ($o['finalizada']) {
                $final++;
            }
        }

        return [
            'os_atendidas' => count($os), 'atendimentos' => count($linhas), 'concluidos' => $concl,
            'horas' => round($horas, 2), 'fotos' => $fotos, 'finalizadas' => $final,
            'nao_realizadas' => $naoRealizadas, 'tecnicos' => count(self::resumoTecnicos($linhas)),
        ];
    }

    /** Série diária completa. $fotosPorDia: [Y-m-d => n]. */
    public static function porDia(array $linhas, array $fotosPorDia, string $inicio, string $fim): array
    {
        $c = [];
        foreach ($linhas as $r) {
            $d = self::diaRef($r, $inicio, $fim);
            $c[$d] = $c[$d] ?? ['atendimentos' => 0, 'concluidos' => 0, 'horas' => 0.0, 'fotos' => 0];
            $c[$d]['atendimentos']++;
            $c[$d]['horas'] += self::horasEntre($r['entrada'] ?? null, $r['saida'] ?? null);
            if (self::concluido($r, $inicio, $fim)) {
                $d2 = substr((string) $r['saida'], 0, 10);
                $c[$d2] = $c[$d2] ?? ['atendimentos' => 0, 'concluidos' => 0, 'horas' => 0.0, 'fotos' => 0];
                $c[$d2]['concluidos']++;
            }
        }
        $out = [];
        $d = new DateTime($inicio);
        $f = new DateTime($fim);
        while ($d <= $f) {
            $k = $d->format('Y-m-d');
            $x = $c[$k] ?? [];
            $out[] = [
                'dia' => $k, 'atendimentos' => (int) ($x['atendimentos'] ?? 0), 'concluidos' => (int) ($x['concluidos'] ?? 0),
                'horas' => round((float) ($x['horas'] ?? 0), 2), 'fotos' => (int) ($fotosPorDia[$k] ?? 0),
            ];
            $d->modify('+1 day');
        }

        return $out;
    }

    /** Status ATUAL das OS atendidas: [['status','total']] por total desc. */
    public static function statusOs(array $os): array
    {
        $c = [];
        foreach ($os as $o) {
            $s = $o['status'] !== '' ? $o['status'] : 'Sem status';
            $c[$s] = ($c[$s] ?? 0) + 1;
        }
        $out = [];
        foreach ($c as $s => $n) {
            $out[] = ['status' => (string) $s, 'total' => $n];
        }
        usort($out, function ($a, $b) {
            return ($b['total'] <=> $a['total']) ?: strcasecmp($a['status'], $b['status']);
        });

        return $out;
    }

    /** Top 10 técnicos por horas + 'Outros' (usuario_id 0); reutiliza Projeto_acompanhamento_model::topTecnicos. */
    public static function horasPorTecnico(array $tecnicos, int $max = 10): array
    {
        $linhas = array_map(function ($t) {
            return ['usuario_id' => $t['usuario_id'], 'nome' => $t['nome'], 'horas' => $t['horas'], 'avancos' => $t['atendimentos']];
        }, $tecnicos);
        $out = [];
        foreach (Projeto_acompanhamento_model::topTecnicos($linhas, $max) as $l) {
            $out[] = ['usuario_id' => (int) $l['usuario_id'], 'nome' => (string) $l['nome'], 'horas' => round((float) $l['horas'], 2), 'atendimentos' => (int) $l['avancos']];
        }

        return $out;
    }

    /** Legenda da foto: etapa/descrição + técnico + data/hora. */
    public static function legendaFoto(array $f): string
    {
        $etapa = self::ETAPAS[(string) ($f['etapa'] ?? '')] ?? ucfirst((string) ($f['etapa'] ?? ''));
        $desc = trim((string) ($f['descricao'] ?? ''));
        $quando = ! empty($f['data']) ? date('d/m/Y H:i', strtotime((string) $f['data'])) : '';

        return implode(' · ', array_filter([$desc !== '' ? ($etapa !== '' ? $etapa . ': ' . $desc : $desc) : $etapa, (string) ($f['tecnico'] ?? ''), $quando], 'strlen'));
    }

    /**
     * Agrupa fotos (idFoto, idOs, cliente, etapa, descricao, tecnico, data) por OS, mais
     * recentes primeiro (OS com a foto mais recente primeiro), limitando os itens por OS.
     */
    public static function agruparFotos(array $fotos, ?int $limite, callable $url): array
    {
        usort($fotos, function ($a, $b) {
            return [(string) $b['data'], (int) $b['idFoto']] <=> [(string) $a['data'], (int) $a['idFoto']];
        });
        $g = [];
        foreach ($fotos as $f) {
            $id = (int) $f['idOs'];
            $g[$id] = $g[$id] ?? ['idOs' => $id, 'cliente' => (string) ($f['cliente'] ?? ''), 'total' => 0, 'restantes' => 0, 'itens' => []];
            $g[$id]['total']++;
            if ($limite === null || count($g[$id]['itens']) < $limite) {
                $g[$id]['itens'][] = ['url' => $url((int) $f['idFoto']), 'legenda' => self::legendaFoto($f)];
            }
        }
        foreach ($g as $id => $x) {
            $g[$id]['restantes'] = $x['total'] - count($x['itens']);
        }

        return array_values($g);
    }

    /* ============================ Banco ============================ */

    /** Linhas de atendimento do período (1 query) + fotos (1) + não realizadas (1). */
    private function carregar(string $inicio, string $fim, array $filtros): array
    {
        $vazio = ['linhas' => [], 'fotos' => [], 'nao' => 0];
        if (! $this->db->table_exists('os_checkin') || ! $this->db->table_exists('os')) {
            return $vazio;
        }
        $usuarioId = (int) ($filtros['usuario_id'] ?? 0);
        $clienteId = (int) ($filtros['cliente_id'] ?? 0);
        $status = trim((string) ($filtros['status'] ?? ''));
        $ini = $inicio . ' 00:00:00';
        $ate = date('Y-m-d', strtotime($fim . ' +1 day')) . ' 00:00:00';

        $this->db->select('c.os_id as idOs, c.data_entrada as entrada, c.data_saida as saida, c.usuarios_id as usuario_id, c.observacao_entrada, c.observacao_saida, u.nome as tecnico, o.status, o.descricaoProduto, o.defeito, cl.nomeCliente as cliente');
        $this->db->from('os_checkin c');
        $this->db->join('os o', 'o.idOs = c.os_id');
        $this->db->join('clientes cl', 'cl.idClientes = o.clientes_id', 'left');
        $this->db->join('usuarios u', 'u.idUsuarios = c.usuarios_id', 'left');
        $this->db->group_start();
        $this->db->group_start()->where('c.data_entrada >=', $ini)->where('c.data_entrada <', $ate)->group_end();
        $this->db->or_group_start()->where('c.data_saida >=', $ini)->where('c.data_saida <', $ate)->group_end();
        $this->db->group_end();
        if ($usuarioId) {
            $this->db->where('c.usuarios_id', $usuarioId);
        }
        if ($clienteId) {
            $this->db->where('o.clientes_id', $clienteId);
        }
        if ($status !== '') {
            $this->db->where('o.status', $status);
        }
        $this->db->order_by('c.data_entrada', 'asc');
        $linhas = [];
        $clientes = [];
        foreach ($this->db->get()->result() as $r) {
            $obs = trim((string) ($r->observacao_entrada ?? ''));
            $obsSaida = trim((string) ($r->observacao_saida ?? ''));
            $linhas[] = [
                'idOs' => (int) $r->idOs, 'cliente' => (string) ($r->cliente ?? ''), 'status' => (string) ($r->status ?? ''),
                'descricao' => self::descricaoCurta($r->descricaoProduto, $r->defeito),
                'entrada' => $r->entrada ?: null, 'saida' => $r->saida ?: null,
                'usuario_id' => (int) $r->usuario_id, 'tecnico' => (string) ($r->tecnico ?? ''),
                'observacao' => implode(' | ', array_filter([$obs, $obsSaida], 'strlen')),
            ];
            $clientes[(int) $r->idOs] = (string) ($r->cliente ?? '');
        }

        // Fotos do atendimento no período (sem o blob), só das OS atendidas
        $fotos = [];
        if ($clientes && $this->db->table_exists('os_fotos_atendimento')) {
            $this->db->select('f.idFoto, f.os_id, f.usuarios_id, f.etapa, f.descricao, f.data_upload, u.nome as tecnico');
            $this->db->from('os_fotos_atendimento f');
            $this->db->join('usuarios u', 'u.idUsuarios = f.usuarios_id', 'left');
            $this->db->where_in('f.os_id', array_keys($clientes));
            $this->db->where('f.data_upload >=', $ini);
            $this->db->where('f.data_upload <', $ate);
            if ($usuarioId) {
                $this->db->where('f.usuarios_id', $usuarioId);
            }
            foreach ($this->db->get()->result() as $f) {
                $fotos[] = [
                    'idFoto' => (int) $f->idFoto, 'idOs' => (int) $f->os_id, 'cliente' => $clientes[(int) $f->os_id] ?? '',
                    'etapa' => (string) $f->etapa, 'descricao' => (string) ($f->descricao ?? ''),
                    'tecnico' => (string) ($f->tecnico ?? ''), 'data' => (string) $f->data_upload,
                ];
            }
        }

        // Serviços não realizados registrados no período
        $nao = 0;
        if ($this->db->table_exists('os_nao_realizada')) {
            $this->db->from('os_nao_realizada nr');
            $this->db->join('os o', 'o.idOs = nr.os_id');
            $this->db->where('nr.data_registro >=', $ini);
            $this->db->where('nr.data_registro <', $ate);
            if ($usuarioId) {
                $this->db->where('nr.usuarios_id', $usuarioId);
            }
            if ($clienteId) {
                $this->db->where('o.clientes_id', $clienteId);
            }
            if ($status !== '') {
                $this->db->where('o.status', $status);
            }
            $nao = (int) $this->db->count_all_results();
        }

        return ['linhas' => $linhas, 'fotos' => $fotos, 'nao' => $nao];
    }

    /** Contagem de fotos [idOs => n] e [dia => n]. */
    private static function contarFotos(array $fotos): array
    {
        $os = [];
        $dia = [];
        foreach ($fotos as $f) {
            $os[(int) $f['idOs']] = ($os[(int) $f['idOs']] ?? 0) + 1;
            $d = substr((string) $f['data'], 0, 10);
            $dia[$d] = ($dia[$d] ?? 0) + 1;
        }

        return [$os, $dia];
    }

    private function consolidar(array $d, string $inicio, string $fim): array
    {
        [$fotosOs] = self::contarFotos($d['fotos']);
        $os = self::agruparOs($d['linhas'], $fotosOs, $inicio, $fim);

        return [
            'os' => $os,
            'tecnicos' => self::resumoTecnicos($d['linhas']),
            'totais' => self::totais($d['linhas'], $os, count($d['fotos']), $d['nao'], $inicio, $fim),
            'linhas' => $d['linhas'],
        ];
    }

    public function relatorio(string $inicio, string $fim, array $filtros = []): array
    {
        $r = $this->consolidar($this->carregar($inicio, $fim, $filtros), $inicio, $fim);
        unset($r['linhas']);

        return $r;
    }

    public function dashboardPeriodo(string $inicio, string $fim, array $filtros = []): array
    {
        $d = $this->carregar($inicio, $fim, $filtros);
        $r = $this->consolidar($d, $inicio, $fim);
        $ant = Projeto_acompanhamento_model::periodoAnterior($inicio, $fim);
        $rAnt = $this->consolidar($this->carregar($ant['inicio'], $ant['fim'], $filtros), $ant['inicio'], $ant['fim']);
        [, $fotosDia] = self::contarFotos($d['fotos']);

        return [
            'kpis' => $r['totais'],
            'kpis_anterior' => $rAnt['totais'],
            'por_dia' => self::porDia($r['linhas'], $fotosDia, $inicio, $fim),
            'status_os' => self::statusOs($r['os']),
            'horas_tecnico' => self::horasPorTecnico($r['tecnicos']),
        ];
    }

    public function fotosPeriodo(string $inicio, string $fim, array $filtros = [], ?int $limitePorOs = self::FOTOS_LIMITE_PADRAO): array
    {
        $d = $this->carregar($inicio, $fim, $filtros);

        return self::agruparFotos($d['fotos'], $limitePorOs, function (int $id) {
            return site_url('checkin/verFotoDB/' . $id);
        });
    }
}
