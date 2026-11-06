<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Dados do painel inicial (Mapos::index).
 *
 * Uma leitura por bloco: contagem por status, OS que pedem atencao, fila de
 * cobranca, estoque minimo e as series dos graficos. As colunas criadas por
 * migration (data_agendamento, tecnico_responsavel) sao opcionais: o painel
 * abre mesmo num banco instalado antes delas.
 */
class Painel_model extends CI_Model
{
    /** Status que ainda exigem acao da equipe (manter igual a Painel_inicio::EM_ABERTO). */
    const EM_ABERTO = ['Aberto', 'Orçamento', 'Negociação', 'Aprovado', 'Aguardando Peças', 'Em Andamento'];

    /** Status que encerram o atendimento. */
    const CONCLUIDOS = ['Finalizado', 'Faturado'];

    /** Cache do resultado de field_exists (uma consulta por requisicao). */
    private $colunas = [];

    private function temColuna($coluna, $tabela = 'os')
    {
        $chave = $tabela . '.' . $coluna;
        if (! array_key_exists($chave, $this->colunas)) {
            $this->colunas[$chave] = $this->db->field_exists($coluna, $tabela);
        }

        return $this->colunas[$chave];
    }

    /** Expressao de receita liquida (mesma regra das estatisticas financeiras). */
    private function receitaLiquida()
    {
        return "valor - (IF(tipo_desconto = 'real', desconto, (desconto * valor) / 100))";
    }

    /** Contagem de OS por status: [status => quantidade]. */
    public function contagemPorStatus()
    {
        $query = $this->db->query('SELECT status, COUNT(*) AS total FROM os GROUP BY status');
        $contagens = [];
        foreach (($query ? $query->result() : []) as $linha) {
            $contagens[(string) $linha->status] = (int) $linha->total;
        }

        return $contagens;
    }

    /**
     * Quantas OS estao atrasadas e quantas estao marcadas para hoje.
     * Considera data_agendamento quando a coluna existe; senao usa dataFinal.
     */
    public function agendaResumo()
    {
        $hoje = date('Y-m-d');
        $agendamento = $this->temColuna('data_agendamento');
        $campo = $agendamento ? 'data_agendamento' : 'dataFinal';
        $encerrados = array_merge(self::CONCLUIDOS, ['Cancelado']);

        // Atrasada: data combinada ja venceu e a OS continua em andamento
        $this->db->where_not_in('status', $encerrados);
        $this->db->where($campo . ' IS NOT NULL', null, false);
        $this->db->where($campo . ' <', $agendamento ? date('Y-m-d H:i:s') : $hoje);
        $atrasadas = (int) $this->db->count_all_results('os');

        // Marcada para hoje
        $this->db->where_not_in('status', $encerrados);
        $this->db->where($campo . ' >=', $agendamento ? $hoje . ' 00:00:00' : $hoje);
        $this->db->where($campo . ' <=', $agendamento ? $hoje . ' 23:59:59' : $hoje);
        $hojeQtd = (int) $this->db->count_all_results('os');

        return ['atrasadas' => $atrasadas, 'hoje' => $hojeQtd];
    }

    /**
     * OS em aberto ordenadas pela data combinada (mais antigas primeiro).
     * Cada item traz o texto de prazo pronto para a tela.
     */
    public function osEmAberto($limite = 8)
    {
        $campo = $this->temColuna('data_agendamento') ? 'data_agendamento' : 'dataFinal';
        $tecnico = $this->temColuna('tecnico_responsavel');

        $this->db->select('os.idOs, os.status, os.dataInicial, os.dataFinal, os.descricaoProduto'
            . ($this->temColuna('data_agendamento') ? ', os.data_agendamento' : '')
            . ($tecnico ? ', os.tecnico_responsavel' : '')
            . ', clientes.nomeCliente, clientes.celular, clientes.telefone'
            . ($tecnico ? ', usuarios.nome AS nome_tecnico' : ''), false);
        $this->db->from('os');
        $this->db->join('clientes', 'clientes.idClientes = os.clientes_id', 'left');
        if ($tecnico) {
            $this->db->join('usuarios', 'usuarios.idUsuarios = os.tecnico_responsavel', 'left');
        }
        $this->db->where_in('os.status', self::EM_ABERTO);
        $this->db->order_by("COALESCE(os.{$campo}, os.dataFinal, os.dataInicial)", 'ASC', false);
        $this->db->order_by('os.idOs', 'DESC');
        $this->db->limit($limite);

        $query = $this->db->get();

        return $query ? $query->result() : [];
    }

