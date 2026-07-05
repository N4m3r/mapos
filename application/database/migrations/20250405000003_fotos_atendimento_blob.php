<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_fotos_atendimento_blob extends CI_Migration {

    public function up()
    {
        // Adiciona coluna para armazenar imagem em base64
        if ($this->db->table_exists('os_fotos_atendimento')) {
            $fields = [
                'imagem_base64' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                    'comment' => 'Imagem armazenada em base64'
                ],
                'mime_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'null' => true,
                    'comment' => 'Tipo MIME da imagem (image/jpeg, image/png, etc)'
                ]
            ];

            $this->dbforge->add_column('os_fotos_atendimento', $fields);

            // Atualiza registros existentes - converte arquivos para base64
            $this->atualizarRegistrosExistentes();
        }
    }

    public function down()
    {
        // Remove colunas adicionadas
        if ($this->db->table_exists('os_fotos_atendimento')) {
            $this->dbforge->drop_column('os_fotos_atendimento', 'imagem_base64');
            $this->dbforge->drop_column('os_fotos_atendimento', 'mime_type');
        }
    }

    /**
     * Converte fotos existentes em arquivos para base64 no banco
     */
    private function atualizarRegistrosExistentes()
    {
        $this->db->select('idFoto, path');
        $this->db->from('os_fotos_atendimento');
        $this->db->where('imagem_base64 IS NULL');
        $query = $this->db->get();

        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $foto) {
                if (!empty($foto->path) && file_exists($foto->path)) {
                    $conteudo = file_get_contents($foto->path);
                    if ($conteudo !== false) {
                        $mime = mime_content_type($foto->path);
                        $base64 = 'data:' . $mime . ';base64,' . base64_encode($conteudo);

                        $this->db->where('idFoto', $foto->idFoto);
                        $this->db->update('os_fotos_atendimento', [
                            'imagem_base64' => $base64,
                            'mime_type' => $mime
                        ]);
                    }
                }
            }
        }
    }
}
