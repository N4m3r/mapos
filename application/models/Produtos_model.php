<?php

class Produtos_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($table, $fields, $where = '', $perpage = 0, $start = 0, $one = false, $array = 'array')
    {
        $this->db->select($fields);
        $this->db->from($table);
        $this->db->order_by('idProdutos', 'desc');
        $this->db->limit($perpage, $start);
        if ($where) {
            $this->db->like('codDeBarra', $where);
            $this->db->or_like('descricao', $where);
        }

        $query = $this->db->get();

        $result = ! $one ? $query->result() : $query->row();

        return $result;
    }

    public function getById($id)
    {
        $this->db->where('idProdutos', $id);
        $this->db->limit(1);

        return $this->db->get('produtos')->row();
    }

    public function add($table, $data)
    {
        $this->db->insert($table, $data);
        if ($this->db->affected_rows() == '1') {
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

    /**
     * Soma ou subtrai do estoque e registra no historico (estoque_movimentacoes).
     *
     * @param array $origem ['tipo' => os|venda|tecnico|api|ajuste, 'referencia' => id da OS/venda, 'observacao' => texto]
     */
    public function updateEstoque($produto, $quantidade, $operacao = '-', array $origem = [])
    {
        $operacao = $operacao === '+' ? '+' : '-';

        $this->db->trans_start();
        $antes = $this->estoqueParaAtualizar($produto);
        $ok = $this->db->query("UPDATE produtos SET estoque = estoque $operacao ? WHERE idProdutos = ?", [$quantidade, $produto]);
        if ($antes !== null) {
            // Reler o valor gravado: a coluna pode arredondar (estoque e inteiro)
            $depois = $this->estoqueParaAtualizar($produto);
            if ($depois !== $antes) {
                $this->registrarMovimentacao($produto, $depois - $antes, $antes, $depois, $origem);
            }
        }
        $this->db->trans_complete();

        return $ok && $this->db->trans_status();
    }

    /**
     * Define o estoque com um valor exato (inventario, edicao do produto) e registra a diferenca.
     */
    public function ajustarEstoque($produto, $novoEstoque, array $origem = [])
    {
        $this->db->trans_start();
        $antes = $this->estoqueParaAtualizar($produto);
        $ok = $this->db->query('UPDATE produtos SET estoque = ? WHERE idProdutos = ?', [$novoEstoque, $produto]);
        if ($antes !== null) {
            $depois = $this->estoqueParaAtualizar($produto);
            if ($depois !== $antes) {
                $this->registrarMovimentacao($produto, $depois - $antes, $antes, $depois, $origem + ['tipo' => 'ajuste']);
            }
        }
        $this->db->trans_complete();

        return $ok && $this->db->trans_status();
    }

    /**
     * Registra uma movimentacao ja aplicada (ex.: estoque inicial no cadastro).
     */
    public function registrarMovimentacao($produto, $quantidade, $anterior, $novo, array $origem = [])
    {
        if (! $this->historicoAtivo()) {
            return;
        }
        $ci = &get_instance();
        $usuario = $origem['usuario'] ?? (isset($ci->session) ? $ci->session->userdata('id_admin') : null);
        $this->db->insert('estoque_movimentacoes', [
            'produtos_id' => (int) $produto,
            'tipo' => $origem['tipo'] ?? 'ajuste',
            'quantidade' => $quantidade,
            'estoque_anterior' => $anterior,
            'estoque_novo' => $novo,
            'referencia_id' => isset($origem['referencia']) ? (int) $origem['referencia'] : null,
            'observacao' => isset($origem['observacao']) ? mb_substr((string) $origem['observacao'], 0, 255) : null,
            'usuarios_id' => $usuario ?: null,
            'criado_em' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Historico de um produto (ou de todos), mais recente primeiro.
     */
    public function getMovimentacoes(array $filtros = [], $limite = 100, $inicio = 0)
    {
        if (! $this->historicoAtivo()) {
            return [];
        }
        $this->filtrarMovimentacoes($filtros);
        $this->db->select('m.*, p.descricao AS produto, u.nome AS usuario')
            ->join('produtos p', 'p.idProdutos = m.produtos_id', 'left')
            ->join('usuarios u', 'u.idUsuarios = m.usuarios_id', 'left')
            ->order_by('m.criado_em', 'DESC')->order_by('m.id', 'DESC')
            ->limit($limite, $inicio);

        return $this->db->get('estoque_movimentacoes m')->result();
    }

    public function contarMovimentacoes(array $filtros = [])
    {
        if (! $this->historicoAtivo()) {
            return 0;
        }
        $this->filtrarMovimentacoes($filtros);

        return $this->db->count_all_results('estoque_movimentacoes m');
    }

    private function filtrarMovimentacoes(array $filtros)
    {
        if (! empty($filtros['produto'])) {
            $this->db->where('m.produtos_id', (int) $filtros['produto']);
        }
        if (! empty($filtros['tipo'])) {
            $this->db->where('m.tipo', $filtros['tipo']);
        }
        if (! empty($filtros['de'])) {
            $this->db->where('m.criado_em >=', $filtros['de'] . ' 00:00:00');
        }
        if (! empty($filtros['ate'])) {
            $this->db->where('m.criado_em <=', $filtros['ate'] . ' 23:59:59');
        }
    }

    /** A coluna produtos.estoque e inteira (padrao do Map-OS)? */
    public function estoqueInteiro()
    {
        static $inteiro = null;
        if ($inteiro === null) {
            $coluna = $this->db->query("SHOW COLUMNS FROM produtos LIKE 'estoque'")->row();
            $inteiro = $coluna ? stripos($coluna->Type, 'int') !== false : true;
        }

        return $inteiro;
    }

    public function historicoAtivo()
    {
        static $ativo = null;
        if ($ativo === null) {
            $ativo = $this->db->table_exists('estoque_movimentacoes');
        }

        return $ativo;
    }

    /** Estoque atual travando a linha ate o fim da transacao (null se o produto nao existe). */
    private function estoqueParaAtualizar($produto)
    {
        $linha = $this->db->query('SELECT estoque FROM produtos WHERE idProdutos = ? FOR UPDATE', [$produto])->row();

        return $linha ? (float) $linha->estoque : null;
    }
}
