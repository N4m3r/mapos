<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Contas de acesso.
 *
 * Antes o cadastro e a edição eram duas telas com campos soltos, sem busca,
 * sem filtro por perfil e sem indicar quem estava com a conta vencida. Agora
 * as duas passam por um formulário único em abas (dados, endereço, acesso e
 * segurança), e a lista mostra perfil, situação, validade e último acesso.
 *
 * O grupo de validação "usuarios" é compartilhado com a API e não muda aqui:
 * esta tela valida com as próprias regras, que aceitam validade em branco
 * (validade nula null = sem expiração) e exigem senha só na criação.
 */
class Usuarios extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cUsuario')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar os usuários.');
            redirect(base_url());
        }

        $this->load->helper(['form', 'permissoes']);
        $this->load->model(['usuarios_model', 'permissoes_model']);
        $this->data['menuUsuarios'] = 'Usuários';
        $this->data['menuConfiguracoes'] = 'Configurações';
    }

    public function index()
    {
        $this->gerenciar();
    }

    public function gerenciar()
    {
        $this->load->library('pagination');

        $filtros = $this->filtrosDaRequisicao();

        $config = $this->data['configuration'];
        $config['base_url'] = site_url('usuarios/gerenciar');
        $config['total_rows'] = $this->usuarios_model->countComFiltros($filtros);
        $config['reuse_query_string'] = true;
        $config['page_query_string'] = true;
        $config['query_string_segment'] = 'pagina';
        $this->pagination->initialize($config);

        $pagina = max(0, (int) $this->input->get('pagina'));
        $this->data['results'] = $this->usuarios_model->get($config['per_page'], $pagina, false, $filtros);
        $this->data['filtros'] = $filtros;
        $this->data['perfis'] = $this->permissoes_model->todos();
        $this->data['view'] = 'usuarios/usuarios';

        return $this->layout();
    }

    public function adicionar()
    {
        $this->data['usuario'] = null;
        $this->data['editar'] = false;
        $this->data['custom_error'] = '';

        if ($this->input->post()) {
            $erros = $this->validar(null);
            if (empty($erros)) {
                $dados = $this->dadosDoPost(null);

                if ($this->usuarios_model->add('usuarios', $dados)) {
                    log_info('Adicionou um usuário.');
                    $this->session->set_flashdata('success', 'Usuário cadastrado com sucesso.');
                    redirect(site_url('usuarios'));
                }
                $this->data['custom_error'] = '<div class="alert alert-danger">Não foi possível cadastrar o usuário.</div>';
            } else {
                $this->data['custom_error'] = $this->mensagemErros($erros);
            }
        }

        $this->carregarAuxiliares();

        $this->data['view'] = 'usuarios/formulario';

        return $this->layout();
    }

    public function editar()
    {
        $id = (int) $this->uri->segment(3);
        $usuario = $id > 0 ? $this->usuarios_model->getById($id) : null;
        if (! $usuario) {
            $this->session->set_flashdata('error', 'Usuário não encontrado.');
            redirect('usuarios');
        }

        $this->data['usuario'] = $usuario;
        $this->data['editar'] = true;
        $this->data['custom_error'] = '';

        if ($this->input->post()) {
            $erros = $this->validar($usuario);
            if (empty($erros)) {
                $dados = $this->dadosDoPost($usuario);

                if ($usuario->idUsuarios == 1 && (int) $this->input->post('situacao') === 0) {
                    $this->session->set_flashdata('error', 'O usuário super admin não pode ser desativado.');
                    redirect(site_url('usuarios/editar/' . $usuario->idUsuarios));
                }

                if ($this->usuarios_model->edit('usuarios', $dados, 'idUsuarios', $usuario->idUsuarios)) {
                    log_info('Alterou um usuário. ID: ' . $usuario->idUsuarios);
                    $this->session->set_flashdata('success', 'Usuário atualizado com sucesso.');
                    redirect(site_url('usuarios/editar/' . $usuario->idUsuarios));
                }
                $this->data['custom_error'] = '<div class="alert alert-danger">Não foi possível atualizar o usuário.</div>';
            } else {
                $this->data['custom_error'] = $this->mensagemErros($erros);
            }
        }

        $this->load->library('dois_fatores');
        $this->data['doisFatoresAtivo'] = ! empty($usuario->totp_ativo);
        $this->data['codigosRestantes'] = Dois_fatores::codigosRestantes($usuario->totp_recuperacao ?? null);
        $this->carregarAuxiliares();

        $this->data['view'] = 'usuarios/formulario';

        return $this->layout();
    }

    /**
     * Remove a conta. Só por POST e apenas quando não há lançamento, OS,
     * venda ou garantia apontando para ela — senão o histórico perderia o
     * responsável. Nesse caso, o caminho é desativar.
     */
    public function excluir()
    {
        if ($this->input->method() !== 'post') {
            show_404();

            return;
        }

        $id = (int) $this->input->post('id');
        $atual = (int) $this->session->userdata('id_admin');

        if ($id < 1 || $id === $atual) {
            $this->session->set_flashdata('error', 'Você não pode excluir a própria conta.');
            redirect(site_url('usuarios'));
        }
        if ($id === 1) {
            $this->session->set_flashdata('error', 'O usuário super admin não pode ser excluído.');
            redirect(site_url('usuarios'));
        }

        $vinculos = $this->usuarios_model->contarVinculos($id);
        if ($vinculos > 0) {
            $this->session->set_flashdata(
                'error',
                'Este usuário está ligado a ' . $vinculos . ' registro(s) do histórico (OS, vendas, lançamentos ou garantias). Desative a conta em vez de excluir.'
            );
            redirect(site_url('usuarios'));
        }

        if ($this->usuarios_model->delete('usuarios', 'idUsuarios', $id)) {
            log_info('Removeu um usuário. ID: ' . $id);
            $this->session->set_flashdata('success', 'Usuário excluído.');
        } else {
            $this->session->set_flashdata('error', 'Não foi possível excluir o usuário.');
        }

        redirect(site_url('usuarios'));
    }

    /**
     * @return array{pesquisa: string, perfil: int, situacao: string}
     */
    private function filtrosDaRequisicao()
    {
        $situacao = (string) $this->input->get('situacao');

        return [
            'pesquisa' => trim((string) $this->input->get('pesquisa')),
            'perfil' => (int) $this->input->get('perfil'),
            'situacao' => in_array($situacao, ['0', '1'], true) ? $situacao : '',
        ];
    }

    /**
     * @param object|null $usuario conta em edição (null ao criar)
     *
     * @return array<string, string>
     */
    private function validar($usuario)
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('nome', 'Nome', 'trim|required|max_length[80]');
        $this->form_validation->set_rules('email', 'E-mail', 'trim|required|valid_email|max_length[80]');
        $this->form_validation->set_rules('telefone', 'Telefone', 'trim|required|max_length[20]');
        $this->form_validation->set_rules('situacao', 'Situação', 'trim|required|in_list[0,1]');
        $this->form_validation->set_rules('permissoes_id', 'Perfil', 'trim|required|numeric');

        $erros = [];
        if ($this->form_validation->run() == false) {
            $erros = array_filter([
                form_error('nome'),
                form_error('email'),
                form_error('telefone'),
                form_error('situacao'),
                form_error('permissoes_id'),
            ]);
        }

        $email = trim((string) $this->input->post('email'));
        $idAtual = $usuario ? (int) $usuario->idUsuarios : 0;
        if ($email !== '' && $this->usuarios_model->emailEmUso($email, $idAtual)) {
            $erros[] = 'Já existe outra conta com este e-mail.';
        }

        $cpf = trim((string) $this->input->post('cpf'));
        if ($cpf !== '') {
            if (function_exists('verific_cpf_cnpj') && ! verific_cpf_cnpj($cpf)) {
                $erros[] = 'O CPF informado não é válido.';
            } elseif ($this->usuarios_model->cpfEmUso($cpf, $idAtual)) {
                $erros[] = 'Já existe outra conta com este CPF.';
            }
        }

        $senha = (string) $this->input->post('senha');
        if (! $usuario && $senha === '') {
            $erros[] = 'Defina uma senha para a nova conta.';
        }
        if ($senha !== '' && mb_strlen($senha) < 6) {
            $erros[] = 'A senha precisa de pelo menos 6 caracteres.';
        }

        $perfil = (int) $this->input->post('permissoes_id');
        if ($perfil > 0) {
            $registro = $this->permissoes_model->getById($perfil);
            if (! $registro) {
                $erros[] = 'O perfil escolhido não existe.';
            } elseif ((int) $registro->situacao !== 1 && (! $usuario || (int) $usuario->permissoes_id !== $perfil)) {
                $erros[] = 'O perfil escolhido está inativo.';
            }
        }

        $validade = $this->dataOuNull($this->input->post('dataExpiracao'));
        if ($validade !== null && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $validade)) {
            $erros[] = 'A data de validade não é válida.';
        }

        return $erros;
    }

    /**
     * @param object|null $usuario
     *
     * @return array<string, mixed>
     */
    private function dadosDoPost($usuario)
    {
        $dados = [
            'nome' => trim((string) $this->input->post('nome')),
            'rg' => trim((string) $this->input->post('rg')),
            'cpf' => trim((string) $this->input->post('cpf')),
            'cep' => trim((string) $this->input->post('cep')),
            'rua' => trim((string) $this->input->post('rua')),
            'numero' => trim((string) $this->input->post('numero')),
            'bairro' => trim((string) $this->input->post('bairro')),
            'cidade' => trim((string) $this->input->post('cidade')),
            'estado' => strtoupper(trim((string) $this->input->post('estado'))),
            'email' => trim((string) $this->input->post('email')),
            'telefone' => trim((string) $this->input->post('telefone')),
            'celular' => trim((string) $this->input->post('celular')),
            'dataExpiracao' => $this->dataOuNull($this->input->post('dataExpiracao')),
            'situacao' => (int) $this->input->post('situacao') === 1 ? 1 : 0,
            'permissoes_id' => (int) $this->input->post('permissoes_id'),
        ];

        $senha = (string) $this->input->post('senha');
        if ($senha !== '') {
            $dados['senha'] = password_hash($senha, PASSWORD_DEFAULT);
        }
        if (! $usuario) {
            $dados['dataCadastro'] = date('Y-m-d');
        }

        // Administrador desliga a verificação em duas etapas de quem perdeu o
        // celular e os códigos de recuperação.
        if ($usuario && $this->input->post('desativar_dois_fatores')) {
            $dados['totp_segredo'] = null;
            $dados['totp_ativo'] = 0;
            $dados['totp_recuperacao'] = null;
            $dados['totp_ultimo_passo'] = null;
            log_info('Desativou a verificação em duas etapas do usuário ID: ' . $usuario->idUsuarios);
        }

        return $dados;
    }

    /** @param array<int, string> $erros */
    private function mensagemErros(array $erros)
    {
        return '<div class="alert alert-danger"><ul style="margin:0;padding-left:18px">'
            . implode('', array_map(static function ($e) {
                return '<li>' . htmlspecialchars(strip_tags((string) $e), ENT_QUOTES, 'UTF-8') . '</li>';
            }, $erros))
            . '</ul></div>';
    }

    private function carregarAuxiliares()
    {
        $this->data['perfis'] = $this->permissoes_model->todos();
        $this->data['tokenName'] = $this->security->get_csrf_token_name();
        $this->data['tokenHash'] = $this->security->get_csrf_hash();
        $this->data['podeVerPerfis'] = $this->permission->checkPermission($this->session->userdata('permissao'), 'cPermissao');
        $this->data['proprioId'] = (int) $this->session->userdata('id_admin');
    }
}
