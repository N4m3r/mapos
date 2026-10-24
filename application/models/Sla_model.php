<?php

/**
 * Painel de SLA das OS (ADR-005, F6).
 *
 * Usa os prazos que a OS ja grava a partir do contrato/prioridade
 * (sla_resposta_prazo, sla_solucao_prazo) e as marcas de cumprimento
 * (sla_resposta_em ao entrar em atendimento, sla_solucao_em ao finalizar).
 */
class Sla_model extends CI_Model
{
    /** Vence em ate tantas horas = "em risco". */
    public const HORAS_RISCO = 4;

    public const STATUS_FECHADOS = ['Finalizado', 'Faturado', 'Cancelado'];

    public function disponivel()
    {
        return $this->db->field_exists('sla_solucao_prazo', 'os');
    }

    /**
     * Situacao de um prazo: 'cumprido', 'atrasado' (cumprido depois do prazo), 'estourado'
     * (vencido e pendente), 'risco' (vence em ate HORAS_RISCO), 'no_prazo' ou null sem prazo.
     */
    public static function situacao($prazo, $cumpridoEm, $agora = null)
    {
        if (empty($prazo)) {
            return null;
        }
        $prazoTs = strtotime($prazo);
        if (! empty($cumpridoEm)) {
            return strtotime($cumpridoEm) <= $prazoTs ? 'cumprido' : 'atrasado';
        }
        $agora = $agora ?? time();
        if ($prazoTs < $agora) {
            return 'estourado';
        }

        return $prazoTs - $agora <= self::HORAS_RISCO * 3600 ? 'risco' : 'no_prazo';
    }

    /**
     * Situacao geral da OS: a pior entre resposta e solucao.
     */
    public static function situacaoGeral($os, $agora = null)
    {
        $ordem = ['estourado' => 4, 'risco' => 3, 'atrasado' => 2, 'no_prazo' => 1, 'cumprido' => 0];
        $pior = null;
        foreach ([[$os->sla_resposta_prazo, $os->sla_resposta_em], [$os->sla_solucao_prazo, $os->sla_solucao_em]] as [$prazo, $em]) {
            $s = self::situacao($prazo, $em, $agora);
            if ($s !== null && ($pior === null || $ordem[$s] > $ordem[$pior])) {
                $pior = $s;
            }
        }

        return $pior;
    }

    /**
     * OS abertas com prazo de SLA, com a situacao calculada, da mais urgente para a menos.
     *
     * @param array $filtros tecnico, contrato, prioridade, situacao (estourado|risco|no_prazo)
     */
    public function abertas(array $filtros = [])
    {
        if (! $this->disponivel()) {
            return [];
        }
        $this->db->select('o.idOs, o.status, o.prioridade, o.dataInicial, o.sla_resposta_prazo, o.sla_resposta_em,
                o.sla_solucao_prazo, o.sla_solucao_em, o.tecnico_responsavel, c.nomeCliente,
                u.nome AS tecnico, ct.descricao AS contrato, ct.codigo AS contrato_codigo', false)
            ->from('os o')
            ->join('clientes c', 'c.idClientes = o.clientes_id', 'left')
            ->join('usuarios u', 'u.idUsuarios = o.tecnico_responsavel', 'left')
            ->join('contratos ct', 'ct.idContrato = o.contrato_id', 'left')
            ->where_not_in('o.status', self::STATUS_FECHADOS)
            ->group_start()
                ->where('o.sla_resposta_prazo IS NOT NULL', null, false)
                ->or_where('o.sla_solucao_prazo IS NOT NULL', null, false)
            ->group_end();
        if (! empty($filtros['tecnico'])) {
            $this->db->where('o.tecnico_responsavel', (int) $filtros['tecnico']);
        }
        if (! empty($filtros['contrato'])) {
            $this->db->where('o.contrato_id', (int) $filtros['contrato']);
        }
        if (! empty($filtros['prioridade'])) {
            $this->db->where('o.prioridade', $filtros['prioridade']);
        }

        $agora = time();
        $lista = [];
        foreach ($this->db->get()->result() as $os) {
            $os->situacao = self::situacaoGeral($os, $agora);
            $os->situacao_resposta = self::situacao($os->sla_resposta_prazo, $os->sla_resposta_em, $agora);
            $os->situacao_solucao = self::situacao($os->sla_solucao_prazo, $os->sla_solucao_em, $agora);
            // Proximo prazo pendente (o que vale para ordenar e para o "falta")
            $pendentes = [];
            if ($os->sla_resposta_prazo && ! $os->sla_resposta_em) {
                $pendentes[] = strtotime($os->sla_resposta_prazo);
            }
            if ($os->sla_solucao_prazo && ! $os->sla_solucao_em) {
                $pendentes[] = strtotime($os->sla_solucao_prazo);
            }
            $os->proximo_prazo = $pendentes ? min($pendentes) : null;
            if (! empty($filtros['situacao']) && $os->situacao !== $filtros['situacao']) {
                continue;
            }
            $lista[] = $os;
        }
        usort($lista, static fn ($a, $b) => ($a->proximo_prazo ?? PHP_INT_MAX) <=> ($b->proximo_prazo ?? PHP_INT_MAX));

        return $lista;
    }

    /**
     * Numeros do painel: abertas com SLA, estouradas, em risco e cumprimento no mes.
     */
    public function indicadores(array $abertas)
    {
        $kpis = ['abertas' => count($abertas), 'estourado' => 0, 'risco' => 0, 'cumpridas_mes' => 0, 'solucionadas_mes' => 0, 'percentual_mes' => null];
        foreach ($abertas as $os) {
            if (isset($kpis[$os->situacao])) {
                $kpis[$os->situacao]++;
            }
        }

        if ($this->disponivel()) {
            $linha = $this->db->select('COUNT(*) AS total, SUM(sla_solucao_em <= sla_solucao_prazo) AS no_prazo', false)
                ->where('sla_solucao_prazo IS NOT NULL', null, false)
                ->where('sla_solucao_em >=', date('Y-m-01 00:00:00'))
                ->get('os')->row();
            $kpis['solucionadas_mes'] = (int) ($linha->total ?? 0);
            $kpis['cumpridas_mes'] = (int) ($linha->no_prazo ?? 0);
            if ($kpis['solucionadas_mes'] > 0) {
                $kpis['percentual_mes'] = round(100 * $kpis['cumpridas_mes'] / $kpis['solucionadas_mes']);
            }
        }

        return $kpis;
    }
}
