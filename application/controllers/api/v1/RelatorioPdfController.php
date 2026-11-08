<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__ . '/AreaApiController.php';

/**
 * API v1 — PDF da OS e do projeto para o app (imprimir, salvar, compartilhar).
 *
 * A web imprime pelo navegador (window.print); o app nao tem essa opcao, entao
 * o servidor gera o PDF com o mPDF ja usado nos relatorios e devolve o binario.
 *
 * Acesso, sempre conferido contra o usuario do token:
 * - OS: quem tem vOs, ou o tecnico da OS (responsavel ou do projeto dela).
 * - Projeto: quem tem vObras, ou o tecnico com acesso ao projeto.
 */
class RelatorioPdfController extends AreaApiController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('os_model');
        $this->load->model('mapos_model');
        $this->load->model('obras_model');
        $this->load->model('obra_pontos_model');
        $this->load->helper('mpdf');
    }

    /** GET /api/v1/os/{id}/pdf */
    public function os_get($id = null)
    {
        $user = $this->logged_user();
        $this->user = $user;
        $uid = (int) $user->usuario->idUsuarios;
        $osId = (int) $id;

        $pode = $this->permission->checkPermission($user->level, 'vOs')
            || ($this->permission->checkPermission($user->level, 'vTecnicoDashboard') && $this->osDoTecnico($osId, $uid));
        if (! $pode) {
            $this->naoEncontrada('OS não encontrada ou sem acesso.');
        }

        $os = $osId ? $this->os_model->getById($osId) : null;
        if (! $os) {
            $this->naoEncontrada('OS não encontrada.');
        }

        $html = $this->load->view('api/relatorio_os_pdf', [
            'result' => $os,
            'produtos' => $this->os_model->getProdutos($osId),
            'servicos' => $this->os_model->getServicos($osId),
            'emitente' => $this->mapos_model->getEmitente(),
        ], true);

        $this->servirPdf($html, 'OS-' . str_pad((string) $osId, 4, '0', STR_PAD_LEFT));
    }

    /** GET /api/v1/projetos/{id}/pdf — o mesmo as-built impresso na web. */
    public function projeto_get($id = null)
    {
        $user = $this->logged_user();
        $this->user = $user;
        $uid = (int) $user->usuario->idUsuarios;
        $obraId = (int) $id;

        $pode = $this->permission->checkPermission($user->level, 'vObras')
            || ($this->permission->checkPermission($user->level, 'vTecnicoDashboard')
                && $obraId && $this->obras_model->usuarioTemAcesso($obraId, $uid));
        $obra = ($pode && $obraId) ? $this->obras_model->getObra($obraId) : null;
        if (! $obra) {
            $this->naoEncontrada('Projeto não encontrado ou sem acesso.');
        }

        $pontos = $this->obra_pontos_model->getPontos($obraId);
        $html = $this->load->view('obras/asbuilt', [
            'obra' => $obra,
            'pontos' => $pontos,
            'resumo' => Obra_pontos_model::resumo($pontos),
            'tipos' => Obra_pontos_model::tipos(),
            'status_lista' => Obra_pontos_model::statusLista(),
            'testes_pontos' => $this->obra_pontos_model->getUltimosTestes($obraId),
            'emitente' => $this->mapos_model->getEmitente(),
            'com_fotos' => false,
            'fotos_pontos' => [],
        ], true);

        // Botao e script da pagina web nao tem lugar no PDF.
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
        $html = preg_replace('#<button\b[^>]*>.*?</button>#is', '', $html);

        $this->servirPdf($html, 'Projeto-' . $obraId, true);
    }

    /** Gera o PDF num arquivo temporario, envia o binario e apaga o arquivo. */
    private function servirPdf($html, $nome, $paisagem = false)
    {
        $nome = preg_replace('/[^A-Za-z0-9_-]/', '', $nome) . '-' . bin2hex(random_bytes(4));
        $caminho = pdf_create($html, $nome, false, $paisagem);
        $bin = is_string($caminho) && is_file($caminho) ? file_get_contents($caminho) : false;
        if (is_string($caminho) && is_file($caminho)) {
            unlink($caminho);
        }
        if ($bin === false) {
            $this->response(['status' => false, 'message' => 'Não foi possível gerar o PDF.'], REST_Controller::HTTP_INTERNAL_ERROR);
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . preg_replace('/-[0-9a-f]{8}$/', '', $nome) . '.pdf"');
        header('Content-Length: ' . strlen($bin));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        echo $bin;
        exit;
    }
}
