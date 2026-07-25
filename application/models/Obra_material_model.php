<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Gestão de material da obra (almoxarifado de canteiro).
 *
 * Fluxo: recebimento (ENTRADA — inclui material do cliente) credita o saldo;
 * entrega (SAÍDA — para a equipe no campo) debita. Fotos e assinaturas ficam
 * anexadas ao movimento. A leitura de código de barras casa com
 * produtos.codDeBarra (ver buscarProdutoPorCodBarra).
 */
class Obra_material_model extends CI_Model
{
    public function disponivel()
    {
        return $this->db->table_exists('obra_material_saldo');
    }

    /* ======================= Busca por cód. barras ======================= */

    public function buscarProdutoPorCodBarra($cod)
    {
        $cod = trim((string) $cod);
        if ($cod === '' || ! $this->db->table_exists('produtos')) {
            return null;
        }
        $this->db->where('codDeBarra', $cod);
        $this->db->limit(1);
        $r = $this->db->get('produtos')->row();
        if (! $r) {
            return null;
        }
        return [
            'id' => $r->idProdutos,
            'descricao' => $r->descricao,
            'cod_barras' => $r->codDeBarra,
            'unidade' => isset($r->unidade) ? $r->unidade : null,
            'preco' => isset($r->precoVenda) ? $r->precoVenda : 0,
        ];
    }

    /* ============================= Saldo ============================= */

    public function getSaldos($obra_id)
    {
        if (! $this->db->table_exists('obra_material_saldo')) {
            return [];
        }
        $this->db->where('obra_id', (int) $obra_id);
        $this->db->order_by('descricao', 'ASC');
        return $this->db->get('obra_material_saldo')->result();
    }

    /**
     * Aplica um delta ao saldo de um item da obra (cria a linha se necessário).
     * Identifica o item por produto_id quando houver, senão pela descrição.
     */
    private function ajustarSaldo($obra_id, $item, $delta, $doCliente = 0)
    {
        if (! $this->db->table_exists('obra_material_saldo')) {
            return;
        }
        $obra_id = (int) $obra_id;
        $produto_id = ! empty($item['produto_id']) ? (int) $item['produto_id'] : null;

        $this->db->where('obra_id', $obra_id);
        if ($produto_id) {
            $this->db->where('produto_id', $produto_id);
        } else {
            $this->db->where('produto_id IS NULL', null, false);
            $this->db->where('descricao', $item['descricao']);
        }
        $linha = $this->db->get('obra_material_saldo')->row();

        if ($linha) {
            $this->db->where('idSaldo', $linha->idSaldo)->update('obra_material_saldo', [
                'saldo' => (float) $linha->saldo + $delta,
            ]);
        } else {
            $this->db->insert('obra_material_saldo', [
                'obra_id' => $obra_id,
                'produto_id' => $produto_id,
                'descricao' => $item['descricao'] ?? null,
                'cod_barras' => $item['cod_barras'] ?? null,
                'unidade' => $item['unidade'] ?? null,
                'do_cliente' => $doCliente ? 1 : 0,
                'saldo' => $delta,
            ]);
        }
    }

    public function saldoDisponivel($obra_id, $produto_id, $descricao)
    {
        if (! $this->db->table_exists('obra_material_saldo')) {
            return 0;
        }
        $this->db->where('obra_id', (int) $obra_id);
        if (! empty($produto_id)) {
            $this->db->where('produto_id', (int) $produto_id);
        } else {
            $this->db->where('produto_id IS NULL', null, false);
            $this->db->where('descricao', $descricao);
        }
        $r = $this->db->get('obra_material_saldo')->row();
        return $r ? (float) $r->saldo : 0;
    }

    /* ========================== Recebimento ========================== */

    public function proximoNumeroRecebimento($obra_id)
    {
        if (! $this->db->table_exists('obra_material_recebimento')) {
            return 1;
        }
        $r = $this->db->select_max('numero')->where('obra_id', (int) $obra_id)
            ->get('obra_material_recebimento')->row();
        return ($r ? (int) $r->numero : 0) + 1;
    }

    /**
     * Registra um recebimento e credita o saldo com os itens conferidos.
     * @return int id do recebimento
     */
    public function registrarRecebimento($cab, $itens, $fotos = [])
    {
        if (! $this->db->table_exists('obra_material_recebimento')) {
            return false;
        }
        $doCliente = (($cab['origem'] ?? 'cliente') === 'cliente') ? 1 : 0;

        $this->db->insert('obra_material_recebimento', $cab);
        $recId = $this->db->insert_id();

        foreach ($itens as $it) {
            $qtdConf = (float) ($it['quantidade_conferida'] ?? 0);
            $qtdPrev = (float) ($it['quantidade_prevista'] ?? $qtdConf);
            $row = [
                'recebimento_id' => $recId,
                'produto_id' => ! empty($it['produto_id']) ? (int) $it['produto_id'] : null,
                'descricao' => $it['descricao'] ?? null,
                'cod_barras' => $it['cod_barras'] ?? null,
                'unidade' => $it['unidade'] ?? null,
                'quantidade_prevista' => $qtdPrev,
                'quantidade_conferida' => $qtdConf,
                'valor_unitario' => (float) ($it['valor_unitario'] ?? 0),
                'divergencia' => ($qtdPrev != $qtdConf) ? 1 : 0,
            ];
            $this->db->insert('obra_material_recebimento_item', $row);

            if ($qtdConf > 0) {
                $this->ajustarSaldo($cab['obra_id'], $it, $qtdConf, $doCliente);
            }
        }

        foreach ($fotos as $f) {
            $this->addFoto('recebimento', $recId, $f['foto'], $f['legenda'] ?? null);
        }

        return $recId;
    }

