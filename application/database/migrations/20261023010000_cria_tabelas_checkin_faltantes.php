<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cria os_checkin, os_assinaturas e os_fotos_atendimento em bancos onde a
 * migration consolidada (20260720000000) falhou ao cria-las: ela usava
 * 'default' => 'CURRENT_TIMESTAMP', que o CI escreve entre aspas (erro 1067 no
 * MySQL/MariaDB), e o erro era engolido com db_debug desligado. A consolidada
 * ja foi corrigida para instalacoes novas; esta cobre as que ja rodaram.
 *
 * As definicoes ja incluem as colunas que migrations posteriores acrescentam
 * (geofence, distancias, imagem_base64), que tambem nao chegaram a esses bancos.
 *
 * Idempotente: so cria o que estiver faltando; nunca altera tabela existente.
 */
class Migration_cria_tabelas_checkin_faltantes extends CI_Migration
{
    private $tabelas = [
        'os_checkin' => "CREATE TABLE IF NOT EXISTS `os_checkin` (
            `idCheckin` INT(11) NOT NULL AUTO_INCREMENT,
            `os_id` INT(11) NOT NULL,
            `usuarios_id` INT(11) NOT NULL,
            `data_entrada` DATETIME NULL DEFAULT NULL,
            `data_saida` DATETIME NULL DEFAULT NULL,
            `latitude_entrada` DECIMAL(10,8) NULL DEFAULT NULL,
            `longitude_entrada` DECIMAL(11,8) NULL DEFAULT NULL,
            `distancia_entrada_metros` INT(11) NULL DEFAULT NULL COMMENT 'Distância (m) da entrada até o local da OS',
            `latitude_saida` DECIMAL(10,8) NULL DEFAULT NULL,
            `longitude_saida` DECIMAL(11,8) NULL DEFAULT NULL,
            `distancia_saida_metros` INT(11) NULL DEFAULT NULL COMMENT 'Distância (m) da saída até o local da OS',
            `observacao_entrada` TEXT NULL,
            `observacao_saida` TEXT NULL,
            `status` VARCHAR(30) NOT NULL DEFAULT 'Em Andamento',
            `data_cadastro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `data_atualizacao` DATETIME NULL DEFAULT NULL,
            `dentro_geofence_entrada` TINYINT(1) NULL DEFAULT NULL COMMENT '1=dentro da área, 0=fora, NULL=sem GPS/local',
            `dentro_geofence_saida` TINYINT(1) NULL DEFAULT NULL COMMENT '1=dentro da área, 0=fora, NULL=sem GPS/local',
            PRIMARY KEY (`idCheckin`),
            KEY `idx_os_id` (`os_id`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        'os_assinaturas' => "CREATE TABLE IF NOT EXISTS `os_assinaturas` (
            `idAssinatura` INT(11) NOT NULL AUTO_INCREMENT,
            `os_id` INT(11) NOT NULL,
            `checkin_id` INT(11) NULL DEFAULT NULL,
            `tipo` VARCHAR(20) NOT NULL COMMENT 'tecnico_entrada, tecnico_saida, cliente_saida',
            `assinatura` MEDIUMTEXT NOT NULL COMMENT 'Caminho do arquivo OU assinatura em base64 (BASE64:...)',
            `nome_assinante` VARCHAR(100) NULL DEFAULT NULL,
            `documento_assinante` VARCHAR(20) NULL DEFAULT NULL,
            `data_assinatura` DATETIME NOT NULL,
            `ip_address` VARCHAR(45) NULL DEFAULT NULL,
            `data_cadastro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`idAssinatura`),
            KEY `idx_os_id` (`os_id`),
            KEY `idx_tipo` (`tipo`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        'os_fotos_atendimento' => "CREATE TABLE IF NOT EXISTS `os_fotos_atendimento` (
            `idFoto` INT(11) NOT NULL AUTO_INCREMENT,
            `os_id` INT(11) NOT NULL,
            `checkin_id` INT(11) NULL DEFAULT NULL,
            `usuarios_id` INT(11) NOT NULL,
            `arquivo` VARCHAR(255) NOT NULL,
            `path` VARCHAR(255) NOT NULL,
            `url` VARCHAR(255) NOT NULL,
            `descricao` TEXT NULL,
            `etapa` VARCHAR(20) NOT NULL DEFAULT 'durante' COMMENT 'entrada, durante, saida',
            `tamanho` INT(11) NULL DEFAULT NULL,
            `tipo_arquivo` VARCHAR(10) NULL DEFAULT NULL,
            `data_upload` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `imagem_base64` LONGTEXT NULL COMMENT 'Imagem armazenada em base64',
            `mime_type` VARCHAR(30) NULL DEFAULT NULL COMMENT 'Tipo MIME da imagem (image/jpeg, image/png, etc)',
            PRIMARY KEY (`idFoto`),
            KEY `idx_os_id` (`os_id`),
            KEY `idx_etapa` (`etapa`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
    ];

    public function up()
    {
        foreach ($this->tabelas as $tabela => $sql) {
            if (! $this->db->table_exists($tabela)) {
                $this->db->query($sql);
                log_message('info', "Migration cria_tabelas_checkin_faltantes: tabela {$tabela} criada");
            }
        }
    }

    public function down()
    {
        // Nao remove: as tabelas guardam check-ins, fotos e assinaturas de atendimento.
    }
}
