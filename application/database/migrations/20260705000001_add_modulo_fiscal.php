<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_modulo_fiscal extends CI_Migration {

    public function up()
    {
        // Tabela de configurações do módulo fiscal (NF-e / NFS-e Nacional)
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'auto_increment' => true,
            ],
            'certificado_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Caminho do arquivo .pfx/.p12 do certificado A1',
            ],
            'senha_certificado' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Senha do certificado criptografada (AES-256)',
            ],
            'ambiente' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 2,
                'comment' => '1=Producao, 2=Homologacao',
            ],
            'serie_nfe' => [
                'type' => 'VARCHAR',
                'constraint' => 3,
                'null' => false,
                'default' => '1',
            ],
            'proximo_numero_nfe' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 1,
            ],
            'serie_dps' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => false,
                'default' => '1',
                'comment' => 'Serie da DPS (NFS-e Nacional)',
            ],
            'proximo_numero_dps' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 1,
            ],
            'csosn_padrao' => [
                'type' => 'VARCHAR',
                'constraint' => 4,
                'null' => false,
                'default' => '102',
                'comment' => 'CSOSN padrao do Simples Nacional (102 = sem credito)',
            ],
            'cfop_padrao' => [
                'type' => 'VARCHAR',
                'constraint' => 4,
                'null' => false,
                'default' => '5102',
            ],
            'op_simp_nac' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 3,
                'comment' => 'Situacao no Simples: 1=Nao optante, 2=MEI, 3=ME/EPP',
            ],
            'reg_esp_trib' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
                'comment' => 'Regime especial de tributacao (NFS-e), 0=Nenhum',
            ],
            'inscricao_municipal' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'codigo_municipio' => [
                'type' => 'VARCHAR',
                'constraint' => 7,
                'null' => true,
                'comment' => 'Codigo IBGE (7 digitos) do municipio do emitente',
            ],
            'aliquota_iss' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => false,
                'default' => 0,
            ],
            'tp_ret_issqn' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
                'comment' => 'Retencao do ISSQN: 1=Nao retido, 2=Retido pelo tomador',
            ],
            'ultima_atualizacao' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('configuracoes_nfe', true);
        $this->db->query('ALTER TABLE `configuracoes_nfe` ENGINE = InnoDB');
        // CURRENT_TIMESTAMP nao pode ser definido via dbforge (ele escapa o valor);
        // aplicado aqui com SQL cru para virar palavra-chave e nao string literal.
        $this->db->query('ALTER TABLE `configuracoes_nfe` MODIFY `ultima_atualizacao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

        // Registro único de configuração
        $existe = $this->db->get('configuracoes_nfe');
        if ($existe->num_rows() == 0) {
            $this->db->insert('configuracoes_nfe', ['ambiente' => 2]);
        }

        // Tabela de notas fiscais emitidas (obrigatório guardar XML por 5 anos)
        $this->dbforge->add_field([
            'idNota' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'auto_increment' => true,
            ],
            'tipo' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => false,
                'comment' => 'nfe ou nfse',
            ],
            'vendas_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'os_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'numero' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'serie' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
            ],
            'chave' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Chave de acesso da NF-e (44) ou NFS-e (50)',
            ],
            'protocolo' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'pendente',
                'comment' => 'pendente, autorizada, rejeitada, cancelada, erro',
            ],
            'motivo' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Retorno da SEFAZ/ADN (cStat + xMotivo ou erro)',
            ],
            'ambiente' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 2,
            ],
            'valor_total' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
                'default' => 0,
            ],
            'xml_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Caminho do XML autorizado no disco',
            ],
            'data_emissao' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'data_autorizacao' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'data_cancelamento' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'usuarios_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'data_cadastro' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        $this->dbforge->add_key('idNota', true);
        $this->dbforge->create_table('notas_fiscais', true);
        $this->db->query('ALTER TABLE `notas_fiscais` ENGINE = InnoDB');
        $this->db->query('ALTER TABLE `notas_fiscais` MODIFY `data_cadastro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->db->query('ALTER TABLE `notas_fiscais` ADD INDEX `idx_vendas_id` (`vendas_id`)');
        $this->db->query('ALTER TABLE `notas_fiscais` ADD INDEX `idx_os_id` (`os_id`)');
        $this->db->query('ALTER TABLE `notas_fiscais` ADD INDEX `idx_chave` (`chave`)');
        $this->db->query('ALTER TABLE `notas_fiscais` ADD INDEX `idx_status` (`status`)');

        // Campos fiscais nos clientes
        if (!$this->db->field_exists('ie', 'clientes')) {
            $this->dbforge->add_column('clientes', [
                'ie' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                    'comment' => 'Inscricao Estadual',
                ],
                'im' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                    'comment' => 'Inscricao Municipal',
                ],
            ]);
        }

        // Campos fiscais nos produtos
        if (!$this->db->field_exists('ncm', 'produtos')) {
            $this->dbforge->add_column('produtos', [
                'ncm' => [
                    'type' => 'VARCHAR',
                    'constraint' => 8,
                    'null' => true,
                ],
                'cest' => [
                    'type' => 'VARCHAR',
                    'constraint' => 7,
                    'null' => true,
                ],
                'cfop' => [
                    'type' => 'VARCHAR',
                    'constraint' => 4,
                    'null' => true,
                    'comment' => 'CFOP especifico do produto; vazio usa o padrao da config',
                ],
            ]);
        }

        // Código de tributação nacional nos serviços (cTribNac da NFS-e Nacional)
        if (!$this->db->field_exists('codigo_servico_municipio', 'servicos')) {
            $this->dbforge->add_column('servicos', [
                'codigo_servico_municipio' => [
                    'type' => 'VARCHAR',
                    'constraint' => 6,
                    'null' => true,
                    'comment' => 'cTribNac: item da LC 116 com 6 digitos, ex 140101',
                ],
            ]);
        }

        // Permissões do módulo fiscal no grupo Administrador
        $this->db->where('idPermissao', 1);
        $query = $this->db->get('permissoes');

        if ($query->num_rows() > 0) {
            $admin = $query->row();
            $permissoes = unserialize($admin->permissoes);

            if (is_array($permissoes)) {
                $permissoes['cNfe'] = 1; // configurar módulo fiscal
                $permissoes['eNfe'] = 1; // emitir notas
                $permissoes['vNfe'] = 1; // visualizar notas
                $permissoes['dNfe'] = 1; // cancelar notas

                $this->db->where('idPermissao', 1);
                $this->db->update('permissoes', ['permissoes' => serialize($permissoes)]);
            }
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('configuracoes_nfe', true);
        $this->dbforge->drop_table('notas_fiscais', true);

        if ($this->db->field_exists('ie', 'clientes')) {
            $this->dbforge->drop_column('clientes', 'ie');
            $this->dbforge->drop_column('clientes', 'im');
        }
        if ($this->db->field_exists('ncm', 'produtos')) {
            $this->dbforge->drop_column('produtos', 'ncm');
            $this->dbforge->drop_column('produtos', 'cest');
            $this->dbforge->drop_column('produtos', 'cfop');
        }
        if ($this->db->field_exists('codigo_servico_municipio', 'servicos')) {
            $this->dbforge->drop_column('servicos', 'codigo_servico_municipio');
        }

        $this->db->where('idPermissao', 1);
        $query = $this->db->get('permissoes');

        if ($query->num_rows() > 0) {
            $admin = $query->row();
            $permissoes = unserialize($admin->permissoes);

            if (is_array($permissoes)) {
                unset($permissoes['cNfe'], $permissoes['eNfe'], $permissoes['vNfe'], $permissoes['dNfe']);
                $this->db->where('idPermissao', 1);
                $this->db->update('permissoes', ['permissoes' => serialize($permissoes)]);
            }
        }
    }
}
