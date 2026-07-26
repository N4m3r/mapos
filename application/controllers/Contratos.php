<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Contratos de manutenção recorrente + SLA.
 *
 * Modela o vínculo de longo prazo com o cliente (mensalidade), a matriz de
 * SLA por prioridade (prazo de resposta/solução) e o faturamento recorrente
 * por competência — gerado como lançamento financeiro (receita), reusando a
 * tela de Lançamentos e o pipeline de Boleto/PIX já existentes.
 *
 * Permissões: vContrato (ver), cContrato (cadastrar), eContrato (editar),
 * dContrato (excluir), fContrato (faturar mensalidade).
 */
class Contratos extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->model('contratos_model');
        $this->data['menuContratos'] = 'Contratos';
    }

    private function precisa($perm, $msg)
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), $perm)) {
            $this->session->set_flashdata('error', $msg);
            redirect(base_url());
        }
    }

    public function index()
    {
        $this->painel();
    }

    /** Painel: KPIs (ativos, MRR, SLA estourado) + contratos recentes. */
    public function painel()
    {
        $this->precisa('vContrato', 'Você não tem permissão para visualizar Contratos.');
        $this->data['kpis'] = $this->contratos_model->kpis();
        $this->data['recentes'] = $this->contratos_model->getContratos(8, 0, ['status' => 'ativo']);
        $this->data['view'] = 'contratos/painel';
        return $this->layout();
    }

    public function gerenciar()
    {
        $this->precisa('vContrato', 'Você não tem permissão para visualizar Contratos.');
        $this->load->library('pagination');

        $filtros = [
            'pesquisa' => $this->input->get('pesquisa'),
            'status' => $this->input->get('status'),
        ];

        $this->data['configuration']['base_url'] = site_url('contratos/gerenciar/');
        $this->data['configuration']['total_rows'] = $this->contratos_model->count($filtros);
        $this->pagination->initialize($this->data['configuration']);

        $this->data['results'] = $this->contratos_model->getContratos(
            $this->data['configuration']['per_page'],
            $this->uri->segment(3),
            $filtros
        );
        $this->data['filtros'] = $filtros;
        $this->data['view'] = 'contratos/gerenciar';
        return $this->layout();
    }

    public function adicionar()
    {
        $this->precisa('cContrato', 'Você não tem permissão para adicionar Contratos.');

        if ($this->input->post()) {
            $descricao = trim((string) $this->input->post('descricao'));
            if ($descricao === '') {
                $this->session->set_flashdata('error', 'Informe a descrição do contrato.');
            } else {
                $dados = $this->montarDados();
                $dados['data_cadastro'] = date('Y-m-d H:i:s');
                $id = $this->contratos_model->add($dados);
                if ($id) {
                    $this->contratos_model->salvarSlas($id, $this->montarSlas());
                    log_info('Cadastrou contrato. ID: ' . $id);
                    $this->session->set_flashdata('success', 'Contrato cadastrado com sucesso.');
                    redirect('contratos/visualizar/' . $id);
                }
                $this->session->set_flashdata('error', 'Ocorreu um erro ao salvar.');
            }
        }

        $this->carregarSelects();
        $this->data['slas'] = $this->slasParaForm(null);
        $this->data['view'] = 'contratos/adicionar';
        return $this->layout();
    }

    public function editar()
    {
        $id = (int) $this->uri->segment(3);
        $this->precisa('eContrato', 'Você não tem permissão para editar Contratos.');
        if (! $id || ! ($contrato = $this->contratos_model->getContrato($id))) {
            $this->session->set_flashdata('error', 'Contrato não encontrado.');
            redirect('contratos/gerenciar');
        }

        if ($this->input->post()) {
            $this->contratos_model->edit($id, $this->montarDados());
            $this->contratos_model->salvarSlas($id, $this->montarSlas());
            log_info('Editou contrato. ID: ' . $id);
            $this->session->set_flashdata('success', 'Contrato atualizado com sucesso.');
            redirect('contratos/visualizar/' . $id);
        }

        $this->carregarSelects();
        $this->data['result'] = $contrato;
        $this->data['slas'] = $this->slasParaForm($id);
        $this->data['view'] = 'contratos/editar';
        return $this->layout();
    }

    public function visualizar()
    {
        $id = (int) $this->uri->segment(3);
        $this->precisa('vContrato', 'Você não tem permissão para visualizar Contratos.');
        if (! $id || ! ($contrato = $this->contratos_model->getContrato($id))) {
            $this->session->set_flashdata('error', 'Contrato não encontrado.');
            redirect('contratos/gerenciar');
        }

        $this->data['contrato'] = $contrato;
        $this->data['slas'] = $this->contratos_model->getSlas($id);
        $this->data['ordens'] = $this->contratos_model->getOsDoContrato($id);
        $this->data['faturas'] = $this->getFaturas($id);
        $this->data['mesAtual'] = date('m/Y');
        $this->data['jaFaturadoMes'] = $this->contratos_model->jaFaturado($id, date('m/Y'));
        $this->data['view'] = 'contratos/visualizar';
        return $this->layout();
    }

    public function excluir()
    {
        $this->precisa('dContrato', 'Você não tem permissão para excluir Contratos.');
        $id = (int) $this->input->post('idContrato');
        if ($id) {
            $this->contratos_model->delete($id);
            log_info('Removeu contrato. ID: ' . $id);
            $this->session->set_flashdata('success', 'Contrato excluído com sucesso.');
        }
        redirect('contratos/gerenciar');
    }

    /**
     * Gera a mensalidade do contrato como lançamento financeiro (receita a
     * receber). Evita duplicidade por competência (MM/AAAA). Depois é possível
     * emitir Boleto/PIX Cora normalmente pela tela de Cobranças/Lançamentos.
     */
    public function faturar()
    {
        $this->precisa('fContrato', 'Você não tem permissão para faturar contratos.');
        $id = (int) $this->input->post('idContrato');
        $contrato = $this->contratos_model->getContrato($id);
        if (! $contrato) {
            $this->session->set_flashdata('error', 'Contrato não encontrado.');
            redirect('contratos/gerenciar');
        }

        // Competência: "AAAA-MM" do input month; padrão = mês corrente.
        $comp = trim((string) $this->input->post('competencia'));
        if ($comp !== '' && preg_match('/^(\d{4})-(\d{2})$/', $comp, $m)) {
            $ano = (int) $m[1];
            $mes = (int) $m[2];
        } else {
            $ano = (int) date('Y');
            $mes = (int) date('m');
        }
        $competencia = sprintf('%02d/%04d', $mes, $ano);

        if (! $this->db->table_exists('lancamentos')) {
            $this->session->set_flashdata('error', 'Módulo financeiro indisponível.');
            redirect('contratos/visualizar/' . $id);
        }
        if ($this->contratos_model->jaFaturado($id, $competencia)) {
            $this->session->set_flashdata('error', 'Este contrato já foi faturado na competência ' . $competencia . '.');
            redirect('contratos/visualizar/' . $id);
        }

        $dia = (int) $contrato->dia_vencimento ?: 10;
        $dia = max(1, min(28, $dia));
        $vencimento = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);

        $lanc = [
            'descricao' => $this->contratos_model->descricaoFatura($id, $competencia),
            'valor' => (float) $contrato->valor,
            'data_vencimento' => $vencimento,
            'baixado' => 0,
            'tipo' => 'receita',
            'cliente_fornecedor' => $contrato->nomeCliente ?: null,
            'clientes_id' => $contrato->clientes_id ?: null,
            'usuarios_id' => (int) $this->session->userdata('idUsuarios'),
            'observacoes' => 'Gerado automaticamente pelo Contrato #' . $id . ' (' . $competencia . ').',
        ];
        $this->db->insert('lancamentos', $lanc);

        log_info('Faturou mensalidade do contrato #' . $id . ' (' . $competencia . ').');
        $this->session->set_flashdata('success', 'Mensalidade de ' . $competencia . ' gerada em Lançamentos (R$ ' . number_format($contrato->valor, 2, ',', '.') . ').');
        redirect('contratos/visualizar/' . $id);
    }

    /* =========================== Helpers =========================== */

    private function montarDados()
    {
        return [
            'codigo' => $this->input->post('codigo') ?: null,
            'clientes_id' => (int) $this->input->post('clientes_id') ?: null,
            'descricao' => trim((string) $this->input->post('descricao')),
            'tipo' => $this->input->post('tipo') ?: 'mensal',
            'valor' => (float) $this->input->post('valor'),
            'dia_vencimento' => (int) $this->input->post('dia_vencimento') ?: 10,
            'data_inicio' => $this->dataOuNull($this->input->post('data_inicio')),
            'data_fim' => $this->dataOuNull($this->input->post('data_fim')),
            'status' => $this->input->post('status') ?: 'ativo',
            'observacoes' => $this->input->post('observacoes'),
        ];
    }

    /** Monta a matriz de SLA (prioridade => horas) a partir do POST. */
    private function montarSlas()
    {
        $resposta = $this->input->post('sla_resposta') ?: [];
        $solucao = $this->input->post('sla_solucao') ?: [];
        $slas = [];
        foreach (array_keys(Contratos_model::prioridadesPadrao()) as $p) {
            $slas[$p] = [
                'resposta_horas' => (int) ($resposta[$p] ?? 0),
                'solucao_horas' => (int) ($solucao[$p] ?? 0),
            ];
        }
        return $slas;
    }

    /**
     * Matriz de SLA para o formulário: valores salvos do contrato ou o padrão.
     * Retorna [prioridade => ['resposta_horas'=>x,'solucao_horas'=>y]].
     */
    private function slasParaForm($contrato_id)
    {
        $matriz = Contratos_model::prioridadesPadrao();
        if ($contrato_id) {
            foreach ($this->contratos_model->getSlas($contrato_id) as $row) {
                if (isset($matriz[$row->prioridade])) {
                    $matriz[$row->prioridade] = [
                        'resposta_horas' => (int) $row->resposta_horas,
                        'solucao_horas' => (int) $row->solucao_horas,
                    ];
                }
            }
        }
        return $matriz;
    }

    /** Lançamentos de mensalidade já gerados para o contrato. */
    private function getFaturas($contrato_id)
    {
        if (! $this->db->table_exists('lancamentos')) {
            return [];
        }
        return $this->db->select('idLancamentos, descricao, valor, data_vencimento, data_pagamento, baixado')
            ->like('descricao', 'Mensalidade Contrato #' . (int) $contrato_id . ' -')
            ->order_by('data_vencimento', 'DESC')
            ->limit(24)
            ->get('lancamentos')->result();
    }

    private function carregarSelects()
    {
        $this->data['clientes'] = $this->db->select('idClientes, nomeCliente')
            ->order_by('nomeCliente', 'ASC')->get('clientes')->result();
    }

    private function dataOuNull($valor)
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return null;
        }
        if (strpos($valor, '/') !== false) {
            $p = explode('/', $valor);
            if (count($p) === 3) {
                return $p[2] . '-' . $p[1] . '-' . $p[0];
            }
        }
        return $valor;
    }
}
