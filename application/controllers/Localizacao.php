<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Mapa de despacho — localização em tempo real dos técnicos em campo.
 *
 * Consome os pings gravados por Tecnico::registrar_localizacao. A tela usa
 * Leaflet + OpenStreetMap e atualiza as posições por polling (AJAX).
 */
class Localizacao extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('localizacao_model');
        $this->load->model('mapos_model');
    }

    /**
     * Tela do mapa (protegida por vTecnicoMapa).
     */
    public function mapa()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vTecnicoMapa')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para ver o mapa dos técnicos.');
            redirect(base_url());
        }

        $this->data['emitente'] = $this->mapos_model->getEmitente();
        $this->data['titulo'] = 'Mapa dos Técnicos';
        $this->data['view'] = 'localizacao/mapa';

        return $this->layout();
    }

    /**
     * Última posição de cada técnico ativo (JSON) — consumido pelo polling.
     */
    public function tecnicos_ativos()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vTecnicoMapa')) {
            $this->output->set_status_header(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            return;
        }

        // Janela de atividade configurável (padrão 10 min).
        $minutos = (int) ($this->input->get('minutos') ?: 10);
        if ($minutos < 1) {
            $minutos = 10;
        }

        $registros = $this->localizacao_model->getUltimasPorTecnico($minutos);

        $tecnicos = [];
        foreach ($registros as $r) {
            $tecnicos[] = [
                'usuarios_id' => (int) $r->usuarios_id,
                'nome'        => $r->nome_tecnico,
                'latitude'    => (float) $r->latitude,
                'longitude'   => (float) $r->longitude,
                'precisao'    => $r->precisao !== null ? (float) $r->precisao : null,
                'os_id'       => $r->idOs ? (int) $r->idOs : null,
                'os_status'   => $r->os_status,
                'cliente'     => $r->nomeCliente,
                'data_hora'   => $r->data_hora,
                'ha_minutos'  => $r->data_hora ? floor((time() - strtotime($r->data_hora)) / 60) : null,
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success'  => true,
                'servidor' => date('Y-m-d H:i:s'),
                'tecnicos' => $tecnicos,
            ]));
    }

    /**
     * Tela de histórico de percurso: seleciona técnico + período e desenha o
     * trajeto percorrido no mapa. Protegida por vTecnicoMapa.
     */
    public function trajeto()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vTecnicoMapa')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para ver o percurso dos técnicos.');
            redirect(base_url());
        }

        $this->data['tecnicos'] = $this->localizacao_model->getTecnicosComRegistro();
        $this->data['emitente'] = $this->mapos_model->getEmitente();
        $this->data['titulo'] = 'Percurso do Técnico';
        $this->data['view'] = 'localizacao/trajeto';

        return $this->layout();
    }

    /**
     * Dados do percurso (JSON) — pings de um técnico no período, agrupados por
     * atendimento (checkin_id). Consumido pela tela de percurso.
     */
    public function trajeto_dados()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vTecnicoMapa')) {
            $this->output->set_status_header(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            return;
        }

        $usuario_id = (int) $this->input->get('usuario_id');
        $data       = $this->input->get('data');       // Y-m-d
        $data_fim   = $this->input->get('data_fim');    // Y-m-d (opcional)
        $os_id      = (int) $this->input->get('os_id'); // opcional

        if (!$usuario_id || !$data) {
            echo json_encode(['success' => false, 'message' => 'Informe o técnico e a data']);
            return;
        }

        if (!$data_fim) {
            $data_fim = $data;
        }

        $inicio = $data . ' 00:00:00';
        $fim    = $data_fim . ' 23:59:59';

        $registros = $this->localizacao_model->getTrajetoPorPeriodo($usuario_id, $inicio, $fim, $os_id ?: null);

        // Agrupa por atendimento (checkin_id). Pings sem checkin caem em '0'.
        $segmentos = [];
        foreach ($registros as $r) {
            $chave = $r->checkin_id ? (int) $r->checkin_id : 0;
            if (!isset($segmentos[$chave])) {
                $segmentos[$chave] = [
                    'checkin_id' => $chave ?: null,
                    'os_id'      => $r->os_id ? (int) $r->os_id : null,
                    'os_status'  => $r->os_status,
                    'cliente'    => $r->nomeCliente,
                    'pontos'     => [],
                ];
            }
            $segmentos[$chave]['pontos'][] = [
                'latitude'  => (float) $r->latitude,
                'longitude' => (float) $r->longitude,
                'precisao'  => $r->precisao !== null ? (float) $r->precisao : null,
                'data_hora' => $r->data_hora,
            ];
        }

        // Métricas por segmento: distância (haversine) e duração.
        $segmentos = array_values($segmentos);
        $total_pontos = 0;
        $distancia_total = 0.0;
        foreach ($segmentos as &$seg) {
            $dist = 0.0;
            $pts = $seg['pontos'];
            for ($i = 1; $i < count($pts); $i++) {
                $dist += $this->haversine(
                    $pts[$i - 1]['latitude'],
                    $pts[$i - 1]['longitude'],
                    $pts[$i]['latitude'],
                    $pts[$i]['longitude']
                );
            }
            $seg['distancia_m'] = round($dist);
            $seg['inicio'] = $pts ? $pts[0]['data_hora'] : null;
            $seg['fim'] = $pts ? $pts[count($pts) - 1]['data_hora'] : null;
            $seg['total_pontos'] = count($pts);
            $total_pontos += count($pts);
            $distancia_total += $dist;
        }
        unset($seg);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success'         => true,
                'segmentos'       => $segmentos,
                'total_pontos'    => $total_pontos,
                'distancia_total' => round($distancia_total),
            ]));
    }

    /**
     * Exporta o percurso (técnico + período, opcionalmente uma OS) como GPX ou
     * KML para abrir em Google Earth, Maps.me, GPS Visualizer etc.
     *
     * GET: usuario_id, data, data_fim (opc), os_id (opc), formato (gpx|kml).
     */
    public function exportar_trajeto()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vTecnicoMapa')) {
            $this->output->set_status_header(403);
            echo 'Sem permissão';
            return;
        }

        $usuario_id = (int) $this->input->get('usuario_id');
        $data       = $this->input->get('data');
        $data_fim   = $this->input->get('data_fim');
        $os_id      = (int) $this->input->get('os_id');
        $formato    = strtolower($this->input->get('formato')) === 'kml' ? 'kml' : 'gpx';

        if (!$usuario_id || !$data) {
            $this->output->set_status_header(400);
            echo 'Informe o técnico e a data';
            return;
        }

        if (!$data_fim) {
            $data_fim = $data;
        }

        $registros = $this->localizacao_model->getTrajetoPorPeriodo(
            $usuario_id,
            $data . ' 00:00:00',
            $data_fim . ' 23:59:59',
            $os_id ?: null
        );

        // Agrupa por atendimento para gerar um segmento/linha por check-in.
        $segmentos = [];
        foreach ($registros as $r) {
            $chave = $r->checkin_id ? (int) $r->checkin_id : 0;
            if (!isset($segmentos[$chave])) {
                $segmentos[$chave] = [
                    'os_id'   => $r->os_id ? (int) $r->os_id : null,
                    'cliente' => $r->nomeCliente,
                    'pontos'  => [],
                ];
            }
            $segmentos[$chave]['pontos'][] = $r;
        }
        $segmentos = array_values($segmentos);

        $nome_tecnico = 'tecnico';
        $this->db->select('nome')->from('usuarios')->where('idUsuarios', $usuario_id)->limit(1);
        $u = $this->db->get()->row();
        if ($u) {
            $nome_tecnico = $u->nome;
        }

        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($this->removerAcentos($nome_tecnico)));
        $slug = trim($slug, '-') ?: 'tecnico';
        $arquivo = 'percurso-' . $slug . '-' . $data . ($data_fim !== $data ? '_a_' . $data_fim : '') . '.' . $formato;

        if ($formato === 'kml') {
            $conteudo = $this->gerarKml($segmentos, $nome_tecnico);
            $mime = 'application/vnd.google-earth.kml+xml';
        } else {
            $conteudo = $this->gerarGpx($segmentos, $nome_tecnico);
            $mime = 'application/gpx+xml';
        }

        $this->output
            ->set_content_type($mime)
            ->set_header('Content-Disposition: attachment; filename="' . $arquivo . '"')
            ->set_output($conteudo);
    }

    /**
     * Monta o GPX (um <trk> por atendimento, com <trkpt> por ping).
     */
    private function gerarGpx($segmentos, $nome_tecnico)
    {
        $x = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $x .= '<gpx version="1.1" creator="Mapos" xmlns="http://www.topografix.com/GPX/1/1">' . "\n";
        $x .= '  <metadata><name>' . $this->xmlEsc('Percurso — ' . $nome_tecnico) . '</name></metadata>' . "\n";
        foreach ($segmentos as $seg) {
            $nome = $seg['os_id'] ? ('OS #' . sprintf('%04d', $seg['os_id'])) : 'Atendimento';
            if ($seg['cliente']) {
                $nome .= ' - ' . $seg['cliente'];
            }
            $x .= '  <trk><name>' . $this->xmlEsc($nome) . '</name><trkseg>' . "\n";
            foreach ($seg['pontos'] as $p) {
                $x .= '    <trkpt lat="' . $p->latitude . '" lon="' . $p->longitude . '">'
                    . '<time>' . $this->isoTime($p->data_hora) . '</time></trkpt>' . "\n";
            }
            $x .= '  </trkseg></trk>' . "\n";
        }
        $x .= '</gpx>' . "\n";

        return $x;
    }

    /**
     * Monta o KML (um <Placemark>/<LineString> por atendimento).
     */
    private function gerarKml($segmentos, $nome_tecnico)
    {
        $x = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $x .= '<kml xmlns="http://www.opengis.net/kml/2.2"><Document>' . "\n";
        $x .= '  <name>' . $this->xmlEsc('Percurso — ' . $nome_tecnico) . '</name>' . "\n";
        foreach ($segmentos as $seg) {
            $nome = $seg['os_id'] ? ('OS #' . sprintf('%04d', $seg['os_id'])) : 'Atendimento';
            if ($seg['cliente']) {
                $nome .= ' - ' . $seg['cliente'];
            }
            $coords = '';
            foreach ($seg['pontos'] as $p) {
                $coords .= $p->longitude . ',' . $p->latitude . ',0 ';
            }
            $x .= '  <Placemark><name>' . $this->xmlEsc($nome) . '</name>'
                . '<LineString><tessellate>1</tessellate><coordinates>' . trim($coords)
                . '</coordinates></LineString></Placemark>' . "\n";
        }
        $x .= '</Document></kml>' . "\n";

        return $x;
    }

    private function isoTime($dt)
    {
        return $dt ? date('c', strtotime($dt)) : '';
    }

    private function xmlEsc($s)
    {
        return htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function removerAcentos($s)
    {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);

        return $t !== false ? $t : $s;
    }

    /**
     * Distância em metros entre dois pontos (fórmula de Haversine).
     */
    private function haversine($lat1, $lon1, $lat2, $lon2)
    {
        $r = 6371000; // raio da Terra em metros
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $r * $c;
    }
}
