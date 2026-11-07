<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Automação de faturamento no aceite da OS.
 *
 * Duas partes na mesma tela: a configuração (o que emitir e quando) e a fila de
 * faturamento agendado (o que está em espera, o que venceu e o que falhou).
 *
 * Escrita só por POST com CSRF. Emitir nota fiscal ou cancelar um faturamento
 * por link (GET) permitia disparar a ação por prefetch, por link colado em
 * outro lugar ou por clique acidental — e emissão fiscal é irreversível.
 */
class Automacao extends MY_Controller
{
    /** Abas da tela, na ordem em que aparecem. */
    private const ABAS = ['configuracao', 'fila'];

    /** Chaves de configuração que a tela lê e grava. */
    private const CHAVES = [
        'automacao_aprovacao_ativa',
        'automacao_desc_servico',
        'automacao_info_complementar',
        'automacao_ctribnac',
        'automacao_ctribmun',
        'automacao_aliquota_iss',
        'automacao_tp_ret_issqn',
        'automacao_faturamento_dia',
    ];

    public function __construct()
    {
        parent::__construct();

        // Aceita a permissão específica de automação OU a de sistema (admin),
        // para não trancar quem já é admin mas ainda não recebeu cAutomacao.
        $perm = $this->session->userdata('permissao');
        if (! $this->permission->checkPermission($perm, 'cAutomacao')
            && ! $this->permission->checkPermission($perm, 'cSistema')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar automações.');
            redirect(base_url());
        }

        require_once APPPATH . 'helpers/automacao_regras_helper.php';
    }

    public function index()
    {
        $aba = (string) $this->input->get('aba');
        if (! in_array($aba, self::ABAS, true)) {
            $aba = 'configuracao';
        }

        $this->data['menuConfiguracoes'] = 'Automacao';

        foreach (self::CHAVES as $c) {
            $this->data[$c] = $this->data['configuration'][$c] ?? '';
        }

        $hoje = date('Y-m-d');
        $dia = AutomacaoRegras::diaFaturamento($this->data['automacao_faturamento_dia']);
        $this->data['automacao_faturamento_dia'] = (string) $dia;

        $agendados = $this->ordenarPorSituacao($this->listarAgendados(), $hoje);

        $this->data['abaAtiva'] = $aba;
        $this->data['hoje'] = $hoje;
        $this->data['agendados'] = $agendados;
        $this->data['resumo'] = AutomacaoRegras::resumoFila($agendados, $hoje);
        $this->data['proxima_data'] = AutomacaoRegras::proximaDataFaturamento($dia, $hoje);
        $this->data['automacao_ativa'] = (string) ($this->data['configuration']['automacao_aprovacao_ativa'] ?? '') === '1';
        $this->data['resultado_fila'] = $this->session->flashdata('automacao_resultado');

        $this->data['view'] = 'automacao/automacao';

        return $this->layout();
    }

    /**
     * Faturamentos que ainda exigem atenção: em espera e em erro. Itens já
     * emitidos, cancelados ou reprocessados ficam fora da tela.
     */
    private function listarAgendados()
    {
        if (! $this->db->table_exists('faturamentos_agendados')) {
            return [];
        }

        return $this->db
            ->select('fa.*, clientes.nomeCliente')
            ->from('faturamentos_agendados fa')
            ->join('clientes', 'clientes.idClientes = fa.cliente_id', 'left')
            ->where_in('fa.status', ['aguardando', 'erro'])
            ->order_by('fa.data_agendada', 'ASC')
            ->limit(200)
            ->get()
            ->result();
    }

    /** Erro primeiro (precisa de decisão), depois o que venceu, depois o futuro. */
    private function ordenarPorSituacao(array $itens, string $hoje): array
    {
        $peso = ['erro' => 0, 'vencido' => 1, 'futuro' => 2, 'resolvido' => 3];

        usort($itens, static function ($a, $b) use ($hoje, $peso) {
            $sa = $peso[AutomacaoRegras::situacaoItem($a->status ?? '', $a->data_agendada ?? '', $hoje)] ?? 9;
            $sb = $peso[AutomacaoRegras::situacaoItem($b->status ?? '', $b->data_agendada ?? '', $hoje)] ?? 9;
            if ($sa !== $sb) {
                return $sa <=> $sb;
            }

            return strcmp((string) ($a->data_agendada ?? ''), (string) ($b->data_agendada ?? ''));
        });

        return $itens;
    }

