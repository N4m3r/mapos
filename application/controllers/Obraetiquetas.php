<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Etiquetas QR dos pontos e planilha as-built. Permissao: vObras.
 * (PhpSpreadsheet nao esta no vendor: a planilha sai em CSV compativel com Excel.)
 */
class Obraetiquetas extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('obras_model');
        $this->load->model('obra_pontos_model');
        $this->load->library('obra_etiquetas');
        $this->load->library('projeto_csv');
        $this->data['menuObras'] = 'Obras';
    }

    private function obraOuSai($obra_id)
    {
        $this->precisa('vObras', 'Você não tem permissão para visualizar Projetos.');
        $obra = (int) $obra_id ? $this->obras_model->getObra((int) $obra_id) : null;
        if (! $obra) {
            $this->session->set_flashdata('error', 'Projeto não encontrado.');
            redirect('obras');
        }
        return $obra;
    }

    public function etiquetas($obra_id = 0)
    {
        $obra = $this->obraOuSai($obra_id);
        $tipos = Obra_pontos_model::tipos();
        $tipo = (string) $this->input->get('tipo');
        if (! isset($tipos[$tipo])) {
            $tipo = '';
        }
        $pontos = Obra_etiquetas::filtrar(
            $this->obra_pontos_model->getPontos((int) $obra->idObra),
            $tipo,
            $this->input->get('ids')
        );
        $itens = [];
        foreach ($pontos as $p) {
            $itens[] = ['ponto' => $p, 'qr' => Obra_etiquetas::qrDataUri(
                Obra_etiquetas::urlPonto(site_url(), $obra->idObra, $p->idPonto)
            )];
        }
        $this->load->view('obras/etiquetas', ['obra' => $obra, 'itens' => $itens, 'tipos' => $tipos]);
    }

    public function asbuiltPlanilha($obra_id = 0)
    {
        $obra = $this->obraOuSai($obra_id);
        $linhas = Obra_etiquetas::linhasAsbuilt(
            $this->obra_pontos_model->getPontos((int) $obra->idObra),
            Obra_pontos_model::tipos(),
            Obra_pontos_model::statusLista()
        );
        $nome = 'asbuilt_obra_' . (int) $obra->idObra . '_' . date('Ymd') . '.csv';
        $this->output
            ->set_content_type('text/csv', 'UTF-8')
            ->set_header('Content-Disposition: attachment; filename="' . $nome . '"')
            ->set_output(Obra_etiquetas::csv($linhas));
    }
}
