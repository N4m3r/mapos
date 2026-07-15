<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Motor de cálculo de horas do RH.
 *
 * Transforma as batidas de ponto de um colaborador em horas trabalhadas,
 * horas extras (50%/100%), faltas e saldo de banco de horas, e consolida
 * o mês em `rh_horas`.
 *
 * Regras (Fase 1, configuráveis depois):
 *  - Minutos trabalhados = soma dos segmentos (entrada|fim_intervalo) →
 *    (inicio_intervalo|saida). O intervalo (almoço) não conta.
 *  - Dia útil = o dia da semana está em `jornada.dias_semana`.
 *  - Extra em dia útil (além da carga prevista) = 50%.
 *  - Trabalho em dia NÃO útil (folga/domingo) = 100%.
 *  - Falta = carga prevista não cumprida em dia útil (além da tolerância).
 *  - Saldo de banco (minuto) = trabalhado − previsto (pode ser +/−).
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
     * Calcula os totais de um dia.
     *
     * @param array $batidas         batidas do dia (ordenadas)
     * @param int   $cargaPrevista   minutos previstos no dia (0 se folga)
     * @param int   $tolerancia      tolerância em minutos
     * @param bool  $ehDiaUtil       se o dia está na escala
     */
    public function calcularDia(array $batidas, $cargaPrevista, $tolerancia = 0, $ehDiaUtil = true)
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
            } elseif ($diff < -$tolerancia) {
                $falta = abs($diff);
            }
        }

        return [
            'trabalhado' => $trabalhado,
            'previsto' => $previsto,
            'extra50' => $extra50,
            'extra100' => $extra100,
            'falta' => $falta,
            'saldo' => $trabalhado - $previsto,
            'trabalhou' => $trabalhado > 0,
        ];
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

        // Percorre todos os dias do mês (para contabilizar faltas em dias sem batida)
        $totalDias = (int) date('t', strtotime($inicio));
        for ($d = 1; $d <= $totalDias; $d++) {
            $dia = sprintf('%s-%s-%02d', $ano, $mes, $d);
            $diaSemana = (int) date('w', strtotime($dia)); // 0=dom .. 6=sáb
            $ehDiaUtil = in_array((string) $diaSemana, $diasEscala, true);
            $batidas = $porDia[$dia] ?? [];
            $abonado = isset($diasAbonados[$dia]);

            // Fora do vínculo (antes da admissão / após demissão) não conta falta
            $foraVinculo = false;
            if (! empty($colaborador->admissao) && $dia < substr($colaborador->admissao, 0, 10)) {
                $foraVinculo = true;
            }
            if (! empty($colaborador->demissao) && $dia > substr($colaborador->demissao, 0, 10)) {
                $foraVinculo = true;
            }

            // Dia útil sem nenhuma batida no passado = falta cheia; futuro = ignora
            // Ausência aprovada no dia = abonado (sem desconto de falta)
            if (empty($batidas)) {
                if ($ehDiaUtil && ! $foraVinculo && strtotime($dia) < strtotime(date('Y-m-d'))) {
                    $totais['minutos_previstos'] += $cargaDiaria;
                    if (! $abonado) {
                        $totais['minutos_faltas'] += $cargaDiaria;
                        $totais['saldo_banco_min'] -= $cargaDiaria;
                    }
                }
                continue;
            }

            $r = $this->calcularDia($batidas, $cargaDiaria, $tolerancia, $ehDiaUtil);
            if ($abonado) {
                $r['falta'] = 0;
            }
            if ($r['trabalhou']) {
                $totais['dias_trabalhados']++;
            }
            $totais['minutos_trabalhados'] += $r['trabalhado'];
            $totais['minutos_previstos'] += $r['previsto'];
            $totais['minutos_extras_50'] += $r['extra50'];
            $totais['minutos_extras_100'] += $r['extra100'];
            $totais['minutos_faltas'] += $r['falta'];
            $totais['saldo_banco_min'] += $abonado ? ($r['trabalhado'] - $r['previsto']) : $r['saldo'];
        }

        $this->CI->rh_extras_model->salvarHoras($colaboradorId, $competencia, $totais);

        // Desconto de falta financeiro: cria/atualiza lançamento automático
        $this->gerarLancamentoFaltas($colaboradorId, $competencia);

        return $totais;
    }

    /**
     * Gera (ou atualiza) o desconto automático de faltas na competência.
     * Fórmula: horas de falta × valor_hora (valor_hora ou salário/220).
     * Aprovado automaticamente para entrar no holerite/folha.
     * Se minutos_faltas = 0, remove o lançamento automático anterior.
     *
     * @return int 1 criado | 2 atualizado | -1 removido | 0 sem alteração | false erro
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
            // Sem base salarial: remove automático se existir e não cria
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
            // Atualiza valor se as horas mudaram (mantém aprovado=1 se já estava)
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
