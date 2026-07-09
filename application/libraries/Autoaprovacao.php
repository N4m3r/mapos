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
            // Cliente precisa estar habilitado para a automação.
            if (empty($os->automacao_aprovacao)) {
                return null;
            }

            // Já existe NFS-e ativa? Não emite outra; se autorizada, segue para o boleto.
            $notaExistente = $this->CI->nfe_model->getNotaAtiva('nfse', 'os_id', $idOs);
            if ($notaExistente) {
                if ($notaExistente->status === 'autorizada') {
                    $this->gerarBoleto($notaExistente->idNota);
                }

                return ['nota' => $notaExistente->idNota, 'reaproveitou' => true];
            }

            $idNota = $this->emitirNfse($idOs, $os);
            if (! $idNota) {
                return ['nota' => null, 'sucesso' => false];
            }

            $this->gerarBoleto($idNota);

            return ['nota' => $idNota, 'sucesso' => true];
        } catch (\Throwable $e) {
            log_info('Automação de aprovação falhou (OS ' . $idOs . '): ' . $e->getMessage());

            return null;
        }
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
                'tp_ret_issqn' => (string) $this->cfg('automacao_tp_ret_issqn'),
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
        ];

        return strtr($texto, $mapa);
    }

    private function cfg($chave)
    {
        $row = $this->CI->db->where('config', $chave)->limit(1)->get('configuracoes')->row();

        return $row ? $row->valor : '';
    }
}
