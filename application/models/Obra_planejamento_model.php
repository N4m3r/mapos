<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Planejamento do projeto (ADR-015 fase 2): itens previstos por etapa e
 * geracao de OS a partir da etapa (individual ou em lote).
 *
 * As funcoes estaticas sao puras (sem banco). As consultas sao resilientes:
 * sem a migracao aplicada devolvem vazio.
 *
 * DECISAO DO ADR: material de OS vinculada sai do almoxarifado do projeto,
 * por isso ao gerar a OS NAO se baixa o estoque geral dos produtos
 * (diferente de Os::adicionarProduto).
 */
class Obra_planejamento_model extends CI_Model
{
    /* ====================== Funcoes puras ====================== */

    /** Descricao da OS: "Projeto X - Etapa 1.2 Nome" + pontos, se houver. */
    public static function montarDescricaoOs($obraNome, $codigoEtapa, $nomeEtapa, array $identificacoes = [], $observacao = '')
    {
        $etapa = trim(trim((string) $codigoEtapa) . ' ' . trim((string) $nomeEtapa));
        $texto = 'Projeto ' . trim((string) $obraNome) . ' - Etapa ' . $etapa;
        $ids = array_values(array_filter(array_map('trim', array_map('strval', $identificacoes)), 'strlen'));
        if ($ids) {
            $texto .= "\nPontos: " . implode(', ', $ids);
        }
        $observacao = trim((string) $observacao);
        if ($observacao !== '') {
            $texto .= "\n" . $observacao;
        }
        return $texto;
    }

    /** Subtotal de um item (2 casas). */
    public static function subtotal($quantidade, $preco)
    {
        return round((float) $quantidade * (float) $preco, 2);
    }

    /** Total dos itens (array/objeto com quantidade e preco). */
    public static function calcularTotal(array $itens)
    {
        $total = 0.0;
        foreach ($itens as $i) {
            $i = (array) $i;
            $total += self::subtotal($i['quantidade'] ?? 0, $i['preco'] ?? 0);
        }
        return round($total, 2);
    }

    /**
     * Quais etapas entram no lote. $osPorEtapa = [etapa_id => [ {status, tipo_vinculo}... ]].
     * Sem $forcar, pula a etapa com OS de execucao nao cancelada.
     * Retorna [etapa_id => true (entra) | false (pulada)].
     */
    public static function filtrarEtapasParaLote(array $etapas, array $osPorEtapa, $forcar = false)
    {
        $res = [];
        foreach ($etapas as $etapa) {
            $id = (int) (is_array($etapa) ? ($etapa['idEtapa'] ?? 0) : (is_object($etapa) ? ($etapa->idEtapa ?? 0) : $etapa));
            if (! $id) {
                continue;
            }
            $res[$id] = $forcar || ! self::temOsDeExecucao($osPorEtapa[$id] ?? []);
        }
        return $res;
    }

    /** Ha OS tipo execucao nao cancelada na lista? */
    public static function temOsDeExecucao(array $osDaEtapa)
    {
        foreach ($osDaEtapa as $o) {
            $o = (array) $o;
            $tipo = $o['tipo_vinculo'] ?? 'execucao';
            if ($tipo === 'execucao' && ($o['status'] ?? '') !== 'Cancelado') {
                return true;
            }
        }
        return false;
    }

    /* ====================== Itens previstos ====================== */

    private function temItens()
    {
        return $this->db->table_exists('obra_etapa_itens');
    }

