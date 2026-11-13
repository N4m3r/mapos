<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API v1 — Execução de projeto pelo técnico: avançar status do ponto,
 * assumir/repassar, registrar RDO com material/serviço e ver fotos.
 *
 * Regras espelhadas de Tecnico.php (ponto_status/assumir_ponto/repassar_ponto/
 * salvar_registro_projeto): acesso ao projeto conferido no servidor, foto da
 * etiqueta e checklist de teste obrigatórios antes de "Instalado"/"Testado",
 * e ponto testado não pode ser repassado.
 */
require_once __DIR__ . '/AreaApiController.php';

class TecnicoProjetoController extends AreaApiController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('obras_model');
        $this->load->model('obra_pontos_model');
        $this->load->model('produtos_model');
        $this->load->helper('tecnico_inicio');
    }

    // ------------------------------------------------------------------
    // Ponto de projeto
    // ------------------------------------------------------------------

    /** GET /api/v1/tecnico/pontos/{id} — detalhe do ponto com histórico e fotos. */
    public function ponto_get($id = null)
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;
        $ponto = $this->pontoAcessivel((int) $this->get('obra_id'), (int) ($id !== null ? $id : $this->get('id')), $uid);
        $statusLista = Obra_pontos_model::statusLista();

        $this->ok([
            'ponto' => $this->pontoResumo($ponto, $uid, $statusLista),
            'statusLista' => $statusLista,
            'proximoStatus' => Obra_pontos_model::proximoStatus($ponto->status),
            'testeItens' => Obra_pontos_model::TESTE_ITENS,
            'exigeTeste' => Obra_pontos_model->precisaTeste($ponto, 'testado'),
            'exigeEtiqueta' => Obra_pontos_model::exigeEtiqueta('instalado', $ponto->tipo),
            'temFotoEtiqueta' => $this->obra_pontos_model->temFotoEtiqueta((int) $ponto->idPonto),
            'historico' => $this->historico($ponto),
            'fotos' => $this->fotosPonto($ponto),
            'executores' => $this->obra_pontos_model->executores((int) $ponto->obra_id),
        ], $ponto->identificacao . ' carregado.');
    }

    /**
     * POST /api/v1/tecnico/pontos/{id}/status
     * { obra_id, status, fotos?, fotos_etiqueta?, metragem_cabo?, ip?, canal?, porta?,
     *   modelo?, serie?, local?, observacao?, repassar_para?, instrucao?, teste? }
     */
    public function ponto_status_post($id = null)
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;
        $corpo = $this->corpo();
        $obraId = (int) ($corpo['obra_id'] ?? 0);
        $ponto = $this->pontoAcessivel($obraId, (int) ($id !== null ? $id : $this->get('id')), $uid);

        $statusLista = Obra_pontos_model::statusLista();
        $status = (string) ($corpo['status'] ?? '');
        if (! isset($statusLista[$status])) {
            $this->invalido('Status inválido.');
        }

        $metragem = $this->metragem($corpo['metragem_cabo'] ?? null);
        if ($metragem === false) {
            $this->invalido('Metros de cabo inválidos.');
        }

        $repasse = $this->resolverRepasse($obraId, $corpo['repassar_para'] ?? '');
        if (! $repasse['valido']) {
            $this->invalido('Destinatário do repasse inválido.');
        }
        if ($repasse['pede'] && $status === 'testado') {
            $this->invalido('Ponto testado não pode ser repassado.');
        }

        $etiquetas = $this->fotosDataUrl((array) ($corpo['fotos_etiqueta'] ?? []));
        $fotos = array_slice($this->fotosDataUrl((array) ($corpo['fotos'] ?? [])), 0, max(0, 6 - count($etiquetas)));

        if (Obra_pontos_model::exigeEtiqueta($status, $ponto->tipo)
            && ! $etiquetas && ! $this->obra_pontos_model->temFotoEtiqueta((int) $ponto->idPonto)) {
            $this->invalido('Tire a foto da etiqueta da câmera (modelo e número de série).');
        }

        $exigeTeste = $this->obra_pontos_model->precisaTeste($ponto, $status);
        if ((Obra_pontos_model::exigeFoto($status) || $exigeTeste) && ! $fotos && ! $etiquetas) {
            foreach ($this->obra_pontos_model->getFotos((int) $ponto->idPonto) as $f) {
                if (($f->etapa ?? null) === $status) {
                    $this->invalido('Já existe foto nesta etapa; envie uma nova para confirmar.');
                }
            }
            $this->invalido($status === 'testado'
                ? 'Tire ao menos uma foto da imagem da câmera testada.'
                : 'Tire ao menos uma foto do equipamento instalado.');
        }

        $teste = null;
        if ($exigeTeste) {
            $teste = [
                'itens' => (array) ($corpo['teste']['itens'] ?? []),
                'retencao_dias' => $corpo['teste']['retencao_dias'] ?? null,
            ];
            $v = Obra_pontos_model::validarTeste($teste);
            if (! $v['ok']) {
                $this->invalido(Obra_pontos_model::mensagemTesteFaltando($v['faltando']));
            }
        }

        $local = $this->cortar($corpo['local'] ?? '', 150);
        $instrucao = $this->cortar($corpo['instrucao'] ?? '', 255);

        $ok = $this->obra_pontos_model->mudarStatus((int) $ponto->idPonto, $obraId, $status, $uid, [
            'ip' => trim((string) ($corpo['ip'] ?? '')),
            'canal' => trim((string) ($corpo['canal'] ?? '')),
            'porta' => trim((string) ($corpo['porta'] ?? '')),
            'modelo' => $this->cortar($corpo['modelo'] ?? '', 80),
            'serie' => $this->cortar($corpo['serie'] ?? '', 60),
            'local' => $local,
            'metragem_cabo' => $metragem,
            'observacao' => $this->cortar($corpo['observacao'] ?? '', 255),
            'teste' => $teste,
        ]);
        if (! $ok) {
            $faltando = $this->obra_pontos_model->ultimoErroTeste();
            $this->invalido($faltando
                ? Obra_pontos_model::mensagemTesteFaltando($faltando)
                : 'Não foi possível atualizar o ponto.');
        }

        if ($local !== null && $local !== (string) ($ponto->local ?? '')) {
            $this->obra_pontos_model->editPonto((int) $ponto->idPonto, $obraId, ['local' => $local]);
        }

        $historicoId = $this->obra_pontos_model->ultimoHistoricoId((int) $ponto->idPonto);
        $salvas = 0;
        foreach ($fotos as $bin) {
            if ($this->obra_pontos_model->addFoto((int) $ponto->idPonto, $obraId, $bin, $uid, $status, $historicoId) > 0) {
                $salvas++;
            }
        }
        foreach ($etiquetas as $bin) {
            if ($this->obra_pontos_model->addFoto((int) $ponto->idPonto, $obraId, $bin, $uid, $status, $historicoId, Obra_pontos_model::LEGENDA_ETIQUETA) > 0) {
                $salvas++;
            }
        }

        $repassado = null;
        if ($repasse['pede'] && $this->obra_pontos_model->repassar(
            (int) $ponto->idPonto,
            $obraId,
            $repasse['destinatario_id'],
            $uid,
            $instrucao
        )) {
            $repassado = $repasse['destinatario_id']
                ? 'repassado para ' . ($repasse['nome'] !== '' ? $repasse['nome'] : 'outro técnico')
                : 'deixado para a equipe';
        }

        log_info('API: técnico ' . $uid . ' atualizou o ponto ' . $ponto->idPonto . ' (' . $status . ')');

        $mensagem = $ponto->identificacao . ': ' . $statusLista[$status]['rotulo'] . ($repassado ? ' · ' . $repassado : '');
        if (($fotos || $etiquetas) && $salvas === 0) {
            $mensagem .= ' Atenção: as fotos não foram salvas (use JPEG, PNG ou WebP até 4 MB).';
        }

        $atual = $this->obra_pontos_model->getPonto((int) $ponto->idPonto);
        $this->ok([
            'ponto' => $this->pontoResumo($atual, $uid, $statusLista),
            'fotos_salvas' => $salvas,
            'repassado' => $repassado,
        ], $mensagem);
    }

    /** POST /api/v1/tecnico/pontos/{id}/assumir { obra_id } */
    public function ponto_assumir_post($id = null)
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;
        $corpo = $this->corpo();
        $obraId = (int) ($corpo['obra_id'] ?? 0);
        $ponto = $this->pontoAcessivel($obraId, (int) ($id !== null ? $id : $this->get('id')), $uid);

        if (! $this->obra_pontos_model->assumir((int) $ponto->idPonto, $obraId, $uid)) {
            $this->invalido('Não foi possível assumir este ponto.');
        }
        log_info('API: técnico ' . $uid . ' assumiu o ponto ' . $ponto->idPonto);

        $this->ok(['idPonto' => (int) $ponto->idPonto], $ponto->identificacao . ': assumido por você.');
    }

    /** POST /api/v1/tecnico/pontos/{id}/repassar { obra_id, repassar_para, instrucao? } */
    public function ponto_repassar_post($id = null)
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;
        $corpo = $this->corpo();
        $obraId = (int) ($corpo['obra_id'] ?? 0);
        $ponto = $this->pontoAcessivel($obraId, (int) ($id !== null ? $id : $this->get('id')), $uid);

        $repasse = $this->resolverRepasse($obraId, $corpo['repassar_para'] ?? '');
        if (! $repasse['valido']) {
            $this->invalido('Destinatário inválido.');
        }
        if (! $repasse['pede']) {
            $this->invalido('Escolha quem vai receber o ponto.');
        }
        if ($ponto->status === 'testado') {
            $this->invalido('Ponto testado não pode ser repassado.');
        }

        if (! $this->obra_pontos_model->repassar(
            (int) $ponto->idPonto,
            $obraId,
            $repasse['destinatario_id'],
            $uid,
            $this->cortar($corpo['instrucao'] ?? '', 255)
        )) {
            $this->invalido('Não foi possível repassar este ponto.');
        }

        $this->ok([
            'idPonto' => (int) $ponto->idPonto,
            'destinatario_id' => $repasse['destinatario_id'],
        ], $ponto->identificacao . ($repasse['destinatario_id']
            ? ' · repassado para ' . ($repasse['nome'] !== '' ? $repasse['nome'] : 'outro técnico')
            : ' · deixado para a equipe'));
    }

    /** GET /api/v1/tecnico/projetos/foto/{id} — foto do ponto (só de projeto com acesso). */
    public function foto_get($id = null)
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;
        $fotoId = (int) ($id !== null ? $id : $this->get('id'));

        $foto = $fotoId ? $this->obra_pontos_model->getFotoBinaria($fotoId) : null;
        if (! $foto || empty($foto->foto) || ! $this->obras_model->usuarioTemAcesso((int) $foto->obra_id, $uid)) {
            $this->naoEncontrada('Foto não encontrada.');
        }

        $this->servirBase64('data:image/jpeg;base64,' . base64_encode($foto->foto), 'image/jpeg', true);
    }

    // ------------------------------------------------------------------
    // RDO (registro de execução no projeto)
    // ------------------------------------------------------------------

    /**
     * POST /api/v1/tecnico/projetos/{id}/rdo
     * { atividades, condicao?, materiais?: [{id,quantidade,nome?}],
     *   servicos?: [{id,quantidade,nome?}], fotos?: [dataUrl], assinatura? }
     */
    public function rdo_post($id = null)
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;
        $obraId = (int) ($id !== null ? $id : $this->get('id'));

        $obra = $obraId ? $this->obras_model->getObra($obraId) : null;
        if (! $obra || ! $this->obras_model->usuarioTemAcesso($obraId, $uid)) {
            $this->naoEncontrada('Projeto não encontrado ou sem acesso.');
        }

        $corpo = $this->corpo();
        $atividades = $this->cortar($corpo['atividades'] ?? '', 4000);
        $materiais = $this->itens($corpo['materiais'] ?? []);
        $servicos = $this->itens($corpo['servicos'] ?? []);
        if ($atividades === null && ! $materiais && ! $servicos) {
            $this->invalido('Descreva o que foi feito, ou informe material/serviço realizado.');
        }

        $condicao = in_array($corpo['condicao'] ?? '', ['praticavel', 'impraticavel'], true)
            ? $corpo['condicao']
            : 'praticavel';
        $assinatura = dataurl_imagem($corpo['assinatura'] ?? '');

        // Registro feito sem internet vale para o dia em que foi feito (até 72 h).
        $agora = $this->momentoInformado($corpo['registrado_em'] ?? null);
        $rdoId = $this->obras_model->addRdo([
            'obra_id' => $obraId,
            'numero' => $this->obras_model->proximoNumeroRdo($obraId),
            'data' => substr($agora, 0, 10),
            'condicao' => $condicao,
            'responsavel_id' => $uid,
            'atividades' => $atividades ?? '(sem descrição)',
            'status' => 'finalizado',
            'assinatura' => $assinatura,
            'data_registro' => $agora,
        ]);
        if (! $rdoId) {
            $this->invalido('Não foi possível registrar o RDO.');
        }

        foreach ($this->fotosDataUrl((array) ($corpo['fotos'] ?? [])) as $bin) {
            $this->obras_model->addRdoFoto($rdoId, $bin);
        }

        $controlaEstoque = $this->config('control_estoque');

        foreach ($materiais as $item) {
            $this->obras_model->addConsumo([
                'obra_id' => $obraId,
                'rdo_id' => $rdoId,
                'tipo' => 'material',
                'referencia_id' => $item['id'],
                'descricao' => $item['nome'],
                'quantidade' => $item['quantidade'],
                'usuario_id' => $uid,
                'data' => $agora,
            ]);
            if ($controlaEstoque) {
                $this->produtos_model->updateEstoque($item['id'], $item['quantidade'], '-', ['tipo' => 'projeto', 'referencia' => $obraId]);
            }
        }

        foreach ($servicos as $item) {
            $this->obras_model->addConsumo([
                'obra_id' => $obraId,
                'rdo_id' => $rdoId,
                'tipo' => 'servico',
                'referencia_id' => $item['id'],
                'descricao' => $item['nome'],
                'quantidade' => $item['quantidade'],
                'usuario_id' => $uid,
                'data' => $agora,
            ]);
        }

        log_info('API: técnico ' . $uid . ' registrou o RDO ' . $rdoId . ' no projeto ' . $obraId);

        try {
            $this->load->library('notificador');
            $this->notificador->whatsappRdo($rdoId);
            $this->notificador->emailRdo($rdoId);
        } catch (Throwable $e) {
            log_info('Falha ao notificar o RDO #' . $rdoId . ': ' . $e->getMessage());
        }

        $this->ok([
            'idRdo' => (int) $rdoId,
            'materiais' => count($materiais),
            'servicos' => count($servicos),
        ], 'Execução registrada!');
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    /** Projeto acessível + ponto pertencente a ele (ou 404). */
    protected function pontoAcessivel($obraId, $pontoId, $uid)
    {
        $obra = $obraId ? $this->obras_model->getObra($obraId) : null;
        if (! $obra || ! $this->obras_model->usuarioTemAcesso($obraId, $uid)) {
            $this->naoEncontrada('Você não tem acesso a este projeto.');
        }

        $ponto = $pontoId ? $this->obra_pontos_model->getPonto($pontoId) : null;
        if (! $ponto || (int) $ponto->obra_id !== (int) $obraId) {
            $this->naoEncontrada('Ponto não encontrado neste projeto.');
        }

        return $ponto;
    }

    /** '' = não repassa; 'equipe' = fila da equipe; id = executor do projeto. */
    protected function resolverRepasse($obraId, $valor)
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return ['pede' => false, 'destinatario_id' => null, 'nome' => '', 'valido' => true];
        }
        if ($valor === 'equipe') {
            return ['pede' => true, 'destinatario_id' => null, 'nome' => '', 'valido' => true];
        }

        $id = ctype_digit($valor) ? (int) $valor : 0;
        foreach ($this->obra_pontos_model->executores($obraId) as $ex) {
            if ((int) $ex['idUsuarios'] === $id) {
                $partes = preg_split('/\s+/', trim((string) $ex['nome']));

                return ['pede' => true, 'destinatario_id' => $id, 'nome' => $partes[0] ?? '', 'valido' => true];
            }
        }

        return ['pede' => true, 'destinatario_id' => null, 'nome' => '', 'valido' => false];
    }

    /** Metros de cabo: aceita vírgula, vazio = 0, inválido = false. */
    protected function metragem($valor)
    {
        $txt = str_replace(',', '.', trim((string) $valor));
        if ($txt === '') {
            return 0.0;
        }
        if (! is_numeric($txt) || (float) $txt < 0) {
            return false;
        }

        return (float) $txt;
    }

    /** Data URLs válidas decodificadas em binário (máx. 6). */
    protected function fotosDataUrl(array $dataUrls)
    {
        $out = [];
        foreach (array_slice($dataUrls, 0, 6) as $dataUrl) {
            $img = dataurl_imagem($dataUrl);
            if ($img === null) {
                continue;
            }
            $bin = base64_decode(substr($img, strpos($img, ',') + 1), true);
            if ($bin !== false && Obra_pontos_model::fotoValida($bin) !== null) {
                $out[] = $bin;
            }
        }

        return $out;
    }

    /** [{ id, quantidade, nome? }] com id numérico e quantidade > 0. */
    protected function itens($lista)
    {
        $out = [];
        foreach ((array) $lista as $item) {
            $id = (int) (is_array($item) ? ($item['id'] ?? 0) : $item);
            $qtd = (float) (is_array($item) ? ($item['quantidade'] ?? 0) : 1);
            if (! $id || $qtd <= 0) {
                continue;
            }
            $out[] = [
                'id' => $id,
                'quantidade' => $qtd,
                'nome' => $this->cortar(is_array($item) ? ($item['nome'] ?? '') : '', 150),
            ];
        }

        return $out;
    }

    protected function pontoResumo($p, $uid, array $statusLista)
    {
        $status = (string) ($p->status ?? '');

        return [
            'idPonto' => (int) $p->idPonto,
            'obra_id' => (int) $p->obra_id,
            'identificacao' => $p->identificacao ?? null,
            'tipo' => $p->tipo ?? null,
            'local' => $p->local ?? null,
            'status' => $status,
            'status_rotulo' => $statusLista[$status]['rotulo'] ?? $status,
            'aguardando' => $p->aguardando ?? null,
            'responsavel' => $p->responsavel_nome ?? null,
            'qtd_fotos' => (int) ($p->qtd_fotos ?? 0),
            'minha' => (int) ($p->responsavel_id ?? 0) === (int) $uid,
            'ip' => $p->ip ?? null,
            'canal' => $p->canal ?? null,
            'porta' => $p->porta ?? null,
            'modelo' => $p->modelo ?? null,
            'serie' => $p->serie ?? null,
            'metragem_cabo' => $p->metragem_cabo ?? null,
            'observacao' => $p->observacao ?? null,
        ];
    }

    protected function historico($ponto)
    {
        $statusLista = Obra_pontos_model::statusLista();
        $out = [];
        foreach ((array) $this->obra_pontos_model->getHistoricoPonto((int) $ponto->idPonto) as $h) {
            $out[] = [
                'id' => (int) ($h->idHistorico ?? 0),
                'de' => $statusLista[$h->status_anterior]['rotulo'] ?? ($h->status_anterior ?? null),
                'para' => $statusLista[$h->status_novo]['rotulo'] ?? ($h->status_novo ?? null),
                'data' => $h->data ?? null,
                'usuario' => $h->usuario ?? null,
                'observacao' => $h->observacao ?? null,
                'evento' => $h->evento ?? null,
                'destinatario' => $h->destinatario ?? null,
            ];
        }

        return $out;
    }

    protected function fotosPonto($ponto)
    {
        $out = [];
        foreach ((array) $this->obra_pontos_model->getFotos((int) $ponto->idPonto) as $f) {
            $out[] = [
                'id' => (int) $f->idFoto,
                'etapa' => $f->etapa ?? null,
                'legenda' => $f->legenda ?? null,
                'data' => $f->data ?? null,
                'url' => 'tecnico/projetos/foto/' . (int) $f->idFoto,
            ];
        }

        return $out;
    }

    /** Valor de configuracoes como booleano. */
    protected function config($chave)
    {
        $row = $this->db->select('valor')->get_where('configuracoes', ['config' => $chave])->row();

        return $row && in_array(strtolower(trim((string) $row->valor)), ['1', 'true', 'sim', 'yes'], true);
    }
}
