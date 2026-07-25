<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Obras — núcleo do módulo: obra, etapas (EAP/cronograma), RDO, medição e custo.
 *
 * Todos os métodos são resilientes: se as tabelas ainda não existem (migration
 * não aplicada), devolvem vazio em vez de quebrar o sistema.
 */
class Obras_model extends CI_Model
{
    public function disponivel()
    {
        return $this->db->table_exists('obras');
    }

    /* ============================ Obra ============================ */

    public function count($where = '')
    {
        if (! $this->db->table_exists('obras')) {
            return 0;
        }
        if ($where) {
            $this->db->where($where);
        }
        return $this->db->count_all_results('obras');
    }

    public function getObras($perpage = 0, $start = 0, $filtros = [])
    {
        if (! $this->db->table_exists('obras')) {
            return [];
        }
        $this->db->select('obras.*, clientes.nomeCliente, usuarios.nome as responsavel');
        $this->db->from('obras');
        $this->db->join('clientes', 'clientes.idClientes = obras.clientes_id', 'left');
        $this->db->join('usuarios', 'usuarios.idUsuarios = obras.responsavel_id', 'left');
        if (! empty($filtros['pesquisa'])) {
            $this->db->group_start()
                ->like('obras.nome', $filtros['pesquisa'])
                ->or_like('obras.codigo', $filtros['pesquisa'])
                ->or_like('clientes.nomeCliente', $filtros['pesquisa'])
                ->group_end();
        }
        if (! empty($filtros['status'])) {
            $this->db->where('obras.status', $filtros['status']);
        }
        $this->db->order_by('obras.idObra', 'desc');
        if ($perpage) {
            $this->db->limit($perpage, $start);
        }
        return $this->db->get()->result();
    }

    public function getObra($id)
    {
        if (! $this->db->table_exists('obras')) {
            return null;
        }
        $this->db->select('obras.*, clientes.nomeCliente, clientes.celular, usuarios.nome as responsavel');
        $this->db->from('obras');
        $this->db->join('clientes', 'clientes.idClientes = obras.clientes_id', 'left');
        $this->db->join('usuarios', 'usuarios.idUsuarios = obras.responsavel_id', 'left');
        $this->db->where('obras.idObra', (int) $id);
        return $this->db->get()->row();
    }

    public function add($data)
    {
        $this->db->insert('obras', $data);
        return $this->db->insert_id();
    }

    public function edit($id, $data)
    {
        $this->db->where('idObra', (int) $id);
        $this->db->update('obras', $data);
        return true;
    }

    public function delete($id)
    {
        $id = (int) $id;
        foreach (['obra_etapas', 'obra_os', 'obra_rdo', 'obra_medicao', 'obra_custo',
            'obra_material_saldo', 'obra_apontamento', 'obra_equipe_alocacao'] as $t) {
            if ($this->db->table_exists($t)) {
                $this->db->where('obra_id', $id)->delete($t);
            }
        }
        $this->db->where('idObra', $id)->delete('obras');
        return true;
    }

    /* ============================ Etapas ========================== */

    public function getEtapas($obra_id)
    {
        if (! $this->db->table_exists('obra_etapas')) {
            return [];
        }
        $this->db->where('obra_id', (int) $obra_id);
        $this->db->order_by('ordem', 'ASC');
        $this->db->order_by('idEtapa', 'ASC');
        return $this->db->get('obra_etapas')->result();
    }

    public function getEtapa($id)
    {
        if (! $this->db->table_exists('obra_etapas')) {
            return null;
        }
        return $this->db->where('idEtapa', (int) $id)->get('obra_etapas')->row();
    }

    public function addEtapa($data)
    {
        $this->db->insert('obra_etapas', $data);
        return $this->db->insert_id();
    }

    public function editEtapa($id, $data)
    {
        $this->db->where('idEtapa', (int) $id)->update('obra_etapas', $data);
        return true;
    }

    public function deleteEtapa($id)
    {
        $this->db->where('idEtapa', (int) $id)->delete('obra_etapas');
        return true;
    }

    /**
     * Recalcula o % concluído da obra a partir das etapas, ponderando pelo peso.
     * Se a soma dos pesos for 0, usa média simples.
     */
    public function recalcularProgresso($obra_id)
    {
        $etapas = $this->getEtapas($obra_id);
        if (! $etapas) {
            $this->edit($obra_id, ['percentual_concluido' => 0]);
            return 0;
        }
        $somaPeso = 0;
        $acc = 0;
        foreach ($etapas as $e) {
            $somaPeso += (float) $e->peso_percentual;
            $acc += (float) $e->peso_percentual * (float) $e->percentual_concluido;
        }
        if ($somaPeso > 0) {
            $pct = $acc / $somaPeso;
        } else {
            $total = 0;
            foreach ($etapas as $e) {
                $total += (float) $e->percentual_concluido;
            }
            $pct = $total / count($etapas);
        }
        $pct = round($pct, 2);
        $this->edit($obra_id, ['percentual_concluido' => $pct]);
        return $pct;
    }

