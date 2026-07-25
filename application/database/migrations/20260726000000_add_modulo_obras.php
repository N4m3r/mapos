<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Módulo de Obras (Equipe de Obra) — MAP-OS.
 *
 * Cria a base para gestão de obras: projeto (contêiner de longa duração),
 * cronograma físico (EAP/etapas), equipes, diário de obra (RDO), medições e
 * a gestão de material do canteiro (recebimento do cliente com foto + leitura
 * de código de barras, saldo/almoxarifado e entrega para a equipe no campo).
 *
 * Idempotente: só cria tabelas/colunas/permissões que ainda não existem.
 * Ver docs/PROJETO-OBRAS.md para o desenho completo.
 */
class Migration_add_modulo_obras extends CI_Migration
{
    public function up()
    {
        $comum = ['ENGINE' => 'InnoDB'];

        // 1) Obra (núcleo) ---------------------------------------------
        if (! $this->db->table_exists('obras')) {
            $this->dbforge->add_field([
                'idObra' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'codigo' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'nome' => ['type' => 'VARCHAR', 'constraint' => 150],
                'clientes_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'responsavel_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'tipo_obra' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                'contrato_numero' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                'valor_contrato' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'bdi_percentual' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
                'cep' => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true],
                'logradouro' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'numero' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'complemento' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'bairro' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'cidade' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'uf' => ['type' => 'VARCHAR', 'constraint' => 2, 'null' => true],
                'latitude' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'longitude' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'data_inicio_prevista' => ['type' => 'DATE', 'null' => true],
                'data_fim_prevista' => ['type' => 'DATE', 'null' => true],
                'data_inicio_real' => ['type' => 'DATE', 'null' => true],
                'data_fim_real' => ['type' => 'DATE', 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'planejamento'],
                'percentual_concluido' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
                'observacoes' => ['type' => 'TEXT', 'null' => true],
                'data_cadastro' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idObra', true);
            $this->dbforge->add_key('clientes_id');
            $this->dbforge->create_table('obras', true, $comum);
        }

        // 2) Etapas (EAP / cronograma físico) --------------------------
        if (! $this->db->table_exists('obra_etapas')) {
            $this->dbforge->add_field([
                'idEtapa' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'obra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'parent_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'codigo_eap' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'nome' => ['type' => 'VARCHAR', 'constraint' => 150],
                'ordem' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'peso_percentual' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
                'valor_previsto' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'data_inicio_prevista' => ['type' => 'DATE', 'null' => true],
                'data_fim_prevista' => ['type' => 'DATE', 'null' => true],
                'data_inicio_real' => ['type' => 'DATE', 'null' => true],
                'data_fim_real' => ['type' => 'DATE', 'null' => true],
                'percentual_concluido' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pendente'],
            ]);
            $this->dbforge->add_key('idEtapa', true);
            $this->dbforge->add_key('obra_id');
            $this->dbforge->create_table('obra_etapas', true, $comum);
        }

        // 3) Vínculo opcional OS <-> obra ------------------------------
        if (! $this->db->table_exists('obra_os')) {
            $this->dbforge->add_field([
                'idVinculo' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'obra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'os_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'etapa_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            ]);
            $this->dbforge->add_key('idVinculo', true);
            $this->dbforge->add_key('obra_id');
            $this->dbforge->create_table('obra_os', true, $comum);
        }

        // 4) Equipes e membros -----------------------------------------
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
        if (! $this->db->table_exists('equipe_membros')) {
            $this->dbforge->add_field([
                'idMembro' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'equipe_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'colaborador_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'nome' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'funcao' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                'valor_diaria' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
                'valor_hora' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
                'data_entrada' => ['type' => 'DATE', 'null' => true],
                'data_saida' => ['type' => 'DATE', 'null' => true],
            ]);
            $this->dbforge->add_key('idMembro', true);
            $this->dbforge->add_key('equipe_id');
            $this->dbforge->create_table('equipe_membros', true, $comum);
        }
        if (! $this->db->table_exists('obra_equipe_alocacao')) {
            $this->dbforge->add_field([
                'idAlocacao' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'obra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'equipe_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'data_inicio' => ['type' => 'DATE', 'null' => true],
                'data_fim' => ['type' => 'DATE', 'null' => true],
                'ativo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            ]);
            $this->dbforge->add_key('idAlocacao', true);
            $this->dbforge->add_key('obra_id');
            $this->dbforge->create_table('obra_equipe_alocacao', true, $comum);
        }

        // 5) Apontamento de efetivo (mão de obra) ----------------------
        if (! $this->db->table_exists('obra_apontamento')) {
            $this->dbforge->add_field([
                'idApontamento' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'obra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'etapa_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'colaborador_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'nome' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'funcao' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                'data' => ['type' => 'DATE', 'null' => true],
                'horas' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
                'diaria' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'origem' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'manual'],
                'valor' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
                'observacao' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            ]);
            $this->dbforge->add_key('idApontamento', true);
            $this->dbforge->add_key('obra_id');
            $this->dbforge->create_table('obra_apontamento', true, $comum);
        }

        // 6) Diário de Obra (RDO) --------------------------------------
        if (! $this->db->table_exists('obra_rdo')) {
            $this->dbforge->add_field([
                'idRdo' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'obra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'numero' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'data' => ['type' => 'DATE', 'null' => true],
                'clima_manha' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'clima_tarde' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'clima_noite' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'condicao' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'praticavel'],
                'responsavel_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'efetivo_total' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'atividades' => ['type' => 'TEXT', 'null' => true],
                'ocorrencias' => ['type' => 'TEXT', 'null' => true],
                'observacoes' => ['type' => 'TEXT', 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'rascunho'],
                'assinatura' => ['type' => 'MEDIUMTEXT', 'null' => true],
                'data_registro' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idRdo', true);
            $this->dbforge->add_key('obra_id');
            $this->dbforge->create_table('obra_rdo', true, $comum);
        }
        if (! $this->db->table_exists('obra_rdo_foto')) {
            $this->dbforge->add_field([
                'idFoto' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'rdo_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'foto' => ['type' => 'LONGBLOB', 'null' => true],
                'legenda' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'data' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idFoto', true);
            $this->dbforge->add_key('rdo_id');
            $this->dbforge->create_table('obra_rdo_foto', true, $comum);
        }

        // 7) Gestão de material ----------------------------------------
        // 7.1 Recebimento (ENTRADA — inclui material do cliente)
        if (! $this->db->table_exists('obra_material_recebimento')) {
            $this->dbforge->add_field([
                'idRecebimento' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'obra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'numero' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'origem' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'cliente'],
                'fornecedor_nome' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'documento' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                'recebido_por' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'data' => ['type' => 'DATE', 'null' => true],
                'observacao' => ['type' => 'TEXT', 'null' => true],
                'assinatura_entregador' => ['type' => 'MEDIUMTEXT', 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'conferido'],
                'data_registro' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idRecebimento', true);
            $this->dbforge->add_key('obra_id');
            $this->dbforge->create_table('obra_material_recebimento', true, $comum);
        }
        if (! $this->db->table_exists('obra_material_recebimento_item')) {
            $this->dbforge->add_field([
                'idItem' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'recebimento_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'produto_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'descricao' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'cod_barras' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                'unidade' => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true],
                'quantidade_prevista' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
                'quantidade_conferida' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
                'valor_unitario' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'divergencia' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            ]);
            $this->dbforge->add_key('idItem', true);
            $this->dbforge->add_key('recebimento_id');
            $this->dbforge->create_table('obra_material_recebimento_item', true, $comum);
        }
        // 7.2 Saldo (almoxarifado da obra)
        if (! $this->db->table_exists('obra_material_saldo')) {
            $this->dbforge->add_field([
                'idSaldo' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'obra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'produto_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'descricao' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'cod_barras' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                'unidade' => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true],
                'do_cliente' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'saldo' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            ]);
            $this->dbforge->add_key('idSaldo', true);
            $this->dbforge->add_key('obra_id');
            $this->dbforge->create_table('obra_material_saldo', true, $comum);
        }
        // 7.3 Entrega (SAÍDA — para a equipe no campo)
        if (! $this->db->table_exists('obra_material_entrega')) {
            $this->dbforge->add_field([
                'idEntrega' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'obra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'numero' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'etapa_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'equipe_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'colaborador_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'recebedor_nome' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'entregue_por' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'data' => ['type' => 'DATE', 'null' => true],
                'observacao' => ['type' => 'TEXT', 'null' => true],
                'assinatura_recebedor' => ['type' => 'MEDIUMTEXT', 'null' => true],
                'data_registro' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idEntrega', true);
            $this->dbforge->add_key('obra_id');
            $this->dbforge->create_table('obra_material_entrega', true, $comum);
        }
        if (! $this->db->table_exists('obra_material_entrega_item')) {
            $this->dbforge->add_field([
                'idItem' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'entrega_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'produto_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'descricao' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'cod_barras' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                'unidade' => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true],
                'quantidade' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            ]);
            $this->dbforge->add_key('idItem', true);
            $this->dbforge->add_key('entrega_id');
            $this->dbforge->create_table('obra_material_entrega_item', true, $comum);
        }
        // 7.4 Fotos de material (recebimento/entrega)
        if (! $this->db->table_exists('obra_material_foto')) {
            $this->dbforge->add_field([
                'idFoto' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'ref_tipo' => ['type' => 'VARCHAR', 'constraint' => 15],
                'ref_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'foto' => ['type' => 'LONGBLOB', 'null' => true],
                'legenda' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'data' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idFoto', true);
            $this->dbforge->add_key('ref_id');
            $this->dbforge->create_table('obra_material_foto', true, $comum);
        }

        // 8) Medição ----------------------------------------------------
        if (! $this->db->table_exists('obra_medicao')) {
            $this->dbforge->add_field([
                'idMedicao' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'obra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'numero' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'periodo_inicio' => ['type' => 'DATE', 'null' => true],
                'periodo_fim' => ['type' => 'DATE', 'null' => true],
                'percentual_periodo' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
                'percentual_acumulado' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
                'valor_medido' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'status' => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'aberta'],
                'aprovado_por' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'data_aprovacao' => ['type' => 'DATETIME', 'null' => true],
                'cobrancas_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'nfse_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'assinatura_cliente' => ['type' => 'MEDIUMTEXT', 'null' => true],
                'data_cadastro' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('idMedicao', true);
            $this->dbforge->add_key('obra_id');
            $this->dbforge->create_table('obra_medicao', true, $comum);
        }
        if (! $this->db->table_exists('obra_medicao_item')) {
            $this->dbforge->add_field([
                'idItem' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'medicao_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'etapa_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'percentual_anterior' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
                'percentual_atual' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
                'valor' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            ]);
            $this->dbforge->add_key('idItem', true);
            $this->dbforge->add_key('medicao_id');
            $this->dbforge->create_table('obra_medicao_item', true, $comum);
        }

        // 9) Custos (previsto x realizado) -----------------------------
        if (! $this->db->table_exists('obra_custo')) {
            $this->dbforge->add_field([
                'idCusto' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'obra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'etapa_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'categoria' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'material'],
                'descricao' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'valor_previsto' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'valor_realizado' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'data' => ['type' => 'DATE', 'null' => true],
            ]);
            $this->dbforge->add_key('idCusto', true);
            $this->dbforge->add_key('obra_id');
            $this->dbforge->create_table('obra_custo', true, $comum);
        }

        // 10) Permissões ------------------------------------------------
        $this->seedPermissoes();

        log_message('info', 'Migration add_modulo_obras executada com sucesso');
    }

    /**
     * Semeia as chaves de permissão do módulo nos grupos existentes,
     * herdando de permissões equivalentes já concedidas (admin recebe tudo).
     */
    private function seedPermissoes()
    {
        if (! $this->db->table_exists('permissoes')) {
            return;
        }

        // chave => permissão da qual herda o valor inicial
        $novas = [
            'vObras' => 'vOs',
            'cObras' => 'cOs',
            'eObras' => 'eOs',
            'dObras' => 'dOs',
            'vObraCronograma' => 'vOs',
            'eObraCronograma' => 'eOs',
            'vObraRdo' => 'vOs',
            'cObraRdo' => 'eOs',
            'vObraMedicao' => 'eOs',
            'cObraMedicao' => 'eOs',
            'aObraMedicao' => 'eOs',
            'vObraCusto' => 'vLancamento',
            'vObraMaterial' => 'vProduto',
            'cObraMaterial' => 'eProduto',
            'rObraMaterial' => 'eProduto',
            'sObraMaterial' => 'eProduto',
            'cObraEquipe' => 'cSistema',
            'vObraCampo' => 'vTecnicoDashboard',
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
        foreach ([
            'obra_custo', 'obra_medicao_item', 'obra_medicao',
            'obra_material_foto', 'obra_material_entrega_item', 'obra_material_entrega',
            'obra_material_saldo', 'obra_material_recebimento_item', 'obra_material_recebimento',
            'obra_rdo_foto', 'obra_rdo', 'obra_apontamento',
            'obra_equipe_alocacao', 'equipe_membros', 'equipes',
            'obra_os', 'obra_etapas', 'obras',
        ] as $t) {
            $this->dbforge->drop_table($t, true);
        }

        if ($this->db->table_exists('permissoes')) {
            $chaves = [
                'vObras', 'cObras', 'eObras', 'dObras', 'vObraCronograma', 'eObraCronograma',
                'vObraRdo', 'cObraRdo', 'vObraMedicao', 'cObraMedicao', 'aObraMedicao',
                'vObraCusto', 'vObraMaterial', 'cObraMaterial', 'rObraMaterial', 'sObraMaterial',
                'cObraEquipe', 'vObraCampo',
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
