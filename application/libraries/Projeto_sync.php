<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Sincroniza o projeto (obras/etapas) com as mudancas de status das OS (ADR-015).
 *
 * A decisao e pura (decidir) para ser testada sem banco; aposMudarStatusOs aplica.
 */
class Projeto_sync
{
    public const ETAPA_PENDENTE = 'pendente';
    public const ETAPA_ANDAMENTO = 'em_andamento';
    public const ETAPA_CONCLUIDA = 'concluida';
    public const OBRA_PLANEJAMENTO = 'planejamento';
    public const OBRA_EXECUCAO = 'em_execucao';
    public const PONTO_FINAL = 'testado';

    private const OS_FINAIS = ['Finalizado', 'Faturado'];

    /**
     * Peso dos status do ponto, igual a Obra_pontos_model::statusLista() (duplicado
     * aqui so para esta funcao continuar pura/sem banco e testavel isoladamente).
     */
    private const PESO_STATUS_PONTO = [
        'previsto' => 0, 'infra' => 20, 'cabeado' => 40,
        'instalado' => 70, 'configurado' => 90, 'testado' => 100,
    ];

    /** @var CI_Controller */
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /**
     * Decide o que fazer, sem tocar no banco.
     *
     * @param string[] $statusOsDaEtapa status de todas as OS da etapa (inclui a atual)
     */
    public static function decidir(string $novo, array $statusOsDaEtapa, int $pontosPendentes): array
    {
        $r = ['iniciar' => false, 'concluir_etapa' => false, 'pendencia_pontos' => false];

        if ($novo === 'Em Andamento') {
            $r['iniciar'] = true;
            return $r;
        }

        if (in_array($novo, self::OS_FINAIS, true)) {
            $ativas = array_values(array_filter($statusOsDaEtapa, function ($s) {
                return $s !== 'Cancelado';
            }));
            if ($ativas === []) {
                return $r;
            }
            foreach ($ativas as $s) {
                if (! in_array($s, self::OS_FINAIS, true)) {
                    return $r;
                }
            }
            if ($pontosPendentes > 0) {
                $r['pendencia_pontos'] = true;
            } else {
                $r['concluir_etapa'] = true;
            }
        }

        return $r;
    }

    /** Horas (2 casas) entre entrada e saida; 0 se faltar data ou saida <= entrada. */
    public static function horasEntre($entrada, $saida): float
    {
        if (! $entrada || ! $saida) {
            return 0.0;
        }
        $ini = strtotime((string) $entrada);
        $fim = strtotime((string) $saida);
        if ($ini === false || $fim === false || $fim <= $ini) {
            return 0.0;
        }
        return round(($fim - $ini) / 3600, 2);
    }

    /** Proximo status do ponto: avanca para $alvo, mas nunca recua se ja estiver mais adiante. */
    public static function decidirPontoNovoStatus(string $atual, string $alvo): string
    {
        if (! isset(self::PESO_STATUS_PONTO[$atual]) || ! isset(self::PESO_STATUS_PONTO[$alvo])) {
            return $atual; // status desconhecido: nao arrisca mudar
        }
        return (self::PESO_STATUS_PONTO[$alvo] > self::PESO_STATUS_PONTO[$atual]) ? $alvo : $atual;
    }

    /**
     * De onde sai o material lancado numa OS vinculada: 'obra' (baixa do almoxarifado
     * do projeto, sem tocar o estoque geral) quando o vinculo fatura no projeto;
     * senao 'estoque' (comportamento atual, estoque geral).
     */
    public static function decidirOrigemMaterial($vinculo): string
    {
        if (! $vinculo) {
            return 'estoque';
        }
        $fatura = is_array($vinculo) ? ($vinculo['fatura_no_projeto'] ?? 0) : ($vinculo->fatura_no_projeto ?? 0);
        return ((int) $fatura === 1) ? 'obra' : 'estoque';
    }

