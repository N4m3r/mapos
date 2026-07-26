<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Equipe externa (terceirizada) na OS — MAP-OS.
 *
 * Reaproveita as tabelas `equipes`/`equipe_membros` do módulo de Projetos
 * (Obras) para permitir atribuir uma OS avulsa a uma EQUIPE (não só a um
 * técnico individual), registrando o CUSTO pago à equipe/terceiro naquela OS.
 *
 * - Em `equipes`: marca a equipe como interna/terceirizada e guarda dados de
 *   contato e um custo padrão (diária/empreitada de referência).
 * - Em `os`: vínculo com a equipe e o custo dessa OS para a equipe.
 *
 * Se o módulo de Projetos ainda não foi instalado, cria a tabela `equipes`
 * mínima aqui para que a atribuição por equipe funcione de forma autônoma.
 *
 * Idempotente: só cria o que ainda não existe.
 */
class Migration_add_os_equipe_externa extends CI_Migration
{
    public function up()
    {
        $comum = ['ENGINE' => 'InnoDB'];

        // 0) Garantir a tabela `equipes` (caso Projetos não esteja instalado).
        if (! $this->db->table_exists('equipes')) {
            $this->dbforge->add_field([
                'idEquipe' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'nome' => ['type' => 'VARCHAR', 'constraint' => 100],
                'encarregado_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'ativo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'data_cadastro' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idEquipe', true);
            $this->dbforge->create_table('equipes', true, $comum);
        }

        // 1) Campos de equipe externa/terceira em `equipes`.
        $colsEquipe = [
            'tipo' => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'interna'], // interna | terceirizada
            'documento' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],     // CPF/CNPJ do terceiro
            'contato' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'telefone' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'custo_padrao' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
        ];
        foreach ($colsEquipe as $nome => $def) {
            if (! $this->db->field_exists($nome, 'equipes')) {
                $this->dbforge->add_column('equipes', [$nome => $def]);
            }
        }

        // 2) Vínculo e custo da equipe na OS.
        if ($this->db->table_exists('os')) {
            if (! $this->db->field_exists('equipe_id', 'os')) {
                $this->dbforge->add_column('os', [
                    'equipe_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                ]);
            }
            if (! $this->db->field_exists('custo_equipe', 'os')) {
                $this->dbforge->add_column('os', [
                    'custo_equipe' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                ]);
            }
        }

        log_message('info', 'Migration add_os_equipe_externa executada com sucesso');
    }

    public function down()
    {
        foreach (['equipe_id', 'custo_equipe'] as $c) {
            if ($this->db->table_exists('os') && $this->db->field_exists($c, 'os')) {
                $this->dbforge->drop_column('os', $c);
            }
        }
        // Não removemos as colunas de `equipes` no down para não conflitar com
        // o módulo de Projetos, que compartilha a mesma tabela.
    }
}