    public function salvar()
    {
        $this->exigirPost('automacao');
        $this->load->model('mapos_model');

        $data = [
            'automacao_aprovacao_ativa' => $this->input->post('automacao_aprovacao_ativa') ? '1' : '0',
            'automacao_desc_servico' => (string) $this->input->post('automacao_desc_servico'),
            'automacao_info_complementar' => (string) $this->input->post('automacao_info_complementar'),
            'automacao_ctribnac' => (string) $this->input->post('automacao_ctribnac'),
            'automacao_ctribmun' => (string) $this->input->post('automacao_ctribmun'),
            'automacao_aliquota_iss' => (string) $this->input->post('automacao_aliquota_iss'),
            'automacao_tp_ret_issqn' => (string) $this->input->post('automacao_tp_ret_issqn'),
            // 1..28: meses curtos não têm o dia 29-31, então o agendamento nunca venceria.
            'automacao_faturamento_dia' => (string) AutomacaoRegras::diaFaturamento($this->input->post('automacao_faturamento_dia')),
        ];

        if ($this->mapos_model->saveConfiguracao($data)) {
            log_info('Alterou a configuração da automação de aprovação.');
            $this->session->set_flashdata('success', 'Automação salva com sucesso!');
        } else {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao salvar a automação.');
        }

        redirect(site_url('automacao'));
    }

    /**
     * Dispara agora a fila de faturamento agendado que já venceu (não força
     * itens ainda no futuro — só emite os que passaram do dia de faturamento).
     */
    public function processarAgendados()
    {
        $this->exigirPost('automacao?aba=fila');

        $this->load->library('autoaprovacao');
        $resumo = $this->autoaprovacao->processarPendentes(100);
        log_info('Disparou manualmente a fila de faturamento agendado.');

        $this->session->set_flashdata('automacao_resultado', $resumo);

        if ((int) $resumo['processados'] === 0) {
            $this->session->set_flashdata('success', 'Nenhum faturamento vencido na fila.');
        } else {
            $this->session->set_flashdata('success', sprintf(
                'Fila processada: %d emitido(s), %d falha(s), %d cancelado(s).',
                (int) $resumo['sucesso'],
                (int) $resumo['falhas'],
                (int) $resumo['cancelados']
            ));
        }

        redirect(site_url('automacao?aba=fila'));
    }

    /**
     * Cancela um item da fila de faturamento agendado (não emite).
     */
    public function cancelarAgendado()
    {
        $this->exigirPost('automacao?aba=fila');

        $id = (int) $this->input->post('id');
        if ($id < 1 || ! $this->db->table_exists('faturamentos_agendados')) {
            redirect(site_url('automacao?aba=fila'));
        }

        $this->db->where('id', $id)
            ->where_in('status', ['aguardando', 'erro'])
            ->update('faturamentos_agendados', [
                'status' => 'cancelado',
                'motivo' => 'Cancelado manualmente.',
                'processed_at' => date('Y-m-d H:i:s'),
            ]);

        log_info('Cancelou o faturamento agendado #' . $id);
        $this->session->set_flashdata('success', 'Agendamento cancelado.');

        redirect(site_url('automacao?aba=fila'));
    }

    /**
     * Devolve um item parado em erro para a fila, zerando as tentativas. A fila
     * volta a tentar sozinha no próximo pulso do hook.
     */
    public function retentarAgendado()
    {
        $this->exigirPost('automacao?aba=fila');

        $id = (int) $this->input->post('id');
        if ($id < 1 || ! $this->db->table_exists('faturamentos_agendados')) {
            redirect(site_url('automacao?aba=fila'));
        }

        $this->db->where('id', $id)
            ->where('status', 'erro')
            ->update('faturamentos_agendados', [
                'status' => 'aguardando',
                'tentativas' => 0,
                'motivo' => null,
                'processed_at' => null,
            ]);

        if ($this->db->affected_rows() > 0) {
            log_info('Automação: faturamento agendado #' . $id . ' devolvido para a fila.');
            $this->session->set_flashdata('success', 'Item devolvido para a fila. Ele será tentado de novo automaticamente.');
        } else {
            $this->session->set_flashdata('error', 'Nenhum item em erro com esse identificador.');
        }

        redirect(site_url('automacao?aba=fila'));
    }

    /**
     * Ações desta tela mudam estado (emitem nota, cancelam, devolvem à fila).
     * Recusa GET com mensagem clara, em vez de executar por link.
     */
    private function exigirPost(string $destino)
    {
        // Mesma checagem usada em Conciliacao, Margem e Tecnico.
        if ($this->input->method() !== 'post') {
            $this->session->set_flashdata('error', 'Esta ação precisa ser confirmada pelo botão da tela de automação.');
            redirect(site_url($destino));
        }
    }
}