    /** Itens previstos agrupados por etapa. */
    public function getItensObra($obraId)
    {
        $obraId = (int) $obraId;
        if (! $obraId || ! $this->temItens()) {
            return [];
        }
        $rows = $this->db->select('i.*, p.descricao AS produto_desc, s.nome AS servico_nome')
            ->from('obra_etapa_itens i')
            ->join('produtos p', "p.idProdutos = i.referencia_id AND i.tipo = 'produto'", 'left')
            ->join('servicos s', "s.idServicos = i.referencia_id AND i.tipo = 'servico'", 'left')
            ->where('i.obra_id', $obraId)
            ->order_by('i.idItem')
            ->get()->result();
        $out = [];
        foreach ($rows as $r) {
            $desc = trim((string) $r->descricao);
            if ($desc === '') {
                $desc = (string) ($r->tipo === 'produto' ? $r->produto_desc : $r->servico_nome);
            }
            $out[(int) $r->etapa_id][] = [
                'idItem' => (int) $r->idItem,
                'tipo' => $r->tipo,
                'referencia_id' => (int) $r->referencia_id,
                'descricao' => $desc,
                'quantidade' => (float) $r->quantidade,
                'preco' => (float) $r->preco,
                'subtotal' => self::subtotal($r->quantidade, $r->preco),
            ];
        }
        return $out;
    }

    /** Adiciona item previsto; devolve idItem ou false. */
    public function addItem($obraId, $etapaId, $tipo, $referenciaId, $quantidade, $preco = null)
    {
        $obraId = (int) $obraId;
        $etapaId = (int) $etapaId;
        $referenciaId = (int) $referenciaId;
        $quantidade = (float) $quantidade;
        if (! $this->temItens() || ! $obraId || ! $etapaId || ! $referenciaId || $quantidade <= 0) {
            return false;
        }
        if (! in_array($tipo, ['produto', 'servico'], true)) {
            return false;
        }
        if (! $this->db->where(['idEtapa' => $etapaId, 'obra_id' => $obraId])->count_all_results('obra_etapas')) {
            return false;
        }
        if ($tipo === 'produto') {
            $ref = $this->db->select('descricao, precoVenda AS valor')->get_where('produtos', ['idProdutos' => $referenciaId])->row();
            $descricao = $ref ? $ref->descricao : '';
        } else {
            $ref = $this->db->select('nome, preco AS valor')->get_where('servicos', ['idServicos' => $referenciaId])->row();
            $descricao = $ref ? $ref->nome : '';
        }
        if (! $ref) {
            return false;
        }
        if ($preco === null) {
            $preco = (float) $ref->valor;
        }
        $this->db->insert('obra_etapa_itens', [
            'obra_id' => $obraId,
            'etapa_id' => $etapaId,
            'tipo' => $tipo,
            'referencia_id' => $referenciaId,
            'descricao' => mb_substr((string) $descricao, 0, 150),
            'quantidade' => $quantidade,
            'preco' => (float) $preco,
            'data_cadastro' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->affected_rows() ? (int) $this->db->insert_id() : false;
    }

    public function deleteItem($idItem, $obraId)
    {
        if (! $this->temItens()) {
            return false;
        }
        $this->db->where(['idItem' => (int) $idItem, 'obra_id' => (int) $obraId])->delete('obra_etapa_itens');
        return $this->db->affected_rows() > 0;
    }

    /** Total previsto (itens) por etapa. */
    public function totaisPrevistosPorEtapa($obraId)
    {
        $out = [];
        foreach ($this->getItensObra($obraId) as $etapaId => $itens) {
            $out[$etapaId] = self::calcularTotal($itens);
        }
        return $out;
    }

    /* ====================== OS da etapa / pontos ====================== */

    /** OS vinculadas por etapa: [etapa_id => [{idOs, status, tecnico_nome, tipo_vinculo}]]. */
    public function getOsPorEtapa($obraId)
    {
        if (! $this->db->table_exists('obra_os')) {
            return [];
        }
        $rows = $this->db->select('v.etapa_id, v.tipo_vinculo, o.idOs, o.status, u.nome AS tecnico_nome')
            ->from('obra_os v')
            ->join('os o', 'o.idOs = v.os_id')
            ->join('usuarios u', 'u.idUsuarios = o.tecnico_responsavel', 'left')
            ->where('v.obra_id', (int) $obraId)
            ->where('v.etapa_id IS NOT NULL', null, false)
            ->order_by('o.idOs')
            ->get()->result();
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->etapa_id][] = (object) [
                'idOs' => (int) $r->idOs,
                'status' => $r->status,
                'tecnico_nome' => $r->tecnico_nome,
                'tipo_vinculo' => $r->tipo_vinculo,
            ];
        }
        return $out;
    }

