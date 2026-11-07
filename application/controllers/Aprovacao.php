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

        // A automação de faturamento (NFS-e + boleto) NÃO ocorre mais na
        // aprovação do orçamento: ela foi movida para o aceite da resolução do
        // serviço realizado (ver Aceite::confirmar).
        if ($decisao === 'aprovado') {
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

    /**
     * Pagina PUBLICA para o cliente pedir o link de aprovacao informando o
     * numero do chamado e o documento. Existe porque a OS pode ter sido aberta
     * com e-mail ou celular incompletos: sem isto, quem tem o chamado em maos
     * nao tem por onde pedir o link.
     */
    public function solicitar()
    {
        if (! $this->aprovacao_model->suportado()) {
            show_404();
        }

        $this->load->model('mapos_model');
        $this->output->set_header('X-Robots-Tag: noindex, nofollow');

        $this->load->view('aprovacao/solicitar', [
            'erro' => $this->session->flashdata('erro'),
            'info' => $this->session->flashdata('info'),
            'emitente' => $this->mapos_model->getEmitente(),
        ]);
    }

    /**
     * POST: confere chamado + documento e envia o link de aprovacao aos
     * contatos cadastrados do cliente.
     *
     * A resposta e sempre a mesma, exista ou nao a OS: responder "nao encontrei"
     * transformaria o formulario num verificador de chamados alheios.
     */
    public function enviarLink()
    {
        if (! $this->aprovacao_model->suportado()) {
            show_404();
        }

        $this->load->helper('chamado_cobranca');
        $this->load->library('limite_login');
        $this->load->model('mapos_model');

        $ip = $this->input->ip_address();
        if ($espera = $this->limite_login->bloqueado('aprovacao', $ip)) {
            $this->session->set_flashdata('erro', $this->limite_login->mensagem($espera));
            redirect('aprovacao/solicitar');
        }

        $chamado = chamado_normalizar($this->input->post('chamado'));
        $documento = trim((string) $this->input->post('documento'));
        if ($chamado === null || chamado_digitos($documento) === '') {
            $this->limite_login->falhou('aprovacao', $ip);
            $this->session->set_flashdata('erro', 'Informe o número do chamado e o CPF/CNPJ do titular.');
            redirect('aprovacao/solicitar');
        }

        $alvo = [];
        foreach ($this->aprovacao_model->getPendentesPorChamado($chamado) as $os) {
            if (chamado_documento_confere($documento, $os->documento ?? '')) {
                $alvo[] = $os;
            }
        }

        $enviados = 0;
        $emitente = $this->mapos_model->getEmitente();
        if ($alvo) {
            $this->load->library('evolution_api');
            $this->load->model('whatsapp_templates_model');

            foreach ($alvo as $os) {
                $link = $this->linkAprovacaoDaOs($os);
                if ($link === null) {
                    continue;
                }
                if ($this->enviarLinkPorWhatsApp($os, $link)) {
                    $enviados++;
                    continue;
                }
                if ($this->enviarLinkPorEmail($os, $link, $emitente)) {
                    $enviados++;
                }
            }
        }

        if (! $alvo || $enviados === 0) {
            $this->limite_login->falhou('aprovacao', $ip);
        } else {
            $this->limite_login->sucesso('aprovacao', $ip);
        }

        log_info('Pedido de link de aprovacao pelo chamado "' . $chamado . '": ' . count($alvo) . ' OS conferida(s), ' . $enviados . ' envio(s).');

        // Mesma resposta nos dois casos, de proposito.
        $this->session->set_flashdata(
            'info',
            'Se houver orçamento aguardando aprovação para este chamado, enviamos o link aos contatos cadastrados. '
            . 'Se não receber em alguns minutos, fale com a empresa.'
        );
        redirect('aprovacao/solicitar');
    }

    /** Link de aprovacao valido da OS: reaproveita o pendente ou gera um novo. */
    private function linkAprovacaoDaOs($os)
    {
        $pendente = ($os->aprovacao_status ?? '') === 'pendente'
            && ! empty($os->aprovacao_token)
            && (empty($os->aprovacao_expira) || strtotime($os->aprovacao_expira) > time());

        if ($pendente) {
            return site_url('aprovacao/' . $os->aprovacao_token);
        }

        $info = $this->aprovacao_model->gerarLink((int) $os->idOs, 7);

        return $info ? site_url('aprovacao/' . $info['token']) : null;
    }

    /** Texto do aviso, usando o modelo de WhatsApp de aprovacao quando existir. */
    private function mensagemAprovacao($os, $link)
    {
        $fallback = 'Ola, {CLIENTE_NOME}. O orcamento da OS #{NUMERO_OS} esta pronto para aprovacao: {LINK}';

        try {
            return $this->whatsapp_templates_model->renderizar('aprovacao', [
                '{CLIENTE_NOME}' => (string) $os->nomeCliente,
                '{NUMERO_OS}' => (string) $os->idOs,
                '{LINK}' => $link,
            ], $fallback);
        } catch (Throwable $e) {
            return str_replace(
                ['{CLIENTE_NOME}', '{NUMERO_OS}', '{LINK}'],
                [(string) $os->nomeCliente, (string) $os->idOs, $link],
                $fallback
            );
        }
    }

    private function enviarLinkPorWhatsApp($os, $link)
    {
        try {
            if (! $this->evolution_api->estaAtivo()) {
                return false;
            }

            $mensagem = $this->mensagemAprovacao($os, $link);
            $enviados = 0;
            foreach ($this->aprovacao_model->numerosDestino($os) as $numero) {
                try {
                    $this->evolution_api->enviarTexto($numero, $mensagem, [
                        'tipo' => 'cliente',
                        'os_id' => (int) $os->idOs,
                        'evento' => 'link_aprovacao_solicitado',
                    ]);
                    $enviados++;
                } catch (Exception $e) {
                    log_info('OS #' . $os->idOs . ': falha ao enviar link de aprovacao para ' . $numero . ': ' . $e->getMessage());
                }
            }

            return $enviados > 0;
        } catch (Exception $e) {
            log_info('OS #' . $os->idOs . ': WhatsApp indisponivel para o link de aprovacao: ' . $e->getMessage());

            return false;
        }
    }

    private function enviarLinkPorEmail($os, $link, $emitente)
    {
        $email = trim((string) ($os->email_cliente ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $emitente || empty($emitente->email)) {
            return false;
        }

        try {
            $this->load->model('email_model');
            $html = '<p>Ola, ' . html_escape((string) $os->nomeCliente) . '.</p>'
                . '<p>O orcamento da Ordem de Servico <strong>#' . (int) $os->idOs . '</strong> esta pronto para aprovacao.</p>'
                . '<p><a href="' . html_escape($link) . '">Abrir o orcamento e decidir</a></p>'
                . '<p>Se o link expirar, peca um novo a empresa.</p>';
            $this->email_model->add('email_queue', [
                'to' => $email,
                'message' => $html,
                'status' => 'pending',
                'date' => date('Y-m-d H:i:s'),
                'headers' => serialize([
                    'From' => $emitente->email,
                    'Subject' => 'Aprovacao da OS #' . (int) $os->idOs,
                    'Return-Path' => '',
                ]),
            ]);

            return true;
        } catch (Exception $e) {
            log_info('OS #' . $os->idOs . ': falha ao enfileirar o link de aprovacao: ' . $e->getMessage());

            return false;
        }
    }
}