    /** Reage a mudanca de status da OS; devolve o que foi feito. */
    public function aposMudarStatusOs($idOs, $anterior, $novo): array
    {
        $acoes = [];
        $db = $this->ci->db;

        if (! $db->table_exists('obra_os')) {
            return $acoes;
        }
        $vinculo = $db->where('os_id', (int) $idOs)->get('obra_os')->row();
        if (! $vinculo) {
            return $acoes;
        }

        $obraId = (int) $vinculo->obra_id;
        $etapaId = $vinculo->etapa_id ? (int) $vinculo->etapa_id : 0;
        $hoje = date('Y-m-d');

        $statusEtapa = [];
        $pendentes = 0;
        if (in_array($novo, self::OS_FINAIS, true) && $etapaId) {
            $rows = $db->select('os.status')->from('obra_os')
                ->join('os', 'os.idOs = obra_os.os_id')
                ->where('obra_os.etapa_id', $etapaId)->get()->result();
            foreach ($rows as $row) {
                $statusEtapa[] = $row->status;
            }
            if ($db->table_exists('obra_pontos')) {
                $pendentes = (int) $db->where('obra_id', $obraId)
                    ->where('status <>', self::PONTO_FINAL)->count_all_results('obra_pontos');
            }
        }

        if (in_array($novo, self::OS_FINAIS, true)) {
            $acoesPontos = $this->avancarPontosDaOs((int) $idOs, (string) $novo);
            $acoes = array_merge($acoes, $acoesPontos);
        }

        $d = self::decidir((string) $novo, $statusEtapa, $pendentes);

        if ($d['iniciar']) {
            if ($etapaId) {
                $etapa = $db->where('idEtapa', $etapaId)->get('obra_etapas')->row();
                if ($etapa && $etapa->status === self::ETAPA_PENDENTE) {
                    $upd = ['status' => self::ETAPA_ANDAMENTO];
                    if (empty($etapa->data_inicio_real)) {
                        $upd['data_inicio_real'] = $hoje;
                    }
                    $db->where('idEtapa', $etapaId)->update('obra_etapas', $upd);
                    $acoes[] = 'etapa_iniciada';
                }
            }
            $obra = $db->where('idObra', $obraId)->get('obras')->row();
            if ($obra && $obra->status === self::OBRA_PLANEJAMENTO) {
                $db->where('idObra', $obraId)->update('obras', ['status' => self::OBRA_EXECUCAO]);
                $acoes[] = 'projeto_iniciado';
            }
        }

        if ($d['concluir_etapa']) {
            $db->where('idEtapa', $etapaId)->update('obra_etapas', [
                'status' => self::ETAPA_CONCLUIDA,
                'percentual_concluido' => 100,
                'data_fim_real' => $hoje,
            ]);
            $this->ci->load->model('obras_model');
            $this->ci->obras_model->recalcularProgresso($obraId);
            $acoes[] = 'etapa_concluida';
        }

        if ($d['pendencia_pontos']) {
            log_message('info', 'Projeto ' . $obraId . ': OS ' . $idOs . ' fechou a etapa ' . $etapaId . ' mas ha ' . $pendentes . ' ponto(s) pendente(s); etapa nao concluida');
            $acoes[] = 'pendencia_pontos';
        }

        return $acoes;
    }

    /**
     * Avanca os pontos ligados a uma OS (obra_ponto_os) para 'instalado' quando a
     * OS finaliza, sem recuar quem ja estiver mais adiante. Grava historico com
     * os_id e recalcula o progresso dos pontos (ADR-015 fase 4).
     */
    public function avancarPontosDaOs(int $idOs, string $statusNovo): array
    {
        $acoes = [];
        if (! in_array($statusNovo, self::OS_FINAIS, true)) {
            return $acoes;
        }
        $db = $this->ci->db;
        if (! $db->table_exists('obra_ponto_os') || ! $db->table_exists('obra_pontos')) {
            return $acoes;
        }

        $this->ci->load->model('obra_pontos_model');
        $usuarioId = $this->ci->session->userdata('id_admin') ?: null;

        $rows = $db->select('obra_ponto_os.ponto_id, obra_pontos.obra_id, obra_pontos.status')
            ->from('obra_ponto_os')
            ->join('obra_pontos', 'obra_pontos.idPonto = obra_ponto_os.ponto_id')
            ->where('obra_ponto_os.os_id', $idOs)
            ->get()->result();

        foreach ($rows as $r) {
            $alvo = self::decidirPontoNovoStatus((string) $r->status, 'instalado');
            if ($alvo === $r->status) {
                continue; // ja esta no alvo ou mais adiante
            }
            $this->ci->obra_pontos_model->mudarStatus(
                (int) $r->ponto_id,
                (int) $r->obra_id,
                $alvo,
                $usuarioId,
                ['os_id' => $idOs]
            );
            $acoes[] = 'ponto_avancado:' . $r->ponto_id;
        }

        return $acoes;
    }

