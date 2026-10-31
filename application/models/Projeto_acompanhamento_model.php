<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Acompanhamento de projetos (ADR-012): relatório por período e painel de saúde.
 *
 * Somente leitura. periodo() e saude() são puras (sem banco); relatorio() e
 * painel() devolvem vazio se as tabelas do módulo ainda não existem.
 */
class Projeto_acompanhamento_model extends CI_Model
{
    private const MESES = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
        7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];

    private const ROTULO_NIVEL = [
        'ok' => 'Em dia', 'atencao' => 'Atenção', 'critico' => 'Crítico', 'parado' => 'Paralisado',
    ];

    /* ========================= Funções puras ========================= */

    /** Converte Y-m-d em DateTime (à meia-noite). Null se vazia ou inválida. */
    private static function data(?string $valor): ?DateTime
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        $d = DateTime::createFromFormat('!Y-m-d', $valor);

        return ($d !== false && $d->format('Y-m-d') === $valor) ? $d : null;
    }

    /** Dias de $de até $ate (positivo se $ate é depois). */
    private static function dias(string $de, string $ate): int
    {
        return (int) self::data($de)->diff(self::data($ate))->format('%r%a');
    }

    /** Converte Y-m-d H:i:s em DateTime (UTC, sem conversão de fuso). Null se vazia ou inválida. */
    private static function dataHora($valor): ?DateTime
    {
        if (! is_string($valor) || $valor === '') {
            return null;
        }
        $d = DateTime::createFromFormat('!Y-m-d H:i:s', $valor, new DateTimeZone('UTC'));

        return ($d !== false && $d->format('Y-m-d H:i:s') === $valor) ? $d : null;
    }

    /**
     * Média, em horas (1 casa), dos intervalos [inicio, fim] em Y-m-d H:i:s (ADR-014).
     * Ignora pares inválidos ou com fim antes do início. Null se não sobrar nenhum.
     */
    public static function tempoMedioHoras(array $pares): ?float
    {
        $horas = [];
        foreach ($pares as $par) {
            if (! is_array($par)) {
                continue;
            }
            $par = array_values($par);
            if (count($par) < 2) {
                continue;
            }
            $ini = self::dataHora($par[0]);
            $fim = self::dataHora($par[1]);
            if ($ini === null || $fim === null || $fim < $ini) {
                continue;
            }
            $horas[] = ($fim->getTimestamp() - $ini->getTimestamp()) / 3600;
        }
        if (! $horas) {
            return null;
        }

        return round(array_sum($horas) / count($horas), 1);
    }

    /** Horas entre $inicio (Y-m-d H:i:s) e agora, 1 casa. Null se vazio ou inválido. */
    private static function horasDesde(?string $inicio): ?float
    {
        $ini = self::dataHora($inicio);
        $agora = self::dataHora(date('Y-m-d H:i:s'));
        if ($ini === null || $agora === null) {
            return null;
        }

        return round(max(0, $agora->getTimestamp() - $ini->getTimestamp()) / 3600, 1);
    }

    /**
     * Período (diário, semanal ou mensal) em torno de uma data de referência.
     * Tipo inválido vira semanal; data inválida vira hoje. Semana: segunda a domingo.
     * anterior/proximo: no semanal e no mensal são o início do período vizinho.
     */
    public static function periodo(string $tipo, string $dataRef): array
    {
        if (! in_array($tipo, ['diario', 'semanal', 'mensal'], true)) {
            $tipo = 'semanal';
        }
        $ref = self::data($dataRef) ?: new DateTime('today');

        if ($tipo === 'diario') {
            $ini = clone $ref;
            $fim = clone $ref;
            $rotulo = $ref->format('d/m/Y');
            $ant = (clone $ref)->modify('-1 day');
            $prox = (clone $ref)->modify('+1 day');
        } elseif ($tipo === 'semanal') {
            $ini = (clone $ref)->modify('-' . ((int) $ref->format('N') - 1) . ' days');
            $fim = (clone $ini)->modify('+6 days');
            $rotulo = 'Semana de ' . $ini->format('d/m') . ' a ' . $fim->format('d/m/Y');
            $ant = (clone $ini)->modify('-7 days');
            $prox = (clone $ini)->modify('+7 days');
        } else {
            $ini = new DateTime($ref->format('Y-m-01'));
            $fim = new DateTime($ref->format('Y-m-t'));
            $rotulo = self::MESES[(int) $ref->format('n')] . '/' . $ref->format('Y');
            $ant = (clone $ini)->modify('-1 month');
            $prox = (clone $ini)->modify('+1 month');
        }

        return [
            'tipo' => $tipo,
            'inicio' => $ini->format('Y-m-d'),
            'fim' => $fim->format('Y-m-d'),
            'rotulo' => $rotulo,
            'anterior' => $ant->format('Y-m-d'),
            'proximo' => $prox->format('Y-m-d'),
        ];
    }

    /** Semáforo de saúde de um projeto a partir dos fatos (ver ADR-012). */
    public static function saude(array $f, string $hoje): array
    {
        $status = (string) ($f['status'] ?? '');
        $pct = (float) ($f['percentual_concluido'] ?? 0);
        $ini = $f['data_inicio_prevista'] ?? null;
        $fimPrev = $f['data_fim_prevista'] ?? null;
        $ultimo = $f['ultimo_registro'] ?? null;

        // Percentual de prazo decorrido (0 a 100); null sem as duas datas
        $esperado = null;
        if (self::data($ini) && self::data($fimPrev)) {
            $total = self::dias($ini, $fimPrev);
            $passados = self::dias($ini, $hoje);
            if ($total <= 0) {
                $esperado = $passados >= 0 ? 100.0 : 0.0;
            } else {
                $esperado = round(max(0, min(100, $passados / $total * 100)), 1);
            }
        }

        $neutros = ['concluida' => 'Concluído', 'entregue' => 'Entregue', 'cancelada' => 'Cancelado'];
        if (isset($neutros[$status])) {
            return ['nivel' => 'neutro', 'rotulo' => $neutros[$status], 'motivos' => [], 'esperado' => $esperado];
        }
        if ($status === 'paralisada') {
            return ['nivel' => 'parado', 'rotulo' => 'Paralisado', 'motivos' => ['Projeto paralisado'], 'esperado' => $esperado];
        }
        if ($status === 'planejamento') {
            if (self::data($ini) && $ini < $hoje) {
                return [
                    'nivel' => 'atencao',
                    'rotulo' => self::ROTULO_NIVEL['atencao'],
                    'motivos' => ['Início previsto em ' . self::data($ini)->format('d/m') . ' não aconteceu'],
                    'esperado' => $esperado,
                ];
            }

            return ['nivel' => 'ok', 'rotulo' => 'Em planejamento', 'motivos' => [], 'esperado' => $esperado];
        }

        // Em execução (e qualquer outro status): soma os motivos, vale o pior nível.
        $ordem = ['ok' => 0, 'atencao' => 1, 'critico' => 2];
        $nivel = 'ok';
        $motivos = [];
        $add = function (string $n, string $motivo) use (&$nivel, &$motivos, $ordem) {
            $motivos[] = $motivo;
            if ($ordem[$n] > $ordem[$nivel]) {
                $nivel = $n;
            }
        };

        if (self::data($fimPrev) && $fimPrev < $hoje) {
            $add('critico', 'Prazo vencido há ' . self::dias($fimPrev, $hoje) . ' dias');
        }
        if ($esperado !== null) {
            $gap = $esperado - $pct;
            if ($gap >= 20) {
                $add('critico', 'Avanço ' . (int) round($gap) . ' pontos abaixo do previsto');
            } elseif ($gap >= 10) {
                $add('atencao', 'Avanço ' . (int) round($gap) . ' pontos abaixo do previsto');
            }
        }
        if (! self::data($ultimo)) {
            $add('critico', 'Nenhum registro de execução');
        } else {
            $sem = self::dias($ultimo, $hoje);
            if ($sem > 7) {
                $add('critico', 'Sem registro há ' . $sem . ' dias');
            } elseif ($sem > 2) {
                $add('atencao', 'Sem registro há ' . $sem . ' dias');
            }
        }
        $et = (int) ($f['etapas_atrasadas'] ?? 0);
        if ($et > 0) {
            $add('atencao', $et . ' etapa(s) atrasada(s)');
        }
        $oc = (int) ($f['ocorrencias_7d'] ?? 0);
        if ($oc > 0) {
            $add('atencao', $oc . ' ocorrência(s) nos últimos 7 dias');
        }

        return [
            'nivel' => $nivel,
            'rotulo' => self::ROTULO_NIVEL[$nivel],
            'motivos' => $motivos,
            'esperado' => $esperado,
        ];
    }

    /* ============================ Relatório ============================ */

    /** Soma quantidades por descrição, em ordem alfabética. */
    private function somarItens(array $itens): array
    {
        $soma = [];
        foreach ($itens as $i) {
            $k = (string) $i['descricao'];
            $soma[$k] = ($soma[$k] ?? 0) + $i['quantidade'];
        }
        ksort($soma, SORT_NATURAL | SORT_FLAG_CASE);
        $out = [];
        foreach ($soma as $descricao => $q) {
            $out[] = ['descricao' => (string) $descricao, 'quantidade' => (float) $q];
        }

        return $out;
    }

    /** Carrega o model de pontos (ADR-013) se o arquivo existir. */
    private static function pontosDisponiveis(): bool
    {
        if (! class_exists('Obra_pontos_model', false)) {
            $arquivo = APPPATH . 'models/Obra_pontos_model.php';
            if (! is_file($arquivo)) {
                return false;
            }
            require_once $arquivo;
        }

        return class_exists('Obra_pontos_model', false);
    }

    /** Tipos de ponto: chave => ['rotulo', 'prefixo', 'icone']. Vazio sem o model. */
    private static function tiposPontos(): array
    {
        return self::pontosDisponiveis() ? Obra_pontos_model::tipos() : [];
    }

    /** Status de ponto: chave => ['rotulo', 'peso', 'acao', 'classe']. Vazio sem o model. */
    private static function statusPontos(): array
    {
        return self::pontosDisponiveis() ? Obra_pontos_model::statusLista() : [];
    }

    /** Resumo dos pontos (Obra_pontos_model::resumo), ou o resumo vazio sem o model. */
    private static function resumoPontos(array $pontos): array
    {
        if (self::pontosDisponiveis()) {
            return Obra_pontos_model::resumo($pontos);
        }

        return ['total' => 0, 'por_status' => [], 'por_tipo' => [], 'progresso' => null, 'cabo_m' => 0.0];
    }

    /** Maior data entre duas datas Y-m-d (null se as duas estiverem vazias). */
    private static function maiorData(?string $a, ?string $b): ?string
    {
        if (! $a) {
            return $b ?: null;
        }
        if (! $b) {
            return $a;
        }

        return $a >= $b ? $a : $b;
    }

    /**
     * Origem de uma atividade a partir do os_id (ADR-015 fase 4): sem OS é "Registro do
     * dia" (RDO); com OS é "OS #N", com link pronto para a tela da OS. Pura, sem banco.
     */
    public static function classificarOrigemAtividade($osId): array
    {
        $id = (int) ($osId ?? 0);
        if ($id > 0) {
            return ['origem' => 'os', 'rotulo' => 'OS #' . $id, 'os_id' => $id];
        }

        return ['origem' => 'rdo', 'rotulo' => 'Registro do dia', 'os_id' => null];
    }

    /** Avanços de ponto (histórico) do período, com filtros opcionais de projeto e técnico. */
    private function avancosPontos(string $inicio, string $fim, int $obraId, int $usuarioId): array
    {
        if (! $this->db->table_exists('obra_ponto_historico') || ! $this->db->table_exists('obra_pontos')) {
            return [];
        }
        $temEvento = $this->db->field_exists('evento', 'obra_ponto_historico');
        $temDestinatario = $this->db->field_exists('destinatario_id', 'obra_ponto_historico');
        $this->db->select('h.idHistorico, h.obra_id, h.ponto_id, h.usuario_id, h.status_anterior, h.status_novo, h.metragem_cabo, h.data, p.identificacao, p.tipo, p.local, usuarios.nome as usuario');
        if ($temEvento) {
            $this->db->select('h.evento');
        }
        if ($temDestinatario) {
            $this->db->select('dest.nome as destinatario');
        }
        $this->db->from('obra_ponto_historico h');
        $this->db->join('obra_pontos p', 'p.idPonto = h.ponto_id', 'left');
        $this->db->join('usuarios', 'usuarios.idUsuarios = h.usuario_id', 'left');
        if ($temDestinatario) {
            $this->db->join('usuarios dest', 'dest.idUsuarios = h.destinatario_id', 'left');
        }
        $this->db->where('h.data >=', $inicio . ' 00:00:00');
        $this->db->where('h.data <=', $fim . ' 23:59:59');
        if ($obraId) {
            $this->db->where('h.obra_id', $obraId);
        }
        if ($usuarioId) {
            $this->db->where('h.usuario_id', $usuarioId);
        }
        $this->db->order_by('h.data', 'asc');
        $this->db->order_by('h.idHistorico', 'asc');

        return $this->db->get()->result();
    }

    /** Um avanço de ponto no formato do relatório (ver ADR-013, parte D). */
    private static function formatarAvanco(object $h, array $statusLista, array $tipos): array
    {
        $de = (string) ($h->status_anterior ?? '');
        $para = (string) ($h->status_novo ?? '');
        $tipo = (string) ($h->tipo ?? '');

        return [
            'data' => (string) $h->data,
            'identificacao' => (string) ($h->identificacao ?? ''),
            'tipo' => $tipo,
            'tipo_rotulo' => $tipos[$tipo]['rotulo'] ?? $tipo,
            'local' => (string) ($h->local ?? ''),
            'de' => $de,
            'de_rotulo' => $statusLista[$de]['rotulo'] ?? ($de !== '' ? $de : '—'),
            'para' => $para,
            'para_rotulo' => $statusLista[$para]['rotulo'] ?? $para,
            'usuario' => (string) ($h->usuario ?? ''),
            'metragem_cabo' => (float) ($h->metragem_cabo ?? 0),
            'evento' => (string) ($h->evento ?? 'status'),
            'destinatario' => (string) ($h->destinatario ?? ''),
            'fotos' => 0,
        ];
    }

    /** Quantidade de fotos (obra_ponto_foto) por histórico, em lote. */
    private function fotosPorHistorico(array $historicoIds): array
    {
        $out = [];
        if (! $historicoIds || ! $this->db->table_exists('obra_ponto_foto') || ! $this->db->field_exists('historico_id', 'obra_ponto_foto')) {
            return $out;
        }
        $this->db->select('historico_id, COUNT(*) as n', false);
        $this->db->where_in('historico_id', array_values(array_unique($historicoIds)));
        $this->db->group_by('historico_id');
        foreach ($this->db->get('obra_ponto_foto')->result() as $f) {
            $out[(int) $f->historico_id] = (int) $f->n;
        }

        return $out;
    }

    /**
     * Tempo até configurar (ADR-014): para cada configuração do período (evento de status
     * que chegou em 'configurado'), o último 'instalado' anterior do mesmo ponto.
     * Recebe os avanços já filtrados. Devolve [['obra_id' => int, 'par' => [instalado, configurado]]].
     */
    private function paresConfiguracao(array $eventos, string $fim): array
    {
        if (! $this->db->table_exists('obra_ponto_historico')) {
            return [];
        }
        $cfgs = [];
        $pontoIds = [];
        foreach ($eventos as $ev) {
            if (($ev->evento ?? 'status') === 'status' && $ev->status_novo === 'configurado') {
                $cfgs[] = $ev;
                $pontoIds[(int) $ev->ponto_id] = true;
            }
        }
        if (! $cfgs) {
            return [];
        }

        $instalados = [];
        $this->db->select('idHistorico, ponto_id, data');
        $this->db->where_in('ponto_id', array_keys($pontoIds));
        $this->db->where('status_novo', 'instalado');
        if ($this->db->field_exists('evento', 'obra_ponto_historico')) {
            $this->db->where('evento', 'status');
        }
        $this->db->where('data <=', $fim . ' 23:59:59');
        $this->db->order_by('data', 'asc');
        $this->db->order_by('idHistorico', 'asc');
        foreach ($this->db->get('obra_ponto_historico')->result() as $h) {
            $instalados[(int) $h->ponto_id][] = [(string) $h->data, (int) $h->idHistorico];
        }

        $saida = [];
        foreach ($cfgs as $c) {
            $cData = (string) $c->data;
            $cId = (int) $c->idHistorico;
            $ini = null;
            foreach ($instalados[(int) $c->ponto_id] ?? [] as $ins) {
                // Lista em ordem crescente: o último anterior ao configurado é o que vale
                if ($ins[0] < $cData || ($ins[0] === $cData && $ins[1] < $cId)) {
                    $ini = $ins[0];
                } else {
                    break;
                }
            }
            if ($ini !== null) {
                $saida[] = ['obra_id' => (int) $c->obra_id, 'par' => [$ini, $cData]];
            }
        }

        return $saida;
    }

    /** Entrada de técnico no relatório (criada por RDO ou por avanço de ponto). */
    private static function tecnicoInicial(int $uid, string $nome): array
    {
        return ['usuario_id' => $uid, 'nome' => $nome, 'registros' => 0, 'avancos' => 0, 'obras' => [], 'datas' => [], 'ultimo' => null];
    }

    /* ===================== Atividade vinda de OS (ADR-015 fase 4) ===================== */

    /** obra_os existe, a tabela existe e tem a coluna os_id (instalação já passou pela migration da ponte). */
    private function temColunaOs(string $tabela): bool
    {
        return $this->db->table_exists('obra_os')
            && $this->db->table_exists($tabela)
            && $this->db->field_exists('os_id', $tabela);
    }

    /** Uma linha de atividade originada de OS, no mesmo formato de 'registros' do RDO (para caber na mesma tabela). */
    private static function linhaOs(int $osId, string $data, string $responsavel, string $descricao, int $usuarioId): array
    {
        $origem = self::classificarOrigemAtividade($osId);

        return [
            'idRdo' => 0,
            'numero' => $origem['rotulo'],
            'data' => $data,
            'responsavel' => $responsavel,
            'atividades' => $descricao,
            'ocorrencias' => '',
            'condicao' => '',
            'efetivo_total' => 0,
            'fotos' => [],
            'materiais' => [],
            'servicos' => [],
            'origem' => $origem['origem'],
            'os_id' => $osId,
            'usuario_id' => $usuarioId ?: null,
        ];
    }

    /**
     * Atividade vinda de OS vinculada a um projeto: check-in/check-out, mão de obra e
     * consumo lançados com os_id. $inicio/$fim nulos = sem limite de data (histórico
     * inteiro, usado na página do projeto). Devolve [obra_id => linhaOs[]]. Protegido por
     * table_exists/field_exists: sem a migration da ponte, devolve vazio.
     */
    private function atividadesOs(?string $inicio, ?string $fim, int $obraId, int $usuarioId): array
    {
        $porObra = [];
        if (! $this->db->table_exists('obra_os')) {
            return $porObra;
        }
        $iniDt = $inicio ? $inicio . ' 00:00:00' : null;
        $fimDt = $fim ? $fim . ' 23:59:59' : null;

        // Check-in / check-out da OS
        if ($this->db->table_exists('os_checkin')) {
            $this->db->select('obra_os.obra_id as obra_id, os_checkin.os_id, os_checkin.usuarios_id, os_checkin.data_entrada, os_checkin.data_saida, usuarios.nome as tecnico');
            $this->db->from('os_checkin');
            $this->db->join('obra_os', 'obra_os.os_id = os_checkin.os_id');
            $this->db->join('usuarios', 'usuarios.idUsuarios = os_checkin.usuarios_id', 'left');
            if ($obraId) {
                $this->db->where('obra_os.obra_id', $obraId);
            }
            if ($usuarioId) {
                $this->db->where('os_checkin.usuarios_id', $usuarioId);
            }
            $this->db->group_start();
            $this->db->where('os_checkin.data_entrada IS NOT NULL', null, false);
            if ($iniDt) {
                $this->db->where('os_checkin.data_entrada >=', $iniDt);
            }
            if ($fimDt) {
                $this->db->where('os_checkin.data_entrada <=', $fimDt);
            }
            $this->db->group_end();
            $this->db->or_group_start();
            $this->db->where('os_checkin.data_saida IS NOT NULL', null, false);
            if ($iniDt) {
                $this->db->where('os_checkin.data_saida >=', $iniDt);
            }
            if ($fimDt) {
                $this->db->where('os_checkin.data_saida <=', $fimDt);
            }
            $this->db->group_end();
            foreach ($this->db->get()->result() as $c) {
                $dataRef = (string) ($c->data_saida ?: $c->data_entrada);
                if ($dataRef === '') {
                    continue;
                }
                $acao = $c->data_saida
                    ? 'Check-in e check-out registrados na execução da OS'
                    : 'Check-in registrado na execução da OS';
                $porObra[(int) $c->obra_id][] = self::linhaOs(
                    (int) $c->os_id,
                    substr($dataRef, 0, 10),
                    (string) ($c->tecnico ?? ''),
                    $acao,
                    (int) ($c->usuarios_id ?? 0)
                );
            }
        }

        // Mão de obra lançada via OS
        if ($this->temColunaOs('obra_apontamento')) {
            $this->db->select('obra_id, os_id, nome, data, SUM(horas) as horas', false);
            $this->db->where('os_id IS NOT NULL', null, false);
            if ($obraId) {
                $this->db->where('obra_id', $obraId);
            }
            if ($iniDt) {
                $this->db->where('data >=', $inicio);
            }
            if ($fimDt) {
                $this->db->where('data <=', $fim);
            }
            $this->db->group_by(['obra_id', 'os_id', 'nome', 'data']);
            foreach ($this->db->get('obra_apontamento')->result() as $a) {
                $horas = number_format((float) $a->horas, 1, ',', '.');
                $porObra[(int) $a->obra_id][] = self::linhaOs(
                    (int) $a->os_id,
                    (string) $a->data,
                    (string) $a->nome,
                    $horas . ' h de mão de obra lançada(s) na OS',
                    0
                );
            }
        }

        // Consumo (material/serviço) lançado via OS
        if ($this->temColunaOs('obra_consumo')) {
            $this->db->select('obra_id, os_id, tipo, descricao, quantidade, data, usuario_id');
            $this->db->where('os_id IS NOT NULL', null, false);
            if ($obraId) {
                $this->db->where('obra_id', $obraId);
            }
            if ($iniDt) {
                $this->db->where('data >=', $iniDt);
            }
            if ($fimDt) {
                $this->db->where('data <=', $fimDt);
            }
            if ($usuarioId) {
                $this->db->where('usuario_id', $usuarioId);
            }
            $this->db->order_by('idConsumo', 'asc');
            $linhas = $this->db->get('obra_consumo')->result();
            $uids = array_values(array_unique(array_filter(array_map(function ($c) {
                return (int) ($c->usuario_id ?? 0);
            }, $linhas))));
            $nomes = [];
            if ($uids) {
                $this->db->select('idUsuarios, nome');
                $this->db->where_in('idUsuarios', $uids);
                foreach ($this->db->get('usuarios')->result() as $u) {
                    $nomes[(int) $u->idUsuarios] = (string) $u->nome;
                }
            }
            foreach ($linhas as $c) {
                $rotuloTipo = $c->tipo === 'servico' ? 'Serviço' : 'Material';
                $qtd = rtrim(rtrim(number_format((float) $c->quantidade, 2, ',', '.'), '0'), ',');
                $desc = $rotuloTipo . ' via OS: ' . $c->descricao . ' (' . $qtd . ')';
                $linha = self::linhaOs(
                    (int) $c->os_id,
                    substr((string) $c->data, 0, 10),
                    $nomes[(int) ($c->usuario_id ?? 0)] ?? '',
                    $desc,
                    (int) ($c->usuario_id ?? 0)
                );
                if ($c->tipo === 'servico') {
                    $linha['servicos'][] = ['descricao' => (string) $c->descricao, 'quantidade' => (float) $c->quantidade];
                } else {
                    $linha['materiais'][] = ['descricao' => (string) $c->descricao, 'quantidade' => (float) $c->quantidade];
                }
                $porObra[(int) $c->obra_id][] = $linha;
            }
        }

        return $porObra;
    }

    /**
     * Último dia de atividade (sem limite) e quantidade num intervalo (se informado) da
     * atividade vinda de OS (check-in/checkout, mão de obra e consumo com os_id), por
     * projeto. Mais leve que atividadesOs(): sem joins com usuários, para uso no painel
     * e na lista de projetos (muitos projetos de uma vez). Protegido do mesmo jeito.
     */
    private function atividadeOsPorObra(array $ids, ?string $ini = null, ?string $fim = null): array
    {
        $ultimo = [];
        $contagem = [];
        if (! $ids) {
            return ['ultimo' => $ultimo, 'contagem' => $contagem];
        }
        $somar = function (int $obraId, string $dia) use (&$ultimo, &$contagem) {
            $ultimo[$obraId] = self::maiorData($ultimo[$obraId] ?? null, $dia);
            $contagem[$obraId] = ($contagem[$obraId] ?? 0) + 1;
        };
        $dentro = function (string $dia) use ($ini, $fim) {
            if ($ini && $dia < $ini) {
                return false;
            }
            if ($fim && $dia > $fim) {
                return false;
            }

            return true;
        };

        if ($this->db->table_exists('obra_os') && $this->db->table_exists('os_checkin')) {
            $this->db->select('obra_os.obra_id as obra_id, os_checkin.data_entrada, os_checkin.data_saida', false);
            $this->db->from('os_checkin');
            $this->db->join('obra_os', 'obra_os.os_id = os_checkin.os_id');
            $this->db->where_in('obra_os.obra_id', $ids);
            foreach ($this->db->get()->result() as $r) {
                foreach ([$r->data_entrada, $r->data_saida] as $dt) {
                    if (! $dt) {
                        continue;
                    }
                    $dia = substr((string) $dt, 0, 10);
                    if ($dentro($dia)) {
                        $somar((int) $r->obra_id, $dia);
                    }
                }
            }
        }
        foreach (['obra_apontamento', 'obra_consumo'] as $tabela) {
            if (! $this->temColunaOs($tabela)) {
                continue;
            }
            $this->db->select('obra_id, data');
            $this->db->where_in('obra_id', $ids);
            $this->db->where('os_id IS NOT NULL', null, false);
            foreach ($this->db->get($tabela)->result() as $r) {
                $dia = substr((string) $r->data, 0, 10);
                if ($dentro($dia)) {
                    $somar((int) $r->obra_id, $dia);
                }
            }
        }

        return ['ultimo' => $ultimo, 'contagem' => $contagem];
    }

    /**
     * Atividade vinda de OS (check-in/checkout, mão de obra e consumo com os_id) de
     * todos os projetos, contada por dia num intervalo — para o gráfico de atividade do
     * painel (ADR-015 fase 4). [dia Y-m-d => quantidade].
     */
    private function atividadePorDiaOs(string $ini, string $fim): array
    {
        $porDia = [];
        if (! $this->db->table_exists('obra_os')) {
            return $porDia;
        }
        $somar = function (string $dia) use (&$porDia) {
            $porDia[$dia] = ($porDia[$dia] ?? 0) + 1;
        };
        $iniDt = $ini . ' 00:00:00';
        $fimDt = $fim . ' 23:59:59';
        if ($this->db->table_exists('os_checkin')) {
            $this->db->select('os_checkin.data_entrada, os_checkin.data_saida', false);
            $this->db->from('os_checkin');
            $this->db->join('obra_os', 'obra_os.os_id = os_checkin.os_id');
            $this->db->group_start()
                ->where('os_checkin.data_entrada >=', $iniDt)->where('os_checkin.data_entrada <=', $fimDt)
            ->group_end();
            $this->db->or_group_start()
                ->where('os_checkin.data_saida >=', $iniDt)->where('os_checkin.data_saida <=', $fimDt)
            ->group_end();
            foreach ($this->db->get()->result() as $r) {
                foreach ([$r->data_entrada, $r->data_saida] as $dt) {
                    if (! $dt) {
                        continue;
                    }
                    $dia = substr((string) $dt, 0, 10);
                    if ($dia >= $ini && $dia <= $fim) {
                        $somar($dia);
                    }
                }
            }
        }
        if ($this->temColunaOs('obra_apontamento')) {
            $this->db->select('data', false);
            $this->db->where('os_id IS NOT NULL', null, false);
            $this->db->where('data >=', $ini);
            $this->db->where('data <=', $fim);
            foreach ($this->db->get('obra_apontamento')->result() as $r) {
                $somar(substr((string) $r->data, 0, 10));
            }
        }
        if ($this->temColunaOs('obra_consumo')) {
            $this->db->select('data', false);
            $this->db->where('os_id IS NOT NULL', null, false);
            $this->db->where('data >=', $iniDt);
            $this->db->where('data <=', $fimDt);
            foreach ($this->db->get('obra_consumo')->result() as $r) {
                $somar(substr((string) $r->data, 0, 10));
            }
        }

        return $porDia;
    }

    /**
     * Atividade de OS de um único projeto (qualquer período), mais recente primeiro.
     * Usada na página do projeto (aba Execução, ADR-016).
     */
    public function atividadesProjeto(int $obraId): array
    {
        if (! $obraId) {
            return [];
        }
        $linhas = $this->atividadesOs(null, null, $obraId, 0)[$obraId] ?? [];
        usort($linhas, function ($a, $b) {
            return strcmp((string) $b['data'], (string) $a['data']);
        });

        return $linhas;
    }

    /**
     * Consumo (material/serviço) do projeto, mais recente primeiro, com a origem (RDO ou
     * OS, pela coluna os_id). Usada na aba Material da página do projeto (ADR-016).
     */
    public function consumoProjeto(int $obraId, int $limite = 100): array
    {
        if (! $obraId || ! $this->db->table_exists('obra_consumo')) {
            return [];
        }
        $temOsId = $this->db->field_exists('os_id', 'obra_consumo');
        $campos = ['idConsumo', 'rdo_id', 'tipo', 'descricao', 'quantidade', 'data', 'usuario_id'];
        if ($temOsId) {
            $campos[] = 'os_id';
        }
        $this->db->select(implode(', ', $campos));
        $this->db->where('obra_id', $obraId);
        $this->db->order_by('data', 'desc');
        $this->db->order_by('idConsumo', 'desc');
        $this->db->limit($limite);
        $linhas = $this->db->get('obra_consumo')->result();

        $uids = array_values(array_unique(array_filter(array_map(function ($c) {
            return (int) ($c->usuario_id ?? 0);
        }, $linhas))));
        $nomes = [];
        if ($uids) {
            $this->db->select('idUsuarios, nome');
            $this->db->where_in('idUsuarios', $uids);
            foreach ($this->db->get('usuarios')->result() as $u) {
                $nomes[(int) $u->idUsuarios] = (string) $u->nome;
            }
        }

        $out = [];
        foreach ($linhas as $c) {
            $osId = $temOsId ? (int) ($c->os_id ?? 0) : 0;
            $origem = self::classificarOrigemAtividade($osId ?: null);
            $out[] = [
                'idConsumo' => (int) $c->idConsumo,
                'tipo' => (string) $c->tipo,
                'descricao' => (string) $c->descricao,
                'quantidade' => (float) $c->quantidade,
                'data' => (string) $c->data,
                'usuario' => $nomes[(int) ($c->usuario_id ?? 0)] ?? '',
                'os_id' => $osId ?: null,
                'origem' => $origem['origem'],
                'origem_rotulo' => $origem['rotulo'],
            ];
        }

        return $out;
    }

    /**
     * Relatório do período: RDOs, avanços de ponto, fotos, consumo, horas, etapas, OS e
     * pontos por projeto. Filtros: obra_id e usuario_id (responsável do RDO ou quem avançou).
     */
    public function relatorio(string $inicio, string $fim, array $filtros = []): array
    {
        $vazio = ['projetos' => [], 'sem_registro' => [], 'tecnicos' => [], 'totais' => [
            'projetos' => 0, 'registros' => 0, 'fotos' => 0, 'ocorrencias' => 0, 'horas' => 0.0, 'tecnicos' => 0,
            'avancos' => 0, 'cabo_m' => 0.0, 'testados' => 0, 'repasses' => 0, 'tempo_medio_config_h' => null,
        ]];
        if (! $this->db->table_exists('obras') || ! $this->db->table_exists('obra_rdo')) {
            return $vazio;
        }
        $obraId = (int) ($filtros['obra_id'] ?? 0);
        $usuarioId = (int) ($filtros['usuario_id'] ?? 0);

        // RDOs do período
        $this->db->select('obra_rdo.idRdo, obra_rdo.obra_id, obra_rdo.numero, obra_rdo.data, obra_rdo.atividades, obra_rdo.ocorrencias, obra_rdo.condicao, obra_rdo.efetivo_total, obra_rdo.responsavel_id, usuarios.nome as responsavel');
        $this->db->from('obra_rdo');
        $this->db->join('usuarios', 'usuarios.idUsuarios = obra_rdo.responsavel_id', 'left');
        $this->db->where('obra_rdo.data >=', $inicio);
        $this->db->where('obra_rdo.data <=', $fim);
        if ($obraId) {
            $this->db->where('obra_rdo.obra_id', $obraId);
        }
        if ($usuarioId) {
            $this->db->where('obra_rdo.responsavel_id', $usuarioId);
        }
        $this->db->order_by('obra_rdo.data', 'asc');
        $this->db->order_by('obra_rdo.idRdo', 'asc');
        $rdos = $this->db->get()->result();

        $rdoIds = [];
        $obraIds = [];
        foreach ($rdos as $r) {
            $rdoIds[] = (int) $r->idRdo;
            $obraIds[(int) $r->obra_id] = true;
        }
        if ($obraId) {
            $obraIds[$obraId] = true;
        }

        // Avanços de ponto do período: o projeto entra na lista mesmo sem RDO
        $tipos = self::tiposPontos();
        $statusLista = self::statusPontos();
        $avRaw = [];
        $todosAv = [];
        foreach ($this->avancosPontos($inicio, $fim, $obraId, $usuarioId) as $ph) {
            $avRaw[(int) $ph->obra_id][] = $ph;
            $todosAv[] = $ph;
            $obraIds[(int) $ph->obra_id] = true;
        }
        // Fotos por avanço e pares de tempo até configurar (ADR-014), em lote
        $fotosAv = $this->fotosPorHistorico(array_map(function ($ph) {
            return (int) $ph->idHistorico;
        }, $todosAv));
        $paresObra = [];
        $paresTodos = [];
        foreach ($this->paresConfiguracao($todosAv, $fim) as $pr) {
            $paresObra[$pr['obra_id']][] = $pr['par'];
            $paresTodos[] = $pr['par'];
        }

        // Atividade vinda de OS vinculada (check-in/checkout, mão de obra e consumo com
        // os_id): o projeto entra na lista mesmo sem RDO (ADR-012/ADR-015 fase 4).
        $osAtividadesPorObra = $this->atividadesOs($inicio, $fim, $obraId, $usuarioId);
        foreach ($osAtividadesPorObra as $oid => $linhas) {
            if ($linhas) {
                $obraIds[(int) $oid] = true;
            }
        }

        // Fotos e consumo dos RDOs, em lote
        $fotos = [];
        if ($rdoIds && $this->db->table_exists('obra_rdo_foto')) {
            $this->db->select('idFoto, rdo_id');
            $this->db->where_in('rdo_id', $rdoIds);
            $this->db->order_by('idFoto', 'asc');
            foreach ($this->db->get('obra_rdo_foto')->result() as $f) {
                $fotos[(int) $f->rdo_id][] = (int) $f->idFoto;
            }
        }
        $consumo = [];
        if ($rdoIds && $this->db->table_exists('obra_consumo')) {
            $this->db->select('rdo_id, tipo, descricao, quantidade');
            $this->db->where_in('rdo_id', $rdoIds);
            $this->db->where_in('tipo', ['material', 'servico']);
            $this->db->order_by('idConsumo', 'asc');
            foreach ($this->db->get('obra_consumo')->result() as $c) {
                $consumo[(int) $c->rdo_id][$c->tipo][] = [
                    'descricao' => (string) $c->descricao,
                    'quantidade' => (float) $c->quantidade,
                ];
            }
        }

        // Dados dos projetos
        $obras = [];
        if ($obraIds) {
            $this->db->select('obras.idObra, obras.nome, obras.codigo, obras.status, obras.percentual_concluido, obras.data_fim_prevista, clientes.nomeCliente, usuarios.nome as responsavel');
            $this->db->from('obras');
            $this->db->join('clientes', 'clientes.idClientes = obras.clientes_id', 'left');
            $this->db->join('usuarios', 'usuarios.idUsuarios = obras.responsavel_id', 'left');
            $this->db->where_in('obras.idObra', array_keys($obraIds));
            foreach ($this->db->get()->result() as $o) {
                $obras[(int) $o->idObra] = $o;
            }
        }

        // Pontos atuais dos projetos, em lote
        $pontosPorObra = [];
        if ($obras && $this->db->table_exists('obra_pontos')) {
            $this->db->select('obra_id, tipo, status, metragem_cabo');
            $this->db->where_in('obra_id', array_keys($obras));
            foreach ($this->db->get('obra_pontos')->result() as $p) {
                $pontosPorObra[(int) $p->obra_id][] = $p;
            }
        }

        // Horas, etapas e OS por projeto, em lote
        $horas = [];
        if ($obras && ! $usuarioId && $this->db->table_exists('obra_apontamento')) {
            $this->db->select('obra_id, nome, SUM(horas) as horas, SUM(valor) as valor', false);
            $this->db->where_in('obra_id', array_keys($obras));
            $this->db->where('data >=', $inicio);
            $this->db->where('data <=', $fim);
            $this->db->group_by(['obra_id', 'nome']);
            $this->db->order_by('nome', 'asc');
            foreach ($this->db->get('obra_apontamento')->result() as $h) {
                $horas[(int) $h->obra_id][] = ['nome' => (string) $h->nome, 'horas' => (float) $h->horas, 'valor' => (float) $h->valor];
            }
        }
        $etapas = [];
        if ($obras && $this->db->table_exists('obra_etapas')) {
            $hoje = date('Y-m-d');
            $this->db->select('obra_id, nome, percentual_concluido, status, data_fim_prevista');
            $this->db->where_in('obra_id', array_keys($obras));
            $this->db->order_by('ordem', 'asc');
            $this->db->order_by('idEtapa', 'asc');
            foreach ($this->db->get('obra_etapas')->result() as $e) {
                $pct = (float) $e->percentual_concluido;
                $etapas[(int) $e->obra_id][] = [
                    'nome' => (string) $e->nome,
                    'percentual_concluido' => $pct,
                    'status' => (string) $e->status,
                    'data_fim_prevista' => $e->data_fim_prevista ?: null,
                    'atrasada' => (bool) ($e->data_fim_prevista && $e->data_fim_prevista < $hoje && $pct < 100 && $e->status !== 'concluida'),
                ];
            }
        }
        $oss = [];
        if ($obras && $this->db->table_exists('obra_os') && $this->db->table_exists('os')) {
            $this->db->select('obra_os.obra_id, os.idOs, os.status, os.descricaoProduto');
            $this->db->from('obra_os');
            $this->db->join('os', 'os.idOs = obra_os.os_id');
            $this->db->where_in('obra_os.obra_id', array_keys($obras));
            $this->db->order_by('os.idOs', 'desc');
            foreach ($this->db->get()->result() as $o) {
                $oss[(int) $o->obra_id][] = ['idOs' => (int) $o->idOs, 'status' => (string) $o->status, 'descricao' => (string) $o->descricaoProduto];
            }
        }

        // Monta projetos e técnicos
        $porObra = [];
        foreach ($rdos as $r) {
            $porObra[(int) $r->obra_id][] = $r;
        }
        $projetos = [];
        $tecnicos = [];
        foreach ($obras as $id => $o) {
            $registros = [];
            $mat = [];
            $srv = [];
            $nFotos = 0;
            $nOcorr = 0;
            $datas = [];
            $resp = [];
            foreach ($porObra[$id] ?? [] as $r) {
                $rid = (int) $r->idRdo;
                $m = $consumo[$rid]['material'] ?? [];
                $s = $consumo[$rid]['servico'] ?? [];
                $mat = array_merge($mat, $m);
                $srv = array_merge($srv, $s);
                $registros[] = [
                    'idRdo' => $rid,
                    'numero' => (int) $r->numero,
                    'data' => $r->data,
                    'responsavel' => (string) $r->responsavel,
                    'atividades' => (string) $r->atividades,
                    'ocorrencias' => (string) $r->ocorrencias,
                    'condicao' => (string) $r->condicao,
                    'efetivo_total' => (int) $r->efetivo_total,
                    'fotos' => $fotos[$rid] ?? [],
                    'materiais' => $m,
                    'servicos' => $s,
                ];
                $nFotos += count($fotos[$rid] ?? []);
                if (trim((string) $r->ocorrencias) !== '') {
                    $nOcorr++;
                }
                $datas[$r->data] = true;
                if ($r->responsavel_id) {
                    $uid = (int) $r->responsavel_id;
                    $resp[$uid] = true;
                    if (! isset($tecnicos[$uid])) {
                        $tecnicos[$uid] = self::tecnicoInicial($uid, (string) $r->responsavel);
                    }
                    $tecnicos[$uid]['registros']++;
                    $tecnicos[$uid]['obras'][$id] = true;
                    $tecnicos[$uid]['datas'][$r->data] = true;
                    if ($tecnicos[$uid]['ultimo'] === null || $r->data > $tecnicos[$uid]['ultimo']) {
                        $tecnicos[$uid]['ultimo'] = $r->data;
                    }
                }
            }

            // Avanços de ponto: contam como registro de execução do dia e do técnico
            $avs = [];
            $cabo = 0.0;
            $testados = 0;
            $repasses = 0;
            foreach ($avRaw[$id] ?? [] as $ph) {
                $av = self::formatarAvanco($ph, $statusLista, $tipos);
                $av['fotos'] = $fotosAv[(int) $ph->idHistorico] ?? 0;
                $avs[] = $av;
                if ($av['evento'] === 'repasse') {
                    $repasses++;
                }
                $cabo += $av['metragem_cabo'];
                if ($av['para'] === 'testado') {
                    $testados++;
                }
                $dia = substr((string) $ph->data, 0, 10);
                $datas[$dia] = true;
                if ($ph->usuario_id) {
                    $uid = (int) $ph->usuario_id;
                    $resp[$uid] = true;
                    if (! isset($tecnicos[$uid])) {
                        $tecnicos[$uid] = self::tecnicoInicial($uid, $av['usuario']);
                    }
                    if (($av['evento'] ?? 'status') === 'status') {
                        $tecnicos[$uid]['avancos']++;
                    }
                    $tecnicos[$uid]['obras'][$id] = true;
                    $tecnicos[$uid]['datas'][$dia] = true;
                    $tecnicos[$uid]['ultimo'] = self::maiorData($tecnicos[$uid]['ultimo'], $dia);
                }
            }

            // Atividade vinda de OS vinculada: entra junto com os registros do dia,
            // identificada pela origem ("OS #123" em vez de "Registro do dia").
            foreach ($osAtividadesPorObra[$id] ?? [] as $linhaOs) {
                $registros[] = $linhaOs;
                $datas[$linhaOs['data']] = true;
                if ($linhaOs['materiais']) {
                    $mat = array_merge($mat, $linhaOs['materiais']);
                }
                if ($linhaOs['servicos']) {
                    $srv = array_merge($srv, $linhaOs['servicos']);
                }
                if ($linhaOs['usuario_id']) {
                    $uid = (int) $linhaOs['usuario_id'];
                    $resp[$uid] = true;
                    if (! isset($tecnicos[$uid])) {
                        $tecnicos[$uid] = self::tecnicoInicial($uid, $linhaOs['responsavel']);
                    }
                    $tecnicos[$uid]['registros']++;
                    $tecnicos[$uid]['obras'][$id] = true;
                    $tecnicos[$uid]['datas'][$linhaOs['data']] = true;
                    $tecnicos[$uid]['ultimo'] = self::maiorData($tecnicos[$uid]['ultimo'], $linhaOs['data']);
                }
            }
            usort($registros, function ($a, $b) {
                return strcmp((string) $a['data'], (string) $b['data']);
            });

            $h = $horas[$id] ?? [];
            $projetos[] = [
                'obra' => [
                    'idObra' => $id,
                    'nome' => (string) $o->nome,
                    'codigo' => (string) $o->codigo,
                    'cliente' => (string) $o->nomeCliente,
                    'status' => (string) $o->status,
                    'percentual_concluido' => (float) $o->percentual_concluido,
                    'responsavel' => (string) $o->responsavel,
                    'data_fim_prevista' => $o->data_fim_prevista ?: null,
                ],
                'registros' => $registros,
                'avancos' => $avs,
                'pontos' => self::resumoPontos($pontosPorObra[$id] ?? []),
                'materiais' => $this->somarItens($mat),
                'servicos' => $this->somarItens($srv),
                'horas' => $h,
                'etapas' => $etapas[$id] ?? [],
                'os' => $oss[$id] ?? [],
                'totais' => [
                    'registros' => count($registros),
                    'fotos' => $nFotos,
                    'ocorrencias' => $nOcorr,
                    'horas' => (float) array_sum(array_column($h, 'horas')),
                    'dias_com_registro' => count($datas),
                    'tecnicos' => count($resp),
                    // Avanço = mudança de status; repasse e "assumiu" contam em 'repasses'.
                    'avancos' => count(array_filter($avs, fn ($x) => ($x['evento'] ?? 'status') === 'status')),
                    'cabo_m' => (float) $cabo,
                    'testados' => $testados,
                    'repasses' => $repasses,
                    'tempo_medio_config_h' => self::tempoMedioHoras($paresObra[$id] ?? []),
                ],
            ];
        }
        usort($projetos, function ($a, $b) {
            return strcasecmp($a['obra']['nome'], $b['obra']['nome']);
        });

        $listaTecnicos = [];
        foreach ($tecnicos as $t) {
            $listaTecnicos[] = [
                'usuario_id' => $t['usuario_id'],
                'nome' => $t['nome'],
                'registros' => $t['registros'],
                'avancos' => $t['avancos'],
                'projetos' => count($t['obras']),
                'dias' => count($t['datas']),
                'ultimo' => $t['ultimo'],
            ];
        }
        usort($listaTecnicos, function ($a, $b) {
            return ($b['registros'] <=> $a['registros']) ?: ($b['avancos'] <=> $a['avancos']) ?: strcasecmp($a['nome'], $b['nome']);
        });

        // Projetos em execução sem RDO nem avanço no período (só no relatório geral)
        $semRegistro = [];
        if (! $obraId && ! $usuarioId) {
            $this->db->select('obras.idObra, obras.nome, clientes.nomeCliente');
            $this->db->from('obras');
            $this->db->join('clientes', 'clientes.idClientes = obras.clientes_id', 'left');
            $this->db->where('obras.status', 'em_execucao');
            if ($obras) {
                $this->db->where_not_in('obras.idObra', array_keys($obras));
            }
            $this->db->order_by('obras.nome', 'asc');
            $lista = $this->db->get()->result();
            $ultimos = [];
            if ($lista) {
                $ids = array_map(function ($o) {
                    return (int) $o->idObra;
                }, $lista);
                $this->db->select('obra_id, MAX(data) as ultimo', false);
                $this->db->where_in('obra_id', $ids);
                $this->db->group_by('obra_id');
                foreach ($this->db->get('obra_rdo')->result() as $u) {
                    $ultimos[(int) $u->obra_id] = $u->ultimo;
                }
                if ($this->db->table_exists('obra_ponto_historico')) {
                    $this->db->select('obra_id, MAX(data) as ultimo', false);
                    $this->db->where_in('obra_id', $ids);
                    $this->db->group_by('obra_id');
                    foreach ($this->db->get('obra_ponto_historico')->result() as $u) {
                        $k = (int) $u->obra_id;
                        $ultimos[$k] = self::maiorData($ultimos[$k] ?? null, substr((string) $u->ultimo, 0, 10));
                    }
                }
                // Atividade vinda de OS vinculada (ADR-012/ADR-015 fase 4): um projeto
                // acompanhado só por OS não deve aparecer aqui como "sem registro".
                foreach ($this->atividadeOsPorObra($ids)['ultimo'] as $k => $ult) {
                    $ultimos[$k] = self::maiorData($ultimos[$k] ?? null, $ult);
                }
            }
            $semRegistro = [];
            foreach ($lista as $o) {
                $semRegistro[] = [
                    'idObra' => (int) $o->idObra,
                    'nome' => (string) $o->nome,
                    'cliente' => (string) $o->nomeCliente,
                    'ultimo_registro' => $ultimos[(int) $o->idObra] ?? null,
                ];
            }
        }

        $totais = array_column($projetos, 'totais');

        return [
            'projetos' => $projetos,
            'sem_registro' => $semRegistro,
            'tecnicos' => $listaTecnicos,
            'totais' => [
                'projetos' => count($projetos),
                // RDOs + atividade vinda de OS (ADR-015 fase 4), já somados em cada projeto.
                'registros' => (int) array_sum(array_column($totais, 'registros')),
                'fotos' => (int) array_sum(array_column($totais, 'fotos')),
                'ocorrencias' => (int) array_sum(array_column($totais, 'ocorrencias')),
                'horas' => (float) array_sum(array_column($totais, 'horas')),
                'tecnicos' => count($listaTecnicos),
                'avancos' => (int) array_sum(array_column($totais, 'avancos')),
                'cabo_m' => (float) array_sum(array_column($totais, 'cabo_m')),
                'testados' => (int) array_sum(array_column($totais, 'testados')),
                'repasses' => (int) array_sum(array_column($totais, 'repasses')),
                'tempo_medio_config_h' => self::tempoMedioHoras($paresTodos),
            ],
        ];
    }

    /* ============================== Painel ============================== */

    /**
     * Fila de repasses (ADR-014): pontos com etapa aguardando, fora de 'testado', em projetos
     * de planejamento ou execução. Agrupa por responsável; sem responsável = equipe.
     * Ordem: total desc.
     */
    private function filaRepasses(array $obras): array
    {
        if (! $this->db->table_exists('obra_pontos')
            || ! $this->db->field_exists('responsavel_id', 'obra_pontos')
            || ! $this->db->field_exists('aguardando', 'obra_pontos')) {
            return [];
        }
        $idsAtivos = [];
        foreach ($obras as $o) {
            if (in_array($o->status, ['planejamento', 'em_execucao'], true)) {
                $idsAtivos[] = (int) $o->idObra;
            }
        }
        if (! $idsAtivos) {
            return [];
        }

        $campos = ['responsavel_id', 'aguardando', 'status'];
        if ($this->db->field_exists('repassado_em', 'obra_pontos')) {
            $campos[] = 'repassado_em';
        }
        if ($this->db->field_exists('atualizado_em', 'obra_pontos')) {
            $campos[] = 'atualizado_em';
        }
        $this->db->select(implode(', ', $campos));
        $this->db->where_in('obra_id', $idsAtivos);
        $this->db->where('aguardando IS NOT NULL', null, false);
        $this->db->where('aguardando <>', '');
        $this->db->where('status <>', 'testado');
        $pontos = $this->db->get('obra_pontos')->result();

        $grupos = [];
        $uids = [];
        foreach ($pontos as $p) {
            $uid = ($p->responsavel_id !== null && $p->responsavel_id !== '') ? (int) $p->responsavel_id : null;
            $chave = $uid ?? 0;
            if (! isset($grupos[$chave])) {
                $grupos[$chave] = ['usuario_id' => $uid, 'aguardando' => [], 'total' => 0, 'mais_antigo' => null];
            }
            if ($uid !== null) {
                $uids[$uid] = true;
            }
            $ag = (string) $p->aguardando;
            $grupos[$chave]['aguardando'][$ag] = ($grupos[$chave]['aguardando'][$ag] ?? 0) + 1;
            $grupos[$chave]['total']++;

            // MIN(COALESCE(repassado_em, atualizado_em))
            $ref = $p->repassado_em ?? ($p->atualizado_em ?? null);
            if ($ref && ($grupos[$chave]['mais_antigo'] === null || $ref < $grupos[$chave]['mais_antigo'])) {
                $grupos[$chave]['mais_antigo'] = (string) $ref;
            }
        }

        $nomes = [];
        if ($uids) {
            $this->db->select('idUsuarios, nome');
            $this->db->where_in('idUsuarios', array_keys($uids));
            foreach ($this->db->get('usuarios')->result() as $u) {
                $nomes[(int) $u->idUsuarios] = (string) $u->nome;
            }
        }

        $fila = [];
        foreach ($grupos as $g) {
            if ($g['usuario_id'] === null) {
                $nome = 'Equipe (livre)';
            } else {
                $nome = $nomes[$g['usuario_id']] ?? ('Usuário #' . $g['usuario_id']);
            }
            $fila[] = [
                'usuario_id' => $g['usuario_id'],
                'nome' => $nome,
                'aguardando' => $g['aguardando'],
                'total' => $g['total'],
                'mais_antigo' => $g['mais_antigo'],
                'horas_mais_antigo' => self::horasDesde($g['mais_antigo']),
            ];
        }
        usort($fila, function ($a, $b) {
            return ($b['total'] <=> $a['total']) ?: strcasecmp($a['nome'], $b['nome']);
        });

        return $fila;
    }

    /** Painel de gestão: saúde por projeto, atividade de 7 dias, técnicos de hoje e ocorrências. */
    public function painel(string $hoje): array
    {
        $saida = [
            'projetos' => [],
            'contagem' => ['ok' => 0, 'atencao' => 0, 'critico' => 0, 'parado' => 0],
            'atividade_7d' => [],
            'tecnicos_hoje' => [],
            'ocorrencias_recentes' => [],
            'pontos_por_tipo' => [],
            'fila' => [],
        ];
        $ini7 = (new DateTime($hoje))->modify('-6 days')->format('Y-m-d');
        for ($i = 0; $i < 7; $i++) {
            $saida['atividade_7d'][] = ['data' => (new DateTime($ini7))->modify("+$i days")->format('Y-m-d'), 'registros' => 0];
        }
        if (! $this->db->table_exists('obras')) {
            return $saida;
        }
        $temRdo = $this->db->table_exists('obra_rdo');
        $temAvanco = $this->db->table_exists('obra_ponto_historico');
        $temPontos = $this->db->table_exists('obra_pontos');
        // Condição fixa (sem entrada do usuário): RDO com ocorrência preenchida
        $comOcorrencia = "TRIM(COALESCE(obra_rdo.ocorrencias, '')) <> ''";

        $this->db->select('obras.idObra, obras.nome, obras.status, obras.percentual_concluido, obras.data_inicio_prevista, obras.data_fim_prevista, obras.responsavel_id, clientes.nomeCliente, usuarios.nome as responsavel');
        $this->db->from('obras');
        $this->db->join('clientes', 'clientes.idClientes = obras.clientes_id', 'left');
        $this->db->join('usuarios', 'usuarios.idUsuarios = obras.responsavel_id', 'left');
        $this->db->where_in('obras.status', ['planejamento', 'em_execucao', 'paralisada']);
        $obras = $this->db->get()->result();
        $ids = array_map(function ($o) {
            return (int) $o->idObra;
        }, $obras);

        $ultimo = [];
        $reg7 = [];
        $oc7 = [];
        $atrasadas = [];
        if ($ids && $temRdo) {
            $this->db->select('obra_id, MAX(data) as ultimo', false);
            $this->db->where_in('obra_id', $ids);
            $this->db->group_by('obra_id');
            foreach ($this->db->get('obra_rdo')->result() as $r) {
                $ultimo[(int) $r->obra_id] = $r->ultimo;
            }
            $this->db->select('obra_id, COUNT(*) as n', false);
            $this->db->where_in('obra_id', $ids);
            $this->db->where('data >=', $ini7);
            $this->db->where('data <=', $hoje);
            $this->db->group_by('obra_id');
            foreach ($this->db->get('obra_rdo')->result() as $r) {
                $reg7[(int) $r->obra_id] = (int) $r->n;
            }
            $this->db->select('obra_id, COUNT(*) as n', false);
            $this->db->from('obra_rdo');
            $this->db->where_in('obra_id', $ids);
            $this->db->where('data >=', $ini7);
            $this->db->where('data <=', $hoje);
            $this->db->where($comOcorrencia, null, false);
            $this->db->group_by('obra_id');
            foreach ($this->db->get()->result() as $r) {
                $oc7[(int) $r->obra_id] = (int) $r->n;
            }
        }

        // Avanços de ponto: último avanço e contagem dos últimos 7 dias, por projeto
        $avUltimo = [];
        $avReg7 = [];
        if ($ids && $temAvanco) {
            $this->db->select('obra_id, MAX(data) as ultimo', false);
            $this->db->where_in('obra_id', $ids);
            $this->db->group_by('obra_id');
            foreach ($this->db->get('obra_ponto_historico')->result() as $r) {
                $avUltimo[(int) $r->obra_id] = substr((string) $r->ultimo, 0, 10);
            }
            $this->db->select('obra_id, COUNT(*) as n', false);
            $this->db->where_in('obra_id', $ids);
            $this->db->where('data >=', $ini7 . ' 00:00:00');
            $this->db->where('data <=', $hoje . ' 23:59:59');
            $this->db->group_by('obra_id');
            foreach ($this->db->get('obra_ponto_historico')->result() as $r) {
                $avReg7[(int) $r->obra_id] = (int) $r->n;
            }
        }

        // Atividade vinda de OS vinculada (check-in/checkout, mão de obra e consumo com
        // os_id): último dia (sem limite) e contagem nos últimos 7 dias (ADR-015 fase 4).
        $osUltimo = [];
        $osReg7 = [];
        if ($ids) {
            $osUltimo = $this->atividadeOsPorObra($ids)['ultimo'];
            $osReg7 = $this->atividadeOsPorObra($ids, $ini7, $hoje)['contagem'];
        }

        // Pontos dos projetos ativos, em lote
        $pontosPorObra = [];
        $pontosAtivos = [];
        if ($ids && $temPontos) {
            $this->db->select('obra_id, tipo, status, metragem_cabo');
            $this->db->where_in('obra_id', $ids);
            foreach ($this->db->get('obra_pontos')->result() as $p) {
                $pontosPorObra[(int) $p->obra_id][] = $p;
                $pontosAtivos[] = $p;
            }
        }

        if ($ids && $this->db->table_exists('obra_etapas')) {
            $this->db->select('obra_id, COUNT(*) as n', false);
            $this->db->where_in('obra_id', $ids);
            $this->db->where('data_fim_prevista <', $hoje);
            $this->db->where('percentual_concluido <', 100);
            $this->db->where('status <>', 'concluida');
            $this->db->group_by('obra_id');
            foreach ($this->db->get('obra_etapas')->result() as $r) {
                $atrasadas[(int) $r->obra_id] = (int) $r->n;
            }
        }

        $peso = ['critico' => 0, 'atencao' => 1, 'parado' => 2, 'ok' => 3, 'neutro' => 4];
        $projetos = [];
        foreach ($obras as $o) {
            $id = (int) $o->idObra;
            $ult = self::maiorData(self::maiorData($ultimo[$id] ?? null, $avUltimo[$id] ?? null), $osUltimo[$id] ?? null);
            $resumo = self::resumoPontos($pontosPorObra[$id] ?? []);
            $saude = self::saude([
                'status' => $o->status,
                'percentual_concluido' => (float) $o->percentual_concluido,
                'data_inicio_prevista' => $o->data_inicio_prevista ?: null,
                'data_fim_prevista' => $o->data_fim_prevista ?: null,
                'ultimo_registro' => $ult,
                'etapas_atrasadas' => $atrasadas[$id] ?? 0,
                'ocorrencias_7d' => $oc7[$id] ?? 0,
            ], $hoje);
            if (isset($saida['contagem'][$saude['nivel']])) {
                $saida['contagem'][$saude['nivel']]++;
            }
            $projetos[] = [
                'idObra' => $id,
                'nome' => (string) $o->nome,
                'cliente' => (string) $o->nomeCliente,
                'responsavel' => (string) $o->responsavel,
                'status' => (string) $o->status,
                'percentual_concluido' => (float) $o->percentual_concluido,
                'data_fim_prevista' => $o->data_fim_prevista ?: null,
                'ultimo_registro' => $ult,
                'dias_sem_registro' => $ult ? self::dias($ult, $hoje) : null,
                'etapas_atrasadas' => $atrasadas[$id] ?? 0,
                'ocorrencias_7d' => $oc7[$id] ?? 0,
                'registros_7d' => ($reg7[$id] ?? 0) + ($avReg7[$id] ?? 0) + ($osReg7[$id] ?? 0),
                'pontos' => [
                    'total' => (int) $resumo['total'],
                    'testados' => (int) ($resumo['por_status']['testado'] ?? 0),
                    'progresso' => $resumo['progresso'] ?? null,
                ],
                'saude' => $saude,
            ];
        }
        usort($projetos, function ($a, $b) use ($peso) {
            return ($peso[$a['saude']['nivel']] <=> $peso[$b['saude']['nivel']]) ?: strcasecmp($a['nome'], $b['nome']);
        });
        $saida['projetos'] = $projetos;
        $saida['pontos_por_tipo'] = self::resumoPontos($pontosAtivos)['por_tipo'] ?? [];
        $saida['fila'] = $this->filaRepasses($obras);

        if (! $temRdo) {
            return $saida;
        }

        // Atividade dos últimos 7 dias: RDOs somados aos avanços de ponto
        $this->db->select('data, COUNT(*) as n', false);
        $this->db->where('data >=', $ini7);
        $this->db->where('data <=', $hoje);
        $this->db->group_by('data');
        $porDia = [];
        foreach ($this->db->get('obra_rdo')->result() as $r) {
            $porDia[$r->data] = (int) $r->n;
        }
        if ($temAvanco) {
            $this->db->select('DATE(data) as dia, COUNT(*) as n', false);
            $this->db->where('data >=', $ini7 . ' 00:00:00');
            $this->db->where('data <=', $hoje . ' 23:59:59');
            $this->db->group_by('DATE(data)', false);
            foreach ($this->db->get('obra_ponto_historico')->result() as $r) {
                $porDia[$r->dia] = ($porDia[$r->dia] ?? 0) + (int) $r->n;
            }
        }
        // ... somado à atividade vinda de OS vinculada (check-in/checkout, mão de obra e
        // consumo com os_id), de todos os projetos (ADR-015 fase 4).
        foreach ($this->atividadePorDiaOs($ini7, $hoje) as $dia => $n) {
            $porDia[$dia] = ($porDia[$dia] ?? 0) + $n;
        }
        foreach ($saida['atividade_7d'] as &$d) {
            $d['registros'] = $porDia[$d['data']] ?? 0;
        }
        unset($d);

        // Técnicos: vinculados ou responsáveis de projetos em execução
        $execIds = [];
        $porUsuario = [];
        foreach ($obras as $o) {
            if ($o->status === 'em_execucao') {
                $execIds[] = (int) $o->idObra;
                if ($o->responsavel_id) {
                    $porUsuario[(int) $o->responsavel_id][(int) $o->idObra] = true;
                }
            }
        }
        if ($execIds && $this->db->table_exists('obra_usuario')) {
            $this->db->select('obra_id, usuario_id');
            $this->db->where_in('obra_id', $execIds);
            foreach ($this->db->get('obra_usuario')->result() as $v) {
                $porUsuario[(int) $v->usuario_id][(int) $v->obra_id] = true;
            }
        }
        if ($porUsuario) {
            $uids = array_keys($porUsuario);
            $this->db->select('idUsuarios, nome');
            $this->db->where_in('idUsuarios', $uids);
            $nomes = [];
            foreach ($this->db->get('usuarios')->result() as $u) {
                $nomes[(int) $u->idUsuarios] = (string) $u->nome;
            }
            $this->db->select('responsavel_id, MAX(data) as ultimo', false);
            $this->db->where_in('responsavel_id', $uids);
            $this->db->group_by('responsavel_id');
            $ultimos = [];
            foreach ($this->db->get('obra_rdo')->result() as $r) {
                $ultimos[(int) $r->responsavel_id] = $r->ultimo;
            }
            if ($temAvanco) {
                $this->db->select('usuario_id, MAX(data) as ultimo', false);
                $this->db->where_in('usuario_id', $uids);
                $this->db->group_by('usuario_id');
                foreach ($this->db->get('obra_ponto_historico')->result() as $r) {
                    $k = (int) $r->usuario_id;
                    $ultimos[$k] = self::maiorData($ultimos[$k] ?? null, substr((string) $r->ultimo, 0, 10));
                }
            }
            $tec = [];
            foreach ($porUsuario as $uid => $lista) {
                $ult = $ultimos[$uid] ?? null;
                $tec[] = [
                    'usuario_id' => (int) $uid,
                    'nome' => $nomes[$uid] ?? '',
                    'projetos' => count($lista),
                    'registrou_hoje' => $ult === $hoje,
                    'ultimo' => $ult,
                ];
            }
            usort($tec, function ($a, $b) {
                return ((int) $a['registrou_hoje'] <=> (int) $b['registrou_hoje']) ?: strcasecmp($a['nome'], $b['nome']);
            });
            $saida['tecnicos_hoje'] = $tec;
        }

        // Ocorrências recentes
        $this->db->select('obra_rdo.idRdo, obra_rdo.obra_id, obra_rdo.data, obra_rdo.ocorrencias, obras.nome as obra_nome, usuarios.nome as responsavel');
        $this->db->from('obra_rdo');
        $this->db->join('obras', 'obras.idObra = obra_rdo.obra_id');
        $this->db->join('usuarios', 'usuarios.idUsuarios = obra_rdo.responsavel_id', 'left');
        $this->db->where('obra_rdo.data >=', $ini7);
        $this->db->where('obra_rdo.data <=', $hoje);
        $this->db->where($comOcorrencia, null, false);
        $this->db->order_by('obra_rdo.data', 'desc');
        $this->db->order_by('obra_rdo.idRdo', 'desc');
        $this->db->limit(10);
        foreach ($this->db->get()->result() as $r) {
            $saida['ocorrencias_recentes'][] = [
                'idRdo' => (int) $r->idRdo,
                'obra_id' => (int) $r->obra_id,
                'obra_nome' => (string) $r->obra_nome,
                'data' => $r->data,
                'responsavel' => (string) $r->responsavel,
                'ocorrencias' => (string) $r->ocorrencias,
            ];
        }

        return $saida;
    }

    /**
     * Resumo para a lista de projetos, em poucas queries agregadas (sem N+1).
     * Retorna [idObra => ['dias_sem_registro' => ?int, 'etapas_sem_os' => int, 'etapas_atrasadas' => int]].
     */
    public function resumoLista(array $ids, ?string $hoje = null): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $hoje = $hoje ?: date('Y-m-d');
        $saida = [];
        foreach ($ids as $id) {
            $saida[$id] = ['dias_sem_registro' => null, 'etapas_sem_os' => 0, 'etapas_atrasadas' => 0];
        }
        if (! $ids) {
            return $saida;
        }
        $ultimo = [];
        if ($this->db->table_exists('obra_rdo')) {
            $this->db->select('obra_id, MAX(data) as ultimo', false);
            $this->db->where_in('obra_id', $ids);
            $this->db->group_by('obra_id');
            foreach ($this->db->get('obra_rdo')->result() as $r) {
                $ultimo[(int) $r->obra_id] = $r->ultimo;
            }
        }
        if ($this->db->table_exists('obra_ponto_historico')) {
            $this->db->select('obra_id, MAX(data) as ultimo', false);
            $this->db->where_in('obra_id', $ids);
            $this->db->group_by('obra_id');
            foreach ($this->db->get('obra_ponto_historico')->result() as $r) {
                $k = (int) $r->obra_id;
                $ultimo[$k] = self::maiorData($ultimo[$k] ?? null, substr((string) $r->ultimo, 0, 10));
            }
        }
        // Atividade vinda de OS vinculada (ADR-015 fase 4): sem ela, um projeto só
        // acompanhado por OS aparecia como sem registro (ADR-012).
        foreach ($this->atividadeOsPorObra($ids)['ultimo'] as $id => $data) {
            $ultimo[$id] = self::maiorData($ultimo[$id] ?? null, $data);
        }
        foreach ($ultimo as $id => $data) {
            if ($data && isset($saida[$id])) {
                $saida[$id]['dias_sem_registro'] = self::dias(substr((string) $data, 0, 10), $hoje);
            }
        }
        if ($this->db->table_exists('obra_etapas')) {
            $temOs = $this->db->table_exists('obra_os');
            $this->db->select('obra_etapas.obra_id, COUNT(*) as n, '
                . 'SUM(CASE WHEN obra_etapas.data_fim_prevista < ' . $this->db->escape($hoje) . ' THEN 1 ELSE 0 END) as atrasadas'
                . ($temOs ? ', SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM obra_os WHERE obra_os.etapa_id = obra_etapas.idEtapa) THEN 1 ELSE 0 END) as sem_os' : ''), false);
            $this->db->from('obra_etapas');
            $this->db->where_in('obra_etapas.obra_id', $ids);
            $this->db->where('obra_etapas.percentual_concluido <', 100);
            $this->db->where('obra_etapas.status <>', 'concluida');
            $this->db->group_by('obra_etapas.obra_id');
            foreach ($this->db->get()->result() as $r) {
                $k = (int) $r->obra_id;
                $saida[$k]['etapas_atrasadas'] = (int) $r->atrasadas;
                $saida[$k]['etapas_sem_os'] = $temOs ? (int) $r->sem_os : 0;
            }
        }

        return $saida;
    }

    /** Quantidade de projetos por status (para os filtros da lista). */
    public function contagemStatus(): array
    {
        if (! $this->db->table_exists('obras')) {
            return [];
        }
        $this->db->select('status, COUNT(*) as n', false);
        $this->db->group_by('status');
        $c = [];
        foreach ($this->db->get('obras')->result() as $r) {
            $c[(string) $r->status] = (int) $r->n;
        }

        return $c;
    }

    /* ================= Alertas da ponte OS x projeto (ADR-015 fase 5) ================= */

    /** Ordem de exibição dos alertas (o que trava dinheiro primeiro). */
    private const ALERTA_ORDEM = [
        'medicao_nao_faturada' => 1, 'os_aguardando_medicao' => 2, 'etapa_atrasada' => 3,
        'etapa_sem_os' => 4, 'os_sem_etapa' => 5,
    ];

    /** Rótulo do botão de cada alerta. */
    private const ALERTA_ACAO = [
        'medicao_nao_faturada' => 'Faturar', 'os_aguardando_medicao' => 'Fazer a medição',
        'etapa_atrasada' => 'Ver etapas', 'etapa_sem_os' => 'Gerar OS', 'os_sem_etapa' => 'Escolher a etapa',
    ];

    /**
     * Texto curto de um alerta da ponte. Pura (sem banco).
     *
     * @param array $d qtd, nome (etapa), dias, os_id, numero (medição), valor
     */
    public static function textoAlerta(string $tipo, array $d): string
    {
        $qtd = max(1, (int) ($d['qtd'] ?? 1));
        $nome = trim((string) ($d['nome'] ?? ''));
        $dias = isset($d['dias']) ? (int) $d['dias'] : null;
        $nDias = function (int $n) {
            return $n . ($n === 1 ? ' dia' : ' dias');
        };
        switch ($tipo) {
            case 'etapa_atrasada':
                if ($qtd > 1) {
                    return $qtd . ' etapas passaram do fim previsto sem OS concluída.';
                }

                return 'A etapa "' . $nome . '" passou do fim previsto' . ($dias !== null && $dias > 0 ? ' há ' . $nDias($dias) : '') . ' sem OS concluída.';
            case 'os_sem_etapa':
                if ($qtd > 1) {
                    return $qtd . ' OS de execução estão sem etapa. Sem etapa o avanço não chega ao planejamento.';
                }

                return 'A OS #' . (int) ($d['os_id'] ?? 0) . ' é de execução e está sem etapa. Sem etapa o avanço não chega ao planejamento.';
            case 'etapa_sem_os':
                if ($qtd > 1) {
                    return $qtd . ' etapas começam em até 7 dias (ou já começaram) e ainda não têm OS.';
                }
                if ($dias === null || $dias === 0) {
                    $quando = 'começa hoje';
                } elseif ($dias > 0) {
                    $quando = 'começa em ' . $nDias($dias);
                } else {
                    $quando = 'deveria ter começado há ' . $nDias(-$dias);
                }

                return 'A etapa "' . $nome . '" ' . $quando . ' e ainda não tem OS.';
            case 'os_aguardando_medicao':
                if ($qtd > 1) {
                    return $qtd . ' OS finalizadas há mais de 7 dias aguardam medição.';
                }

                return 'A OS #' . (int) ($d['os_id'] ?? 0) . ' foi finalizada' . ($dias !== null ? ' há ' . $nDias($dias) : '') . ' e aguarda medição.';
            case 'medicao_nao_faturada':
                $valor = 'R$ ' . number_format((float) ($d['valor'] ?? 0), 2, ',', '.');
                if ($qtd > 1) {
                    return $qtd . ' medições aprovadas ainda não foram faturadas (' . $valor . ').';
                }

                return 'A medição nº ' . (int) ($d['numero'] ?? 0) . ' foi aprovada e ainda não foi faturada (' . $valor . ').';
        }

        return '';
    }

    /** Monta um alerta no formato devolvido por alertasPonte(). Pura. */
    public static function alerta(string $tipo, int $obraId, string $obraNome, array $dados, string $ancora): array
    {
        $link = 'obras/visualizar/' . $obraId . '#' . $ancora;
        if ($tipo === 'os_sem_etapa' && (int) ($dados['qtd'] ?? 0) === 1 && ! empty($dados['os_id'])) {
            // a etapa é escolhida na própria OS (selo do projeto)
            $link = 'os/visualizar/' . (int) $dados['os_id'];
        }

        return [
            'tipo' => $tipo,
            'obra_id' => $obraId,
            'obra_nome' => $obraNome,
            'texto' => self::textoAlerta($tipo, $dados),
            'link' => $link,
            'acao' => self::ALERTA_ACAO[$tipo] ?? 'Abrir',
            'nivel' => in_array($tipo, ['etapa_atrasada', 'medicao_nao_faturada'], true) ? 'perigo' : 'alerta',
        ];
    }

    /**
     * Alertas que cruzam OS e projeto: etapa vencida sem OS concluída, OS de execução sem
     * etapa, etapa começando sem OS, OS aguardando medição há mais de 7 dias e medição
     * aprovada não faturada. Uma linha por projeto e tipo, em consultas agregadas (sem N+1).
     * 'link' é relativo (usar com site_url()).
     *
     * @return array lista de ['tipo','obra_id','obra_nome','texto','link','acao','nivel']
     */
    public function alertasPonte(?int $obraId = null, ?string $hoje = null): array
    {
        $hoje = $hoje ?: date('Y-m-d');
        if (! $this->db->table_exists('obras') || ! $this->db->table_exists('obra_os') || ! $this->db->table_exists('os')) {
            return [];
        }
        $alertas = [];
        $ativos = "obras.status IN ('planejamento', 'em_execucao', 'paralisada')";
        $porObra = $obraId ? ' AND obras.idObra = ' . (int) $obraId : '';
        $eHoje = $this->db->escape($hoje);
        $diasAte = function ($data) use ($hoje) {
            $data = substr((string) $data, 0, 10);

            return self::data($data) ? self::dias($hoje, $data) : null;
        };
        $temEtapas = $this->db->table_exists('obra_etapas');
        $etapaAberta = "obra_etapas.percentual_concluido < 100 AND obra_etapas.status <> 'concluida'";

        // 1) etapa com fim previsto vencido e nenhuma OS concluída nela
        if ($temEtapas) {
            $sql = 'SELECT obras.idObra, obras.nome AS obra_nome, COUNT(*) AS n, MIN(obra_etapas.nome) AS etapa, MIN(obra_etapas.data_fim_prevista) AS fim'
                . ' FROM obra_etapas JOIN obras ON obras.idObra = obra_etapas.obra_id'
                . ' WHERE ' . $ativos . $porObra . ' AND ' . $etapaAberta
                . " AND obra_etapas.data_fim_prevista > '1000-01-01' AND obra_etapas.data_fim_prevista < " . $eHoje
                . " AND NOT EXISTS (SELECT 1 FROM obra_os JOIN os ON os.idOs = obra_os.os_id WHERE obra_os.etapa_id = obra_etapas.idEtapa AND os.status IN ('Finalizado', 'Faturado'))"
                . ' GROUP BY obras.idObra, obras.nome';
            foreach ($this->db->query($sql)->result() as $r) {
                $d = $diasAte($r->fim);
                $alertas[] = self::alerta('etapa_atrasada', (int) $r->idObra, (string) $r->obra_nome, [
                    'qtd' => (int) $r->n, 'nome' => $r->etapa, 'dias' => $d !== null ? -$d : null,
                ], 'planejamento');
            }
        }

        // 2) OS vinculada como execução, mas sem etapa
        if ($this->db->field_exists('tipo_vinculo', 'obra_os')) {
            $sql = 'SELECT obras.idObra, obras.nome AS obra_nome, COUNT(*) AS n, MIN(obra_os.os_id) AS os_id'
                . ' FROM obra_os JOIN obras ON obras.idObra = obra_os.obra_id JOIN os ON os.idOs = obra_os.os_id'
                . ' WHERE ' . $ativos . $porObra . " AND obra_os.tipo_vinculo = 'execucao'"
                . " AND (obra_os.etapa_id IS NULL OR obra_os.etapa_id = 0) AND os.status <> 'Cancelado'"
                . ' GROUP BY obras.idObra, obras.nome';
            foreach ($this->db->query($sql)->result() as $r) {
                $alertas[] = self::alerta('os_sem_etapa', (int) $r->idObra, (string) $r->obra_nome, [
                    'qtd' => (int) $r->n, 'os_id' => (int) $r->os_id,
                ], 'os');
            }
        }

        // 3) etapa que começa em até 7 dias (ou já devia ter começado) e ainda não tem OS.
        //    Etapa com fim vencido já entra no alerta 1.
        if ($temEtapas) {
            $limite = $this->db->escape((new DateTime($hoje))->modify('+7 days')->format('Y-m-d'));
            $sql = 'SELECT obras.idObra, obras.nome AS obra_nome, COUNT(*) AS n, MIN(obra_etapas.nome) AS etapa, MIN(obra_etapas.data_inicio_prevista) AS inicio'
                . ' FROM obra_etapas JOIN obras ON obras.idObra = obra_etapas.obra_id'
                . ' WHERE ' . $ativos . $porObra . ' AND ' . $etapaAberta
                . " AND obra_etapas.data_inicio_prevista > '1000-01-01' AND obra_etapas.data_inicio_prevista <= " . $limite
                . " AND (obra_etapas.data_fim_prevista IS NULL OR obra_etapas.data_fim_prevista < '1000-01-01' OR obra_etapas.data_fim_prevista >= " . $eHoje . ')'
                . ' AND NOT EXISTS (SELECT 1 FROM obra_os WHERE obra_os.etapa_id = obra_etapas.idEtapa)'
                . ' GROUP BY obras.idObra, obras.nome';
            foreach ($this->db->query($sql)->result() as $r) {
                $alertas[] = self::alerta('etapa_sem_os', (int) $r->idObra, (string) $r->obra_nome, [
                    'qtd' => (int) $r->n, 'nome' => $r->etapa, 'dias' => $diasAte($r->inicio),
                ], 'planejamento');
            }
        }

        // 4) OS que fatura no projeto, finalizada há mais de 7 dias e fora de qualquer medição
        if ($this->db->table_exists('obra_medicao_os') && $this->db->field_exists('fatura_no_projeto', 'obra_os')) {
            $corte = $this->db->escape((new DateTime($hoje))->modify('-7 days')->format('Y-m-d'));
            $sql = 'SELECT obras.idObra, obras.nome AS obra_nome, COUNT(*) AS n, MIN(obra_os.os_id) AS os_id, MIN(os.dataFinal) AS final'
                . ' FROM obra_os JOIN obras ON obras.idObra = obra_os.obra_id JOIN os ON os.idOs = obra_os.os_id'
                . " WHERE obras.status <> 'cancelada'" . $porObra
                . " AND obra_os.fatura_no_projeto = 1 AND os.status = 'Finalizado'"
                . " AND os.dataFinal > '1000-01-01' AND os.dataFinal < " . $corte
                . ' AND NOT EXISTS (SELECT 1 FROM obra_medicao_os WHERE obra_medicao_os.os_id = obra_os.os_id)'
                . ' GROUP BY obras.idObra, obras.nome';
            foreach ($this->db->query($sql)->result() as $r) {
                $d = $diasAte($r->final);
                $alertas[] = self::alerta('os_aguardando_medicao', (int) $r->idObra, (string) $r->obra_nome, [
                    'qtd' => (int) $r->n, 'os_id' => (int) $r->os_id, 'dias' => $d !== null ? -$d : null,
                ], 'medicao');
            }
        }

        // 5) medição aprovada e ainda sem lançamento no financeiro
        if ($this->db->table_exists('obra_medicao') && $this->db->field_exists('lancamento_id', 'obra_medicao')) {
            $valor = $this->db->field_exists('valor_total', 'obra_medicao')
                ? 'CASE WHEN obra_medicao.valor_total > 0 THEN obra_medicao.valor_total ELSE obra_medicao.valor_medido END'
                : 'obra_medicao.valor_medido';
            $sql = 'SELECT obras.idObra, obras.nome AS obra_nome, COUNT(*) AS n, MIN(obra_medicao.numero) AS numero, SUM(' . $valor . ') AS valor'
                . ' FROM obra_medicao JOIN obras ON obras.idObra = obra_medicao.obra_id'
                . " WHERE obras.status <> 'cancelada'" . $porObra
                . " AND obra_medicao.status = 'aprovada' AND (obra_medicao.lancamento_id IS NULL OR obra_medicao.lancamento_id = 0)"
                . ' GROUP BY obras.idObra, obras.nome';
            foreach ($this->db->query($sql)->result() as $r) {
                $alertas[] = self::alerta('medicao_nao_faturada', (int) $r->idObra, (string) $r->obra_nome, [
                    'qtd' => (int) $r->n, 'numero' => (int) $r->numero, 'valor' => (float) $r->valor,
                ], 'medicao');
            }
        }

        usort($alertas, function ($a, $b) {
            return [self::ALERTA_ORDEM[$a['tipo']] ?? 9, $a['obra_nome']] <=> [self::ALERTA_ORDEM[$b['tipo']] ?? 9, $b['obra_nome']];
        });

        return $alertas;
    }
}
