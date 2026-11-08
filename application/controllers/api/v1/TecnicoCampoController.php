<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API v1 — Funções de campo do técnico que o app ainda não tinha e a Área do
 * Técnico web já oferece: meu relatório completo, "Estou a caminho" (aviso ao
 * cliente por WhatsApp), posição para o mapa em tempo real e assinatura do
 * solicitante (no aparelho, por link ou por e-mail).
 *
 * Mesmas regras do controller web Tecnico: exige vTecnicoDashboard e só
 * aceita OS designada ao técnico do token.
 */
require_once __DIR__ . '/AreaApiController.php';

class TecnicoCampoController extends AreaApiController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('tecnico_model');
        $this->load->model('checkin_model');
        $this->load->model('localizacao_model');
        $this->load->model('assinaturas_model');
        $this->load->model('mapos_model');
        $this->load->helper('tecnico_inicio');
    }

    // ------------------------------------------------------------------
    // Meu relatório
    // ------------------------------------------------------------------

    /** GET /api/v1/tecnico/relatorio?tipo=diario|semanal|mensal&data=AAAA-MM-DD */
    public function relatorio_get()
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;
        $this->load->model('projeto_acompanhamento_model');

        $tipo = (string) $this->get('tipo');
        if (! in_array($tipo, ['diario', 'semanal', 'mensal'], true)) {
            $tipo = 'diario';
        }
        $dataRef = (string) $this->get('data');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataRef) || ! strtotime($dataRef)) {
            $dataRef = date('Y-m-d');
        }

        $periodo = Projeto_acompanhamento_model::periodo($tipo, $dataRef);
        $rel = $this->projeto_acompanhamento_model->relatorio($periodo['inicio'], $periodo['fim'], ['usuario_id' => $uid]);

        $projetos = [];
        foreach ((array) ($rel['projetos'] ?? []) as $pj) {
            $o = $pj['obra'] ?? [];
            $projetos[] = [
                'idObra' => (int) ($o['idObra'] ?? 0),
                'nome' => (string) ($o['nome'] ?? ''),
                'cliente' => (string) ($o['cliente'] ?? ''),
                'percentual_concluido' => (float) ($o['percentual_concluido'] ?? 0),
                'registros' => array_map(static function ($r) {
                    return [
                        'data' => $r['data'] ?? null,
                        'atividades' => (string) ($r['atividades'] ?? ''),
                        'ocorrencias' => (string) ($r['ocorrencias'] ?? ''),
                    ];
                }, (array) ($pj['registros'] ?? [])),
                'avancos' => array_map(static function ($av) {
                    return [
                        'data' => $av['data'] ?? null,
                        'evento' => $av['evento'] ?? 'status',
                        'identificacao' => (string) ($av['identificacao'] ?? ''),
                        'local' => (string) ($av['local'] ?? ''),
                        'para_rotulo' => (string) ($av['para_rotulo'] ?? ''),
                        'destinatario' => (string) ($av['destinatario'] ?? ''),
                        'metragem_cabo' => (float) ($av['metragem_cabo'] ?? 0),
                    ];
                }, (array) ($pj['avancos'] ?? [])),
            ];
        }

        $this->ok([
            'tipo' => $periodo['tipo'],
            'periodo' => [
                'inicio' => $periodo['inicio'],
                'fim' => $periodo['fim'],
                'rotulo' => $periodo['rotulo'] ?? null,
                'anterior' => $periodo['anterior'] ?? null,
                'proximo' => $periodo['proximo'] ?? null,
            ],
            'totais' => $rel['totais'] ?? [],
            'projetos' => $projetos,
        ], 'Meu relatório');
    }

    // ------------------------------------------------------------------
    // Estou a caminho
    // ------------------------------------------------------------------

    /** POST /api/v1/tecnico/a-caminho { os_id } — avisa o cliente por WhatsApp. */
    public function a_caminho_post()
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;
        $osId = (int) ($this->corpo()['os_id'] ?? 0);

        $os = $this->osDoTecnico($osId, $uid);
        if (! $os) {
            $this->naoEncontrada('OS não encontrada ou não designada a você.');
        }
        if (in_array($os->status, ['Finalizado', 'Faturado', 'Cancelado'], true)) {
            $this->invalido('Esta OS já está ' . $os->status . '.');
        }
        if ($this->checkin_model->getCheckinAtivo($osId)) {
            $this->invalido('Você já chegou ao local.');
        }

        try {
            $this->load->library('notificador');
            $r = $this->notificador->avisarAtendimento($osId, 'os_tecnico_a_caminho');
        } catch (\Throwable $e) {
            log_message('error', 'Aviso a caminho da OS #' . $osId . ' falhou: ' . $e->getMessage());
            $r = ['status' => 'falha', 'quando' => null];
        }

        $hora = ! empty($r['quando']) ? date('H:i', $r['quando']) : date('H:i');
        switch ($r['status'] ?? 'falha') {
            case 'enviado':
                log_info('Técnico avisou pelo app que está a caminho. OS ID: ' . $osId);
                $this->ok(['hora' => $hora, 'repetido' => false], 'Cliente avisado às ' . $hora);
                // no break
            case 'repetido':
                $this->ok(['hora' => $hora, 'repetido' => true], 'O cliente já foi avisado às ' . $hora);
                // no break
            case 'sem_numero':
                $this->invalido('O cliente não tem WhatsApp cadastrado — ele não foi avisado.');
                // no break
            case 'inativo':
            case 'sem_gatilho':
                $this->invalido('O aviso por WhatsApp não está ativo — o cliente não foi avisado.');
                // no break
            default:
                $this->invalido('Não foi possível avisar o cliente agora. Tente de novo.');
        }
    }

    // ------------------------------------------------------------------
    // Posição para o mapa em tempo real
    // ------------------------------------------------------------------

    /** POST /api/v1/tecnico/posicao { latitude, longitude, precisao?, velocidade?, bateria? } */
    public function posicao_post()
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;
        $corpo = $this->corpo();

        $lat = $corpo['latitude'] ?? null;
        $lng = $corpo['longitude'] ?? null;
        if (! is_numeric($lat) || ! is_numeric($lng) || abs((float) $lat) > 90 || abs((float) $lng) > 180) {
            $this->invalido('Coordenadas inválidas.');
        }

        // Vincula ao atendimento em andamento do técnico, como o ping web.
        $checkin = null;
        if ($this->db->table_exists('os_checkin')) {
            $checkin = $this->db->where('usuarios_id', $uid)
                ->where('data_saida IS NULL')
                ->where('data_entrada >', date('Y-m-d H:i:s', strtotime('-24 hours')))
                ->order_by('idCheckin', 'DESC')->limit(1)->get('os_checkin')->row();
        }

        $num = static function ($v) {
            return is_numeric($v) ? $v : null;
        };
        $bateria = $num($corpo['bateria'] ?? null);

        $id = $this->localizacao_model->registrarPing([
            'usuarios_id' => $uid,
            'os_id' => $checkin ? $checkin->os_id : null,
            'checkin_id' => $checkin ? $checkin->idCheckin : null,
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
            'precisao' => $num($corpo['precisao'] ?? null),
            'velocidade' => $num($corpo['velocidade'] ?? null),
            'bateria' => $bateria !== null ? max(0, min(100, (int) $bateria)) : null,
            'data_hora' => date('Y-m-d H:i:s'),
        ]);

        $this->ok(['registrado' => (bool) $id, 'em_atendimento' => (bool) $checkin], 'Posição recebida');
    }

    // ------------------------------------------------------------------
    // Assinatura do solicitante
    // ------------------------------------------------------------------

    /** GET /api/v1/tecnico/os/{id}/assinatura-solicitante — situação do link e assinaturas. */
    public function assinatura_solicitante_get($osId = null)
    {
        $os = $this->osDesignada($osId);
        $this->load->model('aceite_model');

        $assinaturas = [];
        foreach ((array) $this->assinaturas_model->getByOs((int) $os->idOs) as $a) {
            $assinaturas[] = [
                'tipo' => $a->tipo ?? null,
                'nome' => $a->nome_assinante ?? null,
                'data' => $a->data_assinatura ?? null,
            ];
        }
        $temSolicitante = (bool) array_filter($assinaturas, static function ($a) {
            return in_array($a['tipo'], ['solicitante', 'cliente_aceite'], true);
        });

        $this->ok([
            'suportado' => (bool) $this->aceite_model->suportado(),
            'situacao' => $this->aceite_model->situacao($os),
            'link' => ! empty($os->aceite_token) ? site_url('aceite/' . $os->aceite_token) : null,
            'email_cliente' => $os->email ?? null,
            'assinado' => $temSolicitante,
            'assinaturas' => $assinaturas,
        ], 'Assinatura do solicitante');
    }

    /** POST /api/v1/tecnico/os/{id}/assinatura-solicitante { assinatura, nome?, documento? } */
    public function assinatura_solicitante_post($osId = null)
    {
        $os = $this->osDesignada($osId);
        $osId = (int) $os->idOs;
        $corpo = $this->corpo();

        $bruta = (string) ($corpo['assinatura'] ?? '');
        $base64 = (string) dataurl_imagem($bruta);
        if ($base64 === '' || strlen($base64) < 100) {
            $this->invalido(trim($bruta) === ''
                ? 'Assinatura em branco. Peça para o solicitante assinar no quadro.'
                : 'Imagem da assinatura inválida ou muito grande. Limpe o quadro e assine novamente.');
        }

        $nome = $this->cortar(strip_tags((string) ($corpo['nome'] ?? '')), 120);
        $documento = $this->cortar(strip_tags((string) ($corpo['documento'] ?? '')), 30);

        $imagem = $this->assinaturas_model->salvarImagem($base64, $osId, 'solicitante');
        if (! $imagem) {
            $this->invalido('Falha ao salvar a assinatura.');
        }
        // salvarImagem já grava no banco no modo alternativo; só o modo arquivo precisa da linha.
        if (isset($imagem['modo']) && $imagem['modo'] === 'arquivo') {
            $this->assinaturas_model->add([
                'os_id' => $osId,
                'tipo' => 'solicitante',
                'assinatura' => $imagem['path'],
                'nome_assinante' => $nome,
                'documento_assinante' => $documento,
                'data_assinatura' => date('Y-m-d H:i:s'),
                'ip_address' => $this->input->ip_address(),
            ]);
        }

        log_info('Assinatura do solicitante coletada pelo app na OS: ' . $osId);
        $this->ok(['assinado' => true], 'Assinatura registrada com sucesso!');
    }

    /** POST /api/v1/tecnico/os/{id}/link-solicitante — gera (ou regenera) o link público. */
    public function link_solicitante_post($osId = null)
    {
        $os = $this->osDesignada($osId);
        $this->load->model('aceite_model');
        if (! $this->aceite_model->suportado()) {
            $this->invalido('Recurso de link de assinatura não está disponível neste sistema.');
        }

        $res = $this->aceite_model->gerarLink((int) $os->idOs);
        if (! $res) {
            $this->invalido('Não foi possível gerar o link.');
        }

        log_info('Técnico gerou pelo app link de assinatura do solicitante para a OS: ' . $os->idOs);
        $this->ok([
            'link' => site_url('aceite/' . $res['token']),
            'expira' => date('d/m/Y', strtotime($res['expira'])),
        ], 'Link gerado com sucesso!');
    }

    /** POST /api/v1/tecnico/os/{id}/link-solicitante/email { email } */
    public function link_solicitante_email_post($osId = null)
    {
        $os = $this->osDesignada($osId);
        $osId = (int) $os->idOs;
        $email = trim((string) ($this->corpo()['email'] ?? ''));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->invalido('Informe um e-mail válido.');
        }

        $this->load->model('aceite_model');
        if (! $this->aceite_model->suportado()) {
            $this->invalido('Recurso de link de assinatura não está disponível neste sistema.');
        }

        $token = (! empty($os->aceite_token) && $this->aceite_model->situacao($os) === 'pendente')
            ? $os->aceite_token
            : null;
        if (! $token) {
            $res = $this->aceite_model->gerarLink($osId);
            if (! $res) {
                $this->invalido('Não foi possível gerar o link.');
            }
            $token = $res['token'];
        }

        $emitente = $this->mapos_model->getEmitente();
        if (! $emitente || empty($emitente->email)) {
            $this->invalido('E-mail do emitente não configurado. Configure em Emitente.');
        }

        $link = site_url('aceite/' . $token);
        $numero = sprintf('%04d', $osId);
        $html = '<p>Olá, ' . html_escape($os->nomeCliente ?: 'Cliente') . '!</p>'
            . '<p>Por favor, confirme e assine o serviço realizado (OS #' . $numero . ') no link abaixo:</p>'
            . '<p><a href="' . $link . '" style="display:inline-block;padding:12px 20px;background:#667eea;color:#fff;text-decoration:none;border-radius:6px">Abrir e assinar</a></p>'
            . '<p>Ou copie e cole no navegador:<br>' . $link . '</p>'
            . '<p>Atenciosamente,<br>' . html_escape($emitente->nome ?: 'Equipe') . '</p>';

        $this->load->model('email_model');
        $ok = $this->email_model->add('email_queue', [
            'to' => $email,
            'message' => $html,
            'status' => 'pending',
            'date' => date('Y-m-d H:i:s'),
            'headers' => serialize(['From' => $emitente->email, 'Subject' => 'Assinatura do serviço - OS #' . $numero, 'Return-Path' => '']),
        ]);
        if (! $ok) {
            $this->invalido('Falha ao enfileirar o e-mail.');
        }

        log_info('Técnico enviou pelo app link de assinatura por e-mail (' . $email . ') da OS: ' . $osId);
        $this->ok(['link' => $link], 'E-mail enfileirado para ' . $email . '. O envio ocorre automaticamente em instantes.');
    }

    /** OS designada ao técnico do token (mesma regra do web) ou 404. */
    private function osDesignada($osId)
    {
        $user = $this->exigir('vTecnicoDashboard');
        $os = (int) $osId ? $this->tecnico_model->getOsById((int) $osId, (int) $user->usuario->idUsuarios) : null;
        if (! $os) {
            $this->naoEncontrada('OS não encontrada ou não designada a você.');
        }

        return $os;
    }
}
