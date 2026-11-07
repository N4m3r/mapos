<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Perfis de permissao.
 *
 * O formulario vem do catalogo unico (helpers/permissoes_helper.php). Antes,
 * cadastrar e editar tinham cada um a sua lista de campos e a de editar salvava
 * menos chaves: editar um perfil apagava as permissoes de Contrato. Agora as
 * duas rotas passam por salvar(), que grava exatamente as chaves do catalogo.
 */
class Permissoes extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cPermissao')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar as permissões no sistema.');
            redirect(base_url());
        }

        $this->load->helper(['form', 'codegen_helper', 'permissoes']);
        $this->load->model('permissoes_model');
        $this->data['menuConfiguracoes'] = 'Permissões';
    }

    public function index()
    {
        $this->gerenciar();
    }

    public function gerenciar()
    {
        $this->load->library('pagination');

        $this->data['configuration']['base_url'] = site_url('permissoes/gerenciar/');
        $this->data['configuration']['total_rows'] = $this->permissoes_model->count('permissoes');

        $this->pagination->initialize($this->data['configuration']);

        $resultados = $this->permissoes_model->get(
            'permissoes',
            'idPermissao,nome,data,situacao,permissoes',
            '',
            $this->data['configuration']['per_page'],
            $this->uri->segment(3)
        );

        $uso = $this->permissoes_model->usuariosAtivosPorPerfil();
        $this->data['results'] = [];
        foreach ($resultados as $perfil) {
            $perfil->liberadas = permissoes_resumo($this->lerBlob($perfil->permissoes));
            $perfil->total_chaves = count(permissoes_todas_as_chaves());
            $perfil->usuarios = (int) ($uso[$perfil->idPermissao] ?? 0);
            $this->data['results'][] = $perfil;
        }

        $this->data['view'] = 'permissoes/permissoes';

        return $this->layout();
    }

    public function adicionar()
    {
        $inicial = $this->estadoInicial();

        $this->data['secoes'] = permissoes_catalogo();
        $this->data['salvas'] = $inicial['salvas'];
        $this->data['nome'] = $inicial['nome'];
        $this->data['situacao'] = 1;
        $this->data['idPermissao'] = null;
        $this->data['acao'] = site_url('permissoes/adicionar');
        $this->data['titulo'] = 'Novo perfil de permissão';
        $this->data['subtitulo'] = 'Escolha o que este perfil pode ver e fazer. Um perfil novo nasce com as leituras essenciais.';
        $this->data['base'] = $inicial['base'];
        $this->data['custom_error'] = '';

        if ($this->input->post()) {
            if ($this->salvar(null)) {
                return;
            }
            $this->data['salvas'] = permissoes_do_post(fn ($c) => $this->input->post($c));
            $this->data['nome'] = (string) $this->input->post('nome');
        }

        $this->prepararCsrf();
        $this->data['view'] = 'permissoes/formulario';

        return $this->layout();
    }

    public function editar()
    {
        $id = $this->uri->segment(3);
        if (! $id || ! is_numeric($id)) {
            $this->session->set_flashdata('error', 'Perfil não encontrado, parâmetro não foi passado corretamente.');
            redirect(site_url('permissoes'));
        }

        $perfil = $this->permissoes_model->getById($id);
        if (! $perfil) {
            $this->session->set_flashdata('error', 'Perfil não encontrado.');
            redirect(site_url('permissoes'));
        }

        $this->data['secoes'] = permissoes_catalogo();
        $this->data['salvas'] = permissoes_normalizar($this->lerBlob($perfil->permissoes));
        $this->data['nome'] = $perfil->nome;
        $this->data['situacao'] = (int) $perfil->situacao;
        $this->data['idPermissao'] = $perfil->idPermissao;
        $this->data['acao'] = site_url('permissoes/editar/' . $perfil->idPermissao);
        $this->data['titulo'] = 'Editar perfil de permissão';
        $this->data['subtitulo'] = 'As alterações valem para todos os usuários deste perfil, no próximo acesso de cada um.';
        $this->data['base'] = null;
        $this->data['custom_error'] = '';

        if ($this->input->post()) {
            if ($this->salvar($perfil)) {
                return;
            }
            // Repovoa com o que foi enviado, para não perder a escolha em caso de erro.
            $this->data['salvas'] = permissoes_do_post(fn ($c) => $this->input->post($c));
            $this->data['nome'] = (string) $this->input->post('nome');
            $this->data['situacao'] = (int) $this->input->post('situacao');
        }

        $this->prepararCsrf();
        $this->data['view'] = 'permissoes/formulario';

        return $this->layout();
    }

    /**
     * Duplica um perfil existente como ponto de partida para um novo.
     * `/permissoes/adicionar?perfil=ID`
     */
    public function duplicar()
    {
        $id = $this->uri->segment(3);
        if (! $id || ! is_numeric($id)) {
            redirect(site_url('permissoes'));
        }
        redirect(site_url('permissoes/adicionar?perfil=' . (int) $id));
    }

    public function desativar()
    {
        return $this->mudarSituacao(0);
    }

    public function reativar()
    {
        return $this->mudarSituacao(1);
    }

    /**
     * Grava o perfil (novo ou existente). Devolve true quando redirecionou.
     *
     * @param object|null $perfil perfil sendo editado; null cria um novo
     */
    private function salvar($perfil)
    {
        $id = $perfil ? (int) $perfil->idPermissao : null;

        $this->load->library('form_validation');
        $this->form_validation->set_rules('nome', 'Nome', 'trim|required|max_length[80]');
        if ($this->form_validation->run() == false) {
            $this->data['custom_error'] = '<div class="alert alert-danger">' . validation_errors() . '</div>';

            return false;
        }

        $situacao = (int) $this->input->post('situacao') === 1 ? 1 : 0;
        $permissoes = permissoes_do_post(fn ($chave) => $this->input->post($chave));
        $dados = [
            'nome' => (string) $this->input->post('nome'),
            'permissoes' => serialize($permissoes),
            'situacao' => $situacao,
        ];

        if ($id === null) {
            $dados['data'] = date('Y-m-d');
            $ok = $this->permissoes_model->add('permissoes', $dados);
            log_info('Adicionou um perfil de permissão: ' . $dados['nome']);
            $this->session->set_flashdata(
                $ok ? 'success' : 'error',
                $ok ? 'Perfil adicionado com sucesso!' : 'Ocorreu um erro ao adicionar o perfil.'
            );
            redirect(site_url('permissoes'));

            return true;
        }

        $ok = $this->permissoes_model->edit('permissoes', $dados, 'idPermissao', $id);
        log_info('Alterou o perfil de permissão ID: ' . $id);
        $this->session->set_flashdata(
            $ok ? 'success' : 'error',
            $ok ? 'Perfil atualizado com sucesso!' : 'Ocorreu um erro ao atualizar o perfil.'
        );
        redirect(site_url('permissoes/editar/' . $id));

        return true;
    }

    /**
     * Ativa ou desativa um perfil. Um perfil com usuários ativos não é
     * desativado: isso deixaria essas contas num perfil que a tela de usuário
     * não oferece mais, e a próxima edição trocaria o acesso sem aviso.
     */
    private function mudarSituacao($situacao)
    {
        $id = (int) $this->input->post('id');
        if ($id < 1) {
            $this->session->set_flashdata('error', 'Perfil não informado.');
            redirect(site_url('permissoes'));
        }

        if ($id === 1 && $situacao === 0) {
            $this->session->set_flashdata('error', 'O perfil Administrador não pode ser desativado.');
            redirect(site_url('permissoes'));
        }

        if ($situacao === 0) {
            $uso = $this->permissoes_model->usuariosAtivosPorPerfil();
            $quantos = (int) ($uso[$id] ?? 0);
            if ($quantos > 0) {
                $this->session->set_flashdata(
                    'error',
                    'Este perfil ainda está em uso por ' . $quantos . ' usuário(s) ativo(s). Troque o perfil dessas contas antes de desativá-lo.'
                );
                redirect(site_url('permissoes'));
            }
        }

        if ($this->permissoes_model->edit('permissoes', ['situacao' => $situacao], 'idPermissao', $id)) {
            log_info(($situacao === 1 ? 'Reativou' : 'Desativou') . ' um perfil de permissão. ID: ' . $id);
            $this->session->set_flashdata('success', $situacao === 1 ? 'Perfil reativado.' : 'Perfil desativado.');
        } else {
            $this->session->set_flashdata('error', 'Não foi possível alterar a situação do perfil.');
        }

        redirect(site_url('permissoes'));
    }

    /**
     * Estado inicial do formulario de um perfil novo: as leituras padrao do
     * catalogo, ou a copia de um perfil existente quando ?perfil=ID.
     *
     * @return array{salvas: array<string, int>, nome: string, base: ?object}
     */
    private function estadoInicial()
    {
        $id = (int) $this->input->get('perfil');
        if ($id > 0) {
            $perfil = $this->permissoes_model->getById($id);
            if ($perfil) {
                return [
                    'salvas' => permissoes_normalizar($this->lerBlob($perfil->permissoes)),
                    'nome' => trim($perfil->nome . ' (cópia)'),
                    'base' => $perfil,
                ];
            }
        }

        return [
            'salvas' => permissoes_normalizar(array_fill_keys(permissoes_chaves_padrao(), 1)),
            'nome' => '',
            'base' => null,
        ];
    }

    /**
     * Desserializa o blob de permissoes tolerando lixo depois do array (o
     * "Extra data" que ja apareceu em producao) sem derrubar a pagina.
     *
     * @return array<string, mixed>
     */
    private function lerBlob($bruto)
    {
        $bruto = (string) $bruto;
        if ($bruto === '') {
            return [];
        }

        set_error_handler(static function () {
            return true;
        });
        $perms = unserialize($bruto, ['allowed_classes' => false]);
        restore_error_handler();

        return is_array($perms) ? $perms : [];
    }

    private function prepararCsrf()
    {
        $this->data['tokenName'] = $this->security->get_csrf_token_name();
        $this->data['tokenHash'] = $this->security->get_csrf_hash();
    }
}

/* End of file permissoes.php */
/* Location: ./application/controllers/permissoes.php */
