<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Clientes extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('clientes_model');
        $this->data['menuClientes'] = 'clientes';
    }

    public function index()
    {
        $this->gerenciar();
    }

    public function gerenciar()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vCliente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar clientes.');
            redirect(base_url());
        }

        $pesquisa = $this->input->get('pesquisa');

        $this->load->library('pagination');

        $this->data['configuration']['base_url'] = site_url('clientes/gerenciar/');
        $this->data['configuration']['total_rows'] = $this->clientes_model->count('clientes');
        if($pesquisa) {
            $this->data['configuration']['suffix'] = "?pesquisa={$pesquisa}";
            $this->data['configuration']['first_url'] = base_url("index.php/clientes")."\?pesquisa={$pesquisa}";
        }

        $this->pagination->initialize($this->data['configuration']);

        $this->data['results'] = $this->clientes_model->get('clientes', '*', $pesquisa, $this->data['configuration']['per_page'], $this->uri->segment(3));

        $this->data['view'] = 'clientes/clientes';

        return $this->layout();
    }

    public function adicionar()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'aCliente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para adicionar clientes.');
            redirect(base_url());
        }

        $this->load->library('form_validation');
        $this->data['custom_error'] = '';

        $senhaCliente = $this->input->post('senha') ? $this->input->post('senha') : preg_replace('/[^\p{L}\p{N}\s]/', '', set_value('documento'));

        $cpf_cnpj = preg_replace('/[^\p{L}\p{N}\s]/', '', set_value('documento'));

        if (strlen($cpf_cnpj) == 11) {
            $pessoa_fisica = true;
        } else {
            $pessoa_fisica = false;
        }

        if ($this->form_validation->run('clientes') == false) {
            $this->data['custom_error'] = (validation_errors() ? '<div class="form_error">' . validation_errors() . '</div>' : false);
        } else {
            $data = [
                'nomeCliente' => set_value('nomeCliente'),
                'contato' => set_value('contato'),
                'pessoa_fisica' => $pessoa_fisica,
                'documento' => set_value('documento'),
                'ie' => set_value('ie'),
                'im' => set_value('im'),
                'telefone' => set_value('telefone'),
                'celular' => set_value('celular'),
                'email' => set_value('email'),
                'email_secundario' => set_value('email_secundario'),
                'automacao_aprovacao' => $this->input->post('automacao_aprovacao') ? 1 : 0,
                'tp_ret_issqn' => in_array($this->input->post('tp_ret_issqn'), ['1', '2'], true) ? $this->input->post('tp_ret_issqn') : null,
                'senha' => password_hash($senhaCliente, PASSWORD_DEFAULT),
                'rua' => set_value('rua'),
                'numero' => set_value('numero'),
                'complemento' => set_value('complemento'),
                'bairro' => set_value('bairro'),
                'cidade' => set_value('cidade'),
                'estado' => set_value('estado'),
                'cep' => set_value('cep'),
                'dataCadastro' => date('Y-m-d'),
                'fornecedor' => $this->input->post('fornecedor') ? 1 : 0,
            ];

            if ($this->db->field_exists('whatsapp_notificacao', 'clientes')) {
                $data['whatsapp_notificacao'] = $this->input->post('whatsapp_notificacao') ?: null;
            }

            if ($this->db->field_exists('faturamento_agendado', 'clientes')) {
                $data['faturamento_agendado'] = $this->input->post('faturamento_agendado') ? 1 : 0;
            }

            if ($this->db->field_exists('aprovacao_exige_token', 'clientes')) {
                $data['aprovacao_exige_token'] = $this->input->post('aprovacao_exige_token') ? 1 : 0;
            }

            if ($this->db->field_exists('aprovacao_token_numeros', 'clientes')) {
                $data['aprovacao_token_numeros'] = trim((string) $this->input->post('aprovacao_token_numeros')) ?: null;
            }

            if ($this->clientes_model->add('clientes', $data) == true) {
                $this->session->set_flashdata('success', 'Cliente adicionado com sucesso!');
                log_info('Adicionou um cliente.');
                redirect(site_url('clientes/'));
            } else {
                $this->data['custom_error'] = '<div class="form_error"><p>Ocorreu um erro.</p></div>';
            }
        }

        $this->data['view'] = 'clientes/adicionarCliente';

        return $this->layout();
    }

    public function editar()
    {
        if (! $this->uri->segment(3) || ! is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
            redirect('mapos');
        }

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eCliente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para editar clientes.');
            redirect(base_url());
        }

        $this->load->library('form_validation');
        $this->data['custom_error'] = '';

        if ($this->form_validation->run('clientes') == false) {
            $this->data['custom_error'] = (validation_errors() ? '<div class="form_error">' . validation_errors() . '</div>' : false);
        } else {
            $senha = $this->input->post('senha');
            if ($senha != null) {
                $senha = password_hash($senha, PASSWORD_DEFAULT);

                $data = [
                    'nomeCliente' => $this->input->post('nomeCliente'),
                    'contato' => $this->input->post('contato'),
                    'documento' => $this->input->post('documento'),
                    'ie' => $this->input->post('ie'),
                    'im' => $this->input->post('im'),
                    'telefone' => $this->input->post('telefone'),
                    'celular' => $this->input->post('celular'),
                    'email' => $this->input->post('email'),
                    'email_secundario' => $this->input->post('email_secundario'),
                    'automacao_aprovacao' => $this->input->post('automacao_aprovacao') ? 1 : 0,
                    'tp_ret_issqn' => in_array($this->input->post('tp_ret_issqn'), ['1', '2'], true) ? $this->input->post('tp_ret_issqn') : null,
                    'senha' => $senha,
                    'rua' => $this->input->post('rua'),
                    'numero' => $this->input->post('numero'),
                    'complemento' => $this->input->post('complemento'),
                    'bairro' => $this->input->post('bairro'),
                    'cidade' => $this->input->post('cidade'),
                    'estado' => $this->input->post('estado'),
                    'cep' => $this->input->post('cep'),
                    'fornecedor' => (set_value('fornecedor') == true ? 1 : 0),
                ];
            } else {
                $data = [
                    'nomeCliente' => $this->input->post('nomeCliente'),
                    'contato' => $this->input->post('contato'),
                    'documento' => $this->input->post('documento'),
                    'ie' => $this->input->post('ie'),
                    'im' => $this->input->post('im'),
                    'telefone' => $this->input->post('telefone'),
                    'celular' => $this->input->post('celular'),
                    'email' => $this->input->post('email'),
                    'email_secundario' => $this->input->post('email_secundario'),
                    'automacao_aprovacao' => $this->input->post('automacao_aprovacao') ? 1 : 0,
                    'tp_ret_issqn' => in_array($this->input->post('tp_ret_issqn'), ['1', '2'], true) ? $this->input->post('tp_ret_issqn') : null,
                    'rua' => $this->input->post('rua'),
                    'numero' => $this->input->post('numero'),
                    'complemento' => $this->input->post('complemento'),
                    'bairro' => $this->input->post('bairro'),
                    'cidade' => $this->input->post('cidade'),
                    'estado' => $this->input->post('estado'),
                    'cep' => $this->input->post('cep'),
                    'fornecedor' => (set_value('fornecedor') == true ? 1 : 0),
                ];
            }

            if ($this->db->field_exists('whatsapp_notificacao', 'clientes')) {
                $data['whatsapp_notificacao'] = $this->input->post('whatsapp_notificacao') ?: null;
            }

            if ($this->db->field_exists('faturamento_agendado', 'clientes')) {
                $data['faturamento_agendado'] = $this->input->post('faturamento_agendado') ? 1 : 0;
            }

            if ($this->db->field_exists('aprovacao_exige_token', 'clientes')) {
                $data['aprovacao_exige_token'] = $this->input->post('aprovacao_exige_token') ? 1 : 0;
            }

            if ($this->db->field_exists('aprovacao_token_numeros', 'clientes')) {
                $data['aprovacao_token_numeros'] = trim((string) $this->input->post('aprovacao_token_numeros')) ?: null;
            }

            if ($this->clientes_model->edit('clientes', $data, 'idClientes', $this->input->post('idClientes')) == true) {
                // Vínculos multi-CNPJ do portal (clientes que este login acessa).
                $this->clientes_model->setVinculos(
                    (int) $this->input->post('idClientes'),
                    (array) $this->input->post('clientes_vinculados')
                );
                $this->session->set_flashdata('success', 'Cliente editado com sucesso!');
                log_info('Alterou um cliente. ID' . $this->input->post('idClientes'));
                redirect(site_url('clientes/editar/') . $this->input->post('idClientes'));
            } else {
                $this->data['custom_error'] = '<div class="form_error"><p>Ocorreu um erro</p></div>';
            }
        }

        $id = $this->uri->segment(3);
        $this->data['result'] = $this->clientes_model->getById($id);
        $this->data['clientesDisponiveis'] = $this->clientes_model->getAllExceto($id);
        $this->data['vinculosAtuais'] = $this->clientes_model->getVinculos($id);
        $this->data['vinculosSuportado'] = $this->clientes_model->vinculosSuportado();
        $this->data['view'] = 'clientes/editarCliente';

        return $this->layout();
    }

    public function visualizar()
    {
        if (! $this->uri->segment(3) || ! is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
            redirect('mapos');
        }

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vCliente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar clientes.');
            redirect(base_url());
        }

        $this->data['custom_error'] = '';
        $this->data['result'] = $this->clientes_model->getById($this->uri->segment(3));
        $this->data['results'] = $this->clientes_model->getOsByCliente($this->uri->segment(3));
        $this->data['result_vendas'] = $this->clientes_model->getAllVendasByClient($this->uri->segment(3));
        $this->data['view'] = 'clientes/visualizar';

        return $this->layout();
    }

    public function excluir()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'dCliente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para excluir clientes.');
            redirect(base_url());
        }

        $id = $this->input->post('id');
        if ($id == null) {
            $this->session->set_flashdata('error', 'Erro ao tentar excluir cliente.');
            redirect(site_url('clientes/gerenciar/'));
        }

        $os = $this->clientes_model->getAllOsByClient($id);
        if ($os != null) {
            $this->clientes_model->removeClientOs($os);
        }

        // excluindo Vendas vinculadas ao cliente
        $vendas = $this->clientes_model->getAllVendasByClient($id);
        if ($vendas != null) {
            $this->clientes_model->removeClientVendas($vendas);
        }

        $this->clientes_model->delete('clientes', 'idClientes', $id);
        log_info('Removeu um cliente. ID' . $id);

        $this->session->set_flashdata('success', 'Cliente excluido com sucesso!');
        redirect(site_url('clientes/gerenciar/'));
    }
}
