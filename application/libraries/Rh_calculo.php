<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Motor de cálculo de horas do RH.
 *
 * Transforma as batidas de ponto de um colaborador em horas trabalhadas,
 * horas extras (50%/100%), faltas e saldo de banco de horas, e consolida
 * o mês em `rh_horas`.
 *
 * Regras:
 *  - Minutos trabalhados = soma dos segmentos (entrada|fim_intervalo) →
 *    (inicio_intervalo|saida). O intervalo (almoço) não conta.
 *  - Dia útil = o dia da semana está em `jornada.dias_semana`.
 *  - Extra em dia útil (além da carga prevista) = 50%.
 *  - Trabalho em dia NÃO útil (folga/domingo) = 100%.
 *  - Falta / banco negativo só a partir de `ponto_inicio` (se vazio, não cobra
 *    dias sem batida — evita dívida de 80h+ ao cadastrar e recalcular o mês).
 *  - Desconto financeiro automático de faltas só se config
 *    `rh_falta_desconto_automatico` = 1.
 */
class Rh_calculo
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Minutos efetivamente trabalhados a partir de uma lista de batidas
     * (objetos com ->tipo e ->data_hora) já ordenadas no tempo.
     */
    public function minutosTrabalhados(array $batidas)
    {
        $total = 0;
        $abertura = null; // timestamp da última "entrada"/"fim_intervalo"

        foreach ($batidas as $b) {
            $ts = strtotime($b->data_hora);
            if (in_array($b->tipo, ['entrada', 'fim_intervalo'], true)) {
                if ($abertura === null) {
                    $abertura = $ts;
                }
            } elseif (in_array($b->tipo, ['inicio_intervalo', 'saida'], true)) {
                if ($abertura !== null) {
                    $total += max(0, $ts - $abertura);
                    $abertura = null;
                }
            }
        }

        return (int) round($total / 60);
    }

    /**
     * Data a partir da qual faltas e banco negativo passam a valer.
     * NULL = controle ainda não habilitado (só conta o que foi batido).
     */
    public function dataInicioControle($colaborador)
    {
        if (empty($colaborador) || empty($colaborador->ponto_inicio)) {
            return null;
        }
        $d = substr((string) $colaborador->ponto_inicio, 0, 10);
        if ($d === '' || $d === '0000-00-00') {
            return null;
        }
        return $d;
    }

    /** Config: gera lançamento R$ de falta automaticamente? Default: não. */
    public function descontoFaltaAutomaticoAtivo()
    {
        if (! $this->CI->db->table_exists('configuracoes')) {
            return false;
        }
        $row = $this->CI->db->get_where('configuracoes', ['config' => 'rh_falta_desconto_automatico'])->row();
        if (! $row) {
            return false;
        }
        return (string) $row->valor === '1';
    }

    /**
     * Calcula os totais de um dia.
     *
     * @param array $batidas         batidas do dia (ordenadas)
     * @param int   $cargaPrevista   minutos previstos no dia (0 se folga)
     * @param int   $tolerancia      tolerância em minutos
     * @param bool  $ehDiaUtil       se o dia está na escala
     * @param bool  $cobraFalta      se deve gerar falta/saldo negativo (ponto_inicio)
     */
    public function calcularDia(array $batidas, $cargaPrevista, $tolerancia = 0, $ehDiaUtil = true, $cobraFalta = true)
    {
        $trabalhado = $this->minutosTrabalhados($batidas);
        $previsto = $ehDiaUtil ? (int) $cargaPrevista : 0;

        $extra50 = 0;
        $extra100 = 0;
        $falta = 0;

        if (! $ehDiaUtil) {
            // Qualquer trabalho em dia não útil é 100%
            $extra100 = $trabalhado;
        } else {
            $diff = $trabalhado - $previsto;
            if ($diff > $tolerancia) {
                $extra50 = $diff;
            } elseif ($diff < -$tolerancia && $cobraFalta) {
                $falta = abs($diff);
            }
        }

        // Sem controle de ponto: saldo só positivo (não “deve” o dia)
        $saldo = $trabalhado - $previsto;
        if (! $cobraFalta && $saldo < 0) {
            $saldo = 0;
            $previsto = $trabalhado > 0 ? min($previsto, $trabalhado) : 0;
        }

        return [
            'trabalhado' => $trabalhado,
            'previsto' => $previsto,
            'extra50' => $extra50,
            'extra100' => $extra100,
            'falta' => $falta,
            'saldo' => $saldo,
            'trabalhou' => $trabalhado > 0,
            'cobra_falta' => $cobraFalta,
        ];
    }

    /**
     * Indica se o dia entra no controle de faltas/banco negativo.
     */
    public function diaCobraFalta($colaborador, $diaYmd)
    {
        $inicio = $this->dataInicioControle($colaborador);
        if ($inicio === null) {
            return false;
        }
        if ($diaYmd < $inicio) {
            return false;
        }
        if (! empty($colaborador->admissao) && $diaYmd < substr($colaborador->admissao, 0, 10)) {
            return false;
        }
        if (! empty($colaborador->demissao) && $diaYmd > substr($colaborador->demissao, 0, 10)) {
            return false;
        }
        return true;
    }

    /**
     * Consolida uma competência (YYYY-MM) de um colaborador e grava em rh_horas.
     * Retorna o resumo em minutos.
     */
    public function calcularCompetencia($colaboradorId, $competencia)
    {
        $this->CI->load->model('rh_colaboradores_model');
        $this->CI->load->model('rh_ponto_model');
        $this->CI->load->model('rh_extras_model');

        $colaborador = $this->CI->rh_colaboradores_model->getById($colaboradorId);
        if (! $colaborador) {
            return null;
        }

        $jornada = ! empty($colaborador->jornada_id)
            ? $this->CI->rh_colaboradores_model->getJornada($colaborador->jornada_id)
            : null;

        $cargaDiaria = $jornada ? (int) $jornada->carga_diaria_min : 480;
        $tolerancia = $jornada ? (int) $jornada->tolerancia_min : 0;
        $diasEscala = $jornada ? explode(',', $jornada->dias_semana) : ['1', '2', '3', '4', '5'];
        $diasEscala = array_map('trim', $diasEscala);

        // Intervalo da competência
        [$ano, $mes] = explode('-', $competencia);
        $inicio = "$ano-$mes-01";
        $fim = date('Y-m-t', strtotime($inicio));

        $registros = $this->CI->rh_ponto_model->getPorPeriodo($colaboradorId, $inicio, $fim);

        // Agrupa batidas por dia
        $porDia = [];
        foreach ($registros as $r) {
            $dia = substr($r->data_hora, 0, 10);
            $porDia[$dia][] = $r;
        }

        // Dias com ausência aprovada (atestado/férias/folga) não geram falta/desconto
        $diasAbonados = $this->CI->rh_extras_model->diasAusenciaAprovada($colaboradorId, $inicio, $fim);

        $totais = [
            'dias_trabalhados' => 0,
            'minutos_trabalhados' => 0,
            'minutos_previstos' => 0,
            'minutos_extras_50' => 0,
            'minutos_extras_100' => 0,
            'minutos_faltas' => 0,
            'saldo_banco_min' => 0,
        ];

        $hoje = date('Y-m-d');
        $totalDias = (int) date('t', strtotime($inicio));
        for ($d = 1; $d <= $totalDias; $d++) {
            $dia = sprintf('%s-%s-%02d', $ano, $mes, $d);
            $diaSemana = (int) date('w', strtotime($dia)); // 0=dom .. 6=sáb
            $ehDiaUtil = in_array((string) $diaSemana, $diasEscala, true);
            $batidas = $porDia[$dia] ?? [];
            $abonado = isset($diasAbonados[$dia]);
            $cobraFalta = $this->diaCobraFalta($colaborador, $dia);

            // Fora do vínculo (antes da admissão / após demissão) não conta
            $foraVinculo = false;
            if (! empty($colaborador->admissao) && $dia < substr($colaborador->admissao, 0, 10)) {
                $foraVinculo = true;
            }
            if (! empty($colaborador->demissao) && $dia > substr($colaborador->demissao, 0, 10)) {
                $foraVinculo = true;
            }
            if ($foraVinculo) {
                continue;
            }

            // Dia útil sem batida no passado
            if (empty($batidas)) {
                if ($ehDiaUtil && $dia < $hoje && $cobraFalta) {
                    $totais['minutos_previstos'] += $cargaDiaria;
                    if (! $abonado) {
                        // Deve no banco de horas (e conta como falta operacional)
                        $totais['minutos_faltas'] += $cargaDiaria;
                        $totais['saldo_banco_min'] -= $cargaDiaria;
                    }
                }
                // Sem ponto_inicio: dia sem batida NÃO gera dívida
                continue;
            }

            $r = $this->calcularDia($batidas, $cargaDiaria, $tolerancia, $ehDiaUtil, $cobraFalta && ! $abonado);
            if ($abonado) {
                $r['falta'] = 0;
                if ($r['saldo'] < 0) {
                    $r['saldo'] = 0;
                }
            }
            if ($r['trabalhou']) {
                $totais['dias_trabalhados']++;
            }
            $totais['minutos_trabalhados'] += $r['trabalhado'];
            $totais['minutos_previstos'] += $r['previsto'];
            $totais['minutos_extras_50'] += $r['extra50'];
            $totais['minutos_extras_100'] += $r['extra100'];
            $totais['minutos_faltas'] += $r['falta'];
            $totais['saldo_banco_min'] += $r['saldo'];
        }

        $this->CI->rh_extras_model->salvarHoras($colaboradorId, $competencia, $totais);

        // Desconto R$ automático só se a config estiver ligada
        $this->gerarLancamentoFaltas($colaboradorId, $competencia);

        return $totais;
    }

    /**
     * Totais da semana civil atual (dom–sáb ou seg–dom via $inicioSemana).
     * Default: semana iniciando na segunda (ISO-ish com domingo=0 no PHP).
     *
     * @return array{inicio:string,fim:string,minutos_trabalhados:int,minutos_previstos:int,minutos_faltas:int,saldo_banco_min:int,minutos_extras_50:int,minutos_extras_100:int}
     */
    public function totaisSemana($colaboradorId, $referencia = null)
    {
        $this->CI->load->model('rh_colaboradores_model');
        $this->CI->load->model('rh_ponto_model');
        $this->CI->load->model('rh_extras_model');

        $colaborador = $this->CI->rh_colaboradores_model->getById($colaboradorId);
        if (! $colaborador) {
            return null;
        }

        $ref = $referencia ?: date('Y-m-d');
        $ts = strtotime($ref);
        $dow = (int) date('w', $ts); // 0=dom
        // Segunda como início: se domingo, volta 6 dias; senão volta (dow-1)
        $offsetSeg = $dow === 0 ? 6 : $dow - 1;
        $inicio = date('Y-m-d', strtotime("-{$offsetSeg} days", $ts));
        $fim = date('Y-m-d', strtotime('+6 days', strtotime($inicio)));

        $jornada = ! empty($colaborador->jornada_id)
            ? $this->CI->rh_colaboradores_model->getJornada($colaborador->jornada_id)
            : null;
        $cargaDiaria = $jornada ? (int) $jornada->carga_diaria_min : 480;
        $tolerancia = $jornada ? (int) $jornada->tolerancia_min : 0;
        $diasEscala = $jornada ? array_map('trim', explode(',', $jornada->dias_semana)) : ['1', '2', '3', '4', '5'];

        $registros = $this->CI->rh_ponto_model->getPorPeriodo($colaboradorId, $inicio, $fim);
        $porDia = [];
        foreach ($registros as $r) {
            $porDia[substr($r->data_hora, 0, 10)][] = $r;
        }
        $diasAbonados = $this->CI->rh_extras_model->diasAusenciaAprovada($colaboradorId, $inicio, $fim);

        $totais = [
            'inicio' => $inicio,
            'fim' => $fim,
            'minutos_trabalhados' => 0,
            'minutos_previstos' => 0,
            'minutos_faltas' => 0,
            'saldo_banco_min' => 0,
            'minutos_extras_50' => 0,
            'minutos_extras_100' => 0,
        ];

        $hoje = date('Y-m-d');
        for ($tsd = strtotime($inicio); $tsd <= strtotime($fim); $tsd += 86400) {
            $dia = date('Y-m-d', $tsd);
            $dw = (int) date('w', $tsd);
            $ehUtil = in_array((string) $dw, $diasEscala, true);
            $batidas = $porDia[$dia] ?? [];
            $abonado = isset($diasAbonados[$dia]);
            $cobraFalta = $this->diaCobraFalta($colaborador, $dia);

            if (empty($batidas)) {
                if ($ehUtil && $dia < $hoje && $cobraFalta && ! $abonado) {
                    $totais['minutos_previstos'] += $cargaDiaria;
                    $totais['minutos_faltas'] += $cargaDiaria;
                    $totais['saldo_banco_min'] -= $cargaDiaria;
                }
                continue;
            }
            $r = $this->calcularDia($batidas, $cargaDiaria, $tolerancia, $ehUtil, $cobraFalta && ! $abonado);
            if ($abonado) {
                $r['falta'] = 0;
                if ($r['saldo'] < 0) {
                    $r['saldo'] = 0;
                }
            }
            $totais['minutos_trabalhados'] += $r['trabalhado'];
            $totais['minutos_previstos'] += $r['previsto'];
            $totais['minutos_faltas'] += $r['falta'];
            $totais['saldo_banco_min'] += $r['saldo'];
            $totais['minutos_extras_50'] += $r['extra50'];
            $totais['minutos_extras_100'] += $r['extra100'];
        }

        return $totais;
    }

    /**
     * Gera (ou atualiza) o desconto automático de faltas na competência.
     * Só roda se `rh_falta_desconto_automatico` = 1.
     * Se desligado, remove lançamento automático anterior (se houver).
     *
     * @return int 1 criado | 2 atualizado | -1 removido | 0 sem alteração | false erro/desligado
     */
    public function gerarLancamentoFaltas($colaboradorId, $competencia)
    {
        $this->CI->load->model('rh_colaboradores_model');
        $this->CI->load->model('rh_extras_model');

        $colaborador = $this->CI->rh_colaboradores_model->getById($colaboradorId);
        $horas = $this->CI->rh_extras_model->getHoras($colaboradorId, $competencia);
        if (! $colaborador || ! $horas) {
            return false;
        }
        if (! empty($horas->fechado)) {
            return false; // competência fechada: não altera lançamentos
        }

        $existente = $this->CI->rh_extras_model->getLancamentoAutomatico($colaboradorId, $competencia, 'falta');

        // Flag desligada: não registra desconto em R$; limpa o automático antigo
        if (! $this->descontoFaltaAutomaticoAtivo()) {
            if ($existente) {
                $this->CI->rh_extras_model->deleteLancamento($existente->id);
                return -1;
            }
            return 0;
        }

        $min = (int) ($horas->minutos_faltas ?? 0);

        if ($min <= 0) {
            if ($existente) {
                $this->CI->rh_extras_model->deleteLancamento($existente->id);
                return -1;
            }
            return 0;
        }

        $valorHora = $this->valorHora($colaborador);
        if ($valorHora <= 0) {
            if ($existente) {
                $this->CI->rh_extras_model->deleteLancamento($existente->id);
                return -1;
            }
            return false;
        }

        $horasQtd = round($min / 60, 2);
        $valor = round($horasQtd * $valorHora, 2);
        $dados = [
            'colaborador_id' => $colaboradorId,
            'competencia' => $competencia,
            'tipo' => 'falta',
            'natureza' => 'desconto',
            'descricao' => 'Desconto por faltas (' . $this->minParaHoras($min) . ')',
            'quantidade' => $horasQtd,
            'valor' => $valor,
            'aprovado' => 1,
            'origem' => 'automatico',
            'referencia_id' => $horas->id,
        ];

        if ($existente) {
            $this->CI->rh_extras_model->editLancamento($dados, $existente->id);
            return 2;
        }

        $this->CI->rh_extras_model->addLancamento($dados);
        return 1;
    }

    /**
     * Gera lançamentos financeiros de hora extra a partir da consolidação.
     * Usa o valor_hora do colaborador (ou deriva de salario_base / 220h).
     * Só cria se ainda não houver lançamento automático de extra na competência.
     */
    public function gerarLancamentosExtras($colaboradorId, $competencia)
    {
        $this->CI->load->model('rh_colaboradores_model');
        $this->CI->load->model('rh_extras_model');

        $colaborador = $this->CI->rh_colaboradores_model->getById($colaboradorId);
        $horas = $this->CI->rh_extras_model->getHoras($colaboradorId, $competencia);
        if (! $colaborador || ! $horas) {
            return false;
        }

        $valorHora = $this->valorHora($colaborador);
        if ($valorHora <= 0) {
            return false;
        }

        // Fatores e regra de aprovação vêm da config CLT (padrão: 50%/100%, sempre pendente).
        $this->CI->load->library('rh_clt');
        $fator50 = $this->CI->rh_clt->fatorExtra50();
        $fator100 = $this->CI->rh_clt->fatorExtra100();
        $requerAprov = $this->CI->rh_clt->heRequerAprovacao();

        $criados = 0;
        $mapa = [
            'minutos_extras_50' => ['fator' => $fator50, 'desc' => 'Horas extras 50%'],
            'minutos_extras_100' => ['fator' => $fator100, 'desc' => 'Horas extras 100%'],
        ];

        foreach ($mapa as $campo => $cfg) {
            $min = (int) $horas->$campo;
            if ($min <= 0) {
                continue;
            }
            // Não duplica se já existir lançamento automático desta referência/descrição.
            if ($this->jaExisteExtraAutomatico($colaboradorId, $competencia, $cfg['desc'], $horas->id)) {
                continue;
            }
            $horasQtd = round($min / 60, 2);
            $valor = round($horasQtd * $valorHora * $cfg['fator'], 2);

            $this->CI->rh_extras_model->addLancamento([
                'colaborador_id' => $colaboradorId,
                'competencia' => $competencia,
                'tipo' => 'hora_extra',
                'natureza' => 'provento',
                'descricao' => $cfg['desc'] . ' (' . $this->minParaHoras($min) . ')',
                'quantidade' => $horasQtd,
                'valor' => $valor,
                // Horas extras sempre nascem pendentes se a config exigir aprovação.
                'aprovado' => $requerAprov ? 0 : 0,
                'origem' => 'automatico',
                'referencia_id' => $horas->id,
            ]);
            $criados++;
        }

        return $criados;
    }

    /** Evita duplicar HE automática na mesma competência/referência. */
    private function jaExisteExtraAutomatico($colaboradorId, $competencia, $descPrefixo, $referenciaId)
    {
        if (! $this->CI->db->table_exists('rh_lancamentos')) {
            return false;
        }
        $this->CI->db->where('colaborador_id', $colaboradorId);
        $this->CI->db->where('competencia', $competencia);
        $this->CI->db->where('tipo', 'hora_extra');
        $this->CI->db->where('origem', 'automatico');
        if ($referenciaId) {
            $this->CI->db->where('referencia_id', $referenciaId);
        }
        $this->CI->db->like('descricao', $descPrefixo, 'after');
        return $this->CI->db->count_all_results('rh_lancamentos') > 0;
    }

    /** Valor da hora do colaborador (explícito ou derivado do salário / 220h). */
    public function valorHora($colaborador)
    {
        if (! empty($colaborador->valor_hora)) {
            return (float) $colaborador->valor_hora;
        }
        if (! empty($colaborador->salario_base)) {
            return round(((float) $colaborador->salario_base) / 220, 2);
        }
        return 0.0;
    }

    /** Converte minutos em "HHhMM" para exibição. */
    public function minParaHoras($min)
    {
        $min = (int) $min;
        $sinal = $min < 0 ? '-' : '';
        $min = abs($min);
        return sprintf('%s%02dh%02d', $sinal, intdiv($min, 60), $min % 60);
    }
}
