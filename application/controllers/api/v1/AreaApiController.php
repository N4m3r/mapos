<?php

defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

/**
 * Base dos controllers das áreas do Técnico e do Colaborador.
 *
 * Concentra o que as duas áreas repetem: exigir permissão do grupo do usuário
 * do token, restringir a consulta ao próprio dono (IDOR), formatar OS/cliente e
 * servir arquivos em base64 (foto, assinatura, holerite) com o MIME correto.
 *
 * Regra de ouro: nada aqui confia em id vindo do cliente sem conferir contra o
 * usuário/colaborador do token.
 */
abstract class AreaApiController extends REST_Controller
{
    /** @var object usuário autenticado (logged_user()) */
    protected $user;

    // ------------------------------------------------------------------
    // Autorização
    // ------------------------------------------------------------------

    /**
     * Valida o token e exige uma permissão do grupo do usuário.
     * Responde 401/403 e encerra quando não autorizado.
     */
    protected function exigir($permissao)
    {
        $user = $this->logged_user();

        if (! $this->permission->checkPermission($user->level, $permissao)) {
            $this->response([
                'status' => false,
                'message' => 'Você não tem permissão para acessar esta área.',
                'permissao' => $permissao,
            ], REST_Controller::HTTP_FORBIDDEN);
        }

        $this->user = $user;

        return $user;
    }

    /** 404 com a mesma forma de envelope das demais respostas. */
    protected function naoEncontrada($mensagem)
    {
        $this->response([
            'status' => false,
            'message' => $mensagem,
        ], REST_Controller::HTTP_NOT_FOUND);
    }

    /** 400 para entrada inválida. */
    protected function invalido($mensagem)
    {
        $this->response([
            'status' => false,
            'message' => $mensagem,
        ], REST_Controller::HTTP_BAD_REQUEST);
    }

    /** Resposta de sucesso padrão. */
    protected function ok($result, $mensagem = 'OK')
    {
        $this->response([
            'status' => true,
            'message' => $mensagem,
            'result' => $result,
        ], REST_Controller::HTTP_OK);
    }

    /** Colaborador de RH vinculado ao usuário do token (ou null). */
    protected function colaboradorLogado()
    {
        $this->load->model('rh_colaboradores_model');

        return $this->rh_colaboradores_model->getByUsuario((int) $this->user->usuario->idUsuarios);
    }

    /** Confere se uma coluna existe para não quebrar em instalações antigas. */
    protected function col($tabela, $coluna)
    {
        return $this->db->field_exists($coluna, $tabela);
    }

    // ------------------------------------------------------------------
    // Formatação: OS e cliente
    // ------------------------------------------------------------------

    /** Resumo de OS usado na agenda, na lista e na próxima visita. */
    protected function osResumo($os)
    {
        $efetiva = TecnicoInicio::dataEfetiva($os);
        $hoje = date('Y-m-d');
        $rotulo = TecnicoInicio::rotuloOs($os, $hoje);

        return [
            'idOs' => (int) $os->idOs,
            'status' => $os->status,
            'nomeCliente' => $os->nomeCliente ?? null,
            'telefone' => $os->telefone ?? null,
            'celular' => $os->celular ?? null,
            'endereco' => TecnicoInicio::enderecoCurto($os),
            'rua' => $os->rua ?? null,
            'numero' => $os->numero ?? null,
            'bairro' => $os->bairro ?? null,
            'cidade' => $os->cidade ?? null,
            'estado' => $os->estado ?? null,
            'dataInicial' => $os->dataInicial ?? null,
            'data_agendamento' => $os->data_agendamento ?? null,
            'dataEfetiva' => $efetiva,
            'hora' => TecnicoInicio::horaVisita($os),
            'descricaoProduto' => $os->descricaoProduto ?? null,
            'defeito' => $os->defeito ?? null,
            'tipo_servico' => $os->tipo_servico ?? null,
            'prioridade' => $os->prioridade ?? null,
            'nao_programada' => $os->nao_programada ?? null,
            'urlRota' => TecnicoInicio::urlRota($os),
            'urlWhats' => TecnicoInicio::foneWhats($os->celular ?? ($os->telefone ?? '')),
            'aba' => TecnicoInicio::classificar($os, $hoje),
            'rotuloStatus' => $rotulo[0],
            'statusClasse' => $rotulo[1],
        ];
    }

