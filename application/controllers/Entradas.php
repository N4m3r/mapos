<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Entradas / Créditos de IBS/CBS (Reforma Tributária - Fase 3).
 *
 * Registra as notas de compra recebidas dos fornecedores (upload de XML ou
 * lançamento manual) e apura o período: débito das saídas - crédito das
 * entradas = saldo de IBS/CBS a recolher.
 */
class Entradas extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('entradas_model');
        $this->load->helper('form');

        if (!$this->session->userdata('id_admin')) {
            redirect('login');
        }
    }

    public function index()
    {
        $this->gerenciar();
    }

    /** Listagem das entradas, com filtro por competência. */
    public function gerenciar()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vEntrada')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar Entradas.');
            redirect(base_url());
        }

        $competencia = $this->competenciaValida($this->input->get('competencia'));

        $this->data['competencia'] = $competencia;
        $this->data['results'] = $this->entradas_model->getEntradas($competencia, 300);
        $this->data['credito'] = $competencia ? $this->entradas_model->creditoDoPeriodo($competencia) : null;
        $this->data['competencias'] = $this->entradas_model->competenciasComEntrada();
        $this->data['menuEntradas'] = 'Entradas';
        $this->data['view'] = 'entradas/gerenciar';

        return $this->layout();
    }

    /** Importa uma ou mais notas de compra a partir do(s) XML(s). */
    public function importar()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEntrada')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para importar Entradas.');
            redirect(base_url());
        }

        if (empty($_FILES['xml']['name'][0])) {
            $this->session->set_flashdata('error', 'Selecione ao menos um arquivo XML.');
            redirect(site_url('entradas'));
        }

        $importadas = 0;
        $duplicadas = 0;
        $erros = [];
        $total = count($_FILES['xml']['name']);

        for ($i = 0; $i < $total; $i++) {
            $nome = $_FILES['xml']['name'][$i];
            if ($_FILES['xml']['error'][$i] !== UPLOAD_ERR_OK) {
                $erros[] = "{$nome}: falha no upload.";
                continue;
            }
            if ($_FILES['xml']['size'][$i] > 2 * 1024 * 1024) {
                $erros[] = "{$nome}: arquivo maior que 2MB.";
                continue;
            }

            $conteudo = file_get_contents($_FILES['xml']['tmp_name'][$i]);
            $dados = Entradas_model::parseNfe($conteudo);
            if ($dados === null || empty($dados['chave'])) {
                $erros[] = "{$nome}: não é um XML de NF-e válido (chave não encontrada).";
                continue;
            }
            if ($this->entradas_model->existeChave($dados['chave'])) {
                $duplicadas++;
                continue;
            }

            $dados['xml'] = $conteudo;
            $dados['credita'] = 1;
            $dados['usuarios_id'] = $this->session->userdata('id_admin');
            $dados['data_cadastro'] = date('Y-m-d H:i:s');
            $this->entradas_model->add($dados);
            $importadas++;
        }

        $msg = "{$importadas} nota(s) importada(s).";
        if ($duplicadas) {
            $msg .= " {$duplicadas} já existia(m).";
        }
        if ($erros) {
            $msg .= ' Problemas: ' . implode(' ', $erros);
        }
        $this->session->set_flashdata($importadas > 0 ? 'success' : 'error', $msg);

        redirect(site_url('entradas'));
    }

    /** Lançamento manual (notas sem XML, ex.: serviços). */
    public function salvarManual()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEntrada')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para lançar Entradas.');
            redirect(base_url());
        }

        $this->load->library('form_validation');
        $this->form_validation->set_rules('emitente_nome', 'Fornecedor', 'required|trim');
        $this->form_validation->set_rules('data_emissao', 'Data de emissão', 'required');
        $this->form_validation->set_rules('valor_total', 'Valor total', 'trim');

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata('error', validation_errors());
            redirect(site_url('entradas'));
        }

        $data = strtotime((string) $this->input->post('data_emissao'));
        $dataEmissao = $data ? date('Y-m-d', $data) : date('Y-m-d');

        $entrada = [
            'chave' => null,
            'modelo' => 'manual',
            'numero' => $this->input->post('numero'),
            'serie' => $this->input->post('serie'),
            'emitente_cnpj' => preg_replace('/\D/', '', (string) $this->input->post('emitente_cnpj')),
            'emitente_nome' => $this->input->post('emitente_nome'),
            'data_emissao' => $dataEmissao,
            'competencia' => date('Y-m', $data ?: time()),
            'valor_total' => $this->numero($this->input->post('valor_total')),
            'v_bc_ibscbs' => $this->numero($this->input->post('v_bc_ibscbs')),
            'v_ibs' => $this->numero($this->input->post('v_ibs')),
            'v_cbs' => $this->numero($this->input->post('v_cbs')),
            'v_cred_pres_zfm' => $this->numero($this->input->post('v_cred_pres_zfm')),
            'credita' => $this->input->post('credita') ? 1 : 0,
            'observacao' => $this->input->post('observacao'),
            'usuarios_id' => $this->session->userdata('id_admin'),
            'data_cadastro' => date('Y-m-d H:i:s'),
        ];
        $this->entradas_model->add($entrada);

        $this->session->set_flashdata('success', 'Entrada lançada com sucesso.');
        redirect(site_url('entradas'));
    }

    /** Alterna se a entrada aproveita o crédito. */
    public function toggleCredita($id = null)
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEntrada')) {
            $this->session->set_flashdata('error', 'Sem permissão.');
            redirect(base_url());
        }
        $entrada = $this->entradas_model->getById($id);
        if ($entrada) {
            $this->entradas_model->update($id, ['credita' => $entrada->credita ? 0 : 1]);
        }
        redirect(site_url('entradas?competencia=' . urlencode((string) ($entrada->competencia ?? ''))));
    }

    public function excluir($id = null)
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'dEntrada')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para excluir Entradas.');
            redirect(base_url());
        }
        $entrada = $this->entradas_model->getById($id);
        if ($entrada) {
            $this->entradas_model->delete($id);
            $this->session->set_flashdata('success', 'Entrada excluída.');
        }
        redirect(site_url('entradas?competencia=' . urlencode((string) ($entrada->competencia ?? ''))));
    }

    /** Painel de apuração do período: débito x crédito = a recolher. */
    public function apuracao()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vEntrada')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar a apuração.');
            redirect(base_url());
        }

        $competencia = $this->competenciaValida($this->input->get('competencia')) ?: date('Y-m');

        $this->data['competencia'] = $competencia;
        $this->data['debito'] = $this->entradas_model->debitoDoPeriodo($competencia);
        $this->data['credito'] = $this->entradas_model->creditoDoPeriodo($competencia);
        $this->data['competencias'] = $this->entradas_model->competenciasComEntrada();
        $this->data['menuEntradas'] = 'Entradas';
        $this->data['view'] = 'entradas/apuracao';

        return $this->layout();
    }

    /* -------------------------------------------------- helpers */

    /** Valida uma competência AAAA-MM vinda da query string. */
    private function competenciaValida($valor)
    {
        $valor = trim((string) $valor);

        return preg_match('/^\d{4}-\d{2}$/', $valor) ? $valor : null;
    }

    /** Converte "1.234,56" ou "1234.56" em float. */
    private function numero($valor)
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return 0.0;
        }
        // Remove separador de milhar e normaliza a vírgula decimal.
        if (strpos($valor, ',') !== false) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        }

        return (float) $valor;
    }
}
