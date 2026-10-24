<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Contratos de manutenção recorrente + matriz de SLA por prioridade.
 * Resiliente a tabelas ausentes (migration ainda não aplicada).
 */
class Contratos_model extends CI_Model
{
    /** Prioridades e SLA padrão (horas) usados ao criar um contrato. */
    public static function prioridadesPadrao()
    {
        return [
            'Crítica' => ['resposta_horas' => 2,  'solucao_horas' => 8],
            'Alta'    => ['resposta_horas' => 4,  'solucao_horas' => 24],
            'Normal'  => ['resposta_horas' => 8,  'solucao_horas' => 72],
            'Baixa'   => ['resposta_horas' => 24, 'solucao_horas' => 168],
        ];
    }

    public function disponivel()
    {
        return $this->db->table_exists('contratos');
    }

    /* ============================ Listagem =========================== */

    public function count($filtros = [])
    {
        if (! $this->db->table_exists('contratos')) {
            return 0;
        }
        $this->db->from('contratos');
        $this->db->join('clientes', 'clientes.idClientes = contratos.clientes_id', 'left');
        $this->aplicarFiltros($filtros);
        return $this->db->count_all_results();
    }

    public function getContratos($limite = 20, $offset = 0, $filtros = [])
    {
        if (! $this->db->table_exists('contratos')) {
            return [];
        }
        $this->db->select('contratos.*, clientes.nomeCliente,
            (SELECT COUNT(*) FROM os WHERE os.contrato_id = contratos.idContrato) as total_os', false);
        $this->db->from('contratos');
        $this->db->join('clientes', 'clientes.idClientes = contratos.clientes_id', 'left');
        $this->aplicarFiltros($filtros);
        $this->db->order_by('contratos.idContrato', 'DESC');
        $this->db->limit($limite, $offset);
        return $this->db->get()->result();
    }

    private function aplicarFiltros($filtros)
    {
        $pesquisa = isset($filtros['pesquisa']) ? trim((string) $filtros['pesquisa']) : '';
        $status = isset($filtros['status']) ? trim((string) $filtros['status']) : '';
        if ($pesquisa !== '') {
            $this->db->group_start();
            $this->db->like('contratos.descricao', $pesquisa);
            $this->db->or_like('contratos.codigo', $pesquisa);
            $this->db->or_like('clientes.nomeCliente', $pesquisa);
            $this->db->group_end();
        }
        if ($status !== '') {
            $this->db->where('contratos.status', $status);
        }
    }

    public function getContrato($id)
    {
        if (! $this->db->table_exists('contratos')) {
            return null;
        }
        $this->db->select('contratos.*, clientes.nomeCliente');
        $this->db->from('contratos');
        $this->db->join('clientes', 'clientes.idClientes = contratos.clientes_id', 'left');
        $this->db->where('contratos.idContrato', (int) $id);
        return $this->db->get()->row();
    }

    /* ============================ CRUD ============================== */

    public function add($data)
    {
        $this->db->insert('contratos', $data);
        return $this->db->insert_id();
    }

    public function edit($id, $data)
    {
        $this->db->where('idContrato', (int) $id)->update('contratos', $data);
        return true;
    }

    public function delete($id)
    {
        $id = (int) $id;
        if ($this->db->table_exists('contrato_sla')) {
            $this->db->where('contrato_id', $id)->delete('contrato_sla');
        }
        // Desvincula as OS, preservando o histórico da OS.
        if ($this->db->field_exists('contrato_id', 'os')) {
            $this->db->where('contrato_id', $id)->update('os', ['contrato_id' => null]);
        }
        $this->db->where('idContrato', $id)->delete('contratos');
        return true;
    }

    /* ============================ SLA =============================== */

    public function getSlas($contrato_id)
    {
        if (! $this->db->table_exists('contrato_sla')) {
            return [];
        }
        return $this->db->where('contrato_id', (int) $contrato_id)
            ->order_by('idSla', 'ASC')
            ->get('contrato_sla')->result();
    }

    /** SLA de uma prioridade específica (linha ou null). */
    public function slaPara($contrato_id, $prioridade)
    {
        if (! $this->db->table_exists('contrato_sla')) {
            return null;
        }
        return $this->db->where('contrato_id', (int) $contrato_id)
            ->where('prioridade', $prioridade)
            ->limit(1)
            ->get('contrato_sla')->row();
    }

    /** Substitui toda a matriz de SLA do contrato. */
    public function salvarSlas($contrato_id, array $prioridades)
    {
        if (! $this->db->table_exists('contrato_sla')) {
            return;
        }
        $contrato_id = (int) $contrato_id;
        $this->db->where('contrato_id', $contrato_id)->delete('contrato_sla');
        foreach ($prioridades as $prioridade => $horas) {
            $this->db->insert('contrato_sla', [
                'contrato_id' => $contrato_id,
                'prioridade' => $prioridade,
                'resposta_horas' => (int) ($horas['resposta_horas'] ?? 0),
                'solucao_horas' => (int) ($horas['solucao_horas'] ?? 0),
            ]);
        }
    }

    /* ======================== OS do contrato ======================== */

    public function getOsDoContrato($contrato_id, $limite = 100)
    {
        if (! $this->db->table_exists('os') || ! $this->db->field_exists('contrato_id', 'os')) {
            return [];
        }
        $this->db->select('os.idOs, os.status, os.prioridade, os.dataInicial,
            os.sla_solucao_prazo, os.sla_solucao_em, os.sla_resposta_prazo, os.sla_resposta_em,
            usuarios.nome as nome_tecnico');
        $this->db->from('os');
        $this->db->join('usuarios', 'usuarios.idUsuarios = os.tecnico_responsavel', 'left');
        $this->db->where('os.contrato_id', (int) $contrato_id);
        $this->db->order_by('os.idOs', 'DESC');
        $this->db->limit($limite);
        return $this->db->get()->result();
    }

    /* ============================ KPIs ============================== */

    public function kpis()
    {
        $kpis = ['ativos' => 0, 'mrr' => 0, 'sla_estourados' => 0, 'a_faturar' => 0];
        if (! $this->db->table_exists('contratos')) {
            return $kpis;
        }
        $kpis['ativos'] = (int) $this->db->where('status', 'ativo')->count_all_results('contratos');

        // MRR: soma normalizada para valor mensal (trimestral/anual proporcional).
        $rows = $this->db->select('tipo, valor')->where('status', 'ativo')->get('contratos')->result();
        foreach ($rows as $r) {
            $kpis['mrr'] += $this->valorMensalEquivalente($r->tipo, (float) $r->valor);
        }

        // OS com SLA de solução estourado (prazo vencido e ainda sem solução).
        if ($this->db->field_exists('sla_solucao_prazo', 'os')) {
            $this->db->where('sla_solucao_prazo <', date('Y-m-d H:i:s'));
            $this->db->where('sla_solucao_em IS NULL', null, false);
            $this->db->where('sla_solucao_prazo IS NOT NULL', null, false);
            $this->db->where_not_in('status', ['Finalizado', 'Faturado', 'Cancelado']);
            $kpis['sla_estourados'] = (int) $this->db->count_all_results('os');
        }

        return $kpis;
    }

    /** Valor mensal equivalente conforme a periodicidade. */
    public function valorMensalEquivalente($tipo, $valor)
    {
        switch ($tipo) {
            case 'trimestral': return $valor / 3;
            case 'semestral':  return $valor / 6;
            case 'anual':      return $valor / 12;
            default:           return $valor; // mensal
        }
    }

    /* ==================== Faturamento recorrente ==================== */

    /**
     * Descrição-convenção do lançamento de mensalidade (usada para evitar
     * duplicidade por competência e para localizar depois).
     */
    public function descricaoFatura($contrato_id, $competencia)
    {
        return 'Mensalidade Contrato #' . (int) $contrato_id . ' - ' . $competencia;
    }

    /** Já existe lançamento para o contrato nesta competência (MM/AAAA)? */
    public function jaFaturado($contrato_id, $competencia)
    {
        if (! $this->db->table_exists('lancamentos')) {
            return false;
        }
        return $this->db->where('descricao', $this->descricaoFatura($contrato_id, $competencia))
            ->count_all_results('lancamentos') > 0;
    }

    /* ======================= Seletor para OS ======================= */

    /** Contratos ativos (opcionalmente de um cliente) para o seletor da OS. */
    public function getContratosSelect($clientes_id = null)
    {
        if (! $this->db->table_exists('contratos')) {
            return [];
        }
        $this->db->select('idContrato, codigo, descricao, clientes_id');
        $this->db->where('status', 'ativo');
        if ($clientes_id) {
            $this->db->where('clientes_id', (int) $clientes_id);
        }
        $this->db->order_by('descricao', 'ASC');
        return $this->db->get('contratos')->result();
    }
}