    /** Pontos da obra sem OS ativa (nao cancelada). */
    public function pontosSemOs($obraId)
    {
        if (! $this->db->table_exists('obra_pontos')) {
            return [];
        }
        $this->db->from('obra_pontos p')->where('p.obra_id', (int) $obraId);
        if ($this->db->table_exists('obra_ponto_os')) {
            $this->db->where("NOT EXISTS (SELECT 1 FROM obra_ponto_os po JOIN os o ON o.idOs = po.os_id WHERE po.ponto_id = p.idPonto AND (o.status IS NULL OR o.status <> 'Cancelado'))", null, false);
        }
        return $this->db->order_by('p.identificacao')->get()->result();
    }

    public function getPontosDaOs($osId)
    {
        if (! $this->db->table_exists('obra_ponto_os')) {
            return [];
        }
        return $this->db->select('p.*')
            ->from('obra_ponto_os po')
            ->join('obra_pontos p', 'p.idPonto = po.ponto_id')
            ->where('po.os_id', (int) $osId)
            ->order_by('p.identificacao')
            ->get()->result();
    }

    /** Etapas do projeto sem OS de execucao ativa. */
    public function etapasSemOs($obraId)
    {
        if (! $this->db->table_exists('obra_etapas')) {
            return [];
        }
        $etapas = $this->db->where('obra_id', (int) $obraId)->order_by('ordem')->order_by('codigo_eap')->get('obra_etapas')->result();
        $mapa = self::filtrarEtapasParaLote($etapas, $this->getOsPorEtapa($obraId), false);
        return array_values(array_filter($etapas, function ($e) use ($mapa) {
            return ! empty($mapa[(int) $e->idEtapa]);
        }));
    }

    /* ====================== Gerar OS ====================== */

