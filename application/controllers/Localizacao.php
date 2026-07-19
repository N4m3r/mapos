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
}
