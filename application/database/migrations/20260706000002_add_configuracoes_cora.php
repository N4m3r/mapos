<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tela de configuração do banco Cora (gateway de boleto híbrido + PIX).
 * Guarda credenciais mTLS e ambiente em um registro único, para não depender
 * apenas do .env. Os valores aqui têm prioridade sobre payment_gateways.php.
 */
class Migration_add_configuracoes_cora extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'auto_increment' => true,
            ],
            'ativo' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
                'comment' => '1 = habilita geração de boleto Cora',
            ],
            'producao' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
                'comment' => '0 = Stage/homologacao, 1 = Producao',
            ],
            'client_id' => [
                'type' => 'VARCHAR',
                'constraint' => 191,
                'null' => true,
            ],
            'certificado_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Caminho do certificate.pem (mTLS)',
            ],
            'chave_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Caminho do private-key.key (mTLS)',
            ],
            'boleto_expiration' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => false,
                'default' => 'P3D',
                'comment' => 'Vencimento do boleto no formato ISO 8601 (ex: P3D)',
            ],
            'ultima_atualizacao' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('configuracoes_cora', true);
        $this->db->query('ALTER TABLE `configuracoes_cora` ENGINE = InnoDB');
        $this->db->query('ALTER TABLE `configuracoes_cora` MODIFY `ultima_atualizacao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

        // Registro único de configuração.
        $existe = $this->db->get('configuracoes_cora');
        if ($existe->num_rows() == 0) {
            $this->db->insert('configuracoes_cora', ['ativo' => 0, 'producao' => 0, 'boleto_expiration' => 'P3D']);
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('configuracoes_cora', true);
    }
}
