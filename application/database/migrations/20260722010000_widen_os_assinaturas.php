<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Alarga `os_assinaturas.assinatura` de VARCHAR(255) para MEDIUMTEXT.
 *
 * A coluna guarda ou o caminho do arquivo (~50 chars) OU a assinatura em
 * base64 quando o disco não é gravável / a decodificação falha
 * (Assinaturas_model::salvarImagemBase64NoBanco) e no fluxo de aceite público
 * (que grava sempre em base64). Uma assinatura PNG em base64 tem milhares de
 * caracteres e não cabe em VARCHAR(255): o MySQL truncava silenciosamente,
 * corrompendo a imagem (assinatura "quebrada" ao exibir). MEDIUMTEXT (16 MB)
 * acomoda a base64 com folga.
 *
 * Não há índice sobre `assinatura` (os índices são em os_id e tipo), então a
 * conversão para TEXT é segura. Registros já truncados no passado não têm como
 * ser recuperados — apenas as assinaturas gravadas a partir daqui persistem
 * inteiras.
 *
 * Idempotente: só altera se a coluna ainda for VARCHAR. Timestamp maior que a
 * consolidada (20260720000000) e que as posteriores, então roda 1x.
 */
class Migration_Widen_os_assinaturas extends CI_Migration
{
    public function up()
    {
        $dbg = $this->db->db_debug;
        $this->db->db_debug = false;

        if (! $this->db->table_exists('os_assinaturas')) {
            $this->db->db_debug = $dbg;
            return;
        }

        $coluna = $this->db->query("SHOW COLUMNS FROM `os_assinaturas` LIKE 'assinatura'")->row();

        // Só altera quando ainda for VARCHAR (evita reprocessar em re-execução).
        if ($coluna && stripos($coluna->Type, 'varchar') !== false) {
            $this->dbforge->modify_column('os_assinaturas', [
                'assinatura' => [
                    'name'    => 'assinatura',
                    'type'    => 'MEDIUMTEXT',
                    'null'    => false,
                    'comment' => 'Caminho do arquivo OU assinatura em base64 (BASE64:...)',
                ],
            ]);
        }

        $this->db->db_debug = $dbg;
    }

    public function down()
    {
        $dbg = $this->db->db_debug;
        $this->db->db_debug = false;

        if ($this->db->table_exists('os_assinaturas')) {
            // Volta para VARCHAR(255). Pode truncar base64 já gravada — por isso
            // o down é apenas para reverter o schema, não os dados.
            $this->dbforge->modify_column('os_assinaturas', [
                'assinatura' => [
                    'name'       => 'assinatura',
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => false,
                    'comment'    => 'Caminho da imagem da assinatura',
                ],
            ]);
        }

        $this->db->db_debug = $dbg;
    }
}