    /** Cliente completo, sem dados internos de faturamento. */
    protected function cliente($c)
    {
        return [
            'idClientes' => (int) $c->idClientes,
            'nomeCliente' => $c->nomeCliente,
            'documento' => $c->documento ?? null,
            'telefone' => $c->telefone ?? null,
            'celular' => $c->celular ?? null,
            'email' => $c->email ?? null,
            'rua' => $c->rua ?? null,
            'numero' => $c->numero ?? null,
            'complemento' => $c->complemento ?? null,
            'bairro' => $c->bairro ?? null,
            'cidade' => $c->cidade ?? null,
            'estado' => $c->estado ?? null,
            'cep' => $c->cep ?? null,
        ];
    }

    /** Serviços previstos na OS — sem preço (o técnico não vê valores). */
    protected function servicosPrevistos($osId)
    {
        $this->load->model('os_model');
        $out = [];
        foreach ((array) $this->os_model->getServicos($osId) as $s) {
            $out[] = ['nome' => (string) ($s->nome ?? ''), 'quantidade' => $s->quantidade ?? 1];
        }

        return $out;
    }

    /** Produtos previstos na OS — sem preço. */
    protected function produtosPrevistos($osId)
    {
        $this->load->model('os_model');
        $out = [];
        foreach ((array) $this->os_model->getProdutos($osId) as $p) {
            $out[] = ['nome' => (string) ($p->descricao ?? ''), 'quantidade' => $p->quantidade ?? 1];
        }

        return $out;
    }

    /** Últimas 3 OS do cliente (histórico leve, sem valores). */
    protected function historicoCliente($clienteId, $excetoOsId)
    {
        $rows = $this->db
            ->select('idOs, dataInicial, status, defeito')
            ->from('os')
            ->where('clientes_id', $clienteId)
            ->where('idOs !=', (int) $excetoOsId)
            ->order_by('idOs', 'DESC')
            ->limit(3)
            ->get()->result();

        return array_map(static function ($r) {
            return [
                'idOs' => (int) $r->idOs,
                'dataInicial' => $r->dataInicial,
                'status' => $r->status,
                'defeito' => $r->defeito,
            ];
        }, $rows);
    }

    // ------------------------------------------------------------------
    // Observações e fotos do atendimento
    // ------------------------------------------------------------------

    protected function observacoes($osId)
    {
        if (! $this->os_observacoes_tecnico_model->disponivel()) {
            return [];
        }
        $out = [];
        foreach ($this->os_observacoes_tecnico_model->listarPorOs($osId) as $o) {
            $out[] = [
                'id' => (int) $o->id,
                'texto' => $o->texto,
                'data_hora' => $o->data_hora,
                'hora' => date('d/m/Y H:i', strtotime($o->data_hora)),
                'autor' => $o->nome_usuario ?: 'Técnico',
            ];
        }

        return $out;
    }

    /** Fotos do atendimento; a imagem é servida por endpoint próprio. */
    protected function fotos($osId)
    {
        $out = [];
        foreach ((array) $this->fotosatendimento_model->getByOs($osId) as $f) {
            $out[] = [
                'id' => (int) $f->idFoto,
                'etapa' => $f->etapa ?? null,
                'descricao' => $f->descricao ?? null,
                'data_hora' => $f->data_upload ?? null,
                'url' => 'tecnico/foto/' . (int) $f->idFoto,
            ];
        }

        return $out;
    }

    // ------------------------------------------------------------------
    // Não realizado
    // ------------------------------------------------------------------

