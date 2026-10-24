<?php

/**
 * Controle de caixa e vendas do PDV (ADR-005, F5).
 *
 * Um caixa aberto por operador. Cada venda do PDV gera: venda faturada
 * (origem "pdv"), itens, baixa de estoque, lancamento de receita ja recebido e
 * um movimento de caixa por forma de pagamento (troco sai do dinheiro).
 */
class Caixa_model extends CI_Model
{
    public const FORMAS = [
        'dinheiro' => 'Dinheiro',
        'credito' => 'Cartão de crédito',
        'debito' => 'Cartão de débito',
        'pix' => 'PIX',
        'outros' => 'Outros',
    ];

    public function disponivel()
    {
        return $this->db->table_exists('caixas');
    }

    public function aberto($usuarioId)
    {
        return $this->db->where('usuarios_id', (int) $usuarioId)->where('status', 'aberto')
            ->order_by('id', 'DESC')->get('caixas')->row();
    }

    public function getById($id)
    {
        return $this->db->select('c.*, u.nome AS operador')
            ->join('usuarios u', 'u.idUsuarios = c.usuarios_id', 'left')
            ->where('c.id', (int) $id)->get('caixas c')->row();
    }

    public function listar($limite = 50)
    {
        return $this->db->select('c.*, u.nome AS operador,
                (SELECT COUNT(*) FROM vendas v WHERE v.caixas_id = c.id) AS vendas,
                (SELECT COALESCE(SUM(m.valor), 0) FROM caixa_movimentos m WHERE m.caixas_id = c.id AND m.tipo IN (\'venda\', \'troco\')) AS total_vendas', false)
            ->join('usuarios u', 'u.idUsuarios = c.usuarios_id', 'left')
            ->order_by('c.id', 'DESC')->limit($limite)
            ->get('caixas c')->result();
    }

    public function abrir($usuarioId, $valorAbertura, $observacao = null)
    {
        if ($this->aberto($usuarioId)) {
            throw new RuntimeException('Você já tem um caixa aberto.');
        }
        $this->db->insert('caixas', [
            'usuarios_id' => (int) $usuarioId,
            'status' => 'aberto',
            'valor_abertura' => max(0, round((float) $valorAbertura, 2)),
            'aberto_em' => date('Y-m-d H:i:s'),
            'observacao' => $observacao ?: null,
        ]);

        return (int) $this->db->insert_id();
    }

    public function movimentos($caixaId)
    {
        return $this->db->where('caixas_id', (int) $caixaId)->order_by('id')->get('caixa_movimentos')->result();
    }

    /**
     * Sangria (retirada) ou suprimento (reforco) de dinheiro.
     */
    public function movimentar($caixa, $tipo, $valor, $descricao, $usuarioId)
    {
        $valor = round((float) $valor, 2);
        if (! in_array($tipo, ['sangria', 'suprimento'], true) || $valor <= 0) {
            throw new InvalidArgumentException('Informe um valor maior que zero.');
        }
        if ($tipo === 'sangria' && $valor > $this->totais($caixa)['esperado_dinheiro']) {
            throw new RuntimeException('A sangria é maior que o dinheiro no caixa.');
        }
        $this->registrarMovimento($caixa->id, $tipo, 'dinheiro', $tipo === 'sangria' ? -$valor : $valor, null, $descricao, $usuarioId);
    }

    /**
     * Totais do caixa: por forma (vendas liquidas de troco), sangrias, suprimentos e dinheiro esperado na gaveta.
     */
    public function totais($caixa)
    {
        $porForma = array_fill_keys(array_keys(self::FORMAS), 0.0);
        $sangrias = 0.0;
        $suprimentos = 0.0;
        foreach ($this->movimentos($caixa->id) as $m) {
            $valor = (float) $m->valor;
            if ($m->tipo === 'sangria') {
                $sangrias += -$valor;
            } elseif ($m->tipo === 'suprimento') {
                $suprimentos += $valor;
            } else {
                $porForma[$m->forma] = ($porForma[$m->forma] ?? 0) + $valor;
            }
        }
        $vendas = array_sum($porForma);

        return [
            'por_forma' => array_map(static fn ($v) => round($v, 2), $porForma),
            'vendas' => round($vendas, 2),
            'sangrias' => round($sangrias, 2),
            'suprimentos' => round($suprimentos, 2),
            'esperado_dinheiro' => round((float) $caixa->valor_abertura + $porForma['dinheiro'] + $suprimentos - $sangrias, 2),
        ];
    }

    public function fechar($caixa, $contadoDinheiro, $observacao, $usuarioId)
    {
        $totais = $this->totais($caixa);
        $contado = round((float) $contadoDinheiro, 2);
        $this->db->where('id', $caixa->id)->where('status', 'aberto')->update('caixas', [
            'status' => 'fechado',
            'fechado_em' => date('Y-m-d H:i:s'),
            'contado_dinheiro' => $contado,
            'esperado_dinheiro' => $totais['esperado_dinheiro'],
            'resumo_fechamento' => json_encode($totais + ['diferenca_dinheiro' => round($contado - $totais['esperado_dinheiro'], 2)]),
            'observacao' => trim(($caixa->observacao ? $caixa->observacao . "\n" : '') . (string) $observacao) ?: null,
        ]);

        return $totais + ['contado_dinheiro' => $contado, 'diferenca_dinheiro' => round($contado - $totais['esperado_dinheiro'], 2)];
    }

    /**
     * Registra uma venda do PDV.
     *
     * @param array $itens [['produto' => id, 'quantidade' => n], ...] (preco vem do cadastro)
     * @param array $pagamentos [['forma' => dinheiro|credito|debito|pix|outros, 'valor' => x], ...]
     * @return array{venda: int, total: float, desconto: float, liquido: float, troco: float}
     */
    public function registrarVenda($caixa, $usuarioId, array $itens, $desconto, array $pagamentos, $clienteId = null, $cpf = null)
    {
        // Itens com preco do cadastro (o navegador nao define preco)
        $linhas = [];
        $total = 0.0;
        foreach ($itens as $item) {
            $produto = $this->db->where('idProdutos', (int) ($item['produto'] ?? 0))->get('produtos')->row();
            $quantidade = (int) ($item['quantidade'] ?? 0);
            if (! $produto || $quantidade <= 0) {
                throw new InvalidArgumentException('Produto ou quantidade inválida no carrinho.');
            }
            $preco = round((float) $produto->precoVenda, 2);
            $subtotal = round($preco * $quantidade, 2);
            $linhas[] = ['produto' => $produto, 'quantidade' => $quantidade, 'preco' => $preco, 'subtotal' => $subtotal];
            $total += $subtotal;
        }
        if (! $linhas) {
            throw new InvalidArgumentException('O carrinho está vazio.');
        }
        $total = round($total, 2);
        $desconto = round(max(0, (float) $desconto), 2);
        if ($desconto > $total) {
            throw new InvalidArgumentException('O desconto é maior que o total da venda.');
        }
        $liquido = round($total - $desconto, 2);

        // Pagamentos: precisam cobrir o valor; troco so pode sair do dinheiro
        $pagos = [];
        foreach ($pagamentos as $p) {
            $forma = (string) ($p['forma'] ?? '');
            $valor = round((float) ($p['valor'] ?? 0), 2);
            if (! isset(self::FORMAS[$forma]) || $valor <= 0) {
                continue;
            }
            $pagos[$forma] = ($pagos[$forma] ?? 0) + $valor;
        }
        $somaPagos = round(array_sum($pagos), 2);
        if ($somaPagos + 0.001 < $liquido) {
            throw new InvalidArgumentException('Os pagamentos (R$ ' . number_format($somaPagos, 2, ',', '.') . ') não cobrem o total (R$ ' . number_format($liquido, 2, ',', '.') . ').');
        }
        $troco = round($somaPagos - $liquido, 2);
        if ($troco > 0 && $troco > ($pagos['dinheiro'] ?? 0) + 0.001) {
            throw new InvalidArgumentException('Troco só é possível com pagamento em dinheiro.');
        }

        $clienteId = (int) $clienteId ?: $this->consumidorFinal();
        $cliente = $this->db->select('nomeCliente')->where('idClientes', $clienteId)->get('clientes')->row();
        $formasTexto = implode(', ', array_map(static fn ($f) => self::FORMAS[$f], array_keys($pagos)));
        $hoje = date('Y-m-d');
        $controlaEstoque = (bool) ($this->db->where('config', 'control_estoque')->get('configuracoes')->row()->valor ?? false);

        $this->load->model('produtos_model');
        $this->db->trans_start();

        $this->db->insert('vendas', [
            'dataVenda' => $hoje,
            'valorTotal' => $total,
            'desconto' => $desconto,
            'tipo_desconto' => 'real',
            'valor_desconto' => $liquido,
            'faturado' => 1,
            'status' => 'Faturado',
            'clientes_id' => $clienteId,
            'usuarios_id' => (int) $usuarioId,
            'origem' => 'pdv',
            'caixas_id' => (int) $caixa->id,
            'observacoes' => $cpf ? 'CPF na nota: ' . $cpf : null,
        ]);
        $vendaId = (int) $this->db->insert_id();

        foreach ($linhas as $l) {
            $this->db->insert('itens_de_vendas', [
                'quantidade' => $l['quantidade'],
                'subTotal' => $l['subtotal'],
                'produtos_id' => $l['produto']->idProdutos,
                'preco' => $l['preco'],
                'vendas_id' => $vendaId,
            ]);
            if ($controlaEstoque) {
                $this->produtos_model->updateEstoque($l['produto']->idProdutos, $l['quantidade'], '-', ['tipo' => 'venda', 'referencia' => $vendaId, 'observacao' => 'PDV']);
            }
        }

        $this->db->insert('lancamentos', [
            'descricao' => 'Venda PDV #' . $vendaId,
            'valor' => $total,
            'desconto' => $desconto,
            'tipo_desconto' => 'real',
            'valor_desconto' => $liquido,
            'data_vencimento' => $hoje,
            'data_pagamento' => $hoje,
            'baixado' => 1,
            'cliente_fornecedor' => $cliente->nomeCliente ?? 'Consumidor Final',
            'forma_pgto' => $formasTexto,
            'tipo' => 'receita',
            'clientes_id' => $clienteId,
            'vendas_id' => $vendaId,
            'usuarios_id' => (int) $usuarioId,
        ]);
        $this->db->where('idVendas', $vendaId)->update('vendas', ['lancamentos_id' => (int) $this->db->insert_id()]);

        foreach ($pagos as $forma => $valor) {
            $this->registrarMovimento($caixa->id, 'venda', $forma, $valor, $vendaId, 'Venda #' . $vendaId, $usuarioId);
        }
        if ($troco > 0) {
            $this->registrarMovimento($caixa->id, 'troco', 'dinheiro', -$troco, $vendaId, 'Troco da venda #' . $vendaId, $usuarioId);
        }

        $this->db->trans_complete();
        if (! $this->db->trans_status()) {
            throw new RuntimeException('Erro ao gravar a venda. Nada foi registrado.');
        }

        return ['venda' => $vendaId, 'total' => $total, 'desconto' => $desconto, 'liquido' => $liquido, 'troco' => $troco];
    }

    /**
     * Pagamentos de uma venda do PDV no formato da NFC-e (troco separado).
     *
     * @return array{pagamentos: array<int, array{forma: string, valor: float}>, troco: float}
     */
    public function pagamentosDaVenda($vendaId)
    {
        $pagamentos = [];
        $troco = 0.0;
        foreach ($this->db->where('vendas_id', (int) $vendaId)->get('caixa_movimentos')->result() as $m) {
            if ($m->tipo === 'troco') {
                $troco += -(float) $m->valor;
            } elseif ($m->tipo === 'venda') {
                $pagamentos[] = ['forma' => $m->forma, 'valor' => (float) $m->valor];
            }
        }

        return ['pagamentos' => $pagamentos, 'troco' => round($troco, 2)];
    }

    /** Cliente "Consumidor Final" (criado na primeira venda sem cliente). */
    public function consumidorFinal()
    {
        $cliente = $this->db->where('nomeCliente', 'Consumidor Final')->where('documento', '')->get('clientes')->row();
        if ($cliente) {
            return (int) $cliente->idClientes;
        }
        $this->db->insert('clientes', [
            'nomeCliente' => 'Consumidor Final',
            'documento' => '',
            'telefone' => '',
            'email' => '',
            'senha' => '',
            'dataCadastro' => date('Y-m-d'),
        ]);

        return (int) $this->db->insert_id();
    }

    private function registrarMovimento($caixaId, $tipo, $forma, $valor, $vendaId, $descricao, $usuarioId)
    {
        $this->db->insert('caixa_movimentos', [
            'caixas_id' => (int) $caixaId,
            'tipo' => $tipo,
            'forma' => $forma,
            'valor' => round((float) $valor, 2),
            'vendas_id' => $vendaId ? (int) $vendaId : null,
            'descricao' => $descricao ? mb_substr((string) $descricao, 0, 255) : null,
            'usuarios_id' => $usuarioId ? (int) $usuarioId : null,
            'criado_em' => date('Y-m-d H:i:s'),
        ]);
    }
}
