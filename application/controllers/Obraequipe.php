<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Equipes de obra: cadastro de equipes, membros e alocação às obras.
 * Permissão: cObraEquipe (gerenciar); vObras para visualizar.
 */
class Obraequipe extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->model('obra_equipe_model');
        $this->data['menuObras'] = 'Obras';
    }

    private function precisa($perm, $msg)
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), $perm)) {
            $this->session->set_flashdata('error', $msg);
            redirect(base_url());
        }
    }

    public function index()
    {
        $this->precisa('vObras', 'Você não tem permissão para visualizar Projetos.');
        $this->data['equipes'] = $this->obra_equipe_model->getEquipes();
        $this->data['obra_menu'] = 'equipes';
        $this->data['view'] = 'obras/equipes';
        return $this->layout();
    }

    public function salvar()
    {
        $this->precisa('cObraEquipe', 'Você não tem permissão para gerenciar equipes.');
        $id = (int) $this->input->post('idEquipe');
        $nome = trim((string) $this->input->post('nome'));
        if ($nome === '') {
            $this->session->set_flashdata('error', 'Informe o nome da equipe.');
            redirect('obraequipe');
        }
        $dados = [
            'nome' => $nome,
            'encarregado_id' => (int) $this->input->post('encarregado_id') ?: null,
            'ativo' => $this->input->post('ativo') !== null ? 1 : 0,
        ];
        // Campos de equipe externa/terceira (só se as colunas existirem).
        foreach ([
            'tipo' => $this->input->post('tipo') ?: 'interna',
            'documento' => $this->input->post('documento'),
            'contato' => $this->input->post('contato'),
            'telefone' => $this->input->post('telefone'),
            'custo_padrao' => $this->parseMoeda($this->input->post('custo_padrao')),
        ] as $campo => $valor) {
            if ($this->db->field_exists($campo, 'equipes')) {
                $dados[$campo] = $valor;
            }
        }
        if ($id) {
            $this->obra_equipe_model->edit($id, $dados);
            $this->session->set_flashdata('success', 'Equipe atualizada.');
            redirect('obraequipe/membros/' . $id);
        }
        $dados['data_cadastro'] = date('Y-m-d H:i:s');
        $novo = $this->obra_equipe_model->add($dados);
        log_info('Cadastrou equipe de obra. ID: ' . $novo);
        $this->session->set_flashdata('success', 'Equipe cadastrada. Adicione os membros.');
        redirect('obraequipe/membros/' . $novo);
    }

    public function excluir()
    {
        $this->precisa('cObraEquipe', 'Sem permissão.');
        $id = (int) $this->input->post('idEquipe');
        if ($id) {
            $this->obra_equipe_model->delete($id);
            $this->session->set_flashdata('success', 'Equipe excluída.');
        }
        redirect('obraequipe');
    }

    /** Tela de detalhe: membros + alocações da equipe. */
    public function membros()
    {
        $id = (int) $this->uri->segment(3);
        $this->precisa('vObras', 'Sem permissão.');
        $equipe = $this->obra_equipe_model->getEquipe($id);
        if (! $equipe) {
            $this->session->set_flashdata('error', 'Equipe não encontrada.');
            redirect('obraequipe');
        }
        $this->data['equipe'] = $equipe;
        $this->data['membros'] = $this->obra_equipe_model->getMembros($id);
        $this->data['alocacoes'] = $this->obra_equipe_model->getAlocacoes($id);
        $this->data['obras'] = $this->obra_equipe_model->getObrasSelect();
        $this->data['usuarios'] = $this->db->select('idUsuarios, nome')
            ->where('situacao', 1)->order_by('nome', 'ASC')->get('usuarios')->result();
        $this->data['obra_menu'] = 'equipes';
        $this->data['view'] = 'obras/equipe_membros';
        return $this->layout();
    }

    public function salvarMembro()
    {
        $this->precisa('cObraEquipe', 'Sem permissão.');
        $equipe_id = (int) $this->input->post('equipe_id');
        $colab = (int) $this->input->post('colaborador_id') ?: null;
        $nome = trim((string) $this->input->post('nome'));
        if (! $colab && $nome === '') {
            $this->session->set_flashdata('error', 'Informe o colaborador ou um nome.');
            redirect('obraequipe/membros/' . $equipe_id);
        }
        $this->obra_equipe_model->addMembro([
            'equipe_id' => $equipe_id,
            'colaborador_id' => $colab,
            'nome' => $nome ?: null,
            'funcao' => $this->input->post('funcao'),
            'valor_diaria' => (float) $this->input->post('valor_diaria'),
            'valor_hora' => (float) $this->input->post('valor_hora'),
            'data_entrada' => date('Y-m-d'),
        ]);
        $this->session->set_flashdata('success', 'Membro adicionado.');
        redirect('obraequipe/membros/' . $equipe_id);
    }

    public function excluirMembro()
    {
        $this->precisa('cObraEquipe', 'Sem permissão.');
        $equipe_id = (int) $this->input->post('equipe_id');
        $this->obra_equipe_model->deleteMembro((int) $this->input->post('idMembro'));
        $this->session->set_flashdata('success', 'Membro removido.');
        redirect('obraequipe/membros/' . $equipe_id);
    }

    public function alocar()
    {
        $this->precisa('cObraEquipe', 'Sem permissão.');
        $equipe_id = (int) $this->input->post('equipe_id');
        $obra_id = (int) $this->input->post('obra_id');
        if ($obra_id && $equipe_id) {
            $ini = $this->dataOuNull($this->input->post('data_inicio'));
            $fim = $this->dataOuNull($this->input->post('data_fim'));
            $this->obra_equipe_model->alocar($obra_id, $equipe_id, $ini, $fim);
            $this->session->set_flashdata('success', 'Equipe alocada ao projeto.');
        }
        redirect('obraequipe/membros/' . $equipe_id);
    }

    public function desalocar()
    {
        $this->precisa('cObraEquipe', 'Sem permissão.');
        $equipe_id = (int) $this->input->post('equipe_id');
        $this->obra_equipe_model->desalocar((int) $this->input->post('idAlocacao'));
        $this->session->set_flashdata('success', 'Alocação removida.');
        redirect('obraequipe/membros/' . $equipe_id);
    }

    private function dataOuNull($valor)
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return null;
        }
        if (strpos($valor, '/') !== false) {
            $p = explode('/', $valor);
            if (count($p) === 3) {
                return $p[2] . '-' . $p[1] . '-' . $p[0];
            }
        }
        return $valor;
    }

    /** Converte um valor monetário digitado (pt-BR ou simples) em float. */
    private function parseMoeda($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return 0.0;
        }
        if (strpos($raw, ',') !== false) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        }
        return (float) $raw;
    }
}
