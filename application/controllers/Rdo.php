<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Página PÚBLICA de um RDO (diário de projeto) via link temporário.
 *
 * Estende CI_Controller (sem login): quem recebe o link no grupo de WhatsApp
 * abre o "o que foi feito" + fotos sem precisar de conta. Protegido pelo token
 * aleatório na URL (dominio/rdo/<token>), com validade. Espelha o padrão do
 * controller Aceite.
 */
class Rdo extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('obras_model');
    }

    public function index($token = null)
    {
        $rdo = $this->obras_model->getRdoByToken($token);
        if (! $rdo) {
            $this->load->view('rdo/invalido');

            return;
        }

        $this->load->model('mapos_model');
        $data = [
            'rdo' => $rdo,
            'obra' => $this->obras_model->getObra($rdo->obra_id),
            'responsavel' => $rdo->responsavel ?? '',
            'fotos' => $this->obras_model->getRdoFotos($rdo->idRdo),
            'emitente' => $this->mapos_model->getEmitente(),
            'token' => $token,
        ];
        $this->load->view('rdo/publico', $data);
    }

    /**
     * Serve uma foto do RDO validando o token na URL (não expõe fotos de outros
     * RDOs). rdo/foto/<token>/<idFoto>.
     */
    public function foto($token = null, $idFoto = null)
    {
        $rdo = $this->obras_model->getRdoByToken($token);
        if (! $rdo) {
            show_404();

            return;
        }

        $this->db->where('idFoto', (int) $idFoto)->where('rdo_id', (int) $rdo->idRdo);
        $row = $this->db->get('obra_rdo_foto')->row();
        if ($row && $row->foto) {
            header('Content-Type: image/jpeg');
            echo $row->foto;

            return;
        }
        show_404();
    }
}
