<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Página PÚBLICA de aprovação de Ordem de Serviço.
 *
 * Estende CI_Controller (e não MY_Controller) de propósito: o cliente acessa
 * pelo link temporário sem precisar estar logado no sistema. O acesso é
 * protegido pelo token aleatório de 64 caracteres presente na URL.
 */
class Aprovacao extends CI_Controller
{
    /** Quantidade de números que receberam o código no último envio. */
    private $codigoQtdWhats = 0;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->model('aprovacao_model');
    }

    /**
     * Exibe a OS referente ao token e as opções de aprovar/reprovar.
     */
    public function index($token = null)
    {
        $os = $this->aprovacao_model->getByToken($token);
        $situacao = $this->aprovacao_model->situacao($os);

        $data = [
            'token' => $token,
            'situacao' => $situacao,
            'os' => $os,
            'erro' => $this->session->flashdata('erro'),
        ];

        // Estado da verificação por código (token).
        $data['exigeToken'] = $this->aprovacao_model->exigeToken($os);
        $data['codigoValidado'] = $this->aprovacao_model->codigoValidado($os);
        $data['codigoEnviado'] = $os && ! empty($os->aprovacao_codigo);
        $data['canalMascarado'] = $this->contatoMascarado($os);
        $data['qtdDestinos'] = ($os && $data['exigeToken']) ? count($this->aprovacao_model->numerosDestino($os)) : 0;
        $data['info'] = $this->session->flashdata('info');

        // Só carrega detalhes (itens, valores, emitente) quando há uma OS válida.
        if ($os && $situacao !== 'invalido') {
            $this->load->model('os_model');
            $this->load->model('mapos_model');

            $data['produtos'] = $this->os_model->getProdutos($os->idOs);
            $data['servicos'] = $this->os_model->getServicos($os->idOs);
            $data['emitente'] = $this->mapos_model->getEmitente();

            $totais = $this->os_model->valorTotalOS($os->idOs);
            $data['totalProdutos'] = $totais['totalProdutos'];
            $data['totalServico'] = $totais['totalServico'];
            $data['valorDesconto'] = $totais['valor_desconto'];
        }

        $this->load->view('aprovacao/publico', $data);
    }

    /**
     * Recebe a decisão do cliente (POST) e registra na OS.
     */
    public function confirmar()
    {
        $token = $this->input->post('token');
        $decisao = $this->input->post('decisao'); // 'aprovado' | 'reprovado'
        $nome = trim((string) $this->input->post('nome'));
        $obs = trim((string) $this->input->post('obs'));

        $os = $this->aprovacao_model->getByToken($token);
        $situacao = $this->aprovacao_model->situacao($os);

        // Só é possível decidir enquanto o link está pendente e válido.
        if (! $os || $situacao !== 'pendente') {
            redirect('aprovacao/' . $token);
        }

        if (! in_array($decisao, ['aprovado', 'reprovado'], true)) {
            $this->session->set_flashdata('erro', 'Selecione aprovar ou reprovar.');
            redirect('aprovacao/' . $token);
        }

        if ($nome === '') {
            $this->session->set_flashdata('erro', 'Por favor, informe seu nome para confirmar a decisão.');
            redirect('aprovacao/' . $token);
        }

        if ($decisao === 'reprovado' && $obs === '') {
            $this->session->set_flashdata('erro', 'Para reprovar, descreva o motivo no campo de observação.');
            redirect('aprovacao/' . $token);
        }

        // Se esta OS exige verificação por código, ela precisa ter sido validada.
        if ($this->aprovacao_model->exigeToken($os) && ! $this->aprovacao_model->codigoValidado($os)) {
            $this->session->set_flashdata('erro', 'Valide o código de verificação antes de confirmar a decisão.');
            redirect('aprovacao/' . $token);
        }

        $this->aprovacao_model->registrarDecisao(
            $os->idOs,
            $decisao,
            $nome,
            $obs,
            $this->input->ip_address()
        );

        log_info('OS #' . $os->idOs . ' teve aprovação "' . $decisao . '" registrada por ' . $nome . ' via link público.');

        // Automação opcional na aprovação: emite NFS-e e gera boleto (se ligada
        // globalmente e habilitada para o cliente). É best-effort — nunca
        // interrompe o fluxo de aprovação.
        if ($decisao === 'aprovado') {
            $this->load->library('autoaprovacao');
            $this->autoaprovacao->executar($os->idOs);

            // Notificação WhatsApp do evento os_aprovada (gatilhos ativos).
            $this->load->library('notificador');
            $this->notificador->whatsappOs($os->idOs, 'os_aprovada');
        }

        redirect('aprovacao/' . $token);
    }

    /**
     * Gera e envia um código de verificação ao cliente (WhatsApp e/ou e-mail).
     */
    public function enviarCodigo()
    {
        $token = $this->input->post('token');
        $os = $this->aprovacao_model->getByToken($token);
        $situacao = $this->aprovacao_model->situacao($os);

        if (! $os || $situacao !== 'pendente' || ! $this->aprovacao_model->exigeToken($os)) {
            redirect('aprovacao/' . $token);
        }

        $codigo = $this->aprovacao_model->gerarCodigo($os->idOs);
        if ($codigo === false) {
            $this->session->set_flashdata('erro', 'Verificação por código indisponível neste ambiente.');
            redirect('aprovacao/' . $token);
        }

        $canais = $this->enviarCodigoNosCanais($os, $codigo);

        if (empty($canais)) {
            $this->session->set_flashdata('erro', 'Não há WhatsApp nem e-mail disponível para enviar o código. Entre em contato com a empresa.');
            redirect('aprovacao/' . $token);
        }

        $this->aprovacao_model->registrarCanalCodigo($os->idOs, implode('+', $canais));
        log_info('OS #' . $os->idOs . ': código de verificação de aprovação enviado (' . implode(', ', $canais) . ').');

        $this->session->set_flashdata('info', 'Enviamos um código de verificação para ' . $this->descreverCanais($canais) . '. Informe-o abaixo (válido por ' . Aprovacao_model::MINUTOS_CODIGO . ' minutos).');
        redirect('aprovacao/' . $token);
    }

    /**
     * Valida o código informado pelo cliente.
     */
    public function validarCodigo()
    {
        $token = $this->input->post('token');
        $codigo = $this->input->post('codigo');
        $os = $this->aprovacao_model->getByToken($token);
        $situacao = $this->aprovacao_model->situacao($os);

        if (! $os || $situacao !== 'pendente' || ! $this->aprovacao_model->exigeToken($os)) {
            redirect('aprovacao/' . $token);
        }

        switch ($this->aprovacao_model->validarCodigo($os->idOs, $codigo)) {
            case 'ok':
                $this->session->set_flashdata('info', 'Código validado! Agora você pode aprovar ou reprovar o orçamento.');
                break;
            case 'expirado':
                $this->session->set_flashdata('erro', 'O código expirou. Solicite um novo código.');
                break;
            case 'bloqueado':
                $this->session->set_flashdata('erro', 'Muitas tentativas. Solicite um novo código para tentar novamente.');
                break;
            case 'sem_codigo':
                $this->session->set_flashdata('erro', 'Solicite o código antes de validar.');
                break;
            default:
                $this->session->set_flashdata('erro', 'Código inválido. Confira e tente novamente.');
                break;
        }

        redirect('aprovacao/' . $token);
    }

    /**
     * Envia o código pelos canais disponíveis do cliente. Retorna a lista de
     * canais que aceitaram o envio (ex.: ['whatsapp', 'email']). Best-effort.
     */
    private function enviarCodigoNosCanais($os, $codigo)
    {
        $canais = [];
        $emitenteNome = '';
        $this->load->model('mapos_model');
        $emitente = $this->mapos_model->getEmitente();
        if ($emitente && ! empty($emitente->nome)) {
            $emitenteNome = $emitente->nome;
        }

        $mensagem = 'Seu codigo de verificacao para aprovar a Ordem de Servico #' . $os->idOs
            . ($emitenteNome ? ' (' . $emitenteNome . ')' : '') . ' e: ' . $codigo
            . '. Valido por ' . Aprovacao_model::MINUTOS_CODIGO . ' minutos.';

        // WhatsApp (Evolution API): celular do cliente + números extras
        // (cadastro do cliente e/ou avulsos da OS), sem repetição.
        $numeros = $this->aprovacao_model->numerosDestino($os);
        $enviadosWhats = 0;
        if (! empty($numeros)) {
            try {
                $this->load->library('evolution_api');
                if ($this->evolution_api->estaAtivo()) {
                    foreach ($numeros as $numero) {
                        try {
                            $this->evolution_api->enviarTexto($numero, $mensagem, ['tipo' => 'cliente', 'os_id' => $os->idOs, 'evento' => 'codigo_aprovacao']);
                            $enviadosWhats++;
                        } catch (\Exception $e) {
                            log_info('OS #' . $os->idOs . ': falha ao enviar código por WhatsApp para ' . $numero . ': ' . $e->getMessage());
                        }
                    }
                }
            } catch (\Exception $e) {
                log_info('OS #' . $os->idOs . ': WhatsApp indisponível para envio de código: ' . $e->getMessage());
            }
        }
        if ($enviadosWhats > 0) {
            $canais[] = 'whatsapp';
        }
        $this->codigoQtdWhats = $enviadosWhats;

        // E-mail (fila), se o cliente tiver e-mail e o emitente estiver configurado.
        $email = trim((string) ($os->email_cliente ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $emitente && ! empty($emitente->email)) {
            try {
                $this->load->model('email_model');
                $html = '<p>Olá, ' . html_escape($os->nomeCliente) . '.</p>'
                    . '<p>Seu código de verificação para aprovar a Ordem de Serviço <strong>#' . (int) $os->idOs . '</strong> é:</p>'
                    . '<p style="font-size:26px;font-weight:bold;letter-spacing:4px">' . $codigo . '</p>'
                    . '<p>Válido por ' . Aprovacao_model::MINUTOS_CODIGO . ' minutos.</p>';
                $headers = ['From' => $emitente->email, 'Subject' => 'Código de aprovação - OS #' . $os->idOs, 'Return-Path' => ''];
                $this->email_model->add('email_queue', [
                    'to' => $email,
                    'message' => $html,
                    'status' => 'pending',
                    'date' => date('Y-m-d H:i:s'),
                    'headers' => serialize($headers),
                ]);
                $canais[] = 'email';
            } catch (\Exception $e) {
                log_info('OS #' . $os->idOs . ': falha ao enfileirar código por e-mail: ' . $e->getMessage());
            }
        }

        return $canais;
    }

    private function descreverCanais(array $canais)
    {
        $partes = [];
        foreach ($canais as $c) {
            if ($c === 'whatsapp') {
                $partes[] = $this->codigoQtdWhats > 1
                    ? ('WhatsApp (' . $this->codigoQtdWhats . ' números)')
                    : 'seu WhatsApp';
            } elseif ($c === 'email') {
                $partes[] = 'seu e-mail';
            } else {
                $partes[] = $c;
            }
        }

        return implode(' e ', $partes);
    }

    /**
     * Contato mascarado (para exibir na página) do canal preferencial.
     */
    private function contatoMascarado($os)
    {
        if (! $os) {
            return '';
        }

        $celular = preg_replace('/\D/', '', (string) ($os->celular_cliente ?? ''));
        if ($celular !== '' && strlen($celular) >= 4) {
            return 'WhatsApp •••••' . substr($celular, -4);
        }

        $email = trim((string) ($os->email_cliente ?? ''));
        if ($email !== '' && strpos($email, '@') !== false) {
            [$user, $dom] = explode('@', $email, 2);
            $ini = mb_substr($user, 0, 2);

            return 'e-mail ' . $ini . '•••@' . $dom;
        }

        return '';
    }
}
