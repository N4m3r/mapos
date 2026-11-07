<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API v1 — Ocorrências de campo do técnico: serviço não realizado,
 * reagendamento, reabertura e abertura de atividade não programada.
 *
 * Mesmas regras dos métodos AJAX de Tecnico.php: a OS tem de ser do técnico,
 * OS já finalizada não pode ser marcada como não realizada, motivo OU
 * observação são obrigatórios, e o preço de serviços/produtos é resolvido no
 * servidor — o app nunca envia valores.
 */
class TecnicoOcorrenciaController extends AreaApiController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('tecnico_model');
        $this->load->model('checkin_model');
        $this->load->model('naorealizada_model');
        $this->load->model('os_observacoes_tecnico_model');
        $this->load->model('assinaturas_model');
        $this->load->model('fotosatendimento_model');
        $this->load->model('os_model');
        $this->load->model('clientes_model');
        $this->load->model('produtos_model');
        $this->load->helper('tecnico_inicio');
    }

    // ------------------------------------------------------------------
    // Serviço não realizado
    // ------------------------------------------------------------------

    /** POST /api/v1/tecnico/nao-realizado { os_id, motivo_id?, observacao? } */
    public function nao_realizado_post()
    {
        $user = $this->exigir('eTecnicoNaoRealizado');
        $uid = (int) $user->usuario->idUsuarios;
        $corpo = $this->corpo();
        $osId = (int) ($corpo['os_id'] ?? 0);

        $os = $this->osDoTecnico($osId, $uid);
        if (! $os) {
            $this->naoEncontrada('OS não encontrada ou não está designada a você.');
        }
        if (in_array($os->status, ['Finalizado', 'Faturado', 'Cancelado'], true)) {
            $this->invalido('Esta OS já está ' . $os->status . ' e não pode ser marcada como não realizada.');
        }

        $motivoId = (int) ($corpo['motivo_id'] ?? 0);
        $observacao = $this->cortar($corpo['observacao'] ?? '', 2000);
        if (! $motivoId && ($observacao === null || $observacao === '')) {
            $this->invalido('Selecione um motivo ou descreva o que aconteceu.');
        }
        if ($motivoId && ! $this->naorealizada_model->getMotivo($motivoId)) {
            $this->invalido('Motivo inválido.');
        }

        $checkin = $this->checkin_model->getCheckinAtivo($osId);
        if ($checkin) {
            $this->checkin_model->finalizarAtendimento($checkin->idCheckin, [
                'data_saida' => date('Y-m-d H:i:s'),
                'observacao_saida' => 'Serviço não realizado' . ($observacao ? ': ' . $observacao : ''),
                'status' => 'Finalizado',
            ]);
            // O tempo em campo conta mesmo sem serviço executado.
            $this->load->library('projeto_sync');
            $this->projeto_sync->lancarHorasDoCheckin((int) $checkin->idCheckin);
        }

        $id = $this->naorealizada_model->registrar($osId, $uid, $motivoId, (string) $observacao, $os->status);
        if (! $id) {
            $this->invalido('Não foi possível registrar. Verifique se o banco está atualizado.');
        }

        log_info('API: técnico ' . $uid . ' marcou a OS ' . $osId . ' como não realizada');

        $this->avisar($osId, 'os_nao_realizada', [
            'motivo' => trim((string) ($this->naorealizada_model->getOcorrencia($id)->motivo_texto ?? '')) ?: (string) $observacao,
        ]);

        $this->ok([
            'id' => (int) $id,
            'os_id' => $osId,
            'status' => 'Não Realizado',
            'observacao' => $observacao,
        ], 'Registrado. A OS ficou na aba "Não Realizadas".');
    }

    /** POST /api/v1/tecnico/nao-realizadas/reagendar { ocorrencia_id, nova_data } */
    public function reagendar_post()
    {
        $user = $this->exigir('eTecnicoNaoRealizado');
        $uid = (int) $user->usuario->idUsuarios;
        $corpo = $this->corpo();

        $oc = $this->ocorrenciaDoTecnico((int) ($corpo['ocorrencia_id'] ?? 0), $uid);
        $novaData = trim((string) ($corpo['nova_data'] ?? ''));
        if ($novaData === '' || ! strtotime($novaData)) {
            $this->invalido('Informe uma data válida para o reagendamento.');
        }

        if (! $this->naorealizada_model->reagendar((int) $oc->idOcorrencia, $novaData, $uid)) {
            $this->invalido('Não foi possível reagendar (talvez já esteja resolvida).');
        }

        log_info('API: técnico ' . $uid . ' reagendou a OS ' . $oc->os_id);
        $this->avisar((int) $oc->os_id, 'os_reagendada');

        $this->ok([
            'ocorrencia_id' => (int) $oc->idOcorrencia,
            'os_id' => (int) $oc->os_id,
            'nova_data' => date('Y-m-d', strtotime($novaData)),
        ], 'OS reagendada para ' . date('d/m/Y', strtotime($novaData)) . '.');
    }

    /** POST /api/v1/tecnico/nao-realizadas/reabrir { ocorrencia_id } */
    public function reabrir_post()
    {
        $user = $this->exigir('eTecnicoNaoRealizado');
        $uid = (int) $user->usuario->idUsuarios;
        $corpo = $this->corpo();

        $oc = $this->ocorrenciaDoTecnico((int) ($corpo['ocorrencia_id'] ?? 0), $uid);

        if (! $this->naorealizada_model->reabrir((int) $oc->idOcorrencia, $uid)) {
            $this->invalido('Não foi possível reabrir (talvez já esteja resolvida).');
        }

        log_info('API: técnico ' . $uid . ' reabriu a OS ' . $oc->os_id);

        $this->ok([
            'ocorrencia_id' => (int) $oc->idOcorrencia,
            'os_id' => (int) $oc->os_id,
        ], 'OS reaberta e voltou para a sua lista.');
    }

    // ------------------------------------------------------------------
    // Atividade não programada
    // ------------------------------------------------------------------

    /**
     * POST /api/v1/tecnico/atividade
     * { clientes_id, descricaoProduto?, defeito?, observacoes?, servicos?: [{id,quantidade}],
     *   produtos?: [{id,quantidade}], atividade_realizada?, fotos?: [dataUrl], assinatura?, assinatura_nome? }
     */
    public function atividade_post()
    {
        $user = $this->exigir('aTecnicoAtividade');
        $uid = (int) $user->usuario->idUsuarios;
        $corpo = $this->corpo();

        $clienteId = (int) ($corpo['clientes_id'] ?? 0);
        if (! $clienteId || ! $this->clientes_model->getById($clienteId)) {
            $this->invalido('Selecione um cliente válido para abrir a atividade.');
        }

        $idOs = $this->tecnico_model->criarAtividade([
            'dataInicial' => date('Y-m-d'),
            'dataFinal' => date('Y-m-d'),
            'clientes_id' => $clienteId,
            'usuarios_id' => $uid,
            'tecnico_responsavel' => $uid,
            'garantia' => 0,
            'descricaoProduto' => $this->cortar($corpo['descricaoProduto'] ?? '', 255),
            'defeito' => $this->cortar($corpo['defeito'] ?? '', 2000),
            'observacoes' => $this->cortar($corpo['observacoes'] ?? '', 2000),
            'status' => 'Aberto',
            'faturado' => 0,
            'nao_programada' => 1,
            'chamado_id' => $this->db->field_exists('chamado_id', 'os')
                ? $this->cortar($corpo['chamado_id'] ?? '', 60)
                : null,
        ]);
        if (! $idOs) {
            $this->invalido('Não foi possível abrir a atividade. Tente novamente.');
        }

        $controlaEstoque = $this->config('control_estoque');

        $servicos = 0;
        foreach ((array) ($corpo['servicos'] ?? []) as $s) {
            $servicoId = (int) (is_array($s) ? ($s['id'] ?? 0) : $s);
            if (! $servicoId) {
                continue;
            }
            $qtd = (int) (is_array($s) ? ($s['quantidade'] ?? 1) : 1);
            $qtd = $qtd > 0 ? $qtd : 1;
            $preco = $this->tecnico_model->getPrecoServico($servicoId);
            if (! $preco && ! $this->db->where('idServicos', $servicoId)->count_all_results('servicos')) {
                continue;
            }
            $this->os_model->add('servicos_os', [
                'servicos_id' => $servicoId,
                'quantidade' => $qtd,
                'preco' => $preco,
                'os_id' => $idOs,
                'subTotal' => $preco * $qtd,
            ]);
            $servicos++;
        }

        $produtos = 0;
        foreach ((array) ($corpo['produtos'] ?? []) as $p) {
            $produtoId = (int) (is_array($p) ? ($p['id'] ?? 0) : $p);
            if (! $produtoId) {
                continue;
            }
            $produto = $this->tecnico_model->getProduto($produtoId);
            if (! $produto) {
                continue;
            }
            $qtd = (int) (is_array($p) ? ($p['quantidade'] ?? 1) : 1);
            $qtd = $qtd > 0 ? $qtd : 1;
            $preco = (float) $produto->precoVenda;

            $this->os_model->add('produtos_os', [
                'quantidade' => $qtd,
                'subTotal' => $preco * $qtd,
                'produtos_id' => $produtoId,
                'preco' => $preco,
                'os_id' => $idOs,
            ]);
            $produtos++;
            if ($controlaEstoque) {
                $this->produtos_model->updateEstoque($produtoId, $qtd, '-', ['tipo' => 'tecnico', 'referencia' => $idOs]);
            }
        }

        $atividade = $this->cortar($corpo['atividade_realizada'] ?? '', 2000);
        if ($atividade) {
            $this->os_observacoes_tecnico_model->adicionar($idOs, $uid, $atividade);
        }

        $anexos = $this->anexarFotosEAssinatura($idOs, $uid, $corpo);
        log_info('API: técnico ' . $uid . ' abriu a atividade não programada OS ' . $idOs);

        $this->ok([
            'idOs' => (int) $idOs,
            'servicos' => $servicos,
            'produtos' => $produtos,
            'anexos_nao_salvos' => $anexos,
            'status' => 'Aberto',
        ], 'Atividade aberta com sucesso!' . ($anexos ? ' Atenção: não foi possível salvar ' . implode(' e ', $anexos) . '.' : ''));
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    /** Ocorrência cuja OS pertence ao técnico (ou 404). */
    protected function ocorrenciaDoTecnico($ocorrenciaId, $uid)
    {
        $oc = $ocorrenciaId ? $this->naorealizada_model->getOcorrencia($ocorrenciaId) : null;
        if (! $oc) {
            $this->naoEncontrada('Ocorrência não encontrada.');
        }
        if (! $this->osDoTecnico((int) $oc->os_id, $uid)) {
            $this->naoEncontrada('OS não designada a você.');
        }

        return $oc;
    }

    /** Fotos (até 6) e assinatura do solicitante da atividade. Devolve o que falhou. */
    protected function anexarFotosEAssinatura($idOs, $uid, array $corpo)
    {
        $falhas = [];

        foreach (array_slice((array) ($corpo['fotos'] ?? []), 0, 6) as $dataUrl) {
            $foto = dataurl_imagem($dataUrl);
            if ($foto === null) {
                continue;
            }
            $r = $this->fotosatendimento_model->salvarFotoBase64($foto, $idOs, $uid, null, 'durante', '');
            if (isset($r['error'])) {
                $falhas[] = 'uma foto';
                continue;
            }
            if (! $this->fotosatendimento_model->add([
                'os_id' => $idOs,
                'checkin_id' => null,
                'usuarios_id' => $uid,
                'arquivo' => $r['arquivo'],
                'path' => $r['path'],
                'url' => $r['url'],
                'descricao' => '',
                'etapa' => 'durante',
                'data_upload' => date('Y-m-d H:i:s'),
                'tamanho' => $r['tamanho'],
                'tipo_arquivo' => $r['tipo'],
                'imagem_base64' => $r['imagem_base64'],
                'mime_type' => $r['mime_type'],
            ], true)) {
                $falhas[] = 'uma foto';
            }
        }

        $assinatura = dataurl_imagem($corpo['assinatura'] ?? '');
        if ($assinatura !== null && strlen($assinatura) >= 100) {
            $img = $this->assinaturas_model->salvarImagem($assinatura, $idOs, 'solicitante');
            if (! $img) {
                $falhas[] = 'a assinatura';
            } elseif (isset($img['modo']) && $img['modo'] === 'arquivo') {
                $this->assinaturas_model->add([
                    'os_id' => $idOs,
                    'tipo' => 'solicitante',
                    'assinatura' => $img['path'],
                    'nome_assinante' => $this->cortar($corpo['assinatura_nome'] ?? '', 100),
                    'data_assinatura' => date('Y-m-d H:i:s'),
                    'ip_address' => $this->input->ip_address(),
                ]);
            }
        }

        return array_values(array_unique($falhas));
    }

    /** Valor de configuracoes como booleano (ex.: control_estoque). */
    protected function config($chave)
    {
        $row = $this->db->select('valor')->get_where('configuracoes', ['config' => $chave])->row();
        $valor = $row ? strtolower(trim((string) $row->valor)) : '';

        return in_array($valor, ['1', 'true', 'sim', 'yes'], true);
    }

    /** Aviso ao cliente por WhatsApp — best-effort, nunca derruba a resposta. */
    protected function avisar($osId, $evento, array $extras = [])
    {
        try {
            $this->load->library('notificador');
            $this->notificador->avisarAtendimento($osId, $evento, $extras);
        } catch (Throwable $e) {
            log_message('error', 'Aviso ' . $evento . ' da OS #' . $osId . ' falhou: ' . $e->getMessage());
        }
    }
}
