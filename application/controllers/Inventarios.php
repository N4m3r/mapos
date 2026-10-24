<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Inventario (contagem fisica) de estoque (ADR-005, F1).
 * Ver e contar: vProduto. Abrir, salvar contagem, finalizar e cancelar: eProduto.
 */
class Inventarios extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('inventarios_model');
        $this->data['menuProdutos'] = 'Produtos';
    }

    public function index()
    {
        $this->precisa('vProduto', 'Você não tem permissão para visualizar produtos.');

        $this->data['inventarios'] = $this->inventarios_model->listar();
        $this->data['view'] = 'inventarios/lista';

        return $this->layout();
    }

    public function abrir()
    {
        $this->precisa('eProduto', 'Você não tem permissão para fazer inventário.');

        $descricao = trim((string) $this->input->post('descricao')) ?: 'Inventário de ' . date('d/m/Y');
        $id = $this->inventarios_model->abrir(
            mb_substr($descricao, 0, 120),
            $this->input->post('observacao'),
            (int) $this->session->userdata('id_admin')
        );
        if (! $id) {
            $this->session->set_flashdata('error', 'Não foi possível abrir o inventário.');
            redirect('inventarios');
        }

        log_info('Abriu o inventário #' . $id);
        redirect('inventarios/contar/' . $id);
    }

    public function contar($id = null)
    {
        $this->precisa('vProduto', 'Você não tem permissão para visualizar produtos.');

        $inventario = $this->inventarios_model->getById($id);
        if (! $inventario) {
            $this->session->set_flashdata('error', 'Inventário não encontrado.');
            redirect('inventarios');
        }

        $this->data['inventario'] = $inventario;
        $this->data['itens'] = $this->inventarios_model->itens($id);
        $this->data['podeEditar'] = $inventario->status === 'aberto'
            && $this->permission->checkPermission($this->session->userdata('permissao'), 'eProduto');
        $this->data['view'] = 'inventarios/contar';

        return $this->layout();
    }

    public function salvar($id = null)
    {
        $inventario = $this->inventarioAbertoParaEditar($id);

        $this->inventarios_model->salvarContagem($inventario->id, (array) $this->input->post('contado'));

        if ($this->input->post('finalizar')) {
            $alterados = $this->inventarios_model->finalizar($inventario->id);
            if ($alterados === null) {
                $this->session->set_flashdata('error', 'Erro ao finalizar o inventário; nenhum estoque foi alterado.');
                redirect('inventarios/contar/' . $inventario->id);
            }
            log_info('Finalizou o inventário #' . $inventario->id . ' (' . $alterados . ' produto(s) ajustado(s))');
            $this->session->set_flashdata('success', 'Inventário finalizado. Estoque ajustado em ' . $alterados . ' produto(s).');
            redirect('inventarios/contar/' . $inventario->id);
        }

        $this->session->set_flashdata('success', 'Contagem salva. Você pode continuar depois.');
        redirect('inventarios/contar/' . $inventario->id);
    }

    public function cancelar($id = null)
    {
        $inventario = $this->inventarioAbertoParaEditar($id);
        $this->inventarios_model->cancelar($inventario->id);
        log_info('Cancelou o inventário #' . $inventario->id);
        $this->session->set_flashdata('success', 'Inventário cancelado. Nenhum estoque foi alterado.');
        redirect('inventarios');
    }

    private function inventarioAbertoParaEditar($id)
    {
        $this->precisa('eProduto', 'Você não tem permissão para fazer inventário.');

        $inventario = $this->inventarios_model->getById($id);
        if (! $inventario || $inventario->status !== 'aberto' || $this->input->method() !== 'post') {
            $this->session->set_flashdata('error', 'Este inventário não está aberto para contagem.');
            redirect('inventarios');
        }

        return $inventario;
    }
}
