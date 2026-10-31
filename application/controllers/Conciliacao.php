<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Conciliacao bancaria por OFX (ADR-008 item 4).
 */
class Conciliacao extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('conciliacao_model');
    }

    private function exige(string $perm, string $msg)
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), $perm)) {
            $this->session->set_flashdata('error', $msg);
            redirect(base_url());
        }
    }

    public function index()
    {
        $this->exige('vLancamento', 'Você não tem permissão para visualizar lançamentos.');
        $linhas = [];
        foreach ($this->conciliacao_model->pendentes() as $i) {
            $linhas[] = ['item' => $i, 'candidatos' => $this->conciliacao_model->candidatos($i)];
        }
        $this->data['linhas'] = $linhas;
        $this->data['view'] = 'conciliacao/index';

        return $this->layout();
    }

    public function importar()
    {
        $this->exige('eLancamento', 'Você não tem permissão para importar extratos.');
        if ($this->input->method() !== 'post') {
            redirect('conciliacao');
        }
        $f = $_FILES['ofx'] ?? null;
        if (! $f || $f['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file($f['tmp_name'])) {
            $this->session->set_flashdata('error', 'Envie um arquivo .ofx.');
            redirect('conciliacao');
        }
        if (strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) !== 'ofx' || $f['size'] > 2 * 1024 * 1024) {
            $this->session->set_flashdata('error', 'Arquivo inválido: use .ofx de até 2 MB.');
            redirect('conciliacao');
        }
        require_once APPPATH . 'libraries/Ofx_parser.php';
        $parsed = (new Ofx_parser())->parse((string) file_get_contents($f['tmp_name']));
        if (! $parsed['itens']) {
            $this->session->set_flashdata('error', 'Nenhuma transação encontrada no arquivo.');
            redirect('conciliacao');
        }
        [$novos, $rep] = $this->conciliacao_model->importar($parsed, $this->session->userdata('id_admin'));
        log_info("Importou extrato OFX: $novos novos, $rep repetidos");
        $this->session->set_flashdata('success', "Extrato importado: $novos novas transações, $rep já existentes.");
        redirect('conciliacao');
    }

    public function conciliar()
    {
        $this->exige('eLancamento', 'Você não tem permissão para conciliar lançamentos.');
        if ($this->input->method() !== 'post') {
            redirect('conciliacao');
        }
        $item = (int) $this->input->post('item');
        $lanc = (int) $this->input->post('lancamento');
        if ($this->conciliacao_model->conciliar($item, $lanc)) {
            log_info("Conciliou item $item com lançamento $lanc");
            $this->session->set_flashdata('success', 'Lançamento conciliado e baixado.');
        } else {
            $this->session->set_flashdata('error', 'Não foi possível conciliar (item ou lançamento inválido/já baixado).');
        }
        redirect('conciliacao');
    }

    public function ignorar()
    {
        $this->exige('eLancamento', 'Você não tem permissão para conciliar lançamentos.');
        if ($this->input->method() !== 'post') {
            redirect('conciliacao');
        }
        $item = (int) $this->input->post('item');
        if ($this->conciliacao_model->ignorar($item)) {
            log_info("Ignorou item de conciliação $item");
            $this->session->set_flashdata('success', 'Transação ignorada.');
        } else {
            $this->session->set_flashdata('error', 'Item inválido.');
        }
        redirect('conciliacao');
    }
}
