<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Módulo de Contratos de Manutenção + SLA — MAP-OS.
 *
 * Cria o contrato recorrente (mensalidade) do cliente e a matriz de SLA
 * (prazo de resposta/solução por prioridade). Também acopla à OS os campos
 * necessários para amarrar a OS a um contrato, definir a prioridade e
 * cronometrar o SLA (prazo previsto x momento em que foi cumprido).
 *
 * Faturamento recorrente é gerado como lançamento financeiro (receita) por
 * competência — reaproveita a tela de Lançamentos/Caixa e o pipeline de
 * Boleto/PIX Cora já existentes, sem duplicar cobrança.
 *
 * Idempotente: só cria tabelas/colunas/permissões que ainda não existem.
 */
class Migration_add_contratos extends CI_Migration
{
    public function up()
    {
        $comum = ['ENGINE' => 'InnoDB'];

        // 1) Contrato (núcleo) -----------------------------------------
        if (! $this->db->table_exists('contratos')) {
            $this->dbforge->add_field([
                'idContrato' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'codigo' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'clientes_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'descricao' => ['type' => 'VARCHAR', 'constraint' => 150],
                // Periodicidade da parcela cobrada.
                'tipo' => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'mensal'],
                'valor' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'dia_vencimento' => ['type' => 'INT', 'constraint' => 2, 'default' => 10],
                'data_inicio' => ['type' => 'DATE', 'null' => true],
                'data_fim' => ['type' => 'DATE', 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'ativo'],
                'observacoes' => ['type' => 'TEXT', 'null' => true],
                'data_cadastro' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idContrato', true);
            $this->dbforge->add_key('clientes_id');
            $this->dbforge->create_table('contratos', true, $comum);
        }

        // 2) SLA por prioridade ----------------------------------------
        if (! $this->db->table_exists('contrato_sla')) {
            $this->dbforge->add_field([
                'idSla' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'contrato_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'prioridade' => ['type' => 'VARCHAR', 'constraint' => 20],
                // Prazos em horas corridas a partir da abertura da OS.
                'resposta_horas' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'solucao_horas' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            ]);
            $this->dbforge->add_key('idSla', true);
            $this->dbforge->add_key('contrato_id');
            $this->dbforge->create_table('contrato_sla', true, $comum);
        }

        // 3) Acoplamento na OS (contrato, prioridade e cronômetro SLA) --
        if ($this->db->table_exists('os')) {
            $colunas = [
                'contrato_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'prioridade' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'sla_resposta_prazo' => ['type' => 'DATETIME', 'null' => true],
                'sla_solucao_prazo' => ['type' => 'DATETIME', 'null' => true],
                'sla_resposta_em' => ['type' => 'DATETIME', 'null' => true],
                'sla_solucao_em' => ['type' => 'DATETIME', 'null' => true],
            ];
            foreach ($colunas as $nome => $def) {
                if (! $this->db->field_exists($nome, 'os')) {
                    $this->dbforge->add_column('os', [$nome => $def]);
                }
            }
        }

        // 4) Permissões -------------------------------------------------
        $this->seedPermissoes();

        log_message('info', 'Migration add_contratos executada com sucesso');
    }

    /**
     * Semeia as chaves de permissão do módulo, herdando de permissões
     * equivalentes já concedidas (padrão v/c/e/d/f do Mapos).
     */
    private function seedPermissoes()
    {
        if (! $this->db->table_exists('permissoes')) {
            return;
        }

        // chave => permissão da qual herda o valor inicial
        $novas = [
            'vContrato' => 'vOs',
            'cContrato' => 'cOs',
            'eContrato' => 'eOs',
            'dContrato' => 'dOs',
            'fContrato' => 'vLancamento', // faturar mensalidade (financeiro)
        ];

        $grupos = $this->db->get('permissoes')->result();
        foreach ($grupos as $g) {
            set_error_handler(static function () {
                return true;
            });
            $perms = unserialize((string) $g->permissoes);
            restore_error_handler();

            if (! is_array($perms)) {
                continue;
            }

            $mudou = false;
            foreach ($novas as $chave => $herda) {
                if (! array_key_exists($chave, $perms)) {
                    $perms[$chave] = ! empty($perms[$herda]) ? 1 : 0;
                    $mudou = true;
                }
            }

            if ($mudou) {
                $this->db->where('idPermissao', $g->idPermissao)
                    ->update('permissoes', ['permissoes' => serialize($perms)]);
            }
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('contrato_sla', true);
        $this->dbforge->drop_table('contratos', true);

        if ($this->db->table_exists('os')) {
            foreach (['contrato_id', 'prioridade', 'sla_resposta_prazo', 'sla_solucao_prazo', 'sla_resposta_em', 'sla_solucao_em'] as $c) {
                if ($this->db->field_exists($c, 'os')) {
                    $this->dbforge->drop_column('os', $c);
                }
            }
        }

        if ($this->db->table_exists('permissoes')) {
            $chaves = ['vContrato', 'cContrato', 'eContrato', 'dContrato', 'fContrato'];
            $grupos = $this->db->get('permissoes')->result();
            foreach ($grupos as $g) {
                set_error_handler(static function () {
                    return true;
                });
                $perms = unserialize((string) $g->permissoes);
                restore_error_handler();
                if (! is_array($perms)) {
                    continue;
                }
                $mudou = false;
                foreach ($chaves as $c) {
                    if (array_key_exists($c, $perms)) {
                        unset($perms[$c]);
                        $mudou = true;
                    }
                }
                if ($mudou) {
                    $this->db->where('idPermissao', $g->idPermissao)
                        ->update('permissoes', ['permissoes' => serialize($perms)]);
                }
            }
        }
    }
}
