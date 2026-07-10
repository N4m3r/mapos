<?php

use Libraries\Fiscal\CertificadoHelper;
use Libraries\Fiscal\NfseService;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Automação de aprovação: quando o cliente aprova a OS, emite a NFS-e dos
 * serviços e gera o boleto Cora da nota — desde que a automação esteja ligada
 * globalmente e habilitada para aquele cliente.
 *
 * É best-effort e defensiva: qualquer falha é registrada no log e NUNCA
 * interrompe o fluxo de aprovação. A emissão fiscal só ocorre com a flag
 * global `automacao_aprovacao_ativa` = 1.
 */
class Autoaprovacao
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Executa a automação para uma OS recém-aprovada. Retorna um array com o
     * resultado (para log/telemetria) ou null quando não se aplica.
     */
    public function executar($idOs)
    {
        try {
            if (! $idOs || ! is_numeric($idOs)) {
                return null;
            }
            // Master global desligado → não faz nada.
            if ((string) $this->cfg('automacao_aprovacao_ativa') !== '1') {
                return null;
            }

            $this->CI->load->model('os_model');
            $this->CI->load->model('nfe_model');
            $this->CI->load->model('mapos_model');

            $os = $this->CI->os_model->getById($idOs);
            if (! $os) {
                return null;
            }
            // Só para OS aprovada.
            if (isset($os->aprovacao_status) && $os->aprovacao_status !== 'aprovado') {
                return null;
            }
            // Elegibilidade: override da OS tem prioridade sobre a flag do cliente.
            //   os.automacao_override: null = herda do cliente | 1 = força ativo | 0 = desativa nesta OS
            //   clientes.automacao_aprovacao: flag do cliente
            $override = isset($os->automacao_override) ? $os->automacao_override : null;
            if ($override !== null && $override !== '') {
                if ((int) $override !== 1) {
                    return null; // desativado explicitamente nesta OS
                }
            } elseif (empty($os->automacao_aprovacao)) {
                return null; // cliente não habilitado e sem override
            }

            // Faturamento agendado: segura a emissão até o dia de faturamento
            // (padrão dia 01). Só entra na fila se o cliente estiver marcado e
            // hoje ainda não for o dia de emissão.
            if ($this->deveAgendar($os)) {
                return $this->agendar($idOs, $os);
            }

            return $this->emitirAgora($idOs, $os);
        } catch (\Throwable $e) {
            log_info('Automação de aprovação falhou (OS ' . $idOs . '): ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Emite a NFS-e + boleto imediatamente para a OS. É o fluxo "na hora",
     * usado tanto pela aprovação direta quanto pela fila de faturamento
     * agendado quando chega o dia de emissão.
     */
    public function emitirAgora($idOs, $os)
    {
        // Já existe NFS-e ativa? Não emite outra; se autorizada, segue para o boleto.
        $notaExistente = $this->CI->nfe_model->getNotaAtiva('nfse', 'os_id', $idOs);
        if ($notaExistente) {
            if ($notaExistente->status === 'autorizada') {
                $this->gerarBoleto($notaExistente->idNota);
            }

            return ['nota' => $notaExistente->idNota, 'reaproveitou' => true, 'sucesso' => true];
        }

        $idNota = $this->emitirNfse($idOs, $os);
        if (! $idNota) {
            return ['nota' => null, 'sucesso' => false];
        }

        $this->gerarBoleto($idNota);

        return ['nota' => $idNota, 'sucesso' => true];
    }

    /**
     * Emite a NFS-e dos serviços da OS usando os padrões globais da automação.
     * Retorna o id da nota autorizada, ou null em caso de falha/rejeição.
     * Espelha o fluxo de Nfe::emitirNfse (fonte única de emissão).
     */
    private function emitirNfse($idOs, $os)
    {
        $config = $this->CI->nfe_model->getConfig();
        $emitente = $this->CI->mapos_model->getEmitente();
        if (! $config || ! $emitente) {
            log_info('Automação: config fiscal ou emitente ausente. OS ' . $idOs);

            return null;
        }

        $servicos = $this->CI->os_model->getServicos($idOs);
        if (empty($servicos)) {
            log_info('Automação: OS ' . $idOs . ' sem serviços para NFS-e.');

            return null;
        }

        $idNota = null;
        try {
            $service = new NfseService($config, $emitente);

            $valorTotal = 0.0;
            foreach ($servicos as $s) {
                $valorTotal += ((float) ($s->quantidade ?? 1) ?: 1) * (float) ($s->preco ?? $s->precoVenda);
            }

            $reaproveitar = $this->CI->nfe_model->getNotaReaproveitavel('nfse', 'os_id', $idOs);
            if ($reaproveitar) {
                $numero = (int) $reaproveitar->numero;
                $idNota = $reaproveitar->idNota;
                $this->CI->nfe_model->updateNota($idNota, [
                    'status' => 'pendente',
                    'motivo' => null,
                    'chave' => null,
                    'xml_path' => null,
                    'valor_total' => round($valorTotal, 2),
                    'ambiente' => $config->ambiente,
                    'data_emissao' => date('Y-m-d H:i:s'),
                ]);
            } else {
                $numero = $this->CI->nfe_model->reservarNumero('proximo_numero_dps');
                $idNota = $this->CI->nfe_model->addNota([
                    'tipo' => 'nfse',
                    'os_id' => $idOs,
                    'numero' => $numero,
                    'serie' => $config->serie_dps,
                    'status' => 'pendente',
                    'ambiente' => $config->ambiente,
                    'valor_total' => round($valorTotal, 2),
                    'data_emissao' => date('Y-m-d H:i:s'),
                ]);
            }

            $opcoes = [
                'info_complementar' => $this->resolverTagsOs((string) $this->cfg('automacao_info_complementar'), $os),
                'ctribnac' => (string) $this->cfg('automacao_ctribnac'),
                'ctribmun' => (string) $this->cfg('automacao_ctribmun'),
                'desc_servico' => $this->resolverTagsOs((string) $this->cfg('automacao_desc_servico'), $os),
                'tp_ret_issqn' => $this->tpRetIssqn($os),
                'aliquota_iss' => (string) $this->cfg('automacao_aliquota_iss'),
            ];

            $resultado = $service->emitir($os, $servicos, $numero, $opcoes);

            if (empty($resultado['sucesso'])) {
                $this->CI->nfe_model->updateNota($idNota, [
                    'status' => 'rejeitada',
                    'motivo' => $resultado['motivo'] ?? 'Rejeitada',
                ]);
                log_info('Automação: NFS-e da OS ' . $idOs . ' rejeitada: ' . ($resultado['motivo'] ?? ''));

                return null;
            }

            $xmlPath = null;
            if (! empty($resultado['xml'])) {
                $xmlPath = CertificadoHelper::salvarXml('nfse_dps' . $numero . '_' . date('YmdHis') . '.xml', $resultado['xml']);
            }

            $this->CI->nfe_model->updateNota($idNota, [
                'status' => 'autorizada',
                'chave' => $resultado['chave'] ?? null,
                'motivo' => $resultado['motivo'] ?? null,
                'xml_path' => $xmlPath,
                'data_autorizacao' => date('Y-m-d H:i:s'),
            ]);

            log_info('Automação: NFS-e (DPS nº ' . $numero . ') emitida na aprovação da OS ' . $idOs);

            return $idNota;
        } catch (\Throwable $e) {
            if ($idNota) {
                $this->CI->nfe_model->updateNota($idNota, ['status' => 'erro', 'motivo' => $e->getMessage()]);
            }
            log_info('Automação: falha ao emitir NFS-e da OS ' . $idOs . ': ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Decide se a emissão desta OS deve ser segurada (agendada) em vez de sair
     * na hora. Verdadeiro quando o cliente está marcado como "faturamento
     * agendado" e hoje ainda não é o dia de faturamento configurado.
     */
    private function deveAgendar($os)
    {
        if (empty($os->faturamento_agendado)) {
            return false;
        }
        // No próprio dia de faturamento, emite na hora (não faz sentido segurar).
        return (int) date('j') !== $this->diaFaturamento();
    }

    /**
     * Coloca a emissão na fila de faturamento agendado. Idempotente por OS:
     * não duplica se já houver um agendamento aguardando para a mesma OS.
     * Retorna um array de telemetria (para log).
     */
    private function agendar($idOs, $os)
    {
        if (! $this->CI->db->table_exists('faturamentos_agendados')) {
            // Ambiente sem a migration ainda: não segura, emite na hora.
            return $this->emitirAgora($idOs, $os);
        }

        $jaExiste = $this->CI->db
            ->where('os_id', $idOs)
            ->where('status', 'aguardando')
            ->count_all_results('faturamentos_agendados');
        if ($jaExiste > 0) {
            return ['agendado' => true, 'reaproveitou' => true];
        }

        $dataAgendada = $this->proximaDataFaturamento();
        $this->CI->db->insert('faturamentos_agendados', [
            'os_id' => $idOs,
            'cliente_id' => $os->clientes_id ?? null,
            'data_aprovacao' => date('Y-m-d H:i:s'),
            'data_agendada' => $dataAgendada,
            'status' => 'aguardando',
            'tentativas' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        log_info('Automação: emissão da OS ' . $idOs . ' agendada para ' . $dataAgendada . ' (faturamento agendado).');

        return ['agendado' => true, 'data' => $dataAgendada];
    }

    /**
     * Processa a fila de faturamentos agendados que já venceram (data_agendada
     * <= hoje). Chamado pelo hook post_system (~2 min), sem cron externo.
     * Best-effort: falhas ficam registradas na própria fila para nova tentativa.
     */
    public function processarPendentes($limite = 20)
    {
        try {
            if (! $this->CI->db->table_exists('faturamentos_agendados')) {
                return;
            }

            $pendentes = $this->CI->db
                ->where('status', 'aguardando')
                ->where('data_agendada <=', date('Y-m-d'))
                ->order_by('data_agendada', 'ASC')
                ->limit((int) $limite)
                ->get('faturamentos_agendados')
                ->result();

            if (empty($pendentes)) {
                return;
            }

            $this->CI->load->model('os_model');
            $this->CI->load->model('nfe_model');
            $this->CI->load->model('mapos_model');

            foreach ($pendentes as $item) {
                $this->processarAgendado($item);
            }
        } catch (\Throwable $e) {
            log_info('Automação: falha ao processar fila de faturamento agendado: ' . $e->getMessage());
        }
    }

    /**
     * Emite um item da fila de faturamento agendado e atualiza seu status.
     */
    private function processarAgendado($item)
    {
        try {
            $os = $this->CI->os_model->getById($item->os_id);
            // OS sumiu ou não está mais aprovada → cancela o agendamento.
            if (! $os || (isset($os->aprovacao_status) && $os->aprovacao_status !== 'aprovado')) {
                $this->CI->db->where('id', $item->id)->update('faturamentos_agendados', [
                    'status' => 'cancelado',
                    'motivo' => 'OS inexistente ou não aprovada no dia da emissão.',
                    'processed_at' => date('Y-m-d H:i:s'),
                ]);

                return;
            }

            $resultado = $this->emitirAgora($item->os_id, $os);

            if (! empty($resultado['sucesso'])) {
                $this->CI->db->where('id', $item->id)->update('faturamentos_agendados', [
                    'status' => 'processado',
                    'nota_id' => $resultado['nota'] ?? null,
                    'motivo' => null,
                    'processed_at' => date('Y-m-d H:i:s'),
                ]);
                log_info('Automação: faturamento agendado da OS ' . $item->os_id . ' emitido (nota ' . ($resultado['nota'] ?? '?') . ').');

                return;
            }

            // Falhou: incrementa tentativas; após 5, marca erro (para revisão manual).
            $tentativas = (int) $item->tentativas + 1;
            $this->CI->db->where('id', $item->id)->update('faturamentos_agendados', [
                'status' => $tentativas >= 5 ? 'erro' : 'aguardando',
                'tentativas' => $tentativas,
                'motivo' => 'Emissão não concluída (tentativa ' . $tentativas . ').',
            ]);
        } catch (\Throwable $e) {
            $tentativas = (int) $item->tentativas + 1;
            $this->CI->db->where('id', $item->id)->update('faturamentos_agendados', [
                'status' => $tentativas >= 5 ? 'erro' : 'aguardando',
                'tentativas' => $tentativas,
                'motivo' => mb_substr($e->getMessage(), 0, 500),
            ]);
            log_info('Automação: erro ao emitir faturamento agendado da OS ' . $item->os_id . ': ' . $e->getMessage());
        }
    }

    /**
     * Dia do mês configurado para liberar a fila (1..28). Padrão: dia 01.
     */
    private function diaFaturamento()
    {
        $dia = (int) $this->cfg('automacao_faturamento_dia');
        if ($dia < 1 || $dia > 28) {
            $dia = 1;
        }

        return $dia;
    }

    /**
     * Próxima ocorrência do dia de faturamento (formato Y-m-d). Se hoje ainda
     * não passou do dia no mês corrente, usa o mês corrente; senão, o próximo.
     */
    private function proximaDataFaturamento()
    {
        $dia = $this->diaFaturamento();
        $hojeDia = (int) date('j');

        $base = ($hojeDia < $dia) ? new \DateTime('first day of this month') : new \DateTime('first day of next month');
        $base->setDate((int) $base->format('Y'), (int) $base->format('n'), $dia);

        return $base->format('Y-m-d');
    }

    /**
     * Gera o boleto Cora para a nota. O e-mail sai sozinho pelo gatilho
     * "cobranca_gerada". Best-effort.
     */
    private function gerarBoleto($idNota)
    {
        try {
            $this->CI->load->library('Gateways/Cora', null, 'PaymentGateway');
            $this->CI->PaymentGateway->gerarBoletoParaNota($idNota);
            log_info('Automação: boleto gerado para a nota ' . $idNota);
        } catch (\Throwable $e) {
            log_info('Automação: falha ao gerar boleto da nota ' . $idNota . ': ' . $e->getMessage());
        }
    }

    /**
     * Substitui as tags de campos da OS no texto configurado.
     */
    private function resolverTagsOs($texto, $os)
    {
        if ($texto === null || $texto === '') {
            return '';
        }
        $mapa = [
            '{os_numero}' => $os->idOs ?? '',
            '{os_descricao}' => $os->descricaoProduto ?? '',
            '{os_observacoes}' => $os->observacoes ?? '',
            '{os_defeito}' => $os->defeito ?? '',
            '{os_laudo}' => $os->laudoTecnico ?? '',
            '{os_aprovador}' => $os->aprovacao_nome ?? '',
        ];

        return strtr($texto, $mapa);
    }

    private function cfg($chave)
    {
        $row = $this->CI->db->where('config', $chave)->limit(1)->get('configuracoes')->row();

        return $row ? $row->valor : '';
    }

    /**
     * Retenção de ISS: prioriza a definição do cliente (clientes.tp_ret_issqn),
     * caindo no padrão global da automação e, por fim, no padrão do config fiscal.
     */
    private function tpRetIssqn($os)
    {
        $cliente = isset($os->tp_ret_issqn) ? (string) $os->tp_ret_issqn : '';
        if ($cliente === '1' || $cliente === '2') {
            return $cliente;
        }

        return (string) $this->cfg('automacao_tp_ret_issqn');
    }
}
