<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Reforma Tributária (Fase 3) — Entradas / Créditos de IBS/CBS.
 *
 * Cria o módulo de ENTRADAS (compras): registra as notas fiscais recebidas dos
 * fornecedores (via upload do XML ou lançamento manual) e escritura o crédito
 * de IBS/CBS. É onde a empresa efetivamente aproveita crédito — o que, junto do
 * débito das saídas, forma a apuração "débito - crédito = a recolher".
 *
 *  Tabela `notas_entrada`:
 *   - chave / modelo / numero / serie / emitente : identificação da nota de compra
 *   - valor_total                                : valor da nota
 *   - v_bc_ibscbs / v_ibs / v_cbs                : valores de crédito (grupo IBS/CBS)
 *   - v_cred_pres_zfm                            : crédito presumido da ZFM (se houver)
 *   - credita (0/1)                              : se o crédito é aproveitado
 *   - competencia (AAAA-MM)                      : período de apuração do crédito
 *   - xml / observacao                           : XML original e anotações
 *
 * Permissões novas (no perfil Administrador): vEntrada, cEntrada, dEntrada.
 * Idempotente.
 */
class Migration_add_notas_entrada extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('notas_entrada')) {
            $this->dbforge->add_field([
                'idEntrada' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'chave' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => 'Chave de acesso da NF-e (44)'],
                'modelo' => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true, 'comment' => '55, 65, nfse ou manual'],
                'numero' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'serie' => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true],
                'emitente_cnpj' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'emitente_nome' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'data_emissao' => ['type' => 'DATE', 'null' => true],
                'competencia' => ['type' => 'CHAR', 'constraint' => 7, 'null' => true, 'comment' => 'AAAA-MM'],
                'valor_total' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
                'v_bc_ibscbs' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
                'v_ibs' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
                'v_cbs' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
                'v_cred_pres_zfm' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false, 'default' => 0],
                'credita' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
                'observacao' => ['type' => 'TEXT', 'null' => true],
                'xml' => ['type' => 'LONGTEXT', 'null' => true],
                'usuarios_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'data_cadastro' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idEntrada', true);
            $this->dbforge->create_table('notas_entrada', true, ['ENGINE' => 'InnoDB']);

            // Uma nota (chave) não pode ser lançada duas vezes.
            $this->db->query('ALTER TABLE `notas_entrada` ADD UNIQUE KEY `uq_chave` (`chave`)');
            $this->db->query('ALTER TABLE `notas_entrada` ADD KEY `ix_competencia` (`competencia`)');
        }

        // Permissões do módulo no perfil Administrador (idPermissao = 1).
        $this->db->where('idPermissao', 1);
        $q = $this->db->get('permissoes');
        if ($q->num_rows() > 0) {
            $admin = $q->row();
            $permissoes = @unserialize($admin->permissoes);
            if (is_array($permissoes)) {
                $permissoes['vEntrada'] = 1; // visualizar entradas/créditos
                $permissoes['cEntrada'] = 1; // cadastrar/importar entradas
                $permissoes['dEntrada'] = 1; // excluir entradas
                $this->db->where('idPermissao', 1);
                $this->db->update('permissoes', ['permissoes' => serialize($permissoes)]);
            }
        }

        log_message('info', 'Migration add_notas_entrada executada com sucesso');
    }

    public function down()
    {
        $this->dbforge->drop_table('notas_entrada', true);

        $this->db->where('idPermissao', 1);
        $q = $this->db->get('permissoes');
        if ($q->num_rows() > 0) {
            $admin = $q->row();
            $permissoes = @unserialize($admin->permissoes);
            if (is_array($permissoes)) {
                unset($permissoes['vEntrada'], $permissoes['cEntrada'], $permissoes['dEntrada']);
                $this->db->where('idPermissao', 1);
                $this->db->update('permissoes', ['permissoes' => serialize($permissoes)]);
            }
        }
    }
}
