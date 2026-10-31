<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Produtos extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper('form');
        $this->load->helper('classificacao_fiscal');
        $this->load->model('produtos_model');
        $this->data['menuProdutos'] = 'Produtos';
    }

    public function index()
    {
        $this->gerenciar();
    }

    public function gerenciar()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vProduto')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar produtos.');
            redirect(base_url());
        }

        $pesquisa = $this->input->get('pesquisa');

        $this->load->library('pagination');

        $this->data['configuration']['base_url'] = site_url('produtos/gerenciar/');
        $this->data['configuration']['total_rows'] = $this->produtos_model->count('produtos');
        if($pesquisa) {
            $this->data['configuration']['suffix'] = "?pesquisa={$pesquisa}";
            $this->data['configuration']['first_url'] = base_url("index.php/produtos")."\?pesquisa={$pesquisa}";
        }

        $this->pagination->initialize($this->data['configuration']);

        $this->data['results'] = $this->produtos_model->get('produtos', '*', $pesquisa, $this->data['configuration']['per_page'], $this->uri->segment(3));

        $this->data['view'] = 'produtos/produtos';

        return $this->layout();
    }

    public function adicionar()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'aProduto')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para adicionar produtos.');
            redirect(base_url());
        }

        $this->load->library('form_validation');
        $this->data['custom_error'] = '';

        if ($this->form_validation->run('produtos') == false) {
            $this->data['custom_error'] = (validation_errors() ? '<div class="form_error">' . validation_errors() . '</div>' : false);
        } else {
            $precoCompra = $this->input->post('precoCompra');
            $precoCompra = str_replace(',', '', $precoCompra);
            $precoVenda = $this->input->post('precoVenda');
            $precoVenda = str_replace(',', '', $precoVenda);
            $data = [
                'codDeBarra' => set_value('codDeBarra'),
                'descricao' => set_value('descricao'),
                'unidade' => set_value('unidade'),
                'ncm' => preg_replace('/\D/', '', (string) $this->input->post('ncm')),
                'cest' => preg_replace('/\D/', '', (string) $this->input->post('cest')),
                'cfop' => preg_replace('/\D/', '', (string) $this->input->post('cfop')),
                'precoCompra' => $precoCompra,
                'precoVenda' => $precoVenda,
                'estoque' => set_value('estoque'),
                'estoqueMinimo' => set_value('estoqueMinimo'),
                'saida' => set_value('saida'),
                'entrada' => set_value('entrada'),
            ];

            $data = classificacao_aplicar_post($data, 'produto', 'produtos');

            if ($this->produtos_model->add('produtos', $data) == true) {
                if ((float) $data['estoque'] != 0) {
                    $this->produtos_model->registrarMovimentacao($this->db->insert_id(), (float) $data['estoque'], 0, (float) $data['estoque'], ['tipo' => 'cadastro', 'observacao' => 'Estoque inicial']);
                }
                $this->session->set_flashdata('success', 'Produto adicionado com sucesso!');
                log_info('Adicionou um produto');
                redirect(site_url('produtos/adicionar/'));
            } else {
                $this->data['custom_error'] = '<div class="form_error"><p>An Error Occured.</p></div>';
            }
        }
        $this->data['view'] = 'produtos/adicionarProduto';

        return $this->layout();
    }

    public function editar()
    {
        if (! $this->uri->segment(3) || ! is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
            redirect('mapos');
        }

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eProduto')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para editar produtos.');
            redirect(base_url());
        }
        $this->load->library('form_validation');
        $this->data['custom_error'] = '';

        if ($this->form_validation->run('produtos') == false) {
            $this->data['custom_error'] = (validation_errors() ? '<div class="form_error">' . validation_errors() . '</div>' : false);
        } else {
            $precoCompra = $this->input->post('precoCompra');
            $precoCompra = str_replace(',', '', $precoCompra);
            $precoVenda = $this->input->post('precoVenda');
            $precoVenda = str_replace(',', '', $precoVenda);
            $data = [
                'codDeBarra' => set_value('codDeBarra'),
                'descricao' => $this->input->post('descricao'),
                'unidade' => $this->input->post('unidade'),
                'ncm' => preg_replace('/\D/', '', (string) $this->input->post('ncm')),
                'cest' => preg_replace('/\D/', '', (string) $this->input->post('cest')),
                'cfop' => preg_replace('/\D/', '', (string) $this->input->post('cfop')),
                'precoCompra' => $precoCompra,
                'precoVenda' => $precoVenda,
                'estoqueMinimo' => $this->input->post('estoqueMinimo'),
                'saida' => set_value('saida'),
                'entrada' => set_value('entrada'),
            ];

            $data = classificacao_aplicar_post($data, 'produto', 'produtos');

            if ($this->produtos_model->edit('produtos', $data, 'idProdutos', $this->input->post('idProdutos')) == true) {
                // Estoque alterado na edicao fica registrado no historico como ajuste manual
                if (trim((string) $this->input->post('estoque')) !== '') {
                    $this->produtos_model->ajustarEstoque($this->input->post('idProdutos'), (float) $this->input->post('estoque'), ['observacao' => 'Alterado na edição do produto']);
                }
                $this->session->set_flashdata('success', 'Produto editado com sucesso!');
                log_info('Alterou um produto. ID: ' . $this->input->post('idProdutos'));
                redirect(site_url('produtos/editar/') . $this->input->post('idProdutos'));
            } else {
                $this->data['custom_error'] = '<div class="form_error"><p>An Error Occured</p></div>';
            }
        }

        $this->data['result'] = $this->produtos_model->getById($this->uri->segment(3));

        $this->data['view'] = 'produtos/editarProduto';

        return $this->layout();
    }

    public function visualizar()
    {
        if (! $this->uri->segment(3) || ! is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
            redirect('mapos');
        }

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vProduto')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar produtos.');
            redirect(base_url());
        }

        $this->data['result'] = $this->produtos_model->getById($this->uri->segment(3));

        if ($this->data['result'] == null) {
            $this->session->set_flashdata('error', 'Produto não encontrado.');
            redirect(site_url('produtos/editar/') . $this->input->post('idProdutos'));
        }

        $this->data['movimentacoes'] = $this->produtos_model->getMovimentacoes(['produto' => $this->data['result']->idProdutos], 15);
        $this->data['view'] = 'produtos/visualizarProduto';

        return $this->layout();
    }

    public function excluir()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'dProduto')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para excluir produtos.');
            redirect(base_url());
        }

        $id = $this->input->post('id');
        if ($id == null) {
            $this->session->set_flashdata('error', 'Erro ao tentar excluir produto.');
            redirect(base_url() . 'index.php/produtos/gerenciar/');
        }

        $this->produtos_model->delete('produtos_os', 'produtos_id', $id);
        $this->produtos_model->delete('itens_de_vendas', 'produtos_id', $id);
        $this->produtos_model->delete('produtos', 'idProdutos', $id);

        log_info('Removeu um produto. ID: ' . $id);

        $this->session->set_flashdata('success', 'Produto excluido com sucesso!');
        redirect(site_url('produtos/gerenciar/'));
    }

    /**
     * Historico de movimentacao de estoque (todos os produtos ou ?produto=ID).
     */
    public function movimentacoes()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vProduto')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar produtos.');
            redirect(base_url());
        }

        $data = function ($valor) {
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $valor) ? $valor : '';
        };
        $tipo = (string) $this->input->get('tipo');
        $filtros = [
            'tipo' => in_array($tipo, ['os', 'venda', 'tecnico', 'api', 'ajuste', 'cadastro', 'inventario'], true) ? $tipo : '',
            'de' => $data($this->input->get('de')),
            'ate' => $data($this->input->get('ate')),
        ];
        $produto = $this->input->get('produto') ? $this->produtos_model->getById((int) $this->input->get('produto')) : null;
        $consulta = $filtros + ['produto' => $produto ? $produto->idProdutos : null];

        $porPagina = 50;
        $total = $this->produtos_model->contarMovimentacoes($consulta);
        $paginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min($paginas, max(1, (int) $this->input->get('pagina')));

        $this->data['filtros'] = $filtros;
        $this->data['produto'] = $produto;
        $this->data['total'] = $total;
        $this->data['pagina'] = $pagina;
        $this->data['paginas'] = $paginas;
        $this->data['movimentacoes'] = $this->produtos_model->getMovimentacoes($consulta, $porPagina, ($pagina - 1) * $porPagina);
        $this->data['view'] = 'produtos/movimentacoes';

        return $this->layout();
    }

    public function atualizar_estoque()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eProduto')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para atualizar estoque de produtos.');
            redirect(base_url());
        }

        $idProduto = (int) $this->input->post('id');
        $entrada = (float) $this->input->post('estoque');

        // Soma no banco: o "estoque atual" vindo da tela podia estar desatualizado (ou ser alterado)
        $operacao = $entrada >= 0 ? '+' : '-';
        if ($this->produtos_model->updateEstoque($idProduto, abs($entrada), $operacao, ['tipo' => 'ajuste', 'observacao' => 'Entrada/saída manual de estoque'])) {
            $this->session->set_flashdata('success', 'Estoque de Produto atualizado com sucesso!');
            log_info('Atualizou estoque de um produto. ID: ' . $idProduto);
            redirect(site_url('produtos/visualizar/') . $idProduto);
        } else {
            $this->data['custom_error'] = '<div class="alert">Ocorreu um erro.</div>';
        }
    }
}
