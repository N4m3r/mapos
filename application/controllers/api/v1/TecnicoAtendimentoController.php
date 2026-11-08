<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API v1 — Ações de campo do técnico: check-in, check-out, fotos, observações,
 * serviço não realizado, reagendamento, ponto de projeto e atividade avulsa.
 *
 * Espelha as mesmas regras dos controllers web (Checkin, Tecnico, Ponto):
 * valida dono da OS, exige as permissões eTecnico*, valida geofence, imagem e
 * assinatura antes de gravar. Nada aqui confia no id enviado pelo app.
 */
require_once __DIR__ . '/AreaApiController.php';

class TecnicoAtendimentoController extends AreaApiController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('tecnico_model');
        $this->load->model('checkin_model');
        $this->load->model('assinaturas_model');
        $this->load->model('fotosatendimento_model');
        $this->load->model('naorealizada_model');
        $this->load->model('os_observacoes_tecnico_model');
        $this->load->model('os_model');
        $this->load->model('obras_model');
        $this->load->model('obra_pontos_model');
        $this->load->model('mapos_model');
        $this->load->helper('tecnico_inicio');
    }

    // ------------------------------------------------------------------
    // Check-in
    // ------------------------------------------------------------------

    /** POST /api/v1/tecnico/checkin { os_id, observacao?, assinatura?, latitude?, longitude? } */
    public function checkin_post()
    {
        $user = $this->exigir('eTecnicoCheckin');
        $uid = (int) $user->usuario->idUsuarios;
        $corpo = $this->corpo();
        $osId = (int) ($corpo['os_id'] ?? 0);

        $os = $this->osDoTecnico($osId, $uid);
        if (! $os) {
            $this->naoEncontrada('OS não encontrada ou não está designada a você.');
        }

        // OS em status terminal nao aceita novo atendimento. O app esconde o botao,
        // mas a API precisa recusar a chamada direta.
        require_once APPPATH . 'helpers/atendimento_regras_helper.php';
        if (AtendimentoRegras::statusBloqueiaAtendimento($os->status ?? null)) {
            $this->invalido('Esta OS já está ' . $os->status . ' e não aceita novo atendimento. Peça ao administrativo para reabrir a OS (status Aprovado) se precisar voltar ao local.');
        }

        if ($this->checkin_model->hasCheckinAtivo($osId)) {
            $this->invalido('Já existe um atendimento em andamento para esta OS.');
        }

        $assinatura = dataurl_imagem($corpo['assinatura'] ?? '');
        if (! empty($corpo['assinatura']) && $assinatura === null) {
            $this->invalido('Assinatura inválida ou muito grande. Limpe e assine novamente.');
        }

        // Check-in preso (>24h) é fechado antes de abrir um novo.
        $ultimo = $this->checkin_model->getUltimoCheckin($osId);
        if ($ultimo && empty($ultimo->data_saida)) {
            if (strtotime($ultimo->data_entrada) >= strtotime('-24 hours')) {
                $this->invalido('Existe um atendimento iniciado há menos de 24h. Finalize-o antes de iniciar outro.');
            }
            $this->checkin_model->finalizarAtendimento($ultimo->idCheckin, [
                'data_saida' => date('Y-m-d H:i:s'),
                'observacao_saida' => 'Finalizado automaticamente (atendimento anterior não concluído)',
                'status' => 'Finalizado',
            ]);
            $this->load->library('projeto_sync');
            $this->projeto_sync->lancarHorasDoCheckin((int) $ultimo->idCheckin);
        }

        $lat = $this->coordenada($corpo['latitude'] ?? null);
        $lng = $this->coordenada($corpo['longitude'] ?? null);
        $momento = $this->momentoInformado($corpo['registrado_em'] ?? null);

        $dados = [
            'os_id' => $osId,
            'usuarios_id' => $uid,
            'data_entrada' => $momento,
            'latitude_entrada' => $lat,
            'longitude_entrada' => $lng,
            'observacao_entrada' => $this->cortar($corpo['observacao'] ?? '', 2000),
            'status' => 'Em Andamento',
        ];
        $dados = array_merge($dados, $this->geofenceEntrada($osId, $lat, $lng));

        $checkinId = $this->checkin_model->add($dados, true);
        if (! $checkinId) {
            $this->invalido('Não foi possível iniciar o atendimento. Tente novamente.');
        }

        if ($assinatura !== null) {
            $img = $this->assinaturas_model->salvarImagem($assinatura, $osId, 'tecnico_entrada');
            if ($img && isset($img['modo']) && $img['modo'] === 'arquivo') {
                $this->assinaturas_model->add([
                    'os_id' => $osId,
                    'checkin_id' => $checkinId,
                    'tipo' => 'tecnico_entrada',
                    'assinatura' => $img['path'],
                    'nome_assinante' => $user->usuario->nome,
                    'data_assinatura' => $momento,
                    'ip_address' => $this->input->ip_address(),
                ]);
            }
        }

        $this->os_model->alterarStatus($osId, 'Em Andamento');
        log_info('API: técnico ' . $uid . ' iniciou atendimento da OS ' . $osId);

        try {
            require_once APPPATH . 'helpers/avisos_atendimento_helper.php';
            if (AvisosAtendimento::deveAvisarChegada($corpo['registrado_em'] ?? null, time())) {
                $this->load->library('notificador');
                $this->notificador->avisarAtendimento($osId, 'os_tecnico_chegou');
            }
        } catch (Throwable $e) {
            log_message('error', 'Aviso de chegada da OS #' . $osId . ' falhou: ' . $e->getMessage());
        }

        $this->ok([
            'checkin_id' => (int) $checkinId,
            'data_entrada' => $momento,
            'status' => 'Em Andamento',
        ], 'Atendimento iniciado.');
    }

    // ------------------------------------------------------------------
    // Check-out
    // ------------------------------------------------------------------

    /** POST /api/v1/tecnico/checkout { os_id, assinatura, assinatura_cliente?, nome_cliente?, documento_cliente?, observacao?, fotos[]?, latitude?, longitude? } */
    public function checkout_post()
    {
        $user = $this->exigir('eTecnicoCheckout');
        $uid = (int) $user->usuario->idUsuarios;
        $corpo = $this->corpo();
        $osId = (int) ($corpo['os_id'] ?? 0);

        $os = $this->osDoTecnico($osId, $uid);
        if (! $os) {
            $this->naoEncontrada('OS não encontrada ou não está designada a você.');
        }

        $checkin = $this->checkin_model->getCheckinAtivoComAutoFinalizacao($osId);
        if (! $checkin) {
            $ultimo = $this->checkin_model->getUltimoCheckin($osId);
            if ($ultimo && empty($ultimo->data_saida) && strtotime($ultimo->data_entrada) > strtotime('-24 hours')) {
                $checkin = $ultimo;
            } else {
                $this->invalido('Não existe atendimento em andamento para esta OS.');
            }
        }

        $lat = $this->coordenada($corpo['latitude'] ?? null);
        $lng = $this->coordenada($corpo['longitude'] ?? null);
        $geo = $this->geofenceSaida($osId, $lat, $lng);
        if ($geo['modo'] === 'hard') {
            if (! $geo['temGps']) {
                $this->invalido('Localização obrigatória para fechar a OS. Ative o GPS.');
            }
            if ($geo['temCoords'] && $geo['dentro'] === 0) {
                $this->invalido('Você está fora da área do atendimento (' . $geo['distancia'] . ' m).');
            }
        }

        $assinaturaTecnico = dataurl_imagem($corpo['assinatura'] ?? '');
        if ($assinaturaTecnico === null) {
            $this->invalido('Assinatura do técnico obrigatória para finalizar o atendimento.');
        }
        $assinaturaCliente = dataurl_imagem($corpo['assinatura_cliente'] ?? '');
        $clienteOpcional = ! empty($os->nao_programada);
        if ($assinaturaCliente === null && ! $clienteOpcional) {
            $this->invalido('Assinatura do cliente obrigatória para finalizar o atendimento.');
        }

        $fotos = [];
        foreach ((array) ($corpo['fotos'] ?? []) as $dataUrl) {
            $img = dataurl_imagem($dataUrl);
            if ($img !== null) {
                $fotos[] = $img;
            }
        }

        $momento = $this->momentoInformado($corpo['registrado_em'] ?? null);
        $dataCheckout = [
            'data_saida' => $momento,
            'latitude_saida' => $lat,
            'longitude_saida' => $lng,
            'observacao_saida' => $this->cortar($corpo['observacao'] ?? '', 2000),
            'status' => 'Finalizado',
            'data_atualizacao' => date('Y-m-d H:i:s'),
        ];
        if ($this->db->field_exists('dentro_geofence_saida', 'os_checkin')) {
            $dataCheckout['distancia_saida_metros'] = $geo['distancia'];
            $dataCheckout['dentro_geofence_saida'] = $geo['dentro'];
        }

        $falha = null;
        $this->db->trans_begin();

        if (! $this->checkin_model->finalizarAtendimento($checkin->idCheckin, $dataCheckout)) {
            $falha = 'Erro ao finalizar atendimento.';
        }

        foreach ([
            ['tipo' => 'tecnico_saida', 'img' => $assinaturaTecnico, 'nome' => $user->usuario->nome, 'doc' => null],
            ['tipo' => 'cliente_saida', 'img' => $assinaturaCliente, 'nome' => $corpo['nome_cliente'] ?? null, 'doc' => $corpo['documento_cliente'] ?? null],
        ] as $a) {
            if ($falha || $a['img'] === null) {
                continue;
            }
            $img = $this->assinaturas_model->salvarImagem($a['img'], $osId, $a['tipo']);
            if (! $img) {
                $falha = 'Não foi possível salvar a assinatura.';
                break;
            }
            if (isset($img['modo']) && $img['modo'] === 'arquivo') {
                $this->assinaturas_model->add([
                    'os_id' => $osId,
                    'checkin_id' => $checkin->idCheckin,
                    'tipo' => $a['tipo'],
                    'assinatura' => $img['path'],
                    'nome_assinante' => $a['nome'],
                    'documento_assinante' => $a['doc'],
                    'data_assinatura' => $momento,
                    'ip_address' => $this->input->ip_address(),
                ]);
            }
        }

        foreach ($fotos as $foto) {
            if ($falha) {
                break;
            }
            $r = $this->fotosatendimento_model->salvarFotoBase64($foto, $osId, $uid, $checkin->idCheckin, 'saida', '');
            if (isset($r['error'])) {
                $falha = 'Não foi possível salvar uma foto: ' . $r['error'];
                break;
            }
            if (! $this->fotosatendimento_model->add([
                'os_id' => $osId,
                'checkin_id' => $checkin->idCheckin,
                'usuarios_id' => $uid,
                'arquivo' => $r['arquivo'],
                'path' => $r['path'],
                'url' => $r['url'],
                'etapa' => 'saida',
                'data_upload' => $momento,
                'tamanho' => $r['tamanho'],
                'tipo_arquivo' => $r['tipo'],
                'imagem_base64' => $r['imagem_base64'],
                'mime_type' => $r['mime_type'],
            ])) {
                $falha = 'Não foi possível registrar uma foto.';
            }
        }

        if (! $falha && ! $this->os_model->alterarStatus($osId, 'Finalizado')) {
            $falha = 'Não foi possível atualizar o status da OS.';
        }
        if (! $falha && ($this->db->trans_status() === false || ! $this->db->trans_commit())) {
            $falha = 'Falha ao gravar no banco.';
        }
        if ($falha) {
            $this->db->trans_rollback();
            log_info('API checkout falhou (OS ' . $osId . '): ' . $falha);
            $this->invalido($falha . ' Nada foi gravado; tente novamente.');
        }

        $this->load->library('projeto_sync');
        $this->projeto_sync->lancarHorasDoCheckin((int) $checkin->idCheckin);
        log_info('API: técnico ' . $uid . ' finalizou a OS ' . $osId);

        $this->ok([
            'os_id' => $osId,
            'status' => 'Finalizado',
            'data_saida' => $momento,
        ], 'Atendimento finalizado.');
    }

    // ------------------------------------------------------------------
    // Fotos
    // ------------------------------------------------------------------

    /** POST /api/v1/tecnico/fotos { os_id, foto, etapa?, descricao? } */
    public function foto_post()
    {
        $user = $this->exigir('eTecnicoFotos');
        $uid = (int) $user->usuario->idUsuarios;
        $corpo = $this->corpo();
        $osId = (int) ($corpo['os_id'] ?? 0);

        if (! $this->osDoTecnico($osId, $uid)) {
            $this->naoEncontrada('OS não encontrada ou não está designada a você.');
        }

        $foto = dataurl_imagem($corpo['foto'] ?? '');
        if ($foto === null) {
            $this->invalido('Foto inválida. Use JPG, PNG ou WebP até 8 MB.');
        }

        $etapa = in_array($corpo['etapa'] ?? '', ['entrada', 'durante', 'saida'], true) ? $corpo['etapa'] : 'durante';
        $checkin = $this->checkin_model->getCheckinAtivo($osId);

        $r = $this->fotosatendimento_model->salvarFotoBase64(
            $foto,
            $osId,
            $uid,
            $checkin ? $checkin->idCheckin : null,
            $etapa,
            $this->cortar($corpo['descricao'] ?? '', 255)
        );
        if (isset($r['error'])) {
            $this->invalido($r['error']);
        }

        $fotoId = $this->fotosatendimento_model->add([
            'os_id' => $osId,
            'checkin_id' => $checkin ? $checkin->idCheckin : null,
            'usuarios_id' => $uid,
            'arquivo' => $r['arquivo'],
            'path' => $r['path'],
            'url' => $r['url'],
            'descricao' => $this->cortar($corpo['descricao'] ?? '', 255),
            'etapa' => $etapa,
            'data_upload' => date('Y-m-d H:i:s'),
            'tamanho' => $r['tamanho'],
            'tipo_arquivo' => $r['tipo'],
            'imagem_base64' => $r['imagem_base64'],
            'mime_type' => $r['mime_type'],
        ], true);

        if (! $fotoId) {
            $this->invalido('Não foi possível salvar a foto.');
        }

        $this->ok([
            'id' => (int) $fotoId,
            'etapa' => $etapa,
            'url' => 'tecnico/foto/' . (int) $fotoId,
        ], 'Foto enviada.');
    }

    /** GET /api/v1/tecnico/foto/{id} — imagem da foto de atendimento. */
    public function foto_get($id = null)
    {
        $user = $this->exigir('vTecnicoOS');
        $uid = (int) $user->usuario->idUsuarios;
        $fotoId = (int) ($id !== null ? $id : $this->get('id'));

        $foto = $fotoId ? $this->fotosatendimento_model->getById($fotoId) : null;
        if (! $foto || ! $this->osDoTecnico((int) $foto->os_id, $uid)) {
            $this->naoEncontrada('Foto não encontrada.');
        }

        $base64 = $foto->imagem_base64 ?? $this->fotosatendimento_model->getImagemBase64($fotoId);
        $this->servirBase64($base64, $foto->mime_type ?? 'image/jpeg', true);
    }

    // ------------------------------------------------------------------
    // Observações
    // ------------------------------------------------------------------

    /** POST /api/v1/tecnico/observacoes { os_id, texto } */
    public function observacoes_post()
    {
        $user = $this->exigir('vTecnicoOS');
        $uid = (int) $user->usuario->idUsuarios;
        $corpo = $this->corpo();
        $osId = (int) ($corpo['os_id'] ?? 0);

        if (! $this->osDoTecnico($osId, $uid)) {
            $this->naoEncontrada('OS não encontrada ou não está designada a você.');
        }

        $texto = trim((string) ($corpo['texto'] ?? ''));
        $len = function_exists('mb_strlen') ? mb_strlen($texto) : strlen($texto);
        if ($len < 1 || $len > 2000) {
            $this->invalido('Escreva de 1 a 2000 caracteres.');
        }

        $checkin = $this->checkin_model->getCheckinAtivo($osId);
        $id = $this->os_observacoes_tecnico_model->adicionar($osId, $uid, $texto, $checkin ? $checkin->idCheckin : null);
        if (! $id) {
            $this->invalido('Não foi possível salvar a observação.');
        }

        $this->ok([
            'id' => (int) $id,
            'texto' => $texto,
            'hora' => date('d/m/Y H:i'),
            'autor' => $user->usuario->nome,
        ], 'Observação registrada.');
    }
}