    public function getRecebimentos($obra_id)
    {
        if (! $this->db->table_exists('obra_material_recebimento')) {
            return [];
        }
        $this->db->select('r.*, usuarios.nome as recebedor');
        $this->db->from('obra_material_recebimento r');
        $this->db->join('usuarios', 'usuarios.idUsuarios = r.recebido_por', 'left');
        $this->db->where('r.obra_id', (int) $obra_id);
        $this->db->order_by('r.idRecebimento', 'DESC');
        return $this->db->get()->result();
    }

    public function getRecebimento($id)
    {
        if (! $this->db->table_exists('obra_material_recebimento')) {
            return null;
        }
        return $this->db->where('idRecebimento', (int) $id)->get('obra_material_recebimento')->row();
    }

    public function getRecebimentoItens($recId)
    {
        if (! $this->db->table_exists('obra_material_recebimento_item')) {
            return [];
        }
        return $this->db->where('recebimento_id', (int) $recId)
            ->get('obra_material_recebimento_item')->result();
    }

    /* ============================ Entrega ============================ */

    public function proximoNumeroEntrega($obra_id)
    {
        if (! $this->db->table_exists('obra_material_entrega')) {
            return 1;
        }
        $r = $this->db->select_max('numero')->where('obra_id', (int) $obra_id)
            ->get('obra_material_entrega')->row();
        return ($r ? (int) $r->numero : 0) + 1;
    }

    /**
     * Registra a entrega de material para a equipe e debita o saldo.
     * @return int id da entrega
     */
    public function registrarEntrega($cab, $itens, $fotos = [])
    {
        if (! $this->db->table_exists('obra_material_entrega')) {
            return false;
        }
        $this->db->insert('obra_material_entrega', $cab);
        $entId = $this->db->insert_id();

        foreach ($itens as $it) {
            $qtd = (float) ($it['quantidade'] ?? 0);
            $this->db->insert('obra_material_entrega_item', [
                'entrega_id' => $entId,
                'produto_id' => ! empty($it['produto_id']) ? (int) $it['produto_id'] : null,
                'descricao' => $it['descricao'] ?? null,
                'cod_barras' => $it['cod_barras'] ?? null,
                'unidade' => $it['unidade'] ?? null,
                'quantidade' => $qtd,
            ]);
            if ($qtd > 0) {
                $this->ajustarSaldo($cab['obra_id'], $it, -$qtd);
            }
        }

        foreach ($fotos as $f) {
            $this->addFoto('entrega', $entId, $f['foto'], $f['legenda'] ?? null);
        }

        return $entId;
    }

    public function getEntregas($obra_id)
    {
        if (! $this->db->table_exists('obra_material_entrega')) {
            return [];
        }
        $this->db->select('e.*, equipes.nome as equipe, usuarios.nome as almoxarife');
        $this->db->from('obra_material_entrega e');
        $this->db->join('equipes', 'equipes.idEquipe = e.equipe_id', 'left');
        $this->db->join('usuarios', 'usuarios.idUsuarios = e.entregue_por', 'left');
        $this->db->where('e.obra_id', (int) $obra_id);
        $this->db->order_by('e.idEntrega', 'DESC');
        return $this->db->get()->result();
    }

    public function getEntrega($id)
    {
        if (! $this->db->table_exists('obra_material_entrega')) {
            return null;
        }
        return $this->db->where('idEntrega', (int) $id)->get('obra_material_entrega')->row();
    }

    public function getEntregaItens($entId)
    {
        if (! $this->db->table_exists('obra_material_entrega_item')) {
            return [];
        }
        return $this->db->where('entrega_id', (int) $entId)
            ->get('obra_material_entrega_item')->result();
    }

    /* ============================= Fotos ============================= */

    public function addFoto($tipo, $refId, $foto, $legenda = null)
    {
        if (! $this->db->table_exists('obra_material_foto')) {
            return false;
        }
        $this->db->insert('obra_material_foto', [
            'ref_tipo' => $tipo,
            'ref_id' => (int) $refId,
            'foto' => $foto,
            'legenda' => $legenda,
            'data' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }

    public function getFotos($tipo, $refId)
    {
        if (! $this->db->table_exists('obra_material_foto')) {
            return [];
        }
        return $this->db->where('ref_tipo', $tipo)->where('ref_id', (int) $refId)
            ->get('obra_material_foto')->result();
    }

    public function getFoto($id)
    {
        if (! $this->db->table_exists('obra_material_foto')) {
            return null;
        }
        return $this->db->where('idFoto', (int) $id)->get('obra_material_foto')->row();
    }
}