    /* ============================ RDO ============================= */

    public function getRdos($obra_id)
    {
        if (! $this->db->table_exists('obra_rdo')) {
            return [];
        }
        $this->db->select('obra_rdo.*, usuarios.nome as responsavel');
        $this->db->from('obra_rdo');
        $this->db->join('usuarios', 'usuarios.idUsuarios = obra_rdo.responsavel_id', 'left');
        $this->db->where('obra_id', (int) $obra_id);
        $this->db->order_by('data', 'DESC');
        $this->db->order_by('idRdo', 'DESC');
        return $this->db->get()->result();
    }

    public function getRdo($id)
    {
        if (! $this->db->table_exists('obra_rdo')) {
            return null;
        }
        return $this->db->where('idRdo', (int) $id)->get('obra_rdo')->row();
    }

    public function proximoNumeroRdo($obra_id)
    {
        if (! $this->db->table_exists('obra_rdo')) {
            return 1;
        }
        $r = $this->db->select_max('numero')->where('obra_id', (int) $obra_id)->get('obra_rdo')->row();
        return ($r ? (int) $r->numero : 0) + 1;
    }

    public function addRdo($data)
    {
        $this->db->insert('obra_rdo', $data);
        return $this->db->insert_id();
    }

    public function addRdoFoto($rdo_id, $foto, $legenda = null)
    {
        if (! $this->db->table_exists('obra_rdo_foto')) {
            return false;
        }
        $this->db->insert('obra_rdo_foto', [
            'rdo_id' => (int) $rdo_id,
            'foto' => $foto,
            'legenda' => $legenda,
            'data' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }

    public function getRdoFotos($rdo_id)
    {
        if (! $this->db->table_exists('obra_rdo_foto')) {
            return [];
        }
        return $this->db->where('rdo_id', (int) $rdo_id)->get('obra_rdo_foto')->result();
    }

    /* ========================== Medição ========================== */

    public function getMedicoes($obra_id)
    {
        if (! $this->db->table_exists('obra_medicao')) {
            return [];
        }
        $this->db->where('obra_id', (int) $obra_id);
        $this->db->order_by('numero', 'ASC');
        return $this->db->get('obra_medicao')->result();
    }

    public function getMedicao($id)
    {
        if (! $this->db->table_exists('obra_medicao')) {
            return null;
        }
        return $this->db->where('idMedicao', (int) $id)->get('obra_medicao')->row();
    }

    public function proximoNumeroMedicao($obra_id)
    {
        if (! $this->db->table_exists('obra_medicao')) {
            return 1;
        }
        $r = $this->db->select_max('numero')->where('obra_id', (int) $obra_id)->get('obra_medicao')->row();
        return ($r ? (int) $r->numero : 0) + 1;
    }

    public function addMedicao($data, $itens = [])
    {
        $this->db->insert('obra_medicao', $data);
        $id = $this->db->insert_id();
        foreach ($itens as $it) {
            $it['medicao_id'] = $id;
            $this->db->insert('obra_medicao_item', $it);
        }
        return $id;
    }

    public function aprovarMedicao($id, $usuario_id, $assinatura = null)
    {
        $this->db->where('idMedicao', (int) $id)->update('obra_medicao', [
            'status' => 'aprovada',
            'aprovado_por' => (int) $usuario_id ?: null,
            'data_aprovacao' => date('Y-m-d H:i:s'),
            'assinatura_cliente' => $assinatura,
        ]);
        return true;
    }

    /* =========================== Custo =========================== */

    public function getResumoCusto($obra_id)
    {
        $out = ['previsto' => 0, 'realizado' => 0];
        if (! $this->db->table_exists('obra_custo')) {
            return $out;
        }
        $r = $this->db->select('SUM(valor_previsto) prev, SUM(valor_realizado) realiz', false)
            ->where('obra_id', (int) $obra_id)->get('obra_custo')->row();
        if ($r) {
            $out['previsto'] = (float) $r->prev;
            $out['realizado'] = (float) $r->realiz;
        }
        return $out;
    }

    public function addCusto($data)
    {
        if (! $this->db->table_exists('obra_custo')) {
            return false;
        }
        $this->db->insert('obra_custo', $data);
        return $this->db->insert_id();
    }

    /* =========================== Equipe ========================== */

    public function getEquipesAlocadas($obra_id)
    {
        if (! $this->db->table_exists('obra_equipe_alocacao')) {
            return [];
        }
        $this->db->select('a.*, equipes.nome, usuarios.nome as encarregado');
        $this->db->from('obra_equipe_alocacao a');
        $this->db->join('equipes', 'equipes.idEquipe = a.equipe_id', 'left');
        $this->db->join('usuarios', 'usuarios.idUsuarios = equipes.encarregado_id', 'left');
        $this->db->where('a.obra_id', (int) $obra_id);
        return $this->db->get()->result();
    }
}