    /**
     * Lanca mao de obra em obra_apontamento a partir de um check-in de OS vinculada a
     * projeto. Idempotente: reabrir/editar o mesmo check-in atualiza o lancamento (a
     * tabela nao tem coluna de checkin_id, entao a identificacao usa os_id + a marca
     * 'checkin:<id>' gravada em observacao). Sem vinculo ou sem horas, nao faz nada.
     */
    public function lancarHorasDoCheckin(int $idCheckin): array
    {
        $db = $this->ci->db;
        if (! $db->table_exists('os_checkin')) {
            return ['status' => 'sem_checkin'];
        }
        $checkin = $db->where('idCheckin', $idCheckin)->get('os_checkin')->row();
        if (! $checkin || empty($checkin->data_entrada) || empty($checkin->data_saida)) {
            return ['status' => 'sem_checkin'];
        }

        $this->ci->load->model('obras_model');
        $vinculo = $this->ci->obras_model->getVinculoDaOs((int) $checkin->os_id);
        if (! $vinculo) {
            return ['status' => 'sem_vinculo'];
        }
        if (! $db->table_exists('obra_apontamento')) {
            return ['status' => 'sem_tabela'];
        }

        $horas = self::horasEntre($checkin->data_entrada, $checkin->data_saida);
        if ($horas <= 0) {
            return ['status' => 'sem_horas'];
        }

        $obraId = (int) $vinculo->obra_id;
        $etapaId = $vinculo->etapa_id ? (int) $vinculo->etapa_id : null;

        $custoHora = 0.0;
        if ($db->table_exists('configuracoes')) {
            $cfg = $db->where('config', 'custo_hora_tecnico')->get('configuracoes')->row();
            $custoHora = $cfg ? (float) $cfg->valor : 0.0;
        }
        $valor = round($horas * $custoHora, 2);

        $nome = null;
        if ($db->table_exists('usuarios')) {
            $u = $db->select('nome')->where('idUsuarios', (int) $checkin->usuarios_id)->get('usuarios')->row();
            $nome = $u ? $u->nome : null;
        }

        $marca = 'checkin:' . $idCheckin;
        $temOsId = $db->field_exists('os_id', 'obra_apontamento');
        $existente = null;
        if ($temOsId) {
            $existente = $db->where('obra_id', $obraId)
                ->where('os_id', (int) $checkin->os_id)
                ->where('observacao', $marca)
                ->get('obra_apontamento')->row();
        }

        $dados = [
            'obra_id' => $obraId,
            'colaborador_id' => (int) $checkin->usuarios_id ?: null,
            'nome' => $nome,
            'data' => date('Y-m-d', strtotime($checkin->data_saida)),
            'horas' => $horas,
            'diaria' => 0,
            'origem' => 'os',
            'valor' => $valor,
            'observacao' => $marca,
        ];
        if ($etapaId) {
            $dados['etapa_id'] = $etapaId;
        }
        if ($temOsId) {
            $dados['os_id'] = (int) $checkin->os_id;
        }

        if ($existente) {
            $db->where('idApontamento', $existente->idApontamento)->update('obra_apontamento', $dados);
            $acao = 'apontamento_atualizado';
        } else {
            $db->insert('obra_apontamento', $dados);
            $acao = 'apontamento_criado';
        }

        $this->ci->obras_model->recalcularCustoMaoObra($obraId);

        return ['status' => $acao, 'obra_id' => $obraId, 'horas' => $horas, 'valor' => $valor];
    }