    /**
     * Gera uma OS a partir da etapa. Retorna ['ok','idOs','erro'].
     * opcoes: usuario_id, tecnico_id, equipe_id, data_agendamento, tipo_vinculo,
     * fatura_no_projeto, pontos[], observacao.
     */
    public function gerarOsDaEtapa($etapaId, array $opcoes)
    {
        $falha = function ($msg) {
            return ['ok' => false, 'idOs' => null, 'erro' => $msg];
        };
        $etapa = $this->db->get_where('obra_etapas', ['idEtapa' => (int) $etapaId])->row();
        if (! $etapa) {
            return $falha('Etapa nao encontrada');
        }
        $obra = $this->db->get_where('obras', ['idObra' => $etapa->obra_id])->row();
        if (! $obra) {
            return $falha('Projeto nao encontrado');
        }
        if (empty($obra->clientes_id)) {
            return $falha('Projeto sem cliente');
        }
        if (in_array($obra->status, ['concluida', 'entregue', 'cancelada'], true)) {
            return $falha('Projeto nao esta em aberto');
        }
        $usuarioId = (int) ($opcoes['usuario_id'] ?? 0);
        if (! $usuarioId) {
            return $falha('Usuario nao informado');
        }

        // pontos precisam ser da obra
        $identificacoes = [];
        $pontos = array_values(array_unique(array_filter(array_map('intval', (array) ($opcoes['pontos'] ?? [])))));
        if ($pontos) {
            $rows = $this->db->select('idPonto, identificacao')->where('obra_id', (int) $obra->idObra)->where_in('idPonto', $pontos)->order_by('identificacao')->get('obra_pontos')->result();
            if (count($rows) !== count($pontos)) {
                return $falha('Ponto nao pertence ao projeto');
            }
            foreach ($rows as $r) {
                $identificacoes[] = $r->identificacao;
            }
            // ponto com OS ativa nao entra em outra OS
            if ($this->db->table_exists('obra_ponto_os')) {
                $ocupados = $this->db->select('p.identificacao, o.idOs')
                    ->from('obra_ponto_os po')
                    ->join('os o', 'o.idOs = po.os_id')
                    ->join('obra_pontos p', 'p.idPonto = po.ponto_id')
                    ->where_in('po.ponto_id', $pontos)
                    ->where("(o.status IS NULL OR o.status <> 'Cancelado')", null, false)
                    ->get()->result();
                if ($ocupados) {
                    $lista = array_map(function ($o) {
                        return $o->identificacao . ' (OS #' . $o->idOs . ')';
                    }, $ocupados);
                    return $falha('Ponto ja tem OS: ' . implode(', ', $lista));
                }
            }
        }

        // tecnico valido (mesma regra de Os::atribuirTecnicoAction)
        $tecnicoId = (int) ($opcoes['tecnico_id'] ?? 0);
        if ($tecnicoId) {
            $this->load->model('tecnico_model');
            if (! $this->tecnico_model->isTecnicoValido($tecnicoId)) {
                return $falha('Usuario selecionado nao e um tecnico valido');
            }
        }

        $equipeId = (int) ($opcoes['equipe_id'] ?? 0);
        $equipe = null;
        if ($equipeId) {
            $equipe = $this->db->table_exists('equipes') ? $this->db->get_where('equipes', ['idEquipe' => $equipeId])->row() : null;
            if (! $equipe) {
                return $falha('Equipe nao encontrada');
            }
        }

        // agendamento (datetime) - padrao: inicio previsto da etapa
        $agenda = null;
        $bruto = $opcoes['data_agendamento'] ?? $etapa->data_inicio_prevista;
        if (! empty($bruto) && ($ts = strtotime(str_replace('T', ' ', (string) $bruto)))) {
            $agenda = date('Y-m-d H:i:s', $ts);
        }
        $dataInicial = $agenda ? substr($agenda, 0, 10) : date('Y-m-d');
        $dataFinal = ! empty($etapa->data_fim_prevista) ? $etapa->data_fim_prevista : $dataInicial;

        $tipoVinculo = $opcoes['tipo_vinculo'] ?? 'execucao';
        if (! in_array($tipoVinculo, ['origem', 'execucao', 'adicional', 'corretiva'], true)) {
            $tipoVinculo = 'execucao';
        }
        $fatura = array_key_exists('fatura_no_projeto', $opcoes) ? ((int) (bool) $opcoes['fatura_no_projeto']) : 1;

        $itens = $this->temItens()
            ? $this->db->where('etapa_id', (int) $etapa->idEtapa)->order_by('idItem')->get('obra_etapa_itens')->result()
            : [];

        $this->load->model('os_model');
        $this->load->model('obras_model');

        $this->db->trans_start();

        $dados = [
            'dataInicial' => $dataInicial,
            'dataFinal' => $dataFinal,
            'clientes_id' => (int) $obra->clientes_id,
            'usuarios_id' => $usuarioId,
            'descricaoProduto' => self::montarDescricaoOs($obra->nome, $etapa->codigo_eap, $etapa->nome, $identificacoes, $opcoes['observacao'] ?? ''),
            'status' => 'Aberto',
            'faturado' => 0,
        ];
        if ($agenda && $this->db->field_exists('data_agendamento', 'os')) {
            $dados['data_agendamento'] = $agenda;
        }
        if ($equipe && $this->db->field_exists('equipe_id', 'os')) {
            $dados['equipe_id'] = $equipeId;
            if ($this->db->field_exists('custo_equipe', 'os')) {
                $dados['custo_equipe'] = (float) ($equipe->custo_padrao ?? 0);
            }
        }
        $osId = $this->os_model->add('os', $dados, true);
        $erro = null;

        if (! is_numeric($osId) || ! $osId) {
            $erro = 'Erro ao gerar a OS';
        } else {
            // itens: copia inteira da etapa. Sem baixa de estoque geral (ver nota da classe).
            foreach ($itens as $i) {
                if ($i->tipo === 'produto') {
                    $qtd = max(1, (int) round($i->quantidade)); // produtos_os.quantidade e inteira
                    $this->os_model->add('produtos_os', [
                        'os_id' => $osId,
                        'produtos_id' => (int) $i->referencia_id,
                        'quantidade' => $qtd,
                        'preco' => (float) $i->preco,
                        'subTotal' => self::subtotal($qtd, $i->preco),
                    ]);
                } else {
                    $this->os_model->add('servicos_os', [
                        'os_id' => $osId,
                        'servicos_id' => (int) $i->referencia_id,
                        'servico' => mb_substr((string) $i->descricao, 0, 80),
                        'quantidade' => (float) $i->quantidade,
                        'preco' => (float) $i->preco,
                        'subTotal' => self::subtotal($i->quantidade, $i->preco),
                    ]);
                }
            }
            if ($itens) {
                // mesmo calculo do sistema: servicos + produtos (sem desconto)
                $v = $this->os_model->valorTotalOS($osId);
                $this->os_model->edit('os', ['valorTotal' => round($v['totalServico'] + $v['totalProdutos'], 2)], 'idOs', $osId);
            }

            // tecnico: historico em os_tecnico_atribuicao + tecnico_responsavel
            if ($tecnicoId && $this->db->table_exists('os_tecnico_atribuicao')) {
                $this->tecnico_model->atribuirTecnico($osId, $tecnicoId, $usuarioId, $opcoes['observacao'] ?? null);
            }

            // vinculo com o projeto
            if (! $this->obras_model->vincularOs($obra->idObra, $osId, $etapa->idEtapa, $tipoVinculo, $fatura)) {
                $erro = 'Nao foi possivel vincular a OS ao projeto';
            }

            // pontos da OS
            if (! $erro && $pontos && $this->db->table_exists('obra_ponto_os')) {
                foreach ($pontos as $pid) {
                    $this->db->insert('obra_ponto_os', ['ponto_id' => $pid, 'os_id' => $osId, 'data_cadastro' => date('Y-m-d H:i:s')]);
                }
            }
        }

        if ($erro) {
            $this->db->trans_rollback();
            return $falha($erro);
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            return $falha('Erro ao gravar a OS');
        }
        return ['ok' => true, 'idOs' => (int) $osId, 'erro' => null];
    }

