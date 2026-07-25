<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Gestão de material da obra: recebimento (entrada, inclui material do
 * cliente), almoxarifado/saldo e entrega para a equipe no campo — com fotos
 * e leitura de código de barras (casa com produtos.codDeBarra).
 */
class Obramaterial extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->model('obras_model');
        $this->load->model('obra_material_model');
        $this->data['menuObras'] = 'Obras';
    }

    private function precisa($perm, $msg)
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), $perm)) {
            $this->session->set_flashdata('error', $msg);
            redirect(base_url());
        }
    }

    private function getObraOu404($id)
    {
        $obra = $this->obras_model->getObra((int) $id);
        if (! $obra) {
            $this->session->set_flashdata('error', 'Obra não encontrada.');
            redirect('obras');
        }
        return $obra;
    }

    /* ===================== Busca por código de barras ===================== */

    /** AJAX: GET obramaterial/buscarProduto?cod=XXXX -> JSON */
    public function buscarProduto()
    {
        $cod = $this->input->get('cod');
        $p = $this->obra_material_model->buscarProdutoPorCodBarra($cod);
        $this->output->set_content_type('application/json');
        echo json_encode($p ?: ['erro' => 'nao_encontrado', 'cod_barras' => $cod]);
    }

    /* ============================= Recebimento ============================= */

    public function receber()
    {
        $obra_id = (int) $this->uri->segment(3);
        $this->precisa('rObraMaterial', 'Você não tem permissão para receber material.');
        $obra = $this->getObraOu404($obra_id);

        if ($this->input->post()) {
            $itens = $this->coletarItens('conferida');
            if (! $itens) {
                $this->session->set_flashdata('error', 'Adicione ao menos um item.');
                redirect('obramaterial/receber/' . $obra_id);
            }
            $cab = [
                'obra_id' => $obra_id,
                'numero' => $this->obra_material_model->proximoNumeroRecebimento($obra_id),
                'origem' => $this->input->post('origem') ?: 'cliente',
                'fornecedor_nome' => $this->input->post('fornecedor_nome'),
                'documento' => $this->input->post('documento'),
                'recebido_por' => $this->session->userdata('id_admin'),
                'data' => date('Y-m-d'),
                'observacao' => $this->input->post('observacao'),
                'assinatura_entregador' => $this->input->post('assinatura') ?: null,
                'status' => 'conferido',
                'data_registro' => date('Y-m-d H:i:s'),
            ];
            $fotos = $this->coletarFotos();
            $recId = $this->obra_material_model->registrarRecebimento($cab, $itens, $fotos);
            log_info('Recebeu material na obra ' . $obra_id . ' (recebimento ' . $recId . ')');
            $this->session->set_flashdata('success', 'Recebimento registrado e saldo atualizado.');
            redirect('obras/visualizar/' . $obra_id . '#material');
        }

        $this->data['obra'] = $obra;
        $this->data['view'] = 'obramaterial/receber';
        return $this->layout();
    }

    /* =============================== Entrega =============================== */

    public function entregar()
    {
        $obra_id = (int) $this->uri->segment(3);
        $this->precisa('sObraMaterial', 'Você não tem permissão para entregar material.');
        $obra = $this->getObraOu404($obra_id);

        if ($this->input->post()) {
            $itens = $this->coletarItens('quantidade');
            if (! $itens) {
                $this->session->set_flashdata('error', 'Adicione ao menos um item.');
                redirect('obramaterial/entregar/' . $obra_id);
            }
            $cab = [
                'obra_id' => $obra_id,
                'numero' => $this->obra_material_model->proximoNumeroEntrega($obra_id),
                'etapa_id' => (int) $this->input->post('etapa_id') ?: null,
                'equipe_id' => (int) $this->input->post('equipe_id') ?: null,
                'colaborador_id' => (int) $this->input->post('colaborador_id') ?: null,
                'recebedor_nome' => $this->input->post('recebedor_nome'),
                'entregue_por' => $this->session->userdata('id_admin'),
                'data' => date('Y-m-d'),
                'observacao' => $this->input->post('observacao'),
                'assinatura_recebedor' => $this->input->post('assinatura') ?: null,
                'data_registro' => date('Y-m-d H:i:s'),
            ];
            $fotos = $this->coletarFotos();
            $entId = $this->obra_material_model->registrarEntrega($cab, $itens, $fotos);
            log_info('Entregou material na obra ' . $obra_id . ' (entrega ' . $entId . ')');
            $this->session->set_flashdata('success', 'Entrega registrada e saldo debitado.');
            redirect('obras/visualizar/' . $obra_id . '#material');
        }

        $this->data['obra'] = $obra;
        $this->data['etapas'] = $this->obras_model->getEtapas($obra_id);
        $this->data['equipes'] = $this->obras_model->getEquipesAlocadas($obra_id);
        $this->data['saldos'] = $this->obra_material_model->getSaldos($obra_id);
        $this->data['view'] = 'obramaterial/entregar';
        return $this->layout();
    }

    /* =============================== Fotos =============================== */

    public function foto()
    {
        $f = $this->obra_material_model->getFoto((int) $this->uri->segment(3));
        if ($f && $f->foto) {
            header('Content-Type: image/jpeg');
            echo $f->foto;
        }
    }

    /* ============================== Helpers ============================== */

    /**
     * Coleta os itens do POST. Campos paralelos:
     * produto_id[], descricao[], cod_barras[], unidade[] e a coluna de
     * quantidade ($campoQtd = 'conferida' | 'quantidade').
     */
    private function coletarItens($campoQtd)
    {
        $descr = (array) $this->input->post('descricao');
        $prod = (array) $this->input->post('produto_id');
        $cod = (array) $this->input->post('cod_barras');
        $uni = (array) $this->input->post('unidade');
        $qtdConf = (array) $this->input->post('quantidade_conferida');
        $qtdPrev = (array) $this->input->post('quantidade_prevista');
        $qtd = (array) $this->input->post('quantidade');

        $itens = [];
        foreach ($descr as $i => $d) {
            $d = trim((string) $d);
            $pid = (int) ($prod[$i] ?? 0);
            if ($d === '' && ! $pid) {
                continue;
            }
            $item = [
                'produto_id' => $pid ?: null,
                'descricao' => $d,
                'cod_barras' => $cod[$i] ?? null,
                'unidade' => $uni[$i] ?? null,
            ];
            if ($campoQtd === 'conferida') {
                $item['quantidade_conferida'] = (float) ($qtdConf[$i] ?? 0);
                $item['quantidade_prevista'] = (float) ($qtdPrev[$i] ?? ($qtdConf[$i] ?? 0));
            } else {
                $item['quantidade'] = (float) ($qtd[$i] ?? 0);
            }
            $itens[] = $item;
        }
        return $itens;
    }

    /** fotos[] em base64 -> [['foto'=>bin], ...] */
    private function coletarFotos()
    {
        $fotos = [];
        foreach ((array) $this->input->post('fotos') as $b64) {
            if ($b64 && strpos($b64, 'base64,') !== false) {
                $bin = base64_decode(explode('base64,', $b64)[1], true);
                if ($bin) {
                    $fotos[] = ['foto' => $bin];
                }
            }
        }
        return $fotos;
    }
}
