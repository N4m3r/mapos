<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * RH — melhorias CLT e autoatendimento:
 *  - Carteira de trabalho / PIS no cadastro do colaborador
 *  - Dados de desligamento (motivo/tipo)
 *  - Holerite liberado para o colaborador
 *  - Configurações de descontos legais (INSS, IRRF, VT, etc.)
 *  - Flags de controle de horas extras
 */
class Migration_rh_melhorias_clt extends CI_Migration
{
    public function up()
    {
        // ------------------------------------------------------------------
        // Campos de carteira / desligamento em rh_colaboradores
        // ------------------------------------------------------------------
        if ($this->db->table_exists('rh_colaboradores')) {
            $campos = [
                'ctps_numero' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'null' => true,
                    'comment' => 'Número da CTPS',
                ],
                'ctps_serie' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                ],
                'ctps_uf' => [
                    'type' => 'VARCHAR',
                    'constraint' => 2,
                    'null' => true,
                ],
                'ctps_data_emissao' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'pis_pasep' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                    'comment' => 'PIS/PASEP/NIS',
                ],
                'tipo_desligamento' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => true,
                    'comment' => 'pedido|sem_justa_causa|justa_causa|termino_contrato|acordo|aposentadoria|outro',
                ],
                'motivo_desligamento' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'desligado_por' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'desligado_em' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ];
            foreach ($campos as $nome => $def) {
                if (! $this->db->field_exists($nome, 'rh_colaboradores')) {
                    $this->dbforge->add_column('rh_colaboradores', [$nome => $def]);
                }
            }
        }

        // ------------------------------------------------------------------
        // Liberação do holerite para o colaborador
        // ------------------------------------------------------------------
        if ($this->db->table_exists('rh_holerites')) {
            if (! $this->db->field_exists('liberado_colaborador', 'rh_holerites')) {
                $this->dbforge->add_column('rh_holerites', [
                    'liberado_colaborador' => [
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => 0,
                        'comment' => '1 = visível na área do colaborador',
                    ],
                ]);
            }
            if (! $this->db->field_exists('liberado_em', 'rh_holerites')) {
                $this->dbforge->add_column('rh_holerites', [
                    'liberado_em' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                ]);
            }
            if (! $this->db->field_exists('gerado_sistema', 'rh_holerites')) {
                $this->dbforge->add_column('rh_holerites', [
                    'gerado_sistema' => [
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => 0,
                        'comment' => '1 = PDF gerado pelo sistema (não upload contábil)',
                    ],
                ]);
            }
            // Holerites já existentes com arquivo ficam liberados (compatibilidade)
            $this->db->where('arquivo_base64 IS NOT NULL', null, false)
                     ->where('arquivo_base64 !=', '')
                     ->where('liberado_colaborador', 0)
                     ->update('rh_holerites', [
                         'liberado_colaborador' => 1,
                         'liberado_em' => date('Y-m-d H:i:s'),
                     ]);
        }

        // ------------------------------------------------------------------
        // Configurações de descontos CLT e horas extras
        // ------------------------------------------------------------------
        $colConfig = $this->db->field_data('configuracoes');
        foreach ($colConfig as $c) {
            if ($c->name === 'config' && (int) $c->max_length < 60) {
                $this->db->query('ALTER TABLE `configuracoes` MODIFY `config` VARCHAR(60) NOT NULL');
            }
            if ($c->name === 'valor' && strtoupper((string) $c->type) !== 'TEXT' && (int) $c->max_length < 500) {
                $this->db->query('ALTER TABLE `configuracoes` MODIFY `valor` TEXT NULL');
            }
        }

        // Tabelas INSS/IRRF em JSON (valores de referência 2026 — editáveis na tela)
        $inssPadrao = json_encode([
            ['ate' => 1518.00, 'aliquota' => 7.5],
            ['ate' => 2793.88, 'aliquota' => 9.0],
            ['ate' => 4190.83, 'aliquota' => 12.0],
            ['ate' => 8157.41, 'aliquota' => 14.0],
        ], JSON_UNESCAPED_UNICODE);

        $irrfPadrao = json_encode([
            ['ate' => 2428.80, 'aliquota' => 0, 'deducao' => 0],
            ['ate' => 2826.65, 'aliquota' => 7.5, 'deducao' => 182.16],
            ['ate' => 3751.05, 'aliquota' => 15.0, 'deducao' => 394.16],
            ['ate' => 4664.68, 'aliquota' => 22.5, 'deducao' => 675.49],
            ['ate' => 99999999, 'aliquota' => 27.5, 'deducao' => 908.73],
        ], JSON_UNESCAPED_UNICODE);

        $configs = [
            'rh_clt_calcular_inss' => '1',
            'rh_clt_calcular_irrf' => '1',
            'rh_clt_mostrar_fgts' => '1',
            'rh_clt_fgts_aliquota' => '8',
            'rh_clt_vt_ativo' => '0',
            'rh_clt_vt_percentual' => '6',
            'rh_clt_vt_valor_fixo' => '0',
            'rh_clt_outras_deducoes' => '0',
            'rh_clt_dependente_deducao' => '189.59',
            'rh_clt_inss_tabela' => $inssPadrao,
            'rh_clt_irrf_tabela' => $irrfPadrao,
            'rh_he_requer_aprovacao' => '1',
            'rh_he_percentual_50' => '50',
            'rh_he_percentual_100' => '100',
        ];
        foreach ($configs as $chave => $valor) {
            if ($this->db->where('config', $chave)->count_all_results('configuracoes') == 0) {
                $this->db->insert('configuracoes', ['config' => $chave, 'valor' => $valor]);
            }
        }
    }

    public function down()
    {
        if ($this->db->table_exists('rh_colaboradores')) {
            foreach (['ctps_numero', 'ctps_serie', 'ctps_uf', 'ctps_data_emissao', 'pis_pasep',
                'tipo_desligamento', 'motivo_desligamento', 'desligado_por', 'desligado_em'] as $c) {
                if ($this->db->field_exists($c, 'rh_colaboradores')) {
                    $this->dbforge->drop_column('rh_colaboradores', $c);
                }
            }
        }
        if ($this->db->table_exists('rh_holerites')) {
            foreach (['liberado_colaborador', 'liberado_em', 'gerado_sistema'] as $c) {
                if ($this->db->field_exists($c, 'rh_holerites')) {
                    $this->dbforge->drop_column('rh_holerites', $c);
                }
            }
        }
        $chaves = [
            'rh_clt_calcular_inss', 'rh_clt_calcular_irrf', 'rh_clt_mostrar_fgts', 'rh_clt_fgts_aliquota',
            'rh_clt_vt_ativo', 'rh_clt_vt_percentual', 'rh_clt_vt_valor_fixo', 'rh_clt_outras_deducoes',
            'rh_clt_dependente_deducao', 'rh_clt_inss_tabela', 'rh_clt_irrf_tabela',
            'rh_he_requer_aprovacao', 'rh_he_percentual_50', 'rh_he_percentual_100',
        ];
        foreach ($chaves as $chave) {
            $this->db->where('config', $chave)->delete('configuracoes');
        }
    }
}