    /**
     * Fila de cobranca: lancamentos pendentes ordenados por vencimento.
     * $somenteVencidos limita aos que ja passaram da data de vencimento.
     */
    public function lancamentosPendentes($limite = 6, $somenteVencidos = false)
    {
        $this->db->select('idLancamentos, tipo, cliente_fornecedor, descricao, data_vencimento, valor, valor_desconto, desconto, tipo_desconto');
        $this->db->from('lancamentos');
        $this->db->where('baixado', 0);
        if ($somenteVencidos) {
            $this->db->where('data_vencimento <', date('Y-m-d'));
        }
        $this->db->order_by('data_vencimento', 'ASC');
        $this->db->limit($limite);

        $query = $this->db->get();

        return $query ? $query->result() : [];
    }

    /** Totais da fila: receita e despesa pendentes, vencidas e a vencer. */
    public function totaisPendentes()
    {
        $hoje = date('Y-m-d');
        $sql = 'SELECT '
            . "SUM(CASE WHEN tipo = 'receita' AND data_vencimento < ? THEN {$this->receitaLiquida()} ELSE 0 END) AS receita_vencida, "
            . "SUM(CASE WHEN tipo = 'receita' AND data_vencimento >= ? THEN {$this->receitaLiquida()} ELSE 0 END) AS receita_avencer, "
            . "SUM(CASE WHEN tipo = 'despesa' AND data_vencimento < ? THEN valor ELSE 0 END) AS despesa_vencida, "
            . "SUM(CASE WHEN tipo = 'despesa' AND data_vencimento >= ? THEN valor ELSE 0 END) AS despesa_avencer, "
            . 'COUNT(*) AS itens '
            . 'FROM lancamentos WHERE baixado = 0';
        $query = $this->db->query($sql, [$hoje, $hoje, $hoje, $hoje]);
        $linha = $query ? $query->row() : null;

        return [
            'receita_vencida' => (float) ($linha->receita_vencida ?? 0),
            'receita_avencer' => (float) ($linha->receita_avencer ?? 0),
            'despesa_vencida' => (float) ($linha->despesa_vencida ?? 0),
            'despesa_avencer' => (float) ($linha->despesa_avencer ?? 0),
            'itens' => (int) ($linha->itens ?? 0),
        ];
    }

    /** Receita e despesa efetivadas no mes corrente. */
    public function financeiroDoMes()
    {
        $sql = 'SELECT '
            . "SUM(CASE WHEN tipo = 'receita' THEN {$this->receitaLiquida()} ELSE 0 END) AS receita, "
            . "SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) AS despesa "
            . 'FROM lancamentos WHERE baixado = 1 AND data_pagamento BETWEEN ? AND ?';
        $query = $this->db->query($sql, [date('Y-m-01'), date('Y-m-t')]);
        $linha = $query ? $query->row() : null;
        $receita = (float) ($linha->receita ?? 0);
        $despesa = (float) ($linha->despesa ?? 0);

        return ['receita' => $receita, 'despesa' => $despesa, 'liquido' => $receita - $despesa];
    }

    /**
     * Receita e despesa efetivadas mes a mes no ano, para o grafico de colunas.
     * Retorna 12 posicoes (Janeiro..Dezembro) mesmo com meses sem movimento.
     */
    public function financeiroPorMes($ano)
    {
        $ano = (int) $ano ?: (int) date('Y');
        $sql = 'SELECT MONTH(data_pagamento) AS mes, '
            . "SUM(CASE WHEN tipo = 'receita' THEN {$this->receitaLiquida()} ELSE 0 END) AS receita, "
            . "SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) AS despesa "
            . 'FROM lancamentos WHERE baixado = 1 AND data_pagamento BETWEEN ? AND ? GROUP BY MONTH(data_pagamento)';
        $query = $this->db->query($sql, [$ano . '-01-01', $ano . '-12-31']);

        $meses = [];
        for ($m = 1; $m <= 12; $m++) {
            $meses[$m] = ['mes' => $m, 'receita' => 0.0, 'despesa' => 0.0, 'liquido' => 0.0];
        }
        foreach (($query ? $query->result() : []) as $linha) {
            $m = (int) $linha->mes;
            if (! isset($meses[$m])) {
                continue;
            }
            $receita = (float) $linha->receita;
            $despesa = (float) $linha->despesa;
            $meses[$m] = ['mes' => $m, 'receita' => $receita, 'despesa' => $despesa, 'liquido' => $receita - $despesa];
        }

        return array_values($meses);
    }

