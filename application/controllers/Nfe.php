<?php

use Libraries\Fiscal\CertificadoHelper;
use Libraries\Fiscal\NfeService;
use Libraries\Fiscal\NfseService;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Nfe extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('nfe_model');
        $this->load->model('mapos_model');

        if (!$this->session->userdata('id_admin')) {
            redirect('login');
        }
    }

    /**
     * Listagem das notas emitidas
     */
    public function index()
    {
        $this->gerenciar();
    }

    public function gerenciar()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vNfe')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar Notas Fiscais.');
            redirect(base_url());
        }

        $this->load->library('pagination');

        $status = $this->input->get('status') ?: null;
        $porPagina = 20;

        $config = [
            'base_url' => base_url() . 'index.php/nfe/gerenciar/',
            'total_rows' => $this->nfe_model->countNotas($status),
            'per_page' => $porPagina,
            'uri_segment' => 3,
        ];
        $this->pagination->initialize($config);

        $inicio = (int) $this->uri->segment(3);

        $this->data['menuNfe'] = 'Notas Fiscais';
        $this->data['results'] = $this->nfe_model->getNotas($porPagina, $inicio, $status);
        $this->data['configNfe'] = $this->nfe_model->getConfig();
        $this->data['view'] = 'nfe/gerenciar';

        return $this->layout();
    }

    /**
     * Tela de configurações fiscais (certificado, séries, ambiente)
     */
    public function configuracoes()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cNfe')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar o módulo fiscal.');
            redirect(base_url());
        }

        $this->load->library('form_validation');
        $this->data['custom_error'] = '';

        if ($this->input->post()) {
            $this->form_validation->set_rules('ambiente', 'Ambiente', 'required|in_list[1,2]');
            $this->form_validation->set_rules('serie_nfe', 'Série NF-e', 'required|trim|max_length[3]');
            $this->form_validation->set_rules('proximo_numero_nfe', 'Próximo número NF-e', 'required|is_natural_no_zero');
            $this->form_validation->set_rules('serie_dps', 'Série DPS', 'required|trim|max_length[5]');
            $this->form_validation->set_rules('proximo_numero_dps', 'Próximo número DPS', 'required|is_natural_no_zero');
            $this->form_validation->set_rules('codigo_municipio', 'Código IBGE do município', 'required|trim|exact_length[7]|numeric');
            $this->form_validation->set_rules('csosn_padrao', 'CSOSN padrão', 'required|trim|exact_length[3]');
            $this->form_validation->set_rules('cfop_padrao', 'CFOP padrão', 'required|trim|exact_length[4]');
            $this->form_validation->set_rules('op_simp_nac', 'Situação no Simples', 'required|in_list[1,2,3]');
            $this->form_validation->set_rules('aliquota_iss', 'Alíquota ISS', 'trim|numeric');

            if ($this->form_validation->run() == true) {
                try {
                    $data = [
                        'ambiente' => $this->input->post('ambiente'),
                        'serie_nfe' => $this->input->post('serie_nfe'),
                        'proximo_numero_nfe' => $this->input->post('proximo_numero_nfe'),
                        'serie_dps' => $this->input->post('serie_dps'),
                        'proximo_numero_dps' => $this->input->post('proximo_numero_dps'),
                        'codigo_municipio' => $this->input->post('codigo_municipio'),
                        'csosn_padrao' => $this->input->post('csosn_padrao'),
                        'cfop_padrao' => $this->input->post('cfop_padrao'),
                        'op_simp_nac' => $this->input->post('op_simp_nac'),
                        'reg_esp_trib' => $this->input->post('reg_esp_trib') ?: 0,
                        'inscricao_municipal' => $this->input->post('inscricao_municipal'),
                        'aliquota_iss' => $this->input->post('aliquota_iss') ?: 0,
                        'tp_ret_issqn' => $this->input->post('tp_ret_issqn') ?: 1,
                    ];

                    // upload do certificado A1 (opcional: só quando troca)
                    if (!empty($_FILES['certificado']['name'])) {
                        if ($_FILES['certificado']['error'] !== UPLOAD_ERR_OK) {
                            throw new Exception('Falha no upload do certificado (código ' . $_FILES['certificado']['error'] . ')');
                        }
                        $data['certificado_path'] = CertificadoHelper::salvarPfx(
                            $_FILES['certificado']['tmp_name'],
                            $_FILES['certificado']['name']
                        );
                    }

                    // senha só é regravada quando preenchida
                    $senha = $this->input->post('senha_certificado');
                    if ($senha !== null && $senha !== '') {
                        $data['senha_certificado'] = CertificadoHelper::criptografar($senha);
                    }

                    $this->nfe_model->saveConfig($data);

                    // valida o certificado imediatamente para dar retorno claro ao usuário
                    $config = $this->nfe_model->getConfig();
                    if (!empty($config->certificado_path) && !empty($config->senha_certificado)) {
                        $cert = CertificadoHelper::carregar($config->certificado_path, $config->senha_certificado);
                        $this->session->set_flashdata(
                            'success',
                            'Configurações salvas. Certificado válido até ' . $cert->getValidTo()->format('d/m/Y') . '.'
                        );
                    } else {
                        $this->session->set_flashdata('success', 'Configurações salvas. Envie o certificado A1 e a senha para concluir.');
                    }

                    redirect('nfe/configuracoes');
                } catch (Exception $e) {
                    $this->data['custom_error'] = '<div class="alert alert-danger">' . html_escape($e->getMessage()) . '</div>';
                }
            } else {
                $this->data['custom_error'] = (validation_errors() ? '<div class="alert alert-danger">' . validation_errors() . '</div>' : '');
            }
        }

        $this->data['menuNfe'] = 'Notas Fiscais';
        $this->data['configNfe'] = $this->nfe_model->getConfig();
        $this->data['emitente'] = $this->mapos_model->getEmitente();
        $this->data['view'] = 'nfe/configuracoes';

        return $this->layout();
    }

    /**
     * Transmite a NF-e (modelo 55) de uma venda. Chamada via AJAX, retorna JSON.
     */
    public function emitirNfe($idVenda = null)
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'eNfe')) {
            return $this->jsonResponse(false, 'Você não tem permissão para emitir notas fiscais.');
        }
        if (!$idVenda || !is_numeric($idVenda)) {
            return $this->jsonResponse(false, 'Venda inválida.');
        }

        $this->load->model('vendas_model');

        $venda = $this->vendas_model->getById($idVenda);
        if (!$venda) {
            return $this->jsonResponse(false, 'Venda não encontrada.');
        }

        $notaExistente = $this->nfe_model->getNotaAtiva('nfe', 'vendas_id', $idVenda);
        if ($notaExistente) {
            return $this->jsonResponse(false, "Esta venda já possui a NF-e nº {$notaExistente->numero} ({$notaExistente->status}). Cancele-a antes de emitir outra.");
        }

        $itens = $this->vendas_model->getProdutos($idVenda);

        return $this->processarNfe(
            $venda,
            $itens,
            ['vendas_id' => $idVenda],
            (float) ($venda->valorTotal ?? 0),
            "venda {$idVenda}",
            ['info_complementar' => (string) $this->input->post('info_complementar')]
        );
    }

    /**
     * Transmite a NF-e (modelo 55) dos PRODUTOS de uma OS. Chamada via AJAX, retorna JSON.
     * (Os serviços da OS são faturados separadamente via NFS-e.)
     */
    public function emitirNfeOs($idOs = null)
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'eNfe')) {
            return $this->jsonResponse(false, 'Você não tem permissão para emitir notas fiscais.');
        }
        if (!$idOs || !is_numeric($idOs)) {
            return $this->jsonResponse(false, 'OS inválida.');
        }

        $this->load->model('os_model');

        $os = $this->os_model->getById($idOs);
        if (!$os) {
            return $this->jsonResponse(false, 'OS não encontrada.');
        }

        $notaExistente = $this->nfe_model->getNotaAtiva('nfe', 'os_id', $idOs);
        if ($notaExistente) {
            return $this->jsonResponse(false, "Esta OS já possui a NF-e nº {$notaExistente->numero} ({$notaExistente->status}). Cancele-a antes de emitir outra.");
        }

        $itens = $this->os_model->getProdutos($idOs);
        if (empty($itens)) {
            return $this->jsonResponse(false, 'Esta OS não possui produtos para emitir NF-e. Para os serviços, use "Emitir NFS-e".');
        }

        $valorTotal = 0.0;
        foreach ($itens as $item) {
            $valorTotal += (float) $item->quantidade * (float) $item->preco;
        }

        return $this->processarNfe(
            $os,
            $itens,
            ['os_id' => $idOs],
            round($valorTotal, 2),
            "OS {$idOs}",
            ['info_complementar' => (string) $this->input->post('info_complementar')]
        );
    }

    /**
     * Núcleo comum de emissão de NF-e (produtos), usado por Vendas e OS.
     * $origem  = objeto com dados do cliente/documento (getById de venda ou OS)
     * $vinculo = ['vendas_id' => X] ou ['os_id' => Y]
     */
    private function processarNfe($origem, array $itens, array $vinculo, float $valorTotal, string $rotuloLog, array $opcoes = [])
    {
        $config = $this->nfe_model->getConfig();
        $emitente = $this->mapos_model->getEmitente();
        if (!$emitente) {
            return $this->jsonResponse(false, 'Emitente não cadastrado. Configure em Configurações > Emitente.');
        }

        $idNota = null;
        try {
            $service = new NfeService($config, $emitente);

            $numero = $this->nfe_model->reservarNumero('proximo_numero_nfe');

            $idNota = $this->nfe_model->addNota(array_merge([
                'tipo' => 'nfe',
                'numero' => $numero,
                'serie' => $config->serie_nfe,
                'status' => 'pendente',
                'ambiente' => $config->ambiente,
                'valor_total' => $valorTotal,
                'data_emissao' => date('Y-m-d H:i:s'),
                'usuarios_id' => $this->session->userdata('id_admin'),
            ], $vinculo));

            $resultado = $service->emitir($origem, $itens, $numero, $opcoes);

            if (!$resultado['sucesso']) {
                $this->nfe_model->updateNota($idNota, [
                    'status' => 'rejeitada',
                    'chave' => $resultado['chave'],
                    'motivo' => "[{$resultado['cstat']}] {$resultado['motivo']}",
                ]);

                return $this->jsonResponse(false, "NF-e rejeitada pela SEFAZ: [{$resultado['cstat']}] {$resultado['motivo']}");
            }

            $xmlPath = CertificadoHelper::salvarXml("nfe_{$resultado['chave']}.xml", $resultado['xml']);

            $this->nfe_model->updateNota($idNota, [
                'status' => 'autorizada',
                'chave' => $resultado['chave'],
                'protocolo' => $resultado['protocolo'],
                'motivo' => "[{$resultado['cstat']}] {$resultado['motivo']}",
                'xml_path' => $xmlPath,
                'data_autorizacao' => date('Y-m-d H:i:s'),
            ]);

            log_info("Emitiu NF-e nº {$numero} (chave {$resultado['chave']}) da {$rotuloLog}");

            return $this->jsonResponse(true, "NF-e nº {$numero} autorizada com sucesso!", [
                'idNota' => $idNota,
                'chave' => $resultado['chave'],
                'protocolo' => $resultado['protocolo'],
                'urlXml' => site_url("nfe/xml/{$idNota}"),
                'urlDanfe' => site_url("nfe/danfe/{$idNota}"),
            ]);
        } catch (\Throwable $e) {
            $tecnico = $e->getMessage();
            if ($idNota) {
                $this->nfe_model->updateNota($idNota, ['status' => 'erro', 'motivo' => $tecnico]);
            }
            log_message('error', 'Falha na emissão fiscal: ' . $tecnico);

            return $this->jsonResponse(false, $this->traduzErroFiscal($e));
        }
    }

    /**
     * Transmite a NFS-e (Padrão Nacional) de uma OS. Chamada via AJAX, retorna JSON.
     */
    public function emitirNfse($idOs = null)
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'eNfe')) {
            return $this->jsonResponse(false, 'Você não tem permissão para emitir notas fiscais.');
        }
        if (!$idOs || !is_numeric($idOs)) {
            return $this->jsonResponse(false, 'OS inválida.');
        }

        $this->load->model('os_model');

        $os = $this->os_model->getById($idOs);
        if (!$os) {
            return $this->jsonResponse(false, 'OS não encontrada.');
        }

        $notaExistente = $this->nfe_model->getNotaAtiva('nfse', 'os_id', $idOs);
        if ($notaExistente) {
            return $this->jsonResponse(false, "Esta OS já possui a NFS-e nº {$notaExistente->numero} ({$notaExistente->status}). Cancele-a antes de emitir outra.");
        }

        $config = $this->nfe_model->getConfig();
        $emitente = $this->mapos_model->getEmitente();
        if (!$emitente) {
            return $this->jsonResponse(false, 'Emitente não cadastrado. Configure em Configurações > Emitente.');
        }

        $idNota = null;
        try {
            $servicos = $this->os_model->getServicos($idOs);
            $service = new NfseService($config, $emitente);

            $numero = $this->nfe_model->reservarNumero('proximo_numero_dps');

            $valorTotal = 0.0;
            foreach ($servicos as $s) {
                $valorTotal += ((float) ($s->quantidade ?? 1) ?: 1) * (float) ($s->preco ?? $s->precoVenda);
            }

            $idNota = $this->nfe_model->addNota([
                'tipo' => 'nfse',
                'os_id' => $idOs,
                'numero' => $numero,
                'serie' => $config->serie_dps,
                'status' => 'pendente',
                'ambiente' => $config->ambiente,
                'valor_total' => round($valorTotal, 2),
                'data_emissao' => date('Y-m-d H:i:s'),
                'usuarios_id' => $this->session->userdata('id_admin'),
            ]);

            $opcoes = [
                'info_complementar' => (string) $this->input->post('info_complementar'),
                'ctribnac' => (string) $this->input->post('ctribnac'),
                'desc_servico' => (string) $this->input->post('desc_servico'),
                'tp_ret_issqn' => (string) $this->input->post('tp_ret_issqn'),
                'aliquota_iss' => (string) $this->input->post('aliquota_iss'),
            ];
            $resultado = $service->emitir($os, $servicos, $numero, $opcoes);

            if (!$resultado['sucesso']) {
                $this->nfe_model->updateNota($idNota, [
                    'status' => 'rejeitada',
                    'motivo' => $resultado['motivo'],
                ]);

                return $this->jsonResponse(false, 'NFS-e rejeitada pelo Sefin Nacional: ' . $resultado['motivo']);
            }

            $xmlPath = null;
            if (!empty($resultado['xml'])) {
                $xmlPath = CertificadoHelper::salvarXml("nfse_dps{$numero}_" . date('YmdHis') . '.xml', $resultado['xml']);
            }

            $this->nfe_model->updateNota($idNota, [
                'status' => 'autorizada',
                'chave' => $resultado['chave'],
                'motivo' => $resultado['motivo'],
                'xml_path' => $xmlPath,
                'data_autorizacao' => date('Y-m-d H:i:s'),
            ]);

            log_info("Emitiu NFS-e (DPS nº {$numero}) da OS {$idOs}");

            return $this->jsonResponse(true, "NFS-e gerada com sucesso (DPS nº {$numero})!", [
                'idNota' => $idNota,
                'chave' => $resultado['chave'],
                'urlXml' => $xmlPath ? site_url("nfe/xml/{$idNota}") : null,
                'urlDanfe' => site_url("nfe/danfe/{$idNota}"),
            ]);
        } catch (\Throwable $e) {
            $tecnico = $e->getMessage();
            if ($idNota) {
                $this->nfe_model->updateNota($idNota, ['status' => 'erro', 'motivo' => $tecnico]);
            }
            log_message('error', 'Falha na emissão fiscal: ' . $tecnico);

            return $this->jsonResponse(false, $this->traduzErroFiscal($e));
        }
    }

    /**
     * Dados para o passo de revisão do wizard de emissão (via AJAX, JSON).
     * $tipo = 'nfe' (produtos) ou 'nfse' (serviços) de uma OS.
     */
    public function previewOs($idOs = null, $tipo = 'nfse')
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'eNfe')) {
            return $this->jsonResponse(false, 'Sem permissão.');
        }
        if (!$idOs || !is_numeric($idOs) || !in_array($tipo, ['nfe', 'nfse'])) {
            return $this->jsonResponse(false, 'Parâmetros inválidos.');
        }

        $this->load->model('os_model');
        $os = $this->os_model->getById($idOs);
        if (!$os) {
            return $this->jsonResponse(false, 'OS não encontrada.');
        }

        $config = $this->nfe_model->getConfig();
        $itens = [];
        $avisos = [];
        $defaults = [];
        $total = 0.0;

        if ($tipo === 'nfe') {
            foreach ($this->os_model->getProdutos($idOs) as $p) {
                $sub = (float) $p->quantidade * (float) $p->preco;
                $total += $sub;
                $ncm = preg_replace('/\D/', '', (string) ($p->ncm ?? ''));
                if (strlen($ncm) !== 8) {
                    $avisos[] = "Produto \"{$p->descricao}\" está sem NCM válido (8 dígitos).";
                }
                $itens[] = [
                    'descricao' => $p->descricao,
                    'quantidade' => (float) $p->quantidade,
                    'preco' => (float) $p->preco,
                    'subtotal' => $sub,
                ];
            }
            if (empty($itens)) {
                $avisos[] = 'Esta OS não possui produtos. Use "Emitir NFS-e" para os serviços.';
            }
        } else {
            $cTribNac = '';
            foreach ($this->os_model->getServicos($idOs) as $s) {
                $preco = (float) ($s->preco ?: $s->precoVenda);
                $qtd = (float) ($s->quantidade ?: 1);
                $sub = $preco * $qtd;
                $total += $sub;
                $codigo = preg_replace('/\D/', '', (string) ($s->codigo_servico_municipio ?? ''));
                if ($cTribNac === '' && strlen($codigo) === 6) {
                    $cTribNac = $codigo;
                }
                $itens[] = [
                    'descricao' => trim($s->nome . (empty($s->descricao) ? '' : ' - ' . $s->descricao)),
                    'quantidade' => $qtd,
                    'preco' => $preco,
                    'subtotal' => $sub,
                ];
            }
            if (empty($itens)) {
                $avisos[] = 'Esta OS não possui serviços lançados.';
            }
            if ($cTribNac === '') {
                $avisos[] = 'Nenhum serviço tem Código de Tributação Nacional cadastrado. Informe abaixo.';
            }
            $defaults = [
                'ctribnac' => $cTribNac,
                'aliquota_iss' => number_format((float) $config->aliquota_iss, 2, '.', ''),
                'tp_ret_issqn' => (int) $config->tp_ret_issqn,
                'desc_servico' => 'OS nr. ' . $os->idOs,
            ];
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'ambiente' => (int) $config->ambiente,
                'cliente' => [
                    'nome' => $os->nomeCliente,
                    'documento' => $os->documento,
                ],
                'itens' => $itens,
                'total' => round($total, 2),
                'avisos' => $avisos,
                'defaults' => $defaults,
            ]));
    }

    /**
     * Prévia VISUAL (HTML) de como a nota vai sair, antes de emitir.
     * Apenas ilustrativa — marcada como SEM VALOR FISCAL.
     * $tipo = 'nfe' (DANFE / produtos) ou 'nfse' (DANFSe / serviços) de uma OS.
     */
    public function modeloPreview($idOs = null, $tipo = 'nfse')
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'eNfe')) {
            show_error('Sem permissão para visualizar a prévia.', 403);
            return;
        }
        if (!$idOs || !is_numeric($idOs) || !in_array($tipo, ['nfe', 'nfse'])) {
            show_error('Parâmetros inválidos.', 400);
            return;
        }

        $this->load->model('os_model');
        $os = $this->os_model->getById($idOs);
        if (!$os) {
            show_error('OS não encontrada.', 404);
            return;
        }

        $data = [
            'os' => $os,
            'emitente' => $this->mapos_model->getEmitente(),
            'config' => $this->nfe_model->getConfig(),
        ];

        if ($tipo === 'nfe') {
            $produtos = $this->os_model->getProdutos($idOs);

            // 1ª opção: DANFE real (design oficial) via sped-da, a partir de um
            // XML de rascunho (não assinado). Requer as libs do composer e dados
            // completos (NCM etc.). Em qualquer falha, cai no mockup HTML.
            try {
                if (!class_exists(\NFePHP\DA\NFe\Danfe::class)) {
                    throw new Exception('Bibliotecas fiscais não instaladas (rode composer install).');
                }
                $service = new NfeService($data['config'], $data['emitente']);
                $xml = $service->montarXmlRascunho($os, $produtos, (int) ($data['config']->proximo_numero_nfe ?? 1));
                $danfe = new \NFePHP\DA\NFe\Danfe($xml);
                $pdf = $danfe->render($this->logoEmitente($data['emitente']));
                $this->output
                    ->set_content_type('application/pdf')
                    ->set_header('Content-Disposition: inline; filename="previa_danfe_os' . $idOs . '.pdf"')
                    ->set_output($pdf);

                return;
            } catch (\Throwable $e) {
                $data['produtos'] = $produtos;
                $data['previaAviso'] = 'Prévia aproximada (o DANFE oficial fica disponível quando o composer estiver instalado e os dados completos). Detalhe: ' . $e->getMessage();
                $this->load->view('nfe/modelo_danfe', $data);

                return;
            }
        }

        $data['servicos'] = $this->os_model->getServicos($idOs);
        $this->load->view('nfe/modelo_danfse', $data);
    }

    /**
     * Download do XML autorizado
     */
    public function xml($idNota = null)
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vNfe')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar Notas Fiscais.');
            redirect(base_url());
        }

        $nota = $this->nfe_model->getNotaById($idNota);
        if (!$nota || empty($nota->xml_path) || !file_exists($nota->xml_path)) {
            $this->session->set_flashdata('error', 'XML não encontrado para esta nota.');
            redirect('nfe/gerenciar');
        }

        $this->load->helper('download');
        force_download(basename($nota->xml_path), file_get_contents($nota->xml_path));
    }

    /**
     * DANFE (NF-e, gerado localmente) ou DANFSe (NFS-e, baixado do Sefin Nacional)
     */
    public function danfe($idNota = null)
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vNfe')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar Notas Fiscais.');
            redirect(base_url());
        }

        $nota = $this->nfe_model->getNotaById($idNota);
        if (!$nota || $nota->status !== 'autorizada') {
            $this->session->set_flashdata('error', 'Nota não encontrada ou não autorizada.');
            redirect('nfe/gerenciar');
        }

        try {
            if ($nota->tipo === 'nfe') {
                if (empty($nota->xml_path) || !file_exists($nota->xml_path)) {
                    throw new Exception('XML da NF-e não encontrado em disco.');
                }
                $danfe = new NFePHP\DA\NFe\Danfe(file_get_contents($nota->xml_path));
                $pdf = $danfe->render($this->logoEmitente($this->mapos_model->getEmitente()));
            } else {
                $config = $this->nfe_model->getConfig();
                $emitente = $this->mapos_model->getEmitente();
                $service = new NfseService($config, $emitente);
                $pdf = $service->danfse($nota->chave);
            }

            $this->output
                ->set_content_type('application/pdf')
                ->set_header('Content-Disposition: inline; filename="nota_' . $nota->numero . '.pdf"')
                ->set_output($pdf);
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Falha ao gerar o PDF: ' . $e->getMessage());
            redirect('nfe/gerenciar');
        }
    }

    /**
     * Cancela uma nota autorizada. POST: idNota, justificativa. Retorna JSON.
     */
    public function cancelar()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'dNfe')) {
            return $this->jsonResponse(false, 'Você não tem permissão para cancelar notas fiscais.');
        }

        $idNota = $this->input->post('idNota');
        $justificativa = trim((string) $this->input->post('justificativa'));

        $nota = $this->nfe_model->getNotaById($idNota);
        if (!$nota) {
            return $this->jsonResponse(false, 'Nota não encontrada.');
        }
        if ($nota->status !== 'autorizada') {
            return $this->jsonResponse(false, 'Somente notas autorizadas podem ser canceladas.');
        }

        $config = $this->nfe_model->getConfig();
        $emitente = $this->mapos_model->getEmitente();

        try {
            if ($nota->tipo === 'nfe') {
                $service = new NfeService($config, $emitente);
                $resultado = $service->cancelar($nota->chave, $nota->protocolo, $justificativa);
                if ($resultado['sucesso'] && !empty($resultado['xml'])) {
                    CertificadoHelper::salvarXml("nfe_{$nota->chave}_cancelamento.xml", $resultado['xml']);
                }
            } else {
                $service = new NfseService($config, $emitente);
                $resultado = $service->cancelar($nota->chave, $justificativa);
            }

            if (!$resultado['sucesso']) {
                return $this->jsonResponse(false, 'Cancelamento não homologado: ' . $resultado['motivo']);
            }

            $this->nfe_model->updateNota($nota->idNota, [
                'status' => 'cancelada',
                'motivo' => 'Cancelada: ' . $justificativa,
                'data_cancelamento' => date('Y-m-d H:i:s'),
            ]);

            log_info("Cancelou a nota fiscal nº {$nota->numero} ({$nota->tipo})");

            return $this->jsonResponse(true, 'Nota cancelada com sucesso.');
        } catch (\Throwable $e) {
            log_message('error', 'Falha no cancelamento fiscal: ' . $e->getMessage());

            return $this->jsonResponse(false, $this->traduzErroFiscal($e));
        }
    }

    /**
     * Converte erros técnicos (assinatura/OpenSSL/certificado) em mensagens
     * acionáveis para o usuário; os demais erros passam com a própria mensagem.
     */
    private function traduzErroFiscal(\Throwable $e): string
    {
        $baixo = strtolower($e->getMessage());
        $chaves = ['invalid digest', 'digital envelope', 'assinatura', 'unsupported', 'legacy', 'pkcs12', 'certificad', 'openssl'];
        foreach ($chaves as $chave) {
            if (str_contains($baixo, $chave)) {
                return \Libraries\Fiscal\CertificadoHelper::traduzErroCertificado($e->getMessage());
            }
        }

        return $e->getMessage();
    }

    /**
     * Devolve o logo do emitente como data-URI (base64) para o sped-da (DANFE),
     * ou string vazia se não houver logo/arquivo. O sped-da aceita
     * 'data://text/plain;base64,...' no parâmetro do render().
     */
    private function logoEmitente($emitente)
    {
        if (empty($emitente) || empty($emitente->url_logo)) {
            return '';
        }
        $arquivo = FCPATH . 'assets/uploads/' . basename($emitente->url_logo);
        if (!is_file($arquivo)) {
            return '';
        }

        return 'data://text/plain;base64,' . base64_encode(file_get_contents($arquivo));
    }

    private function jsonResponse($sucesso, $mensagem, array $extra = [])
    {
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array_merge([
                'success' => $sucesso,
                'message' => $mensagem,
            ], $extra)));
    }
}
