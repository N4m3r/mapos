<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Emailtemplates extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cEmail')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar e-mails.');
            redirect(base_url());
        }

        $this->load->model('email_templates_model');
        $this->load->library('emailtemplate');
    }

    public function index()
    {
        $this->data['menuConfiguracoes'] = 'Email';
        $this->data['results'] = $this->email_templates_model->getAll();
        $this->data['layout'] = $this->emailtemplate->getLayout();
        $this->data['css'] = $this->emailtemplate->getCss();

        $this->data['view'] = 'emails/templates';

        return $this->layout();
    }

    public function editar($id = null)
    {
        if (! $id || ! is_numeric($id)) {
            $this->session->set_flashdata('error', 'Modelo não encontrado.');
            redirect(site_url('emailtemplates'));
        }

        $template = $this->email_templates_model->getById($id);
        if (! $template) {
            $this->session->set_flashdata('error', 'Modelo não encontrado.');
            redirect(site_url('emailtemplates'));
        }

        $this->data['menuConfiguracoes'] = 'Email';
        $this->data['template'] = $template;
        $this->data['tagsDisponiveis'] = $this->parseTags($template->tags);

        $this->data['view'] = 'emails/editarTemplate';

        return $this->layout();
    }

    public function salvar()
    {
        $id = $this->input->post('id');
        $template = $id && is_numeric($id) ? $this->email_templates_model->getById($id) : null;
        if (! $template) {
            $this->session->set_flashdata('error', 'Modelo não encontrado.');
            redirect(site_url('emailtemplates'));
        }

        $this->load->library('form_validation');
        $this->form_validation->set_rules('assunto', 'Assunto', 'required|trim');

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata('error', validation_errors());
            redirect(site_url('emailtemplates/editar/' . $id));
        }

        $this->email_templates_model->update($id, [
            'assunto' => $this->input->post('assunto'),
            'corpo' => $this->input->post('corpo'),
            'ativo' => $this->input->post('ativo') ? 1 : 0,
        ]);

        log_info('Alterou o modelo de e-mail: ' . $template->slug);
        $this->session->set_flashdata('success', 'Modelo de e-mail salvo com sucesso!');
        redirect(site_url('emailtemplates/editar/' . $id));
    }

    public function layout()
    {
        $this->data['menuConfiguracoes'] = 'Email';
        $this->data['layout'] = $this->emailtemplate->getLayout();
        $this->data['css'] = $this->emailtemplate->getCss();

        $this->data['view'] = 'emails/layoutTemplate';

        return $this->layout();
    }

    public function salvarLayout()
    {
        $this->load->model('mapos_model');

        $ok = $this->mapos_model->saveConfiguracao([
            'email_layout' => $this->input->post('email_layout'),
            'email_css' => $this->input->post('email_css'),
        ]);

        if ($ok) {
            log_info('Alterou o layout global de e-mail.');
            $this->session->set_flashdata('success', 'Layout dos e-mails salvo com sucesso!');
        } else {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao salvar o layout.');
        }
        redirect(site_url('emailtemplates/layout'));
    }

    /**
     * Renderiza uma pré-visualização com dados de exemplo. Aceita o corpo/
     * assunto vindos do formulário (POST) para refletir edições não salvas.
     */
    public function preview()
    {
        $slug = $this->input->post('slug') ?: 'os';

        $fake = (object) [
            'slug' => $slug,
            'ativo' => 1,
            'assunto' => $this->input->post('assunto') ?: 'Pré-visualização',
            'corpo' => $this->input->post('corpo') ?: '',
        ];

        // Layout/CSS opcionais do formulário (tela de layout), senão os salvos.
        if ($this->input->post('email_layout') !== null) {
            $fake->corpo = '{{conteudo}}';
        }

        $resultado = $this->emailtemplate->renderTemplate($fake, $this->sampleContext());

        // Quando o preview vem da tela de layout, injeta layout/css do form.
        if ($this->input->post('email_layout') !== null) {
            $tags = $this->emailtemplate->buildTags($this->sampleContext());
            $conteudo = '<p>Olá, <strong>' . ($tags['cliente_nome'] ?? '') . '</strong>! Este é um exemplo de conteúdo dentro do layout.</p>'
                . '<p>Use este espaço para conferir cabeçalho, rodapé e estilos.</p>';
            $documento = str_replace(
                ['{{css}}', '{{conteudo}}'],
                [$this->input->post('email_css'), $conteudo],
                $this->input->post('email_layout')
            );
            $this->output->set_content_type('text/html; charset=utf-8')->set_output($this->emailtemplate->applyTags($documento, $tags));

            return;
        }

        $this->output->set_content_type('text/html; charset=utf-8')->set_output($resultado['corpo']);
    }

    private function parseTags($tags)
    {
        if (! $tags) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $tags))));
    }

    private function sampleContext()
    {
        $this->load->model('mapos_model');
        $emitente = $this->mapos_model->getEmitente();

        $cliente = (object) [
            'nomeCliente' => 'Maria Oliveira',
            'email' => 'maria@exemplo.com',
            'celular' => '(11) 98888-7777',
            'telefone' => '(11) 3333-4444',
            'rua' => 'Rua das Flores', 'numero' => '123', 'bairro' => 'Centro',
            'cidade' => 'São Paulo', 'estado' => 'SP',
        ];

        $os = (object) array_merge((array) $cliente, [
            'idOs' => 1042,
            'status' => 'Finalizado',
            'dataInicial' => date('Y-m-d'),
            'dataFinal' => date('Y-m-d'),
            'garantia' => '90 dias',
            'descricaoProduto' => 'Notebook Acme X15',
            'defeito' => 'Não liga.',
            'observacoes' => 'Cliente relatou queda.',
            'laudoTecnico' => 'Troca da placa de energia.',
            'desconto' => 0,
            'valor_desconto' => 0,
            'nome' => 'Técnico Responsável',
        ]);

        $cobranca = (object) array_merge((array) $cliente, [
            'idCobranca' => 88,
            'total' => 25000,
            'expire_at' => date('Y-m-d', strtotime('+5 days')),
            'message' => 'Pagamento referente à NFS-e nº 123 - OS #1042',
            'link' => 'https://pagamento.exemplo.com/boleto/abc',
            'pdf' => 'https://pagamento.exemplo.com/boleto/abc.pdf',
            'barcode' => '34191.79001 01043.510047 91020.150008 8 91230000025000',
            'pix' => '00020126360014BR.GOV.BCB.PIX0114+551199999999952040000530398654041.005802BR5909Exemplo6009SAO PAULO62070503***6304ABCD',
        ]);

        return [
            'emitente' => $emitente,
            'cliente' => $cliente,
            'os' => $os,
            'cobranca' => $cobranca,
            'produtos' => [
                (object) ['descricao' => 'Placa de energia', 'quantidade' => 1, 'preco' => 180.00, 'precoVenda' => 180.00, 'subTotal' => 180.00],
            ],
            'servicos' => [
                (object) ['nome' => 'Mão de obra', 'quantidade' => 1, 'preco' => 70.00, 'precoVenda' => 70.00],
            ],
        ];
    }
}
