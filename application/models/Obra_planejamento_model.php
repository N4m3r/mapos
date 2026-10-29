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

    /* ====================== Converter OS em projeto (fase 3) ====================== */

    /** Status da OS que podem virar projeto. */
    public static function statusConversao()
    {
        return ['Aprovado', 'Aguardando Peças', 'Em Andamento'];
    }

    /** Pesos (%) por subtotal, com 2 casas e soma exata de 100 (a diferenca vai para o ultimo). */
    public static function distribuirPesos(array $subtotais)
    {
        $n = count($subtotais);
        if ($n === 0) {
            return [];
        }
        $vals = array_map(function ($v) {
            return max(0.0, (float) $v);
        }, array_values($subtotais));
        $total = array_sum($vals);
        $pesos = [];
        $soma = 0.0;
        foreach ($vals as $i => $v) {
            if ($i === $n - 1) {
                $pesos[] = round(100 - $soma, 2);
                break;
            }
            $p = $total > 0 ? round($v / $total * 100, 2) : round(100 / $n, 2);
            $pesos[] = $p;
            $soma = round($soma + $p, 2);
        }
        return $pesos;
    }

    /** Tipo de ponto sugerido pela descricao do produto; null para cabo, conector, caixa etc. */
    public static function classificarProduto($descricao)
    {
        $t = strtolower(strtr((string) $descricao, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c',
            'Á' => 'a', 'À' => 'a', 'Â' => 'a', 'Ã' => 'a', 'É' => 'e', 'Ê' => 'e', 'Í' => 'i',
            'Ó' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ú' => 'u', 'Ç' => 'c',
        ]));
        $t = trim(preg_replace('/\s+/', ' ', $t));
        if ($t === '') {
            return null;
        }
        // acessorio no inicio da descricao nao e ponto ("Fonte para camera", "Suporte de camera")
        $primeira = strtok($t, ' ');
        $acessorios = ['cabo', 'conector', 'caixa', 'fonte', 'suporte', 'patch', 'fita', 'parafuso', 'bucha',
            'eletroduto', 'eletrocalha', 'espelho', 'gabinete', 'balun', 'abracadeira', 'canaleta', 'terminal',
            'hd', 'cartao', 'adaptador', 'filtro', 'protetor', 'tubo', 'curva', 'luva', 'organizador'];
        if (in_array($primeira, $acessorios, true)) {
            return null;
        }
        $regras = [
            'nvr' => ['nvr', 'dvr', 'gravador'],
            'camera' => ['camera', 'cam ip', 'dome', 'bullet', 'ptz'],
            'leitor_acesso' => ['leitor', 'controladora', 'biometri', 'facial'],
            'fechadura' => ['fechadura', 'eletroima', 'eletro-ima', 'trava eletr'],
            'botoeira' => ['botoeira', 'botao de saida'],
            'interfone' => ['interfone', 'video porteiro', 'videoporteiro', 'porteiro'],
            'sensor' => ['sensor', 'infravermelho'],
            'ap_wifi' => ['access point', 'ponto de acesso', 'roteador wifi', 'ap wifi', 'wi-fi'],
            'switch' => ['switch'],
            'rack' => ['rack'],
            'ponto_rede' => ['ponto de rede', 'keystone', 'rj45', 'rj-45', 'tomada de rede'],
        ];
        foreach ($regras as $tipo => $palavras) {
            foreach ($palavras as $p) {
                if (strpos($t, $p) !== false) {
                    return $tipo;
                }
            }
        }
        return null;
    }

    /**
     * Etapas do projeto a partir dos servicos e produtos da OS.
     * servicos: [{servicos_id, nome, quantidade, preco, subtotal}], produtos: [{produtos_id, descricao, quantidade, preco}].
     * Retorna [{codigo_eap, nome, peso_percentual, valor_previsto, itens:[{tipo, referencia_id, quantidade, preco}]}].
     */
    public static function montarEtapasDosServicos(array $servicos, array $produtos)
    {
        $itensProdutos = [];
        $valorProdutos = 0.0;
        foreach ($produtos as $p) {
            $p = (array) $p;
            $q = (float) ($p['quantidade'] ?? 0);
            $itensProdutos[] = ['tipo' => 'produto', 'referencia_id' => (int) ($p['produtos_id'] ?? 0), 'quantidade' => $q, 'preco' => (float) ($p['preco'] ?? 0)];
            $valorProdutos += self::subtotal($q, $p['preco'] ?? 0);
        }

        $etapas = [];
        if (! $servicos) {
            if ($itensProdutos) {
                $etapas[] = ['codigo_eap' => '1', 'nome' => 'Execucao', 'peso_percentual' => 100.0, 'valor_previsto' => round($valorProdutos, 2), 'itens' => $itensProdutos];
            }
            return $etapas;
        }

        $subs = [];
        foreach (array_values($servicos) as $s) {
            $s = (array) $s;
            $subs[] = isset($s['subtotal']) ? (float) $s['subtotal'] : self::subtotal($s['quantidade'] ?? 0, $s['preco'] ?? 0);
        }
        $pesos = self::distribuirPesos($subs);
        $n = 0;
        foreach (array_values($servicos) as $i => $s) {
            $s = (array) $s;
            $n++;
            $nome = trim((string) ($s['nome'] ?? ''));
            $etapas[] = [
                'codigo_eap' => (string) $n,
                'nome' => mb_substr($nome !== '' ? $nome : 'Servico ' . $n, 0, 150),
                'peso_percentual' => $pesos[$i],
                'valor_previsto' => round($subs[$i], 2),
                'itens' => [['tipo' => 'servico', 'referencia_id' => (int) ($s['servicos_id'] ?? 0), 'quantidade' => (float) (($s['quantidade'] ?? 0) ?: 1), 'preco' => (float) ($s['preco'] ?? 0)]],
            ];
        }
        if ($itensProdutos) {
            $etapas[] = ['codigo_eap' => (string) ($n + 1), 'nome' => 'Fornecimento de materiais', 'peso_percentual' => 0.0, 'valor_previsto' => round($valorProdutos, 2), 'itens' => $itensProdutos];
        }
        return $etapas;
    }

    /* ====================== Converter OS em projeto (banco) ====================== */

    /** [servicos, produtos] da OS no formato de montarEtapasDosServicos. */
    private function itensDaOs($osId)
    {
        $servicos = [];
        $rows = $this->db->select('servicos_os.servicos_id, servicos_os.quantidade, servicos_os.preco, servicos_os.subTotal, servicos.nome, servicos_os.servico, servicos.preco AS preco_cat')
            ->from('servicos_os')->join('servicos', 'servicos.idServicos = servicos_os.servicos_id', 'left')
            ->where('servicos_os.os_id', (int) $osId)->order_by('servicos_os.idServicos_os')->get()->result();
        foreach ($rows as $r) {
            $preco = (float) ($r->preco ?: $r->preco_cat);
            $qtd = (float) ($r->quantidade ?: 1);
            $servicos[] = [
                'servicos_id' => (int) $r->servicos_id,
                'nome' => $r->nome ?: $r->servico,
                'quantidade' => $qtd,
                'preco' => $preco,
                'subtotal' => (float) $r->subTotal > 0 ? (float) $r->subTotal : self::subtotal($qtd, $preco),
            ];
        }
        $produtos = [];
        $rows = $this->db->select('produtos_os.produtos_id, produtos_os.quantidade, produtos_os.preco, produtos.descricao')
            ->from('produtos_os')->join('produtos', 'produtos.idProdutos = produtos_os.produtos_id', 'left')
            ->where('produtos_os.os_id', (int) $osId)->order_by('produtos_os.idProdutos_os')->get()->result();
        foreach ($rows as $r) {
            $produtos[] = [
                'produtos_id' => (int) $r->produtos_id,
                'descricao' => (string) $r->descricao,
                'quantidade' => (float) $r->quantidade,
                'preco' => (float) $r->preco,
            ];
        }
        return [$servicos, $produtos];
    }

    /** Valida a OS para conversao; devolve [os|null, erro|null]. */
    private function validarConversao($osId)
    {
        $os = $this->db->select('os.*, clientes.nomeCliente')->from('os')
            ->join('clientes', 'clientes.idClientes = os.clientes_id', 'left')
            ->where('os.idOs', (int) $osId)->get()->row();
        if (! $os) {
            return [null, 'OS nao encontrada'];
        }
        if (! in_array($os->status, self::statusConversao(), true)) {
            if (in_array($os->status, ['Orçamento', 'Aberto'], true)) {
                return [null, 'Aprove o orcamento antes de converter a OS em projeto'];
            }
            return [null, 'O status "' . $os->status . '" nao permite converter a OS em projeto'];
        }
        if (! $this->db->table_exists('obra_os') || ! $this->db->table_exists('obra_etapa_itens')) {
            return [null, 'Recurso indisponivel neste ambiente (migracao pendente)'];
        }
        if ($this->db->where('os_id', (int) $osId)->count_all_results('obra_os')) {
            return [null, 'A OS ja esta vinculada a um projeto'];
        }
        if (empty($os->clientes_id)) {
            return [null, 'OS sem cliente'];
        }
        return [$os, null];
    }

    /** Previa da conversao (etapas com pesos e produtos com tipo de ponto sugerido). */
    public function previaConversao($osId)
    {
        $osId = (int) $osId;
        list($os, $erro) = $this->validarConversao($osId);
        if ($erro) {
            return ['ok' => false, 'erro' => $erro];
        }
        list($servicos, $produtos) = $this->itensDaOs($osId);
        $prods = [];
        foreach ($produtos as $p) {
            $prods[] = [
                'produtos_id' => $p['produtos_id'],
                'descricao' => $p['descricao'],
                'quantidade' => $p['quantidade'],
                'tipo' => self::classificarProduto($p['descricao']),
            ];
        }
        $etapas = array_map(function ($e) {
            unset($e['itens']);
            return $e;
        }, self::montarEtapasDosServicos($servicos, $produtos));
        return ['ok' => true, 'etapas' => $etapas, 'produtos' => $prods];
    }

    /** Total da OS sem desconto (servicos + produtos). */
    private function valorOsSemDesconto($osId)
    {
        $this->load->model('os_model');
        $v = $this->os_model->valorTotalOS($osId);
        return round($v['totalServico'] + $v['totalProdutos'], 2);
    }

    /**
     * Converte OS aprovada em projeto (ADR-015 fase 3). Retorna ['ok','idObra','erro','resumo'].
     * opcoes: usuario_id, nome, tipo_obra, data_inicio_prevista, data_fim_prevista,
     * etapas ('servicos'|'padrao'), gerar_pontos, pontos_por_produto [produtos_id => tipo].
     * Nao muda o status da OS nem mexe em estoque.
     */
    public function converterOsEmProjeto($osId, array $opcoes)
    {
        $osId = (int) $osId;
        $resumo = ['etapas' => 0, 'itens' => 0, 'pontos' => 0];
        $falha = function ($msg) use (&$resumo) {
            return ['ok' => false, 'idObra' => null, 'erro' => $msg, 'resumo' => ['etapas' => 0, 'itens' => 0, 'pontos' => 0]];
        };
        $usuarioId = (int) ($opcoes['usuario_id'] ?? 0);
        if (! $usuarioId) {
            return $falha('Usuario nao informado');
        }
        list($os, $erro) = $this->validarConversao($osId);
        if ($erro) {
            return $falha($erro);
        }
        list($servicos, $produtos) = $this->itensDaOs($osId);
        if (! $servicos && ! $produtos) {
            return $falha('A OS nao tem servicos nem produtos para converter');
        }

        $nome = trim((string) ($opcoes['nome'] ?? ''));
        if ($nome === '') {
            $nome = 'Projeto - OS #' . $osId . ' - ' . trim((string) $os->nomeCliente);
        }
        $nome = mb_substr($nome, 0, 150);
        $tipoObra = mb_substr(trim((string) ($opcoes['tipo_obra'] ?? '')), 0, 60);
        $modo = ($opcoes['etapas'] ?? 'servicos') === 'padrao' ? 'padrao' : 'servicos';
        $data = function ($v) {
            $v = trim((string) $v);
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
        };
        $ini = $data($opcoes['data_inicio_prevista'] ?? '');
        $fim = $data($opcoes['data_fim_prevista'] ?? '');
        if ($ini && $fim && $fim < $ini) {
            return $falha('A data final nao pode ser anterior a inicial');
        }

        // valor da OS ja com desconto (mesmo criterio da tela da OS)
        $valor = (float) $os->valor_desconto != 0 ? (float) $os->valor_desconto : $this->valorOsSemDesconto($osId);

        $this->load->model('obras_model');
        $this->load->model('obra_pontos_model');
        $this->db->trans_start();

        $idObra = (int) $this->obras_model->add([
            'nome' => $nome,
            'clientes_id' => (int) $os->clientes_id,
            'responsavel_id' => $usuarioId,
            'tipo_obra' => $tipoObra !== '' ? $tipoObra : null,
            'valor_contrato' => round($valor, 2),
            'data_inicio_prevista' => $ini,
            'data_fim_prevista' => $fim,
            'status' => 'planejamento',
            'observacoes' => 'Criado a partir da OS #' . $osId,
            'data_cadastro' => date('Y-m-d H:i:s'),
        ]);
        $erro = $idObra ? null : 'Erro ao criar o projeto';

        // etapas e itens previstos
        if (! $erro) {
            $itensTodos = [];
            foreach (self::montarEtapasDosServicos($servicos, $produtos) as $e) {
                foreach ($e['itens'] as $i) {
                    $itensTodos[] = $i;
                }
                if ($modo !== 'servicos') {
                    continue;
                }
                $etapaId = (int) $this->obras_model->addEtapa([
                    'obra_id' => $idObra,
                    'codigo_eap' => $e['codigo_eap'],
                    'nome' => $e['nome'],
                    'ordem' => (int) $e['codigo_eap'],
                    'peso_percentual' => $e['peso_percentual'],
                    'valor_previsto' => $e['valor_previsto'],
                    'status' => 'pendente',
                ]);
                if (! $etapaId) {
                    $erro = 'Erro ao criar etapa';
                    break;
                }
                $resumo['etapas']++;
                foreach ($e['itens'] as $i) {
                    if (! $this->addItem($idObra, $etapaId, $i['tipo'], $i['referencia_id'], $i['quantidade'], $i['preco'])) {
                        $erro = 'Erro ao gravar item previsto';
                        break 2;
                    }
                    $resumo['itens']++;
                }
            }
            if (! $erro && $modo === 'padrao') {
                $resumo['etapas'] = (int) $this->obra_pontos_model->aplicarEtapasPadrao($idObra, $tipoObra);
                $primeira = $this->db->where('obra_id', $idObra)->order_by('ordem')->order_by('idEtapa')->limit(1)->get('obra_etapas')->row();
                if (! $primeira) {
                    $erro = 'Erro ao criar as etapas padrao';
                } else {
                    foreach ($itensTodos as $i) {
                        if (! $this->addItem($idObra, $primeira->idEtapa, $i['tipo'], $i['referencia_id'], $i['quantidade'], $i['preco'])) {
                            $erro = 'Erro ao gravar item previsto';
                            break;
                        }
                        $resumo['itens']++;
                    }
                }
            }
        }

        // pontos pelos produtos classificados
        if (! $erro && ! empty($opcoes['gerar_pontos'])) {
            $tipos = Obra_pontos_model::tipos();
            $over = (array) ($opcoes['pontos_por_produto'] ?? []);
            $contador = [];
            foreach ($produtos as $p) {
                $tipo = array_key_exists($p['produtos_id'], $over) ? (string) $over[$p['produtos_id']] : self::classificarProduto($p['descricao']);
                if (! $tipo || ! isset($tipos[$tipo])) {
                    continue;
                }
                $qtd = min(500, (int) round($p['quantidade']));
                if ($qtd < 1) {
                    continue;
                }
                $inicio = ($contador[$tipo] ?? 0) + 1;
                $resumo['pontos'] += $this->obra_pontos_model->gerarLote($idObra, $tipo, $tipos[$tipo]['prefixo'], $inicio, $qtd);
                $contador[$tipo] = $inicio + $qtd - 1;
            }
        }

        // vinculo de origem; a OS segue com o fluxo normal (sem mudar status)
        if (! $erro && ! $this->obras_model->vincularOs($idObra, $osId, null, 'origem', 1)) {
            $erro = 'Nao foi possivel vincular a OS ao projeto';
        }

        if ($erro) {
            $this->db->trans_rollback();
            return $falha($erro);
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            return $falha('Erro ao gravar o projeto');
        }
        return ['ok' => true, 'idObra' => $idObra, 'erro' => null, 'resumo' => $resumo];
    }
}
