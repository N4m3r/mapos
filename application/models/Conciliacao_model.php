<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Conciliacao_model extends CI_Model
{
    /** Grava itens novos (ignora FITID repetido na conta). Retorna [novos, repetidos]. */
    public function importar(array $parsed, $usuarioId): array
    {
        $conta = trim(($parsed['conta']['banco'] ?? '') . '-' . ($parsed['conta']['agencia'] ?? '') . '-' . ($parsed['conta']['conta'] ?? ''), '-') ?: 'desconhecida';
        $conta = substr($conta, 0, 100);
        $novos = $repetidos = 0;
        foreach ($parsed['itens'] as $i) {
            $fitid = substr($i['fitid'], 0, 100);
            $existe = $this->db->where('conta', $conta)->where('fitid', $fitid)->count_all_results('conciliacao_itens');
            if ($existe) {
                $repetidos++;
                continue;
            }
            $this->db->insert('conciliacao_itens', [
                'fitid' => $fitid,
                'conta' => $conta,
                'data' => $i['data'],
                'valor' => $i['valor'],
                'descricao' => substr($i['descricao'], 0, 255),
                'status' => 'pendente',
                'criado_em' => date('Y-m-d H:i:s'),
                'usuarios_id' => $usuarioId,
            ]);
            $novos++;
        }

        return [$novos, $repetidos];
    }

    public function pendentes(int $limite = 200): array
    {
        return $this->db->where('status', 'pendente')->order_by('data', 'ASC')->limit($limite)->get('conciliacao_itens')->result();
    }

    public function getItem(int $id)
    {
        return $this->db->where('id', $id)->get('conciliacao_itens')->row();
    }

    private function valorExpr(): string
    {
        return 'IF(valor_desconto = 0, valor, valor_desconto)';
    }

    /** Candidatos em aberto: mesmo valor absoluto, tipo coerente, vencimento +-5 dias. Ordenados por proximidade. */
    public function candidatos($item): array
    {
        $tipo = $item->valor >= 0 ? 'receita' : 'despesa';
        $abs = round(abs((float) $item->valor), 2);
        $this->db->select('idLancamentos, descricao, cliente_fornecedor, data_vencimento, ' . $this->valorExpr() . ' AS valor_final', false);
        $this->db->where('baixado', 0)->where('tipo', $tipo);
        $this->db->where($this->valorExpr() . ' = ' . $this->db->escape($abs), null, false);
        $this->db->where('data_vencimento >=', date('Y-m-d', strtotime($item->data . ' -5 days')));
        $this->db->where('data_vencimento <=', date('Y-m-d', strtotime($item->data . ' +5 days')));
        $this->db->order_by('ABS(DATEDIFF(data_vencimento, ' . $this->db->escape($item->data) . '))', 'ASC', false);

        return $this->db->limit(10)->get('lancamentos')->result();
    }

    public function conciliar(int $itemId, int $lancId): bool
    {
        $item = $this->getItem($itemId);
        if (! $item || $item->status !== 'pendente') {
            return false;
        }
        $tipo = $item->valor >= 0 ? 'receita' : 'despesa';
        $l = $this->db->where('idLancamentos', $lancId)->where('baixado', 0)->where('tipo', $tipo)->get('lancamentos')->row();
        if (! $l) {
            return false;
        }
        $this->db->trans_start();
        $this->db->where('idLancamentos', $lancId)->update('lancamentos', ['baixado' => 1, 'data_pagamento' => $item->data]);
        $this->db->where('id', $itemId)->update('conciliacao_itens', ['status' => 'conciliado', 'lancamentos_id' => $lancId]);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function ignorar(int $itemId): bool
    {
        $item = $this->getItem($itemId);
        if (! $item || $item->status !== 'pendente') {
            return false;
        }
        $this->db->where('id', $itemId)->update('conciliacao_itens', ['status' => 'ignorado']);

        return true;
    }
}
