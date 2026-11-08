<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API v1 — Solicitações do colaborador ao RH: correção de ponto,
 * justificativa de falta, abono, férias, folga, atestado e licença.
 *
 * Espelha Colaborador::solicitarOcorrencia/solicitarAusencia: o registro vai
 * sempre para o colaborador do token com status "pendente", e uma batida só
 * pode ser vinculada se pertencer a ele.
 */
require_once __DIR__ . '/AreaApiController.php';

class ColaboradorSolicitacaoController extends AreaApiController
{
    private $colaborador = null;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('rh_colaboradores_model');
        $this->load->model('rh_ponto_model');
        $this->load->model('rh_extras_model');
    }

    // ------------------------------------------------------------------
    // Ocorrências
    // ------------------------------------------------------------------

    /** GET /api/v1/colaborador/ocorrencias */
    public function ocorrencias_get()
    {
        $user = $this->exigir('vAreaColaborador');
        $c = $this->colaborador($user);

        $this->ok(array_map([$this, 'ocorrenciaResumo'], $this->rh_extras_model->listarOcorrencias([
            'colaborador_id' => $c->id,
        ])), 'Minhas solicitações');
    }

    /**
     * POST /api/v1/colaborador/ocorrencias
     * { tipo: correcao_ponto|justificativa_falta|abono, data_referencia?, registro_id?,
     *   justificar_tipo?, correcao_tipo?, correcao_data_hora?, descricao?, anexo?, anexo_nome? }
     */
    public function ocorrencias_post()
    {
        $user = $this->exigir('vAreaColaborador');
        $c = $this->colaborador($user);
        $corpo = $this->corpo();

        $tipo = (string) ($corpo['tipo'] ?? '');
        if (! in_array($tipo, ['correcao_ponto', 'justificativa_falta', 'abono'], true)) {
            $this->invalido('Tipo de solicitação inválido.');
        }

        $descricao = $this->cortar($corpo['descricao'] ?? '', 2000);
        if ($descricao === null) {
            $this->invalido('Descreva o motivo da solicitação.');
        }

        $registroId = (int) ($corpo['registro_id'] ?? 0) ?: null;
        if ($registroId) {
            $bat = $this->rh_ponto_model->getById($registroId);
            if (! $bat || (int) $bat->colaborador_id !== (int) $c->id) {
                $registroId = null;
            }
        }

        $dataReferencia = (string) ($corpo['data_referencia'] ?? '');
        if ($dataReferencia !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataReferencia)) {
            $dataReferencia = '';
        }

        $anexo = $this->anexo($corpo);
        $temCorrecaoTipo = $this->db->field_exists('correcao_tipo', 'rh_ocorrencias');

        $dados = [
            'colaborador_id' => (int) $c->id,
            'tipo' => $tipo,
            'data_referencia' => $dataReferencia !== '' ? $dataReferencia : null,
            'registro_id' => $registroId,
            'descricao' => $descricao,
            'anexo_base64' => $anexo['base64'],
            'anexo_mime' => $anexo['mime'],
            'anexo_nome' => $anexo['nome'],
            'status' => 'pendente',
        ];

        $justificar = (string) ($corpo['justificar_tipo'] ?? '');
        if ($temCorrecaoTipo && in_array($justificar, ['entrada', 'saida', 'inicio_intervalo', 'fim_intervalo'], true)) {
            $dados['correcao_tipo'] = $justificar;
        }

        if ($temCorrecaoTipo) {
            $ct = (string) ($corpo['correcao_tipo'] ?? '');
            if (in_array($ct, ['entrada', 'saida', 'inicio_intervalo', 'fim_intervalo'], true)) {
                $dados['correcao_tipo'] = $ct;
            }
            $cdh = (string) ($corpo['correcao_data_hora'] ?? '');
            if ($cdh !== '' && strtotime($cdh)) {
                $dados['correcao_data_hora'] = date('Y-m-d H:i:s', strtotime($cdh));
                if (empty($dados['data_referencia'])) {
                    $dados['data_referencia'] = date('Y-m-d', strtotime($cdh));
                }
            }
        }

        $id = $this->rh_extras_model->addOcorrencia($dados, true);
        if (! $id) {
            $this->invalido('Não foi possível enviar a solicitação.');
        }

        log_info('API: colaborador ' . $c->id . ' solicitou ocorrência ' . $tipo);
        $this->notificarRh('Nova solicitação de ' . $tipo . ' de ' . $c->nome);

        $this->ok($this->ocorrenciaResumo($this->rh_extras_model->getOcorrencia($id)), 'Solicitação enviada para o RH.');
    }

    // ------------------------------------------------------------------
    // Ausências
    // ------------------------------------------------------------------

    /** GET /api/v1/colaborador/ausencias */
    public function ausencias_get()
    {
        $user = $this->exigir('vAreaColaborador');
        $c = $this->colaborador($user);

        $this->ok(array_map([$this, 'ausenciaResumo'], $this->rh_extras_model->listarAusencias([
            'colaborador_id' => $c->id,
        ])), 'Minhas folgas e férias');
    }

    /**
     * POST /api/v1/colaborador/ausencias
     * { tipo: ferias|folga|atestado|licenca, data_inicio, data_fim?, motivo?, anexo?, anexo_nome? }
     */
    public function ausencias_post()
    {
        $user = $this->exigir('vAreaColaborador');
        $c = $this->colaborador($user);
        $corpo = $this->corpo();

        $tipo = (string) ($corpo['tipo'] ?? '');
        if (! in_array($tipo, ['ferias', 'folga', 'atestado', 'licenca'], true)) {
            $this->invalido('Tipo de ausência inválido.');
        }

        $inicio = (string) ($corpo['data_inicio'] ?? '');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio) || ! strtotime($inicio)) {
            $this->invalido('Informe a data de início.');
        }
        $fim = (string) ($corpo['data_fim'] ?? '');
        if ($fim === '') {
            $fim = $inicio;
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fim) || ! strtotime($fim)) {
            $this->invalido('Informe uma data final válida.');
        }
        if (strtotime($fim) < strtotime($inicio)) {
            $this->invalido('A data final não pode ser anterior à inicial.');
        }

        $dias = (int) ((strtotime($fim) - strtotime($inicio)) / 86400) + 1;
        $anexo = $this->anexo($corpo);

        $id = $this->rh_extras_model->addAusencia([
            'colaborador_id' => (int) $c->id,
            'tipo' => $tipo,
            'data_inicio' => $inicio,
            'data_fim' => $fim,
            'dias' => $dias > 0 ? $dias : 1,
            'motivo' => $this->cortar($corpo['motivo'] ?? '', 2000),
            'anexo_base64' => $anexo['base64'],
            'anexo_mime' => $anexo['mime'],
            'anexo_nome' => $anexo['nome'],
            'status' => 'pendente',
        ], true);

        if (! $id) {
            $this->invalido('Não foi possível enviar a solicitação.');
        }

        log_info('API: colaborador ' . $c->id . ' solicitou ausência ' . $tipo);
        $this->notificarRh('Nova solicitação de ' . $tipo . ' de ' . $c->nome);

        $this->ok($this->ausenciaResumo($this->rh_extras_model->getAusencia($id)), 'Solicitação enviada para o RH.');
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

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

    /**
     * Anexo em data URL (imagem ou PDF até 5 MB), como o upload da web.
     * Aceita { anexo: dataUrl, anexo_nome }.
     */
    protected function anexo(array $corpo)
    {
        $vazio = ['base64' => null, 'mime' => null, 'nome' => null];
        $valor = trim((string) ($corpo['anexo'] ?? ''));
        if ($valor === '' || strlen($valor) > 7 * 1024 * 1024) {
            return $vazio;
        }
        if (! preg_match('#^data:([\w/+.-]+);base64,[A-Za-z0-9+/]+={0,2}$#', $valor, $m)) {
            return $vazio;
        }

        $bin = base64_decode(substr($valor, strlen($m[0])), true);
        if ($bin === false || strlen($bin) === 0 || strlen($bin) > 5 * 1024 * 1024) {
            return $vazio;
        }

        $permitidos = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        $mime = $m[1];
        if (! in_array($mime, $permitidos, true)) {
            // Confere o conteúdo em vez do prefixo declarado.
            $real = (new finfo(FILEINFO_MIME_TYPE))->buffer($bin);
            if (! in_array($real, $permitidos, true)) {
                return $vazio;
            }
            $mime = $real;
        }

        return [
            'base64' => 'data:' . $mime . ';base64,' . base64_encode($bin),
            'mime' => $mime,
            'nome' => $this->cortar($corpo['anexo_nome'] ?? '', 160) ?: 'anexo',
        ];
    }

    protected function ocorrenciaResumo($o)
    {
        if (! $o) {
            return null;
        }

        return [
            'id' => (int) $o->id,
            'tipo' => $o->tipo,
            'data_referencia' => $o->data_referencia ?? null,
            'correcao_tipo' => $o->correcao_tipo ?? null,
            'correcao_data_hora' => $o->correcao_data_hora ?? null,
            'descricao' => $o->descricao ?? null,
            'status' => $o->status,
            'resposta' => $o->resposta ?? null,
            'tem_anexo' => ! empty($o->anexo_base64),
            'created_at' => $o->created_at ?? null,
        ];
    }

    protected function ausenciaResumo($a)
    {
        if (! $a) {
            return null;
        }

        return [
            'id' => (int) $a->id,
            'tipo' => $a->tipo,
            'data_inicio' => $a->data_inicio,
            'data_fim' => $a->data_fim,
            'dias' => (int) ($a->dias ?? 0),
            'motivo' => $a->motivo ?? null,
            'status' => $a->status,
            'resposta' => $a->resposta ?? null,
            'tem_anexo' => ! empty($a->anexo_base64),
            'created_at' => $a->created_at ?? null,
        ];
    }

    /** Notifica o RH por e-mail — best-effort. */
    protected function notificarRh($mensagem)
    {
        try {
            $this->load->library('notificador');
            if (method_exists($this->notificador, 'dispararEvento')) {
                $this->notificador->dispararEvento('rh_solicitacao', ['mensagem' => $mensagem]);
            }
        } catch (Throwable $e) {
            log_message('error', 'Falha ao notificar RH: ' . $e->getMessage());
        }
    }
}
