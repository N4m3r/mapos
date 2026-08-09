<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Reforma Tributária (Fase 2) — grupo IBS/CBS na emissão da NF-e (modelo 55).
 *
 * Adiciona à tabela `configuracoes_nfe` os parâmetros do grupo IBS/CBS
 * (NT 2025.002 / layout PL_010 do sped-nfe), todos guardados atrás de um
 * checkbox mestre `reforma_ativa`:
 *
 *  - reforma_ativa      : liga/desliga a emissão dos campos IBS/CBS. Desligado
 *                         (padrão) o XML sai idêntico ao de hoje (PL_009), sem
 *                         nenhum risco para quem ainda não vai usar.
 *  - reforma_ibs_uf     : alíquota (%) do IBS de competência da UF.
 *  - reforma_ibs_mun    : alíquota (%) do IBS de competência do Município.
 *  - reforma_cbs        : alíquota (%) da CBS.
 *  - reforma_cst        : CST do IBS/CBS (3 díg.). Padrão 000 (tributação integral).
 *  - reforma_cclasstrib : cClassTrib (6 díg.). Padrão 000001.
 *
 * Defaults refletem o ANO-TESTE 2026 (IBS 0,1% total + CBS 0,9%). O contador
 * ajusta conforme o regime escolhido (por dentro x híbrido) e a ZFM.
 *
 * Idempotente: só cria coluna que ainda não existe.
 */
class Migration_add_reforma_ibscbs extends CI_Migration
{
    public function up()
    {
        $colunas = [
            'reforma_ativa' => "`reforma_ativa` TINYINT(1) NOT NULL DEFAULT 0",
            'reforma_ibs_uf' => "`reforma_ibs_uf` DECIMAL(6,4) NOT NULL DEFAULT 0.1000",
            'reforma_ibs_mun' => "`reforma_ibs_mun` DECIMAL(6,4) NOT NULL DEFAULT 0.0000",
            'reforma_cbs' => "`reforma_cbs` DECIMAL(6,4) NOT NULL DEFAULT 0.9000",
            'reforma_cst' => "`reforma_cst` VARCHAR(3) NOT NULL DEFAULT '000'",
            'reforma_cclasstrib' => "`reforma_cclasstrib` VARCHAR(6) NOT NULL DEFAULT '000001'",
        ];

        foreach ($colunas as $coluna => $definicao) {
            $this->addColuna('configuracoes_nfe', $coluna, $definicao);
        }

        log_message('info', 'Migration add_reforma_ibscbs executada com sucesso');
    }

    public function down()
    {
        foreach (['reforma_ativa', 'reforma_ibs_uf', 'reforma_ibs_mun', 'reforma_cbs', 'reforma_cst', 'reforma_cclasstrib'] as $c) {
            if ($this->db->field_exists($c, 'configuracoes_nfe')) {
                $this->dbforge->drop_column('configuracoes_nfe', $c);
            }
        }
    }

    /** Adiciona uma coluna só se a tabela existir e a coluna faltar. */
    private function addColuna($tabela, $coluna, $definicao)
    {
        if ($this->db->table_exists($tabela) && ! $this->db->field_exists($coluna, $tabela)) {
            $this->db->query("ALTER TABLE `{$tabela}` ADD COLUMN {$definicao}");
        }
    }
}
