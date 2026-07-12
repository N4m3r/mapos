<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Área do Colaborador — autoatendimento (mobile-first).
 *
 * O colaborador vê apenas os PRÓPRIOS dados: espelho de ponto, horas/extras,
 * holerite/demonstrativo, e solicita correções/justificativas e folgas/férias
 * (que vão para aprovação do RH). Bater ponto fica em /ponto.
 */
class Colaborador extends MY_Controller
{
    private $colaborador;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('rh_colaboradores_model');
        $this->load->model('rh_ponto_model');
        $this->load->model('rh_extras_model');
        $this->load->helper('date');

        if (! $this->session->userdata('id_admin')) {
            redirect('login');
        }
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vAreaColaborador')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para acessar a Área do Colaborador.');
            redirect(base_url());
        }
        if (! $this->rh_colaboradores_model->suportado()) {
            $this->session->set_flashdata('error', 'O módulo de RH ainda não foi ativado. Procure o RH.');
            redirect(base_url());
        }

        $this->colaborador = $this->rh_colaboradores_model->getByUsuario($this->session->userdata('id_admin'));
        if (! $this->colaborador) {
            $this->session->set_flashdata('error', 'Seu usuário não está vinculado a um cadastro de colaborador. Procure o RH.');
            redirect(base_url());
        }
    }

    private function baseData($titulo)
    {
        return [
            'colaborador' => $this->colaborador,
            'titulo' => $titulo,
            'pode_bater_ponto' => $this->permission->checkPermission($this->session->userdata('permissao'), 'baterPonto'),
            'pode_ver_sistema' => $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs'),
        ];
    }

    public function index()
    {
        $this->load->library('rh_calculo');
        $competencia = date('Y-m');
        $data = $this->baseData('Minha Área');
        $data['batidas_hoje'] = $this->rh_ponto_model->getDoDia($this->colaborador->id);
        $data['proximo_tipo'] = $this->rh_ponto_model->proximoTipo($this->colaborador->id);
        $data['horas'] = $this->rh_extras_model->getHoras($this->colaborador->id, $competencia);
        $data['pendentes'] = count($this->rh_extras_model->listarOcorrencias([
            'colaborador_id' => $this->colaborador->id, 'status' => 'pendente',
        ]));
        $data['calc'] = $this->rh_calculo;
        $this->load->view('colaborador/dashboard', $data);
    }

    public function espelho($competencia = null)
    {
        $this->load->library('rh_calculo');
        $competencia = $competencia ?: date('Y-m');
        [$ano, $mes] = explode('-', $competencia);
        $inicio = "$ano-$mes-01";
        $fim = date('Y-m-t', strtotime($inicio));

        $registros = $this->rh_ponto_model->getPorPeriodo($this->colaborador->id, $inicio, $fim);
        $porDia = [];
        foreach ($registros as $r) {
            $porDia[substr($r->data_hora, 0, 10)][] = $r;
        }

        $jornada = ! empty($this->colaborador->jornada_id)
            ? $this->rh_colaboradores_model->getJornada($this->colaborador->jornada_id) : null;
        $carga = $jornada ? (int) $jornada->carga_diaria_min : 480;
        $tol = $jornada ? (int) $jornada->tolerancia_min : 0;
        $dias = $jornada ? array_map('trim', explode(',', $jornada->dias_semana)) : ['1', '2', '3', '4', '5'];

        $linhas = [];
        $totalDias = (int) date('t', strtotime($inicio));
        for ($d = 1; $d <= $totalDias; $d++) {
            $dia = sprintf('%s-%s-%02d', $ano, $mes, $d);
            $dw = (int) date('w', strtotime($dia));
            $ehUtil = in_array((string) $dw, $dias, true);
            $bat = $porDia[$dia] ?? [];
            $linhas[] = [
                'data' => $dia, 'dia_semana' => $dw, 'eh_util' => $ehUtil,
                'batidas' => $bat,
                'calc' => $this->rh_calculo->calcularDia($bat, $carga, $tol, $ehUtil),
            ];
        }

        $data = $this->baseData('Meu Espelho de Ponto');
        $data['competencia'] = $competencia;
        $data['linhas'] = $linhas;
        $data['totais'] = $this->rh_calculo->calcularCompetencia($this->colaborador->id, $competencia);
        $data['calc'] = $this->rh_calculo;
        $this->load->view('colaborador/espelho', $data);
    }

    // ------------------------------------------------------------------
    // Ocorrências (correção de ponto / justificativa de falta)
    // ------------------------------------------------------------------

    public function ocorrencias()
    {
        $data = $this->baseData('Justificativas e Correções');
        $data['ocorrencias'] = $this->rh_extras_model->listarOcorrencias([
            'colaborador_id' => $this->colaborador->id,
        ]);
        $this->load->view('colaborador/ocorrencias', $data);
    }

    public function solicitarOcorrencia()
    {
        $tipo = $this->input->post('tipo');
        if (! in_array($tipo, ['correcao_ponto', 'justificativa_falta', 'abono'], true)) {
            $this->session->set_flashdata('error', 'Tipo inválido.');
            redirect(site_url('colaborador/ocorrencias'));
        }
        $anexo = $this->arquivoParaBase64('anexo');
        $dados = [
            'colaborador_id' => $this->colaborador->id,
            'tipo' => $tipo,
            'data_referencia' => $this->input->post('data_referencia') ?: null,
            'registro_id' => $this->input->post('registro_id') ?: null,
            'descricao' => $this->input->post('descricao'),
            'anexo_base64' => $anexo['base64'],
            'anexo_mime' => $anexo['mime'],
            'anexo_nome' => $anexo['nome'],
            'status' => 'pendente',
        ];
        // Correção de ponto: batida desejada (aplicada automaticamente na aprovação).
        // Só grava se a migration 20260712000005 tiver rodado.
        if ($tipo === 'correcao_ponto' && $this->db->field_exists('correcao_tipo', 'rh_ocorrencias')) {
            $ct = $this->input->post('correcao_tipo');
            $cdh = $this->input->post('correcao_data_hora');
            if (in_array($ct, ['entrada', 'saida', 'inicio_intervalo', 'fim_intervalo'], true) && $cdh) {
                $dados['correcao_tipo'] = $ct;
                $dados['correcao_data_hora'] = date('Y-m-d H:i:s', strtotime($cdh));
                if (empty($dados['data_referencia'])) {
                    $dados['data_referencia'] = date('Y-m-d', strtotime($cdh));
                }
            }
        }
        $this->rh_extras_model->addOcorrencia($dados);
        log_info('Colaborador ' . $this->colaborador->id . ' solicitou ocorrência ' . $tipo);
        $this->notificarRh('Nova solicitação de ' . $tipo . ' de ' . $this->colaborador->nome);
        $this->session->set_flashdata('success', 'Solicitação enviada para o RH.');
        redirect(site_url('colaborador/ocorrencias'));
    }

    // ------------------------------------------------------------------
    // Ausências (folga / férias / atestado)
    // ------------------------------------------------------------------

    public function ausencias()
    {
        $data = $this->baseData('Folgas e Férias');
        $data['ausencias'] = $this->rh_extras_model->listarAusencias([
            'colaborador_id' => $this->colaborador->id,
        ]);
        $this->load->view('colaborador/ausencias', $data);
    }

    public function solicitarAusencia()
    {
        $tipo = $this->input->post('tipo');
        if (! in_array($tipo, ['ferias', 'folga', 'atestado', 'licenca'], true)) {
            $this->session->set_flashdata('error', 'Tipo inválido.');
            redirect(site_url('colaborador/ausencias'));
        }
        $inicio = $this->input->post('data_inicio');
        $fim = $this->input->post('data_fim') ?: $inicio;
        if (! $inicio) {
            $this->session->set_flashdata('error', 'Informe a data.');
            redirect(site_url('colaborador/ausencias'));
        }
        $dias = (int) ((strtotime($fim) - strtotime($inicio)) / 86400) + 1;
        $anexo = $this->arquivoParaBase64('anexo');
        $this->rh_extras_model->addAusencia([
            'colaborador_id' => $this->colaborador->id,
            'tipo' => $tipo,
            'data_inicio' => $inicio,
            'data_fim' => $fim,
            'dias' => $dias > 0 ? $dias : 1,
            'motivo' => $this->input->post('motivo'),
            'anexo_base64' => $anexo['base64'],
            'anexo_mime' => $anexo['mime'],
            'anexo_nome' => $anexo['nome'],
            'status' => 'pendente',
        ]);
        log_info('Colaborador ' . $this->colaborador->id . ' solicitou ausência ' . $tipo);
        $this->notificarRh('Nova solicitação de ' . $tipo . ' de ' . $this->colaborador->nome);
        $this->session->set_flashdata('success', 'Solicitação enviada para o RH.');
        redirect(site_url('colaborador/ausencias'));
    }

    // ------------------------------------------------------------------
    // Holerite / demonstrativo
    // ------------------------------------------------------------------

    public function holerite($competencia = null)
    {
        $competencia = $competencia ?: date('Y-m');
        $data = $this->baseData('Holerite / Demonstrativo');
        $data['competencia'] = $competencia;
        $data['resumo'] = $this->rh_extras_model->resumoCompetencia($this->colaborador->id, $competencia);
        $data['holerite'] = $this->rh_extras_model->getHolerite($this->colaborador->id, $competencia);
        $this->load->view('colaborador/holerite', $data);
    }

    /** Baixa o PDF oficial do holerite DO PRÓPRIO colaborador. */
    public function baixarHolerite($competencia = null)
    {
        $competencia = $competencia ?: date('Y-m');
        $h = $this->rh_extras_model->getHolerite($this->colaborador->id, $competencia);
        if (! $h || empty($h->arquivo_base64)) {
            show_404();
            return;
        }
        $base64 = $h->arquivo_base64;
        if (preg_match('/^data:([\w\/\+\.\-]+);base64,/', $base64, $m)) {
            $mime = $m[1];
            $dados = substr($base64, strlen($m[0]));
        } else {
            $mime = $h->arquivo_mime ?: 'application/pdf';
            $dados = $base64;
        }
        $bin = base64_decode($dados, true);
        if ($bin === false) {
            show_404();
            return;
        }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strlen($bin));
        header('Content-Disposition: inline; filename="' . ($h->arquivo_nome ?: 'holerite.pdf') . '"');
        echo $bin;
        exit;
    }

    // ------------------------------------------------------------------
    // Meus dados (edição limitada: contato/PIX)
    // ------------------------------------------------------------------

    public function meusDados()
    {
        if ($this->input->post('salvar')) {
            $this->rh_colaboradores_model->edit([
                'email' => $this->input->post('email'),
                'celular' => $this->input->post('celular'),
                'pix_tipo' => $this->input->post('pix_tipo'),
                'pix_chave' => $this->input->post('pix_chave'),
            ], $this->colaborador->id);
            $this->session->set_flashdata('success', 'Dados atualizados.');
            redirect(site_url('colaborador/meusDados'));
        }
        $data = $this->baseData('Meus Dados');
        $this->load->view('colaborador/meus_dados', $data);
    }

    /** Serve o anexo de uma ocorrência/ausência DO PRÓPRIO colaborador. */
    public function anexo($tipo = null, $id = null)
    {
        $registro = $tipo === 'ocorrencia'
            ? $this->rh_extras_model->getOcorrencia($id)
            : $this->rh_extras_model->getAusencia($id);
        if (! $registro || $registro->colaborador_id != $this->colaborador->id || empty($registro->anexo_base64)) {
            show_404();
            return;
        }
        $base64 = $registro->anexo_base64;
        if (preg_match('/^data:([\w\/\+\.\-]+);base64,/', $base64, $m)) {
            $mime = $m[1];
            $dados = substr($base64, strlen($m[0]));
        } else {
            $mime = $registro->anexo_mime ?: 'application/octet-stream';
            $dados = $base64;
        }
        $bin = base64_decode($dados, true);
        if ($bin === false) {
            show_error('Erro ao decodificar anexo', 500);
            return;
        }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strlen($bin));
        echo $bin;
        exit;
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** Converte um upload em data URI base64 (padrão base64-no-banco). */
    private function arquivoParaBase64($campo)
    {
        $vazio = ['base64' => null, 'mime' => null, 'nome' => null];
        if (empty($_FILES[$campo]) || $_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
            return $vazio;
        }
        if ($_FILES[$campo]['size'] > 5 * 1024 * 1024) {
            return $vazio; // limite 5MB
        }
        $conteudo = file_get_contents($_FILES[$campo]['tmp_name']);
        if ($conteudo === false) {
            return $vazio;
        }
        $mime = mime_content_type($_FILES[$campo]['tmp_name']) ?: $_FILES[$campo]['type'];
        return [
            'base64' => 'data:' . $mime . ';base64,' . base64_encode($conteudo),
            'mime' => $mime,
            'nome' => $_FILES[$campo]['name'],
        ];
    }

    /** Notifica o RH por e-mail (fila) sobre uma nova solicitação. */
    private function notificarRh($mensagem)
    {
        try {
            $this->load->library('notificador');
            if (method_exists($this->notificador, 'dispararEvento')) {
                $this->notificador->dispararEvento('rh_solicitacao', ['mensagem' => $mensagem]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Falha ao notificar RH: ' . $e->getMessage());
        }
    }
}
