<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Dados do painel de cobranca dos chamados (OS).
 *
 * Reune numa lista unica o que exige acao financeira: OS aguardando aprovacao
 * do cliente, documentos sem cobranca e cobrancas ja emitidas. Nao duplica
 * regra: o que ainda falta cobrar vem de Cobrancas_model::aCobrar().
 */
class Chamado_cobranca_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /** O campo de chamado so existe depois da migration 20261107000000. */
    public function disponivel()
    {
        return $this->db->field_exists('chamado_id', 'os');
    }

    /**
     * Lista normalizada do painel.
     *
     * Item: chave, tipo, ref_id, idOs, chamado, cliente, data_ref, valor,
     * status, detalhe e link.
     *
     * @param array $filtros busca, chamado, status (array), hoje
     */
    public function itens(array $filtros = [])
    {
        $this->load->helper(['cobranca', 'chamado_cobranca']);

        $busca = trim((string) ($filtros['busca'] ?? ''));
        $chamado = trim((string) ($filtros['chamado'] ?? ''));
        $hoje = (string) ($filtros['hoje'] ?? date('Y-m-d'));

        $itens = array_merge(
            $this->pendentesAprovacao($busca, $chamado),
            $this->semCobranca($busca, $chamado),
            $this->cobrancas($busca, $chamado, $hoje)
        );

        $status = Chamado_cobranca::filtro((array) ($filtros['status'] ?? []));
        if ($status) {
            $itens = array_values(array_filter($itens, static function ($i) use ($status) {
                return in_array($i['status'], $status, true);
            }));
        }

        $ordem = ['vencida' => 0, 'pendente_aprovacao' => 1, 'a_cobrar' => 2, 'pendente' => 3, 'paga' => 4, 'cancelada' => 5];
        usort($itens, static function ($a, $b) use ($ordem) {
            return [$ordem[$a['status']] ?? 9, $a['data_ref'] ?: '9999']
                <=> [$ordem[$b['status']] ?? 9, $b['data_ref'] ?: '9999'];
        });

        return $itens;
    }

    /** Valor do documento (produtos + servicos), com o desconto em reais. */
    private function totalOs()
    {
        $bruto = '(COALESCE((SELECT SUM(po.subTotal) FROM produtos_os po WHERE po.os_id = os.idOs),0)'
            . ' + COALESCE((SELECT SUM(so.preco * so.quantidade) FROM servicos_os so WHERE so.os_id = os.idOs),0))';

        return "CASE WHEN os.desconto <> 0 AND os.valor_desconto <> 0 THEN os.valor_desconto ELSE $bruto END";
    }

    /** Coluna de chamado, ou NULL em ambiente ainda sem a migration. */
    private function chamadoSelect()
    {
        return $this->disponivel() ? ', os.chamado_id' : ', NULL AS chamado_id';
    }

    /** OS aguardando decisao do cliente (link de aprovacao em aberto). */
    private function pendentesAprovacao($busca, $chamado)
    {
        if (! $this->db->field_exists('aprovacao_status', 'os')) {
            return [];
        }

        $sql = 'SELECT os.idOs, c.idClientes, c.nomeCliente, os.dataInicial AS data_ref, '
            . $this->totalOs() . ' AS valor' . $this->chamadoSelect()
            . ' FROM os JOIN clientes c ON c.idClientes = os.clientes_id'
            . " WHERE os.aprovacao_status = 'pendente' AND os.status <> 'Cancelado'";
        $binds = [];

        if ($busca !== '') {
            $sql .= ' AND c.nomeCliente LIKE ?';
            $binds[] = '%' . addcslashes($busca, '%_\\') . '%';
        }
        if ($chamado !== '' && $this->disponivel()) {
            $sql .= ' AND os.chamado_id LIKE ?';
            $binds[] = '%' . addcslashes($chamado, '%_\\') . '%';
        }
        $sql .= ' ORDER BY os.dataInicial ASC';

        $itens = [];
        foreach ($this->db->query($sql, $binds)->result() as $r) {
            $itens[] = $this->item(
                'os:' . (int) $r->idOs,
                $r,
                'pendente_aprovacao',
                'Aguardando aprovação do cliente',
                'os/visualizar/' . (int) $r->idOs,
                (int) $r->idOs,
                (int) $r->idClientes
            );
        }

        return $itens;
    }

    /** OS finalizadas e vendas faturadas com algo ainda a cobrar. */
    private function semCobranca($busca, $chamado)
    {
        $this->load->model('cobrancas_model');
        $rows = $this->cobrancas_model->aCobrar($busca);
        $chamados = $this->chamadosPorOs($rows);

        $itens = [];
        foreach ($rows as $r) {
            $id = (int) $r->ref_id;
            $chaveDoc = $chamados[$id] ?? null;
            if ($chamado !== '' && stripos((string) $chaveDoc, $chamado) === false) {
                continue;
            }

            $r->chamado_id = $chaveDoc;
            $rota = $r->tipo === 'os' ? 'os/visualizar/' : 'vendas/visualizar/';
            $itens[] = $this->item(
                $r->tipo === 'os' ? 'os:' . $id : 'vend:' . $id,
                $r,
                'a_cobrar',
                $r->tipo === 'os' ? 'OS sem cobrança' : 'Venda sem cobrança',
                $rota . $id,
                $id,
                0
            );
        }

        return $itens;
    }

    /** Cobrancas emitidas, com o status que o gateway devolveu. */
    private function cobrancas($busca, $chamado, $hoje)
    {
        $this->load->helper('cobranca');

        $rows = $this->db->select('cobrancas.*, clientes.nomeCliente, os.chamado_id, os.idOs')
            ->from('cobrancas')
            ->join('clientes', 'clientes.idClientes = cobrancas.clientes_id', 'left')
            ->join('os', 'os.idOs = cobrancas.os_id', 'left')
            ->order_by('cobrancas.idCobranca', 'desc')
            ->limit(2000)
            ->get()->result();

        $itens = [];
        foreach ($rows as $r) {
            if ($busca !== '' && stripos((string) $r->nomeCliente, $busca) === false) {
                continue;
            }
            if ($chamado !== '' && stripos((string) ($r->chamado_id ?? ''), $chamado) === false) {
                continue;
            }

            $r->data_ref = $r->expire_at;
            $r->valor = ((float) ($r->total ?? 0)) / 100;
            $status = cobranca_classificar_status($r->status, $r->expire_at, $hoje);

            $itens[] = $this->item(
                'cob:' . (int) $r->idCobranca,
                $r,
                $status,
                cobranca_prazo_texto($r->expire_at, $hoje),
                'cobrancas/visualizar/' . (int) $r->idCobranca,
                (int) $r->idCobranca,
                (int) ($r->clientes_id ?? 0)
            );
        }

        return $itens;
    }

    private function item($chave, $r, $status, $detalhe, $link, $refId, $clientesId)
    {
        return [
            'chave' => $chave,
            'tipo' => substr($chave, 0, strpos($chave, ':')),
            'ref_id' => (int) $refId,
            'idOs' => isset($r->idOs) ? (int) $r->idOs : (int) ($r->os_id ?? 0),
            'chamado' => ! empty($r->chamado_id) ? (string) $r->chamado_id : null,
            'cliente' => (string) ($r->nomeCliente ?? ''),
            'clientes_id' => (int) $clientesId,
            'data_ref' => $r->data_ref ?? null,
            'valor' => round((float) ($r->valor ?? 0), 2),
            'status' => $status,
            'detalhe' => $detalhe,
            'link' => $link,
        ];
    }

    /** Chamado de cada OS de uma lista de documentos (uma consulta). */
    private function chamadosPorOs(array $rows)
    {
        if (! $this->disponivel()) {
            return [];
        }

        $ids = [];
        foreach ($rows as $r) {
            if ($r->tipo === 'os') {
                $ids[] = (int) $r->ref_id;
            }
        }
        if (! $ids) {
            return [];
        }

        $mapa = [];
        foreach ($this->db->select('idOs, chamado_id')->where_in('idOs', array_unique($ids))->get('os')->result() as $o) {
            $mapa[(int) $o->idOs] = (string) $o->chamado_id;
        }

        return $mapa;
    }

    /** OS de um lote de chaves 'os:<id>' (para solicitar aprovacao). */
    public function osPorChaves(array $chaves)
    {
        $ids = [];
        foreach ($chaves as $chave) {
            if (strpos($chave, 'os:') === 0) {
                $ids[] = (int) substr($chave, 3);
            }
        }
        if (! $ids) {
            return [];
        }

        return $this->db->where_in('idOs', array_unique($ids))->get('os')->result();
    }
}
