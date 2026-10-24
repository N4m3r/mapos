<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Custo real e margem por OS (ADR-008).
 */
class Margem_model extends CI_Model
{
    public const STATUS = ['Finalizado', 'Faturado'];

    public function salvarCustoHora($valor)
    {
        $valor = (string) max(0, round((float) $valor, 2));
        $existe = $this->db->where('config', 'custo_hora_tecnico')->count_all_results('configuracoes');
        if ($existe) {
            $this->db->where('config', 'custo_hora_tecnico')->update('configuracoes', ['valor' => $valor]);
        } else {
            $this->db->insert('configuracoes', ['config' => 'custo_hora_tecnico', 'valor' => $valor]);
        }
    }

    private function acumula(array &$grupo, $chave, $nome, $receita, $custo, $horas, $margem)
    {
        $grupo[$chave] ??= ['nome' => $nome, 'os' => 0, 'receita' => 0.0, 'custo' => 0.0, 'horas' => 0.0, 'margem' => 0.0, 'margem_pct' => null];
        $grupo[$chave]['os']++;
        $grupo[$chave]['receita'] += $receita;
        $grupo[$chave]['custo'] += $custo;
        $grupo[$chave]['horas'] += $horas;
        $grupo[$chave]['margem'] += $margem;
    }

    /**
     * Retorna ['os', 'tecnicos', 'clientes', 'totais', 'avisos', 'tem_checkin'].
     */
    public function calcular($ini, $fim, $custoHora)
    {
        $temCheckin = $this->db->table_exists('os_checkin');
        $horasSql = $temCheckin
            ? '(SELECT os_id, SUM(TIMESTAMPDIFF(MINUTE, data_entrada, data_saida)) / 60 AS horas, COUNT(*) AS n
                FROM os_checkin WHERE data_entrada IS NOT NULL AND data_saida IS NOT NULL AND data_saida >= data_entrada
                GROUP BY os_id)'
            : '(SELECT 0 AS os_id, 0 AS horas, 0 AS n FROM DUAL WHERE 1 = 0)';

        $sql = "SELECT os.idOs, os.dataFinal, os.status, os.usuarios_id, os.clientes_id,
                    c.nomeCliente, u.nome AS tecnico,
                    COALESCE(p.bruto, 0) AS bruto_produtos, COALESCE(s.bruto, 0) AS bruto_servicos,
                    os.desconto, os.valor_desconto,
                    COALESCE(p.custo, 0) AS custo_pecas, COALESCE(p.sem_custo, 0) AS pecas_sem_custo,
                    COALESCE(h.horas, 0) AS horas, COALESCE(h.n, 0) AS checkins
                FROM os
                LEFT JOIN clientes c ON c.idClientes = os.clientes_id
                LEFT JOIN usuarios u ON u.idUsuarios = os.usuarios_id
                LEFT JOIN (
                    SELECT po.os_id, SUM(po.subTotal) AS bruto,
                        SUM(po.quantidade * COALESCE(pr.precoCompra, 0)) AS custo,
                        SUM(CASE WHEN COALESCE(pr.precoCompra, 0) = 0 THEN 1 ELSE 0 END) AS sem_custo
                    FROM produtos_os po LEFT JOIN produtos pr ON pr.idProdutos = po.produtos_id
                    GROUP BY po.os_id
                ) p ON p.os_id = os.idOs
                LEFT JOIN (
                    SELECT so.os_id,
                        SUM(COALESCE(NULLIF(so.preco, 0), sv.preco, 0) * COALESCE(NULLIF(so.quantidade, 0), 1)) AS bruto
                    FROM servicos_os so LEFT JOIN servicos sv ON sv.idServicos = so.servicos_id
                    GROUP BY so.os_id
                ) s ON s.os_id = os.idOs
                LEFT JOIN {$horasSql} h ON h.os_id = os.idOs
                WHERE os.status IN ('Finalizado', 'Faturado') AND os.dataFinal BETWEEN ? AND ?
                ORDER BY os.dataFinal DESC, os.idOs DESC";
        $rows = $this->db->query($sql, [$ini, $fim])->result();

        $lista = [];
        $tec = [];
        $cli = [];
        $tot = ['os' => 0, 'receita' => 0.0, 'custo_pecas' => 0.0, 'horas' => 0.0, 'custo_mo' => 0.0, 'margem' => 0.0, 'margem_pct' => null];
        $avisos = ['os_pecas_sem_custo' => 0, 'os_sem_checkin' => 0];

        foreach ($rows as $r) {
            $bruto = (float) $r->bruto_produtos + (float) $r->bruto_servicos;
            $receita = ((float) $r->desconto != 0 && (float) $r->valor_desconto != 0) ? (float) $r->valor_desconto : $bruto;
            $horas = (float) $r->horas;
            $custoMo = $horas * $custoHora;
            $custo = (float) $r->custo_pecas + $custoMo;
            $margem = $receita - $custo;
            $item = [
                'id' => (int) $r->idOs, 'dataFinal' => $r->dataFinal, 'cliente' => $r->nomeCliente, 'tecnico' => $r->tecnico,
                'receita' => $receita, 'custo_pecas' => (float) $r->custo_pecas, 'horas' => $horas, 'custo_mo' => $custoMo,
                'margem' => $margem, 'margem_pct' => $receita > 0 ? $margem * 100 / $receita : null,
                'pecas_sem_custo' => (int) $r->pecas_sem_custo > 0, 'sem_checkin' => (int) $r->checkins === 0,
            ];
            $lista[] = $item;
            $avisos['os_pecas_sem_custo'] += $item['pecas_sem_custo'] ? 1 : 0;
            $avisos['os_sem_checkin'] += $item['sem_checkin'] ? 1 : 0;

            $this->acumula($tec, (int) $r->usuarios_id, (string) $r->tecnico, $receita, $custo, $horas, $margem);
            $this->acumula($cli, (int) $r->clientes_id, (string) $r->nomeCliente, $receita, $custo, $horas, $margem);

            $tot['os']++;
            $tot['receita'] += $receita;
            $tot['custo_pecas'] += $item['custo_pecas'];
            $tot['horas'] += $horas;
            $tot['custo_mo'] += $custoMo;
            $tot['margem'] += $margem;
        }

        $pct = static fn ($m, $r) => $r > 0 ? $m * 100 / $r : null;
        $tot['margem_pct'] = $pct($tot['margem'], $tot['receita']);
        foreach ([&$tec, &$cli] as &$grupo) {
            foreach ($grupo as &$g) {
                $g['margem_pct'] = $pct($g['margem'], $g['receita']);
            }
            unset($g);
            uasort($grupo, static fn ($a, $b) => $b['margem'] <=> $a['margem']);
        }
        unset($grupo);

        return ['os' => $lista, 'tecnicos' => $tec, 'clientes' => $cli, 'totais' => $tot, 'avisos' => $avisos, 'tem_checkin' => $temCheckin];
    }
}
