<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Configuração dos modelos (templates) de mensagens de WhatsApp.
 * Configurações > Modelos de WhatsApp.
 */
class Whatsapptemplates extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar modelos de WhatsApp.');
            redirect(base_url());
        }

        $this->load->model('whatsapp_templates_model');
    }

    public function index()
    {
        if (! $this->whatsapp_templates_model->suportado()) {
            $this->session->set_flashdata('error', 'Recurso indisponível: rode updates/update_whatsapp_templates.sql.');
        }

        $this->data['menuConfiguracoes'] = 'WhatsappTemplates';
        $this->data['templates'] = $this->whatsapp_templates_model->getAll();
        $this->data['view'] = 'whatsapptemplates/index';

        return $this->layout();
    }

    public function novo()
    {
        $this->data['menuConfiguracoes'] = 'WhatsappTemplates';
        $this->data['view'] = 'whatsapptemplates/novo';

        return $this->layout();
    }

    public function criar()
    {
        $nome = trim((string) $this->input->post('nome'));
        if ($nome === '') {
            $this->session->set_flashdata('error', 'Informe o nome do modelo.');
            redirect(site_url('whatsapptemplates/novo'));
        }

        $tags = trim((string) $this->input->post('tags'));
        $slug = $this->whatsapp_templates_model->gerarSlug($nome);

        $id = $this->whatsapp_templates_model->create([
            'slug' => $slug,
            'nome' => $nome,
            'descricao' => trim((string) $this->input->post('descricao')) ?: null,
            'tags' => $tags !== '' ? $tags : Whatsapp_templates_model::tagsPadraoOs(),
            'conteudo' => (string) $this->input->post('conteudo'),
            'ativo' => $this->input->post('ativo') ? 1 : 0,
        ]);

        if ($id) {
            log_info('Criou o modelo de WhatsApp: ' . $slug);
            $this->session->set_flashdata('success', 'Modelo criado com sucesso!');
            redirect(site_url('whatsapptemplates/editar/' . $slug));
        }

        $this->session->set_flashdata('error', 'Não foi possível criar o modelo.');
        redirect(site_url('whatsapptemplates/novo'));
    }

    public function excluir($slug = null)
    {
        if ($slug) {
            if (in_array($slug, Whatsapp_templates_model::slugsCore(), true)) {
                $this->session->set_flashdata('error', 'Os modelos padrão do sistema não podem ser excluídos.');
            } elseif ($this->whatsapp_templates_model->delete($slug)) {
                log_info('Excluiu o modelo de WhatsApp: ' . $slug);
                $this->session->set_flashdata('success', 'Modelo excluído.');
            }
        }
        redirect(site_url('whatsapptemplates'));
    }

    public function editar($slug = null)
    {
        $tpl = $slug ? $this->whatsapp_templates_model->getBySlug($slug) : null;
        if (! $tpl) {
            $this->session->set_flashdata('error', 'Modelo não encontrado.');
            redirect(site_url('whatsapptemplates'));
        }

        $this->data['menuConfiguracoes'] = 'WhatsappTemplates';
        $this->data['tpl'] = $tpl;
        $this->data['view'] = 'whatsapptemplates/editar';

        return $this->layout();
    }

    public function salvar()
    {
        $slug = $this->input->post('slug');
        $tpl = $slug ? $this->whatsapp_templates_model->getBySlug($slug) : null;
        if (! $tpl) {
            $this->session->set_flashdata('error', 'Modelo não encontrado.');
            redirect(site_url('whatsapptemplates'));
        }

        $this->whatsapp_templates_model->update($slug, [
            'conteudo' => (string) $this->input->post('conteudo'),
            'ativo' => $this->input->post('ativo') ? 1 : 0,
        ]);

        log_info('Alterou o modelo de WhatsApp: ' . $slug);
        $this->session->set_flashdata('success', 'Modelo salvo com sucesso!');
        redirect(site_url('whatsapptemplates/editar/' . $slug));
    }
}