    /**
     * Aplica o lancamento de um produto de OS vinculada no almoxarifame do projeto
     * (quando fatura_no_projeto=1): baixa/devolve obra_material_saldo e grava/ajusta
     * obra_consumo, sem tocar o estoque geral (quem decide isso e o controller, a
     * partir do 'origem' devolvido). Nao bloqueia por saldo insuficiente.
     *
     * @param string $acao 'adicionar' (consome $qtd), 'remover' (devolve $qtd e some
     *                      com o consumo lancado) ou 'ajustar' (consumo passa a ser
     *                      a quantidade $qtd, qualquer que fosse a anterior)
     */
    public function aplicarProdutoDaOs(int $idOs, int $produtoId, float $qtd, string $acao): array
    {
        $db = $this->ci->db;
        $this->ci->load->model('obras_model');
        $vinculo = $this->ci->obras_model->getVinculoDaOs($idOs);
        $origem = self::decidirOrigemMaterial($vinculo);
        if ($origem !== 'obra') {
            return ['origem' => 'estoque'];
        }
        if (! $db->table_exists('obra_consumo') || ! $db->table_exists('obra_material_saldo')) {
            return ['origem' => 'estoque'];
        }

        $obraId = (int) $vinculo->obra_id;
        $etapaId = $vinculo->etapa_id ? (int) $vinculo->etapa_id : null;
        $descricao = null;
        if ($db->table_exists('produtos')) {
            $p = $db->select('descricao')->where('idProdutos', $produtoId)->get('produtos')->row();
            $descricao = $p ? $p->descricao : null;
        }

        $this->ci->load->model('obra_material_model');
        $temOsId = $db->field_exists('os_id', 'obra_consumo');
        $existente = null;
        if ($temOsId) {
            $existente = $db->where('obra_id', $obraId)
                ->where('os_id', $idOs)
                ->where('tipo', 'material')
                ->where('referencia_id', $produtoId)
                ->get('obra_consumo')->row();
        }

        $usuarioId = $this->ci->session->userdata('id_admin') ?: null;
        $agora = date('Y-m-d H:i:s');
        $qtdAnterior = $existente ? (float) $existente->quantidade : 0.0;

        if ($acao === 'remover') {
            $delta = $qtdAnterior > 0 ? $qtdAnterior : $qtd; // devolve o que foi consumido
            $this->ci->obra_material_model->ajustarSaldoPorOs($obraId, $produtoId, $descricao, $delta);
            if ($existente) {
                $db->where('idConsumo', $existente->idConsumo)->delete('obra_consumo');
            }
            $this->ci->obras_model->recalcularCustoMaterialConsumo($obraId);

            return ['origem' => 'obra', 'acao' => 'devolvido', 'quantidade' => $delta];
        }

        // adicionar: soma ao que ja havia lancado para esse produto nessa OS.
        // ajustar: o novo total passa a ser $qtd (delta e a diferenca p/ o que estava lancado).
        $novaQtd = ($acao === 'adicionar') ? ($qtdAnterior + $qtd) : $qtd;
        $delta = $novaQtd - $qtdAnterior; // positivo = consome mais, negativo = devolve parte

        $this->ci->obra_material_model->ajustarSaldoPorOs($obraId, $produtoId, $descricao, -$delta);

        $dadosConsumo = [
            'obra_id' => $obraId,
            'tipo' => 'material',
            'referencia_id' => $produtoId,
            'descricao' => $descricao,
            'quantidade' => $novaQtd,
            'usuario_id' => $usuarioId,
            'data' => $agora,
        ];
        if ($temOsId) {
            $dadosConsumo['os_id'] = $idOs;
        }

        if ($existente) {
            $db->where('idConsumo', $existente->idConsumo)->update('obra_consumo', $dadosConsumo);
        } else {
            $db->insert('obra_consumo', $dadosConsumo);
        }

        $this->ci->obras_model->recalcularCustoMaterialConsumo($obraId);

        $saldo = $this->ci->obra_material_model->saldoDisponivel($obraId, $produtoId, $descricao);
        $aviso = ($saldo < 0)
            ? 'Saldo do almoxarifado do projeto ficou negativo (' . $saldo . ') para ' . ($descricao ?: ('produto #' . $produtoId)) . '.'
            : null;

        return ['origem' => 'obra', 'acao' => $acao, 'quantidade' => $novaQtd, 'saldo' => $saldo, 'aviso' => $aviso];
    }

    /**
     * Etapas que fechariam (todas as OS ativas finalizadas) mas o projeto tem
     * pontos pendentes (status != testado) — sugestao para o gestor concluir na
     * mao. Consumido pela tela do projeto (outro agente).
     *
     * @return array<int, array{etapa_id:int, etapa_nome:string, os_finalizadas:int, pontos_pendentes:int}>
     */
    public function etapasSugeridasParaConcluir(int $obraId): array
    {
        $out = [];
        $db = $this->ci->db;
        if (! $db->table_exists('obra_etapas') || ! $db->table_exists('obra_os') || ! $db->table_exists('obra_pontos')) {
            return $out;
        }

        $pendentes = (int) $db->where('obra_id', $obraId)
            ->where('status <>', self::PONTO_FINAL)->count_all_results('obra_pontos');
        if ($pendentes <= 0) {
            return $out;
        }

        $etapas = $db->where('obra_id', $obraId)->where('status !=', self::ETAPA_CONCLUIDA)
            ->get('obra_etapas')->result();

        foreach ($etapas as $etapa) {
            $statusOs = [];
            $rows = $db->select('os.status')->from('obra_os')
                ->join('os', 'os.idOs = obra_os.os_id')
                ->where('obra_os.etapa_id', (int) $etapa->idEtapa)->get()->result();
            foreach ($rows as $row) {
                $statusOs[] = $row->status;
            }
            $ativas = array_values(array_filter($statusOs, static function ($s) {
                return $s !== 'Cancelado';
            }));
            if ($ativas === []) {
                continue;
            }
            $finalizadas = array_values(array_filter($ativas, static function ($s) {
                return in_array($s, self::OS_FINAIS, true);
            }));
            if (count($finalizadas) === count($ativas)) {
                $out[] = [
                    'etapa_id' => (int) $etapa->idEtapa,
                    'etapa_nome' => $etapa->nome,
                    'os_finalizadas' => count($finalizadas),
                    'pontos_pendentes' => $pendentes,
                ];
            }
        }

        return $out;
    }
}
