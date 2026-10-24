<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Comprasugerida extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'rProduto')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para ver a compra sugerida.');
            redirect(base_url());
        }
        $this->load->model('comprasugerida_model');
    }

    private function params()
    {
        $int = function ($k, $padrao, $max) {
            $v = $this->input->get($k);
            if (! is_string($v) || ! ctype_digit($v) || (int) $v < 1) {
                return $padrao;
            }

            return min((int) $v, $max);
        };
        $enviado = $this->input->get('janela') !== null;

        return [
            'janela' => $int('janela', 90, 730),
            'prazo' => $int('prazo', 15, 365),
            'cobertura' => $int('cobertura', 30, 365),
            'somente' => $enviado ? $this->input->get('somente') === '1' : true,
        ];
    }

    public function index()
    {
        $p = $this->params();
        $this->data['filtro'] = $p;
        $this->data['disponivel'] = $this->comprasugerida_model->disponivel();
        $this->data['itens'] = $this->data['disponivel']
            ? $this->comprasugerida_model->calcular($p['janela'], $p['prazo'], $p['cobertura'], $p['somente'])
            : [];
        $this->data['view'] = 'comprasugerida/index';

        return $this->layout();
    }

    public function csv()
    {
        if (! $this->comprasugerida_model->disponivel()) {
            redirect('comprasugerida');
        }
        $p = $this->params();
        $itens = $this->comprasugerida_model->calcular($p['janela'], $p['prazo'], $p['cobertura'], $p['somente']);

        $f = fn ($n, $d = 2) => number_format($n, $d, ',', '');
        // evita injecao de formula em planilhas
        $t = function ($s) {
            $s = (string) $s;

            return ($s !== '' && strpos('=+-@', $s[0]) !== false) ? "'" . $s : $s;
        };
        $linhas = [['Codigo', 'Produto', 'Unidade', 'Estoque', 'Minimo', 'Saidas na janela', 'Consumo diario', 'Ponto de pedido', 'Dias de cobertura', 'Qtd sugerida', 'Preco de compra', 'Custo estimado']];
        foreach ($itens as $i) {
            $linhas[] = [
                $i['idProdutos'], $t($i['descricao']), $t($i['unidade']), $f($i['estoque'], 3), $f($i['estoqueMinimo'], 3),
                $f($i['saidas'], 3), $f($i['consumo_diario'], 3), $f($i['ponto_pedido'], 3),
                $i['dias_cobertura'] === null ? '' : $f($i['dias_cobertura'], 1),
                $i['sugerida'], $f($i['precoCompra']), $f($i['custo']),
            ];
        }

        $fp = fopen('php://temp', 'r+');
        foreach ($linhas as $l) {
            fputcsv($fp, $l, ';');
        }
        rewind($fp);
        $conteudo = "\xEF\xBB\xBF" . stream_get_contents($fp);
        fclose($fp);

        $this->output
            ->set_content_type('text/csv', 'UTF-8')
            ->set_header('Content-Disposition: attachment; filename="compra-sugerida-' . date('Ymd') . '.csv"')
            ->set_output($conteudo);
    }
}
