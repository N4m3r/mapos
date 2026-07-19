<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Formulários de Atendimento personalizados.
 *
 * Área administrativa (Configurações) para montar formulários com campos
 * livres vinculados às etapas do fluxo de atendimento, além dos endpoints
 * usados pela tela de atendimento para renderizar e salvar as respostas.
 */
class Formularios extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('formularios_atendimento_model', 'formularios');
        $this->load->helper('date');
    }

    /** Permissão de gestão dos formulários. */
    private function exigirGestao()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cFormularioAtendimento')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para gerenciar formulários de atendimento.');
            redirect(base_url());
        }
    }

    /** Permissão para responder (equipe de campo / OS). */
    private function podeResponder()
    {
        $permissao = $this->session->userdata('permissao');

        return $this->permission->checkPermission($permissao, 'eOs')
            || $this->permission->checkPermission($permissao, 'eTecnicoCheckin')
            || $this->permission->checkPermission($permissao, 'eTecnicoCheckout')
            || $this->permission->checkPermission($permissao, 'eTecnicoFotos');
    }

    /* ================================================================== */
    /* Gestão                                                              */
    /* ================================================================== */

    public function index()
    {
        $this->exigirGestao();

        $results = $this->formularios->getAll();
        $contagem = [];
        foreach ($results as $r) {
            $contagem[$r->idFormulario] = count($this->formularios->getCampos($r->idFormulario));
        }

        $this->data['menuConfiguracoes'] = 'Formularios';
        $this->data['results'] = $results;
        $this->data['camposCount'] = $contagem;
        $this->data['etapas'] = $this->formularios->etapasDisponiveis();
        $this->data['tipos'] = $this->formularios->tiposCampo();

        $this->data['view'] = 'formularios/gerenciar';

        return $this->layout();
    }

    public function adicionar()
    {
        $this->exigirGestao();

        $this->data['menuConfiguracoes'] = 'Formularios';
        $this->data['formulario'] = null;
        $this->data['campos'] = [];
        $this->data['etapas'] = $this->formularios->etapasDisponiveis();
        $this->data['tipos'] = $this->formularios->tiposCampo();
        $this->data['tiposComOpcoes'] = $this->formularios->tiposComOpcoes();

        $this->data['view'] = 'formularios/form';

        return $this->layout();
    }

    public function editar($id = null)
    {
        $this->exigirGestao();

        $formulario = $id ? $this->formularios->getById($id) : null;
        if (! $formulario) {
            $this->session->set_flashdata('error', 'Formulário não encontrado.');
            redirect(site_url('formularios'));
        }

        $this->data['menuConfiguracoes'] = 'Formularios';
        $this->data['formulario'] = $formulario;
        $this->data['campos'] = $this->formularios->getCampos($id);
        $this->data['etapas'] = $this->formularios->etapasDisponiveis();
        $this->data['tipos'] = $this->formularios->tiposCampo();
        $this->data['tiposComOpcoes'] = $this->formularios->tiposComOpcoes();

        $this->data['view'] = 'formularios/form';

        return $this->layout();
    }

    public function salvar()
    {
        $this->exigirGestao();

        $this->load->library('form_validation');
        $this->form_validation->set_rules('nome', 'Nome do formulário', 'required|trim');
        $this->form_validation->set_rules('etapa', 'Etapa', 'required|trim');

        $id = $this->input->post('idFormulario');
        $etapasValidas = array_keys($this->formularios->etapasDisponiveis());
        $etapa = $this->input->post('etapa');

        if ($this->form_validation->run() == false || ! in_array($etapa, $etapasValidas, true)) {
            $this->session->set_flashdata('error', validation_errors() ?: 'Selecione uma etapa válida.');
            redirect(site_url($id ? 'formularios/editar/' . $id : 'formularios/adicionar'));
        }

        $dados = [
            'nome' => $this->input->post('nome'),
            'descricao' => $this->input->post('descricao'),
            'etapa' => $etapa,
            'obrigatorio' => $this->input->post('obrigatorio') ? 1 : 0,
            'ativo' => $this->input->post('ativo') ? 1 : 0,
            'ordem' => (int) $this->input->post('ordem'),
        ];

        if ($id) {
            $this->formularios->update($id, $dados);
        } else {
            $id = $this->formularios->create($dados);
        }

        if (! $id) {
            $this->session->set_flashdata('error', 'Não foi possível salvar o formulário.');
            redirect(site_url('formularios'));
        }

        $this->formularios->saveCampos($id, $this->camposDoPost());

        log_info('Salvou o formulário de atendimento: ' . $dados['nome']);
        $this->session->set_flashdata('success', 'Formulário salvo com sucesso!');
        redirect(site_url('formularios/editar/' . $id));
    }

    /** Normaliza os campos vindos do POST (campos[N][...]) para o model. */
    private function camposDoPost()
    {
        $campos = $this->input->post('campos');
        if (! is_array($campos)) {
            return [];
        }

        $comOpcoes = $this->formularios->tiposComOpcoes();
        $resultado = [];

        foreach ($campos as $campo) {
            $label = trim($campo['label'] ?? '');
            if ($label === '') {
                continue;
            }

            $tipo = $campo['tipo'] ?? 'texto';
            $opcoes = null;
            if (in_array($tipo, $comOpcoes, true) && ! empty($campo['opcoes'])) {
                // Uma opção por linha -> JSON.
                $linhas = preg_split('/\r\n|\r|\n/', $campo['opcoes']);
                $linhas = array_values(array_filter(array_map('trim', $linhas), function ($v) {
                    return $v !== '';
                }));
                $opcoes = ! empty($linhas) ? json_encode($linhas, JSON_UNESCAPED_UNICODE) : null;
            }

            $resultado[] = [
                'label' => $label,
                'tipo' => $tipo,
                'opcoes' => $opcoes,
                'placeholder' => trim($campo['placeholder'] ?? '') ?: null,
                'ajuda' => trim($campo['ajuda'] ?? '') ?: null,
                'obrigatorio' => ! empty($campo['obrigatorio']) ? 1 : 0,
            ];
        }

        return $resultado;
    }

    public function excluir($id = null)
    {
        $this->exigirGestao();

        $formulario = $id ? $this->formularios->getById($id) : null;
        if (! $formulario) {
            $this->session->set_flashdata('error', 'Formulário não encontrado.');
            redirect(site_url('formularios'));
        }

        $this->formularios->delete($id);
        log_info('Excluiu o formulário de atendimento: ' . $formulario->nome);
        $this->session->set_flashdata('success', 'Formulário excluído com sucesso!');
        redirect(site_url('formularios'));
    }

    public function duplicar($id = null)
    {
        $this->exigirGestao();

        $formulario = $id ? $this->formularios->getById($id) : null;
        if (! $formulario) {
            $this->session->set_flashdata('error', 'Formulário não encontrado.');
            redirect(site_url('formularios'));
        }

        $novoId = $this->formularios->create([
            'nome' => $formulario->nome . ' (cópia)',
            'descricao' => $formulario->descricao,
            'etapa' => $formulario->etapa,
            'obrigatorio' => $formulario->obrigatorio,
            'ativo' => 0,
            'ordem' => $formulario->ordem,
        ]);

        if ($novoId) {
            $campos = [];
            foreach ($this->formularios->getCampos($id) as $c) {
                $campos[] = [
                    'label' => $c->label,
                    'tipo' => $c->tipo,
                    'opcoes' => $c->opcoes,
                    'placeholder' => $c->placeholder,
                    'ajuda' => $c->ajuda,
                    'obrigatorio' => $c->obrigatorio,
                ];
            }
            $this->formularios->saveCampos($novoId, $campos);
        }

        $this->session->set_flashdata('success', 'Formulário duplicado. Revise e ative a cópia.');
        redirect(site_url('formularios/editar/' . $novoId));
    }

    /* ================================================================== */
    /* Endpoints do fluxo de atendimento (AJAX)                            */
    /* ================================================================== */

    /**
     * Retorna, em JSON, os formulários ativos de uma etapa já com os campos
     * e os valores eventualmente respondidos para a OS informada.
     */
    public function porEtapa()
    {
        if (! $this->input->is_ajax_request()) {
            redirect(base_url());
        }
        if (! $this->podeResponder()) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão.']);
            return;
        }

        $etapa = $this->input->post('etapa');
        $osId = (int) $this->input->post('os_id');
        $etapasValidas = array_keys($this->formularios->etapasDisponiveis());

        if (! in_array($etapa, $etapasValidas, true)) {
            echo json_encode(['success' => false, 'message' => 'Etapa inválida.']);
            return;
        }

        $formularios = $this->formularios->getByEtapa($etapa);
        $saida = [];

        foreach ($formularios as $f) {
            $valores = $osId ? $this->formularios->getValoresRespondidos($f->idFormulario, $osId) : [];
            $campos = [];
            foreach ($f->campos as $c) {
                $campos[] = [
                    'id' => $c->idCampo,
                    'label' => $c->label,
                    'tipo' => $c->tipo,
                    'opcoes' => $c->opcoes ? json_decode($c->opcoes, true) : [],
                    'placeholder' => $c->placeholder,
                    'ajuda' => $c->ajuda,
                    'obrigatorio' => (int) $c->obrigatorio,
                    'valor' => $valores[$c->idCampo] ?? '',
                ];
            }

            $saida[] = [
                'id' => $f->idFormulario,
                'nome' => $f->nome,
                'descricao' => $f->descricao,
                'obrigatorio' => (int) $f->obrigatorio,
                'campos' => $campos,
            ];
        }

        echo json_encode(['success' => true, 'formularios' => $saida]);
    }

    /**
     * Salva a resposta de um formulário para uma OS.
     * Espera: formulario_id, os_id, etapa, checkin_id (opcional),
     *         valores[campo_id] (valor ou array).
     */
    public function salvarResposta()
    {
        if (! $this->input->is_ajax_request()) {
            redirect(base_url());
        }
        if (! $this->podeResponder()) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão.']);
            return;
        }

        $formularioId = (int) $this->input->post('formulario_id');
        $osId = (int) $this->input->post('os_id');
        $etapa = $this->input->post('etapa');
        $checkinId = $this->input->post('checkin_id');
        $valores = $this->input->post('valores');

        if (! $formularioId || ! $osId) {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
            return;
        }

        $formulario = $this->formularios->getById($formularioId);
        if (! $formulario) {
            echo json_encode(['success' => false, 'message' => 'Formulário não encontrado.']);
            return;
        }

        if (! is_array($valores)) {
            $valores = [];
        }

        // Valida campos obrigatórios.
        foreach ($this->formularios->getCampos($formularioId) as $campo) {
            if ($campo->obrigatorio) {
                $v = $valores[$campo->idCampo] ?? '';
                if (is_array($v)) {
                    $v = implode('', $v);
                }
                if (trim((string) $v) === '') {
                    echo json_encode(['success' => false, 'message' => 'Preencha o campo obrigatório: ' . $campo->label]);
                    return;
                }
            }
        }

        $ok = $this->formularios->salvarResposta(
            $formularioId,
            $osId,
            $checkinId,
            $this->session->userdata('id_admin'),
            $etapa ?: $formulario->etapa,
            $valores
        );

        if ($ok) {
            log_info('Salvou resposta do formulário "' . $formulario->nome . '" na OS ' . $osId);
            echo json_encode(['success' => true, 'message' => 'Respostas salvas.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Não foi possível salvar as respostas.']);
        }
    }
}
