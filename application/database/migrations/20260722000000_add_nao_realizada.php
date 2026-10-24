<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Serviço não realizado (Área do Técnico).
 *
 * Quando o técnico não consegue executar o serviço em campo, ele registra
 * uma ocorrência com um motivo padronizado (lista gerenciável) + observação
 * livre. A OS passa ao status "Não Realizado" e fica num painel de espera,
 * de onde pode ser reagendada (nova data) ou reaberta para refazer — sem que
 * a informação se perca.
 */
class Migration_add_nao_realizada extends CI_Migration
{
    public function up()
    {
        // 1) Motivos (lista gerenciável) --------------------------------
        if (! $this->db->table_exists('motivos_nao_realizada')) {
            $this->dbforge->add_field([
                'idMotivo' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'nome' => ['type' => 'VARCHAR', 'constraint' => 120],
                'ativo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'ordem' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'data_cadastro' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idMotivo', true);
            $this->dbforge->create_table('motivos_nao_realizada', true, ['ENGINE' => 'InnoDB']);
        }

        // Seed dos motivos padrão (só quando a tabela está vazia).
        if ($this->db->table_exists('motivos_nao_realizada')
            && $this->db->count_all('motivos_nao_realizada') === 0) {
            $padrao = [
                'Cliente ausente',
                'Endereço não localizado',
                'Falta de peça / material',
                'Condições climáticas',
                'Cliente recusou o serviço',
                'Acesso ao local negado',
                'Equipamento indisponível',
                'Outro',
            ];
            $ordem = 0;
            foreach ($padrao as $nome) {
                $this->db->insert('motivos_nao_realizada', [
                    'nome' => $nome,
                    'ativo' => 1,
                    'ordem' => $ordem++,
                    'data_cadastro' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // 2) Ocorrências (registro de "não realizado") ------------------
        if (! $this->db->table_exists('os_nao_realizada')) {
            $this->dbforge->add_field([
                'idOcorrencia' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'os_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'usuarios_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'motivo_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                // Snapshot do nome do motivo: preserva o histórico mesmo que o
                // motivo seja renomeado ou removido da lista depois.
                'motivo_texto' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'observacao' => ['type' => 'TEXT', 'null' => true],
                // Status que a OS tinha antes de virar "Não Realizado" — usado
                // para restaurar ao reabrir.
                'status_anterior' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'data_registro' => ['type' => 'DATETIME', 'null' => true],
                // Fluxo de espera → resolução.
                'resolvido' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'resolucao' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true], // 'reagendado' | 'reaberto'
                'nova_data' => ['type' => 'DATE', 'null' => true],
                'resolvido_por' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'data_resolucao' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idOcorrencia', true);
            $this->dbforge->add_key('os_id');
            $this->dbforge->add_key('motivo_id');
            $this->dbforge->create_table('os_nao_realizada', true, ['ENGINE' => 'InnoDB']);
        }

        // 3) Permissões --------------------------------------------------
        if ($this->db->table_exists('permissoes')) {
            $grupos = $this->db->get('permissoes')->result();
            foreach ($grupos as $g) {
                // Desserialização resiliente (blobs podem estar corrompidos).
                set_error_handler(static function () {
                    return true;
                });
                $perms = unserialize((string) $g->permissoes);
                restore_error_handler();

                if (! is_array($perms)) {
                    continue;
                }

                $mudou = false;

                // Marcar "Não Realizado": para quem já usa a Área do Técnico.
                if (! array_key_exists('eTecnicoNaoRealizado', $perms)) {
                    $perms['eTecnicoNaoRealizado'] = ! empty($perms['vTecnicoDashboard']) ? 1 : 0;
                    $mudou = true;
                }

                // Gerenciar a lista de motivos: para quem administra o sistema.
                if (! array_key_exists('cMotivoNaoRealizado', $perms)) {
                    $perms['cMotivoNaoRealizado'] = ! empty($perms['cSistema']) ? 1 : 0;
                    $mudou = true;
                }

                if ($mudou) {
                    $this->db->where('idPermissao', $g->idPermissao)
                        ->update('permissoes', ['permissoes' => serialize($perms)]);
                }
            }
        }

        log_message('info', 'Migration add_nao_realizada executada com sucesso');
    }

    public function down()
    {
        $this->dbforge->drop_table('os_nao_realizada', true);
        $this->dbforge->drop_table('motivos_nao_realizada', true);

        if ($this->db->table_exists('permissoes')) {
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
                foreach (['eTecnicoNaoRealizado', 'cMotivoNaoRealizado'] as $chave) {
                    if (array_key_exists($chave, $perms)) {
                        unset($perms[$chave]);
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
