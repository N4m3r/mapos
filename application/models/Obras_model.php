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

    /**
     * Mantém uma linha única de custo "Mão de obra (apontamento)" com o total
     * realizado a partir dos apontamentos da obra.
     */
    public function recalcularCustoMaoObra($obra_id)
    {
        if (! $this->db->table_exists('obra_custo') || ! $this->db->table_exists('obra_apontamento')) {
            return;
        }
        $obra_id = (int) $obra_id;
        $r = $this->db->select('SUM(valor) t', false)->where('obra_id', $obra_id)
            ->get('obra_apontamento')->row();
        $total = $r ? (float) $r->t : 0;

        $desc = 'Mão de obra (apontamento)';
        $ex = $this->db->where('obra_id', $obra_id)->where('categoria', 'mao_obra')
            ->where('descricao', $desc)->get('obra_custo')->row();
        if ($ex) {
            $this->db->where('idCusto', $ex->idCusto)->update('obra_custo', [
                'valor_realizado' => $total, 'data' => date('Y-m-d'),
            ]);
        } else {
            $this->db->insert('obra_custo', [
                'obra_id' => $obra_id, 'categoria' => 'mao_obra', 'descricao' => $desc,
                'valor_previsto' => 0, 'valor_realizado' => $total, 'data' => date('Y-m-d'),
            ]);
        }
    }

    /* ========================= Apontamento (efetivo) ========================= */

    public function getApontamentos($obra_id, $limite = 200)
    {
        if (! $this->db->table_exists('obra_apontamento')) {
            return [];
        }
        $this->db->where('obra_id', (int) $obra_id);
        $this->db->order_by('data', 'DESC');
        $this->db->order_by('idApontamento', 'DESC');
        $this->db->limit($limite);
        return $this->db->get('obra_apontamento')->result();
    }

    public function getTotalApontamento($obra_id)
    {
        if (! $this->db->table_exists('obra_apontamento')) {
            return 0;
        }
        $r = $this->db->select('SUM(valor) t', false)->where('obra_id', (int) $obra_id)
            ->get('obra_apontamento')->row();
        return $r ? (float) $r->t : 0;
    }

    public function addApontamento($data)
    {
        if (! $this->db->table_exists('obra_apontamento')) {
            return false;
        }
        $this->db->insert('obra_apontamento', $data);
        $id = $this->db->insert_id();
        $this->recalcularCustoMaoObra($data['obra_id']);
        return $id;
    }

    public function deleteApontamento($id, $obra_id)
    {
        if (! $this->db->table_exists('obra_apontamento')) {
            return false;
        }
        $this->db->where('idApontamento', (int) $id)->delete('obra_apontamento');
        $this->recalcularCustoMaoObra($obra_id);
        return true;
    }

    /**
     * Consolida o efetivo do dia a partir do ponto facial/GPS: agrupa as
     * batidas vinculadas à obra por colaborador, calcula horas (1ª entrada →
     * última saída), busca diária/hora do membro da equipe e grava em
     * obra_apontamento. Idempotente por (obra, dia): recria a consolidação.
     *
     * @return int quantidade de colaboradores consolidados
     */
    public function consolidarPonto($obra_id, $dataDia)
    {
        $obra_id = (int) $obra_id;
        if (! $this->db->table_exists('obra_apontamento')
            || ! $this->db->table_exists('rh_ponto_registros')
            || ! $this->db->field_exists('obra_id', 'rh_ponto_registros')) {
            return 0;
        }
        $dataDia = date('Y-m-d', strtotime($dataDia));

        $this->db->select('colaborador_id, MIN(data_hora) ent, MAX(data_hora) sai, COUNT(*) n', false);
        $this->db->where('obra_id', $obra_id);
        $this->db->where('DATE(data_hora) = ' . $this->db->escape($dataDia), null, false);
        $this->db->group_by('colaborador_id');
        $rows = $this->db->get('rh_ponto_registros')->result();
        if (! $rows) {
            return 0;
        }

        // Remove consolidação anterior do mesmo dia (mantém apontamentos manuais).
        $this->db->where('obra_id', $obra_id)
            ->where('data', $dataDia)
            ->where('origem', 'ponto_facial')
            ->delete('obra_apontamento');

        $count = 0;
        foreach ($rows as $r) {
            $horas = (strtotime($r->sai) - strtotime($r->ent)) / 3600;
            $horas = $horas > 0 ? round($horas, 2) : 0;

            $nome = null;
            $usuarioId = null;
            if ($this->db->table_exists('rh_colaboradores')) {
                $c = $this->db->where('id', (int) $r->colaborador_id)->get('rh_colaboradores')->row();
                if ($c) {
                    $nome = $c->nome ?? null;
                    $usuarioId = $c->usuarios_id ?? null;
                }
            }

            $funcao = null;
            $diaria = 0;
            $hora = 0;
            if ($usuarioId && $this->db->table_exists('equipe_membros')) {
                $this->db->select('em.funcao, em.valor_diaria, em.valor_hora');
                $this->db->from('equipe_membros em');
                if ($this->db->table_exists('obra_equipe_alocacao')) {
                    $this->db->join('obra_equipe_alocacao a', 'a.equipe_id = em.equipe_id');
                    $this->db->where('a.obra_id', $obra_id);
                }
                $this->db->where('em.colaborador_id', (int) $usuarioId);
                $this->db->limit(1);
                $m = $this->db->get()->row();
                if ($m) {
                    $funcao = $m->funcao;
                    $diaria = (float) $m->valor_diaria;
                    $hora = (float) $m->valor_hora;
                }
            }

            $usaDiaria = $diaria > 0;
            $valor = $usaDiaria ? $diaria : $hora * $horas;

            $this->db->insert('obra_apontamento', [
                'obra_id' => $obra_id,
                'colaborador_id' => (int) $r->colaborador_id,
                'nome' => $nome,
                'funcao' => $funcao,
                'data' => $dataDia,
                'horas' => $horas,
                'diaria' => $usaDiaria ? 1 : 0,
                'origem' => 'ponto_facial',
                'valor' => round($valor, 2),
            ]);
            $count++;
        }

        $this->recalcularCustoMaoObra($obra_id);
        return $count;
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

    /* ===================== Painel / KPIs (cross-obra) ===================== */

    /**
     * Indicadores gerais do painel de obras.
     */
    public function kpis()
    {
        $out = [
            'total' => 0, 'em_execucao' => 0, 'planejamento' => 0, 'concluidas' => 0,
            'paralisadas' => 0, 'progresso_medio' => 0, 'valor_contratos' => 0,
            'medicoes_abertas' => 0, 'custodia_cliente' => 0,
        ];
        if (! $this->db->table_exists('obras')) {
            return $out;
        }

        $r = $this->db->select("
            COUNT(*) total,
            SUM(status = 'em_execucao') em_execucao,
            SUM(status = 'planejamento') planejamento,
            SUM(status IN ('concluida','entregue')) concluidas,
            SUM(status = 'paralisada') paralisadas,
            AVG(percentual_concluido) progresso_medio,
            SUM(valor_contrato) valor_contratos
        ", false)->get('obras')->row();
        if ($r) {
            $out['total'] = (int) $r->total;
            $out['em_execucao'] = (int) $r->em_execucao;
            $out['planejamento'] = (int) $r->planejamento;
            $out['concluidas'] = (int) $r->concluidas;
            $out['paralisadas'] = (int) $r->paralisadas;
            $out['progresso_medio'] = round((float) $r->progresso_medio, 1);
            $out['valor_contratos'] = (float) $r->valor_contratos;
        }

        if ($this->db->table_exists('obra_medicao')) {
            $out['medicoes_abertas'] = (int) $this->db->where('status', 'aberta')
                ->count_all_results('obra_medicao');
        }
        if ($this->db->table_exists('obra_material_saldo')) {
            $c = $this->db->select('SUM(saldo) s', false)->where('do_cliente', 1)
                ->where('saldo >', 0)->get('obra_material_saldo')->row();
            $out['custodia_cliente'] = $c ? (float) $c->s : 0;
        }
        return $out;
    }

    /** Obras por status (para o painel), com cliente e responsável. */
    public function getObrasPorStatus($status, $limite = 10)
    {
        if (! $this->db->table_exists('obras')) {
            return [];
        }
        $this->db->select('obras.*, clientes.nomeCliente, usuarios.nome as responsavel');
        $this->db->from('obras');
        $this->db->join('clientes', 'clientes.idClientes = obras.clientes_id', 'left');
        $this->db->join('usuarios', 'usuarios.idUsuarios = obras.responsavel_id', 'left');
        if (is_array($status)) {
            $this->db->where_in('obras.status', $status);
        } else {
            $this->db->where('obras.status', $status);
        }
        $this->db->order_by('obras.percentual_concluido', 'DESC');
        $this->db->limit($limite);
        return $this->db->get()->result();
    }

    /** Medições aguardando aprovação em todas as obras. */
    public function getMedicoesAbertas($limite = 20)
    {
        if (! $this->db->table_exists('obra_medicao')) {
            return [];
        }
        $this->db->select('m.*, obras.nome as obra_nome, clientes.nomeCliente');
        $this->db->from('obra_medicao m');
        $this->db->join('obras', 'obras.idObra = m.obra_id', 'left');
        $this->db->join('clientes', 'clientes.idClientes = obras.clientes_id', 'left');
        $this->db->where('m.status', 'aberta');
        $this->db->order_by('m.data_cadastro', 'DESC');
        $this->db->limit($limite);
        return $this->db->get()->result();
    }

    /** RDOs recentes de todas as obras. */
    public function getRdosRecentes($limite = 8)
    {
        if (! $this->db->table_exists('obra_rdo')) {
            return [];
        }
        $this->db->select('r.*, obras.nome as obra_nome, usuarios.nome as responsavel');
        $this->db->from('obra_rdo r');
        $this->db->join('obras', 'obras.idObra = r.obra_id', 'left');
        $this->db->join('usuarios', 'usuarios.idUsuarios = r.responsavel_id', 'left');
        $this->db->order_by('r.data', 'DESC');
        $this->db->order_by('r.idRdo', 'DESC');
        $this->db->limit($limite);
        return $this->db->get()->result();
    }
}
