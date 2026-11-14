<?php if (!defined('BASEPATH')) { exit('No direct script access allowed'); }
class Mapos extends MY_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->model('mapos_model');
    }

    public function index()
    {
        // Tecnicos (sem acesso ao fluxo padrao de OS) vao direto para a Area do Tecnico,
        // otimizada para uso em campo. Evita cair no painel administrativo pesado.
        $temOs = $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs');
        if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vTecnicoDashboard') && !$temOs) {
            redirect('tecnico');
        }
        // Colaboradores (autoatendimento) vao direto para a Area do Colaborador.
        if (!$temOs
            && !$this->permission->checkPermission($this->session->userdata('permissao'), 'vTecnicoDashboard')
            && $this->permission->checkPermission($this->session->userdata('permissao'), 'vAreaColaborador')) {
            redirect('colaborador');
        }

        // Painel inicial enxuto: cada bloco carrega so o necessario para a tela,
        // e cada consulta respeita a permissao de quem esta vendo.
        $perm = $this->session->userdata('permissao');
        $pode = fn ($atividade) => $this->permission->checkPermission($perm, $atividade);

        $this->load->helper('painel_inicio');
        $this->load->model('painel_model');

        $contagens = $this->painel_model->contagemPorStatus();
        $this->data['pi_status'] = Painel_inicio::resumoStatus($contagens);
        $this->data['pi_em_aberto'] = Painel_inicio::totalEmAberto($contagens);
        $this->data['pi_agenda'] = $this->painel_model->agendaResumo();
        $this->data['pi_movimento'] = $this->painel_model->movimentoDeHoje();
        $this->data['pi_os'] = $pode('vOs') ? $this->painel_model->osEmAberto(8) : [];
        $this->data['pi_serie'] = Painel_inicio::serieDias($this->painel_model->movimentoPorDia(14));
        $this->data['pi_status_pontos'] = Painel_inicio::pontosStatus($contagens);
        $this->data['pi_nome'] = (string) $this->session->userdata('nome_admin');
        $this->data['pi_iniciais'] = Painel_inicio::iniciais($this->data['pi_nome']);

        // Cobranca e financeiro so para quem tem acesso aos lancamentos
        $this->data['pi_pendentes'] = null;
        $this->data['pi_cobrar'] = [];
        $this->data['pi_mes'] = null;
        $this->data['pi_meses'] = [];
        $this->data['pi_ano'] = (int) date('Y');
        if ($pode('vLancamento')) {
            $this->data['pi_pendentes'] = $this->painel_model->totaisPendentes();
            $this->data['pi_cobrar'] = $this->painel_model->lancamentosPendentes(6, true);
            if ($pode('rFinanceiro')) {
                $this->data['pi_mes'] = $this->painel_model->financeiroDoMes();
                $ano = (int) preg_replace('/[^0-9]/', '', (string) $this->input->get('year'));
                $this->data['pi_ano'] = $ano ?: (int) date('Y');
                $this->data['pi_meses'] = $this->painel_model->financeiroPorMes($this->data['pi_ano']);
            }
        }

        $this->data['pi_estoque'] = $pode('vProduto') ? $this->painel_model->estoqueMinimo(5) : null;
        $this->data['pi_vendas_mes'] = $pode('vVenda') ? $this->painel_model->vendasDoMes() : null;

        // IVA-T9: lembrete da janela de opcao IBS/CBS do Simples (so para quem emite nota)
        $this->data['avisoJanelaIbscbs'] = null;
        if ($this->permission->checkPermission($this->session->userdata('permissao'), 'cNfe')) {
            $this->load->helper('tributacao');
            $j = tributacao_janela_opcao(date('Y-m-d'));
            if ($j && ($j['aberta'] || $j['dias_para_abrir'] <= 30)) {
                $this->data['avisoJanelaIbscbs'] = $j;
            }
        }
        $this->data['menuPainel'] = 'Painel';
        $this->data['view'] = 'mapos/painel';

        return $this->layout();
    }

    public function minhaConta()
    {
        $this->data['usuario'] = $this->mapos_model->getById($this->session->userdata('id_admin'));
        $this->load->library('dois_fatores');
        $this->data['codigosRestantes'] = Dois_fatores::codigosRestantes($this->data['usuario']->totp_recuperacao ?? null);

        // Modo escolhido no botao claro/escuro do topo (cookie lido no MY_Controller).
        $modo = $this->input->cookie('mapos_modo_' . (int) $this->session->userdata('id_admin'));
        $this->data['modoTema'] = in_array($modo, ['claro', 'escuro'], true) ? $modo : 'sistema';

        // Ultimos logins (a auditoria grava pelo nome do usuario; sem migration).
        $this->data['acessosRecentes'] = [];
        if ($this->data['usuario'] && $this->db->table_exists('logs')) {
            $this->data['acessosRecentes'] = $this->db->select('data, hora, ip')
                ->from('logs')
                ->where('usuario', $this->data['usuario']->nome)
                ->where('tarefa', 'Efetuou login no sistema')
                ->order_by('idLogs', 'DESC')
                ->limit(5)
                ->get()->result();
        }
        $this->data['view'] = 'mapos/minhaConta';

        return $this->layout();
    }

    public function alterarSenha()
    {
        $current_user = $this->mapos_model->getById($this->session->userdata('id_admin'));

        if (!$current_user) {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao pesquisar usuário!');
            redirect(site_url('mapos/minhaConta'));
        }

        $oldSenha = (string) $this->input->post('oldSenha');
        $senha = (string) $this->input->post('novaSenha');

        if (!password_verify($oldSenha, $current_user->senha)) {
            $this->session->set_flashdata('error', 'A senha atual não corresponde com a senha informada.');
            redirect(site_url('mapos/minhaConta'));
        }

        // Antes so o JS validava: um POST direto gravava senha vazia.
        $erro = senha_nova_erro($senha, $this->input->post('confirmarSenha'));
        if ($erro !== null) {
            $this->session->set_flashdata('error', $erro);
            redirect(site_url('mapos/minhaConta'));
        }

        $result = $this->mapos_model->alterarSenha($senha);

        if ($result) {
            log_info('Alterou a própria senha');
            $this->session->set_flashdata('success', 'Senha alterada com sucesso!');
            redirect(site_url('mapos/minhaConta'));
        }

        $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar alterar a senha!');
        redirect(site_url('mapos/minhaConta'));
    }

    /* ============ Verificacao em duas etapas (ADR-005, F3) ============ */

    /**
     * Mostra o QR code para cadastrar no aplicativo; o segredo so e gravado depois de confirmado.
     */
    public function doisFatores()
    {
        $usuario = $this->mapos_model->getById($this->session->userdata('id_admin'));
        if (! empty($usuario->totp_ativo)) {
            redirect('mapos/minhaConta');
        }

        $this->load->library('dois_fatores');
        $segredo = $this->session->userdata('totp_configurando');
        if (! $segredo || $this->input->get('novo')) {
            $segredo = Dois_fatores::gerarSegredo();
            $this->session->set_userdata('totp_configurando', $segredo);
        }

        $emissor = (string) ($this->config->item('app_name') ?: 'Map-OS');
        $this->data['segredo'] = trim(chunk_split($segredo, 4, ' '));
        $this->data['qrCode'] = Dois_fatores::qrCode(Dois_fatores::uri($segredo, $usuario->email, $emissor));
        $this->data['view'] = 'mapos/dois_fatores';

        return $this->layout();
    }

    public function doisFatoresConfirmar()
    {
        $segredo = $this->session->userdata('totp_configurando');
        if (! $segredo) {
            redirect('mapos/doisFatores');
        }

        $this->load->library('dois_fatores');
        $passo = Dois_fatores::passoDoCodigo($segredo, $this->input->post('codigo'));
        if ($passo === null) {
            $this->session->set_flashdata('error', 'Código inválido. Confira se o relógio do celular está certo e digite o código atual.');
            redirect('mapos/doisFatores');
        }

        $idUsuario = $this->session->userdata('id_admin');
        $codigos = $this->dois_fatores->ativar($idUsuario, $segredo, $passo);
        $this->session->unset_userdata('totp_configurando');
        log_info('Ativou a verificação em duas etapas');

        $this->data['codigos'] = $codigos;
        $this->data['view'] = 'mapos/dois_fatores_codigos';

        return $this->layout();
    }

    public function doisFatoresNovosCodigos()
    {
        $usuario = $this->usuarioComSenhaConferida();
        $this->load->library('dois_fatores');
        $this->data['codigos'] = $this->dois_fatores->novosCodigosRecuperacao($usuario->idUsuarios);
        log_info('Gerou novos códigos de recuperação da verificação em duas etapas');
        $this->data['view'] = 'mapos/dois_fatores_codigos';

        return $this->layout();
    }

    public function doisFatoresDesativar()
    {
        $usuario = $this->usuarioComSenhaConferida();
        $this->load->library('dois_fatores');
        $this->dois_fatores->desativar($usuario->idUsuarios);
        log_info('Desativou a verificação em duas etapas');
        $this->session->set_flashdata('success', 'Verificação em duas etapas desativada.');
        redirect('mapos/minhaConta');
    }

    /** Usuario logado, se a senha atual enviada no POST conferir; senao volta para Minha Conta. */
    private function usuarioComSenhaConferida()
    {
        $usuario = $this->db->where('idUsuarios', (int) $this->session->userdata('id_admin'))->get('usuarios')->row();
        if (! $usuario || empty($usuario->totp_ativo) || ! password_verify((string) $this->input->post('senha'), $usuario->senha)) {
            $this->session->set_flashdata('error', 'Senha atual incorreta.');
            redirect('mapos/minhaConta');
        }

        return $usuario;
    }

    public function pesquisar()
    {
        $termo = $this->input->get('termo');

        $data['results'] = $this->mapos_model->pesquisar($termo);
        $this->data['produtos'] = $data['results']['produtos'];
        $this->data['servicos'] = $data['results']['servicos'];
        $this->data['os'] = $data['results']['os'];
        $this->data['clientes'] = $data['results']['clientes'];
        $this->data['view'] = 'mapos/pesquisa';

        return $this->layout();
    }

    public function backup()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cBackup')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para efetuar backup.');
            redirect(base_url());
        }

        $this->load->library('backup_banco');
        $backup = $this->backup_banco->gerarZip();

        log_info('Efetuou backup do banco de dados.');

        $this->load->helper('download');
        force_download('backup' . date('d-m-Y H-i-s') . '.zip', $backup);
    }

    /**
     * Lista dos backups automaticos (gerados por "php index.php backup/executar").
     */
    public function backups()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cBackup')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para ver os backups.');
            redirect(base_url());
        }

        $this->load->library('backup_banco');
        $this->data['menuConfiguracoes'] = 'Backup';
        $this->data['backups'] = $this->backup_banco->listar();
        $this->data['pasta'] = $this->backup_banco->pasta();
        $this->data['retencao'] = $this->backup_banco->retencaoDias();
        $this->data['view'] = 'mapos/backups';

        return $this->layout();
    }

    public function baixarBackup($nome = null)
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cBackup')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para baixar backups.');
            redirect(base_url());
        }

        $this->load->library('backup_banco');
        $arquivo = $this->backup_banco->caminho(rawurldecode((string) $nome));
        if ($arquivo === null) {
            show_404();
        }

        log_info('Baixou o backup automático ' . basename($arquivo));
        $this->load->helper('download');
        force_download($arquivo, null);
    }

    /**
     * Tela do emitente: dados da empresa e a conferencia do que falta para
     * emitir nota. O checklist usa o mesmo criterio da emissao (NfeService),
     * entao a tela avisa antes, e nao no meio do faturamento.
     */
    public function emitente()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmitente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar emitente.');
            redirect(base_url());
        }

        $this->load->helper(['emitente', 'form']);

        $dados = $this->mapos_model->getEmitente();

        // O modulo fiscal e opcional: instalacao sem a tabela precisa abrir a
        // tela do mesmo jeito, so com o checklist incompleto.
        $configFiscal = null;
        if ($this->db->table_exists('configuracoes_nfe')) {
            $this->load->model('nfe_model');
            $configFiscal = $this->nfe_model->getConfig();
        }

        $checklist = emitente_checklist_fiscal($dados, $configFiscal, $this->certificadoPresente($configFiscal));

        $this->data['dados'] = $dados;
        $this->data['checklist'] = $checklist;
        $this->data['resumoChecklist'] = emitente_checklist_resumo($checklist);
        $this->data['configFiscalExiste'] = $configFiscal !== null;
        $this->data['tokenName'] = $this->security->get_csrf_token_name();
        $this->data['tokenHash'] = $this->security->get_csrf_hash();
        $this->data['menuConfiguracoes'] = 'Configuracoes';
        $this->data['view'] = 'mapos/emitente';

        return $this->layout();
    }

    /**
     * Ha certificado A1 guardado? Nao carrega o .pfx (isso depende da senha e da
     * biblioteca fiscal): so confere se o arquivo esta no lugar.
     */
    private function certificadoPresente($configFiscal)
    {
        $caminho = trim((string) ($configFiscal->certificado_path ?? ''));
        if ($caminho === '') {
            return false;
        }
        if (is_file($caminho)) {
            return true;
        }

        // Deploy pode ter mudado o caminho absoluto; a pasta padrao e o fallback
        // usado tambem pelo CertificadoHelper.
        return is_file(APPPATH . 'arquivos_fiscais' . DIRECTORY_SEPARATOR . 'certificado' . DIRECTORY_SEPARATOR . basename($caminho));
    }

    /**
     * Cria a pasta de upload com .htaccess e index.html. As pastas sao ignoradas
     * pelo git (conteudo de runtime), entao a guarda precisa nascer junto com a
     * pasta — sem isso o servidor passa a servir o que for enviado para la.
     */
    private function prepararPasta($pasta, $extensoesPermitidas = '')
    {
        if (!is_dir($pasta) && !mkdir($pasta, 0755, true) && !is_dir($pasta)) {
            return false;
        }

        $htaccess = $pasta . '.htaccess';
        if (!is_file($htaccess)) {
            $conteudo = "Options -Indexes\n"
                . "<FilesMatch \"\\.(php|phtml|phar|cgi|pl|py|sh|cgi-bin)$\">\n"
                . "    Require all denied\n"
                . "</FilesMatch>\n";
            file_put_contents($htaccess, $conteudo);
        }

        $index = $pasta . 'index.html';
        if (!is_file($index)) {
            file_put_contents($index, '<!DOCTYPE html><title>403 Forbidden</title><p>Acesso negado.</p>');
        }

        return true;
    }

    /**
     * Recebe a logo do emitente e devolve o nome do arquivo, ou null quando nao
     * veio arquivo ou ele foi recusado. Antes um erro de upload dava print_r e
     * exit(): pagina branca com texto de debug no lugar da tela.
     */
    public function do_upload()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmitente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar emitente.');
            redirect(base_url());
        }

        if (!isset($_FILES['userfile']) || (int) $_FILES['userfile']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $image_upload_folder = FCPATH . 'assets/uploads' . DIRECTORY_SEPARATOR;
        if (!$this->prepararPasta($image_upload_folder)) {
            $this->session->set_flashdata('error', 'Não foi possível criar a pasta de uploads em assets/uploads.');
            return null;
        }

        $this->load->library('upload');
        $this->upload->initialize([
            'upload_path' => $image_upload_folder,
            'allowed_types' => 'png|jpg|jpeg|bmp',
            'max_size' => 2048,
            'remove_spaces' => true,
            'encrypt_name' => true,
        ]);

        if (!$this->upload->do_upload()) {
            $this->session->set_flashdata('error', 'Logo não aceita: ' . strip_tags($this->upload->display_errors('', '')));

            return null;
        }

        $info = $this->upload->data();

        return $info['file_name'];
    }

    /**
     * Mesma coisa para a foto do usuario, com o gate certo: quem troca a
     * propria foto nao precisa de cUsuario. A permissao e checada em
     * uploadUserImage(), que so deixa alterar a conta da propria sessao.
     */
    public function do_upload_user()
    {
        if (!isset($_FILES['userfile']) || (int) $_FILES['userfile']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $image_upload_folder = FCPATH . 'assets/userImage' . DIRECTORY_SEPARATOR;
        if (!$this->prepararPasta($image_upload_folder)) {
            $this->session->set_flashdata('error', 'Não foi possível criar a pasta de fotos em assets/userImage.');
            return null;
        }

        $this->load->library('upload');
        $this->upload->initialize([
            'upload_path' => $image_upload_folder,
            'allowed_types' => 'png|jpg|jpeg|bmp',
            'max_size' => 2048,
            'remove_spaces' => true,
            'encrypt_name' => true,
        ]);

        if (!$this->upload->do_upload()) {
            $this->session->set_flashdata('error', 'Foto não aceita: ' . strip_tags($this->upload->display_errors('', '')));

            return null;
        }

        $info = $this->upload->data();

        return $info['file_name'];
    }

    /** Dados do formulario do emitente, com os nomes de coluna da tabela. */
    private function dadosDoEmitente()
    {
        return [
            'nome' => trim((string) $this->input->post('nome')),
            'nome_fantasia' => trim((string) $this->input->post('nome_fantasia')) ?: null,
            'cnpj' => trim((string) $this->input->post('cnpj')),
            'ie' => trim((string) $this->input->post('ie')) ?: null,
            'inscricao_municipal' => trim((string) $this->input->post('inscricao_municipal')) ?: null,
            'cep' => trim((string) $this->input->post('cep')),
            'rua' => trim((string) $this->input->post('logradouro')),
            'numero' => trim((string) $this->input->post('numero')),
            'complemento' => trim((string) $this->input->post('complemento')) ?: null,
            'bairro' => trim((string) $this->input->post('bairro')),
            'cidade' => trim((string) $this->input->post('cidade')),
            'uf' => strtoupper(trim((string) $this->input->post('uf'))),
            'telefone' => trim((string) $this->input->post('telefone')),
            'email' => trim((string) $this->input->post('email')),
        ];
    }

    /** Validacao comum do cadastro e da edicao do emitente. */
    private function validarEmitente()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('nome', 'Razão Social', 'required|trim|max_length[255]');
        $this->form_validation->set_rules('nome_fantasia', 'Nome fantasia', 'trim|max_length[255]');
        $this->form_validation->set_rules('cnpj', 'CNPJ', 'required|trim|max_length[45]');
        $this->form_validation->set_rules('ie', 'Inscrição estadual', 'trim|max_length[50]');
        $this->form_validation->set_rules('inscricao_municipal', 'Inscrição municipal', 'trim|max_length[20]');
        $this->form_validation->set_rules('cep', 'CEP', 'required|trim|max_length[20]');
        $this->form_validation->set_rules('logradouro', 'Logradouro', 'required|trim|max_length[70]');
        $this->form_validation->set_rules('numero', 'Número', 'required|trim|max_length[15]');
        $this->form_validation->set_rules('complemento', 'Complemento', 'trim|max_length[45]');
        $this->form_validation->set_rules('bairro', 'Bairro', 'required|trim|max_length[45]');
        $this->form_validation->set_rules('cidade', 'Cidade', 'required|trim|max_length[45]');
        $this->form_validation->set_rules('uf', 'UF', 'required|trim|exact_length[2]');
        $this->form_validation->set_rules('telefone', 'Telefone', 'required|trim|max_length[20]');
        $this->form_validation->set_rules('email', 'E-mail', 'required|trim|valid_email|max_length[255]');

        return $this->form_validation->run();
    }

    public function cadastrarEmitente()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmitente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar emitente.');
            redirect(base_url());
        }

        if ($this->input->method() !== 'post') {
            redirect(site_url('mapos/emitente'));
        }

        $this->load->helper('emitente');

        if (!$this->validarEmitente()) {
            $this->session->set_flashdata('error', 'Confira os campos obrigatórios: ' . strip_tags(validation_errors(' ', ' ')));
            redirect(site_url('mapos/emitente'));
        }

        $dados = $this->dadosDoEmitente();

        $arquivo = $this->do_upload();
        if ($arquivo !== null) {
            $dados['url_logo'] = base_url() . 'assets/uploads/' . $arquivo;
        }

        if ($this->mapos_model->addEmitente($dados)) {
            $this->session->set_flashdata('success', 'Dados do emitente cadastrados.');
            log_info('Cadastrou os dados do emitente.');
        } else {
            $this->session->set_flashdata('error', 'Não foi possível cadastrar os dados do emitente.');
        }

        redirect(site_url('mapos/emitente'));
    }

    public function editarEmitente()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmitente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar emitente.');
            redirect(base_url());
        }

        if ($this->input->method() !== 'post') {
            redirect(site_url('mapos/emitente'));
        }

        $id = (int) $this->input->post('id');
        if ($id < 1) {
            $this->session->set_flashdata('error', 'Emitente não informado.');
            redirect(site_url('mapos/emitente'));
        }

        $this->load->helper('emitente');

        if (!$this->validarEmitente()) {
            $this->session->set_flashdata('error', 'Confira os campos obrigatórios: ' . strip_tags(validation_errors(' ', ' ')));
            redirect(site_url('mapos/emitente'));
        }

        $dados = $this->dadosDoEmitente();

        // A logo so e tocada quando um arquivo novo chega; trocar o endereco
        // nao pode apagar a logo existente.
        $arquivo = $this->do_upload();
        if ($arquivo !== null) {
            $anterior = $this->mapos_model->getEmitente();
            $dados['url_logo'] = base_url() . 'assets/uploads/' . $arquivo;
            $this->apagarLogoAntiga($anterior, $dados['url_logo']);
        }

        if ($this->mapos_model->editEmitente($id, $dados)) {
            $this->session->set_flashdata('success', 'Dados do emitente atualizados.');
            log_info('Alterou os dados do emitente. ID: ' . $id);
        } else {
            $this->session->set_flashdata('error', 'Não foi possível atualizar os dados do emitente.');
        }

        redirect(site_url('mapos/emitente'));
    }

    /**
     * Troca a logo. Antes este metodo chamava delete_files() na pasta
     * assets/uploads inteira — o que tambem apagava a pasta temp/ que o mPDF
     * usa para gerar PDF — e depois subia a imagem nova. Agora so remove o
     * arquivo antigo, pelo nome.
     */
    public function editarLogo()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmitente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar emitente.');
            redirect(base_url());
        }

        if ($this->input->method() !== 'post') {
            redirect(site_url('mapos/emitente'));
        }

        $id = (int) $this->input->post('id');
        if ($id < 1) {
            $this->session->set_flashdata('error', 'Emitente não informado.');
            redirect(site_url('mapos/emitente'));
        }

        $arquivo = $this->do_upload();
        if ($arquivo === null) {
            redirect(site_url('mapos/emitente'));
        }

        $anterior = $this->mapos_model->getEmitente();
        $logo = base_url() . 'assets/uploads/' . $arquivo;

        if ($this->mapos_model->editLogo($id, $logo)) {
            $this->apagarLogoAntiga($anterior, $logo);
            $this->session->set_flashdata('success', 'Logo atualizada.');
            log_info('Alterou a logomarca do emitente.');
        } else {
            $this->session->set_flashdata('error', 'Não foi possível atualizar a logo.');
        }

        redirect(site_url('mapos/emitente'));
    }

    /** Remove a logo e o arquivo dela. */
    public function removerLogo()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmitente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar emitente.');
            redirect(base_url());
        }

        if ($this->input->method() !== 'post') {
            redirect(site_url('mapos/emitente'));
        }

        $id = (int) $this->input->post('id');
        if ($id < 1) {
            $this->session->set_flashdata('error', 'Emitente não informado.');
            redirect(site_url('mapos/emitente'));
        }

        $anterior = $this->mapos_model->getEmitente();
        if ($this->mapos_model->removerLogo($id)) {
            $this->apagarLogoAntiga($anterior, null);
            $this->session->set_flashdata('success', 'Logo removida.');
            log_info('Removeu a logomarca do emitente. ID: ' . $id);
        } else {
            $this->session->set_flashdata('error', 'Não foi possível remover a logo.');
        }

        redirect(site_url('mapos/emitente'));
    }

    /**
     * Apaga o arquivo da logo anterior, quando ele e desta instalacao (URL da
     * propria base) e nao coincide com o novo. Nunca apaga a pasta inteira.
     */
    private function apagarLogoAntiga($emitente, $novo = null)
    {
        $atual = trim((string) ($emitente->url_logo ?? ''));
        if ($atual === '' || $atual === (string) $novo) {
            return;
        }

        $base = base_url() . 'assets/uploads/';
        if (strpos($atual, $base) !== 0) {
            return; // logo externa: nao e nossa para apagar
        }

        $arquivo = FCPATH . 'assets/uploads' . DIRECTORY_SEPARATOR . basename($atual);
        if (is_file($arquivo)) {
            @unlink($arquivo);
        }
    }

    /**
     * Troca a foto da propria conta. O gate e o vinculo com a sessao; antes
     * exigia cUsuario (bloqueando tecnico) e gravava a chave de sessao
     * `url_image_user`, enquanto o topo le `url_image_user_admin` — a foto so
     * aparecia depois de sair e entrar.
     */
    public function uploadUserImage()
    {
        $id = (int) $this->session->userdata('id_admin');
        if ($id < 1) {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar alterar sua foto.');
            redirect(site_url('mapos/minhaConta'));
        }

        if ($this->input->method() !== 'post') {
            redirect(site_url('mapos/minhaConta'));
        }

        $usuario = $this->mapos_model->getById($id);
        if (!$usuario) {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar alterar sua foto.');
            redirect(site_url('mapos/minhaConta'));
        }

        $arquivo = $this->do_upload_user();
        if ($arquivo === null) {
            redirect(site_url('mapos/minhaConta'));
        }

        if ($this->mapos_model->editImageUser($id, $arquivo)) {
            $anterior = FCPATH . 'assets/userImage' . DIRECTORY_SEPARATOR . basename((string) $usuario->url_image_user);
            if (!empty($usuario->url_image_user) && is_file($anterior)) {
                @unlink($anterior);
            }

            $this->session->set_userdata('url_image_user_admin', $arquivo);
            $this->session->set_userdata('url_image_user', $arquivo);
            $this->session->set_flashdata('success', 'Foto alterada com sucesso.');
            log_info('Alterou a Imagem do Usuario.');
        } else {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar alterar sua foto.');
        }

        redirect(site_url('mapos/minhaConta'));
    }

    /**
     * Teste de envio de e-mail COM ANEXO (simula NF/Boleto). Envia na hora
     * (fora da fila) um e-mail com um PDF de exemplo, usando o SMTP salvo.
     * Retorna JSON. Usado pelo botão na aba E-mail de Configurar Sistema.
     */
    public function testarEmailAnexo()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema')) {
            return $this->output->set_content_type('application/json')->set_status_header(403)
                ->set_output(json_encode(['result' => false, 'mensagem' => 'Sem permissão.']));
        }

        $dest = trim((string) $this->input->post('email'));
        if (! filter_var($dest, FILTER_VALIDATE_EMAIL)) {
            return $this->output->set_content_type('application/json')->set_status_header(400)
                ->set_output(json_encode(['result' => false, 'mensagem' => 'Informe um e-mail de destino válido.']));
        }

        $emitente = $this->mapos_model->getEmitente();
        $emailEmitente = ($emitente && ! empty($emitente->email)) ? $emitente->email : '';
        $smtpUser = isset($_ENV['EMAIL_SMTP_USER']) ? trim($_ENV['EMAIL_SMTP_USER']) : '';
        // From deve bater com a conta SMTP autenticada (senão o SPF do destino
        // reprova e o e-mail é aceito mas não chega). O emitente vira Reply-To.
        $from = (strpos($smtpUser, '@') !== false) ? $smtpUser : ($emailEmitente ?: 'no-reply@localhost');
        $replyTo = $emailEmitente ?: $from;
        $nomeFrom = ($emitente && ! empty($emitente->nome)) ? $emitente->nome : 'Map-OS';

        // PDF de exemplo em arquivo temporário.
        $tmp = tempnam(sys_get_temp_dir(), 'mapteste_');
        file_put_contents($tmp, $this->gerarPdfTeste());

        $this->load->library('email');
        $this->email->clear(true);
        $this->email->from($from, $nomeFrom);
        if ($replyTo && strcasecmp($replyTo, $from) !== 0) {
            $this->email->reply_to($replyTo, $nomeFrom);
        }
        $this->email->to($dest);
        $this->email->subject('Teste de envio (NF + Boleto) - ' . $nomeFrom);
        $this->email->message(
            '<p>Este é um <strong>e-mail de teste</strong> do Map-OS.</p>'
            . '<p>Se você recebeu esta mensagem com o arquivo <strong>boleto_teste.pdf</strong> anexado, '
            . 'o envio de e-mails com anexos (NF-e/NFS-e e Boleto) está funcionando corretamente.</p>'
        );
        $this->email->attach($tmp, '', 'boleto_teste.pdf');

        $ok = $this->email->send(true); // true = envia imediatamente (não enfileira)
        $debug = $ok ? '' : $this->email->print_debugger(['headers', 'subject']);
        $this->email->registrarEnvioLog($dest, 'Teste de envio (NF + Boleto)', 'teste', $ok ? 'enviado' : 'falha', $ok ? null : $debug);
        if (is_file($tmp)) {
            unlink($tmp);
        }

        if ($ok) {
            log_info('Enviou e-mail de teste com anexo para ' . $dest);

            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['result' => true, 'mensagem' => 'E-mail de teste enviado para ' . $dest . ' com o PDF anexado. Confira a caixa de entrada (e o spam).']));
        }

        return $this->output->set_content_type('application/json')->set_status_header(400)
            ->set_output(json_encode(['result' => false, 'mensagem' => 'Falha no envio. ' . trim(strip_tags((string) $debug))]));
    }

    /**
     * Gera um PDF mínimo válido (uma página) para usar como anexo de teste.
     */
    private function gerarPdfTeste()
    {
        $stream = 'BT /F1 14 Tf 20 60 Td (Map-OS - Teste de anexo NF/Boleto) Tj ET';
        $objs = [
            1 => '<</Type/Catalog/Pages 2 0 R>>',
            2 => '<</Type/Pages/Count 1/Kids[3 0 R]>>',
            3 => '<</Type/Page/Parent 2 0 R/Resources<</Font<</F1 4 0 R>>>>/MediaBox[0 0 320 120]/Contents 5 0 R>>',
            4 => '<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>',
            5 => '<</Length ' . strlen($stream) . ">>stream\n" . $stream . "\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        for ($i = 1; $i <= 5; $i++) {
            $offsets[$i] = strlen($pdf);
            $pdf .= $i . ' 0 obj' . $objs[$i] . "endobj\n";
        }
        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer<</Size 6/Root 1 0 R>>\nstartxref\n" . $xrefPos . "\n%%EOF";

        return $pdf;
    }

    /**
     * Log de e-mails enviados (fila e testes), com status e motivo da falha.
     */
    public function emailsLog()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cEmail')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar o log de e-mails');
            redirect(base_url());
        }

        // O historico unificado (e-mail + WhatsApp) vive na Central de Notificacoes.
        redirect(site_url('notificacoes?aba=historico'));
    }

    /**
     * Reenvia um e-mail do histórico: reenfileira a MESMA linha de email_queue
     * (com os mesmos anexos) voltando o status para "pending". O disparo
     * automático da fila (~2 min) reenvia. Requer o vínculo queue_id no log.
     */
    public function reenviarEmail($id = null)
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cEmail')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para reenviar e-mails');
            redirect(base_url());
        }

        $this->load->model('email_envios_model');
        $log = $id ? $this->email_envios_model->getById($id) : null;
        if (! $log) {
            $this->session->set_flashdata('error', 'Registro de e-mail não encontrado.');
            redirect(site_url('notificacoes?aba=historico'));
        }

        $queueId = isset($log->queue_id) ? (int) $log->queue_id : 0;
        if ($queueId <= 0) {
            $this->session->set_flashdata('error', 'Não é possível reenviar: este envio não tem o e-mail original guardado na fila (sem anexos para reaproveitar). Gere o e-mail novamente pela origem (OS/cobrança).');
            redirect(site_url('notificacoes?aba=historico'));
        }

        $fila = $this->db->where('id', $queueId)->limit(1)->get('email_queue')->row();
        if (! $fila) {
            $this->session->set_flashdata('error', 'O e-mail original não está mais na fila; não é possível reenviar com os anexos.');
            redirect(site_url('notificacoes?aba=historico'));
        }

        // Reenfileira a mesma mensagem/anexos para novo disparo.
        $this->db->where('id', $queueId)->update('email_queue', [
            'status' => 'pending',
            'date' => date('Y-m-d H:i:s'),
        ]);

        log_info('Reenvio de e-mail solicitado (fila #' . $queueId . ' -> ' . $log->destino . ')');
        $this->session->set_flashdata('success', 'E-mail reenfileirado para ' . $log->destino . '. Será reenviado com os mesmos anexos no próximo disparo (em até ~2 min).');
        redirect(site_url('notificacoes?aba=historico'));
    }

    public function emails()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmail')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar fila de e-mails');
            redirect(base_url());
        }

        $this->data['menuConfiguracoes'] = 'Email';

        $this->load->library('pagination');
        $this->load->model('email_model');

        $this->data['configuration']['base_url'] = site_url('mapos/emails/');
        $this->data['configuration']['total_rows'] = $this->email_model->count('email_queue');

        $this->pagination->initialize($this->data['configuration']);

        $this->data['results'] = $this->email_model->get('email_queue', '*', '', $this->data['configuration']['per_page'], $this->uri->segment(3));

        $this->data['view'] = 'emails/emails';

        return $this->layout();
    }

    public function excluirEmail()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmail')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para excluir e-mail da fila.');
            redirect(base_url());
        }

        $id = $this->input->post('id');
        if ($id == null) {
            $this->session->set_flashdata('error', 'Erro ao tentar excluir e-mail da fila.');
            redirect(site_url('mapos/emails/'));
        }

        $this->load->model('email_model');
        $this->email_model->delete('email_queue', 'id', $id);

        log_info('Removeu um e-mail da fila de envio. ID: ' . $id);

        $this->session->set_flashdata('success', 'E-mail removido da fila de envio!');
        redirect(site_url('mapos/emails/'));
    }

    public function configurar()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar o sistema');
            redirect(base_url());
        }
        $this->data['menuConfiguracoes'] = 'Sistema';

        $this->load->library('form_validation');
        $this->load->model('mapos_model');

        $this->data['custom_error'] = '';

        $this->form_validation->set_rules('app_name', 'Nome do Sistema', 'required|trim');
        $this->form_validation->set_rules('per_page', 'Registros por página', 'required|numeric|trim');
        $this->form_validation->set_rules('app_theme', 'Tema do Sistema', 'required|trim');
        $this->form_validation->set_rules('control_estoque', 'Controle de Estoque', 'required|trim');
        $this->form_validation->set_rules('control_baixa', 'Controle de Baixa', 'required|trim');
        $this->form_validation->set_rules('control_editos', 'Controle de Edição de OS', 'required|trim');
        $this->form_validation->set_rules('control_edit_vendas', 'Controle de Edição de Vendas', 'required|trim');
        $this->form_validation->set_rules('control_datatable', 'Controle de Visualização em DataTables', 'required|trim');
        $this->form_validation->set_rules('os_status_list[]', 'Controle de visualização de OS', 'required|trim', ['required' => 'Selecione ao menos uma das opções!']);
        $this->form_validation->set_rules('control_2vias', 'Controle Impressão 2 Vias', 'required|trim');
        $this->form_validation->set_rules('pix_key', 'Chave Pix', 'trim|valid_pix_key', [
            'valid_pix_key' => 'Chave Pix inválida!',
        ]);

        if ($this->form_validation->run() == false) {
            $this->data['custom_error'] = (validation_errors() ? '<div class="alert">' . validation_errors() . '</div>' : false);
        } else {
            // Edição do .env
            $dataDotEnv = [
                'IMPRIMIR_ANEXOS' => $this->input->post('imprmirAnexos'),
                'PAYMENT_GATEWAYS_EFI_PRODUCTION' => $this->input->post('PAYMENT_GATEWAYS_EFI_PRODUCTION'),
                'PAYMENT_GATEWAYS_EFI_CREDENTIAIS_CLIENT_ID' => $this->input->post('PAYMENT_GATEWAYS_EFI_CREDENTIAIS_CLIENT_ID'),
                'PAYMENT_GATEWAYS_EFI_CREDENTIAIS_CLIENT_SECRET' => $this->input->post('PAYMENT_GATEWAYS_EFI_CREDENTIAIS_CLIENT_SECRET'),
                'PAYMENT_GATEWAYS_EFI_BOLETO_EXPIRATION' => $this->input->post('PAYMENT_GATEWAYS_EFI_BOLETO_EXPIRATION'),
                'PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_PUBLIC_KEY' => $this->input->post('PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_PUBLIC_KEY'),
                'PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_ACCESS_TOKEN' => $this->input->post('PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_ACCESS_TOKEN'),
                'PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_CLIENT_ID' => $this->input->post('PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_CLIENT_ID'),
                'PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_CLIENT_SECRET' => $this->input->post('PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_CLIENT_SECRET'),
                'PAYMENT_GATEWAYS_MERCADO_PAGO_BOLETO_EXPIRATION' => $this->input->post('PAYMENT_GATEWAYS_MERCADO_PAGO_BOLETO_EXPIRATION'),
                'PAYMENT_GATEWAYS_ASAAS_PRODUCTION' => $this->input->post('PAYMENT_GATEWAYS_ASAAS_PRODUCTION'),
                'PAYMENT_GATEWAYS_ASAAS_NOTIFY' => $this->input->post('PAYMENT_GATEWAYS_ASAAS_NOTIFY'),
                'PAYMENT_GATEWAYS_ASAAS_CREDENTIAIS_API_KEY' => $this->input->post('PAYMENT_GATEWAYS_ASAAS_CREDENTIAIS_API_KEY'),
                'PAYMENT_GATEWAYS_ASAAS_BOLETO_EXPIRATION' => $this->input->post('PAYMENT_GATEWAYS_ASAAS_BOLETO_EXPIRATION'),
                'API_ENABLED' => $this->input->post('apiEnabled'),
                'API_TOKEN_EXPIRE_TIME' => $this->input->post('apiExpireTime'),
                'API_JWT_KEY' => $this->input->post('resetJwtToken'),
            ];

            if (!$this->editDontEnv($dataDotEnv)) {
                $this->data['custom_error'] = '<div class="alert">Falha ao editar o .env</div>';
            }
            // FIM Edição do .env

            $data = [
                'app_name' => $this->input->post('app_name'),
                'per_page' => $this->input->post('per_page'),
                'app_theme' => $this->input->post('app_theme'),
                'control_estoque' => $this->input->post('control_estoque'),
                'control_baixa' => $this->input->post('control_baixa'),
                'control_editos' => $this->input->post('control_editos'),
                'control_edit_vendas' => $this->input->post('control_edit_vendas'),
                'control_datatable' => $this->input->post('control_datatable'),
                'pix_key' => $this->input->post('pix_key'),
                'os_status_list' => json_encode($this->input->post('os_status_list')),
                'control_2vias' => $this->input->post('control_2vias'),
                // Geofence do atendimento em campo (fechamento da OS).
                'os_geofence_modo' => in_array($this->input->post('os_geofence_modo'), ['off', 'soft', 'hard'], true)
                    ? $this->input->post('os_geofence_modo') : 'off',
                'os_geofence_raio_metros' => (int) $this->input->post('os_geofence_raio_metros') ?: 200,
            ];
            if ($this->mapos_model->saveConfiguracao($data) == true) {
                $this->session->set_flashdata('success', 'Configurações do sistema atualizadas com sucesso!');
                redirect(site_url('mapos/configurar'));
            } else {
                $this->data['custom_error'] = '<div class="alert">Ocorreu um errro.</div>';
            }
        }

        $this->data['view'] = 'mapos/configurar';

        return $this->layout();
    }

    public function atualizarBanco()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar o sistema');
            redirect(base_url());
        }

        $this->load->library('migration');

        if ($this->migration->latest() === false) {
            $this->session->set_flashdata('error', $this->migration->error_string());
        } else {
            $this->session->set_flashdata('success', 'Banco de dados atualizado com sucesso!');
        }

        return redirect(site_url('mapos/configurar'));
    }

    public function atualizarMapos()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar o sistema');
            redirect(base_url());
        }

        $this->load->library('github_updater');

        if (!$this->github_updater->has_update()) {
            $this->session->set_flashdata('success', 'Seu mapos já está atualizado!');

            return redirect(site_url('mapos/configurar'));
        }

        $success = $this->github_updater->update();

        if ($success) {
            $this->session->set_flashdata('success', 'Mapos atualizado com sucesso!');
        } else {
            $this->session->set_flashdata('error', 'Erro ao atualizar mapos!');
        }

        return redirect(site_url('mapos/configurar'));
    }

    public function calendario()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar O.S.');
            redirect(base_url());
        }
        $this->load->model('os_model');
        $status = $this->input->get('status') ?: null;
        $start = $this->input->get('start') ?: null;
        $end = $this->input->get('end') ?: null;

        $allOs = $this->mapos_model->calendario(
            $start,
            $end,
            $status
        );
        $events = array_map(function ($os) {
            switch ($os->status) {
                case 'Aberto':
                    $cor = '#00cd00';
                    break;
                case 'Negociação':
                    $cor = '#AEB404';
                    break;
                case 'Em Andamento':
                    $cor = '#436eee';
                    break;
                case 'Orçamento':
                    $cor = '#CDB380';
                    break;
                case 'Cancelado':
                    $cor = '#CD0000';
                    break;
                case 'Finalizado':
                    $cor = '#256';
                    break;
                case 'Faturado':
                    $cor = '#B266FF';
                    break;
                case 'Aguardando Peças':
                    $cor = '#FF7F00';
                    break;
                case 'Aprovado':
                    $cor = '#808080';
                    break;
                default:
                    $cor = '#E0E4CC';
                    break;
            }

            return [
                'title' => "OS: {$os->idOs}, Cliente: {$os->nomeCliente}",
                'start' => $os->dataFinal,
                'end' => $os->dataFinal,
                'color' => $cor,
                'extendedProps' => [
                    'id' => $os->idOs,
                    'cliente' => '<b>Cliente:</b> ' . $os->nomeCliente,
                    'dataInicial' => '<b>Data Inicial:</b> ' . date('d/m/Y', strtotime($os->dataInicial)),
                    'dataFinal' => '<b>Data Final:</b> ' . date('d/m/Y', strtotime($os->dataFinal)),
                    'garantia' => '<b>Garantia:</b> ' . formatar_garantia($os->garantia),
                    'status' => '<b>Status da OS:</b> ' . $os->status,
                    'description' => '<b>Descrição/Produto:</b> ' . strip_tags(html_entity_decode($os->descricaoProduto)),
                    'defeito' => '<b>Defeito:</b> ' . strip_tags(html_entity_decode($os->defeito)),
                    'observacoes' => '<b>Observações:</b> ' . strip_tags(html_entity_decode($os->observacoes)),
                    'subtotal' => '<br><b>Subtotal:</b> R$ ' . number_format($os->totalProdutos + $os->totalServicos, 2, ',', '.'),
                    'desconto' => '<b>Desconto:</b> -R$ ' . ($os->desconto > 0 ? number_format(($os->totalProdutos + $os->totalServicos) - $os->valor_desconto, 2, ',', '.') : number_format($os->desconto, 2, ',', '.')),
                    'total' => '<b>Total:</b> R$ ' . ($os->valor_desconto == 0 ? number_format($os->totalProdutos + $os->totalServicos, 2, ',', '.') : number_format($os->valor_desconto, 2, ',', '.')),
                    'faturado' => '<br><b>Faturado:</b> ' . ($os->faturado ? 'SIM' : 'PENDENTE'),
                    'editar' => $this->os_model->isEditable($os->idOs),
                ],
            ];
        }, $allOs);

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($events));
    }

    private function editDontEnv(array $data)
    {
        $env_file_path = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . '.env';
        $env_file = file_get_contents($env_file_path);

        foreach ($data as $constante => $valor) {
            if ($constante == 'API_JWT_KEY' && $valor == 'sim') {
                $base64 = base64_encode(openssl_random_pseudo_bytes(32));
                $valor = '"' . $base64 . '"';
                $env_file = str_replace("$constante=" . '"' . $_ENV[$constante] . '"', "$constante={$valor}", $env_file);
            } else {
                if (isset($_ENV[$constante])) {
                    $env_file = str_replace("$constante={$_ENV[$constante]}", "$constante={$valor}", $env_file);
                } else {
                    file_put_contents($env_file_path, $env_file . "\n{$constante}={$valor}\n");
                    $env_file = file_get_contents($env_file_path);
                }
            }
        }
        return file_put_contents($env_file_path, $env_file) ? true : false;
    }
}
