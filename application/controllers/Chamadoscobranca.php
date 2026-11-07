<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Painel de cobranca dos chamados (OS).
 *
 * Lista o que exige acao financeira: OS aguardando aprovacao do cliente,
 * documentos sem cobranca e cobrancas emitidas. Permite selecao multipla para
 * solicitar aprovacao em lote, confirmar/cancelar cobrancas, exportar CSV e
 * imprimir.
 */
class Chamadoscobranca extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper(['form', 'chamado_cobranca']);
        $this->load->model('chamado_cobranca_model');
        $this->data['menuCobrancas'] = 'financeiro';
    }

    private function pode($atividade)
    {
        return $this->permission->checkPermission($this->session->userdata('permissao'), $atividade);
    }

    /** Redireciona e devolve true quando falta a permissao. */
    private function negar($atividade, $mensagem)
    {
        if ($this->pode($atividade)) {
            return false;
        }

        $this->session->set_flashdata('error', $mensagem);
        redirect(site_url('chamadoscobranca'));

        return true;
    }

    private function filtros()
    {
        $status = array_filter((array) $this->input->get('status'), 'is_string');

        return [
            'busca' => trim((string) $this->input->get('q')),
            'chamado' => trim((string) $this->input->get('chamado')),
            'status' => array_values($status),
            'hoje' => date('Y-m-d'),
        ];
    }

    public function index()
    {
        if ($this->negar('vCobranca', 'Você não tem permissão para ver o painel de cobrança.')) {
            return;
        }

        $filtros = $this->filtros();
        $itens = $this->chamado_cobranca_model->itens($filtros);

        $this->data['filtro'] = $filtros;
        $this->data['itens'] = $itens;
        $this->data['kpis'] = Chamado_cobranca::kpis($itens);
        $this->data['emAberto'] = Chamado_cobranca::totalEmAberto($itens);
        $this->data['pontosStatus'] = Chamado_cobranca::pontosStatus($itens);
        $this->data['topClientes'] = Chamado_cobranca::topClientes($itens);
        $this->data['disponivel'] = $this->chamado_cobranca_model->disponivel();
        $this->data['view'] = 'chamadoscobranca/index';

        return $this->layout();
    }

    /**
     * POST: solicita aprovacao das OS selecionadas. Gera (ou regenera) o link
     * publico de cada uma e devolve a lista pronta para envio ao cliente.
     */
    public function solicitarAprovacao()
    {
        if ($this->negar('vOs', 'Você não tem permissão para solicitar aprovação.')) {
            return;
        }

        $chaves = Chamado_cobranca::lerSelecao((array) $this->input->post('selecionados'));
        $chaves = array_values(array_filter($chaves, static fn ($c) => strpos($c, 'os:') === 0));
        if (! $chaves) {
            $this->session->set_flashdata('error', 'Selecione ao menos uma OS para solicitar aprovação.');
            redirect(site_url('chamadoscobranca'));

            return;
        }

        $this->load->model('aprovacao_model');
        if (! $this->aprovacao_model->suportado()) {
            $this->session->set_flashdata('error', 'Recurso indisponível: execute as migrations.');
            redirect(site_url('chamadoscobranca'));

            return;
        }

        $dias = (int) $this->input->post('dias') ?: 7;
        $gerados = 0;
        foreach ($this->chamado_cobranca_model->osPorChaves($chaves) as $os) {
            if ($os->status === 'Cancelado') {
                continue;
            }
            if ($this->aprovacao_model->gerarLink((int) $os->idOs, $dias)) {
                $gerados++;
            }
        }

        log_info('Solicitou aprovação de ' . $gerados . ' OS pelo painel de cobrança.');
        $this->session->set_flashdata(
            $gerados > 0 ? 'success' : 'error',
            $gerados > 0
                ? $gerados . ' link(s) de aprovação gerado(s). Copie e envie ao cliente.'
                : 'Nenhuma OS pôde ter o link gerado.'
        );
        redirect(site_url('chamadoscobranca'));
    }

    /**
     * POST: confirma ou cancela as cobrancas selecionadas ('cob:<id>').
     * Cada uma passa pelo gateway de origem, como nas ações individuais.
     */
    public function cobrancasAcao()
    {
        if ($this->negar('eCobranca', 'Você não tem permissão para alterar cobranças.')) {
            return;
        }

        $acao = $this->input->post('acao') === 'cancelar' ? 'cancelar' : 'confirmar';
        $ids = [];
        foreach (Chamado_cobranca::lerSelecao((array) $this->input->post('selecionados')) as $chave) {
            if (strpos($chave, 'cob:') === 0) {
                $ids[] = (int) substr($chave, 4);
            }
        }
        if (! $ids) {
            $this->session->set_flashdata('error', 'Selecione ao menos uma cobrança.');
            redirect(site_url('chamadoscobranca'));

            return;
        }

        $this->load->model('cobrancas_model');
        $ok = 0;
        $falhas = [];
        foreach ($ids as $id) {
            try {
                $acao === 'cancelar'
                    ? $this->cobrancas_model->cancelarPagamento($id)
                    : $this->cobrancas_model->confirmarPagamento($id);
                $ok++;
            } catch (\Throwable $e) {
                $falhas[] = '#' . $id . ': ' . $e->getMessage();
            }
        }

        if ($falhas) {
            $this->session->set_flashdata('error', $ok . ' processada(s). Falhas: ' . implode(' | ', $falhas));
        } else {
            $this->session->set_flashdata('success', $ok . ' cobrança(s) ' . ($acao === 'cancelar' ? 'cancelada(s)' : 'confirmada(s)') . '.');
        }
        redirect(site_url('chamadoscobranca'));
    }

    /** CSV do que está na tela, com os filtros aplicados. */
    public function csv()
    {
        if ($this->negar('vCobranca', 'Você não tem permissão para exportar o painel.')) {
            return;
        }

        $itens = $this->chamado_cobranca_model->itens($this->filtros());

        $texto = static function ($s) {
            $s = (string) $s;

            return ($s !== '' && strpos('=+-@', $s[0]) !== false) ? "'" . $s : $s;
        };
        $linhas = [['Situação', 'Chamado', 'OS/Cobrança', 'Cliente', 'Data', 'Valor', 'Detalhe']];
        foreach ($itens as $i) {
            $linhas[] = [
                Chamado_cobranca::rotulo($i['status']),
                $texto($i['chamado'] ?? ''),
                $i['ref_id'],
                $texto($i['cliente']),
                $i['data_ref'] ? date('d/m/Y', strtotime($i['data_ref'])) : '',
                number_format((float) $i['valor'], 2, ',', ''),
                $texto($i['detalhe']),
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
            ->set_header('Content-Disposition: attachment; filename="cobranca-chamados-' . date('Ymd') . '.csv"')
            ->set_output($conteudo);
    }
}
