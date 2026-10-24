<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * PDV - frente de caixa (ADR-005, F5). Permissao: aVenda.
 * Precisa de um caixa aberto pelo operador (Caixa / Pdv::abrirCaixa).
 */
class Pdv extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('caixa_model');
        $this->data['menuVendas'] = 'Vendas';
    }

    public function index()
    {
        $this->precisa('aVenda', 'Você não tem permissão para vender.');
        if (! $this->caixa_model->disponivel()) {
            $this->session->set_flashdata('error', 'O PDV ainda não está instalado no banco. Rode as migrations.');
            redirect('vendas');
        }

        $caixa = $this->caixa_model->aberto($this->session->userdata('id_admin'));
        $this->data['caixa'] = $caixa;
        if (! $caixa) {
            $this->data['view'] = 'pdv/abrir_caixa';

            return $this->layout();
        }

        $this->load->model('nfe_model');
        $config = $this->db->table_exists('configuracoes_nfe') ? $this->nfe_model->getConfig() : null;
        $this->data['nfceDisponivel'] = $config && ! empty($config->certificado_path)
            && $this->permission->checkPermission($this->session->userdata('permissao'), 'eNfe');
        $this->data['formas'] = Caixa_model::FORMAS;
        $this->data['totais'] = $this->caixa_model->totais($caixa);
        $this->data['view'] = 'pdv/pdv';

        return $this->layout();
    }

    public function abrirCaixa()
    {
        $this->precisa('aVenda', 'Você não tem permissão para vender.');
        try {
            $id = $this->caixa_model->abrir(
                $this->session->userdata('id_admin'),
                $this->paraNumero($this->input->post('valor_abertura')),
                trim((string) $this->input->post('observacao'))
            );
            log_info('Abriu o caixa #' . $id);
        } catch (Throwable $e) {
            $this->session->set_flashdata('error', $e->getMessage());
        }
        redirect('pdv');
    }

    /** Busca de produto (codigo de barras exato ou parte do nome). */
    public function produtos()
    {
        $this->precisa('aVenda', 'Você não tem permissão para vender.');
        $termo = trim((string) $this->input->get('termo'));
        $resultado = [];
        if ($termo !== '') {
            $exato = $this->db->where('codDeBarra', $termo)->limit(1)->get('produtos')->result();
            $lista = $exato ?: $this->db->group_start()->like('descricao', $termo)->or_like('codDeBarra', $termo)->group_end()
                ->order_by('descricao')->limit(15)->get('produtos')->result();
            foreach ($lista as $p) {
                $resultado[] = [
                    'id' => (int) $p->idProdutos,
                    'descricao' => $p->descricao,
                    'codigo' => $p->codDeBarra,
                    'unidade' => $p->unidade,
                    'preco' => (float) $p->precoVenda,
                    'estoque' => (float) $p->estoque,
                    'exato' => (bool) $exato,
                ];
            }
        }
        $this->json($resultado);
    }

    /** Busca de cliente para identificar a venda (opcional). */
    public function clientes()
    {
        $this->precisa('aVenda', 'Você não tem permissão para vender.');
        $termo = trim((string) $this->input->get('termo'));
        $resultado = [];
        if (mb_strlen($termo) >= 2) {
            $digitos = preg_replace('/\D/', '', $termo);
            $this->db->select('idClientes, nomeCliente, documento')->group_start()->like('nomeCliente', $termo);
            if ($digitos !== '') {
                $this->db->or_like("REPLACE(REPLACE(REPLACE(documento, '.', ''), '-', ''), '/', '')", $digitos, 'both', false);
            }
            foreach ($this->db->group_end()->order_by('nomeCliente')->limit(10)->get('clientes')->result() as $c) {
                $resultado[] = ['id' => (int) $c->idClientes, 'nome' => $c->nomeCliente, 'documento' => $c->documento];
            }
        }
        $this->json($resultado);
    }

    /**
     * Fecha a venda. POST JSON: {itens: [{produto, quantidade}], desconto, pagamentos: [{forma, valor}], cliente, cpf}
     */
    public function finalizar()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'aVenda')) {
            return $this->json(['result' => false, 'mensagem' => 'Você não tem permissão para vender.'], 403);
        }
        $caixa = $this->caixa_model->aberto($this->session->userdata('id_admin'));
        if (! $caixa) {
            return $this->json(['result' => false, 'mensagem' => 'Abra o caixa antes de vender.'], 422);
        }

        $dados = json_decode((string) $this->input->post('venda'), true);
        if (! is_array($dados)) {
            return $this->json(['result' => false, 'mensagem' => 'Dados da venda inválidos.'], 422);
        }
        $cpf = preg_replace('/\D/', '', (string) ($dados['cpf'] ?? ''));
        if ($cpf !== '' && ! cpf_valido($cpf) && ! cnpj_valido($cpf)) {
            return $this->json(['result' => false, 'mensagem' => 'CPF/CNPJ informado é inválido.'], 422);
        }

        try {
            $r = $this->caixa_model->registrarVenda(
                $caixa,
                $this->session->userdata('id_admin'),
                (array) ($dados['itens'] ?? []),
                $this->paraNumero($dados['desconto'] ?? 0),
                (array) ($dados['pagamentos'] ?? []),
                (int) ($dados['cliente'] ?? 0) ?: null,
                $cpf ?: null
            );
        } catch (InvalidArgumentException $e) {
            return $this->json(['result' => false, 'mensagem' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            log_message('error', 'PDV: ' . $e->getMessage());

            return $this->json(['result' => false, 'mensagem' => 'Erro ao gravar a venda. Nada foi registrado.'], 500);
        }

        log_info('Venda PDV #' . $r['venda'] . ' (caixa #' . $caixa->id . ', R$ ' . number_format($r['liquido'], 2, ',', '.') . ')');

        return $this->json($r + [
            'result' => true,
            'cupom' => site_url('pdv/cupom/' . $r['venda']),
            'nfce' => site_url('nfe/emitirNfce/' . $r['venda']),
        ]);
    }

    /** Comprovante nao fiscal (80 mm) de uma venda do PDV. */
    public function cupom($vendaId = null)
    {
        $this->precisa('vVenda', 'Você não tem permissão para visualizar vendas.');
        $this->load->model('vendas_model');
        $venda = $this->vendas_model->getById((int) $vendaId);
        if (! $venda) {
            show_404();
        }
        $this->load->view('pdv/cupom', [
            'venda' => $venda,
            'itens' => $this->vendas_model->getProdutos((int) $vendaId),
            'pagamentos' => $this->caixa_model->pagamentosDaVenda((int) $vendaId),
            'emitente' => $this->db->get('emitente')->row(),
            'formas' => Caixa_model::FORMAS,
        ]);
    }

    private function json($dados, $status = 200)
    {
        return $this->output->set_status_header($status)->set_content_type('application/json')->set_output(json_encode($dados));
    }

    /** "1.234,56", "1234,56" ou "1234.56" -> 1234.56 */
    private function paraNumero($valor)
    {
        $valor = trim((string) $valor);
        if (strpos($valor, ',') !== false) {
            $valor = str_replace(['.', ','], ['', '.'], $valor);
        }

        return (float) $valor;
    }
}
