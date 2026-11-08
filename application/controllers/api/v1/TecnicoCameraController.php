<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API v1 — Configuração de câmeras e NVR Hikvision no app do técnico.
 *
 * O app descobre os equipamentos pela rede local (adaptador USB/Ethernet) e
 * envia aqui o resultado. O servidor não fala com a rede do cliente: ele apenas
 * confere acesso ao projeto, evita IP/canal duplicados e grava o ponto.
 *
 * O cadastro prévio do projeto (pontos tipo=camera e tipo=nvr) é a base da
 * configuração: `plano` devolve o que o projeto previu e o app usa isso para
 * casar cada câmera descoberta com o ponto correspondente.
 */
require_once __DIR__ . '/AreaApiController.php';

class TecnicoCameraController extends AreaApiController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('obras_model');
        $this->load->model('obra_pontos_model');
        $this->load->model('camera_nvr_model');
    }

    // ------------------------------------------------------------------
    // Plano do projeto
    // ------------------------------------------------------------------

    /** GET /api/v1/tecnico/projetos/{id}/camera-config — NVRs, câmeras e pendências. */
    public function plano_get($id = null)
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;
        $obraId = (int) ($id !== null ? $id : $this->get('id'));
        $obra = $this->obraAcessivel($obraId, $uid);

        $plano = $this->camera_nvr_model->plano($obraId);
        $statusLista = Obra_pontos_model::statusLista();

        // Canais já ocupados por câmera do projeto, por NVR (para o app não
        // sugerir um canal que já é de outra câmera desta mesma lista).
        $canais = [];
        foreach ($plano['cameras'] as $camera) {
            if ($camera['nvr_id'] !== null && $camera['canal'] !== null && $camera['canal'] !== '') {
                $canais[(int) $camera['nvr_id']][(string) $camera['canal']] = $camera['identificacao'];
            }
        }

        $this->ok([
            'obra' => [
                'idObra' => (int) $obra->idObra,
                'nome' => $obra->nome ?? null,
                'nomeCliente' => $obra->nomeCliente ?? null,
                'cidade' => $obra->cidade ?? null,
                'uf' => $obra->uf ?? null,
                'endereco' => trim(implode(', ', array_filter([
                    trim((string) ($obra->logradouro ?? '')),
                    trim((string) ($obra->numero ?? '')),
                    trim((string) ($obra->bairro ?? '')),
                ]))),
            ],
            'nvr' => array_map([$this, 'nvrSaida'], $plano['nvr']),
            'cameras' => array_map(function ($c) use ($statusLista) {
                return $this->cameraSaida($c, $statusLista);
            }, $plano['cameras']),
            'resumo' => $plano['resumo'],
            'canaisOcupados' => $canais,
            'tipos' => [
                'camera' => Obra_pontos_model::tipos()['camera'],
                'nvr' => Obra_pontos_model::tipos()['nvr'],
            ],
        ], 'Plano de câmeras do projeto.');
    }

    // ------------------------------------------------------------------
    // Gravação
    // ------------------------------------------------------------------

    /**
     * POST /api/v1/tecnico/projetos/{id}/camera-config/pontos/{ponto}
     * { ip, canal, nvr_id, mac, modelo, serie, sadp_id, firmware, ativada, observacao, status? }
     */
    public function ponto_post($id = null, $pontoId = null)
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;
        $obraId = (int) ($id !== null ? $id : $this->get('obra_id'));
        $this->obraAcessivel($obraId, $uid);

        $ponto = (int) ($pontoId !== null ? $pontoId : $this->get('ponto_id'));
        if (! $ponto) {
            $this->invalido('Informe o ponto do projeto.');
        }

        $r = $this->camera_nvr_model->salvar($obraId, $ponto, $this->dadosCamera($this->corpo()), $uid);
        if (! $r['ok']) {
            $this->invalido((string) $r['erro']);
        }

        $this->ok([
            'ponto' => $this->cameraSaida($this->linhaPonto($r['ponto']), Obra_pontos_model::statusLista()),
        ], $r['ponto']->identificacao . ': configuração salva.');
    }

    /**
     * POST /api/v1/tecnico/projetos/{id}/camera-config/nova
     * Cria um ponto novo (quando o projeto não previu essa câmera).
     * { identificacao?, tipo?, ip, canal?, nvr_id?, mac, modelo, serie, sadp_id?, firmware?, ativada? }
     */
    public function criar_post($id = null)
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;
        $obraId = (int) ($id !== null ? $id : $this->get('obra_id'));
        $this->obraAcessivel($obraId, $uid);

        $r = $this->camera_nvr_model->criarDoDescoberto($obraId, $this->dadosCamera($this->corpo()), $uid);
        if (! $r['ok']) {
            $this->invalido((string) $r['erro']);
        }

        $this->ok([
            'idPonto' => (int) $r['idPonto'],
            'ponto' => $this->cameraSaida($this->linhaPonto($r['ponto']), Obra_pontos_model::statusLista()),
        ], $r['ponto']->identificacao . ': ponto criado e configurado.');
    }

    /**
     * POST /api/v1/tecnico/projetos/{id}/camera-config/lote
     * { cameras: [ { ponto_id? , identificacao?, ...campos da câmera } ] }
     *
     * Salva de uma vez o que o técnico configurou em campo (a rede cai, o
     * celular fica sem sinal: é melhor mandar tudo junto no fim).
     */
    public function lote_post($id = null)
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;
        $obraId = (int) ($id !== null ? $id : $this->get('obra_id'));
        $this->obraAcessivel($obraId, $uid);

        $corpo = $this->corpo();
        $lista = (array) ($corpo['cameras'] ?? []);
        if (! $lista) {
            $this->invalido('Envie ao menos uma câmera.');
        }
        if (count($lista) > 64) {
            $this->invalido('Envie no máximo 64 câmeras por vez.');
        }

        $salvas = 0;
        $criadas = 0;
        $falhas = [];
        foreach ($lista as $item) {
            $item = is_array($item) ? $item : [];
            $dados = $this->dadosCamera($item);
            $pontoId = (int) ($item['ponto_id'] ?? 0);
            if ($pontoId) {
                $r = $this->camera_nvr_model->salvar($obraId, $pontoId, $dados, $uid);
                if ($r['ok']) {
                    $salvas++;
                } else {
                    $falhas[] = ['ponto_id' => $pontoId, 'erro' => $r['erro']];
                }
                continue;
            }

            $r = $this->camera_nvr_model->criarDoDescoberto($obraId, $dados, $uid);
            if ($r['ok']) {
                $criadas++;
            } else {
                $falhas[] = ['ip' => $dados['ip'] ?? null, 'erro' => $r['erro']];
            }
        }

        if (! $salvas && ! $criadas) {
            $primeiro = $falhas[0]['erro'] ?? 'Nenhuma câmera foi salva.';
            $this->invalido((string) $primeiro);
        }

        $mensagem = $salvas + $criadas === 1
            ? '1 câmera salva no projeto.'
            : ($salvas + $criadas) . ' câmeras salvas no projeto.';
        if ($falhas) {
            $mensagem .= ' ' . count($falhas) . ' com problema.';
        }

        $this->ok([
            'salvas' => $salvas,
            'criadas' => $criadas,
            'falhas' => $falhas,
            'plano' => $this->camera_nvr_model->plano($obraId),
        ], $mensagem);
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    /** Projeto acessível pelo usuário do token (ou 404). */
    protected function obraAcessivel($obraId, $uid)
    {
        $obra = $obraId ? $this->obras_model->getObra($obraId) : null;
        if (! $obra || ! $this->obras_model->usuarioTemAcesso($obraId, $uid)) {
            $this->naoEncontrada('Projeto não encontrado ou sem acesso.');
        }

        return $obra;
    }

    /** Campos aceitos da câmera/NVR descoberto (só o que o app realmente mediu). */
    protected function dadosCamera(array $corpo)
    {
        $dados = [
            'ip' => trim((string) ($corpo['ip'] ?? '')),
            'canal' => trim((string) ($corpo['canal'] ?? '')),
            'porta' => trim((string) ($corpo['porta'] ?? '')),
            'modelo' => $this->cortar($corpo['modelo'] ?? '', 80),
            'serie' => $this->cortar($corpo['serie'] ?? '', 60),
            'mac' => strtoupper(trim((string) ($corpo['mac'] ?? ''))),
            'sadp_id' => $this->cortar($corpo['sadp_id'] ?? '', 60),
            'fabricante' => $this->cortar($corpo['fabricante'] ?? '', 30) ?: 'Hikvision',
            'firmware' => $this->cortar($corpo['firmware'] ?? '', 80),
            'observacao' => $this->cortar($corpo['observacao'] ?? '', 255),
            'nvr_id' => isset($corpo['nvr_id']) && (int) $corpo['nvr_id'] > 0 ? (int) $corpo['nvr_id'] : null,
        ];

        if (array_key_exists('ativada', $corpo)) {
            $dados['ativada'] = $corpo['ativada'] ? true : false;
        }
        if (isset($corpo['status']) && is_string($corpo['status'])) {
            $dados['status'] = $corpo['status'];
        }
        if ($dados['mac'] !== '' && ! preg_match('/^[0-9A-F]{2}([:-]?[0-9A-F]{2}){5}$/', $dados['mac'])) {
            $dados['mac'] = '';
        }

        return $dados;
    }

    /** Linha do banco com as mesmas chaves que cameraSaida espera. */
    protected function linhaPonto($p)
    {
        return [
            'idPonto' => (int) $p->idPonto,
            'identificacao' => $p->identificacao,
            'tipo' => $p->tipo,
            'local' => $p->local,
            'status' => $p->status,
            'ip' => $p->ip,
            'canal' => $p->canal,
            'porta' => $p->porta,
            'modelo' => $p->modelo,
            'serie' => $p->serie,
            'mac' => $p->mac ?? null,
            'nvr_id' => $p->nvr_id !== null ? (int) $p->nvr_id : null,
            'fabricante' => $p->fabricante ?? null,
            'firmware' => $p->firmware ?? null,
            'ativada' => $p->ativada !== null ? (int) $p->ativada : null,
            'configurada_em' => $p->configurada_em ?? null,
            'observacao' => $p->observacao,
            'configurada' => $p->status === 'configurado' || $p->status === 'testado',
        ];
    }

    protected function cameraSaida($c, array $statusLista)
    {
        $status = (string) ($c['status'] ?? '');
        $c['status_rotulo'] = $statusLista[$status]['rotulo'] ?? $status;

        return $c;
    }

    protected function nvrSaida($n)
    {
        return $n;
    }
}
