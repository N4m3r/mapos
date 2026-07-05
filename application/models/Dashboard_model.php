<?php

class Dashboard_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retorna KPIs principais
     */
    public function getKPIs($data_inicio, $data_fim)
    {
        $result = [];

        // Total de OS no período
        $this->db->where('dataInicial >=', $data_inicio);
        $this->db->where('dataInicial <=', $data_fim . ' 23:59:59');
        $result['total_os'] = $this->db->count_all_results('os');

        // OS pendentes (Aberto + Orçamento)
        $this->db->where_in('status', ['Aberto', 'Orçamento']);
        $result['os_pendentes'] = $this->db->count_all_results('os');

        // OS finalizadas no período
        $this->db->where('dataInicial >=', $data_inicio);
        $this->db->where('dataInicial <=', $data_fim . ' 23:59:59');
        $this->db->where_in('status', ['Finalizado', 'Faturado']);
        $result['os_finalizadas'] = $this->db->count_all_results('os');

        // Valor total faturado
        $this->db->select('SUM(valorTotal) as total');
        $this->db->where('dataInicial >=', $data_inicio);
        $this->db->where('dataInicial <=', $data_fim . ' 23:59:59');
        $this->db->where_in('status', ['Finalizado', 'Faturado']);
        $query = $this->db->get('os');
        $result['valor_faturado'] = $query->row()->total ?: 0;

        // Ticket médio
        if ($result['os_finalizadas'] > 0) {
            $result['ticket_medio'] = $result['valor_faturado'] / $result['os_finalizadas'];
        } else {
            $result['ticket_medio'] = 0;
        }

        // Total de clientes
        $result['total_clientes'] = $this->db->count_all('clientes');

        // Novos clientes no período
        $this->db->where('dataCadastro >=', $data_inicio);
        $this->db->where('dataCadastro <=', $data_fim);
        $result['novos_clientes'] = $this->db->count_all_results('clientes');

        // Taxa de conclusão
        if ($result['total_os'] > 0) {
            $result['taxa_conclusao'] = ($result['os_finalizadas'] / $result['total_os']) * 100;
        } else {
            $result['taxa_conclusao'] = 0;
        }

        return $result;
    }

    /**
     * OS por status para gráfico de pizza
     */
    public function getOsPorStatus($data_inicio, $data_fim)
    {
        $this->db->select('status, COUNT(*) as total');
        $this->db->where('dataInicial >=', $data_inicio);
        $this->db->where('dataInicial <=', $data_fim . ' 23:59:59');
        $this->db->group_by('status');
        $query = $this->db->get('os');
        return $query->result();
    }

    /**
     * OS por mês para gráfico de linha
     */
    public function getOsPorMes($data_inicio, $data_fim)
    {
        $this->db->select("DATE_FORMAT(dataInicial, '%Y-%m') as mes, COUNT(*) as total");
        $this->db->where('dataInicial >=', $data_inicio);
        $this->db->where('dataInicial <=', $data_fim . ' 23:59:59');
        $this->db->group_by("DATE_FORMAT(dataInicial, '%Y-%m')");
        $this->db->order_by('mes', 'ASC');
        $query = $this->db->get('os');
        return $query->result();
    }

    /**
     * Faturamento mensal para gráfico de barras
     */
    public function getFaturamentoMensal($data_inicio, $data_fim)
    {
        $this->db->select("DATE_FORMAT(dataInicial, '%Y-%m') as mes, SUM(valorTotal) as total");
        $this->db->where('dataInicial >=', $data_inicio);
        $this->db->where('dataInicial <=', $data_fim . ' 23:59:59');
        $this->db->where_in('status', ['Finalizado', 'Faturado']);
        $this->db->group_by("DATE_FORMAT(dataInicial, '%Y-%m')");
        $this->db->order_by('mes', 'ASC');
        $query = $this->db->get('os');
        return $query->result();
    }

    /**
     * OS por técnico
     */
    public function getOsPorTecnico($data_inicio, $data_fim)
    {
        $this->db->select('u.nome as tecnico, COUNT(os.idOs) as total, SUM(os.valorTotal) as valor');
        $this->db->from('os');
        $this->db->join('usuarios u', 'u.idUsuarios = os.tecnico_responsavel', 'left');
        $this->db->where('os.dataInicial >=', $data_inicio);
        $this->db->where('os.dataInicial <=', $data_fim . ' 23:59:59');
        $this->db->group_by('u.idUsuarios, u.nome');
        $this->db->order_by('total', 'DESC');
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Top produtos vendidos
     */
    public function getTopProdutos($data_inicio, $data_fim, $limite = 10)
    {
        $this->db->select('p.descricao, SUM(op.quantidade) as total_vendido, SUM(op.subTotal) as valor_total');
        $this->db->from('produtos_os op');
        $this->db->join('produtos p', 'p.idProdutos = op.produtos_id');
        $this->db->join('os', 'os.idOs = op.os_id');
        $this->db->where('os.dataInicial >=', $data_inicio);
        $this->db->where('os.dataInicial <=', $data_fim . ' 23:59:59');
        $this->db->group_by('p.idProdutos, p.descricao');
        $this->db->order_by('total_vendido', 'DESC');
        $this->db->limit($limite);
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Top serviços vendidos
     */
    public function getTopServicos($data_inicio, $data_fim, $limite = 10)
    {
        $this->db->select('s.nome, SUM(osv.quantidade) as total_vendido, SUM(osv.subTotal) as valor_total');
        $this->db->from('servicos_os osv');
        $this->db->join('servicos s', 's.idServicos = osv.servicos_id');
        $this->db->join('os', 'os.idOs = osv.os_id');
        $this->db->where('os.dataInicial >=', $data_inicio);
        $this->db->where('os.dataInicial <=', $data_fim . ' 23:59:59');
        $this->db->group_by('s.idServicos, s.nome');
        $this->db->order_by('total_vendido', 'DESC');
        $this->db->limit($limite);
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Clientes novos vs recorrentes
     */
    public function getClientesNovosRecorrentes($data_inicio, $data_fim)
    {
        // Clientes novos (com OS neste período e data de cadastro no período)
        $this->db->select('COUNT(DISTINCT c.idClientes) as total');
        $this->db->from('clientes c');
        $this->db->join('os', 'os.clientes_id = c.idClientes');
        $this->db->where('os.dataInicial >=', $data_inicio);
        $this->db->where('os.dataInicial <=', $data_fim . ' 23:59:59');
        $this->db->where('c.dataCadastro >=', $data_inicio);
        $this->db->where('c.dataCadastro <=', $data_fim);
        $novos = $this->db->get()->row()->total ?: 0;

        // Clientes recorrentes (com OS neste período e data de cadastro anterior ao período)
        $this->db->select('COUNT(DISTINCT c.idClientes) as total');
        $this->db->from('clientes c');
        $this->db->join('os', 'os.clientes_id = c.idClientes');
        $this->db->where('os.dataInicial >=', $data_inicio);
        $this->db->where('os.dataInicial <=', $data_fim . ' 23:59:59');
        $this->db->where('c.dataCadastro <', $data_inicio);
        $recorrentes = $this->db->get()->row()->total ?: 0;

        return [
            'novos' => $novos,
            'recorrentes' => $recorrentes
        ];
    }

    /**
     * Relatório detalhado de atendimentos
     */
    public function getRelatorioAtendimentos($data_inicio, $data_fim, $tecnico_id = null)
    {
        $this->db->select('os.*, c.nomeCliente, u.nome as nome_tecnico');
        $this->db->from('os');
        $this->db->join('clientes c', 'c.idClientes = os.clientes_id', 'left');
        $this->db->join('usuarios u', 'u.idUsuarios = os.tecnico_responsavel', 'left');
        $this->db->where('os.dataInicial >=', $data_inicio);
        $this->db->where('os.dataInicial <=', $data_fim . ' 23:59:59');

        if ($tecnico_id) {
            $this->db->where('os.tecnico_responsavel', $tecnico_id);
        }

        $this->db->order_by('os.dataInicial', 'DESC');
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Técnicos ativos
     */
    public function getTecnicosAtivos()
    {
        $this->db->where('situacao', 1);
        $this->db->order_by('nome', 'ASC');
        $query = $this->db->get('usuarios');
        return $query->result();
    }

    /**
     * Relatório financeiro
     */
    public function getRelatorioFinanceiro($data_inicio, $data_fim)
    {
        $this->db->select('*');
        $this->db->where('data_vencimento >=', $data_inicio);
        $this->db->where('data_vencimento <=', $data_fim);
        $this->db->order_by('data_vencimento', 'DESC');
        $query = $this->db->get('lancamentos');
        return $query->result();
    }

    /**
     * Lançamentos por categoria
     */
    public function getLancamentosPorCategoria($data_inicio, $data_fim)
    {
        $this->db->select('tipo, SUM(valor) as total');
        $this->db->where('data_vencimento >=', $data_inicio);
        $this->db->where('data_vencimento <=', $data_fim);
        $this->db->group_by('tipo');
        $query = $this->db->get('lancamentos');
        return $query->result();
    }

    /**
     * Relatório de produtos
     */
    public function getRelatorioProdutos($data_inicio, $data_fim)
    {
        $this->db->select('p.descricao, p.estoque, SUM(op.quantidade) as total_vendido, SUM(op.subTotal) as valor_total');
        $this->db->from('produtos_os op');
        $this->db->join('produtos p', 'p.idProdutos = op.produtos_id');
        $this->db->join('os', 'os.idOs = op.os_id');
        $this->db->where('os.dataInicial >=', $data_inicio);
        $this->db->where('os.dataInicial <=', $data_fim . ' 23:59:59');
        $this->db->group_by('p.idProdutos, p.descricao, p.estoque');
        $this->db->order_by('total_vendido', 'DESC');
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Relatório de serviços
     */
    public function getRelatorioServicos($data_inicio, $data_fim)
    {
        $this->db->select('s.nome, SUM(osv.quantidade) as total_vendido, SUM(osv.subTotal) as valor_total');
        $this->db->from('servicos_os osv');
        $this->db->join('servicos s', 's.idServicos = osv.servicos_id');
        $this->db->join('os', 'os.idOs = osv.os_id');
        $this->db->where('os.dataInicial >=', $data_inicio);
        $this->db->where('os.dataInicial <=', $data_fim . ' 23:59:59');
        $this->db->group_by('s.idServicos, s.nome');
        $this->db->order_by('total_vendido', 'DESC');
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Estoque crítico (produtos abaixo do mínimo)
     */
    public function getEstoqueCritico($limite = 10)
    {
        $this->db->where('estoque <=', 5); // Considera estoque <= 5 como crítico
        $this->db->order_by('estoque', 'ASC');
        $query = $this->db->get('produtos');
        return $query->result();
    }

    /**
     * Relatório de clientes
     */
    public function getRelatorioClientes($data_inicio, $data_fim)
    {
        $this->db->select('c.nomeCliente, COUNT(os.idOs) as total_os, SUM(os.valorTotal) as valor_total');
        $this->db->from('clientes c');
        $this->db->join('os', 'os.clientes_id = c.idClientes');
        $this->db->where('os.dataInicial >=', $data_inicio);
        $this->db->where('os.dataInicial <=', $data_fim . ' 23:59:59');
        $this->db->group_by('c.idClientes, c.nomeCliente');
        $this->db->order_by('total_os', 'DESC');
        $query = $this->db->get();

        $result = $query->result();

        // Calcula ticket médio
        foreach ($result as $row) {
            $row->ticket_medio = $row->total_os > 0 ? $row->valor_total / $row->total_os : 0;
        }

        return $result;
    }

    /**
     * Top clientes
     */
    public function getTopClientes($data_inicio, $data_fim, $limite = 10)
    {
        $this->db->select('c.nomeCliente, COUNT(os.idOs) as total_os, SUM(os.valorTotal) as valor_total');
        $this->db->from('clientes c');
        $this->db->join('os', 'os.clientes_id = c.idClientes');
        $this->db->where('os.dataInicial >=', $data_inicio);
        $this->db->where('os.dataInicial <=', $data_fim . ' 23:59:59');
        $this->db->group_by('c.idClientes, c.nomeCliente');
        $this->db->order_by('valor_total', 'DESC');
        $this->db->limit($limite);
        $query = $this->db->get();
        return $query->result();
    }
}
