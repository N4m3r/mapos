<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Cobrancas_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($table, $fields, $where = '', $perpage = 0, $start = 0, $one = false, $array = 'array')
    {
        $this->db->select($fields, 'vendas.*,os.*');
        $this->db->from($table);
        $this->db->limit($perpage, $start);
        $this->db->order_by('idCobranca', 'desc');
        if ($where) {
            $this->db->where($where);
        }

        $query = $this->db->get();
        $result = ! $one ? $query->result() : $query->row();

        return $result;
    }

    /**
     * Todas as cobrancas com nome do cliente (classificacao/filtro de status
     * e feita em PHP pelo cobranca_helper, pois o status depende do gateway).
     */
    public function listarComCliente($busca = '')
    {
        $this->db->select('cobrancas.*, clientes.nomeCliente');
        $this->db->from('cobrancas');
        $this->db->join('clientes', 'clientes.idClientes = cobrancas.clientes_id', 'left');
        if ($busca !== '') {
            $this->db->like('clientes.nomeCliente', $busca);
        }
        $this->db->order_by('cobrancas.idCobranca', 'desc');

        return $this->db->get()->result();
    }

    /**
     * OS (Finalizado/Faturado) e vendas faturadas com algo ainda a cobrar,
     * com cliente e valor (em reais, já com desconto). Documento cobrado só em
     * parte (cobrança por item) aparece com o valor do que falta.
     * Cobranças canceladas não contam. Ordenadas das mais antigas.
     */
    public function aCobrar($busca = '')
    {
        get_instance()->load->helper('cobranca');

        $like = '';
        $binds = [];
        if ($busca !== '') {
            $like = ' AND c.nomeCliente LIKE ?';
            $binds[] = '%' . $this->db->escape_like_str($busca) . '%';
        }

        // Cobrança ativa (não cancelada) que cobre o documento inteiro / só alguns itens.
        $ativa = 'LOWER(cb.status) NOT IN (' . implode(',', array_map([$this->db, 'escape'], cobranca_status_cancelados())) . ')';
        $porItem = $this->db->field_exists('itens_ref', 'cobrancas');
        $inteira = $porItem ? "$ativa AND (cb.itens_ref IS NULL OR cb.itens_ref = '')" : $ativa;
        $parcial = $porItem ? "$ativa AND cb.itens_ref <> ''" : '0';

        $osTotal = '(COALESCE((SELECT SUM(po.subTotal) FROM produtos_os po WHERE po.os_id = os.idOs),0)'
            . ' + COALESCE((SELECT SUM(so.preco * so.quantidade) FROM servicos_os so WHERE so.os_id = os.idOs),0))';
        $sqlOs = "SELECT 'os' AS tipo, os.idOs AS ref_id, c.nomeCliente, os.dataFinal AS data_ref,
                CASE WHEN os.desconto <> 0 AND os.valor_desconto <> 0 THEN os.valor_desconto ELSE $osTotal END AS valor,
                $osTotal AS bruto,
                (SELECT GROUP_CONCAT(cb.itens_ref) FROM cobrancas cb WHERE cb.os_id = os.idOs AND $parcial) AS cobrados
            FROM os JOIN clientes c ON c.idClientes = os.clientes_id
            WHERE (os.status IN ('Finalizado','Faturado') OR os.faturado = 1)
              AND os.status <> 'Cancelado'
              AND NOT EXISTS (SELECT 1 FROM cobrancas cb WHERE cb.os_id = os.idOs AND $inteira)$like";

        $vTotal = 'COALESCE((SELECT SUM(iv.subTotal) FROM itens_de_vendas iv WHERE iv.vendas_id = vendas.idVendas),0)';
        $sqlV = "SELECT 'venda' AS tipo, vendas.idVendas AS ref_id, c.nomeCliente, vendas.dataVenda AS data_ref,
                CASE WHEN vendas.valor_desconto <> 0 THEN vendas.valor_desconto ELSE $vTotal END AS valor,
                $vTotal AS bruto,
                (SELECT GROUP_CONCAT(cb.itens_ref) FROM cobrancas cb WHERE cb.vendas_id = vendas.idVendas AND $parcial) AS cobrados
            FROM vendas JOIN clientes c ON c.idClientes = vendas.clientes_id
            WHERE vendas.faturado = 1
              AND NOT EXISTS (SELECT 1 FROM cobrancas cb WHERE cb.vendas_id = vendas.idVendas AND $inteira)$like";

        $rows = $this->db->query("($sqlOs) UNION ALL ($sqlV) ORDER BY data_ref ASC", array_merge($binds, $binds))->result();

        foreach ($rows as $r) {
            if ($r->cobrados) {
                $r->valor = $this->valorRestante($r);
                $r->parcial = true;
            }
        }

        return array_values(array_filter($rows, static function ($r) {
            return (float) $r->valor > 0;
        }));
    }

    /** Valor (com desconto proporcional) dos itens ainda não cobrados de um documento. */
    private function valorRestante($r)
    {
        $cobrados = array_flip(array_filter(explode(',', (string) $r->cobrados)));
        $itens = $r->tipo === 'os'
            ? $this->db->query("SELECT CONCAT('p:', idProdutos_os) AS chave, subTotal AS valor FROM produtos_os WHERE os_id = ?
                UNION ALL SELECT CONCAT('s:', idServicos_os), preco * quantidade FROM servicos_os WHERE os_id = ?", [$r->ref_id, $r->ref_id])->result()
            : $this->db->query("SELECT CONCAT('p:', idItens) AS chave, subTotal AS valor FROM itens_de_vendas WHERE vendas_id = ?", [$r->ref_id])->result();

        $resta = 0.0;
        foreach ($itens as $i) {
            if (! isset($cobrados[$i->chave])) {
                $resta += (float) $i->valor;
            }
        }
        $fator = (float) $r->bruto > 0 ? (float) $r->valor / (float) $r->bruto : 1.0;

        return round($resta * $fator, 2);
    }

    public function getById($id)
    {
        $this->db->select('cobrancas.*, clientes.*');
        $this->db->from('cobrancas');
        $this->db->where('cobrancas.idCobranca', $id);
        $this->db->join('clientes', 'clientes.idClientes = cobrancas.clientes_id');
        $this->db->limit(1);

        return $this->db->get()->row();
    }

    public function getByOs($id)
    {
        return $this->db->query('SELECT DISTINCT `cobrancas`.*,`clientes`.*,`os`.* FROM `cobrancas`,`clientes`,`os` WHERE `charge_id` = ? AND `os`.`idOs` = `cobrancas`.`os_id`', [(int) $id])->row();
    }

    public function getByVendas($id)
    {
        return $this->db->query('SELECT DISTINCT `cobrancas`.*,`clientes`.*,`vendas`.* FROM `cobrancas`,`clientes`,`vendas` WHERE `charge_id` = ? AND `vendas`.`idVendas` = `cobrancas`.`vendas_id`', [(int) $id])->row();
    }

    /**
     * Cobrança pelo id da fatura do gateway (charge_id), com dados do cliente.
     * Usada pelo webhook para localizar a cobrança que foi paga.
     */
    public function getByChargeId($chargeId)
    {
        $this->db->select('cobrancas.*, clientes.*');
        $this->db->from('cobrancas');
        $this->db->where('cobrancas.charge_id', $chargeId);
        $this->db->join('clientes', 'clientes.idClientes = cobrancas.clientes_id', 'left');
        $this->db->limit(1);

        return $this->db->get()->row();
    }

    /**
     * Dá baixa no(s) lançamento(s) financeiro(s) da OS/venda de origem da
     * cobrança (marca como recebido). Best-effort: se não achar lançamento,
     * apenas não faz nada. Retorna a quantidade de lançamentos baixados.
     */
    public function darBaixaLancamento($cobranca)
    {
        if (! $cobranca) {
            return 0;
        }
        if (! $this->db->table_exists('lancamentos')) {
            return 0;
        }

        $update = [
            'baixado' => 1,
            'data_pagamento' => date('Y-m-d'),
            'forma_pgto' => 'Boleto/PIX (Cora)',
        ];

        $this->db->where('baixado', 0);
        if (! empty($cobranca->vendas_id)) {
            // Venda: lançamento vinculado por vendas_id.
            $this->db->where('vendas_id', $cobranca->vendas_id);
        } elseif (! empty($cobranca->os_id)) {
            // OS: lançamento de faturamento segue a convenção exata "Fatura de OS - #<id>".
            $this->db->where('descricao', 'Fatura de OS - #' . $cobranca->os_id);
        } else {
            return 0;
        }
        $this->db->update('lancamentos');

        return $this->db->affected_rows();
    }

    /**
     * Cobrança ativa (não cancelada) vinculada a uma nota fiscal.
     * Usada para bloquear boleto duplicado e exibir o boleto na OS.
     */
    public function getByNota($idNota)
    {
        return $this->db
            ->where('nota_id', $idNota)
            ->where_not_in('status', ['CANCELLED', 'cancelada'])
            ->order_by('idCobranca', 'DESC')
            ->limit(1)
            ->get('cobrancas')
            ->row();
    }

    /**
     * Mapa de cobranças indexado por nota_id, para a aba de notas da OS.
     */
    public function getByNotaIds(array $ids)
    {
        $mapa = [];
        if (empty($ids)) {
            return $mapa;
        }
        $rows = $this->db
            ->where_in('nota_id', $ids)
            ->order_by('idCobranca', 'ASC')
            ->get('cobrancas')
            ->result();
        foreach ($rows as $row) {
            // A última cobrança (maior id) prevalece por nota.
            $mapa[$row->nota_id] = $row;
        }

        return $mapa;
    }

    /**
     * Mapa nota_id => LISTA de cobranças (todas as parcelas), para a aba de
     * notas da OS exibir cada boleto do parcelamento.
     */
    public function getListByNotaIds(array $ids)
    {
        $mapa = [];
        if (empty($ids)) {
            return $mapa;
        }
        $rows = $this->db
            ->where_in('nota_id', $ids)
            ->order_by('idCobranca', 'ASC')
            ->get('cobrancas')
            ->result();
        foreach ($rows as $row) {
            $mapa[$row->nota_id][] = $row;
        }

        return $mapa;
    }

    public function add($table, $data, $returnId = false)
    {
        $this->db->insert($table, $data);
        if ($this->db->affected_rows() == '1') {
            if ($returnId == true) {
                return $this->db->insert_id($table);
            }

            return true;
        }

        return false;
    }

    public function edit($table, $data, $fieldID, $ID)
    {
        $this->db->where($fieldID, $ID);
        $this->db->update($table, $data);

        if ($this->db->affected_rows() >= 0) {
            return true;
        }

        return false;
    }

    public function delete($table, $fieldID, $ID)
    {
        $this->db->where($fieldID, $ID);
        $this->db->delete($table);
        if ($this->db->affected_rows() == '1') {
            return true;
        }

        return false;
    }

    public function count($table)
    {
        return $this->db->count_all($table);
    }

    public function atualizarStatus($idCobranca)
    {
        $cobranca = $this->getById($idCobranca);
        if (empty($cobranca)) {
            return $this->session->set_flashdata('error', 'Cobrança não existe!');
        }

        $gatewayDePagamento = $cobranca->payment_gateway;
        $this->load->library("Gateways/$gatewayDePagamento", null, 'PaymentGateway');

        $result = $this->PaymentGateway->atualizarDados($cobranca->idCobranca);

        return $result;
    }

    public function confirmarPagamento($idCobranca)
    {
        $cobranca = $this->getById($idCobranca);
        if (empty($cobranca)) {
            return $this->session->set_flashdata('error', 'Cobrança não existe!');
        }

        $gatewayDePagamento = $cobranca->payment_gateway;
        $this->load->library("Gateways/$gatewayDePagamento", null, 'PaymentGateway');

        $result = $this->PaymentGateway->confirmarPagamento($cobranca->idCobranca);

        return $result;
    }

    public function cancelarPagamento($idCobranca)
    {
        $cobranca = $this->getById($idCobranca);
        if (empty($cobranca)) {
            return $this->session->set_flashdata('error', 'Cobrança não existe!');
        }

        $gatewayDePagamento = $cobranca->payment_gateway;
        $this->load->library("Gateways/$gatewayDePagamento", null, 'PaymentGateway');

        $result = $this->PaymentGateway->cancelar($cobranca->idCobranca);

        return $result;
    }

    public function enviarEmail($idCobranca)
    {
        $cobranca = $this->getById($idCobranca);
        if (empty($cobranca)) {
            return $this->session->set_flashdata('error', 'Cobrança não existe!');
        }

        $gatewayDePagamento = $cobranca->payment_gateway;
        $this->load->library("Gateways/$gatewayDePagamento", null, 'PaymentGateway');

        $result = $this->PaymentGateway->enviarPorEmail($cobranca->idCobranca);

        return $result;
    }
}
