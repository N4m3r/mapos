<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Redirecionador do encurtador de links (rota pública `/l/<slug>`).
 *
 * Estende CI_Controller (sem login): o link curto precisa abrir para qualquer
 * pessoa que o recebeu. A segurança fica no destino (tokens aleatórios do
 * aceite/RDO). Slug inexistente ou expirado → 404 amigável.
 */
class L extends CI_Controller
{
    public function index($slug = null)
    {
        $this->load->library('encurtador');
        $url = $this->encurtador->resolver($slug);

        if (empty($url)) {
            show_404();

            return;
        }

        redirect($url);
    }
}
