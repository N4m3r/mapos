<?php

class Assinaturas_model extends CI_Model
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
        return $this->db->table_exists('os_assinaturas');
    }

    /**
     * Obtém assinatura por ID
     */
    public function getById($id)
    {
        if (!$this->tabelaExiste()) {
            return null;
        }

        $this->db->select('*');
        $this->db->from('os_assinaturas');
        $this->db->where('idAssinatura', $id);
        $this->db->limit(1);

        $query = $this->db->get();

        if (!$query || $query->num_rows() === 0) {
            return null;
        }

        $resultado = $query->row();

        // Adicionar URL de visualização se for base64
        if (isset($resultado->assinatura) && strpos($resultado->assinatura, 'BASE64:') === 0) {
            $resultado->url_visualizacao = base_url('index.php/checkin/verAssinatura/' . $resultado->idAssinatura);
            $resultado->is_base64 = true;
        } else {
            $resultado->url_visualizacao = base_url($resultado->assinatura);
            $resultado->is_base64 = false;
        }

        return $resultado;
    }

    /**
     * Obtém todas as assinaturas de uma OS
     */
    public function getByOs($os_id)
    {
        if (!$this->tabelaExiste()) {
            log_message('error', 'Assinaturas_model::getByOs - Tabela nao existe');
            return [];
        }

        $this->db->select('*');
        $this->db->from('os_assinaturas');
        $this->db->where('os_id', $os_id);
        $this->db->order_by('data_assinatura', 'asc');

        $query = $this->db->get();

        log_message('info', 'Assinaturas_model::getByOs - Query: ' . $this->db->last_query());
        log_message('info', 'Assinaturas_model::getByOs - Resultados: ' . ($query ? $query->num_rows() : 0));

        if (!$query || $query->num_rows() === 0) {
            return [];
        }

        $resultados = $query->result();

        // Adicionar URLs de visualização
        foreach ($resultados as $resultado) {
            if (isset($resultado->assinatura) && strpos($resultado->assinatura, 'BASE64:') === 0) {
                $resultado->url_visualizacao = base_url('index.php/checkin/verAssinatura/' . $resultado->idAssinatura);
                $resultado->is_base64 = true;
            } else {
                $resultado->url_visualizacao = base_url($resultado->assinatura);
                $resultado->is_base64 = false;
            }
        }

        return $resultados;
    }

    /**
     * Obtém assinatura por tipo
     */
    public function getByTipo($os_id, $tipo)
    {
        if (!$this->tabelaExiste()) {
            return null;
        }

        $this->db->select('*');
        $this->db->from('os_assinaturas');
        $this->db->where('os_id', $os_id);
        $this->db->where('tipo', $tipo);
        $this->db->limit(1);

        $query = $this->db->get();
        return $query ? $query->row() : null;
    }

    /**
     * Obtém assinatura por checkin
     */
    public function getByCheckin($checkin_id, $tipo = null)
    {
        if (!$this->tabelaExiste()) {
            return [];
        }

        $this->db->select('*');
        $this->db->from('os_assinaturas');
        $this->db->where('checkin_id', $checkin_id);

        if ($tipo) {
            $this->db->where('tipo', $tipo);
        }

        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    /**
     * Verifica se existe assinatura
     */
    public function existeAssinatura($os_id, $tipo)
    {
        if (!$this->tabelaExiste()) {
            return false;
        }

        $this->db->where('os_id', $os_id);
        $this->db->where('tipo', $tipo);
        $query = $this->db->get('os_assinaturas');

        return $query && $query->num_rows() > 0;
    }

    /**
     * Adiciona nova assinatura
     */
    public function add($data, $returnId = false)
    {
        if (!$this->tabelaExiste()) {
            log_message('error', 'Assinaturas_model::add - Tabela nao existe');
            return false;
        }

        log_message('info', 'Assinaturas_model::add - Inserindo dados: ' . print_r($data, true));

        $this->db->insert('os_assinaturas', $data);
        $affected = $this->db->affected_rows();
        $insert_id = $this->db->insert_id();

        log_message('info', 'Assinaturas_model::add - Linhas afetadas: ' . $affected . ', ID: ' . $insert_id);

        if ($affected == '1') {
            if ($returnId == true) {
                return $insert_id;
            }

            return true;
        }

        return false;
    }

    /**
     * Atualiza assinatura
     */
    public function edit($data, $id)
    {
        if (!$this->tabelaExiste()) {
            return false;
        }

        $this->db->where('idAssinatura', $id);
        $this->db->update('os_assinaturas', $data);

        if ($this->db->affected_rows() >= 0) {
            return true;
        }

        return false;
    }

    /**
     * Remove assinatura
     */
    public function delete($id)
    {
        if (!$this->tabelaExiste()) {
            return false;
        }

        // Primeiro obtém o arquivo para deletar fisicamente
        $assinatura = $this->getById($id);

        if ($assinatura && isset($assinatura->assinatura) && file_exists($assinatura->assinatura)) {
            @unlink($assinatura->assinatura);
        }

        $this->db->where('idAssinatura', $id);
        $this->db->delete('os_assinaturas');

        if ($this->db->affected_rows() == '1') {
            return true;
        }

        return false;
    }

    /**
     * Remove todas as assinaturas de uma OS
     */
    public function deleteByOs($os_id)
    {
        if (!$this->tabelaExiste()) {
            return false;
        }

        // Obtém arquivos para deletar fisicamente
        $assinaturas = $this->getByOs($os_id);
        if (is_array($assinaturas)) {
            foreach ($assinaturas as $assinatura) {
                if (isset($assinatura->assinatura) && file_exists($assinatura->assinatura)) {
                    @unlink($assinatura->assinatura);
                }
            }
        }

        $this->db->where('os_id', $os_id);
        return $this->db->delete('os_assinaturas');
    }

    /**
     * Conta total de assinaturas
     */
    public function count()
    {
        if (!$this->tabelaExiste()) {
            return 0;
        }

        return $this->db->count_all('os_assinaturas');
    }

    /**
     * Obtém assinaturas para impressão
     */
    public function getParaImpressao($os_id)
    {
        if (!$this->tabelaExiste()) {
            return [];
        }

        $this->db->select('*');
        $this->db->from('os_assinaturas');
        $this->db->where('os_id', $os_id);
        $this->db->where_in('tipo', ['cliente_saida', 'tecnico_saida']);
        $this->db->order_by('data_assinatura', 'asc');

        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    /**
     * Salva imagem da assinatura em arquivo (VERSÃO CORRIGIDA)
     */
    public function salvarImagem($base64_image, $os_id, $tipo)
    {
        // Verificar se é um base64 válido
        if (empty($base64_image)) {
            log_message('error', 'Assinaturas_model::salvarImagem - Base64 vazio');
            return false;
        }

        // Log do início
        log_message('info', 'Assinaturas_model::salvarImagem - Base64 recebido (primeiros 100 chars): ' . substr($base64_image, 0, 100));
        log_message('info', 'Assinaturas_model::salvarImagem - Tamanho original: ' . strlen($base64_image));

        // Extrair dados da imagem base64 - remover header data:image/png;base64,
        if (strpos($base64_image, ',') !== false) {
            list($header, $base64_image) = explode(',', $base64_image, 2);
            log_message('info', 'Assinaturas_model::salvarImagem - Header encontrado: ' . $header);
        }

        // Salvar base64 "cru" (sem tratamentos agressivos) para tentativa de decodificação
        $base64_original = $base64_image;

        // Tentativa 1: Limpar apenas espaços em branco e quebras de linha
        $base64_limpo = str_replace([' ', "\t", "\n", "\r"], '', $base64_image);

        // Tentativa 2: Regex mais permissiva (preserva caracteres válidos de base64)
        $base64_regex = preg_replace('/[^a-zA-Z0-9+\/=]/', '', $base64_limpo);

        // Tentativa 3: Se o tamanho mudou muito após regex, tentar o original
        if (strlen($base64_regex) < strlen($base64_limpo) * 0.9) {
            log_message('warning', 'Assinaturas_model::salvarImagem - Muitos caracteres removidos pelo regex');
            $base64_regex = $base64_limpo;
        }

        // Adicionar padding se necessário (base64 precisa ser múltiplo de 4)
        $mod = strlen($base64_regex) % 4;
        if ($mod > 0) {
            $base64_regex .= str_repeat('=', 4 - $mod);
        }

        log_message('info', 'Assinaturas_model::salvarImagem - Tamanho após limpeza: ' . strlen($base64_regex));

        // Decodificar base64
        $image_data = base64_decode($base64_regex, true);

        if ($image_data === false) {
            log_message('error', 'Assinaturas_model::salvarImagem - Falha ao decodificar base64. Tamanho: ' . strlen($base64_regex));
            log_message('error', 'Assinaturas_model::salvarImagem - Primeiros 200 chars do base64: ' . substr($base64_regex, 0, 200));

            // Tentar método alternativo: salvar no banco
            log_message('info', 'Assinaturas_model::salvarImagem - Tentando salvar no banco como alternativa...');
            return $this->salvarImagemBase64NoBanco($base64_original, $os_id, $tipo);
        }

        // Verificar se os dados decodificados são uma imagem válida
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_buffer($finfo, $image_data);
        finfo_close($finfo);

        log_message('info', 'Assinaturas_model::salvarImagem - MIME type detectado: ' . $mime_type);

        // Determinar extensão baseada no MIME type
        $extensao = 'png';
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                $extensao = 'jpg';
                break;
            case 'image/png':
                $extensao = 'png';
                break;
            case 'image/gif':
                $extensao = 'gif';
                break;
            case 'image/webp':
                $extensao = 'webp';
                break;
        }

        // Criar pasta por data
        $pasta = 'assets/assinaturas/' . date('Y-m');
        if (!is_dir($pasta)) {
            if (!mkdir($pasta, 0755, true)) {
                log_message('error', 'Assinaturas_model::salvarImagem - Falha ao criar pasta: ' . $pasta);
                return false;
            }
        }

        // Verificar permissões da pasta
        if (!is_writable($pasta)) {
            log_message('error', 'Assinaturas_model::salvarImagem - Pasta sem permissão de escrita: ' . $pasta);
            return false;
        }

        // Nome do arquivo
        $nome_arquivo = 'os_' . $os_id . '_' . $tipo . '_' . time() . '.' . $extensao;
        $caminho_completo = $pasta . '/' . $nome_arquivo;

        // Salvar arquivo
        $bytes_escritos = file_put_contents($caminho_completo, $image_data);

        if ($bytes_escritos === false || $bytes_escritos === 0) {
            log_message('error', 'Assinaturas_model::salvarImagem - Falha ao salvar arquivo: ' . $caminho_completo);
            return false;
        }

        log_message('info', 'Assinaturas_model::salvarImagem - Imagem salva com sucesso: ' . $caminho_completo . ' (' . $bytes_escritos . ' bytes)');

        return [
            'arquivo' => $nome_arquivo,
            'path' => $caminho_completo,
            'url' => base_url($caminho_completo),
            'tamanho' => $bytes_escritos,
            'modo' => 'arquivo'
        ];
    }

    /**
     * Salva a assinatura em base64 diretamente no banco de dados
     * Útil quando há problemas com decodificação ou permissões de arquivo
     */
    public function salvarImagemBase64NoBanco($base64_image, $os_id, $tipo)
    {
        log_message('info', 'Assinaturas_model::salvarImagemBase64NoBanco - Salvando no banco');

        // Limpar apenas espaços em branco (não fazer regex agressivo)
        $base64_limpo = trim($base64_image);
        $base64_limpo = str_replace(["\n", "\r", "\t"], '', $base64_limpo);

        // Se tem header data:image, manter ele
        if (strpos($base64_limpo, 'data:') === 0) {
            // Já está no formato correto com header
            $base64_final = $base64_limpo;
        } else {
            // Adicionar header PNG padrão se não tiver
            $base64_final = 'data:image/png;base64,' . $base64_limpo;
        }

        log_message('info', 'Assinaturas_model::salvarImagemBase64NoBanco - Base64 final (primeiros 100 chars): ' . substr($base64_final, 0, 100));

        // Salvar no banco de dados
        $data = [
            'os_id' => $os_id,
            'tipo' => $tipo,
            'assinatura' => 'BASE64:' . $base64_final, // Prefixo para identificar que é base64
            'data_assinatura' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('os_assinaturas', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_message('info', 'Assinaturas_model::salvarImagemBase64NoBanco - Salvo no banco com ID: ' . $insert_id);

            return [
                'arquivo' => 'assinatura_base64_' . $insert_id . '.png',
                'path' => 'BASE64:' . $insert_id,
                'url' => base_url('index.php/checkin/verAssinatura/' . $insert_id),
                'tamanho' => strlen($base64_final),
                'modo' => 'banco',
                'id' => $insert_id
            ];
        }

        log_message('error', 'Assinaturas_model::salvarImagemBase64NoBanco - Erro ao salvar no banco');
        return false;
    }

    /**
     * Obtém a imagem da assinatura para exibição
     * Retorna o conteúdo da imagem para ser exibido diretamente
     */
    public function getImagemBase64($assinatura_id)
    {
        $assinatura = $this->getById($assinatura_id);

        if (!$assinatura) {
            return false;
        }

        // Se começa com BASE64:, é armazenado no banco
        if (strpos($assinatura->assinatura, 'BASE64:') === 0) {
            $base64_data = substr($assinatura->assinatura, 7); // Remove "BASE64:"
            return $base64_data;
        }

        // Se é um arquivo no disco
        if (file_exists($assinatura->assinatura)) {
            $mime = mime_content_type($assinatura->assinatura);
            $data = file_get_contents($assinatura->assinatura);
            return 'data:' . $mime . ';base64,' . base64_encode($data);
        }

        return false;
    }

    /**
     * Atualiza o campo assinatura para salvar base64
     * Usado para migrar de arquivo para base64 no banco
     */
    public function atualizarParaBase64($id, $base64_data)
    {
        if (!$this->tabelaExiste()) {
            return false;
        }

        $this->db->where('idAssinatura', $id);
        return $this->db->update('os_assinaturas', [
            'assinatura' => 'BASE64:' . $base64_data
        ]);
    }
}
