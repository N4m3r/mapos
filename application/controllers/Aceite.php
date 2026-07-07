<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Página PÚBLICA de aceite do serviço REALIZADO (pós-execução).
 *
 * Estende CI_Controller (não MY_Controller) de propósito: o cliente acessa pelo
 * link temporário sem login. Protegido pelo token aleatório de 64 caracteres na
 * URL. Espelha o controller Aprovacao, mas para o aceite pós-serviço, com
 * evidências (fotos/assinaturas do atendimento) e assinatura digital do cliente.
 */
class Aceite extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->model('aceite_model');
    }

    /**
     * Exibe a OS realizada, as evidências e o formulário de aceite/assinatura.
     */
    public function index($token = null)
    {
        $os = $this->aceite_model->getByToken($token);
        $situacao = $this->aceite_model->situacao($os);

        $data = [
            'token' => $token,
            'situacao' => $situacao,
            'os' => $os,
            'erro' => $this->session->flashdata('erro'),
            'produtos' => [],
            'servicos' => [],
            'fotos_etapa' => ['entrada' => [], 'durante' => [], 'saida' => []],
            'assinaturas' => [],
            'emitente' => null,
            'totalProdutos' => 0,
            'totalServico' => 0,
            'valorDesconto' => 0,
        ];

        if ($os && $situacao !== 'invalido') {
            $this->load->model('os_model');
            $this->load->model('mapos_model');
            $this->load->model('fotosatendimento_model');
            $this->load->model('assinaturas_model');

            $data['produtos'] = $this->os_model->getProdutos($os->idOs);
            $data['servicos'] = $this->os_model->getServicos($os->idOs);
            $data['emitente'] = $this->mapos_model->getEmitente();

            $totais = $this->os_model->valorTotalOS($os->idOs);
            $data['totalProdutos'] = $totais['totalProdutos'];
            $data['totalServico'] = $totais['totalServico'];
            $data['valorDesconto'] = $totais['valor_desconto'];

            // Evidências: fotos agrupadas por etapa
            foreach ($this->fotosatendimento_model->getByOs($os->idOs) as $foto) {
                $etapa = in_array($foto->etapa, ['entrada', 'durante', 'saida'], true) ? $foto->etapa : 'durante';
                $data['fotos_etapa'][$etapa][] = $foto;
            }

            // Assinaturas de saída do técnico/cliente feitas no atendimento
            $data['assinaturas'] = $this->assinaturas_model->getByOs($os->idOs);
        }

        $this->load->view('aceite/publico', $data);
    }

    /**
     * Recebe a decisão do cliente (POST) e registra na OS, com assinatura.
     */
    public function confirmar()
    {
        $token = $this->input->post('token');
        $decisao = $this->input->post('decisao'); // 'aceito' | 'recusado'
        $nome = trim((string) $this->input->post('nome'));
        $obs = trim((string) $this->input->post('obs'));
        $assinaturaBase64 = (string) $this->input->post('assinatura');

        $os = $this->aceite_model->getByToken($token);
        $situacao = $this->aceite_model->situacao($os);

        if (! $os || $situacao !== 'pendente') {
            redirect('aceite/' . $token);
        }
        if (! in_array($decisao, ['aceito', 'recusado'], true)) {
            $this->session->set_flashdata('erro', 'Selecione aceitar ou recusar o serviço.');
            redirect('aceite/' . $token);
        }
        if ($nome === '') {
            $this->session->set_flashdata('erro', 'Por favor, informe seu nome para confirmar.');
            redirect('aceite/' . $token);
        }
        if ($decisao === 'recusado' && $obs === '') {
            $this->session->set_flashdata('erro', 'Para recusar, descreva o motivo no campo de observação.');
            redirect('aceite/' . $token);
        }

        $assinaturaId = null;
        if ($decisao === 'aceito') {
            if (empty($assinaturaBase64) || strlen($assinaturaBase64) < 100) {
                $this->session->set_flashdata('erro', 'Por favor, assine no quadro para confirmar o aceite.');
                redirect('aceite/' . $token);
            }
            $this->load->model('assinaturas_model');
            // Armazena a assinatura (base64) no banco e retorna o id (robusto em
            // hospedagem sem permissão de escrita em disco).
            $res = $this->assinaturas_model->salvarImagemBase64NoBanco($assinaturaBase64, $os->idOs, 'cliente_aceite');
            $assinaturaId = is_array($res) ? ($res['id'] ?? null) : null;
        }

        $this->aceite_model->registrarDecisao(
            $os->idOs,
            $decisao,
            $nome,
            $obs,
            $this->input->ip_address(),
            $assinaturaId
        );

        log_info('OS #' . $os->idOs . ' teve aceite "' . $decisao . '" registrado por ' . $nome . ' via link público.');

        redirect('aceite/' . $token);
    }

    /**
     * Foto do atendimento (pública, protegida pelo token do aceite).
     */
    public function foto($token = null, $fotoId = null)
    {
        $os = $this->aceite_model->getByToken($token);
        if (! $os || ! is_numeric($fotoId)) {
            show_404();
        }

        $this->load->model('fotosatendimento_model');
        $foto = $this->fotosatendimento_model->getById($fotoId);
        if (! $foto || (int) $foto->os_id !== (int) $os->idOs || empty($foto->imagem_base64)) {
            show_404();
        }

        $this->streamBase64($foto->imagem_base64, 'image/jpeg');
    }

    /**
     * Assinatura do atendimento (pública, protegida pelo token do aceite).
     */
    public function assinatura($token = null, $assinId = null)
    {
        $os = $this->aceite_model->getByToken($token);
        if (! $os || ! is_numeric($assinId)) {
            show_404();
        }

        $this->load->model('assinaturas_model');
        $assin = $this->assinaturas_model->getById($assinId);
        if (! $assin || (int) $assin->os_id !== (int) $os->idOs) {
            show_404();
        }

        $dataUri = $this->assinaturas_model->getImagemBase64($assinId);
        if (! $dataUri) {
            show_404();
        }
        $this->streamBase64($dataUri, 'image/png');
    }

    /**
     * Decodifica um base64 (com ou sem prefixo data:) e envia como imagem.
     */
    private function streamBase64($base64, $mimePadrao = 'image/png')
    {
        $mime = $mimePadrao;
        if (preg_match('/^data:(image\/\w+);base64,/', $base64, $m)) {
            $mime = $m[1];
            $base64 = substr($base64, strlen($m[0]));
        }
        $bin = base64_decode($base64, true);
        if ($bin === false) {
            show_404();
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: private, max-age=3600');
        echo $bin;
        exit;
    }
}
