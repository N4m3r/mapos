<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Importação de pontos de um projeto por planilha (CSV, ou XLSX se PhpSpreadsheet existir).
 * Fluxo: previa (upload + validação) -> confirmar (grava em transação).
 * Permissão: eObras.
 */
class Obraimportar extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->model('obras_model');
        $this->load->model('obra_pontos_model');
        require_once APPPATH . 'libraries/Pontos_importacao.php';
        $this->data['menuObras'] = 'Obras';
    }

    public function modelo()
    {
        $this->precisa('eObras', 'Você não tem permissão para editar Projetos.');
        $this->output
            ->set_content_type('text/csv', 'UTF-8')
            ->set_header('Content-Disposition: attachment; filename="modelo-pontos.csv"')
            ->set_output(Pontos_importacao::modeloCsv());
    }

    public function previa()
    {
        $this->precisa('eObras', 'Você não tem permissão para editar Projetos.');
        $obra = $this->obraOuSai((int) $this->input->post('obra_id'));
        $volta = 'obras/visualizar/' . (int) $obra->idObra . '#pontos';
        $modo = $this->input->post('existentes') === 'atualizar' ? 'atualizar' : 'pular';

        $erro = null;
        $arq = $_FILES['planilha'] ?? null;
        if (! $arq || $arq['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file($arq['tmp_name'])) {
            $erro = 'Selecione uma planilha (limite de 2 MB).';
        } elseif ($arq['size'] > Pontos_importacao::MAX_BYTES) {
            $erro = 'A planilha excede 2 MB.';
        }
        $ext = $arq ? strtolower(pathinfo((string) $arq['name'], PATHINFO_EXTENSION)) : '';
        $xlsxOk = class_exists('\PhpOffice\PhpSpreadsheet\IOFactory');
        if (! $erro && ! in_array($ext, $xlsxOk ? ['csv', 'xlsx'] : ['csv'], true)) {
            $erro = 'Formato não aceito. Envie ' . ($xlsxOk ? 'um arquivo .csv ou .xlsx.' : 'um arquivo .csv.');
        }
        if (! $erro) {
            $mime = function_exists('mime_content_type') ? (string) mime_content_type($arq['tmp_name']) : 'text/plain';
            $okCsv = ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel', 'inode/x-empty'];
            $okXlsx = ['application/zip', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/octet-stream'];
            if (! in_array($mime, $ext === 'csv' ? $okCsv : $okXlsx, true)) {
                $erro = 'Tipo de arquivo não aceito (' . $mime . ').';
            }
        }
        $lido = null;
        if (! $erro) {
            $lido = $ext === 'csv'
                ? Pontos_importacao::lerCsv((string) file_get_contents($arq['tmp_name']))
                : $this->lerXlsx($arq['tmp_name']);
            $erro = $lido['erro'];
        }
        if ($erro) {
            $this->session->set_flashdata('error', $erro);
            redirect($volta);
        }

        $tipos = Obra_pontos_model::tipos();
        $linhas = [];
        foreach ($lido['linhas'] as $reg) {
            $v = Pontos_importacao::validarLinha($reg, $tipos);
            $v['_n'] = $reg['_n'];
            $linhas[] = $v;
        }
        $classif = Pontos_importacao::classificar($linhas, $this->existentes($obra->idObra), $modo);

        $token = bin2hex(random_bytes(16));
        $this->limparTemporarios();
        file_put_contents($this->caminho($token), json_encode($linhas, JSON_UNESCAPED_UNICODE));
        $this->session->set_userdata('imp_pontos', ['token' => $token, 'obra_id' => (int) $obra->idObra]);

        $this->data['obra'] = $obra;
        $this->data['linhas'] = $classif;
        $this->data['contagem'] = Pontos_importacao::contar($classif);
        $this->data['modo'] = $modo;
        $this->data['tipos'] = $tipos;
        $this->data['obra_menu'] = 'obras';
        $this->data['view'] = 'obras/importar_pontos_previa';
        return $this->layout();
    }

    public function confirmar()
    {
        $this->precisa('eObras', 'Você não tem permissão para editar Projetos.');
        $obra = $this->obraOuSai((int) $this->input->post('obra_id'));
        $volta = 'obras/visualizar/' . (int) $obra->idObra . '#pontos';
        $sess = (array) $this->session->userdata('imp_pontos');
        $token = (string) $this->input->post('token');
        $arquivo = preg_match('/^[a-f0-9]{32}$/', $token) ? $this->caminho($token) : '';
        if (! $sess || ($sess['token'] ?? '') !== $token || (int) ($sess['obra_id'] ?? 0) !== (int) $obra->idObra
            || ! $arquivo || ! is_file($arquivo)) {
            $this->session->set_flashdata('error', 'A prévia expirou. Envie a planilha novamente.');
            redirect($volta);
        }
        $linhas = json_decode((string) file_get_contents($arquivo), true);
        @unlink($arquivo);
        $this->session->unset_userdata('imp_pontos');
        if (! is_array($linhas)) {
            $this->session->set_flashdata('error', 'Prévia inválida. Envie a planilha novamente.');
            redirect($volta);
        }

        $modo = $this->input->post('existentes') === 'atualizar' ? 'atualizar' : 'pular';
        // Reclassifica contra o estado atual do banco antes de gravar
        $classif = Pontos_importacao::classificar($linhas, $this->existentes($obra->idObra), $modo);
        $uid = (int) $this->session->userdata('id_admin');
        $agora = date('Y-m-d H:i:s');
        $novos = $atualizados = 0;

        $this->db->trans_start();
        foreach ($classif as $l) {
            $d = $l['dados'];
            if ($l['status'] === 'novo') {
                $d['obra_id'] = (int) $obra->idObra;
                $d['status'] = 'previsto';
                $d['data_cadastro'] = $agora;
                $this->obra_pontos_model->addPonto($d);
                $novos++;
            } elseif ($l['status'] === 'atualizar') {
                if (! empty($l['tipo_vazio'])) {
                    unset($d['tipo']);
                }
                // Célula vazia não apaga o que já está cadastrado
                $d = array_filter($d, static function ($v) {
                    return $v !== '';
                });
                unset($d['identificacao']);
                $d['atualizado_por'] = $uid ?: null;
                $d['atualizado_em'] = $agora;
                $this->obra_pontos_model->editPonto((int) $l['idPonto'], (int) $obra->idObra, $d);
                $atualizados++;
            }
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            $this->session->set_flashdata('error', 'Falha ao gravar a importação; nada foi salvo.');
            redirect($volta);
        }
        $this->obra_pontos_model->recalcularProgressoObra((int) $obra->idObra);
        $c = Pontos_importacao::contar($classif);
        log_info('Importou pontos na obra ' . (int) $obra->idObra . ": $novos novos, $atualizados atualizados");
        $this->session->set_flashdata('success', "Importação concluída: $novos novo(s), $atualizados atualizado(s), {$c['pular']} pulado(s), {$c['erro']} com erro (não importado(s)).");
        redirect($volta);
    }

    private function obraOuSai($id)
    {
        $obra = $id ? $this->obras_model->getObra($id) : null;
        if (! $obra) {
            $this->session->set_flashdata('error', 'Projeto não encontrado.');
            redirect('obras');
        }

        return $obra;
    }

    private function existentes($obra_id)
    {
        if (! $this->db->table_exists('obra_pontos')) {
            return [];
        }

        return $this->db->select('idPonto, identificacao, ip')->where('obra_id', (int) $obra_id)
            ->get('obra_pontos')->result_array();
    }

    private function caminho($token)
    {
        return APPPATH . 'cache/imp_pontos_' . $token . '.json';
    }

    /** Apaga prévias abandonadas há mais de 2 horas. */
    private function limparTemporarios()
    {
        foreach (glob(APPPATH . 'cache/imp_pontos_*.json') ?: [] as $f) {
            if (filemtime($f) < time() - 7200) {
                @unlink($f);
            }
        }
    }

    private function lerXlsx($caminho)
    {
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $matriz = $reader->load($caminho)->getActiveSheet()->toArray(null, true, false, false);
        } catch (\Throwable $e) {
            return ['linhas' => [], 'erro' => 'Não foi possível ler o arquivo .xlsx.'];
        }
        if (count($matriz) > Pontos_importacao::MAX_LINHAS + 1) {
            return ['linhas' => [], 'erro' => 'A planilha tem mais de ' . Pontos_importacao::MAX_LINHAS . ' linhas.'];
        }

        return Pontos_importacao::linhasParaRegistros($matriz);
    }
}
