<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API v1 — Batida de ponto do colaborador.
 *
 * Mesmas regras do Ponto web: máquina de estados (entrada → intervalo → … →
 * saída), vínculo opcional com OS ou obra do próprio usuário, geofence da
 * unidade, e selfie como prova. O que muda é só o transporte: JSON em vez de
 * formulário, e o colaborador vem do token.
 */
require_once __DIR__ . '/AreaApiController.php';

class PontoController extends AreaApiController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('rh_colaboradores_model');
        $this->load->model('rh_ponto_model');
        $this->load->helper('date');
    }

    /** GET /api/v1/ponto — estado atual: batidas, próximo tipo e vínculos possíveis. */
    public function index_get()
    {
        $user = $this->exigir('baterPonto');
        $c = $this->colaborador($user);
        $uid = (int) $user->usuario->idUsuarios;

        $this->ok([
            'colaborador' => ['id' => (int) $c->id, 'nome' => $c->nome],
            'hoje' => date('Y-m-d'),
            'batidasHoje' => $this->batidas($c->id),
            'proximoTipo' => $this->rh_ponto_model->proximoTipo($c->id),
            'unidades' => array_map(static function ($u) {
                return [
                    'id' => (int) $u->id,
                    'nome' => $u->nome,
                    'latitude' => $u->latitude ?? null,
                    'longitude' => $u->longitude ?? null,
                    'raio_metros' => $u->raio_metros ?? null,
                ];
            }, (array) $this->rh_colaboradores_model->listarUnidades(true)),
            'os' => $this->osDoUsuario($uid),
            'obras' => $this->obrasDoUsuario($uid),
            'temBiometria' => (bool) $this->rh_colaboradores_model->temBiometria($c->id),
            'config' => [
                'geofenceObrigatorio' => $this->configInt('rh_geofence_obrigatorio') === 1,
                'faceObrigatorio' => $this->configInt('rh_face_obrigatorio') === 1,
                'faceScoreMinimo' => (float) ($this->configValor('rh_face_score_minimo') ?: 0.55),
            ],
        ], 'Registro de ponto');
    }

    /** POST /api/v1/ponto { tipo, foto?, latitude?, longitude?, unidade_id?, os_id?, obra_id?, face_score? } */
    public function registrar_post()
    {
        $user = $this->exigir('baterPonto');
        $c = $this->colaborador($user);
        $uid = (int) $user->usuario->idUsuarios;
        $corpo = $this->corpo();

        $tipo = (string) ($corpo['tipo'] ?? 'entrada');
        if (! in_array($tipo, ['entrada', 'saida', 'inicio_intervalo', 'fim_intervalo'], true)) {
            $this->invalido('Tipo de batida inválido.');
        }

        $lat = $this->coordenada($corpo['latitude'] ?? null);
        $lng = $this->coordenada($corpo['longitude'] ?? null);
        $foto = dataurl_imagem($corpo['foto'] ?? '');
        $faceScore = $this->score($corpo['face_score'] ?? null);
        $unidadeId = (int) ($corpo['unidade_id'] ?? 0) ?: (int) ($c->unidade_id ?? 0);

        // Vínculo com OS (atendimento em campo): a OS tem de ser do usuário.
        $os = null;
        if (! empty($corpo['os_id'])) {
            $os = $this->osDoUsuario($uid, (int) $corpo['os_id']);
            if (! $os) {
                $this->invalido('OS inválida ou não atribuída a você.');
            }
            $unidadeId = 0;
        }

        // Vínculo com obra (canteiro), só quando não houver OS.
        $obra = null;
        if (! $os && ! empty($corpo['obra_id'])) {
            $obra = $this->obrasDoUsuario($uid, (int) $corpo['obra_id']);
            if (! $obra) {
                $this->invalido('Obra inválida ou não disponível.');
            }
            $unidadeId = 0;
        }

        // Em campo o local varia: o geofence fixo da unidade não bloqueia.
        $geofenceObrig = $this->configInt('rh_geofence_obrigatorio') === 1 && ! $os && ! $obra;
        $faceObrig = $this->configInt('rh_face_obrigatorio') === 1;
        $faceMin = (float) ($this->configValor('rh_face_score_minimo') ?: 0.55);

        if ($faceObrig) {
            if ($faceScore === null) {
                $this->invalido('Reconhecimento facial obrigatório. Cadastre/valide seu rosto.');
            }
            if ($faceScore < $faceMin) {
                $this->invalido('Rosto não reconhecido com segurança. Tente novamente com melhor iluminação.');
            }
        }

        $dentro = null;
        $distancia = null;

        if ($unidadeId && $lat && $lng) {
            $unidade = $this->rh_colaboradores_model->getUnidade($unidadeId);
            if ($unidade && $unidade->latitude && $unidade->longitude) {
                $distancia = (int) round($this->distanciaMetros($lat, $lng, $unidade->latitude, $unidade->longitude));
                $dentro = $distancia <= (int) $unidade->raio_metros ? 1 : 0;
                if ($geofenceObrig && ! $dentro) {
                    $this->invalido('Você está fora da área permitida (' . $distancia . 'm). Aproxime-se do local de trabalho.');
                }
            }
        } elseif ($geofenceObrig) {
            $this->invalido('Localização obrigatória. Ative o GPS e permita o acesso à localização.');
        }

        if ($os && $lat && $lng) {
            if (! empty($os->latitude) && ! empty($os->longitude)) {
                $distancia = (int) round($this->distanciaMetros($lat, $lng, $os->latitude, $os->longitude));
                $dentro = $distancia <= (int) ($this->configInt('os_geofence_raio_metros') ?: 200) ? 1 : 0;
            } else {
                $this->db->where('idOs', (int) $os->idOs)->update('os', ['latitude' => $lat, 'longitude' => $lng]);
            }
        }

        if ($obra && $lat && $lng) {
            if (! empty($obra->latitude) && ! empty($obra->longitude)) {
                $distancia = (int) round($this->distanciaMetros($lat, $lng, $obra->latitude, $obra->longitude));
                $dentro = $distancia <= 300 ? 1 : 0;
            } elseif ($this->db->field_exists('latitude', 'obras')) {
                $this->db->where('idObra', (int) $obra->idObra)->update('obras', ['latitude' => $lat, 'longitude' => $lng]);
            }
        }

        $fotoMime = $foto && preg_match('#^data:(image/[\w+.-]+);base64,#', $foto, $m) ? $m[1] : null;

        // Batida feita sem internet chega pela fila do app com a hora do aparelho
        // (aceita até 72 h atrás) e fica marcada como "app_offline" para o RH.
        $offline = ! empty($corpo['registrado_em']);
        $dados = [
            'colaborador_id' => (int) $c->id,
            'data_hora' => $this->momentoInformado($corpo['registrado_em'] ?? null),
            'tipo' => $tipo,
            'origem' => $offline ? 'app_offline' : 'api',
            'unidade_id' => $unidadeId ?: null,
            'latitude' => $lat,
            'longitude' => $lng,
            'dentro_geofence' => $dentro,
            'distancia_metros' => $distancia,
            'face_score' => $faceScore,
            'foto_base64' => $foto,
            'foto_mime' => $fotoMime,
            'ip' => $this->input->ip_address(),
            'user_agent' => substr((string) $this->input->user_agent(), 0, 255),
            'status' => 'valido',
        ];
        // Só grava os vínculos quando a migration da coluna já rodou.
        if ($os && $this->col_db('os_id', 'rh_ponto_registros')) {
            $dados['os_id'] = (int) $os->idOs;
        }
        if ($obra && $this->col_db('obra_id', 'rh_ponto_registros')) {
            $dados['obra_id'] = (int) $obra->idObra;
        }

        $registroId = $this->rh_ponto_model->registrar($dados, true);
        if (! $registroId) {
            $this->invalido('Não foi possível registrar o ponto.');
        }

        $labels = [
            'entrada' => 'Entrada',
            'saida' => 'Saída',
            'inicio_intervalo' => 'Início do intervalo',
            'fim_intervalo' => 'Fim do intervalo',
        ];
        log_info('API: ponto ' . $tipo . ' do colaborador ' . $c->id);

        $this->ok([
            'registro_id' => (int) $registroId,
            'tipo' => $tipo,
            'hora' => date('H:i'),
            'proximoTipo' => $this->rh_ponto_model->proximoTipo($c->id),
            'fora_area' => $dentro === 0,
            'distancia_metros' => $distancia,
            'os_id' => $os ? (int) $os->idOs : null,
            'obra_id' => $obra ? (int) $obra->idObra : null,
            'batidasHoje' => $this->batidas($c->id),
        ], $labels[$tipo] . ' registrada às ' . date('H:i') . '.');
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    protected function colaborador($user)
    {
        if (! $this->rh_colaboradores_model->suportado()) {
            $this->response([
                'status' => false,
                'message' => 'O módulo de RH ainda não foi ativado. Procure o RH.',
            ], REST_Controller::HTTP_FORBIDDEN);
        }
        $c = $this->rh_colaboradores_model->getByUsuario((int) $user->usuario->idUsuarios);
        if (! $c) {
            $this->response([
                'status' => false,
                'message' => 'Seu usuário não está vinculado a um cadastro de colaborador. Procure o RH.',
            ], REST_Controller::HTTP_FORBIDDEN);
        }

        return $c;
    }

    /** OS ativas do usuário; com $osId confere se ela está na lista. */
    protected function osDoUsuario($uid, $osId = null)
    {
        $this->db->select('os.idOs, os.status, os.latitude, os.longitude');
        $this->db->from('os');
        $this->db->where_in('os.status', ['Aberto', 'Em Andamento', 'Aprovado', 'Aguardando Peças', 'Orçamento']);
        if ($this->col_db('tecnico_responsavel', 'os')) {
            $this->db->group_start()
                ->where('os.tecnico_responsavel', $uid)
                ->or_where('os.usuarios_id', $uid)
                ->group_end();
        } else {
            $this->db->where('os.usuarios_id', $uid);
        }
        if ($osId) {
            $this->db->where('os.idOs', (int) $osId);

            return $this->db->get()->row() ?: null;
        }

        return $this->db->order_by('os.idOs', 'DESC')->limit(30)->get()->result();
    }

    /** Obras ativas do usuário; com $obraId confere se está na lista. */
    protected function obrasDoUsuario($uid, $obraId = null)
    {
        if (! $this->db->table_exists('obras')) {
            return $obraId ? null : [];
        }

        $this->db->select('DISTINCT obras.idObra, obras.nome, obras.status, obras.latitude, obras.longitude', false);
        $this->db->from('obras');
        $temEquipe = $this->db->table_exists('obra_equipe_alocacao') && $this->db->table_exists('equipe_membros');
        if ($temEquipe) {
            $this->db->join('obra_equipe_alocacao a', 'a.obra_id = obras.idObra', 'left');
            $this->db->join('equipe_membros em', 'em.equipe_id = a.equipe_id AND em.colaborador_id = ' . (int) $uid, 'left');
        }
        $this->db->group_start()->where('obras.responsavel_id', (int) $uid);
        if ($temEquipe) {
            $this->db->or_where('em.idMembro IS NOT NULL', null, false);
        }
        $this->db->group_end();
        $this->db->where_in('obras.status', ['planejamento', 'em_execucao', 'paralisada']);
        if ($obraId) {
            $this->db->where('obras.idObra', (int) $obraId);
        }
        $this->db->order_by('obras.nome', 'ASC');
        $lista = $this->db->get()->result();

        if ($obraId) {
            if ($lista) {
                return $lista[0];
            }
            // Sem vínculo de equipe: cai para as obras ativas gerais, como a web.
            return $this->db->select('idObra, nome, status, latitude, longitude')
                ->where('idObra', (int) $obraId)
                ->where_in('status', ['planejamento', 'em_execucao', 'paralisada'])
                ->get('obras')->row() ?: null;
        }

        return $lista;
    }

    protected function batidas($colaboradorId)
    {
        $labels = [
            'entrada' => 'Entrada',
            'saida' => 'Saída',
            'inicio_intervalo' => 'Início do intervalo',
            'fim_intervalo' => 'Fim do intervalo',
        ];

        $out = [];
        foreach ($this->rh_ponto_model->getDoDia($colaboradorId) as $b) {
            $out[] = [
                'id' => (int) $b->id,
                'tipo' => $b->tipo,
                'label' => $labels[$b->tipo] ?? $b->tipo,
                'hora' => date('H:i', strtotime($b->data_hora)),
                'data_hora' => $b->data_hora,
                'latitude' => $b->latitude ?? null,
                'longitude' => $b->longitude ?? null,
                'os_id' => $b->os_id ?? null,
                'obra_id' => $b->obra_id ?? null,
                'dentro_geofence' => $b->dentro_geofence ?? null,
                'distancia_metros' => $b->distancia_metros ?? null,
            ];
        }

        return $out;
    }

    /** Score facial entre 0 e 1, ou null. */
    protected function score($valor)
    {
        if ($valor === null || $valor === '' || ! is_numeric($valor)) {
            return null;
        }
        $n = (float) $valor;

        return ($n < 0 || $n > 1) ? null : $n;
    }

    protected function col_db($coluna, $tabela)
    {
        return $this->db->field_exists($coluna, $tabela);
    }

    protected function configValor($chave)
    {
        $row = $this->db->select('valor')->get_where('configuracoes', ['config' => $chave])->row();

        return $row->valor ?? null;
    }

    protected function configInt($chave)
    {
        return (int) ($this->configValor($chave) ?? 0);
    }
}
