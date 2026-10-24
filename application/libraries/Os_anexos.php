<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Anexos da OS: upload (com miniatura para imagens), exclusao e download.
 *
 * Extraido do controller Os (ADR-006, padrao do ADR-003). Alem da extracao:
 * o id da OS e validado antes de virar nome de pasta (antes "../" no POST
 * gravava fora de assets/anexos) e anexo inexistente nao derruba a pagina.
 */
class Os_anexos
{
    private const TIPOS_PERMITIDOS = 'jpg|png|gif|jpeg|JPG|PNG|GIF|JPEG|pdf|PDF|cdr|CDR|docx|DOCX|txt';

    /** @var CI_Controller */
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->model('os_model');
    }

    /**
     * Grava os arquivos de $_FILES['userfile'] (multiplos) como anexos da OS.
     *
     * @return array{result: bool, mensagem: string, errors?: array}
     */
    public function anexar($osId)
    {
        $osId = (int) $osId;
        if ($osId <= 0 || ! $this->ci->os_model->getById($osId)) {
            return ['result' => false, 'mensagem' => 'OS não encontrada.'];
        }
        if (empty($_FILES['userfile']['name'])) {
            return ['result' => false, 'mensagem' => 'Nenhum arquivo enviado.'];
        }

        $this->ci->load->library('upload');
        $this->ci->load->library('image_lib');

        $subpasta = 'assets' . DIRECTORY_SEPARATOR . 'anexos' . DIRECTORY_SEPARATOR . date('m-Y') . DIRECTORY_SEPARATOR . 'OS-' . $osId;
        $directory = FCPATH . $subpasta;
        if (! is_dir($directory . DIRECTORY_SEPARATOR . 'thumbs') && ! mkdir($directory . DIRECTORY_SEPARATOR . 'thumbs', 0755, true)) {
            return ['result' => false, 'mensagem' => 'Não foi possível criar a pasta de anexos.'];
        }

        $this->ci->upload->initialize([
            'upload_path' => $directory,
            'allowed_types' => self::TIPOS_PERMITIDOS,
            'max_size' => 0,
        ]);

        // userfile[] -> file_1, file_2... (o CI so faz upload de um campo por vez)
        foreach ($_FILES['userfile'] as $key => $val) {
            $i = 1;
            foreach ((array) $val as $v) {
                $_FILES['file_' . $i][$key] = $v;
                $i++;
            }
        }
        unset($_FILES['userfile']);

        $error = [];
        foreach (array_keys($_FILES) as $campo) {
            if (strpos($campo, 'file_') !== 0) {
                continue;
            }
            if (! $this->ci->upload->do_upload($campo)) {
                $error['upload'][] = $this->ci->upload->display_errors();
                continue;
            }
            $dados = $this->ci->upload->data();

            // Nome aleatorio mantendo a extensao original
            $novoNome = uniqid() . '.' . pathinfo($dados['file_name'], PATHINFO_EXTENSION);
            $novoCaminho = $dados['file_path'] . $novoNome;
            rename($dados['full_path'], $novoCaminho);

            $miniatura = '';
            if ($dados['is_image'] == 1) {
                $this->ci->image_lib->initialize([
                    'source_image' => $novoCaminho,
                    'new_image' => $dados['file_path'] . 'thumbs' . DIRECTORY_SEPARATOR . 'thumb_' . $novoNome,
                    'width' => 200,
                    'height' => 125,
                ]);
                if (! $this->ci->image_lib->resize()) {
                    $error['resize'][] = $this->ci->image_lib->display_errors();
                    continue;
                }
                $miniatura = 'thumb_' . $novoNome;
            }

            if (! $this->ci->os_model->anexar($osId, $novoNome, base_url($subpasta), $miniatura, $directory)) {
                $error['db'][] = 'Erro ao inserir no banco de dados.';
            }
        }

        if ($error) {
            return ['result' => false, 'mensagem' => 'Ocorreu um erro ao processar os arquivos.', 'errors' => $error];
        }
        log_info('Adicionou anexo(s) a uma OS. ID (OS): ' . $osId);

        return ['result' => true, 'mensagem' => 'Arquivo(s) anexado(s) com sucesso.'];
    }

    /**
     * Apaga o anexo (arquivo, miniatura e registro).
     *
     * @return array{result: bool, mensagem: string}
     */
    public function excluir($id)
    {
        $anexo = $this->buscar($id);
        if (! $anexo) {
            return ['result' => false, 'mensagem' => 'Anexo não encontrado.'];
        }

        foreach ([$anexo->anexo, $anexo->thumb ? 'thumbs' . DIRECTORY_SEPARATOR . $anexo->thumb : null] as $arquivo) {
            $caminho = $arquivo ? $anexo->path . DIRECTORY_SEPARATOR . $arquivo : null;
            if ($caminho && is_file($caminho)) {
                unlink($caminho);
            }
        }

        if (! $this->ci->os_model->delete('anexos', 'idAnexos', $anexo->idAnexos)) {
            return ['result' => false, 'mensagem' => 'Erro ao tentar excluir anexo.'];
        }
        log_info('Removeu anexo de uma OS. ID (OS): ' . $anexo->os_id);

        return ['result' => true, 'mensagem' => 'Anexo excluído com sucesso.'];
    }

    /** Caminho completo do arquivo do anexo, ou null se nao existir. */
    public function arquivo($id)
    {
        $anexo = $this->buscar($id);
        if (! $anexo) {
            return null;
        }
        $caminho = $anexo->path . DIRECTORY_SEPARATOR . $anexo->anexo;

        return is_file($caminho) ? $caminho : null;
    }

    private function buscar($id)
    {
        if (! is_numeric($id) || (int) $id <= 0) {
            return null;
        }

        return $this->ci->db->where('idAnexos', (int) $id)->get('anexos', 1)->row();
    }
}