    /** Total vendido no mes (vendas nao canceladas). */
    public function vendasDoMes()
    {
        $this->db->select('COUNT(*) AS quantidade, COALESCE(SUM(valorTotal), 0) AS total', false);
        $this->db->from('vendas');
        $this->db->where('dataVenda >=', date('Y-m-01'));
        $this->db->where('dataVenda <=', date('Y-m-t'));
        // status e nullable: "!= Cancelado" sozinho descartaria as vendas sem status
        $this->db->group_start();
        $this->db->where('status IS NULL', null, false);
        $this->db->or_where('status !=', 'Cancelado');
        $this->db->group_end();
        $linha = $this->db->get()->row();

        return [
            'quantidade' => (int) ($linha->quantidade ?? 0),
            'total' => (float) ($linha->total ?? 0),
        ];
    }

    /** Produtos no estoque minimo (ate $limite na lista, total no indicador). */
    public function estoqueMinimo($limite = 6)
    {
        // Comparacao entre colunas: o query builder viraria "estoque <= estoqueMinimo IS NULL",
        // entao a consulta vai inteira (mesmo criterio de Mapos_model::getProdutosMinimo).
        $condicao = 'WHERE estoque <= estoqueMinimo AND estoqueMinimo > 0';
        $lista = $this->db->query(
            'SELECT idProdutos, descricao, estoque, estoqueMinimo, precoVenda FROM produtos '
            . $condicao . ' ORDER BY estoque ASC LIMIT ?',
            [(int) $limite]
        );
        $total = $this->db->query('SELECT COUNT(*) AS total FROM produtos ' . $condicao);

        return [
            'itens' => $lista ? $lista->result() : [],
            'total' => (int) ($total && $total->row() ? $total->row()->total : 0),
        ];
    }

    /**
     * Movimento dos ultimos N dias: OS abertas (dataInicial) e vendas (dataVenda).
     * Uma consulta por tabela, agrupada por dia.
     */
    public function movimentoPorDia($dias = 14)
    {
        $inicio = date('Y-m-d', strtotime('-' . ((int) $dias - 1) . ' days'));

        $os = $this->db->query(
            'SELECT DATE(dataInicial) AS dia, COUNT(*) AS total FROM os WHERE dataInicial >= ? GROUP BY DATE(dataInicial)',
            [$inicio]
        );
        $vendas = $this->db->query(
            'SELECT DATE(dataVenda) AS dia, COUNT(*) AS total FROM vendas'
            . " WHERE dataVenda >= ? AND (status IS NULL OR status != 'Cancelado') GROUP BY DATE(dataVenda)",
            [$inicio]
        );

        $porDia = [];
        foreach (($os ? $os->result() : []) as $linha) {
            $porDia[$linha->dia]['dia'] = $linha->dia;
            $porDia[$linha->dia]['os'] = (int) $linha->total;
        }
        foreach (($vendas ? $vendas->result() : []) as $linha) {
            $porDia[$linha->dia]['dia'] = $linha->dia;
            $porDia[$linha->dia]['vendas'] = (int) $linha->total;
        }

        $saida = [];
        foreach ($porDia as $linha) {
            $saida[] = [
                'dia' => $linha['dia'],
                'os' => (int) ($linha['os'] ?? 0),
                'vendas' => (int) ($linha['vendas'] ?? 0),
            ];
        }

        return $saida;
    }

    /** Contagens de hoje para o cabecalho (OS abertas hoje, vendas hoje). */
    public function movimentoDeHoje()
    {
        $os = $this->db->query('SELECT COUNT(*) AS total FROM os WHERE DATE(dataInicial) = ?', [date('Y-m-d')]);
        $vendas = $this->db->query(
            "SELECT COUNT(*) AS total FROM vendas WHERE DATE(dataVenda) = ? AND (status IS NULL OR status != 'Cancelado')",
            [date('Y-m-d')]
        );

        return [
            'os' => (int) ($os && $os->row() ? $os->row()->total : 0),
            'vendas' => (int) ($vendas && $vendas->row() ? $vendas->row()->total : 0),
        ];
    }
}
