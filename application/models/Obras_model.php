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
        foreach (['obra_etapas', 'obra_os', 'obra_usuario', 'obra_consumo', 'obra_rdo', 'obra_medicao', 'obra_custo',
            'obra_material_saldo', 'obra_apontamento', 'obra_equipe_alocacao'] as $t) {
            if ($this->db->table_exists($t)) {
                $this->db->where('obra_id', $id)->delete($t);
            }
        }
        $this->db->where('idObra', $id)->delete('obras');
        return true;
    }

    /* ======================= Consumo (baixa) ===================== */

    /** Registra um item consumido/executado no projeto (material ou serviço). */
    public function addConsumo($data)
    {
        if (! $this->db->table_exists('obra_consumo')) {
            return false;
        }
        $this->db->insert('obra_consumo', $data);
        return $this->db->insert_id();
    }

    /** Itens consumidos/executados de um projeto (para exibição). */
    public function getConsumo($obra_id, $tipo = null)
    {
        if (! $this->db->table_exists('obra_consumo')) {
            return [];
        }
        $this->db->where('obra_id', (int) $obra_id);
        if ($tipo) {
            $this->db->where('tipo', $tipo);
        }
        $this->db->order_by('idConsumo', 'DESC');
        return $this->db->get('obra_consumo')->result();
    }

    /* ==================== Usuários do projeto ==================== */

    /** Usuários vinculados ao projeto (executores com acesso). */
    public function getUsuariosProjeto($obra_id)
    {
        if (! $this->db->table_exists('obra_usuario')) {
            return [];
        }
        $this->db->select('obra_usuario.idObraUsuario, obra_usuario.usuario_id, usuarios.nome, usuarios.email');
        $this->db->from('obra_usuario');
        $this->db->join('usuarios', 'usuarios.idUsuarios = obra_usuario.usuario_id', 'left');
        $this->db->where('obra_usuario.obra_id', (int) $obra_id);
        $this->db->order_by('usuarios.nome', 'ASC');
        return $this->db->get()->result();
    }

    /** Projetos que um usuário pode executar: onde é responsável ou está vinculado. */
    public function getProjetosDoUsuario($usuario_id)
    {
        if (! $this->db->table_exists('obras')) {
            return [];
        }
        $usuario_id = (int) $usuario_id;
        $ids = [];
        if ($this->db->table_exists('obra_usuario')) {
            $rows = $this->db->select('obra_id')->where('usuario_id', $usuario_id)
                ->get('obra_usuario')->result();
            foreach ($rows as $r) {
                $ids[] = (int) $r->obra_id;
            }
        }
        $this->db->select('obras.*, clientes.nomeCliente');
        $this->db->from('obras');
        $this->db->join('clientes', 'clientes.idClientes = obras.clientes_id', 'left');
        $this->db->group_start()->where('obras.responsavel_id', $usuario_id);
        if ($ids) {
            $this->db->or_where_in('obras.idObra', $ids);
        }
        $this->db->group_end();
        $this->db->order_by('obras.idObra', 'DESC');
        return $this->db->get()->result();
    }

    public function vincularUsuario($obra_id, $usuario_id)
    {
        if (! $this->db->table_exists('obra_usuario')) {
            return false;
        }
        $obra_id = (int) $obra_id;
        $usuario_id = (int) $usuario_id;
        if (! $obra_id || ! $usuario_id) {
            return false;
        }
        if ($this->db->where(['obra_id' => $obra_id, 'usuario_id' => $usuario_id])->count_all_results('obra_usuario')) {
            return false;
        }
        $this->db->insert('obra_usuario', [
            'obra_id' => $obra_id,
            'usuario_id' => $usuario_id,
            'data_vinculo' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }

    public function desvincularUsuario($id, $obra_id)
    {
        if (! $this->db->table_exists('obra_usuario')) {
            return false;
        }
        $this->db->where('idObraUsuario', (int) $id)
            ->where('obra_id', (int) $obra_id)
            ->delete('obra_usuario');
        return true;
    }

    /**
     * Quem pode dar seguimento/registrar a execução do projeto:
     * o responsável do projeto ou qualquer usuário vinculado a ele.
     */
    public function usuarioTemAcesso($obra_id, $usuario_id)
    {
        $usuario_id = (int) $usuario_id;
        if (! $usuario_id) {
            return false;
        }
        $obra = $this->getObra($obra_id);
        if ($obra && (int) $obra->responsavel_id === $usuario_id) {
            return true;
        }
        if (! $this->db->table_exists('obra_usuario')) {
            return false;
        }
        return (bool) $this->db->where(['obra_id' => (int) $obra_id, 'usuario_id' => $usuario_id])
            ->count_all_results('obra_usuario');
    }

    /** True se a OS está vinculada a algum projeto que o usuário pode acessar. */
    public function osEmProjetoDoUsuario($os_id, $usuario_id)
    {
        if (! $this->db->table_exists('obra_os')) {
            return false;
        }
        $vinculos = $this->db->select('obra_id')->where('os_id', (int) $os_id)
            ->get('obra_os')->result();
        foreach ($vinculos as $v) {
            if ($this->usuarioTemAcesso($v->obra_id, $usuario_id)) {
                return true;
            }
        }
        return false;
    }

    /* ======================= OS vinculadas ======================= */

    /** OS ligadas ao projeto, com cliente, técnico e status de execução. */
    public function getOsVinculadas($obra_id)
    {
        if (! $this->db->table_exists('obra_os') || ! $this->db->table_exists('os')) {
            return [];
        }
        $this->db->select('obra_os.idVinculo, obra_os.etapa_id, os.idOs, os.status, os.dataInicial, clientes.nomeCliente, usuarios.nome as tecnico, etapa.nome as etapa_nome');
        $this->db->from('obra_os');
        $this->db->join('os', 'os.idOs = obra_os.os_id');
        $this->db->join('clientes', 'clientes.idClientes = os.clientes_id', 'left');
        $this->db->join('usuarios', 'usuarios.idUsuarios = os.usuarios_id', 'left');
        $this->db->join('obra_etapas etapa', 'etapa.idEtapa = obra_os.etapa_id', 'left');
        $this->db->where('obra_os.obra_id', (int) $obra_id);
        $this->db->order_by('os.idOs', 'desc');
        return $this->db->get()->result();
    }

    /** OS ainda não vinculadas a nenhum projeto (candidatas ao vínculo). */
    public function getOsDisponiveis($limite = 300)
    {
        if (! $this->db->table_exists('obra_os') || ! $this->db->table_exists('os')) {
            return [];
        }
        $this->db->select('os.idOs, os.status, os.dataInicial, clientes.nomeCliente');
        $this->db->from('os');
        $this->db->join('clientes', 'clientes.idClientes = os.clientes_id', 'left');
        $this->db->where('os.idOs NOT IN (SELECT os_id FROM obra_os)', null, false);
        $this->db->order_by('os.idOs', 'desc');
        $this->db->limit((int) $limite);
        return $this->db->get()->result();
    }

    public function vincularOs($obra_id, $os_id, $etapa_id = null)
    {
        if (! $this->db->table_exists('obra_os')) {
            return false;
        }
        $obra_id = (int) $obra_id;
        $os_id = (int) $os_id;
        if (! $obra_id || ! $os_id) {
            return false;
        }
        // não duplica o mesmo vínculo
        if ($this->db->where(['obra_id' => $obra_id, 'os_id' => $os_id])->count_all_results('obra_os')) {
            return false;
        }
        $this->db->insert('obra_os', [
            'obra_id' => $obra_id,
            'os_id' => $os_id,
            'etapa_id' => $etapa_id ? (int) $etapa_id : null,
        ]);
        return $this->db->insert_id();
    }

    public function desvincularOs($idVinculo, $obra_id)
    {
        if (! $this->db->table_exists('obra_os')) {
            return false;
        }
        $this->db->where('idVinculo', (int) $idVinculo)
            ->where('obra_id', (int) $obra_id)
            ->delete('obra_os');
        return true;
    }

    /** Serviços das OS ligadas — "o que executar" no projeto. */
    public function getServicosDasOs($obra_id)
    {
        if (! $this->db->table_exists('obra_os') || ! $this->db->table_exists('servicos_os')) {
            return [];
        }
        $this->db->select('servicos_os.os_id, servicos_os.servicos_id, servicos.nome, servicos_os.quantidade, servicos_os.preco');
        $this->db->from('obra_os');
        $this->db->join('servicos_os', 'servicos_os.os_id = obra_os.os_id');
        $this->db->join('servicos', 'servicos.idServicos = servicos_os.servicos_id', 'left');
        $this->db->where('obra_os.obra_id', (int) $obra_id);
        $this->db->order_by('servicos_os.os_id', 'asc');
        return $this->db->get()->result();
    }

    /** Produtos das OS ligadas — "material a utilizar" no projeto. */
    public function getProdutosDasOs($obra_id)
    {
        if (! $this->db->table_exists('obra_os') || ! $this->db->table_exists('produtos_os')) {
            return [];
        }
        $this->db->select('produtos_os.os_id, produtos_os.produtos_id, produtos.descricao as nome, produtos_os.quantidade, produtos_os.preco');
        $this->db->from('obra_os');
        $this->db->join('produtos_os', 'produtos_os.os_id = obra_os.os_id');
        $this->db->join('produtos', 'produtos.idProdutos = produtos_os.produtos_id', 'left');
        $this->db->where('obra_os.obra_id', (int) $obra_id);
        $this->db->order_by('produtos_os.os_id', 'asc');
        return $this->db->get()->result();
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

    /**
     * Gera (ou reaproveita) um token público para abrir o RDO por link temporário
     * (dominio/rdo/<token>), usado no envio ao grupo de WhatsApp. Só funciona se a
     * coluna `token` existir (migration add_links_grupo_events).
     *
     * @return string|null o token, ou null se indisponível
     */
    public function gerarTokenRdo($rdo_id, $diasValidade = 30)
    {
        if (! $this->db->field_exists('token', 'obra_rdo')) {
            return null;
        }
        $rdo = $this->getRdo($rdo_id);
        if (! $rdo) {
            return null;
        }

        // Reaproveita o token ainda válido.
        if (! empty($rdo->token) && (empty($rdo->token_expira) || strtotime($rdo->token_expira) > time())) {
            return $rdo->token;
        }

        $token = bin2hex(random_bytes(24));
        $expira = date('Y-m-d H:i:s', strtotime('+' . (int) $diasValidade . ' days'));
        $this->db->where('idRdo', (int) $rdo_id)->update('obra_rdo', [
            'token' => $token,
            'token_expira' => $expira,
        ]);

        return $token;
    }

    /**
     * Carrega um RDO pelo token público (com nome do responsável), respeitando
     * a validade. Retorna null se o token não existir ou tiver expirado.
     */
    public function getRdoByToken($token)
    {
        if (empty($token) || ! $this->db->field_exists('token', 'obra_rdo')) {
            return null;
        }
        $this->db->select('obra_rdo.*, usuarios.nome as responsavel');
        $this->db->from('obra_rdo');
        $this->db->join('usuarios', 'usuarios.idUsuarios = obra_rdo.responsavel_id', 'left');
        $this->db->where('obra_rdo.token', $token);
        $this->db->limit(1);
        $rdo = $this->db->get()->row();

        if (! $rdo) {
            return null;
        }
        if (! empty($rdo->token_expira) && strtotime($rdo->token_expira) < time()) {
            return null;
        }

        return $rdo;
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

    /**
     * Mantém uma linha única de custo "Material (compras da empresa)" com o
     * total realizado a partir dos recebimentos cuja origem é 'compra'
     * (material comprado pela empresa). Idempotente.
     */
    public function recalcularCustoMaterialCompra($obra_id)
    {
        if (! $this->db->table_exists('obra_custo')
            || ! $this->db->table_exists('obra_material_recebimento')
            || ! $this->db->table_exists('obra_material_recebimento_item')) {
            return;
        }
        $obra_id = (int) $obra_id;
        $r = $this->db->select('SUM(i.quantidade_conferida * i.valor_unitario) t', false)
            ->from('obra_material_recebimento_item i')
            ->join('obra_material_recebimento r', 'r.idRecebimento = i.recebimento_id')
            ->where('r.obra_id', $obra_id)
            ->where('r.origem', 'compra')
            ->get()->row();
        $total = $r ? (float) $r->t : 0;

        $desc = 'Material (compras da empresa)';
        $ex = $this->db->where('obra_id', $obra_id)->where('categoria', 'material')
            ->where('descricao', $desc)->get('obra_custo')->row();
        if ($ex) {
            $this->db->where('idCusto', $ex->idCusto)->update('obra_custo', [
                'valor_realizado' => $total, 'data' => date('Y-m-d'),
            ]);
        } else {
            $this->db->insert('obra_custo', [
                'obra_id' => $obra_id, 'categoria' => 'material', 'descricao' => $desc,
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
