<?php

class Fotosatendimento_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Verifica se a tabela existe
     */
    private function tabelaExiste()
    {
        return $this->db->table_exists('os_fotos_atendimento');
    }

    /**
     * Obtém foto por ID
     */
    public function getById($id)
    {
        if (!$this->tabelaExiste()) {
            return null;
        }

        $this->db->select('*');
        $this->db->from('os_fotos_atendimento');
        $this->db->where('idFoto', $id);
        $this->db->limit(1);

        $query = $this->db->get();
        $foto = $query ? $query->row() : null;

        // Atualiza a URL para apontar para o novo endpoint base64
        if ($foto && !empty($foto->imagem_base64)) {
            $foto->url = base_url('index.php/checkin/verFotoDB/' . $foto->idFoto);
        }

        return $foto;
    }

    /**
     * Obtém todas as fotos de uma OS
     */
    public function getByOs($os_id, $etapa = null)
    {
        if (!$this->tabelaExiste()) {
            return [];
        }

        $this->db->select('os_fotos_atendimento.*, usuarios.nome as nome_usuario');
        $this->db->from('os_fotos_atendimento');
        $this->db->join('usuarios', 'usuarios.idUsuarios = os_fotos_atendimento.usuarios_id', 'left');
        $this->db->where('os_fotos_atendimento.os_id', $os_id);

        if ($etapa) {
            $this->db->where('etapa', $etapa);
        }

        $this->db->order_by('data_upload', 'desc');

        $query = $this->db->get();
        $fotos = $query ? $query->result() : [];

        // Atualiza a URL para apontar para o novo endpoint base64
        foreach ($fotos as $foto) {
            if (!empty($foto->imagem_base64)) {
                $foto->url = base_url('index.php/checkin/verFotoDB/' . $foto->idFoto);
            }
        }

        return $fotos;
    }

    /**
     * Obtém fotos por checkin
     */
    public function getByCheckin($checkin_id, $etapa = null)
    {
        if (!$this->tabelaExiste()) {
            return [];
        }

        $this->db->select('*');
        $this->db->from('os_fotos_atendimento');
        $this->db->where('checkin_id', $checkin_id);

        if ($etapa) {
            $this->db->where('etapa', $etapa);
        }

        $this->db->order_by('data_upload', 'desc');

        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    /**
     * Adiciona nova foto
     */
    public function add($data, $returnId = false)
    {
        if (!$this->tabelaExiste()) {
            return false;
        }

        $this->db->insert('os_fotos_atendimento', $data);
        if ($this->db->affected_rows() == '1') {
            if ($returnId == true) {
                return $this->db->insert_id();
            }

            return true;
        }

        return false;
    }

    /**
     * Atualiza foto
     */
    public function edit($data, $id)
    {
        if (!$this->tabelaExiste()) {
            return false;
        }

        $this->db->where('idFoto', $id);
        $this->db->update('os_fotos_atendimento', $data);

        if ($this->db->affected_rows() >= 0) {
            return true;
        }

        return false;
    }


    /**
     * Conta total de fotos
     */
    public function count($os_id = null)
    {
        if (!$this->tabelaExiste()) {
            return 0;
        }

        if ($os_id) {
            $this->db->where('os_id', $os_id);
            return $this->db->count_all_results('os_fotos_atendimento');
        }
        return $this->db->count_all('os_fotos_atendimento');
    }

    /**
     * Conta fotos por etapa
     */
    public function countByEtapa($os_id, $etapa)
    {
        if (!$this->tabelaExiste()) {
            return 0;
        }

        $this->db->where('os_id', $os_id);
        $this->db->where('etapa', $etapa);
        return $this->db->count_all_results('os_fotos_atendimento');
    }

    /**
     * Faz upload de arquivo - agora salva no banco de dados como base64
     */
    public function uploadFoto($arquivo, $os_id, $usuario_id, $checkin_id = null, $etapa = 'durante', $descricao = '')
    {
        // Extensão permitida
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($extensao, $extensoes_permitidas)) {
            return ['error' => 'Tipo de arquivo não permitido. Use JPG, PNG ou GIF.'];
        }

        // Tamanho máximo (5MB)
        $tamanho_maximo = 5 * 1024 * 1024;
        if ($arquivo['size'] > $tamanho_maximo) {
            return ['error' => 'Arquivo muito grande. Tamanho máximo: 5MB'];
        }

        // Lê o arquivo e converte para base64
        $conteudo = file_get_contents($arquivo['tmp_name']);
        if ($conteudo === false) {
            return ['error' => 'Erro ao ler arquivo.'];
        }

        // Determina o mime type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($conteudo);
        if (!$mime) {
            $mime = 'image/jpeg';
        }

        // Converte para base64
        $base64 = 'data:' . $mime . ';base64,' . base64_encode($conteudo);

        // Nome do arquivo para referência
        $nome_arquivo = 'os_' . $os_id . '_' . $etapa . '_' . time() . '_' . rand(1000, 9999) . '.' . $extensao;

        return [
            'arquivo' => $nome_arquivo,
            'path' => $nome_arquivo, // Mantém para compatibilidade
            'url' => base_url('index.php/checkin/verFotoDB/0'), // Será atualizado após salvar
            'tamanho' => $arquivo['size'],
            'tipo' => $extensao,
            'imagem_base64' => $base64,
            'mime_type' => $mime
        ];
    }

    /**
     * Salva foto em base64 - agora retorna dados para salvar no banco
     */
    public function salvarFotoBase64($base64_data, $os_id, $usuario_id, $checkin_id = null, $etapa = 'durante', $descricao = '')
    {
        // Log para debug
        log_message('debug', 'FotosAtendimento: Tamanho recebido: ' . strlen($base64_data));

        // Limpa espaços em branco
        $base64_data = trim($base64_data);

        // Extrair dados da imagem base64
        if (strpos($base64_data, ';base64,') !== false) {
            $partes = explode(';base64,', $base64_data);
            $tipo_imagem = str_replace('data:image/', '', $partes[0]);
            $image_data = base64_decode($partes[1], true);
        } elseif (strpos($base64_data, 'data:image/') === 0) {
            // Caso tenha data:image/ mas sem o ;base64,
            // Extrai o tipo e tenta decodificar o resto
            if (preg_match('/^data:image\/(\w+);base64,(.+)/', $base64_data, $matches)) {
                $tipo_imagem = $matches[1];
                $image_data = base64_decode($matches[2], true);
            } else {
                // Tenta extrair tipo e dados de outra forma
                $tipo_imagem = 'jpeg';
                $comma_pos = strpos($base64_data, ',');
                if ($comma_pos !== false) {
                    $image_data = base64_decode(substr($base64_data, $comma_pos + 1), true);
                } else {
                    $image_data = base64_decode($base64_data, true);
                }
            }
        } else {
            // Assume que é apenas o base64 puro
            $image_data = base64_decode($base64_data, true);
            $tipo_imagem = 'jpeg';
        }

        if ($image_data === false) {
            log_message('error', 'FotosAtendimento: Falha ao decodificar base64. Dados: ' . substr($base64_data, 0, 100));
            return ['error' => 'Dados da imagem inválidos ou corrompidos.'];
        }

        if (strlen($image_data) === 0) {
            log_message('error', 'FotosAtendimento: Imagem decodificada está vazia');
            return ['error' => 'Imagem está vazia.'];
        }

        // Valida tipo de imagem permitido
        $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array(strtolower($tipo_imagem), $tipos_permitidos)) {
            $tipo_imagem = 'jpg'; // Converte para jpg por padrão
        }

        // Determina MIME type
        $mime = 'image/' . $tipo_imagem;
        if ($tipo_imagem === 'jpg') {
            $mime = 'image/jpeg';
        }

        // Nome do arquivo para referência
        $nome_arquivo = 'os_' . $os_id . '_' . $etapa . '_' . time() . '_' . rand(1000, 9999) . '.' . $tipo_imagem;

        log_message('debug', 'FotosAtendimento: Foto processada para DB: ' . $nome_arquivo);

        return [
            'arquivo' => $nome_arquivo,
            'path' => $nome_arquivo, // Mantém para compatibilidade
            'url' => base_url('index.php/checkin/verFotoDB/0'), // Será atualizado após salvar
            'tamanho' => strlen($image_data),
            'tipo' => $tipo_imagem,
            'imagem_base64' => $base64_data,
            'mime_type' => $mime
        ];
    }

    /**
     * Obtém fotos para relatório
     */
    public function getParaRelatorio($data_inicio, $data_fim, $usuario_id = null)
    {
        if (!$this->tabelaExiste()) {
            return [];
        }

        $this->db->select('os_fotos_atendimento.*, usuarios.nome as nome_usuario, os.status as os_status');
        $this->db->from('os_fotos_atendimento');
        $this->db->join('usuarios', 'usuarios.idUsuarios = os_fotos_atendimento.usuarios_id', 'left');
        $this->db->join('os', 'os.idOs = os_fotos_atendimento.os_id', 'left');
        $this->db->where('DATE(os_fotos_atendimento.data_upload) >=', $data_inicio);
        $this->db->where('DATE(os_fotos_atendimento.data_upload) <=', $data_fim);

        if ($usuario_id) {
            $this->db->where('os_fotos_atendimento.usuarios_id', $usuario_id);
        }

        $this->db->order_by('os_fotos_atendimento.data_upload', 'desc');

        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    /**
     * Obtém a imagem em base64 do banco de dados
     */
    public function getImagemBase64($foto_id)
    {
        if (!$this->tabelaExiste()) {
            return null;
        }

        $this->db->select('imagem_base64, mime_type, arquivo');
        $this->db->from('os_fotos_atendimento');
        $this->db->where('idFoto', $foto_id);
        $this->db->limit(1);

        $query = $this->db->get();
        return $query ? $query->row() : null;
    }

    /**
     * Remove foto - agora só remove do banco, não do sistema de arquivos
     */
    public function delete($id)
    {
        if (!$this->tabelaExiste()) {
            return false;
        }

        // Não precisa mais remover arquivo físico
        // Apenas remove do banco de dados
        $this->db->where('idFoto', $id);
        $this->db->delete('os_fotos_atendimento');

        if ($this->db->affected_rows() == '1') {
            return true;
        }

        return false;
    }

    /**
     * Remove todas as fotos de uma OS
     */
    public function deleteByOs($os_id)
    {
        if (!$this->tabelaExiste()) {
            return false;
        }

        $this->db->where('os_id', $os_id);
        return $this->db->delete('os_fotos_atendimento');
    }
}