    protected function naoRealizadas($uid)
    {
        if (! $this->naorealizada_model->disponivel()) {
            return [];
        }
        $out = [];
        foreach ($this->naorealizada_model->getPendentes($uid) as $o) {
            $out[] = [
                'id' => (int) $o->idOcorrencia,
                'os_id' => (int) $o->os_id,
                'idOs' => (int) $o->os_id,
                'nomeCliente' => $o->nomeCliente ?? null,
                'telefone' => $o->telefone ?? null,
                'celular' => $o->celular ?? null,
                'motivo' => $o->motivo_texto ?? null,
                'observacao' => $o->observacao ?? null,
                'data_registro' => $o->data_registro ?? null,
                'status_anterior' => $o->status_anterior ?? null,
                'descricaoProduto' => $o->descricaoProduto ?? null,
                'dataInicial' => $o->dataInicial ?? null,
            ];
        }

        return $out;
    }

    protected function motivos()
    {
        if (! $this->naorealizada_model->disponivel()) {
            return [];
        }
        $out = [];
        foreach ($this->naorealizada_model->getMotivos(true) as $m) {
            $out[] = ['id' => (int) $m->idMotivo, 'nome' => $m->nome];
        }

        return $out;
    }

    // ------------------------------------------------------------------
    // Arquivos em base64
    // ------------------------------------------------------------------

    /**
     * Serve um data URI / base64 cru como binário.
     * Recebe [mime, dados] já separados ou o valor completo no primeiro campo.
     */
    protected function servirBase64($base64, $mimeFallback = null, $forcarImagem = false)
    {
        if (empty($base64)) {
            $this->naoEncontrada('Arquivo não encontrado.');
        }

        $mime = $mimeFallback ?: ($forcarImagem ? 'image/jpeg' : 'application/octet-stream');
        $dados = $base64;
        if (preg_match('#^data:([\w/+.-]+);base64,#', $base64, $m)) {
            $mime = $m[1];
            $dados = substr($base64, strlen($m[0]));
        }

        if ($forcarImagem && ! in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            $mime = 'image/jpeg';
        }

        $bin = base64_decode($dados, true);
        if ($bin === false) {
            $this->naoEncontrada('Arquivo corrompido.');
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strlen($bin));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=3600');
        echo $bin;
        exit;
    }

    // ------------------------------------------------------------------
    // OS do técnico (IDOR) e entrada de campo
    // ------------------------------------------------------------------

    /**
     * Abre a OS só se estiver designada ao técnico ou vinculada a um projeto
     * que ele executa. Qualquer id fora disso não existe para quem pediu.
     */
    protected function osDoTecnico($osId, $uid)
    {
        $os = $osId ? $this->db->where('idOs', (int) $osId)->get('os')->row() : null;
        if (! $os) {
            return null;
        }
        if ((int) $os->tecnico_responsavel === (int) $uid) {
            return $os;
        }

        $this->load->model('obras_model');
        if ($this->obras_model->osEmProjetoDoUsuario((int) $osId, (int) $uid)) {
            return $os;
        }

        return null;
    }

    /** [modo, raio_metros] do geofence de atendimento, como o Checkin web lê. */
    protected function geoConfig()
    {
        $config = [];
        foreach ((array) $this->db->get('configuracoes')->result() as $c) {
            $config[$c->config] = $c->valor;
        }

        $modo = $config['os_geofence_modo'] ?? 'off';
        if (! in_array($modo, ['off', 'soft', 'hard'], true)) {
            $modo = 'off';
        }
        $raio = (int) ($config['os_geofence_raio_metros'] ?? 200);

        return [$modo, $raio > 0 ? $raio : 200];
    }

    /** Coordenadas cadastradas da OS (null quando não há). */
    protected function coordsOs($osId)
    {
        foreach (['latitude', 'longitude'] as $col) {
            if (! $this->db->field_exists($col, 'os')) {
                return null;
            }
        }
        $row = $this->db->select('latitude, longitude')->get_where('os', ['idOs' => (int) $osId])->row();
        if (! $row || $row->latitude === null || $row->latitude === '' || $row->longitude === null || $row->longitude === '') {
            return null;
        }

        return $row;
    }

