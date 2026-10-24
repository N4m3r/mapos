<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Controle de caixa do PDV (ADR-005, F5).
 * Historico e detalhes: vVenda. Sangria, suprimento e fechamento do proprio caixa: aVenda.
 */
class Caixa extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('caixa_model');
        $this->data['menuVendas'] = 'Vendas';
    }

    public function index()
    {
        $this->precisa('vVenda', 'Você não tem permissão para visualizar vendas.');
        $this->data['caixas'] = $this->caixa_model->disponivel() ? $this->caixa_model->listar() : [];
        $this->data['meuCaixa'] = $this->caixa_model->disponivel() ? $this->caixa_model->aberto($this->session->userdata('id_admin')) : null;
        $this->data['view'] = 'caixa/lista';

        return $this->layout();
    }

    public function ver($id = null)
    {
        $this->precisa('vVenda', 'Você não tem permissão para visualizar vendas.');
        $caixa = $this->caixa_model->getById($id);
        if (! $caixa) {
            $this->session->set_flashdata('error', 'Caixa não encontrado.');
            redirect('caixa');
        }
        $this->data['caixa'] = $caixa;
        $this->data['totais'] = $this->caixa_model->totais($caixa);
        $this->data['movimentos'] = $this->caixa_model->movimentos($caixa->id);
        $this->data['formas'] = Caixa_model::FORMAS;
        $this->data['meu'] = $caixa->status === 'aberto' && (int) $caixa->usuarios_id === (int) $this->session->userdata('id_admin')
            && $this->permission->checkPermission($this->session->userdata('permissao'), 'aVenda');
        $this->data['view'] = 'caixa/ver';

        return $this->layout();
    }

    public function movimentar()
    {
        $caixa = $this->meuCaixaAberto();
        try {
            $tipo = (string) $this->input->post('tipo');
            $this->caixa_model->movimentar($caixa, $tipo, $this->paraNumero($this->input->post('valor')), trim((string) $this->input->post('descricao')), $this->session->userdata('id_admin'));
            log_info(ucfirst($tipo) . ' no caixa #' . $caixa->id . ': R$ ' . $this->input->post('valor'));
            $this->session->set_flashdata('success', ucfirst($tipo) . ' registrada.');
        } catch (Throwable $e) {
            $this->session->set_flashdata('error', $e->getMessage());
        }
        redirect('caixa/ver/' . $caixa->id);
    }

    public function fechar()
    {
        $caixa = $this->meuCaixaAberto();
        $r = $this->caixa_model->fechar($caixa, $this->paraNumero($this->input->post('contado_dinheiro')), trim((string) $this->input->post('observacao')), $this->session->userdata('id_admin'));
        log_info('Fechou o caixa #' . $caixa->id . ' (diferença em dinheiro: R$ ' . number_format($r['diferenca_dinheiro'], 2, ',', '.') . ')');
        $this->session->set_flashdata('success', 'Caixa fechado. Diferença em dinheiro: R$ ' . number_format($r['diferenca_dinheiro'], 2, ',', '.'));
        redirect('caixa/ver/' . $caixa->id);
    }

    private function meuCaixaAberto()
    {
        $this->precisa('aVenda', 'Você não tem permissão para operar o caixa.');
        $caixa = $this->caixa_model->aberto($this->session->userdata('id_admin'));
        if (! $caixa || (int) $caixa->id !== (int) $this->input->post('caixa_id')) {
            $this->session->set_flashdata('error', 'Este caixa não está aberto para você.');
            redirect('caixa');
        }

        return $caixa;
    }

    private function paraNumero($valor)
    {
        $valor = trim((string) $valor);
        if (strpos($valor, ',') !== false) {
            $valor = str_replace(['.', ','], ['', '.'], $valor);
        }

        return (float) $valor;
    }
}
