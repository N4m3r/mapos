<?php

class Login extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('mapos_model');
    }

    public function index()
    {
        $this->load->view('mapos/login');
    }

    public function sair()
    {
        $this->session->sess_destroy();

        return redirect($_SERVER['HTTP_REFERER']);
    }

    public function verificarLogin()
    {
        header('Access-Control-Allow-Origin: ' . base_url());
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Max-Age: 1000');
        header('Access-Control-Allow-Headers: Content-Type');

        $this->load->library('form_validation');
        $this->form_validation->set_rules('email', 'E-mail', 'valid_email|required|trim');
        $this->form_validation->set_rules('senha', 'Senha', 'required|trim');
        if ($this->form_validation->run() == false) {
            $json = ['result' => false, 'message' => validation_errors()];
            echo json_encode($json);
        } else {
            $email = $this->input->post('email');
            $password = $this->input->post('senha');

            // Forca bruta: bloqueio temporario apos varias tentativas erradas
            $this->load->library('limite_login');
            if ($espera = $this->limite_login->bloqueado('painel', $email)) {
                echo json_encode(['result' => false, 'message' => $this->limite_login->mensagem($espera), 'MAPOS_TOKEN' => $this->security->get_csrf_hash()]);
                exit();
            }

            $this->load->model('Mapos_model');
            $user = $this->Mapos_model->check_credentials($email);

            if ($user) {
                // Verificar se acesso está expirado
                if ($this->chk_date($user->dataExpiracao)) {
                    $json = ['result' => false, 'message' => 'A conta do usuário está expirada, por favor entre em contato com o administrador do sistema.'];
                    echo json_encode($json);
                    exit();
                }

                // Verificar credenciais do usuário
                if (password_verify($password, $user->senha)) {
                    $this->limite_login->sucesso('painel', $email);

                    // Verificacao em duas etapas: a sessao so e criada depois do codigo
                    if (! empty($user->totp_ativo)) {
                        $this->session->set_userdata('dois_fatores_pendente', ['id' => (int) $user->idUsuarios, 'ate' => time() + 300]);
                        echo json_encode(['result' => true, 'redirect' => site_url('login/doisFatores')]);
                        exit();
                    }

                    echo json_encode(['result' => true, 'redirect' => site_url($this->concluirLogin($user))]);
                } else {
                    $this->limite_login->falhou('painel', $email);
                    $json = ['result' => false, 'message' => 'Os dados de acesso estão incorretos.', 'MAPOS_TOKEN' => $this->security->get_csrf_hash()];
                    echo json_encode($json);
                }
            } else {
                // Mesma mensagem da senha errada: nao revela quais e-mails estao cadastrados
                $this->limite_login->falhou('painel', $email);
                $json = ['result' => false, 'message' => 'Os dados de acesso estão incorretos.', 'MAPOS_TOKEN' => $this->security->get_csrf_hash()];
                echo json_encode($json);
            }
        }
        exit();
    }

    /**
     * Tela do codigo de 6 digitos (depois da senha certa, para quem ativou a verificacao em duas etapas).
     */
    public function doisFatores()
    {
        if (! $this->usuarioPendente()) {
            redirect('login');
        }
        $this->load->helper('form');
        $this->load->view('mapos/login_dois_fatores');
    }

    public function verificarDoisFatores()
    {
        $usuario = $this->usuarioPendente();
        if (! $usuario) {
            $this->session->set_flashdata('error', 'A verificação expirou. Entre com e-mail e senha novamente.');
            redirect('login');
        }

        $this->load->library('limite_login');
        if ($espera = $this->limite_login->bloqueado('2fa', $usuario->email)) {
            $this->session->set_flashdata('error', $this->limite_login->mensagem($espera));
            redirect('login/doisFatores');
        }

        $this->load->library('dois_fatores');
        $tipo = $this->dois_fatores->verificar($usuario, $this->input->post('codigo'));
        if ($tipo === null) {
            $this->limite_login->falhou('2fa', $usuario->email);
            $this->session->set_flashdata('error', 'Código inválido. Confira o código no aplicativo e tente de novo.');
            redirect('login/doisFatores');
        }

        $this->limite_login->sucesso('2fa', $usuario->email);
        $this->session->unset_userdata('dois_fatores_pendente');
        $destino = $this->concluirLogin($usuario);
        if ($tipo === 'recuperacao') {
            $this->session->set_flashdata('error', 'Você entrou com um código de recuperação. Se perdeu o celular, gere novos códigos ou reconfigure a verificação em Minha Conta.');
        }
        redirect($destino);
    }

    /**
     * Cria a sessao do usuario autenticado e devolve a rota inicial do perfil.
     */
    private function concluirLogin($user)
    {
        // Novo id de sessao ao autenticar (evita fixacao de sessao)
        $this->session->sess_regenerate(true);
        $session_admin_data = ['nome_admin' => $user->nome, 'email_admin' => $user->email, 'url_image_user_admin' => $user->url_image_user, 'id_admin' => $user->idUsuarios, 'permissao' => $user->permissoes_id, 'logado' => true];
        $this->session->set_userdata($session_admin_data);
        log_info('Efetuou login no sistema');

        // Redirecionamento inteligente por perfil: tecnicos vao direto para a Area do Tecnico
        // e colaboradores para a Area do Colaborador (ambas mobile). Demais perfis seguem para o painel.
        $temOs = $this->permission->checkPermission($user->permissoes_id, 'vOs');
        $isTecnico = $this->permission->checkPermission($user->permissoes_id, 'vTecnicoDashboard') && ! $temOs;
        $isColaborador = ! $isTecnico && ! $temOs
            && $this->permission->checkPermission($user->permissoes_id, 'vAreaColaborador');

        if ($isTecnico) {
            return 'tecnico';
        }
        if ($isColaborador) {
            return 'colaborador';
        }

        return 'mapos';
    }

    /** Usuario que passou da senha e ainda deve o codigo (no maximo 5 minutos), ou null. */
    private function usuarioPendente()
    {
        $pendente = $this->session->userdata('dois_fatores_pendente');
        if (! is_array($pendente) || ($pendente['ate'] ?? 0) < time()) {
            return null;
        }
        $usuario = $this->db->where('idUsuarios', (int) $pendente['id'])->where('situacao', 1)->get('usuarios')->row();

        return ($usuario && ! empty($usuario->totp_ativo)) ? $usuario : null;
    }

    private function chk_date($data_banco)
    {
        $data_banco = new DateTime($data_banco);
        $data_hoje = new DateTime('now');

        return $data_banco < $data_hoje;
    }
}
