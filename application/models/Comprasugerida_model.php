<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Compra sugerida / ponto de pedido (ADR-008, item 3).
 */
class Comprasugerida_model extends CI_Model
{
    public function disponivel()
    {
        return $this->db->table_exists('estoque_movimentacoes');
    }

    /**
     * @return array linhas ordenadas: abaixo do ponto de pedido primeiro
     */
    public function calcular($janela, $prazo, $cobertura, $somenteComprar = true)
    {
        $janela = max(1, (int) $janela);
        $desde = date('Y-m-d H:i:s', strtotime('-' . $janela . ' days'));

        $sql = "SELECT p.idProdutos, p.descricao, p.unidade, p.precoCompra, p.estoque, p.estoqueMinimo,
                    COALESCE(m.saidas, 0) AS saidas
                FROM produtos p
                LEFT JOIN (
                    SELECT produtos_id, SUM(-quantidade) AS saidas
                    FROM estoque_movimentacoes
                    WHERE quantidade < 0 AND tipo NOT IN ('ajuste', 'inventario') AND criado_em >= ?
                    GROUP BY produtos_id
                ) m ON m.produtos_id = p.idProdutos";
        $linhas = $this->db->query($sql, [$desde])->result_array();

        $out = [];
        foreach ($linhas as $l) {
            $estoque = (float) $l['estoque'];
            $minimo = (float) $l['estoqueMinimo'];
            $diario = (float) $l['saidas'] / $janela;
            $ponto = $diario * $prazo + $minimo;
            $sugerida = (int) max(0, ceil(round($diario * ($prazo + $cobertura) + $minimo - $estoque, 6)));
            $l['consumo_diario'] = $diario;
            $l['ponto_pedido'] = $ponto;
            $l['abaixo'] = $estoque <= $ponto && ($diario > 0 || $minimo > 0);
            $l['sugerida'] = $sugerida;
            $l['dias_cobertura'] = $diario > 0 ? $estoque / $diario : null;
            $l['custo'] = $sugerida * (float) $l['precoCompra'];
            if ($somenteComprar && $sugerida <= 0) {
                continue;
            }
            $out[] = $l;
        }

        usort($out, function ($a, $b) {
            if ($a['abaixo'] !== $b['abaixo']) {
                return $a['abaixo'] ? -1 : 1;
            }

            return strcasecmp($a['descricao'], $b['descricao']);
        });

        return $out;
    }
}
