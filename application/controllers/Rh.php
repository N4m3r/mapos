<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Módulo de RH — painel administrativo.
 *
 * Colaboradores, unidades, jornadas, biometria facial de referência, espelho
 * de ponto, lançamentos financeiros/extras e aprovações (ocorrências/ausências).
 */
class Rh extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->model('rh_colaboradores_model');
        $this->load->model('rh_ponto_model');
        $this->load->model('rh_extras_model');
        $this->data['menuRh'] = 'RH';

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vRh')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para acessar o RH.');
            redirect(base_url());
        }

        if (! $this->rh_colaboradores_model->suportado()) {
            $this->session->set_flashdata('error', 'As tabelas do RH ainda não foram criadas. Rode "Atualizar Banco" em Configurações.');
            redirect(base_url());
        }
    }

    private function exigir($perm, $msg)
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), $perm)) {
            $this->session->set_flashdata('error', $msg);
            redirect(site_url('rh'));
        }
    }

    // =====================================================================
    // Dashboard
    // =====================================================================

    public function index()
    {
        $this->data['total_colaboradores'] = $this->rh_colaboradores_model->contarAtivos();
        $this->data['presentes_hoje'] = $this->rh_ponto_model->contarPresentesHoje();
        $this->data['pendencias'] = $this->rh_extras_model->contarPendencias();
        $this->data['ultimos_registros'] = $this->rh_ponto_model->ultimosRegistros(12);
        $this->data['aniversariantes'] = $this->rh_colaboradores_model->aniversariantesDoMes();

        $this->data['view'] = 'rh/dashboard';
        return $this->layout();
    }

    // =====================================================================
    // Colaboradores
    // =====================================================================

    public function colaboradores()
    {
        $this->data['filtro_situacao'] = $this->input->get('situacao');
        $this->data['busca'] = $this->input->get('busca');
        $this->data['colaboradores'] = $this->rh_colaboradores_model->listar([
            'situacao' => $this->data['filtro_situacao'],
            'busca' => $this->data['busca'],
        ]);
        $this->data['view'] = 'rh/colaboradores';
        return $this->layout();
    }

    public function adicionarColaborador()
    {
        $this->exigir('eRh', 'Você não tem permissão para cadastrar colaboradores.');
        $this->data['custom_error'] = '';

        if ($this->input->post('nome')) {
            $nome = trim($this->input->post('nome'));
            if ($nome === '') {
                $this->data['custom_error'] = '<div class="form_error"><p>Informe o nome.</p></div>';
            } else {
                $dados = $this->dadosColaboradorDoPost();
                if ($foto = $this->fotoColaboradorUpload()) {
                    $dados = array_merge($dados, $foto);
                }
                $id = $this->rh_colaboradores_model->add($dados, true);
                if ($id) {
                    log_info('RH: cadastrou colaborador ID ' . $id);
                    $this->session->set_flashdata('success', 'Colaborador cadastrado com sucesso!');
                    redirect(site_url('rh/editarColaborador/' . $id));
                }
                $this->data['custom_error'] = '<div class="form_error"><p>Ocorreu um erro ao salvar.</p></div>';
            }
        }

        $this->data['unidades'] = $this->rh_colaboradores_model->listarUnidades();
        $this->data['jornadas'] = $this->rh_colaboradores_model->listarJornadas();
        $this->data['usuarios'] = $this->usuariosDisponiveis();
        $this->data['view'] = 'rh/form_colaborador';
        return $this->layout();
    }

    public function editarColaborador($id = null)
    {
        $this->exigir('eRh', 'Você não tem permissão para editar colaboradores.');
        $id = $id ?: $this->input->post('id');
        $colaborador = $this->rh_colaboradores_model->getById($id);
        if (! $colaborador) {
            $this->session->set_flashdata('error', 'Colaborador não encontrado.');
            redirect(site_url('rh/colaboradores'));
        }
        $this->data['custom_error'] = '';

        if ($this->input->post('nome')) {
            $dados = $this->dadosColaboradorDoPost();
            if ($foto = $this->fotoColaboradorUpload()) {
                $dados = array_merge($dados, $foto);
            } elseif ($this->input->post('remover_foto')) {
                $dados['foto_base64'] = null;
                $dados['foto_mime'] = null;
            }
            if ($this->rh_colaboradores_model->edit($dados, $id)) {
                log_info('RH: editou colaborador ID ' . $id);
                $this->session->set_flashdata('success', 'Colaborador atualizado com sucesso!');
                redirect(site_url('rh/editarColaborador/' . $id));
            }
            $this->data['custom_error'] = '<div class="form_error"><p>Ocorreu um erro ao salvar.</p></div>';
        }

        $this->data['colaborador'] = $colaborador;
        $this->data['unidades'] = $this->rh_colaboradores_model->listarUnidades();
        $this->data['jornadas'] = $this->rh_colaboradores_model->listarJornadas();
        $this->data['usuarios'] = $this->usuariosDisponiveis($colaborador->usuarios_id);
        $this->data['tem_biometria'] = $this->rh_colaboradores_model->temBiometria($id);
        $this->data['view'] = 'rh/form_colaborador';
        return $this->layout();
    }

    public function excluirColaborador()
    {
        $this->exigir('eRh', 'Você não tem permissão para excluir colaboradores.');
        $id = $this->input->post('id');
        if ($id) {
            $this->rh_colaboradores_model->delete($id);
            log_info('RH: excluiu colaborador ID ' . $id);
            $this->session->set_flashdata('success', 'Colaborador excluído.');
        }
        redirect(site_url('rh/colaboradores'));
    }

    /**
     * Ficha do colaborador — hub que consolida ponto, ausências, lançamentos,
     * ocorrências e biometria num só lugar (gestão por colaborador).
     */
    public function ficha($id = null)
    {
        $colaborador = $this->rh_colaboradores_model->getById($id);
        if (! $colaborador) {
            $this->session->set_flashdata('error', 'Colaborador não encontrado.');
            redirect(site_url('rh/colaboradores'));
        }
        $competencia = $this->input->get('competencia') ?: date('Y-m');
        $this->load->library('rh_calculo');

        $this->data['colaborador'] = $colaborador;
        $this->data['unidade'] = $colaborador->unidade_id ? $this->rh_colaboradores_model->getUnidade($colaborador->unidade_id) : null;
        $this->data['jornada'] = $colaborador->jornada_id ? $this->rh_colaboradores_model->getJornada($colaborador->jornada_id) : null;
        $this->data['tem_biometria'] = $this->rh_colaboradores_model->temBiometria($id);
        $this->data['competencia'] = $competencia;
        $this->data['horas'] = $this->rh_calculo->calcularCompetencia($id, $competencia);
        $this->data['ultimas_batidas'] = $this->rh_ponto_model->ultimasDoColaborador($id, 12);
        $this->data['ausencias'] = $this->rh_extras_model->listarAusencias(['colaborador_id' => $id]);
        $this->data['lancamentos'] = $this->rh_extras_model->listarLancamentos($id, $competencia);
        $this->data['ocorrencias'] = $this->rh_extras_model->listarOcorrencias(['colaborador_id' => $id]);
        $this->data['resumo_financeiro'] = $this->rh_extras_model->resumoCompetencia($id, $competencia);
        $this->data['pode_financeiro'] = $this->permission->checkPermission($this->session->userdata('permissao'), 'vRhFinanceiro');
        $this->data['pode_editar'] = $this->permission->checkPermission($this->session->userdata('permissao'), 'eRh');

        $this->data['view'] = 'rh/ficha';
        return $this->layout();
    }

    /** RH registra uma batida manualmente (correção/lançamento retroativo). */
    public function registrarPontoManual()
    {
        $this->exigir('eRh', 'Sem permissão.');
        $colaboradorId = $this->input->post('colaborador_id');
        $dataHora = $this->input->post('data_hora');
        $tipo = $this->input->post('tipo');
        if (! $colaboradorId || ! $dataHora || ! in_array($tipo, ['entrada', 'saida', 'inicio_intervalo', 'fim_intervalo'], true)) {
            $this->session->set_flashdata('error', 'Dados incompletos para a batida manual.');
            redirect(site_url('rh/ficha/' . $colaboradorId));
        }
        $this->rh_ponto_model->registrar([
            'colaborador_id' => $colaboradorId,
            'data_hora' => date('Y-m-d H:i:s', strtotime($dataHora)),
            'tipo' => $tipo,
            'origem' => 'manual',
            'status' => 'ajustado',
            'observacao' => 'Lançada manualmente pelo RH',
            'registrado_por' => $this->session->userdata('id_admin'),
        ]);
        log_info('RH: batida manual colaborador ' . $colaboradorId);
        $this->session->set_flashdata('success', 'Batida registrada manualmente.');
        redirect(site_url('rh/ficha/' . $colaboradorId));
    }

    /** RH registra uma ausência em nome do colaborador (já aprovada). */
    public function salvarAusencia()
    {
        $this->exigir('eRh', 'Sem permissão.');
        $colaboradorId = $this->input->post('colaborador_id');
        $tipo = $this->input->post('tipo');
        $inicio = $this->input->post('data_inicio');
        $fim = $this->input->post('data_fim') ?: $inicio;
        if (! $colaboradorId || ! in_array($tipo, ['ferias', 'folga', 'atestado', 'licenca'], true) || ! $inicio) {
            $this->session->set_flashdata('error', 'Dados incompletos.');
            redirect(site_url('rh/ficha/' . $colaboradorId));
        }
        $dias = (int) ((strtotime($fim) - strtotime($inicio)) / 86400) + 1;
        $this->rh_extras_model->addAusencia([
            'colaborador_id' => $colaboradorId,
            'tipo' => $tipo,
            'data_inicio' => $inicio,
            'data_fim' => $fim,
            'dias' => $dias > 0 ? $dias : 1,
            'motivo' => $this->input->post('motivo'),
            'status' => 'aprovado',
            'aprovador_id' => $this->session->userdata('id_admin'),
            'data_analise' => date('Y-m-d H:i:s'),
        ]);
        $this->session->set_flashdata('success', 'Ausência registrada.');
        redirect(site_url('rh/ficha/' . $colaboradorId));
    }

    private function dadosColaboradorDoPost()
    {
        $salario = str_replace(['.', ','], ['', '.'], (string) $this->input->post('salario_base'));
        $valorHora = str_replace(['.', ','], ['', '.'], (string) $this->input->post('valor_hora'));
        return [
            'nome' => trim($this->input->post('nome')),
            'cpf' => $this->input->post('cpf'),
            'rg' => $this->input->post('rg'),
            'data_nascimento' => $this->input->post('data_nascimento') ?: null,
            'cargo' => $this->input->post('cargo'),
            'departamento' => $this->input->post('departamento'),
            'tipo_contrato' => $this->input->post('tipo_contrato') ?: 'CLT',
            'admissao' => $this->input->post('admissao') ?: null,
            'demissao' => $this->input->post('demissao') ?: null,
            'unidade_id' => $this->input->post('unidade_id') ?: null,
            'jornada_id' => $this->input->post('jornada_id') ?: null,
            'salario_base' => $salario !== '' ? $salario : null,
            'valor_hora' => $valorHora !== '' ? $valorHora : null,
            'email' => $this->input->post('email'),
            'celular' => $this->input->post('celular'),
            'pix_tipo' => $this->input->post('pix_tipo'),
            'pix_chave' => $this->input->post('pix_chave'),
            'usuarios_id' => $this->input->post('usuarios_id') ?: null,
            'situacao' => $this->input->post('situacao') !== null ? (int) $this->input->post('situacao') : 1,
            'observacoes' => $this->input->post('observacoes'),
        ];
    }

    /** Processa o upload de foto do colaborador (multipart → base64 no banco). */
    private function fotoColaboradorUpload()
    {
        if (empty($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        if ($_FILES['foto']['size'] > 3 * 1024 * 1024) {
            $this->session->set_flashdata('error', 'A foto excede 3MB e não foi salva.');
            return null;
        }
        $tmp = $_FILES['foto']['tmp_name'];
        $mime = mime_content_type($tmp) ?: $_FILES['foto']['type'];
        if (strpos((string) $mime, 'image/') !== 0) {
            $this->session->set_flashdata('error', 'Arquivo de foto inválido (use uma imagem).');
            return null;
        }
        $conteudo = file_get_contents($tmp);
        if ($conteudo === false) {
            return null;
        }
        return [
            'foto_base64' => 'data:' . $mime . ';base64,' . base64_encode($conteudo),
            'foto_mime' => $mime,
        ];
    }

    /** Serve a foto de perfil de um colaborador. */
    public function fotoColaborador($id = null)
    {
        $colaborador = $this->rh_colaboradores_model->getById($id);
        if (! $colaborador || empty($colaborador->foto_base64)) {
            show_404();
            return;
        }
        $base64 = $colaborador->foto_base64;
        if (preg_match('/^data:(image\/\w+);base64,/', $base64, $m)) {
            $mime = $m[1];
            $dados = substr($base64, strlen($m[0]));
        } else {
            $mime = $colaborador->foto_mime ?: 'image/jpeg';
            $dados = $base64;
        }
        $bin = base64_decode($dados, true);
        if ($bin === false) {
            show_404();
            return;
        }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: private, max-age=3600');
        echo $bin;
        exit;
    }

    /** Usuários do sistema ainda não vinculados (para o select). */
    private function usuariosDisponiveis($incluir = null)
    {
        $this->load->model('usuarios_model');
        $usuarios = $this->db->select('idUsuarios, nome, email')->get('usuarios')->result();
        $vinculados = $this->db->select('usuarios_id')->where('usuarios_id IS NOT NULL')->get('rh_colaboradores')->result();
        $ocupados = array_map(function ($v) {
            return $v->usuarios_id;
        }, $vinculados);
        return array_filter($usuarios, function ($u) use ($ocupados, $incluir) {
            return ! in_array($u->idUsuarios, $ocupados) || $u->idUsuarios == $incluir;
        });
    }

    // =====================================================================
    // Biometria facial de referência
    // =====================================================================

    public function biometria($colaborador_id = null)
    {
        $this->exigir('eRh', 'Você não tem permissão para cadastrar biometria.');
        $colaborador = $this->rh_colaboradores_model->getById($colaborador_id);
        if (! $colaborador) {
            $this->session->set_flashdata('error', 'Colaborador não encontrado.');
            redirect(site_url('rh/colaboradores'));
        }
        $this->data['colaborador'] = $colaborador;
        $this->data['tem_biometria'] = $this->rh_colaboradores_model->temBiometria($colaborador_id);
        $this->data['view'] = 'rh/biometria';
        return $this->layout();
    }

    /** Salva o descriptor facial (AJAX). */
    public function salvarBiometria()
    {
        if (! $this->input->is_ajax_request()) {
            redirect(base_url());
        }
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eRh')) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão.']);
            return;
        }
        $colaboradorId = $this->input->post('colaborador_id');
        $descriptor = $this->input->post('descriptor', false); // JSON de floats
        $foto = $this->input->post('foto', false);

        if (! $colaboradorId || ! $descriptor) {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
            return;
        }
        // valida que o descriptor é um array JSON
        $arr = json_decode($descriptor);
        if (! is_array($arr) || count($arr) < 64) {
            echo json_encode(['success' => false, 'message' => 'Descriptor facial inválido.']);
            return;
        }
        $fotoMime = null;
        if ($foto && preg_match('/^data:(image\/\w+);base64,/', $foto, $m)) {
            $fotoMime = $m[1];
        }
        $ok = $this->rh_colaboradores_model->salvarBiometria(
            $colaboradorId,
            json_encode($arr),
            $foto ?: null,
            $fotoMime,
            'face-api-0.22'
        );
        echo json_encode(['success' => (bool) $ok, 'message' => $ok ? 'Biometria salva com sucesso!' : 'Erro ao salvar.']);
    }

    // =====================================================================
    // Unidades / Jornadas
    // =====================================================================

    public function unidades()
    {
        $this->data['unidades'] = $this->rh_colaboradores_model->listarUnidades();
        $this->data['view'] = 'rh/unidades';
        return $this->layout();
    }

    public function salvarUnidade()
    {
        $this->exigir('eRh', 'Sem permissão.');
        $dados = [
            'nome' => trim($this->input->post('nome')),
            'endereco' => $this->input->post('endereco'),
            'latitude' => $this->input->post('latitude') ?: null,
            'longitude' => $this->input->post('longitude') ?: null,
            'raio_metros' => (int) ($this->input->post('raio_metros') ?: 150),
            'situacao' => $this->input->post('situacao') !== null ? (int) $this->input->post('situacao') : 1,
        ];
        $id = $this->input->post('id');
        if ($id) {
            $this->rh_colaboradores_model->editUnidade($dados, $id);
            $this->session->set_flashdata('success', 'Unidade atualizada.');
        } else {
            $this->rh_colaboradores_model->addUnidade($dados);
            $this->session->set_flashdata('success', 'Unidade cadastrada.');
        }
        redirect(site_url('rh/unidades'));
    }

    public function excluirUnidade()
    {
        $this->exigir('eRh', 'Sem permissão.');
        if ($id = $this->input->post('id')) {
            $this->rh_colaboradores_model->deleteUnidade($id);
            $this->session->set_flashdata('success', 'Unidade excluída.');
        }
        redirect(site_url('rh/unidades'));
    }

    public function jornadas()
    {
        $this->data['jornadas'] = $this->rh_colaboradores_model->listarJornadas();
        $this->data['view'] = 'rh/jornadas';
        return $this->layout();
    }

    public function salvarJornada()
    {
        $this->exigir('eRh', 'Sem permissão.');
        $dias = $this->input->post('dias_semana');
        $dados = [
            'nome' => trim($this->input->post('nome')),
            'carga_diaria_min' => (int) ($this->input->post('carga_diaria_min') ?: 480),
            'tolerancia_min' => (int) ($this->input->post('tolerancia_min') ?: 10),
            'dias_semana' => is_array($dias) ? implode(',', $dias) : ($dias ?: '1,2,3,4,5'),
            'hora_entrada' => $this->input->post('hora_entrada') ?: null,
            'hora_saida' => $this->input->post('hora_saida') ?: null,
            'intervalo_min' => (int) ($this->input->post('intervalo_min') ?: 60),
            'situacao' => $this->input->post('situacao') !== null ? (int) $this->input->post('situacao') : 1,
        ];
        $id = $this->input->post('id');
        if ($id) {
            $this->rh_colaboradores_model->editJornada($dados, $id);
            $this->session->set_flashdata('success', 'Jornada atualizada.');
        } else {
            $this->rh_colaboradores_model->addJornada($dados);
            $this->session->set_flashdata('success', 'Jornada cadastrada.');
        }
        redirect(site_url('rh/jornadas'));
    }

    public function excluirJornada()
    {
        $this->exigir('eRh', 'Sem permissão.');
        if ($id = $this->input->post('id')) {
            $this->rh_colaboradores_model->deleteJornada($id);
            $this->session->set_flashdata('success', 'Jornada excluída.');
        }
        redirect(site_url('rh/jornadas'));
    }

    // =====================================================================
    // Espelho de ponto
    // =====================================================================

    public function espelho($colaborador_id = null, $competencia = null)
    {
        $colaborador = $this->rh_colaboradores_model->getById($colaborador_id);
        if (! $colaborador) {
            $this->session->set_flashdata('error', 'Colaborador não encontrado.');
            redirect(site_url('rh/colaboradores'));
        }
        $competencia = $competencia ?: date('Y-m');
        $this->prepararEspelho($colaborador, $competencia);
        $this->data['imprimir'] = false;
        $this->data['view'] = 'rh/espelho';
        return $this->layout();
    }

    /** Espelho em PDF (mpdf). */
    public function espelhoPdf($colaborador_id = null, $competencia = null)
    {
        $colaborador = $this->rh_colaboradores_model->getById($colaborador_id);
        if (! $colaborador) {
            show_error('Colaborador não encontrado', 404);
            return;
        }
        $competencia = $competencia ?: date('Y-m');
        $this->prepararEspelho($colaborador, $competencia);
        $this->data['imprimir'] = true;
        $this->load->model('mapos_model');
        $this->data['emitente'] = $this->mapos_model->getEmitente();

        $html = $this->load->view('rh/espelho_pdf', $this->data, true);
        $this->load->helper('mpdf');
        $nome = 'espelho_' . $colaborador->id . '_' . $competencia . '.pdf';
        pdf_create($html, $nome, true);
    }

    /** Monta os dados do espelho (batidas por dia + totais consolidados). */
    private function prepararEspelho($colaborador, $competencia)
    {
        $this->load->library('rh_calculo');
        [$ano, $mes] = explode('-', $competencia);
        $inicio = "$ano-$mes-01";
        $fim = date('Y-m-t', strtotime($inicio));

        $registros = $this->rh_ponto_model->getPorPeriodo($colaborador->id, $inicio, $fim);
        $porDia = [];
        foreach ($registros as $r) {
            $porDia[substr($r->data_hora, 0, 10)][] = $r;
        }

        $jornada = ! empty($colaborador->jornada_id)
            ? $this->rh_colaboradores_model->getJornada($colaborador->jornada_id) : null;
        $cargaDiaria = $jornada ? (int) $jornada->carga_diaria_min : 480;
        $tolerancia = $jornada ? (int) $jornada->tolerancia_min : 0;
        $diasEscala = $jornada ? array_map('trim', explode(',', $jornada->dias_semana)) : ['1', '2', '3', '4', '5'];

        $linhas = [];
        $totalDias = (int) date('t', strtotime($inicio));
        for ($d = 1; $d <= $totalDias; $d++) {
            $dia = sprintf('%s-%s-%02d', $ano, $mes, $d);
            $dw = (int) date('w', strtotime($dia));
            $ehUtil = in_array((string) $dw, $diasEscala, true);
            $batidas = $porDia[$dia] ?? [];
            $calc = $this->rh_calculo->calcularDia($batidas, $cargaDiaria, $tolerancia, $ehUtil);
            $linhas[] = [
                'data' => $dia,
                'dia_semana' => $dw,
                'eh_util' => $ehUtil,
                'batidas' => $batidas,
                'calc' => $calc,
            ];
        }

        $this->data['colaborador'] = $colaborador;
        $this->data['competencia'] = $competencia;
        $this->data['linhas'] = $linhas;
        $this->data['totais'] = $this->rh_calculo->calcularCompetencia($colaborador->id, $competencia);
        $this->data['jornada'] = $jornada;
    }

    /** Recalcula a competência e opcionalmente gera os extras. */
    public function recalcular($colaborador_id = null, $competencia = null)
    {
        $this->exigir('eRh', 'Sem permissão.');
        $competencia = $competencia ?: date('Y-m');
        $this->load->library('rh_calculo');
        $this->rh_calculo->calcularCompetencia($colaborador_id, $competencia);
        if ($this->input->get('extras') == '1' && $this->permission->checkPermission($this->session->userdata('permissao'), 'vRhFinanceiro')) {
            $n = $this->rh_calculo->gerarLancamentosExtras($colaborador_id, $competencia);
            $this->session->set_flashdata('success', 'Competência recalculada. ' . (int) $n . ' lançamento(s) de extra gerado(s).');
        } else {
            $this->session->set_flashdata('success', 'Competência recalculada.');
        }
        redirect(site_url("rh/espelho/$colaborador_id/$competencia"));
    }

    // =====================================================================
    // Lançamentos financeiros / extras
    // =====================================================================

    public function lancamentos()
    {
        $this->exigir('vRhFinanceiro', 'Você não tem permissão para ver o financeiro do RH.');
        $competencia = $this->input->get('competencia') ?: date('Y-m');
        $colaboradorId = $this->input->get('colaborador_id') ?: null;
        $this->data['competencia'] = $competencia;
        $this->data['colaborador_id'] = $colaboradorId;
        $this->data['lancamentos'] = $this->rh_extras_model->listarLancamentos($colaboradorId, $competencia);
        $this->data['colaboradores'] = $this->rh_colaboradores_model->listar(['situacao' => 1]);
        $this->data['view'] = 'rh/lancamentos';
        return $this->layout();
    }

    public function salvarLancamento()
    {
        $this->exigir('vRhFinanceiro', 'Sem permissão.');
        $valor = str_replace(['.', ','], ['', '.'], (string) $this->input->post('valor'));
        $tipo = $this->input->post('tipo');
        $descontos = ['desconto', 'adiantamento', 'falta', 'vale'];
        $dados = [
            'colaborador_id' => $this->input->post('colaborador_id'),
            'competencia' => $this->input->post('competencia') ?: date('Y-m'),
            'tipo' => $tipo,
            'natureza' => in_array($tipo, $descontos, true) ? 'desconto' : 'provento',
            'descricao' => $this->input->post('descricao'),
            'quantidade' => $this->input->post('quantidade') ?: null,
            'valor' => $valor !== '' ? $valor : 0,
            'aprovado' => $this->input->post('aprovado') ? 1 : 0,
            'aprovador_id' => $this->input->post('aprovado') ? $this->session->userdata('id_admin') : null,
            'origem' => 'manual',
        ];
        $id = $this->input->post('id');
        if ($id) {
            $this->rh_extras_model->editLancamento($dados, $id);
            $this->session->set_flashdata('success', 'Lançamento atualizado.');
        } else {
            $this->rh_extras_model->addLancamento($dados);
            $this->session->set_flashdata('success', 'Lançamento adicionado.');
        }
        redirect(site_url('rh/lancamentos?competencia=' . $dados['competencia']));
    }

    public function aprovarLancamento()
    {
        $this->exigir('vRhFinanceiro', 'Sem permissão.');
        if ($id = $this->input->post('id')) {
            $this->rh_extras_model->editLancamento([
                'aprovado' => 1,
                'aprovador_id' => $this->session->userdata('id_admin'),
            ], $id);
        }
        redirect($_SERVER['HTTP_REFERER'] ?? site_url('rh/lancamentos'));
    }

    public function excluirLancamento()
    {
        $this->exigir('vRhFinanceiro', 'Sem permissão.');
        if ($id = $this->input->post('id')) {
            $this->rh_extras_model->deleteLancamento($id);
            $this->session->set_flashdata('success', 'Lançamento excluído.');
        }
        redirect($_SERVER['HTTP_REFERER'] ?? site_url('rh/lancamentos'));
    }

    // =====================================================================
    // Holerite (demonstrativo + PDF oficial)
    // =====================================================================

    public function holerite($colaborador_id = null, $competencia = null)
    {
        $this->exigir('vRhFinanceiro', 'Você não tem permissão para ver holerites.');
        $colaborador = $this->rh_colaboradores_model->getById($colaborador_id);
        if (! $colaborador) {
            $this->session->set_flashdata('error', 'Colaborador não encontrado.');
            redirect(site_url('rh/colaboradores'));
        }
        $competencia = $competencia ?: date('Y-m');
        $this->data['colaborador'] = $colaborador;
        $this->data['competencia'] = $competencia;
        $this->data['resumo'] = $this->rh_extras_model->resumoCompetencia($colaborador_id, $competencia);
        $this->data['holerite'] = $this->rh_extras_model->getHolerite($colaborador_id, $competencia);
        $this->data['view'] = 'rh/holerite';
        return $this->layout();
    }

    /** Sobe/atualiza o PDF oficial do holerite e/ou dados do recibo. */
    public function salvarHolerite()
    {
        $this->exigir('vRhFinanceiro', 'Sem permissão.');
        $colaboradorId = $this->input->post('colaborador_id');
        $competencia = $this->input->post('competencia') ?: date('Y-m');
        if (! $colaboradorId) {
            redirect(site_url('rh/colaboradores'));
        }

        $dados = [
            'valor_liquido' => ($v = str_replace(['.', ','], ['', '.'], (string) $this->input->post('valor_liquido'))) !== '' ? $v : null,
            'observacao' => $this->input->post('observacao'),
            'created_by' => $this->session->userdata('id_admin'),
        ];

        // Arquivo PDF (opcional): mantém o anterior se nenhum novo for enviado
        if (! empty($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['arquivo']['size'] > 8 * 1024 * 1024) {
                $this->session->set_flashdata('error', 'O arquivo excede 8MB.');
                redirect(site_url("rh/holerite/$colaboradorId/$competencia"));
            }
            $mime = mime_content_type($_FILES['arquivo']['tmp_name']) ?: $_FILES['arquivo']['type'];
            $conteudo = file_get_contents($_FILES['arquivo']['tmp_name']);
            if ($conteudo !== false) {
                $dados['arquivo_base64'] = 'data:' . $mime . ';base64,' . base64_encode($conteudo);
                $dados['arquivo_mime'] = $mime;
                $dados['arquivo_nome'] = $_FILES['arquivo']['name'];
            }
        }

        $this->rh_extras_model->salvarHolerite($colaboradorId, $competencia, $dados);
        log_info('RH: salvou holerite colaborador ' . $colaboradorId . ' comp ' . $competencia);
        $this->session->set_flashdata('success', 'Holerite salvo.');
        redirect(site_url("rh/holerite/$colaboradorId/$competencia"));
    }

    public function excluirHolerite()
    {
        $this->exigir('vRhFinanceiro', 'Sem permissão.');
        $colaboradorId = $this->input->post('colaborador_id');
        $competencia = $this->input->post('competencia');
        if ($colaboradorId && $competencia) {
            $this->rh_extras_model->deleteHolerite($colaboradorId, $competencia);
            $this->session->set_flashdata('success', 'Holerite removido.');
        }
        redirect(site_url("rh/holerite/$colaboradorId/$competencia"));
    }

    /** Serve o PDF do holerite (acesso RH financeiro). */
    public function baixarHolerite($colaborador_id = null, $competencia = null)
    {
        $this->exigir('vRhFinanceiro', 'Sem permissão.');
        $h = $this->rh_extras_model->getHolerite($colaborador_id, $competencia);
        if (! $h || empty($h->arquivo_base64)) {
            show_404();
            return;
        }
        $this->servirArquivoBase64($h->arquivo_base64, $h->arquivo_mime, $h->arquivo_nome);
    }

    /** Ecoa um arquivo data-URI base64 como download/inline. */
    private function servirArquivoBase64($base64, $mimeFallback = null, $nome = null)
    {
        if (preg_match('/^data:([\w\/\+\.\-]+);base64,/', $base64, $m)) {
            $mime = $m[1];
            $dados = substr($base64, strlen($m[0]));
        } else {
            $mime = $mimeFallback ?: 'application/octet-stream';
            $dados = $base64;
        }
        $bin = base64_decode($dados, true);
        if ($bin === false) {
            show_error('Erro ao decodificar arquivo', 500);
            return;
        }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strlen($bin));
        if ($nome) {
            header('Content-Disposition: inline; filename="' . $nome . '"');
        }
        echo $bin;
        exit;
    }

    // =====================================================================
    // Ajuste de ponto (editar/excluir batidas)
    // =====================================================================

    public function ajustarPonto($colaborador_id = null, $competencia = null)
    {
        $this->exigir('eRh', 'Você não tem permissão para ajustar o ponto.');
        $colaborador = $this->rh_colaboradores_model->getById($colaborador_id);
        if (! $colaborador) {
            $this->session->set_flashdata('error', 'Colaborador não encontrado.');
            redirect(site_url('rh/colaboradores'));
        }
        $competencia = $competencia ?: date('Y-m');
        [$ano, $mes] = explode('-', $competencia);
        $inicio = "$ano-$mes-01";
        $fim = date('Y-m-t', strtotime($inicio));

        $this->data['colaborador'] = $colaborador;
        $this->data['competencia'] = $competencia;
        $this->data['registros'] = $this->rh_ponto_model->getPorPeriodo($colaborador_id, $inicio, $fim, true);
        $this->data['view'] = 'rh/ajustar_ponto';
        return $this->layout();
    }

    public function editarBatida()
    {
        $this->exigir('eRh', 'Sem permissão.');
        $id = $this->input->post('id');
        $colaboradorId = $this->input->post('colaborador_id');
        $competencia = $this->input->post('competencia');
        $dataHora = $this->input->post('data_hora');
        $tipo = $this->input->post('tipo');
        $status = $this->input->post('status') ?: 'ajustado';
        $destino = site_url('rh/ajustarPonto/' . $colaboradorId . '/' . $competencia);

        if (! $id || ! $dataHora || ! in_array($tipo, ['entrada', 'saida', 'inicio_intervalo', 'fim_intervalo'], true)) {
            $this->session->set_flashdata('error', 'Dados incompletos.');
            redirect($destino);
        }
        $this->rh_ponto_model->edit([
            'data_hora' => date('Y-m-d H:i:s', strtotime($dataHora)),
            'tipo' => $tipo,
            'status' => in_array($status, ['valido', 'ajustado', 'rejeitado'], true) ? $status : 'ajustado',
            'observacao' => trim(($this->input->post('observacao') ?: '') . ' [editada pelo RH]'),
        ], $id);
        log_info('RH: editou batida ' . $id);
        $this->session->set_flashdata('success', 'Batida atualizada.');
        redirect($destino);
    }

    public function excluirBatida()
    {
        $this->exigir('eRh', 'Sem permissão.');
        $id = $this->input->post('id');
        $colaboradorId = $this->input->post('colaborador_id');
        $competencia = $this->input->post('competencia');
        if ($id) {
            $this->rh_ponto_model->delete($id);
            log_info('RH: excluiu batida ' . $id);
            $this->session->set_flashdata('success', 'Batida excluída.');
        }
        redirect(site_url('rh/ajustarPonto/' . $colaboradorId . '/' . $competencia));
    }

    // =====================================================================
    // Aprovações: ocorrências e ausências
    // =====================================================================

    public function ocorrencias()
    {
        $this->data['status'] = $this->input->get('status') ?: '';
        $this->data['ocorrencias'] = $this->rh_extras_model->listarOcorrencias(['status' => $this->data['status']]);
        $this->data['view'] = 'rh/ocorrencias';
        return $this->layout();
    }

    public function analisarOcorrencia()
    {
        $this->exigir('aprovarRh', 'Você não tem permissão para aprovar.');
        $id = $this->input->post('id');
        $status = $this->input->post('status'); // aprovado|recusado
        $resposta = $this->input->post('resposta');
        if ($id && in_array($status, ['aprovado', 'recusado'], true)) {
            $this->rh_extras_model->analisarOcorrencia($id, $status, $this->session->userdata('id_admin'), $resposta);
            $oc = $this->rh_extras_model->getOcorrencia($id);
            $extra = '';
            if ($status === 'aprovado' && $oc) {
                $extra = $this->aplicarOcorrencia($oc);
            }
            $this->session->set_flashdata('success', 'Ocorrência ' . $status . '.' . $extra);
        }
        redirect(site_url('rh/ocorrencias'));
    }

    /**
     * Ao aprovar, aplica automaticamente o que o colaborador pediu, conforme o tipo:
     *  - correcao_ponto: cria/edita a batida solicitada (tipo + data/hora);
     *  - justificativa_falta / abono: registra uma ausência aprovada (dia justificado).
     * Devolve um texto curto do que foi feito (para o flashdata).
     */
    private function aplicarOcorrencia($oc)
    {
        // Correção de ponto ------------------------------------------------
        if ($oc->tipo === 'correcao_ponto') {
            $temCorrecao = ! empty($oc->correcao_tipo) && ! empty($oc->correcao_data_hora);
            $jaAplicada = ! empty($oc->correcao_aplicada);
            if ($temCorrecao && ! $jaAplicada) {
                $dados = [
                    'tipo' => $oc->correcao_tipo,
                    'data_hora' => date('Y-m-d H:i:s', strtotime($oc->correcao_data_hora)),
                    'status' => 'ajustado',
                    'observacao' => 'Correção aprovada (ocorrência #' . $oc->id . ')',
                ];
                if (! empty($oc->registro_id)) {
                    $this->rh_ponto_model->edit($dados, $oc->registro_id);
                } else {
                    $dados['colaborador_id'] = $oc->colaborador_id;
                    $dados['origem'] = 'manual';
                    $dados['registrado_por'] = $this->session->userdata('id_admin');
                    $this->rh_ponto_model->registrar($dados);
                }
                if ($this->db->field_exists('correcao_aplicada', 'rh_ocorrencias')) {
                    $this->db->where('id', $oc->id)->update('rh_ocorrencias', ['correcao_aplicada' => 1]);
                }
                $this->recalcularCompetenciaDaData($oc->colaborador_id, $oc->correcao_data_hora);
                return ' Batida corrigida no ponto.';
            }
            if (! empty($oc->registro_id)) {
                $this->rh_ponto_model->edit(['status' => 'ajustado'], $oc->registro_id);
            }
            return '';
        }

        // Justificativa de falta / abono -> ausência aprovada --------------
        if (in_array($oc->tipo, ['justificativa_falta', 'abono'], true) && ! empty($oc->data_referencia)) {
            $tipoAusencia = ($oc->tipo === 'justificativa_falta' && ! empty($oc->anexo_base64)) ? 'atestado' : 'folga';
            $this->rh_extras_model->addAusencia([
                'colaborador_id' => $oc->colaborador_id,
                'tipo' => $tipoAusencia,
                'data_inicio' => $oc->data_referencia,
                'data_fim' => $oc->data_referencia,
                'dias' => 1,
                'motivo' => 'Gerado da ocorrência #' . $oc->id . ' — ' . ($oc->descricao ?: ''),
                'anexo_base64' => $oc->anexo_base64,
                'anexo_mime' => $oc->anexo_mime,
                'anexo_nome' => $oc->anexo_nome,
                'status' => 'aprovado',
                'aprovador_id' => $this->session->userdata('id_admin'),
                'data_analise' => date('Y-m-d H:i:s'),
            ]);
            return ' Ausência (' . $tipoAusencia . ') registrada para ' . date('d/m/Y', strtotime($oc->data_referencia)) . '.';
        }

        return '';
    }

    /** Recalcula a competência (YYYY-MM) de uma data para refletir no espelho. */
    private function recalcularCompetenciaDaData($colaborador_id, $dataHora)
    {
        if (empty($dataHora)) {
            return;
        }
        $this->load->library('rh_calculo');
        $this->rh_calculo->calcularCompetencia($colaborador_id, date('Y-m', strtotime($dataHora)));
    }

    public function ausencias()
    {
        $this->data['status'] = $this->input->get('status') ?: '';
        $this->data['ausencias'] = $this->rh_extras_model->listarAusencias(['status' => $this->data['status']]);
        $this->data['view'] = 'rh/ausencias';
        return $this->layout();
    }

    public function analisarAusencia()
    {
        $this->exigir('aprovarRh', 'Você não tem permissão para aprovar.');
        $id = $this->input->post('id');
        $status = $this->input->post('status');
        $resposta = $this->input->post('resposta');
        if ($id && in_array($status, ['aprovado', 'recusado'], true)) {
            $this->rh_extras_model->analisarAusencia($id, $status, $this->session->userdata('id_admin'), $resposta);
            $this->session->set_flashdata('success', 'Solicitação ' . $status . '.');
        }
        redirect(site_url('rh/ausencias'));
    }

    /** Anexo (atestado/comprovante) de ocorrência ou ausência. */
    public function anexo($tipo = null, $id = null)
    {
        $registro = $tipo === 'ocorrencia'
            ? $this->rh_extras_model->getOcorrencia($id)
            : $this->rh_extras_model->getAusencia($id);
        if (! $registro || empty($registro->anexo_base64)) {
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
}
