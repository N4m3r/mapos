<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API v1 — Área do Colaborador / RH (autoatendimento).
 *
 * Espelha Colaborador.php: o colaborador lê apenas os próprios dados —
 * batidas, espelho, ocorrências, ausências, holerite e contato/PIX. O vínculo
 * com o cadastro de colaborador vem do token, nunca de um id enviado pelo app.
 */
require_once __DIR__ . '/AreaApiController.php';

class ColaboradorController extends AreaApiController
{
    /** @var object colaborador vinculado ao usuário do token */
    protected $colaborador;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('rh_colaboradores_model');
        $this->load->model('rh_ponto_model');
        $this->load->model('rh_extras_model');
        $this->load->helper('date');
    }

    // ------------------------------------------------------------------
    // Painel
    // ------------------------------------------------------------------

    /** GET /api/v1/colaborador — painel: batidas de hoje, totais e pendências. */
    public function index_get()
    {
        $user = $this->exigir('vAreaColaborador');
        $c = $this->colaborador($user);
        $this->load->library('rh_calculo');
        $competencia = date('Y-m');

        $this->ok([
            'colaborador' => $this->dadosBasicos($c),
            'batidasHoje' => $this->batidas($c->id, null),
            'proximoTipo' => $this->rh_ponto_model->proximoTipo($c->id),
            'totaisMes' => $this->rh_calculo->calcularCompetencia($c->id, $competencia),
            'totaisSemana' => $this->rh_calculo->totaisSemana($c->id),
            'horas' => $this->horas($c->id, $competencia),
            'pontoInicio' => $this->rh_calculo->dataInicioControle($c),
            'pendentes' => count($this->rh_extras_model->listarOcorrencias([
                'colaborador_id' => $c->id,
                'status' => 'pendente',
            ])),
            'podeBaterPonto' => $this->permission->checkPermission($user->level, 'baterPonto'),
            'hoje' => date('Y-m-d'),
        ], 'Minha área');
    }

    /** GET /api/v1/colaborador/espelho?competencia=AAAA-MM */
    public function espelho_get()
    {
        $user = $this->exigir('vAreaColaborador');
        $c = $this->colaborador($user);
        $this->load->library('rh_calculo');

        $competencia = $this->competencia($this->get('competencia'));
        [$ano, $mes] = explode('-', $competencia);
        $inicio = "$ano-$mes-01";
        $fim = date('Y-m-t', strtotime($inicio));
        $hoje = date('Y-m-d');

        $porDia = [];
        foreach ($this->rh_ponto_model->getPorPeriodo($c->id, $inicio, $fim) as $r) {
            $porDia[substr($r->data_hora, 0, 10)][] = (int) $r->id;
        }

        $jornada = ! empty($c->jornada_id) ? $this->rh_colaboradores_model->getJornada($c->jornada_id) : null;
        $carga = $jornada ? (int) $jornada->carga_diaria_min : 480;
        $tol = $jornada ? (int) $jornada->tolerancia_min : 0;
        $dias = $jornada ? array_map('trim', explode(',', $jornada->dias_semana)) : ['1', '2', '3', '4', '5'];
        $abonados = $this->rh_extras_model->diasAusenciaAprovada($c->id, $inicio, $fim);

        $linhas = [];
        $totalDias = (int) date('t', strtotime($inicio));
        for ($d = 1; $d <= $totalDias; $d++) {
            $dia = sprintf('%s-%s-%02d', $ano, $mes, $d);
            $dw = (int) date('w', strtotime($dia));
            $ehUtil = in_array((string) $dw, $dias, true);
            $ids = $porDia[$dia] ?? [];
            $batidas = $ids ? $this->batidas($c->id, $dia) : [];
            $abonado = isset($abonados[$dia]);
            $cobraFalta = $this->rh_calculo->diaCobraFalta($c, $dia);
            $calc = $this->rh_calculo->calcularDia($batidas, $carga, $tol, $ehUtil, $cobraFalta && ! $abonado);

            if (empty($batidas) && $ehUtil && $dia < $hoje && $cobraFalta && ! $abonado) {
                $calc['previsto'] = $carga;
                $calc['falta'] = $carga;
                $calc['saldo'] = -$carga;
            } elseif (empty($batidas)) {
                $calc['previsto'] = 0;
                $calc['falta'] = 0;
                $calc['saldo'] = 0;
            }

            $linhas[] = [
                'data' => $dia,
                'dia_semana' => $dw,
                'eh_util' => $ehUtil,
                'abonado' => $abonado,
                'cobra_falta' => $cobraFalta,
                'batidas' => $batidas,
                'calc' => $calc,
            ];
        }

        $this->ok([
            'competencia' => $competencia,
            'linhas' => $linhas,
            'totais' => $this->rh_calculo->calcularCompetencia($c->id, $competencia),
            'totaisSemana' => $this->rh_calculo->totaisSemana($c->id),
            'pontoInicio' => $this->rh_calculo->dataInicioControle($c),
            'horas' => $this->horas($c->id, $competencia),
        ], 'Meu espelho de ponto');
    }

    /** GET /api/v1/colaborador/batidas?data=AAAA-MM-DD */
    public function batidas_get()
    {
        $user = $this->exigir('vAreaColaborador');
        $c = $this->colaborador($user);
        $data = (string) $this->get('data');

        $this->ok([
            'data' => $this->dataValida($data) ? $data : date('Y-m-d'),
            'batidas' => $this->batidas($c->id, $this->dataValida($data) ? $data : null),
        ], 'Batidas do dia');
    }

    // ------------------------------------------------------------------
    // Holerite
    // ------------------------------------------------------------------

    /** GET /api/v1/colaborador/holerite?competencia=AAAA-MM */
    public function holerite_get()
    {
        $user = $this->exigir('vAreaColaborador');
        $c = $this->colaborador($user);
        $competencia = $this->competencia($this->get('competencia'));

        $this->ok($this->resumoHolerite($c, $competencia), 'Holerite de ' . $competencia);
    }

    /** GET /api/v1/colaborador/holerite/arquivo?competencia=AAAA-MM — PDF liberado. */
    public function holerite_arquivo_get()
    {
        $user = $this->exigir('vAreaColaborador');
        $c = $this->colaborador($user);
        $h = $this->rh_extras_model->getHolerite($c->id, $this->competencia($this->get('competencia')));

        if (! $h || empty($h->arquivo_base64)) {
            $this->naoEncontrada('Holerite não disponível.');
        }
        if ($this->db->field_exists('liberado_colaborador', 'rh_holerites') && empty($h->liberado_colaborador)) {
            $this->naoEncontrada('Holerite ainda não liberado pelo RH.');
        }

        $mime = $h->arquivo_mime ?: 'application/pdf';
        $this->servirBase64($h->arquivo_base64, $mime);
    }

    // ------------------------------------------------------------------
    // Meus dados
    // ------------------------------------------------------------------

    /** GET /api/v1/colaborador/dados */
    public function dados_get()
    {
        $user = $this->exigir('vAreaColaborador');
        $c = $this->colaborador($user);

        $this->ok($this->dadosBasicos($c) + [
            'email' => $c->email ?? null,
            'celular' => $c->celular ?? null,
            'pix_tipo' => $c->pix_tipo ?? null,
            'pix_chave' => $c->pix_chave ?? null,
            'admissao' => $c->admissao ?? null,
            'unidade' => $this->nomeUnidade($c),
        ], 'Meus dados');
    }

    /** PUT /api/v1/colaborador/dados { email?, celular?, pix_tipo?, pix_chave? } */
    public function dados_put()
    {
        $user = $this->exigir('vAreaColaborador');
        $c = $this->colaborador($user);
        $corpo = $this->corpo();

        $dados = [];
        if (array_key_exists('email', $corpo)) {
            $email = trim((string) $corpo['email']);
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->invalido('E-mail inválido.');
            }
            $dados['email'] = $email !== '' ? $email : null;
        }
        if (array_key_exists('celular', $corpo)) {
            $digitos = preg_replace('/\D/', '', (string) $corpo['celular']);
            if ($digitos !== '' && (strlen($digitos) < 10 || strlen($digitos) > 13)) {
                $this->invalido('Celular inválido. Use DDD + número.');
            }
            $dados['celular'] = $digitos !== '' ? $digitos : null;
        }
        if (array_key_exists('pix_tipo', $corpo)) {
            $tipo = (string) $corpo['pix_tipo'];
            if (! in_array($tipo, ['', 'cpf', 'cnpj', 'email', 'telefone', 'aleatoria'], true)) {
                $this->invalido('Tipo de chave PIX inválido.');
            }
            $dados['pix_tipo'] = $tipo !== '' ? $tipo : null;
        }
        if (array_key_exists('pix_chave', $corpo)) {
            $dados['pix_chave'] = $this->cortar($corpo['pix_chave'], 140);
        }
        if (! $dados) {
            $this->invalido('Nada para atualizar.');
        }

        if (! $this->rh_colaboradores_model->edit($dados, $c->id)) {
            $this->invalido('Não foi possível atualizar seus dados.');
        }

        $atual = $this->rh_colaboradores_model->getById($c->id);
        $this->ok($this->dadosBasicos($atual) + [
            'email' => $atual->email ?? null,
            'celular' => $atual->celular ?? null,
            'pix_tipo' => $atual->pix_tipo ?? null,
            'pix_chave' => $atual->pix_chave ?? null,
        ], 'Dados atualizados.');
    }

    /** GET /api/v1/colaborador/foto — foto do próprio colaborador. */
    public function foto_get()
    {
        $user = $this->exigir('vAreaColaborador');
        $c = $this->colaborador($user);

        if (empty($c->foto_base64)) {
            $this->naoEncontrada('Sem foto cadastrada.');
        }
        $this->servirBase64($c->foto_base64, $c->foto_mime ?: 'image/jpeg', true);
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    /** Colaborador ativo vinculado ao usuário do token (ou 403). */
    protected function colaborador($user)
    {
        if ($this->colaborador === null) {
            if (! $this->rh_colaboradores_model->suportado()) {
                $this->response([
                    'status' => false,
                    'message' => 'O módulo de RH ainda não foi ativado. Procure o RH.',
                ], REST_Controller::HTTP_FORBIDDEN);
            }
            $this->colaborador = $this->rh_colaboradores_model->getByUsuario((int) $user->usuario->idUsuarios);
            if (! $this->colaborador) {
                $this->response([
                    'status' => false,
                    'message' => 'Seu usuário não está vinculado a um cadastro de colaborador ativo. Procure o RH.',
                ], REST_Controller::HTTP_FORBIDDEN);
            }
        }

        return $this->colaborador;
    }

    protected function dadosBasicos($c)
    {
        $nome = trim((string) ($c->nome ?? ''));

        return [
            'id' => (int) $c->id,
            'nome' => $nome,
            'primeiroNome' => $nome !== '' ? explode(' ', $nome)[0] : '',
            'cargo' => $c->cargo ?? null,
            'fotoUrl' => empty($c->foto_base64) ? null : 'colaborador/foto',
        ];
    }

    /** Batidas de um dia (ou de hoje) com rótulo em português. */
    protected function batidas($colaboradorId, $data)
    {
        $labels = [
            'entrada' => 'Entrada',
            'saida' => 'Saída',
            'inicio_intervalo' => 'Início do intervalo',
            'fim_intervalo' => 'Fim do intervalo',
        ];

        $out = [];
        foreach ($this->rh_ponto_model->getDoDia($colaboradorId, $data) as $b) {
            $out[] = [
                'id' => (int) $b->id,
                'tipo' => $b->tipo,
                'label' => $labels[$b->tipo] ?? $b->tipo,
                'hora' => date('H:i', strtotime($b->data_hora)),
                'data_hora' => $b->data_hora,
                'latitude' => $b->latitude ?? null,
                'longitude' => $b->longitude ?? null,
                'os_id' => $b->os_id ?? null,
                'obra_id' => $b->obra_id ?? null,
                'dentro_geofence' => $b->dentro_geofence ?? null,
                'distancia_metros' => $b->distancia_metros ?? null,
            ];
        }

        return $out;
    }

    /** Consolidação de horas da competência (só números, sem itens). */
    protected function horas($colaboradorId, $competencia)
    {
        $h = $this->rh_extras_model->getHoras($colaboradorId, $competencia);
        if (! $h) {
            return null;
        }

        return [
            'normais_min' => (int) ($h->normais_min ?? 0),
            'extras_50_min' => (int) ($h->extras_50_min ?? 0),
            'extras_100_min' => (int) ($h->extras_100_min ?? 0),
            'saldo_min' => (int) ($h->saldo_min ?? 0),
            'fechado' => ! empty($h->fechado),
        ];
    }

    /** Resumo do holerite como o controller web monta (base + legais). */
    protected function resumoHolerite($c, $competencia)
    {
        $this->load->library('rh_clt');
        $resumo = $this->rh_extras_model->resumoCompetencia($c->id, $competencia);
        $salario = (float) ($c->salario_base ?? 0);
        $proventos = $salario + (float) $resumo['proventos'];
        $legais = $this->rh_clt->descontosLegais($c, $proventos);

        $itens = [];
        if ($salario > 0) {
            $itens[] = ['tipo' => 'salario', 'natureza' => 'provento', 'descricao' => 'Salário base', 'valor' => $salario];
        }
        foreach ($resumo['itens'] as $i) {
            $itens[] = [
                'tipo' => $i->tipo ?? null,
                'natureza' => $i->natureza ?? null,
                'descricao' => $i->descricao ?? null,
                'valor' => (float) ($i->valor ?? 0),
            ];
        }
        foreach ($legais['itens'] as $i) {
            $itens[] = is_object($i)
                ? ['tipo' => $i->tipo ?? null, 'natureza' => $i->natureza ?? null, 'descricao' => $i->descricao ?? null, 'valor' => (float) ($i->valor ?? 0)]
                : (array) $i;
        }

        $holerite = $this->rh_extras_model->getHolerite($c->id, $competencia);
        $arquivo = null;
        if ($holerite && ! empty($holerite->arquivo_base64)) {
            $liberado = ! $this->db->field_exists('liberado_colaborador', 'rh_holerites') || ! empty($holerite->liberado_colaborador);
            if ($liberado) {
                $arquivo = [
                    'nome' => $holerite->arquivo_nome ?: 'holerite.pdf',
                    'mime' => $holerite->arquivo_mime ?: 'application/pdf',
                    'url' => 'colaborador/holerite/arquivo?competencia=' . $competencia,
                ];
            }
        }

        return [
            'competencia' => $competencia,
            'resumo' => [
                'proventos' => $proventos,
                'descontos' => (float) $resumo['descontos'] + (float) $legais['total_descontos'],
                'liquido' => $proventos - ((float) $resumo['descontos'] + (float) $legais['total_descontos']),
                'fgts' => $legais['fgts'] ?? null,
                'itens' => $itens,
            ],
            'arquivo' => $arquivo,
        ];
    }

    protected function nomeUnidade($c)
    {
        if (empty($c->unidade_id)) {
            return null;
        }
        $u = $this->rh_colaboradores_model->getUnidade($c->unidade_id);

        return $u->nome ?? null;
    }

    /** Competência AAAA-MM válida ou a atual. */
    protected function competencia($valor)
    {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $valor) ? (string) $valor : date('Y-m');
    }

    protected function dataValida($valor)
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $valor) && strtotime((string) $valor);
    }
}
