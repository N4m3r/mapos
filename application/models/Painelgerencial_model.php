<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/** Indicadores do painel gerencial (ADR-008, item 1). */
class Painelgerencial_model extends CI_Model
{
    public const STATUS_CONCLUIDOS = ['Finalizado', 'Faturado'];

    public function indicadores($ini, $fim)
    {
        $r = [
            'abertas' => 0, 'concluidas' => 0, 'tempoAtendimentoH' => null, 'tempoConclusaoDias' => null,
            'retrabalhoPct' => null, 'retrabalhoQtd' => 0, 'ticketMedio' => null, 'faturamento' => 0.0,
            'porTecnico' => [], 'topServicos' => [], 'checkin' => $this->db->table_exists('os_checkin'),
        ];

        $r['abertas'] = (int) $this->db->where('dataInicial >=', $ini)->where('dataInicial <=', $fim)->count_all_results('os');

        $concl = $this->db->select('idOs, clientes_id, usuarios_id, dataInicial, dataFinal, descricaoProduto, desconto, valor_desconto')
            ->where_in('status', self::STATUS_CONCLUIDOS)
            ->where('dataFinal >=', $ini)->where('dataFinal <=', $fim)
            ->get('os')->result();
        $r['concluidas'] = count($concl);
        if (! $concl) {
            return $r;
        }
        $ids = array_map(fn ($o) => (int) $o->idOs, $concl);

        $subtotais = array_fill_keys($ids, 0.0);
        foreach (['produtos_os', 'servicos_os'] as $t) {
            $rows = $this->db->select('os_id, SUM(subTotal) AS s')->where_in('os_id', $ids)->group_by('os_id')->get($t)->result();
            foreach ($rows as $row) {
                $subtotais[(int) $row->os_id] += (float) $row->s;
            }
        }

        // Faturamento/ticket seguem a regra de Os_model (valor_desconto substitui o total quando ha desconto)
        $dias = [];
        $tec = [];
        foreach ($concl as $o) {
            $total = ($o->desconto != 0 && $o->valor_desconto != 0) ? (float) $o->valor_desconto : $subtotais[(int) $o->idOs];
            $r['faturamento'] += $total;
            if ($o->dataInicial && $o->dataFinal && $o->dataFinal >= $o->dataInicial) {
                $dias[] = (strtotime($o->dataFinal) - strtotime($o->dataInicial)) / 86400;
            }
            $k = (int) $o->usuarios_id;
            $tec[$k] = $tec[$k] ?? ['qtd' => 0, 'valor' => 0.0];
            $tec[$k]['qtd']++;
            $tec[$k]['valor'] += $total;
        }
        $r['ticketMedio'] = $r['faturamento'] / $r['concluidas'];
        $r['tempoConclusaoDias'] = $dias ? array_sum($dias) / count($dias) : null;

        if ($tec) {
            $nomes = [];
            foreach ($this->db->select('idUsuarios, nome')->where_in('idUsuarios', array_keys($tec))->get('usuarios')->result() as $u) {
                $nomes[(int) $u->idUsuarios] = $u->nome;
            }
            foreach ($tec as $id => $v) {
                $r['porTecnico'][] = ['tecnico' => $nomes[$id] ?? 'Sem técnico', 'qtd' => $v['qtd'], 'valor' => $v['valor']];
            }
            usort($r['porTecnico'], fn ($a, $b) => $b['valor'] <=> $a['valor']);
        }

        if ($r['checkin']) {
            $row = $this->db->select('AVG(TIMESTAMPDIFF(MINUTE, data_entrada, data_saida)) / 60 AS h', false)
                ->where_in('os_id', $ids)->where('data_saida IS NOT NULL', null, false)->get('os_checkin')->row();
            $r['tempoAtendimentoH'] = ($row && $row->h !== null) ? (float) $row->h : null;
        }

        // Retrabalho: outra OS do mesmo cliente com a mesma descricao, aberta em ate 30 dias apos a conclusao
        foreach ($concl as $o) {
            if (trim((string) $o->descricaoProduto) === '' || ! $o->dataFinal) {
                continue;
            }
            $limite = date('Y-m-d', strtotime($o->dataFinal . ' +30 days'));
            $n = $this->db->where('clientes_id', $o->clientes_id)->where('idOs !=', $o->idOs)
                ->where('descricaoProduto', $o->descricaoProduto)
                ->where('dataInicial >', $o->dataFinal)->where('dataInicial <=', $limite)
                ->count_all_results('os');
            if ($n > 0) {
                $r['retrabalhoQtd']++;
            }
        }
        $r['retrabalhoPct'] = $r['retrabalhoQtd'] * 100 / $r['concluidas'];

        $r['topServicos'] = $this->db->select('servicos.nome, SUM(servicos_os.quantidade) AS qtd, SUM(servicos_os.subTotal) AS valor')
            ->from('servicos_os')->join('servicos', 'servicos.idServicos = servicos_os.servicos_id', 'left')
            ->where_in('servicos_os.os_id', $ids)->group_by('servicos_os.servicos_id, servicos.nome')
            ->order_by('valor', 'DESC')->limit(10)->get()->result_array();

        return $r;
    }
}