    /**
     * Gera OS para varias etapas. etapaIds vazio = todas as etapas sem OS.
     * Retorna [['etapa_id','ok','idOs','erro'], ...].
     */
    public function gerarOsEmLote($obraId, array $etapaIds, array $opcoes)
    {
        $obraId = (int) $obraId;
        $forcar = ! empty($opcoes['forcar']);
        unset($opcoes['forcar'], $opcoes['pontos']); // pontos so valem para OS individual

        $todas = $this->db->where('obra_id', $obraId)->order_by('ordem')->order_by('codigo_eap')->get('obra_etapas')->result();
        $validas = array_map(function ($e) {
            return (int) $e->idEtapa;
        }, $todas);
        $osPorEtapa = $this->getOsPorEtapa($obraId);

        $ids = array_map('intval', $etapaIds);
        if (! $ids) {
            $mapa = self::filtrarEtapasParaLote($todas, $osPorEtapa, $forcar);
            $ids = array_values(array_filter($validas, function ($id) use ($mapa) {
                return ! empty($mapa[$id]);
            }));
        }

        $res = [];
        foreach ($ids as $id) {
            if (! in_array($id, $validas, true)) {
                $res[] = ['etapa_id' => $id, 'ok' => false, 'idOs' => null, 'erro' => 'Etapa nao pertence ao projeto'];
                continue;
            }
            if (! $forcar && self::temOsDeExecucao($osPorEtapa[$id] ?? [])) {
                $res[] = ['etapa_id' => $id, 'ok' => false, 'idOs' => null, 'erro' => 'Etapa ja tem OS'];
                continue;
            }
            $r = $this->gerarOsDaEtapa($id, $opcoes);
            $res[] = ['etapa_id' => $id, 'ok' => $r['ok'], 'idOs' => $r['idOs'], 'erro' => $r['erro']];
        }
        return $res;
    }
}
