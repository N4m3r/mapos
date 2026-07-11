<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Registro de ponto (batida) — tela mobile do colaborador.
 *
 * Canal principal: navegador com selfie (facial + GPS + geofence). O
 * reconhecimento facial roda no cliente (face-api.js); aqui recebemos o
 * `face_score` já calculado e a selfie como prova.
 */
class Ponto extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('rh_colaboradores_model');
        $this->load->model('rh_ponto_model');
        $this->load->helper('date');

        if (! $this->session->userdata('id_admin')) {
            redirect('login');
        }
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'baterPonto')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para registrar ponto.');
            redirect(base_url());
        }
    }

    /** Resolve o colaborador vinculado ao usuário logado. */
    private function colaboradorLogado()
    {
        return $this->rh_colaboradores_model->getByUsuario($this->session->userdata('id_admin'));
    }

    /** Tela de batida. */
    public function index()
    {
        $colaborador = $this->colaboradorLogado();
        if (! $colaborador) {
            $this->session->set_flashdata('error', 'Seu usuário não está vinculado a um cadastro de colaborador. Procure o RH.');
            redirect(base_url());
        }

        $data['colaborador'] = $colaborador;
        $data['batidas_hoje'] = $this->rh_ponto_model->getDoDia($colaborador->id);
        $data['proximo_tipo'] = $this->rh_ponto_model->proximoTipo($colaborador->id);
        $data['unidades'] = $this->rh_colaboradores_model->listarUnidades(true);
        $data['tem_biometria'] = $this->rh_colaboradores_model->temBiometria($colaborador->id);
        $data['cfg'] = [
            'geofence_obrigatorio' => (int) ($this->data['configuration']['rh_geofence_obrigatorio'] ?? 0),
            'face_obrigatorio' => (int) ($this->data['configuration']['rh_face_obrigatorio'] ?? 0),
            'face_score_minimo' => (float) ($this->data['configuration']['rh_face_score_minimo'] ?? 0.55),
        ];
        $data['pode_ver_sistema'] = $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs');
        $data['titulo'] = 'Registrar Ponto';

        $this->load->view('ponto/registrar', $data);
    }

    /** Devolve o descriptor facial de referência do colaborador (JSON). */
    public function descriptor()
    {
        if (! $this->input->is_ajax_request()) {
            redirect(base_url());
        }
        $colaborador = $this->colaboradorLogado();
        if (! $colaborador) {
            echo json_encode(['success' => false, 'message' => 'Colaborador não encontrado']);
            return;
        }
        $bio = $this->rh_colaboradores_model->getBiometria($colaborador->id);
        echo json_encode([
            'success' => true,
            'tem_biometria' => $bio ? true : false,
            'descriptor' => $bio ? json_decode($bio->descriptor) : null,
        ]);
    }

    /** Registra a batida (AJAX POST). */
    public function registrar()
    {
        if (! $this->input->is_ajax_request()) {
            redirect(base_url());
        }

        $colaborador = $this->colaboradorLogado();
        if (! $colaborador) {
            echo json_encode(['success' => false, 'message' => 'Colaborador não encontrado']);
            return;
        }

        $tipo = $this->input->post('tipo') ?: 'entrada';
        $tiposValidos = ['entrada', 'saida', 'inicio_intervalo', 'fim_intervalo'];
        if (! in_array($tipo, $tiposValidos, true)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de batida inválido']);
            return;
        }

        // FALSE no XSS filter para não corromper o base64 da selfie
        $foto = $this->input->post('foto', false);
        $latitude = $this->input->post('latitude');
        $longitude = $this->input->post('longitude');
        $faceScore = $this->input->post('face_score');
        $unidadeId = $this->input->post('unidade_id') ?: $colaborador->unidade_id;

        $cfgGeofenceObrig = (int) ($this->data['configuration']['rh_geofence_obrigatorio'] ?? 0);
        $cfgFaceObrig = (int) ($this->data['configuration']['rh_face_obrigatorio'] ?? 0);
        $cfgFaceMin = (float) ($this->data['configuration']['rh_face_score_minimo'] ?? 0.55);

        // ---- Validação facial ----
        if ($cfgFaceObrig) {
            if ($faceScore === null || $faceScore === '') {
                echo json_encode(['success' => false, 'message' => 'Reconhecimento facial obrigatório. Cadastre/valide seu rosto.']);
                return;
            }
            if ((float) $faceScore < $cfgFaceMin) {
                echo json_encode(['success' => false, 'message' => 'Rosto não reconhecido com segurança. Tente novamente com melhor iluminação.']);
                return;
            }
        }

        // ---- Geofence ----
        $dentro = null;
        $distancia = null;
        if ($unidadeId && $latitude && $longitude) {
            $unidade = $this->rh_colaboradores_model->getUnidade($unidadeId);
            if ($unidade && $unidade->latitude && $unidade->longitude) {
                $distancia = (int) round($this->distanciaMetros(
                    (float) $latitude,
                    (float) $longitude,
                    (float) $unidade->latitude,
                    (float) $unidade->longitude
                ));
                $dentro = $distancia <= (int) $unidade->raio_metros ? 1 : 0;

                if ($cfgGeofenceObrig && ! $dentro) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Você está fora da área permitida (' . $distancia . 'm). Aproxime-se do local de trabalho.',
                    ]);
                    return;
                }
            }
        } elseif ($cfgGeofenceObrig) {
            echo json_encode(['success' => false, 'message' => 'Localização obrigatória. Ative o GPS e permita o acesso à localização.']);
            return;
        }

        // ---- Selfie ----
        $fotoMime = null;
        if ($foto && preg_match('/^data:(image\/\w+);base64,/', $foto, $m)) {
            $fotoMime = $m[1];
        }

        $registroId = $this->rh_ponto_model->registrar([
            'colaborador_id' => $colaborador->id,
            'data_hora' => date('Y-m-d H:i:s'),
            'tipo' => $tipo,
            'origem' => 'browser',
            'unidade_id' => $unidadeId ?: null,
            'latitude' => $latitude ?: null,
            'longitude' => $longitude ?: null,
            'dentro_geofence' => $dentro,
            'distancia_metros' => $distancia,
            'face_score' => ($faceScore !== null && $faceScore !== '') ? (float) $faceScore : null,
            'foto_base64' => $foto ?: null,
            'foto_mime' => $fotoMime,
            'ip' => $this->input->ip_address(),
            'user_agent' => substr((string) $this->input->user_agent(), 0, 255),
            'status' => 'valido',
        ], true);

        if (! $registroId) {
            echo json_encode(['success' => false, 'message' => 'Erro ao registrar ponto']);
            return;
        }

        log_info('Ponto registrado - Colaborador: ' . $colaborador->id . ' Tipo: ' . $tipo);

        $labels = [
            'entrada' => 'Entrada', 'saida' => 'Saída',
            'inicio_intervalo' => 'Início do intervalo', 'fim_intervalo' => 'Fim do intervalo',
        ];

        echo json_encode([
            'success' => true,
            'message' => $labels[$tipo] . ' registrada às ' . date('H:i'),
            'registro_id' => $registroId,
            'hora' => date('H:i'),
            'tipo' => $tipo,
            'proximo_tipo' => $this->rh_ponto_model->proximoTipo($colaborador->id),
            'fora_area' => ($dentro === 0),
        ]);
    }

    /** Serve a selfie de uma batida (dono ou quem tem vRh). */
    public function foto($id = null)
    {
        $registro = $this->rh_ponto_model->getById($id);
        if (! $registro || empty($registro->foto_base64)) {
            show_404();
            return;
        }

        // Autorização: dono da batida ou usuário com acesso ao RH
        $colaborador = $this->colaboradorLogado();
        $ehDono = $colaborador && $colaborador->id == $registro->colaborador_id;
        $ehRh = $this->permission->checkPermission($this->session->userdata('permissao'), 'vRh');
        if (! $ehDono && ! $ehRh) {
            show_error('Acesso negado', 403);
            return;
        }

        $this->servirImagemBase64($registro->foto_base64, $registro->foto_mime);
    }

    /** Comprovante da batida (tela simples imprimível). */
    public function comprovante($id = null)
    {
        $registro = $this->rh_ponto_model->getById($id);
        if (! $registro) {
            show_404();
            return;
        }
        $colaborador = $this->colaboradorLogado();
        if (! $colaborador || $colaborador->id != $registro->colaborador_id) {
            if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vRh')) {
                show_error('Acesso negado', 403);
                return;
            }
        }
        $data['registro'] = $registro;
        $data['colaborador'] = $this->rh_colaboradores_model->getById($registro->colaborador_id);
        $data['titulo'] = 'Comprovante de Ponto';
        $this->load->view('ponto/comprovante', $data);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** Distância (metros) entre duas coordenadas — fórmula de Haversine. */
    private function distanciaMetros($lat1, $lon1, $lat2, $lon2)
    {
        $raioTerra = 6371000; // metros
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $raioTerra * $c;
    }

    private function servirImagemBase64($base64, $mimeFallback = null)
    {
        if (preg_match('/^data:(image\/\w+);base64,/', $base64, $m)) {
            $mime = $m[1];
            $dados = substr($base64, strlen($m[0]));
        } else {
            $mime = $mimeFallback ?: 'image/jpeg';
            $dados = $base64;
        }
        $bin = base64_decode($dados, true);
        if ($bin === false) {
            show_error('Erro ao decodificar imagem', 500);
            return;
        }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: private, max-age=86400');
        echo $bin;
        exit;
    }
}