    /** Distância em metros entre dois pontos (Haversine). */
    protected function distanciaMetros($lat1, $lon1, $lat2, $lon2)
    {
        $r = 6371000;
        $dLat = deg2rad((float) $lat2 - (float) $lat1);
        $dLon = deg2rad((float) $lon2 - (float) $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad((float) $lat1)) * cos(deg2rad((float) $lat2)) * sin($dLon / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Colunas de geofence do check-in (chegada). Nunca bloqueia: a chegada
     * inicia o atendimento e, na primeira com GPS, ensina o local da OS.
     */
    protected function geofenceEntrada($osId, $lat, $lng)
    {
        if (! $this->db->field_exists('dentro_geofence_entrada', 'os_checkin')) {
            return [];
        }
        $out = ['distancia_entrada_metros' => null, 'dentro_geofence_entrada' => null];
        if (! $lat || ! $lng) {
            return $out;
        }

        $coords = $this->coordsOs($osId);
        if (! $coords) {
            $this->db->where('idOs', (int) $osId)->update('os', ['latitude' => $lat, 'longitude' => $lng]);
            $out['distancia_entrada_metros'] = 0;
            $out['dentro_geofence_entrada'] = 1;

            return $out;
        }

        [$modo, $raio] = $this->geoConfig();
        $dist = (int) round($this->distanciaMetros($lat, $lng, $coords->latitude, $coords->longitude));
        $out['distancia_entrada_metros'] = $dist;
        $out['dentro_geofence_entrada'] = $dist <= $raio ? 1 : 0;

        return $out;
    }

    /** Avaliação do geofence na saída; o bloqueio só acontece em modo 'hard'. */
    protected function geofenceSaida($osId, $lat, $lng)
    {
        [$modo, $raio] = $this->geoConfig();
        $temGps = ($lat && $lng);
        $coords = $this->coordsOs($osId);

        $dist = null;
        $dentro = null;
        if ($temGps && $coords) {
            $dist = (int) round($this->distanciaMetros($lat, $lng, $coords->latitude, $coords->longitude));
            $dentro = $dist <= $raio ? 1 : 0;
        }

        return [
            'modo' => $modo,
            'raio' => $raio,
            'temGps' => (bool) $temGps,
            'temCoords' => (bool) $coords,
            'distancia' => $dist,
            'dentro' => $dentro,
        ];
    }

    /** Latitude/longitude válidas ou null (nunca confia no JSON do app). */
    protected function coordenada($valor)
    {
        if ($valor === null || $valor === '' || ! is_numeric($valor)) {
            return null;
        }
        $n = (float) $valor;
        if (abs($n) > 180 || $n == 0.0) {
            return null;
        }

        return $n;
    }

    /**
     * Momento informado pelo app (fila offline). Vale das últimas 72h até 5
     * minutos no futuro; fora disso, agora.
     */
    protected function momentoInformado($registradoEm)
    {
        $ts = $registradoEm ? strtotime((string) $registradoEm) : false;
        if ($ts && $ts >= time() - 72 * 3600 && $ts <= time() + 300) {
            return date('Y-m-d H:i:s', min($ts, time()));
        }

        return date('Y-m-d H:i:s');
    }

    /** Corta um texto no limite da coluna, preservando UTF-8. */
    protected function cortar($texto, $max)
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return null;
        }

        return function_exists('mb_substr') ? mb_substr($texto, 0, (int) $max) : substr($texto, 0, (int) $max);
    }

    /** Corpo JSON da requisição como array (POST/PUT dos controllers REST). */
    protected function corpo()
    {
        $raw = $this->input->raw_input_stream;
        if ($raw === '' || $raw === null) {
            return (array) $this->post();
        }
        $json = json_decode($raw, true);

        return is_array($json) ? $json : (array) $this->post();
    }
}
