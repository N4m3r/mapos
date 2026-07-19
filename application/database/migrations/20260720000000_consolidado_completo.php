<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * MIGRATION CONSOLIDADA (enxuta) — cria o schema do Mapos de forma direta e
 * idempotente: se a tabela nao existir, cria; se ja existir, mantem e apenas
 * completa o que falta.
 *
 *  1) criarBase(): CREATE TABLE IF NOT EXISTS de todas as tabelas base
 *     (extraidas do banco.sql — 1 statement por tabela, rapido pro MySQL).
 *  2) Passos das migrations que agregam algo novo (tabelas de modulos como
 *     fiscal/Cora/RH/notificacoes, colunas e seeds de permissoes/config). Cada
 *     passo e o codigo original da migration, ja idempotente (create_table IF
 *     NOT EXISTS, field_exists antes de add_column, seeds com guarda).
 *
 * As migrations antigas cujas colunas o banco.sql ja possui foram descartadas
 * (redundantes). Durante a execucao, FOREIGN_KEY_CHECKS e db_debug ficam
 * desligados para que operacoes ja aplicadas virem no-op em vez de abortar.
 *
 * Timestamp 20260720000000 > ultima antiga (20260719000001): roda 1x em qualquer
 * instalacao via migration->latest().
 */
class Migration_consolidado_completo extends CI_Migration
{
    public function up()
    {
        $dbg = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        try {
            $this->criarBase();
            $this->m20201224012424_06_up();
            $this->m20201230231550_07_up();
            $this->m20210105223548_08_up();
            $this->m20210107190526_09_up();
            $this->m20210108201419_10_up();
            $this->m20210110153941_11_up();
            $this->m20210114151942_12_up();
            $this->m20210114151943_13_up();
            $this->m20210114151944_14_up();
            $this->m20210125023104_15_up();
            $this->m20210125173737_17_up();
            $this->m20210125173738_18_up();
            $this->m20210125173739_19_up();
            $this->m20210125173740_20_up();
            $this->m20210125173741_21_up();
            $this->m20220313023104_24_up();
            $this->m20220320173741_25_up();
            $this->m20221130180810_28_up();
            $this->m20230428110810_29_up();
            $this->m20240503170400_30_up();
            $this->m20250403000001_31_up();
            $this->m20250403000002_32_up();
            $this->m20250404000001_33_up();
            $this->m20250404000002_34_up();
            $this->m20250404000003_35_up();
            $this->m20250405000001_36_up();
            $this->m20250405000002_37_up();
            $this->m20250405000003_38_up();
            $this->m20250406000001_39_up();
            $this->m20260705000001_40_up();
            $this->m20260706000001_41_up();
            $this->m20260706000002_42_up();
            $this->m20260706000003_43_up();
            $this->m20260706000004_44_up();
            $this->m20260706000005_45_up();
            $this->m20260708000001_46_up();
            $this->m20260708000002_47_up();
            $this->m20260709000001_48_up();
            $this->m20260709000002_49_up();
            $this->m20260709000003_50_up();
            $this->m20260709000004_51_up();
            $this->m20260709000005_52_up();
            $this->m20260709000006_53_up();
            $this->m20260710000001_54_up();
            $this->m20260711000001_55_up();
            $this->m20260711000002_56_up();
            $this->m20260711000003_57_up();
            $this->m20260711000004_58_up();
            $this->m20260711000005_59_up();
            $this->m20260711000006_60_up();
            $this->m20260712000001_61_up();
            $this->m20260712000002_62_up();
            $this->m20260712000003_63_up();
            $this->m20260712000004_64_up();
            $this->m20260712000005_65_up();
            $this->m20260714000001_66_up();
            $this->m20260715000001_67_up();
            $this->m20260715000002_68_up();
            $this->m20260716000001_69_up();
            $this->m20260718000001_70_up();
            $this->m20260718000001_71_up();
            $this->m20260719000001_72_up();
        } finally {
            $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
            $this->db->db_debug = $dbg;
        }
    }

    public function down()
    {
        $dbg = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        try {
            $this->m20260719000001_72_down();
            $this->m20260718000001_71_down();
            $this->m20260718000001_70_down();
            $this->m20260716000001_69_down();
            $this->m20260715000002_68_down();
            $this->m20260715000001_67_down();
            $this->m20260714000001_66_down();
            $this->m20260712000005_65_down();
            $this->m20260712000004_64_down();
            $this->m20260712000003_63_down();
            $this->m20260712000002_62_down();
            $this->m20260712000001_61_down();
            $this->m20260711000006_60_down();
            $this->m20260711000005_59_down();
            $this->m20260711000004_58_down();
            $this->m20260711000003_57_down();
            $this->m20260711000002_56_down();
            $this->m20260711000001_55_down();
            $this->m20260710000001_54_down();
            $this->m20260709000006_53_down();
            $this->m20260709000005_52_down();
            $this->m20260709000004_51_down();
            $this->m20260709000003_50_down();
            $this->m20260709000002_49_down();
            $this->m20260709000001_48_down();
            $this->m20260708000002_47_down();
            $this->m20260708000001_46_down();
            $this->m20260706000005_45_down();
            $this->m20260706000004_44_down();
            $this->m20260706000003_43_down();
            $this->m20260706000002_42_down();
            $this->m20260706000001_41_down();
            $this->m20260705000001_40_down();
            $this->m20250406000001_39_down();
            $this->m20250405000003_38_down();
            $this->m20250405000002_37_down();
            $this->m20250405000001_36_down();
            $this->m20250404000003_35_down();
            $this->m20250404000002_34_down();
            $this->m20250404000001_33_down();
            $this->m20250403000002_32_down();
            $this->m20250403000001_31_down();
            $this->m20240503170400_30_down();
            $this->m20230428110810_29_down();
            $this->m20221130180810_28_down();
            $this->m20220320173741_25_down();
            $this->m20220313023104_24_down();
            $this->m20210125173741_21_down();
            $this->m20210125173740_20_down();
            $this->m20210125173739_19_down();
            $this->m20210125173738_18_down();
            $this->m20210125173737_17_down();
            $this->m20210125023104_15_down();
            $this->m20210114151944_14_down();
            $this->m20210114151943_13_down();
            $this->m20210114151942_12_down();
            $this->m20210110153941_11_down();
            $this->m20210108201419_10_down();
            $this->m20210107190526_09_down();
            $this->m20210105223548_08_down();
            $this->m20201230231550_07_down();
            $this->m20201224012424_06_down();
            $this->dbforge->drop_table('configuracoes', true);
            $this->dbforge->drop_table('anotacoes_os', true);
            $this->dbforge->drop_table('email_queue', true);
            $this->dbforge->drop_table('logs', true);
            $this->dbforge->drop_table('equipamentos_os', true);
            $this->dbforge->drop_table('equipamentos', true);
            $this->dbforge->drop_table('marcas', true);
            $this->dbforge->drop_table('documentos', true);
            $this->dbforge->drop_table('anexos', true);
            $this->dbforge->drop_table('itens_de_vendas', true);
            $this->dbforge->drop_table('cobrancas', true);
            $this->dbforge->drop_table('vendas', true);
            $this->dbforge->drop_table('servicos_os', true);
            $this->dbforge->drop_table('servicos', true);
            $this->dbforge->drop_table('produtos_os', true);
            $this->dbforge->drop_table('produtos', true);
            $this->dbforge->drop_table('os', true);
            $this->dbforge->drop_table('garantias', true);
            $this->dbforge->drop_table('lancamentos', true);
            $this->dbforge->drop_table('usuarios', true);
            $this->dbforge->drop_table('permissoes', true);
            $this->dbforge->drop_table('contas', true);
            $this->dbforge->drop_table('categorias', true);
            $this->dbforge->drop_table('resets_de_senha', true);
            $this->dbforge->drop_table('clientes', true);
            $this->dbforge->drop_table('ci_sessions', true);
        } finally {
            $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
            $this->db->db_debug = $dbg;
        }
    }

    /** Cria as tabelas base (CREATE TABLE IF NOT EXISTS, do banco.sql). */
    private function criarBase()
    {
        $tabelas = [
            'CREATE TABLE IF NOT EXISTS `ci_sessions` ( `id` varchar(128) NOT NULL, `ip_address` varchar(45) NOT NULL, `timestamp` int(10) unsigned DEFAULT 0 NOT NULL, `data` blob NOT NULL, KEY `ci_sessions_timestamp` (`timestamp`) );',
            'CREATE TABLE IF NOT EXISTS `clientes` ( `idClientes` INT(11) NOT NULL AUTO_INCREMENT, `asaas_id` VARCHAR(255) DEFAULT NULL, `nomeCliente` VARCHAR(255) NOT NULL, `sexo` VARCHAR(20) NULL, `pessoa_fisica` BOOLEAN NOT NULL DEFAULT 1, `documento` VARCHAR(20) NOT NULL, `telefone` VARCHAR(20) NOT NULL, `celular` VARCHAR(20) NULL DEFAULT NULL, `email` VARCHAR(100) NOT NULL, `senha` VARCHAR(200) NOT NULL, `dataCadastro` DATE NULL DEFAULT NULL, `rua` VARCHAR(70) NULL DEFAULT NULL, `numero` VARCHAR(15) NULL DEFAULT NULL, `bairro` VARCHAR(45) NULL DEFAULT NULL, `cidade` VARCHAR(45) NULL DEFAULT NULL, `estado` VARCHAR(20) NULL DEFAULT NULL, `cep` VARCHAR(20) NULL DEFAULT NULL, `contato` varchar(45) DEFAULT NULL, `complemento` varchar(45) DEFAULT NULL, `fornecedor` BOOLEAN NOT NULL DEFAULT 0, PRIMARY KEY (`idClientes`)) ENGINE = InnoDB AUTO_INCREMENT = 1 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `resets_de_senha` ( `id` INT NOT NULL AUTO_INCREMENT, `email` VARCHAR(200) NOT NULL , `token` VARCHAR(255) NOT NULL , `data_expiracao` DATETIME NOT NULL, `token_utilizado` TINYINT NOT NULL, PRIMARY KEY (`id`)) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `categorias` ( `idCategorias` INT NOT NULL AUTO_INCREMENT, `categoria` VARCHAR(80) NULL, `cadastro` DATE NULL, `status` TINYINT(1) NULL, `tipo` VARCHAR(15) NULL, PRIMARY KEY (`idCategorias`)) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `contas` ( `idContas` INT NOT NULL AUTO_INCREMENT, `conta` VARCHAR(45) NULL, `banco` VARCHAR(45) NULL, `numero` VARCHAR(45) NULL, `saldo` DECIMAL(10,2) NULL, `cadastro` DATE NULL, `status` TINYINT(1) NULL, `tipo` VARCHAR(80) NULL, PRIMARY KEY (`idContas`)) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `permissoes` ( `idPermissao` INT NOT NULL AUTO_INCREMENT, `nome` VARCHAR(80) NOT NULL, `permissoes` TEXT NULL, `situacao` TINYINT(1) NULL, `data` DATE NULL, PRIMARY KEY (`idPermissao`)) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `usuarios` ( `idUsuarios` INT(11) NOT NULL AUTO_INCREMENT, `nome` VARCHAR(80) NOT NULL, `rg` VARCHAR(20) NULL DEFAULT NULL, `cpf` VARCHAR(20) NOT NULL, `cep` VARCHAR(9) NOT NULL, `rua` VARCHAR(70) NULL DEFAULT NULL, `numero` VARCHAR(15) NULL DEFAULT NULL, `bairro` VARCHAR(45) NULL DEFAULT NULL, `cidade` VARCHAR(45) NULL DEFAULT NULL, `estado` VARCHAR(20) NULL DEFAULT NULL, `email` VARCHAR(80) NOT NULL, `senha` VARCHAR(200) NOT NULL, `telefone` VARCHAR(20) NOT NULL, `celular` VARCHAR(20) NULL DEFAULT NULL, `situacao` TINYINT(1) NOT NULL, `dataCadastro` DATE NOT NULL, `permissoes_id` INT NOT NULL, `dataExpiracao` date DEFAULT NULL, `url_image_user` VARCHAR(255) DEFAULT NULL, PRIMARY KEY (`idUsuarios`), INDEX `fk_usuarios_permissoes1_idx` (`permissoes_id` ASC), CONSTRAINT `fk_usuarios_permissoes1` FOREIGN KEY (`permissoes_id`) REFERENCES `permissoes` (`idPermissao`) ON DELETE NO ACTION ON UPDATE NO ACTION) ENGINE = InnoDB AUTO_INCREMENT = 1 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `lancamentos` ( `idLancamentos` INT(11) NOT NULL AUTO_INCREMENT, `descricao` VARCHAR(255) NULL DEFAULT NULL, `valor` DECIMAL(10, 2) NULL DEFAULT 0, `desconto` DECIMAL(10, 2) NULL DEFAULT 0, `valor_desconto` DECIMAL(10, 2) NULL DEFAULT 0, `tipo_desconto` varchar(8) NULL DEFAULT NULL, `data_vencimento` DATE NOT NULL, `data_pagamento` DATE NULL DEFAULT NULL, `baixado` TINYINT(1) NULL DEFAULT 0, `cliente_fornecedor` VARCHAR(255) NULL DEFAULT NULL, `forma_pgto` VARCHAR(100) NULL DEFAULT NULL, `tipo` VARCHAR(45) NULL DEFAULT NULL, `anexo` VARCHAR(250) NULL, `observacoes` TEXT NULL, `clientes_id` INT(11) NULL DEFAULT NULL, `categorias_id` INT NULL, `contas_id` INT NULL, `vendas_id` INT NULL, `usuarios_id` INT NOT NULL, PRIMARY KEY (`idLancamentos`), INDEX `fk_lancamentos_clientes1` (`clientes_id` ASC), INDEX `fk_lancamentos_categorias1_idx` (`categorias_id` ASC), INDEX `fk_lancamentos_contas1_idx` (`contas_id` ASC), INDEX `fk_lancamentos_usuarios1` (`usuarios_id` ASC), CONSTRAINT `fk_lancamentos_clientes1` FOREIGN KEY (`clientes_id`) REFERENCES `clientes` (`idClientes`) ON DELETE NO ACTION ON UPDATE NO ACTION, CONSTRAINT `fk_lancamentos_categorias1` FOREIGN KEY (`categorias_id`) REFERENCES `categorias` (`idCategorias`) ON DELETE NO ACTION ON UPDATE NO ACTION, CONSTRAINT `fk_lancamentos_contas1` FOREIGN KEY (`contas_id`) REFERENCES `contas` (`idContas`) ON DELETE NO ACTION ON UPDATE NO ACTION, CONSTRAINT `fk_lancamentos_usuarios1` FOREIGN KEY (`usuarios_id`) REFERENCES `usuarios` (`idUsuarios`) ON DELETE NO ACTION ON UPDATE NO ACTION) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `garantias` ( `idGarantias` INT NOT NULL AUTO_INCREMENT, `dataGarantia` DATE NULL, `refGarantia` VARCHAR(15) NULL, `textoGarantia` TEXT NULL, `usuarios_id` INT(11) NULL, PRIMARY KEY (`idGarantias`), INDEX `fk_garantias_usuarios1` (`usuarios_id` ASC), CONSTRAINT `fk_garantias_usuarios1` FOREIGN KEY (`usuarios_id`) REFERENCES `usuarios` (`idUsuarios`) ON DELETE NO ACTION ON UPDATE NO ACTION) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `os` ( `idOs` INT(11) NOT NULL AUTO_INCREMENT, `dataInicial` DATE NULL DEFAULT NULL, `dataFinal` DATE NULL DEFAULT NULL, `garantia` VARCHAR(45) NULL DEFAULT NULL, `descricaoProduto` TEXT NULL DEFAULT NULL, `defeito` TEXT NULL DEFAULT NULL, `status` VARCHAR(45) NULL DEFAULT NULL, `observacoes` TEXT NULL DEFAULT NULL, `laudoTecnico` TEXT NULL DEFAULT NULL, `valorTotal` DECIMAL(10, 2) NULL DEFAULT 0, `desconto`DECIMAL(10, 2) NULL DEFAULT 0, `valor_desconto` DECIMAL(10, 2) NULL DEFAULT 0, `tipo_desconto` varchar(8) NULL DEFAULT NULL, `clientes_id` INT(11) NOT NULL, `usuarios_id` INT(11) NOT NULL, `lancamento` INT(11) NULL DEFAULT NULL, `faturado` TINYINT(1) NOT NULL, `garantias_id` int(11) NULL, PRIMARY KEY (`idOs`), INDEX `fk_os_clientes1` (`clientes_id` ASC), INDEX `fk_os_usuarios1` (`usuarios_id` ASC), INDEX `fk_os_lancamentos1` (`lancamento` ASC), INDEX `fk_os_garantias1` (`garantias_id` ASC), CONSTRAINT `fk_os_clientes1` FOREIGN KEY (`clientes_id`) REFERENCES `clientes` (`idClientes`) ON DELETE NO ACTION ON UPDATE NO ACTION, CONSTRAINT `fk_os_lancamentos1` FOREIGN KEY (`lancamento`) REFERENCES `lancamentos` (`idLancamentos`) ON DELETE NO ACTION ON UPDATE NO ACTION, CONSTRAINT `fk_os_usuarios1` FOREIGN KEY (`usuarios_id`) REFERENCES `usuarios` (`idUsuarios`) ON DELETE NO ACTION ON UPDATE NO ACTION) ENGINE = InnoDB AUTO_INCREMENT = 1 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `produtos` ( `idProdutos` INT(11) NOT NULL AUTO_INCREMENT, `codDeBarra` VARCHAR(70) NOT NULL, `descricao` VARCHAR(80) NOT NULL, `unidade` VARCHAR(10) NULL DEFAULT NULL, `precoCompra` DECIMAL(10,2) NULL DEFAULT NULL, `precoVenda` DECIMAL(10,2) NOT NULL, `estoque` INT(11) NOT NULL, `estoqueMinimo` INT(11) NULL DEFAULT NULL, `saida` TINYINT(1) NULL DEFAULT NULL, `entrada` TINYINT(1) NULL DEFAULT NULL, PRIMARY KEY (`idProdutos`)) ENGINE = InnoDB AUTO_INCREMENT = 1 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `produtos_os` ( `idProdutos_os` INT(11) NOT NULL AUTO_INCREMENT, `quantidade` INT(11) NOT NULL, `descricao` VARCHAR(80) NULL, `preco` DECIMAL(10,2) NULL DEFAULT 0, `os_id` INT(11) NOT NULL, `produtos_id` INT(11) NOT NULL, `subTotal` DECIMAL(10,2) NULL DEFAULT 0, PRIMARY KEY (`idProdutos_os`), INDEX `fk_produtos_os_os1` (`os_id` ASC), INDEX `fk_produtos_os_produtos1` (`produtos_id` ASC), CONSTRAINT `fk_produtos_os_os1` FOREIGN KEY (`os_id`) REFERENCES `os` (`idOs`) ON DELETE NO ACTION ON UPDATE NO ACTION, CONSTRAINT `fk_produtos_os_produtos1` FOREIGN KEY (`produtos_id`) REFERENCES `produtos` (`idProdutos`) ON DELETE NO ACTION ON UPDATE NO ACTION) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `servicos` ( `idServicos` INT(11) NOT NULL AUTO_INCREMENT, `nome` VARCHAR(45) NOT NULL, `descricao` VARCHAR(45) NULL DEFAULT NULL, `preco` DECIMAL(10,2) NOT NULL, PRIMARY KEY (`idServicos`)) ENGINE = InnoDB AUTO_INCREMENT = 1 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `servicos_os` ( `idServicos_os` INT(11) NOT NULL AUTO_INCREMENT, `servico` VARCHAR(80) NULL, `quantidade` DOUBLE NULL, `preco` DECIMAL(10,2) NULL DEFAULT 0, `os_id` INT(11) NOT NULL, `servicos_id` INT(11) NOT NULL, `subTotal` DECIMAL(10,2) NULL DEFAULT 0, PRIMARY KEY (`idServicos_os`), INDEX `fk_servicos_os_os1` (`os_id` ASC), INDEX `fk_servicos_os_servicos1` (`servicos_id` ASC), CONSTRAINT `fk_servicos_os_os1` FOREIGN KEY (`os_id`) REFERENCES `os` (`idOs`) ON DELETE NO ACTION ON UPDATE NO ACTION, CONSTRAINT `fk_servicos_os_servicos1` FOREIGN KEY (`servicos_id`) REFERENCES `servicos` (`idServicos`) ON DELETE NO ACTION ON UPDATE NO ACTION) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `vendas` ( `idVendas` INT NOT NULL AUTO_INCREMENT, `dataVenda` DATE NULL, `valorTotal` DECIMAL(10, 2) NULL DEFAULT 0, `desconto` DECIMAL(10, 2) NULL DEFAULT 0, `valor_desconto` DECIMAL(10, 2) NULL DEFAULT 0, `tipo_desconto` varchar(8) NULL DEFAULT NULL, `faturado` TINYINT(1) NULL, `observacoes` TEXT NULL, `observacoes_cliente` TEXT NULL, `clientes_id` INT(11) NOT NULL, `usuarios_id` INT(11) NULL, `lancamentos_id` INT(11) NULL, `status` VARCHAR(45) NULL, `garantia` INT(11) NULL, PRIMARY KEY (`idVendas`), INDEX `fk_vendas_clientes1` (`clientes_id` ASC), INDEX `fk_vendas_usuarios1` (`usuarios_id` ASC), INDEX `fk_vendas_lancamentos1` (`lancamentos_id` ASC), CONSTRAINT `fk_vendas_clientes1` FOREIGN KEY (`clientes_id`) REFERENCES `clientes` (`idClientes`) ON DELETE NO ACTION ON UPDATE NO ACTION, CONSTRAINT `fk_vendas_usuarios1` FOREIGN KEY (`usuarios_id`) REFERENCES `usuarios` (`idUsuarios`) ON DELETE NO ACTION ON UPDATE NO ACTION, CONSTRAINT `fk_vendas_lancamentos1` FOREIGN KEY (`lancamentos_id`) REFERENCES `lancamentos` (`idLancamentos`) ON DELETE NO ACTION ON UPDATE NO ACTION) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `cobrancas` ( `idCobranca` INT(11) NOT NULL AUTO_INCREMENT, `charge_id` varchar(255) DEFAULT NULL, `conditional_discount_date` date DEFAULT NULL, `created_at` datetime DEFAULT NULL, `custom_id` int(11) DEFAULT NULL, `expire_at` date NOT NULL, `message` varchar(255) NOT NULL, `payment_method` varchar(11) DEFAULT NULL, `payment_url` varchar(255) DEFAULT NULL, `request_delivery_address` varchar(64) DEFAULT NULL, `status` varchar(36) NOT NULL, `total` varchar(15) DEFAULT NULL, `barcode` varchar(255) NOT NULL, `link` varchar(255) NOT NULL, `payment_gateway` varchar(255) NULL DEFAULT NULL, `payment` varchar(64) NOT NULL, `pdf` varchar(255) DEFAULT NULL, `vendas_id` int(11) DEFAULT NULL, `os_id` int(11) DEFAULT NULL, `clientes_id` int(11) DEFAULT NULL, PRIMARY KEY (`idCobranca`), INDEX `fk_cobrancas_os1` (`os_id` ASC), CONSTRAINT `fk_cobrancas_os1` FOREIGN KEY (`os_id`) REFERENCES `os` (`idOs`) ON DELETE NO ACTION ON UPDATE NO ACTION, INDEX `fk_cobrancas_vendas1` (`vendas_id` ASC), CONSTRAINT `fk_cobrancas_vendas1` FOREIGN KEY (`vendas_id`) REFERENCES `vendas` (`idVendas`) ON DELETE NO ACTION ON UPDATE NO ACTION, INDEX `fk_cobrancas_clientes1` (`clientes_id` ASC), CONSTRAINT `fk_cobrancas_clientes1` FOREIGN KEY (`clientes_id`) REFERENCES `clientes` (`idClientes`) ON DELETE NO ACTION ON UPDATE NO ACTION ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `itens_de_vendas` ( `idItens` INT NOT NULL AUTO_INCREMENT, `subTotal` DECIMAL(10,2) NULL DEFAULT 0, `quantidade` INT(11) NULL, `preco` DECIMAL(10,2) NULL DEFAULT 0, `vendas_id` INT NOT NULL, `produtos_id` INT(11) NOT NULL, PRIMARY KEY (`idItens`), INDEX `fk_itens_de_vendas_vendas1` (`vendas_id` ASC), INDEX `fk_itens_de_vendas_produtos1` (`produtos_id` ASC), CONSTRAINT `fk_itens_de_vendas_vendas1` FOREIGN KEY (`vendas_id`) REFERENCES `vendas` (`idVendas`) ON DELETE NO ACTION ON UPDATE NO ACTION, CONSTRAINT `fk_itens_de_vendas_produtos1` FOREIGN KEY (`produtos_id`) REFERENCES `produtos` (`idProdutos`) ON DELETE NO ACTION ON UPDATE NO ACTION) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `anexos` ( `idAnexos` INT NOT NULL AUTO_INCREMENT, `anexo` VARCHAR(45) NULL, `thumb` VARCHAR(45) NULL, `url` VARCHAR(300) NULL, `path` VARCHAR(300) NULL, `os_id` INT(11) NOT NULL, PRIMARY KEY (`idAnexos`), INDEX `fk_anexos_os1` (`os_id` ASC), CONSTRAINT `fk_anexos_os1` FOREIGN KEY (`os_id`) REFERENCES `os` (`idOs`) ON DELETE NO ACTION ON UPDATE NO ACTION) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `documentos` ( `idDocumentos` INT NOT NULL AUTO_INCREMENT, `documento` VARCHAR(70) NULL, `descricao` TEXT NULL, `file` VARCHAR(100) NULL, `path` VARCHAR(300) NULL, `url` VARCHAR(300) NULL, `cadastro` DATE NULL, `categoria` VARCHAR(80) NULL, `tipo` VARCHAR(15) NULL, `tamanho` VARCHAR(45) NULL, PRIMARY KEY (`idDocumentos`)) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `marcas` ( `idMarcas` INT NOT NULL AUTO_INCREMENT, `marca` VARCHAR(100) NULL, `cadastro` DATE NULL, `situacao` TINYINT(1) NULL, PRIMARY KEY (`idMarcas`)) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `equipamentos` ( `idEquipamentos` INT NOT NULL AUTO_INCREMENT, `equipamento` VARCHAR(150) NOT NULL, `num_serie` VARCHAR(80) NULL, `modelo` VARCHAR(80) NULL, `cor` VARCHAR(45) NULL, `descricao` VARCHAR(150) NULL, `tensao` VARCHAR(45) NULL, `potencia` VARCHAR(45) NULL, `voltagem` VARCHAR(45) NULL, `data_fabricacao` DATE NULL, `marcas_id` INT NULL, `clientes_id` INT(11) NULL, PRIMARY KEY (`idEquipamentos`), INDEX `fk_equipanentos_marcas1_idx` (`marcas_id` ASC), INDEX `fk_equipanentos_clientes1_idx` (`clientes_id` ASC), CONSTRAINT `fk_equipanentos_marcas1` FOREIGN KEY (`marcas_id`) REFERENCES `marcas` (`idMarcas`) ON DELETE NO ACTION ON UPDATE NO ACTION, CONSTRAINT `fk_equipanentos_clientes1` FOREIGN KEY (`clientes_id`) REFERENCES `clientes` (`idClientes`) ON DELETE NO ACTION ON UPDATE NO ACTION) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `equipamentos_os` ( `idEquipamentos_os` INT NOT NULL AUTO_INCREMENT, `defeito_declarado` VARCHAR(200) NULL, `defeito_encontrado` VARCHAR(200) NULL, `solucao` VARCHAR(45) NULL, `equipamentos_id` INT NULL, `os_id` INT(11) NULL, PRIMARY KEY (`idEquipamentos_os`), INDEX `fk_equipamentos_os_equipanentos1_idx` (`equipamentos_id` ASC), INDEX `fk_equipamentos_os_os1_idx` (`os_id` ASC), CONSTRAINT `fk_equipamentos_os_equipanentos1` FOREIGN KEY (`equipamentos_id`) REFERENCES `equipamentos` (`idEquipamentos`) ON DELETE NO ACTION ON UPDATE NO ACTION, CONSTRAINT `fk_equipamentos_os_os1` FOREIGN KEY (`os_id`) REFERENCES `os` (`idOs`) ON DELETE NO ACTION ON UPDATE NO ACTION) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `logs` ( `idLogs` INT NOT NULL AUTO_INCREMENT, `usuario` VARCHAR(80) NULL, `tarefa` VARCHAR(100) NULL, `data` DATE NULL, `hora` TIME NULL, `ip` VARCHAR(45) NULL, PRIMARY KEY (`idLogs`)) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `email_queue` ( `id` int(11) NOT NULL AUTO_INCREMENT, `to` varchar(255) NOT NULL, `cc` varchar(255) DEFAULT NULL, `bcc` varchar(255) DEFAULT NULL, `message` text NOT NULL, `status` enum(\'pending\',\'sending\',\'sent\',\'failed\') DEFAULT NULL, `date` datetime DEFAULT NULL, `headers` text, PRIMARY KEY (`id`) )ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `anotacoes_os` ( `idAnotacoes` INT(11) NOT NULL AUTO_INCREMENT, `anotacao` VARCHAR(255) NOT NULL , `data_hora` DATETIME NOT NULL , `os_id` INT(11) NOT NULL , PRIMARY KEY (`idAnotacoes`) ) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;',
            'CREATE TABLE IF NOT EXISTS `configuracoes` ( `idConfig` INT NOT NULL AUTO_INCREMENT , `config` VARCHAR(20) NOT NULL UNIQUE, `valor` TEXT NULL , PRIMARY KEY (`idConfig`) ) ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;'
        ];
        foreach ($tabelas as $sql) {
            $this->db->query($sql);
        }
    }

    // =====================================================================
    // PASSOS — modulos/colunas/seeds (codigo original prefixado)
    // =====================================================================

    // ---- 20201224012424_add_cep_to_emitente_table.php (add coluna nao coberta: emitente.cep) ----
    private function m20201224012424_06_up()
    {
        $this->dbforge->add_column('emitente', [
            'cep' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
                'default' => null,
            ],
        ]);
    }

    private function m20201224012424_06_down()
    {
        $this->dbforge->drop_column('emitente', 'cep');
    }

    // ---- 20201230231550_add_controle_cobrancas.php (faz seed (insert/update)) ----
    private function m20201230231550_07_up()
    {
        $this->dbforge->add_field([
            'idCobranca' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'auto_increment' => true,
            ],
            'charge_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'conditional_discount_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'custom_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'expire_at' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'message' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 36,
                'null' => true,
            ],
            'payment_url' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'request_delivery_address' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 36,
                'null' => true,
            ],
            'total' => [
                'type' => 'VARCHAR',
                'constraint' => 15,
                'null' => true,
            ],
            'barcode' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'link' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'payment' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'pdf' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
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
        ]);
        $this->dbforge->add_key('idCobranca', true);
        $this->dbforge->create_table('cobrancas', true);
        $this->db->query('ALTER TABLE  `cobrancas` ADD INDEX `fk_cobrancas_os1` (`os_id` ASC)');
        $this->db->query('ALTER TABLE  `cobrancas` ADD CONSTRAINT `fk_cobrancas_os1`
			FOREIGN KEY (`os_id`)
			REFERENCES `os` (`idOs`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
        ');
        $this->db->query('ALTER TABLE  `cobrancas` ADD INDEX `fk_cobrancas_vendas1` (`vendas_id` ASC)');
        $this->db->query('ALTER TABLE  `cobrancas` ADD CONSTRAINT `fk_cobrancas_vendas1`
			FOREIGN KEY (`vendas_id`)
			REFERENCES `vendas` (`idVendas`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
		');
        $this->db->query('ALTER TABLE `cobrancas` ENGINE = InnoDB');
    }

    private function m20201230231550_07_down()
    {
        $this->dbforge->drop_table('cobrancas');
    }

    // ---- 20210105223548_add_cobrancas_cliente.php (faz seed (insert/update)) ----
    private function m20210105223548_08_up()
    {
        $this->dbforge->add_column('cobrancas', [
            'clientes_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
        ]);
        $this->db->query('ALTER TABLE  `cobrancas` ADD INDEX `fk_cobrancas_clientes1` (`clientes_id` ASC)');
        $this->db->query('ALTER TABLE  `cobrancas` ADD CONSTRAINT `fk_cobrancas_clientes1`
			FOREIGN KEY (`clientes_id`)
			REFERENCES `clientes` (`idClientes`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
        ');
    }

    private function m20210105223548_08_down()
    {
        $this->dbforge->drop_table('cobrancas');
    }

    // ---- 20210107190526_fix_table_cobrancas.php (muda tipo/charset/drop) ----
    private function m20210107190526_09_up()
    {
        $this->db->query('ALTER TABLE `cobrancas` CHANGE `idCobranca` `idCobranca` INT(11) NOT NULL AUTO_INCREMENT');
    }

    private function m20210107190526_09_down()
    {
        $this->dbforge->drop_table('cobrancas');
    }

    // ---- 20210108201419_add_usuarios_lancamentos.php (faz seed (insert/update)) ----
    private function m20210108201419_10_up()
    {
        $this->dbforge->add_column('lancamentos', [
            'usuarios_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
        ]);
        $this->db->query('ALTER TABLE `lancamentos` ADD INDEX `fk_lancamentos_usuarios1` (`usuarios_id` ASC)');
        $this->db->query('ALTER TABLE `lancamentos` ADD CONSTRAINT `fk_lancamentos_usuarios1`
			FOREIGN KEY (`usuarios_id`)
			REFERENCES `usuarios` (`idUsuarios`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
        ');
    }

    private function m20210108201419_10_down()
    {
        $this->dbforge->drop_column('lancamentos', 'usuarios_id');
    }

    // ---- 20210110153941_feature_notificawhats.php (faz seed (insert/update); muda tipo/charset/drop) ----
    private function m20210110153941_11_up()
    {
        $this->db->query('ALTER TABLE `configuracoes` CHANGE `valor` `valor` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL');
        $sql = "INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES ('7', 'notifica_whats', 'Prezado(a), {CLIENTE_NOME} a OS de nº {NUMERO_OS} teve o status alterado para :{STATUS_OS} segue a descrição {DESCRI_PRODUTOS} com valor total de {VALOR_OS}!\\r\\nPara mais informações entre em contato conosco.\\r\\nAtenciosamente, {EMITENTE} {TELEFONE_EMITENTE}.')";
        $this->db->query($sql);
    }

    private function m20210110153941_11_down()
    {
        $this->db->query('DELETE FROM `configuracoes` WHERE `configuracoes`.`idConfig` = 7');
        $this->db->query('ALTER TABLE `configuracoes` CHANGE `valor` `valor` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;');
    }

    // ---- 20210114151942_feature_control_baixaretroativa.php (faz seed (insert/update)) ----
    private function m20210114151942_12_up()
    {
        $sql = "INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (8, 'control_baixa', 0)";
        $this->db->query($sql);
    }

    private function m20210114151942_12_down()
    {
        $this->db->query('DELETE FROM `configuracoes` WHERE `configuracoes`.`idConfig` = 8');
    }

    // ---- 20210114151943_drop_table_pagamento.php (faz seed (insert/update); muda tipo/charset/drop) ----
    private function m20210114151943_13_up()
    {
        $this->dbforge->drop_table('pagamento');
        $this->db->query("UPDATE permissoes
        SET permissoes = 'a:53:{s:8:\"aCliente\";s:1:\"1\";s:8:\"eCliente\";s:1:\"1\";s:8:\"dCliente\";s:1:\"1\";s:8:\"vCliente\";s:1:\"1\";s:8:\"aProduto\";s:1:\"1\";s:8:\"eProduto\";s:1:\"1\";s:8:\"dProduto\";s:1:\"1\";s:8:\"vProduto\";s:1:\"1\";s:8:\"aServico\";s:1:\"1\";s:8:\"eServico\";s:1:\"1\";s:8:\"dServico\";s:1:\"1\";s:8:\"vServico\";s:1:\"1\";s:3:\"aOs\";s:1:\"1\";s:3:\"eOs\";s:1:\"1\";s:3:\"dOs\";s:1:\"1\";s:3:\"vOs\";s:1:\"1\";s:6:\"aVenda\";s:1:\"1\";s:6:\"eVenda\";s:1:\"1\";s:6:\"dVenda\";s:1:\"1\";s:6:\"vVenda\";s:1:\"1\";s:9:\"aGarantia\";s:1:\"1\";s:9:\"eGarantia\";s:1:\"1\";s:9:\"dGarantia\";s:1:\"1\";s:9:\"vGarantia\";s:1:\"1\";s:8:\"aArquivo\";s:1:\"1\";s:8:\"eArquivo\";s:1:\"1\";s:8:\"dArquivo\";s:1:\"1\";s:8:\"vArquivo\";s:1:\"1\";s:10:\"aPagamento\";N;s:10:\"ePagamento\";N;s:10:\"dPagamento\";N;s:10:\"vPagamento\";N;s:11:\"aLancamento\";s:1:\"1\";s:11:\"eLancamento\";s:1:\"1\";s:11:\"dLancamento\";s:1:\"1\";s:11:\"vLancamento\";s:1:\"1\";s:8:\"cUsuario\";s:1:\"1\";s:9:\"cEmitente\";s:1:\"1\";s:10:\"cPermissao\";s:1:\"1\";s:7:\"cBackup\";s:1:\"1\";s:10:\"cAuditoria\";s:1:\"1\";s:6:\"cEmail\";s:1:\"1\";s:8:\"cSistema\";s:1:\"1\";s:8:\"rCliente\";s:1:\"1\";s:8:\"rProduto\";s:1:\"1\";s:8:\"rServico\";s:1:\"1\";s:3:\"rOs\";s:1:\"1\";s:6:\"rVenda\";s:1:\"1\";s:11:\"rFinanceiro\";s:1:\"1\";s:9:\"aCobranca\";s:1:\"1\";s:9:\"eCobranca\";s:1:\"1\";s:9:\"dCobranca\";s:1:\"1\";s:9:\"vCobranca\";s:1:\"1\";}'
        WHERE idPermissao = 1;
        ");
    }

    private function m20210114151943_13_down()
    {
        $this->db->query("UPDATE permissoes
        SET permissoes = 'a:53:{s:8:\"aCliente\";s:1:\"1\";s:8:\"eCliente\";s:1:\"1\";s:8:\"dCliente\";s:1:\"1\";s:8:\"vCliente\";s:1:\"1\";s:8:\"aProduto\";s:1:\"1\";s:8:\"eProduto\";s:1:\"1\";s:8:\"dProduto\";s:1:\"1\";s:8:\"vProduto\";s:1:\"1\";s:8:\"aServico\";s:1:\"1\";s:8:\"eServico\";s:1:\"1\";s:8:\"dServico\";s:1:\"1\";s:8:\"vServico\";s:1:\"1\";s:3:\"aOs\";s:1:\"1\";s:3:\"eOs\";s:1:\"1\";s:3:\"dOs\";s:1:\"1\";s:3:\"vOs\";s:1:\"1\";s:6:\"aVenda\";s:1:\"1\";s:6:\"eVenda\";s:1:\"1\";s:6:\"dVenda\";s:1:\"1\";s:6:\"vVenda\";s:1:\"1\";s:9:\"aGarantia\";s:1:\"1\";s:9:\"eGarantia\";s:1:\"1\";s:9:\"dGarantia\";s:1:\"1\";s:9:\"vGarantia\";s:1:\"1\";s:8:\"aArquivo\";s:1:\"1\";s:8:\"eArquivo\";s:1:\"1\";s:8:\"dArquivo\";s:1:\"1\";s:8:\"vArquivo\";s:1:\"1\";s:10:\"aPagamento\";s:1:\"1\";s:10:\"ePagamento\";s:1:\"1\";s:10:\"dPagamento\";s:1:\"1\";s:10:\"vPagamento\";s:1:\"1\";s:11:\"aLancamento\";s:1:\"1\";s:11:\"eLancamento\";s:1:\"1\";s:11:\"dLancamento\";s:1:\"1\";s:11:\"vLancamento\";s:1:\"1\";s:8:\"cUsuario\";s:1:\"1\";s:9:\"cEmitente\";s:1:\"1\";s:10:\"cPermissao\";s:1:\"1\";s:7:\"cBackup\";s:1:\"1\";s:10:\"cAuditoria\";s:1:\"1\";s:6:\"cEmail\";s:1:\"1\";s:8:\"cSistema\";s:1:\"1\";s:8:\"rCliente\";s:1:\"1\";s:8:\"rProduto\";s:1:\"1\";s:8:\"rServico\";s:1:\"1\";s:3:\"rOs\";s:1:\"1\";s:6:\"rVenda\";s:1:\"1\";s:11:\"rFinanceiro\";s:1:\"1\";s:9:\"aCobranca\";s:1:\"1\";s:9:\"eCobranca\";s:1:\"1\";s:9:\"dCobranca\";s:1:\"1\";s:9:\"vCobranca\";s:1:\"1\";}'
        WHERE idPermissao = 1;
        ");
        $this->db->query('CREATE TABLE `pagamento` (
        `idPag` INT NOT NULL AUTO_INCREMENT,
        `nome` varchar(50) COLLATE utf8_bin NOT NULL,
        `client_id` varchar(200) COLLATE utf8_bin NOT NULL,
        `client_secret` varchar(200) COLLATE utf8_bin NOT NULL,
        `public_key` varchar(200) COLLATE utf8_bin NOT NULL,
        `access_token` varchar(200) COLLATE utf8_bin NOT NULL,
        `default_pag` int(1) NOT NULL,
        PRIMARY KEY (`idPag`)
        ) ENGINE=InnoDB;');
    }

    // ---- 20210114151944_add_payment_gateway_to_cobrancas.php (faz seed (insert/update)) ----
    private function m20210114151944_14_up()
    {
        $this->dbforge->add_column('cobrancas', [
            'payment_gateway' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null,
            ],
        ]);
        $this->db->query('UPDATE cobrancas SET payment_gateway="GerencianetSdk" WHERE payment_gateway IS NULL');
    }

    private function m20210114151944_14_down()
    {
        $this->db->query('UPDATE cobrancas SET payment_gateway=NULL');
        $this->dbforge->drop_column('cobrancas', 'payment_gateway');
    }

    // ---- 20210125023104_controle_editar_os.php (faz seed (insert/update)) ----
    private function m20210125023104_15_up()
    {
        $sql = "INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (9, 'control_editos', 1)";
        $this->db->query($sql);
    }

    private function m20210125023104_15_down()
    {
        $this->db->query('DELETE FROM `configuracoes` WHERE `configuracoes`.`idConfig` = 9');
    }

    // ---- 20210125173737_add_control_datatable.php (faz seed (insert/update)) ----
    private function m20210125173737_17_up()
    {
        $sql = "INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (10, 'control_datatable', 1)";
        $this->db->query($sql);
    }

    private function m20210125173737_17_down()
    {
        $this->db->query('DELETE FROM `configuracoes` WHERE `configuracoes`.`idConfig` = 10');
    }

    // ---- 20210125173738_add_pix_key.php (faz seed (insert/update)) ----
    private function m20210125173738_18_up()
    {
        $sql = "INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (11, 'pix_key', '')";
        $this->db->query($sql);
    }

    private function m20210125173738_18_down()
    {
        $this->db->query('DELETE FROM `configuracoes` WHERE `configuracoes`.`idConfig` = 11');
    }

    // ---- 20210125173739_add_os_status_list.php (faz seed (insert/update)) ----
    private function m20210125173739_19_up()
    {
        $sql = "INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (12, 'os_status_list', '[\"Aberto\",\"Faturado\",\"Negocia\\u00e7\\u00e3o\",\"Em Andamento\",\"Or\\u00e7amento\",\"Finalizado\",\"Cancelado\",\"Aguardando Pe\\u00e7as\"]')";
        $this->db->query($sql);
    }

    private function m20210125173739_19_down()
    {
        $this->db->query('DELETE FROM `configuracoes` WHERE `configuracoes`.`idConfig` = 12');
    }

    // ---- 20210125173740_add_aprovado_to_status_list.php (faz seed (insert/update)) ----
    private function m20210125173740_20_up()
    {
        $configurationSql = '
            SELECT valor
            FROM configuracoes
            WHERE idConfig = 12
            LIMIT 1
        ';
        $result = $this->db->query($configurationSql)->row();

        $osStatus = json_decode($result->valor, true);
        if (empty($osStatus)) {
            $osStatus = ['Aberto', 'Faturado', 'Negociação', 'Em Andamento', 'Orçamento', 'Finalizado', 'Cancelado', 'Aguardando Peças', 'Aprovado'];
        } else {
            $osStatus[] = 'Aprovado';
        }

        $sql = 'UPDATE `configuracoes` SET valor = ? WHERE idConfig = 12';
        $this->db->query($sql, [json_encode($osStatus)]);
    }

    private function m20210125173740_20_down()
    {
        $this->db->query("UPDATE `configuracoes` SET valor = '[\"Aberto\",\"Faturado\",\"Negocia\\u00e7\\u00e3o\",\"Em Andamento\",\"Or\\u00e7amento\",\"Finalizado\",\"Cancelado\",\"Aguardando Pe\\u00e7as\"]' WHERE idConfig = 12");
    }

    // ---- 20210125173741_asaas_payment_gateway.php (muda tipo/charset/drop) ----
    private function m20210125173741_21_up()
    {
        // Um comando SQL por chamada: o driver mysqli/MySQL 8 rejeita
        // multiplas instrucoes num unico query() (erro 1064).
        if (! $this->db->field_exists('asaas_id', 'clientes')) {
            $this->db->query('ALTER TABLE clientes ADD asaas_id varchar(255) NULL');
        }
        $this->db->query('ALTER TABLE cobrancas MODIFY COLUMN charge_id VARCHAR(255) NOT NULL');
    }

    private function m20210125173741_21_down()
    {
        $this->db->query('ALTER TABLE cobrancas MODIFY COLUMN charge_id INT(11) NOT NULL');
        if ($this->db->field_exists('asaas_id', 'clientes')) {
            $this->db->query('ALTER TABLE clientes DROP COLUMN asaas_id');
        }
    }

    // ---- 20220313023104_controle_editar_vendas.php (faz seed (insert/update)) ----
    private function m20220313023104_24_up()
    {
        $this->db->query("INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (13, 'control_edit_vendas', 1);");
    }

    private function m20220313023104_24_down()
    {
        $this->db->query('DELETE FROM `configuracoes` WHERE `configuracoes`.`idConfig` = 13');
    }

    // ---- 20220320173741_add_desconto_lancamentos_os_vendas.php (faz seed (insert/update); muda tipo/charset/drop) ----
    private function m20220320173741_25_up()
    {
        $this->db->query('ALTER TABLE `lancamentos` CHANGE `valor` `valor` DECIMAL(10,2) NOT NULL DEFAULT 0');
        $this->db->query('ALTER TABLE `lancamentos` ADD `desconto` DECIMAL(10, 2) NULL DEFAULT 0');
        $this->db->query('ALTER TABLE `lancamentos` ADD `valor_desconto` DECIMAL(10, 2) NULL DEFAULT 0');
        $this->db->query('ALTER TABLE `os` CHANGE `valorTotal` `valorTotal` DECIMAL(10,2) NULL DEFAULT 0');
        $this->db->query('ALTER TABLE `os` ADD `desconto` DECIMAL(10, 2) NULL DEFAULT 0');
        $this->db->query('ALTER TABLE `os` ADD `valor_desconto` DECIMAL(10, 2) NULL DEFAULT 0');
        $this->db->query('ALTER TABLE `vendas` CHANGE `valorTotal` `valorTotal` DECIMAL(10,2) NULL DEFAULT 0');
        $this->db->query('ALTER TABLE `vendas` CHANGE `desconto` `desconto` DECIMAL(10,2) NULL DEFAULT 0');
        $this->db->query('ALTER TABLE `vendas` ADD `valor_desconto` DECIMAL(10, 2) NULL DEFAULT 0');
        $this->db->query('ALTER TABLE `cobrancas` CHANGE `total` `total` DECIMAL(10,2) NULL DEFAULT 0');
        $this->db->query('ALTER TABLE `produtos_os` CHANGE `preco` `preco` DECIMAL(10,2) NULL DEFAULT 0');
        $this->db->query('ALTER TABLE `produtos_os` CHANGE `subTotal` `subTotal` DECIMAL(10,2) NULL DEFAULT 0');
        $this->db->query('ALTER TABLE `servicos_os` CHANGE `preco` `preco` DECIMAL(10,2) NULL DEFAULT 0');
        $this->db->query('ALTER TABLE `servicos_os` CHANGE `subTotal` `subTotal` DECIMAL(10,2) NULL DEFAULT 0');
        $this->db->query('ALTER TABLE `itens_de_vendas` CHANGE `subTotal` `subTotal` DECIMAL(10,2) NULL DEFAULT 0');
        $this->db->query('ALTER TABLE `itens_de_vendas` CHANGE `preco` `preco` DECIMAL(10,2) NULL DEFAULT 0');
        $this->db->query("INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (14, 'email_automatico', 1)");
    }

    private function m20220320173741_25_down()
    {
        $this->db->query('ALTER TABLE `lancamentos` DROP `desconto`');
        $this->db->query('ALTER TABLE `lancamentos` DROP `valor_desconto`');
        $this->db->query('ALTER TABLE `os` DROP `desconto`');
        $this->db->query('ALTER TABLE `os` DROP `valor_desconto`');
        $this->db->query('ALTER TABLE `vendas` DROP `desconto`');
        $this->db->query('ALTER TABLE `vendas` DROP `valor_desconto`');
        $this->db->query('DELETE FROM `configuracoes` WHERE `configuracoes`.`idConfig` = 14');
    }

    // ---- 20221130180810_add_config_control_print_2ways_os.php (faz seed (insert/update)) ----
    private function m20221130180810_28_up()
    {
        $this->db->query("INSERT INTO `configuracoes` (`idConfig`, `config`, `valor`) VALUES (15, 'control_2vias', 0);");
    }

    private function m20221130180810_28_down()
    {
        $this->db->query('DELETE FROM `configuracoes` WHERE `configuracoes`.`idConfig` = 15');
    }

    // ---- 20230428110810_alter_charset_configuracoes.php (faz seed (insert/update); muda tipo/charset/drop) ----
    private function m20230428110810_29_up()
    {
        $this->db->query('ALTER TABLE `configuracoes` CHANGE `config` `config` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;');
        $this->db->query("UPDATE `configuracoes` SET `valor` = 'Prezado(a), {CLIENTE_NOME} a OS de nº {NUMERO_OS} teve o status alterado para: {STATUS_OS} segue a descrição {DESCRI_PRODUTOS} com valor total de {VALOR_OS}! Para mais informações entre em contato conosco. Atenciosamente, {EMITENTE} {TELEFONE_EMITENTE}.' WHERE `configuracoes`.`idConfig` = 7");
    }

    private function m20230428110810_29_down()
    {
        $this->db->query('DELETE FROM `configuracoes` WHERE `configuracoes`.`idConfig` = 7');
    }

    // ---- 20240503170400_add_garantia_status_to_vendas_table.php (add coluna nao parseavel (mantida por seguranca)) ----
    private function m20240503170400_30_up()
    {
        // Adiciona o campo garantia
        $this->dbforge->add_column('vendas', array(
            'garantia' => array(
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
            ),
        ));

        // Adiciona o campo status
        $this->dbforge->add_column('vendas', array(
            'status' => array(
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
            ),
        ));
    }

    private function m20240503170400_30_down()
    {
        // Remove o campo garantia
        $this->dbforge->drop_column('vendas', 'garantia');

        // Remove o campo status
        $this->dbforge->drop_column('vendas', 'status');
    }

    // ---- 20250403000001_add_checkin_tables.php (cria tabela nova: os_assinaturas,os_checkin,os_fotos_atendimento) ----
    private function m20250403000001_31_up()
    {
        // Tabela de Check-in/Check-out da OS
        $this->dbforge->add_field([
            'idCheckin' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'auto_increment' => true,
            ],
            'os_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'usuarios_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'data_entrada' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'data_saida' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'latitude_entrada' => [
                'type' => 'DECIMAL',
                'constraint' => '10,8',
                'null' => true,
            ],
            'longitude_entrada' => [
                'type' => 'DECIMAL',
                'constraint' => '11,8',
                'null' => true,
            ],
            'latitude_saida' => [
                'type' => 'DECIMAL',
                'constraint' => '10,8',
                'null' => true,
            ],
            'longitude_saida' => [
                'type' => 'DECIMAL',
                'constraint' => '11,8',
                'null' => true,
            ],
            'observacao_entrada' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'observacao_saida' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => false,
                'default' => 'Em Andamento',
            ],
            'data_cadastro' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'data_atualizacao' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->dbforge->add_key('idCheckin', true);
        $this->dbforge->create_table('os_checkin', true);
        $this->db->query('ALTER TABLE `os_checkin` ENGINE = InnoDB');

        // Tabela de Assinaturas
        $this->dbforge->add_field([
            'idAssinatura' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'auto_increment' => true,
            ],
            'os_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'checkin_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'tipo' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'comment' => 'tecnico_entrada, tecnico_saida, cliente_saida',
            ],
            'assinatura' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'comment' => 'Caminho da imagem da assinatura',
            ],
            'nome_assinante' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'documento_assinante' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'data_assinatura' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'data_cadastro' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->dbforge->add_key('idAssinatura', true);
        $this->dbforge->create_table('os_assinaturas', true);
        $this->db->query('ALTER TABLE `os_assinaturas` ENGINE = InnoDB');

        // Tabela de Fotos do Atendimento
        $this->dbforge->add_field([
            'idFoto' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'auto_increment' => true,
            ],
            'os_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'checkin_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'usuarios_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'arquivo' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'url' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'descricao' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'etapa' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'durante',
                'comment' => 'entrada, durante, saida',
            ],
            'tamanho' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'tipo_arquivo' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true,
            ],
            'data_upload' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->dbforge->add_key('idFoto', true);
        $this->dbforge->create_table('os_fotos_atendimento', true);
        $this->db->query('ALTER TABLE `os_fotos_atendimento` ENGINE = InnoDB');

        // Adicionar índices para melhor performance
        $this->db->query('ALTER TABLE `os_checkin` ADD INDEX `idx_os_id` (`os_id`)');
        $this->db->query('ALTER TABLE `os_checkin` ADD INDEX `idx_status` (`status`)');
        $this->db->query('ALTER TABLE `os_assinaturas` ADD INDEX `idx_os_id` (`os_id`)');
        $this->db->query('ALTER TABLE `os_assinaturas` ADD INDEX `idx_tipo` (`tipo`)');
        $this->db->query('ALTER TABLE `os_fotos_atendimento` ADD INDEX `idx_os_id` (`os_id`)');
        $this->db->query('ALTER TABLE `os_fotos_atendimento` ADD INDEX `idx_etapa` (`etapa`)');
    }

    private function m20250403000001_31_down()
    {
        $this->dbforge->drop_table('os_checkin', true);
        $this->dbforge->drop_table('os_assinaturas', true);
        $this->dbforge->drop_table('os_fotos_atendimento', true);
    }

    // ---- 20250403000002_add_permissao_atendimentos.php (faz seed (insert/update)) ----
    private function m20250403000002_32_up()
    {
        // Adiciona a permissão vRelatorioAtendimentos ao grupo de permissões
        // Esta migration adiciona a permissão necessária para acessar o relatório de atendimentos

        // Verifica se já existe permissão com este nome
        $this->db->where('nome', 'Visualizar Relatório de Atendimentos');
        $exists = $this->db->get('permissoes');

        if ($exists->num_rows() == 0) {
            $permissoes = [
                'vRelatorioAtendimentos' => 1,
            ];

            $this->db->insert('permissoes', [
                'nome' => 'Visualizar Relatório de Atendimentos',
                'data' => date('Y-m-d'),
                'permissoes' => serialize($permissoes),
                'situacao' => 1,
            ]);
        }
    }

    private function m20250403000002_32_down()
    {
        // Remove a permissão
        $this->db->where('nome', 'Visualizar Relatório de Atendimentos');
        $this->db->delete('permissoes');
    }

    // ---- 20250404000001_add_tecnico_os_relacao.php (cria tabela nova: os_tecnico_atribuicao; faz seed (insert/update)) ----
    private function m20250404000001_33_up()
    {
        // Adicionar campo tecnico_responsavel na tabela os
        if (!$this->db->field_exists('tecnico_responsavel', 'os')) {
            $this->dbforge->add_column('os', [
                'tecnico_responsavel' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'ID do usuario tecnico responsavel pela OS',
                ]
            ]);

            // Adicionar chave estrangeira
            $this->db->query('ALTER TABLE `os` ADD INDEX `idx_tecnico_responsavel` (`tecnico_responsavel`)');
        }

        // Criar tabela de historico de atribuicoes
        if (!$this->db->table_exists('os_tecnico_atribuicao')) {
            $this->dbforge->add_field([
                'idAtribuicao' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                    'auto_increment' => true,
                ],
                'os_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                ],
                'tecnico_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                    'comment' => 'ID do tecnico atribuido',
                ],
                'atribuido_por' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                    'comment' => 'ID do usuario que fez a atribuicao',
                ],
                'data_atribuicao' => [
                    'type' => 'TIMESTAMP',
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                ],
                'data_remocao' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'motivo_remocao' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'observacao' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('idAtribuicao', true);
            $this->dbforge->create_table('os_tecnico_atribuicao', true);
            $this->db->query('ALTER TABLE `os_tecnico_atribuicao` ENGINE = InnoDB');

            // Adicionar indices
            $this->db->query('ALTER TABLE `os_tecnico_atribuicao` ADD INDEX `idx_os_id` (`os_id`)');
            $this->db->query('ALTER TABLE `os_tecnico_atribuicao` ADD INDEX `idx_tecnico_id` (`tecnico_id`)');
        }

        // Adicionar permissao de tecnico - verifica se existe grupo com nome 'Área do Técnico'
        $this->db->where('nome', 'Área do Técnico');
        $exists = $this->db->get('permissoes');

        if ($exists->num_rows() == 0) {
            // Cria o grupo de permissões com permissão aTecnico ativada
            $permissoes = [
                'aTecnico' => 1,
            ];

            $this->db->insert('permissoes', [
                'nome' => 'Área do Técnico',
                'data' => date('Y-m-d'),
                'permissoes' => serialize($permissoes),
                'situacao' => 1,
            ]);
        }
    }

    private function m20250404000001_33_down()
    {
        // Remover campo da tabela os
        if ($this->db->field_exists('tecnico_responsavel', 'os')) {
            $this->dbforge->drop_column('os', 'tecnico_responsavel');
        }

        // Remover tabela de atribuicoes
        $this->dbforge->drop_table('os_tecnico_atribuicao', true);
    }

    // ---- 20250404000002_add_permissoes_tecnico.php (faz seed (insert/update)) ----
    private function m20250404000002_34_up()
    {
        // Verificar se ja existe um grupo de permissao chamado "Tecnico"
        $this->db->where('nome', 'Tecnico');
        $exists = $this->db->get('permissoes');

        if ($exists->num_rows() == 0) {
            // Criar grupo de permissao para Tecnicos
            // Permissoes: Visualizar OS, Visualizar Produtos, Visualizar Servicos, Visualizar Clientes
            // Permissoes especificas: vTecnicoDashboard, vTecnicoOS, eTecnicoCheckin, eTecnicoCheckout, eTecnicoFotos
            $permissoes_array = [
                'aCliente' => 0,
                'eCliente' => 0,
                'dCliente' => 0,
                'vCliente' => 1,  // Visualizar clientes

                'aProduto' => 0,
                'eProduto' => 0,
                'dProduto' => 0,
                'vProduto' => 1,  // Visualizar produtos

                'aServico' => 0,
                'eServico' => 0,
                'dServico' => 0,
                'vServico' => 1,  // Visualizar servicos

                'aOs' => 0,
                'eOs' => 0,
                'dOs' => 0,
                'vOs' => 0,  // Nao acessa o padrao de OS
                'vBtnAtendimento' => 0,
                'vTecnicoOS' => 1,       // Visualizar OS na area do tecnico
                'eTecnicoCheckin' => 1,  // Fazer checkin
                'eTecnicoCheckout' => 1, // Fazer checkout
                'eTecnicoFotos' => 1,    // Adicionar fotos

                'aVenda' => 0,
                'eVenda' => 0,
                'dVenda' => 0,
                'vVenda' => 0,

                'aGarantia' => 0,
                'eGarantia' => 0,
                'dGarantia' => 0,
                'vGarantia' => 0,

                'aArquivo' => 0,
                'eArquivo' => 0,
                'dArquivo' => 0,
                'vArquivo' => 0,

                'aPagamento' => 0,
                'ePagamento' => 0,
                'dPagamento' => 0,
                'vPagamento' => 0,

                'aLancamento' => 0,
                'eLancamento' => 0,
                'dLancamento' => 0,
                'vLancamento' => 0,

                'cUsuario' => 0,
                'cEmitente' => 0,
                'cPermissao' => 0,
                'cBackup' => 0,
                'cAuditoria' => 0,
                'cEmail' => 0,
                'cSistema' => 0,

                'rCliente' => 0,
                'rProduto' => 0,
                'rServico' => 0,
                'rOs' => 0,
                'rVenda' => 0,
                'rFinanceiro' => 0,

                'aCobranca' => 0,
                'eCobranca' => 0,
                'dCobranca' => 0,
                'vCobranca' => 0,

                // Permissoes para acesso ao dashboard do tecnico
                'vTecnicoDashboard' => 1,
            ];

            $data = [
                'nome' => 'Tecnico',
                'data' => date('Y-m-d'),
                'permissoes' => serialize($permissoes_array),
                'situacao' => 1,
            ];

            $this->db->insert('permissoes', $data);
            log_message('info', 'Grupo de permissao Tecnico criado com sucesso');
        }
    }

    private function m20250404000002_34_down()
    {
        // Remover grupo de permissao Tecnico
        $this->db->where('nome', 'Tecnico');
        $this->db->delete('permissoes');
    }

    // ---- 20250404000003_fix_home_acesso.php (cria tabela nova: os_tecnico_atribuicao) ----
    private function m20250404000003_35_up()
    {
        // Garantir que as permissões de técnico existam mas não bloqueiem o acesso
        // Se o usuário não tem permissão vTecnicoDashboard, ele deve acessar o Home normalmente

        // Verificar se existe a coluna tecnico_responsavel na tabela os
        if (!$this->db->field_exists('tecnico_responsavel', 'os')) {
            $this->dbforge->add_column('os', [
                'tecnico_responsavel' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'ID do usuario tecnico responsavel pela OS',
                ]
            ]);

            $this->db->query('ALTER TABLE `os` ADD INDEX `idx_tecnico_responsavel` (`tecnico_responsavel`)');
        }

        // Verificar se a tabela de atribuicoes existe
        if (!$this->db->table_exists('os_tecnico_atribuicao')) {
            $this->dbforge->add_field([
                'idAtribuicao' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                    'auto_increment' => true,
                ],
                'os_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                ],
                'tecnico_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                ],
                'atribuido_por' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                ],
                'data_atribuicao' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'data_remocao' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'motivo_remocao' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'observacao' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('idAtribuicao', true);
            $this->dbforge->create_table('os_tecnico_atribuicao', true);
        }

        log_message('info', 'Migration fix_home_acesso executada com sucesso - estrutura do tecnico verificada');
    }

    private function m20250404000003_35_down()
    {
        // Nao remove nada para manter a compatibilidade
    }

    // ---- 20250405000001_add_permissao_btn_atendimento.php (sem efeito detectavel — mantida por seguranca) ----
    private function m20250405000001_36_up()
    {
        // Esta migration documenta a adição da permissão vBtnAtendimento
        // A permissão é armazenada como string no array serializado do campo 'permissoes' na tabela 'permissoes'
        //
        // Nova permissão: vBtnAtendimento
        // Descrição: Permite visualizar os botões de Iniciar/Finalizar Atendimento na visualização da OS
        //
        // Como usar:
        // - A permissão já está disponível automaticamente no sistema de permissões
        // - Ao editar ou criar um grupo de permissões, marque a opção "Visualizar Botões Iniciar/Finalizar Atendimento"
        //   na seção "Ordens de Serviço"
        //
        // Comportamento:
        // - Usuários com permissão 'vBtnAtendimento' OU 'eOs' podem ver os botões
        // - Técnicos com apenas 'vTecnicoOS' (sem 'eOs') só veem OS atribuídas a eles
        //   e só podem ver os botões se tiverem 'vBtnAtendimento'
        //
        // Arquivos modificados:
        // - application/controllers/Permissoes.php (adicionada no array de permissões)
        // - application/views/permissoes/adicionarPermissao.php (adicionado checkbox)
        // - application/views/permissoes/editarPermissao.php (adicionado checkbox)
        // - application/views/os/visualizarOs.php (verificação da permissão)

        log_message('info', 'Migration vBtnAtendimento executada - permissão documentada');
    }

    private function m20250405000001_36_down()
    {
        // Não é necessário remover nada pois a permissão é apenas uma string no array serializado
        // Para "remover", basta desmarcar a opção no grupo de permissões
        log_message('info', 'Rollback vBtnAtendimento - nenhuma ação necessária');
    }

    // ---- 20250405000002_add_dashboard_perm_admin.php (faz seed (insert/update)) ----
    private function m20250405000002_37_up()
    {
        // Buscar o grupo Administrador (idPermissao = 1)
        $this->db->where('idPermissao', 1);
        $query = $this->db->get('permissoes');

        if ($query->num_rows() > 0) {
            $admin = $query->row();

            // Deserializa as permissões
            $permissoes = unserialize($admin->permissoes);

            if (is_array($permissoes)) {
                // Adiciona as novas permissões do Dashboard
                $permissoes['vDashboard'] = 1;
                $permissoes['vRelatorioCompleto'] = 1;
                $permissoes['vExportarDados'] = 1;

                // Atualiza no banco
                $this->db->where('idPermissao', 1);
                $this->db->update('permissoes', ['permissoes' => serialize($permissoes)]);

                log_message('info', 'Permissões do Dashboard adicionadas ao grupo Administrador');
            }
        }
    }

    private function m20250405000002_37_down()
    {
        // Buscar o grupo Administrador (idPermissao = 1)
        $this->db->where('idPermissao', 1);
        $query = $this->db->get('permissoes');

        if ($query->num_rows() > 0) {
            $admin = $query->row();

            // Deserializa as permissões
            $permissoes = unserialize($admin->permissoes);

            if (is_array($permissoes)) {
                // Remove as permissões do Dashboard
                unset($permissoes['vDashboard']);
                unset($permissoes['vRelatorioCompleto']);
                unset($permissoes['vExportarDados']);

                // Atualiza no banco
                $this->db->where('idPermissao', 1);
                $this->db->update('permissoes', ['permissoes' => serialize($permissoes)]);
            }
        }
    }

    // ---- 20250405000003_fotos_atendimento_blob.php (add coluna nao parseavel (mantida por seguranca)) ----
    private function m20250405000003_38_up()
    {
        // Adiciona coluna para armazenar imagem em base64
        if ($this->db->table_exists('os_fotos_atendimento')) {
            $fields = [
                'imagem_base64' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                    'comment' => 'Imagem armazenada em base64'
                ],
                'mime_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'null' => true,
                    'comment' => 'Tipo MIME da imagem (image/jpeg, image/png, etc)'
                ]
            ];

            $this->dbforge->add_column('os_fotos_atendimento', $fields);

            // Atualiza registros existentes - converte arquivos para base64
            $this->m20250405000003_38_atualizarRegistrosExistentes();
        }
    }

    private function m20250405000003_38_down()
    {
        // Remove colunas adicionadas
        if ($this->db->table_exists('os_fotos_atendimento')) {
            $this->dbforge->drop_column('os_fotos_atendimento', 'imagem_base64');
            $this->dbforge->drop_column('os_fotos_atendimento', 'mime_type');
        }
    }

    /**
     * Converte fotos existentes em arquivos para base64 no banco
     */
    private function m20250405000003_38_atualizarRegistrosExistentes()
    {
        $this->db->select('idFoto, path');
        $this->db->from('os_fotos_atendimento');
        $this->db->where('imagem_base64 IS NULL');
        $query = $this->db->get();

        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $foto) {
                if (!empty($foto->path) && file_exists($foto->path)) {
                    $conteudo = file_get_contents($foto->path);
                    if ($conteudo !== false) {
                        $mime = mime_content_type($foto->path);
                        $base64 = 'data:' . $mime . ';base64,' . base64_encode($conteudo);

                        $this->db->where('idFoto', $foto->idFoto);
                        $this->db->update('os_fotos_atendimento', [
                            'imagem_base64' => $base64,
                            'mime_type' => $mime
                        ]);
                    }
                }
            }
        }
    }

    // ---- 20250406000001_add_permissao_dashboard.php (faz seed (insert/update)) ----
    private function m20250406000001_39_up()
    {
        // Verificar se ja existe uma permissao chamada "Dashboard"
        $this->db->where('nome', 'Dashboard');
        $exists = $this->db->get('permissoes');

        if ($exists->num_rows() == 0) {
            // Criar grupo de permissao para Dashboard
            $permissoes_array = [
                'aCliente' => 0, 'eCliente' => 0, 'dCliente' => 0, 'vCliente' => 0,
                'aProduto' => 0, 'eProduto' => 0, 'dProduto' => 0, 'vProduto' => 0,
                'aServico' => 0, 'eServico' => 0, 'dServico' => 0, 'vServico' => 0,
                'aOs' => 0, 'eOs' => 0, 'dOs' => 0, 'vOs' => 0,
                'vBtnAtendimento' => 0, 'vTecnicoOS' => 0, 'eTecnicoCheckin' => 0,
                'eTecnicoCheckout' => 0, 'eTecnicoFotos' => 0,
                'aVenda' => 0, 'eVenda' => 0, 'dVenda' => 0, 'vVenda' => 0,
                'aGarantia' => 0, 'eGarantia' => 0, 'dGarantia' => 0, 'vGarantia' => 0,
                'aArquivo' => 0, 'eArquivo' => 0, 'dArquivo' => 0, 'vArquivo' => 0,
                'aPagamento' => 0, 'ePagamento' => 0, 'dPagamento' => 0, 'vPagamento' => 0,
                'aLancamento' => 0, 'eLancamento' => 0, 'dLancamento' => 0, 'vLancamento' => 0,
                'cUsuario' => 0, 'cEmitente' => 0, 'cPermissao' => 0, 'cBackup' => 0,
                'cAuditoria' => 0, 'cEmail' => 0, 'cSistema' => 0,
                'rCliente' => 0, 'rProduto' => 0, 'rServico' => 0, 'rOs' => 0,
                'rVenda' => 0, 'rFinanceiro' => 0,
                'aCobranca' => 0, 'eCobranca' => 0, 'dCobranca' => 0, 'vCobranca' => 0,
                'vDashboard' => 1, // Permissao do dashboard
                'vRelatorioCompleto' => 1,
                'vExportarDados' => 1,
            ];

            $data = [
                'nome' => 'Dashboard',
                'data' => date('Y-m-d'),
                'permissoes' => serialize($permissoes_array),
                'situacao' => 1,
            ];

            $this->db->insert('permissoes', $data);
            log_message('info', 'Grupo de permissao Dashboard criado com sucesso');
        }
    }

    private function m20250406000001_39_down()
    {
        // Remover grupo de permissao Dashboard
        $this->db->where('nome', 'Dashboard');
        $this->db->delete('permissoes');
    }

    // ---- 20260705000001_add_modulo_fiscal.php (cria tabela nova: configuracoes_nfe,notas_fiscais; faz seed (insert/update); muda tipo/charset/drop) ----
    private function m20260705000001_40_up()
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

    private function m20260705000001_40_down()
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

    // ---- 20260706000001_add_cora_boleto_cobrancas.php (muda tipo/charset/drop) ----
    private function m20260706000001_41_up()
    {
        // charge_id: INT -> VARCHAR(64). Gateways (Cora, Asaas) usam id textual.
        $this->db->query('ALTER TABLE `cobrancas` MODIFY `charge_id` VARCHAR(64) NOT NULL');

        if (! $this->db->field_exists('nota_id', 'cobrancas')) {
            $this->dbforge->add_column('cobrancas', [
                'nota_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'FK para notas_fiscais.idNota (boleto emitido a partir da nota)',
                ],
            ]);
            $this->db->query('ALTER TABLE `cobrancas` ADD INDEX `idx_cobrancas_nota_id` (`nota_id`)');
        }

        if (! $this->db->field_exists('pix', 'cobrancas')) {
            $this->dbforge->add_column('cobrancas', [
                'pix' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'comment' => 'PIX copia e cola (EMV) do boleto híbrido',
                ],
            ]);
        }

        if (! $this->db->field_exists('valor_iss_retido', 'cobrancas')) {
            $this->dbforge->add_column('cobrancas', [
                'valor_iss_retido' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                    'default' => 0,
                    'comment' => 'ISS retido pelo tomador abatido do valor do boleto',
                ],
            ]);
        }
    }

    private function m20260706000001_41_down()
    {
        if ($this->db->field_exists('valor_iss_retido', 'cobrancas')) {
            $this->dbforge->drop_column('cobrancas', 'valor_iss_retido');
        }
        if ($this->db->field_exists('pix', 'cobrancas')) {
            $this->dbforge->drop_column('cobrancas', 'pix');
        }
        if ($this->db->field_exists('nota_id', 'cobrancas')) {
            $this->db->query('ALTER TABLE `cobrancas` DROP INDEX `idx_cobrancas_nota_id`');
            $this->dbforge->drop_column('cobrancas', 'nota_id');
        }
        // charge_id permanece VARCHAR (compatível com todos os gateways).
    }

    // ---- 20260706000002_add_configuracoes_cora.php (cria tabela nova: configuracoes_cora; faz seed (insert/update); muda tipo/charset/drop) ----
    private function m20260706000002_42_up()
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

    private function m20260706000002_42_down()
    {
        $this->dbforge->drop_table('configuracoes_cora', true);
    }

    // ---- 20260706000003_add_codigo_tributacao_municipal.php (add coluna nao coberta: servicos.codigo_tributacao_municipal) ----
    private function m20260706000003_43_up()
    {
        if (!$this->db->field_exists('codigo_tributacao_municipal', 'servicos')) {
            $this->dbforge->add_column('servicos', [
                'codigo_tributacao_municipal' => [
                    'type' => 'VARCHAR',
                    'constraint' => 3,
                    'null' => true,
                    'comment' => 'cTribMun: código de tributação municipal (3 dígitos), exigido por alguns municípios (ex.: Manaus) na NFS-e Nacional',
                ],
            ]);
        }
    }

    private function m20260706000003_43_down()
    {
        if ($this->db->field_exists('codigo_tributacao_municipal', 'servicos')) {
            $this->dbforge->drop_column('servicos', 'codigo_tributacao_municipal');
        }
    }

    // ---- 20260706000004_add_substituicao_nfse.php (add coluna nao coberta: notas_fiscais.substitui_nota_id) ----
    private function m20260706000004_44_up()
    {
        if (!$this->db->field_exists('substitui_nota_id', 'notas_fiscais')) {
            $this->dbforge->add_column('notas_fiscais', [
                'substitui_nota_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'idNota da nota que ESTA substituiu (NFS-e Nacional). A original fica com status substituida.',
                ],
            ]);
        }
    }

    private function m20260706000004_44_down()
    {
        if ($this->db->field_exists('substitui_nota_id', 'notas_fiscais')) {
            $this->dbforge->drop_column('notas_fiscais', 'substitui_nota_id');
        }
    }

    // ---- 20260706000005_add_webhook_cora.php (add coluna nao coberta: configuracoes_cora.webhook_endpoint_id) ----
    private function m20260706000005_45_up()
    {
        if (! $this->db->field_exists('webhook_endpoint_id', 'configuracoes_cora')) {
            $this->dbforge->add_column('configuracoes_cora', [
                'webhook_endpoint_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                    'comment' => 'ID do endpoint de webhook registrado na Cora',
                ],
            ]);
        }
    }

    private function m20260706000005_45_down()
    {
        if ($this->db->field_exists('webhook_endpoint_id', 'configuracoes_cora')) {
            $this->dbforge->drop_column('configuracoes_cora', 'webhook_endpoint_id');
        }
    }

    // ---- 20260708000001_add_email_secundario_clientes.php (add coluna nao coberta: clientes.email_secundario) ----
    private function m20260708000001_46_up()
    {
        // E-mail secundário (financeiro) do cliente, usado como destinatário
        // adicional no envio de cobranças e boletos da nota fiscal.
        if (! $this->db->field_exists('email_secundario', 'clientes')) {
            $this->dbforge->add_column('clientes', [
                'email_secundario' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'email',
                ],
            ]);
            log_message('info', 'Coluna clientes.email_secundario criada com sucesso');
        }
    }

    private function m20260708000001_46_down()
    {
        if ($this->db->field_exists('email_secundario', 'clientes')) {
            $this->dbforge->drop_column('clientes', 'email_secundario');
        }
    }

    // ---- 20260708000002_create_email_templates.php (cria tabela nova: email_templates) ----
    private function m20260708000002_47_up()
    {
        // Tabela de modelos de e-mail (um registro por tipo de e-mail enviado).
        if (! $this->db->table_exists('email_templates')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'slug' => [
                    'type' => 'VARCHAR',
                    'constraint' => 60,
                ],
                'nome' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ],
                'descricao' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'assunto' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                ],
                'corpo' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'tags' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'ativo' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ],
                'data_criacao' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'data_atualizacao' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('slug');
            $this->dbforge->create_table('email_templates');
        }

        // Layout global (HTML/CSS) que envolve o corpo de todos os e-mails.
        $this->m20260708000002_47_seedConfig('email_layout', $this->m20260708000002_47_defaultLayout());
        $this->m20260708000002_47_seedConfig('email_css', $this->m20260708000002_47_defaultCss());

        // Modelos padrão.
        $agora = date('Y-m-d H:i:s');

        $this->m20260708000002_47_seedTemplate([
            'slug' => 'os',
            'nome' => 'Ordem de Serviço',
            'descricao' => 'Enviado ao cliente ao compartilhar/notificar uma Ordem de Serviço.',
            'assunto' => 'Ordem de Serviço #{{os_numero}} - {{empresa_nome}}',
            'corpo' => $this->m20260708000002_47_defaultCorpoOs(),
            'tags' => 'cliente_nome, cliente_email, empresa_nome, os_numero, os_status, os_data_inicial, os_data_final, os_garantia, os_aprovador, os_detalhes_html, os_itens_html, os_valor_total, data_atual',
            'data_criacao' => $agora,
            'data_atualizacao' => $agora,
        ]);

        $this->m20260708000002_47_seedTemplate([
            'slug' => 'cobranca',
            'nome' => 'Cobrança / Boleto da NF',
            'descricao' => 'Enviado ao cliente com o boleto/PIX gerado a partir da nota fiscal ou da cobrança.',
            'assunto' => 'Cobrança #{{cobranca_numero}} - {{empresa_nome}}',
            'corpo' => $this->m20260708000002_47_defaultCorpoCobranca(),
            'tags' => 'cliente_nome, cliente_email, empresa_nome, cobranca_numero, cobranca_valor, cobranca_vencimento, cobranca_descricao, cobranca_pagamento_html, cobranca_link, cobranca_pdf, cobranca_barcode, cobranca_pix, os_numero, os_status, os_data_inicial, os_data_final, os_garantia, os_aprovador, os_descricao, os_defeito, os_observacoes, os_laudo, os_produtos_html, os_servicos_html, os_itens_html, os_valor_total, data_atual',
            'data_criacao' => $agora,
            'data_atualizacao' => $agora,
        ]);
    }

    private function m20260708000002_47_down()
    {
        if ($this->db->table_exists('email_templates')) {
            $this->dbforge->drop_table('email_templates');
        }
        $this->db->where_in('config', ['email_layout', 'email_css'])->delete('configuracoes');
    }

    private function m20260708000002_47_seedConfig($config, $valor)
    {
        $existe = $this->db->where('config', $config)->count_all_results('configuracoes');
        if ($existe == 0) {
            $this->db->insert('configuracoes', ['config' => $config, 'valor' => $valor]);
        }
    }

    private function m20260708000002_47_seedTemplate($data)
    {
        $existe = $this->db->where('slug', $data['slug'])->count_all_results('email_templates');
        if ($existe == 0) {
            $this->db->insert('email_templates', $data);
        }
    }

    private function m20260708000002_47_defaultLayout()
    {
        return <<<'HTML'
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>{{css}}</style>
</head>
<body>
    <div class="email-bg">
        <table class="email-wrapper" role="presentation" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td class="email-header">
                    {{empresa_logo_img}}
                    <div class="email-header-name">{{empresa_nome}}</div>
                </td>
            </tr>
            <tr>
                <td class="email-body">
                    {{conteudo}}
                </td>
            </tr>
            <tr>
                <td class="email-footer">
                    <strong>{{empresa_nome}}</strong><br>
                    {{empresa_endereco}}<br>
                    {{empresa_telefone}} &middot; {{empresa_email}}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
HTML;
    }

    private function m20260708000002_47_defaultCss()
    {
        return <<<'CSS'
body { margin: 0; padding: 0; background: #eaf1fb; }
.email-bg { background: #eaf1fb; background: linear-gradient(180deg, #eaf1fb 0%, #f4f8ff 100%); padding: 32px 14px; font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #334155; }
.email-wrapper { max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(37, 99, 235, 0.12); }
.email-header { background: #2563eb; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 55%, #3b82f6 100%); padding: 36px 32px; text-align: center; }
.email-header img { max-height: 60px; max-width: 200px; display: inline-block; }
.email-header-name { color: #ffffff; font-size: 22px; font-weight: 700; margin-top: 10px; letter-spacing: .3px; }
.email-body { padding: 36px 34px; font-size: 15px; line-height: 1.65; color: #334155; }
.email-body p { margin: 0 0 15px; }
.email-body strong { color: #1e293b; }
.email-body h2 { font-size: 16px; color: #1e3a8a; margin: 26px 0 12px; padding-bottom: 8px; border-bottom: 2px solid #dbeafe; }
.email-body table.dados { width: 100%; border-collapse: collapse; margin: 10px 0 20px; }
.email-body table.dados td { padding: 11px 14px; border-bottom: 1px solid #eef2fb; font-size: 14px; }
.email-body table.dados td.rotulo { color: #64748b; width: 40%; font-weight: 600; }
.email-body table.itens { width: 100%; border-collapse: collapse; margin: 12px 0 20px; font-size: 14px; border-radius: 10px; overflow: hidden; }
.email-body table.itens th { background: #eff6ff; text-align: left; padding: 12px 14px; color: #1e3a8a; font-weight: 700; }
.email-body table.itens td { padding: 12px 14px; border-bottom: 1px solid #eef2fb; }
.email-body .total { font-size: 18px; color: #1e3a8a; text-align: right; margin-top: 8px; font-weight: 700; }
.btn-pagar { display: inline-block; background: #2563eb; background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: #ffffff !important; text-decoration: none; padding: 14px 30px; border-radius: 10px; font-weight: 700; margin: 8px 8px 8px 0; box-shadow: 0 6px 16px rgba(37, 99, 235, 0.30); }
.btn-link { display: inline-block; background: #1e3a8a; color: #ffffff !important; text-decoration: none; padding: 14px 30px; border-radius: 10px; font-weight: 700; margin: 8px 8px 8px 0; }
.box-pagamento { background: #f4f8ff; border: 1px solid #dbeafe; border-radius: 12px; padding: 18px; margin: 16px 0; }
.box-pagamento .rotulo { color: #2563eb; font-size: 12px; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; font-weight: 700; }
.box-pagamento code { display: block; word-break: break-all; background: #ffffff; border: 1px solid #dbeafe; border-radius: 8px; padding: 12px; font-size: 12px; color: #334155; }
.email-footer { background: #f4f8ff; padding: 24px 32px; text-align: center; font-size: 12px; color: #7b8aa5; line-height: 1.7; border-top: 1px solid #e7eefc; }
.email-footer strong { color: #1e3a8a; }
CSS;
    }

    private function m20260708000002_47_defaultCorpoOs()
    {
        return <<<'HTML'
<p>Olá, <strong>{{cliente_nome}}</strong>!</p>
<p>Segue o resumo da sua Ordem de Serviço <strong>#{{os_numero}}</strong>.</p>
<table class="dados" role="presentation" cellpadding="0" cellspacing="0">
    <tr><td class="rotulo">Status</td><td>{{os_status}}</td></tr>
    <tr><td class="rotulo">Abertura</td><td>{{os_data_inicial}}</td></tr>
    <tr><td class="rotulo">Encerramento</td><td>{{os_data_final}}</td></tr>
    <tr><td class="rotulo">Garantia</td><td>{{os_garantia}}</td></tr>
</table>
{{os_detalhes_html}}
{{os_itens_html}}
<p class="total">Total: <strong>{{os_valor_total}}</strong></p>
<p>Qualquer dúvida, estamos à disposição.</p>
HTML;
    }

    private function m20260708000002_47_defaultCorpoCobranca()
    {
        return <<<'HTML'
<p>Olá, <strong>{{cliente_nome}}</strong>!</p>
<p>Você tem uma cobrança no valor de <strong>{{cobranca_valor}}</strong> com vencimento em <strong>{{cobranca_vencimento}}</strong>.</p>
{{cobranca_pagamento_html}}
<p style="color:#8a90a6; font-size:13px;">{{cobranca_descricao}}</p>
<p>Assim que o pagamento for identificado, você receberá a confirmação. Obrigado!</p>
HTML;
    }

    // ---- 20260709000001_create_notification_triggers.php (cria tabela nova: notification_triggers) ----
    private function m20260709000001_48_up()
    {
        if (! $this->db->table_exists('notification_triggers')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'evento' => ['type' => 'VARCHAR', 'constraint' => 60],
                'nome' => ['type' => 'VARCHAR', 'constraint' => 120],
                'descricao' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'grupo' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
                'ativo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                // Listas separadas por vírgula.
                'canais' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'destinatarios' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
                'blocos' => ['type' => 'TEXT', 'null' => true],
                'anexos' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'template_slug' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                'data_criacao' => ['type' => 'DATETIME', 'null' => true],
                'data_atualizacao' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('evento');
            $this->dbforge->create_table('notification_triggers');
        }

        // Intervalo (em segundos) do disparo automático da fila de e-mail.
        $this->m20260709000001_48_seedConfig('notif_intervalo_disparo', '120');

        $agora = date('Y-m-d H:i:s');
        foreach ($this->m20260709000001_48_eventosPadrao() as $ev) {
            $ev['data_criacao'] = $agora;
            $ev['data_atualizacao'] = $agora;
            $this->m20260709000001_48_seedEvento($ev);
        }
    }

    private function m20260709000001_48_down()
    {
        if ($this->db->table_exists('notification_triggers')) {
            $this->dbforge->drop_table('notification_triggers');
        }
        $this->db->where('config', 'notif_intervalo_disparo')->delete('configuracoes');
    }

    private function m20260709000001_48_seedConfig($config, $valor)
    {
        if ($this->db->where('config', $config)->count_all_results('configuracoes') == 0) {
            $this->db->insert('configuracoes', ['config' => $config, 'valor' => $valor]);
        }
    }

    private function m20260709000001_48_seedEvento($ev)
    {
        if ($this->db->where('evento', $ev['evento'])->count_all_results('notification_triggers') == 0) {
            $this->db->insert('notification_triggers', $ev);
        }
    }

    private function m20260709000001_48_eventosPadrao()
    {
        $blocosOs = 'dados,defeito,laudo,observacoes,produtos,servicos,valores';

        return [
            ['evento' => 'os_aberta', 'nome' => 'OS aberta', 'grupo' => 'Ordem de Serviço', 'descricao' => 'Ao criar uma nova Ordem de Serviço.', 'ativo' => 1, 'canais' => 'email', 'destinatarios' => 'cliente', 'blocos' => $blocosOs, 'anexos' => null, 'template_slug' => 'os'],
            ['evento' => 'os_editada', 'nome' => 'OS editada / status alterado', 'grupo' => 'Ordem de Serviço', 'descricao' => 'Ao editar a OS ou mudar o status.', 'ativo' => 1, 'canais' => 'email', 'destinatarios' => 'cliente', 'blocos' => $blocosOs, 'anexos' => null, 'template_slug' => 'os'],
            ['evento' => 'os_aprovada', 'nome' => 'OS aprovada', 'grupo' => 'Ordem de Serviço', 'descricao' => 'Quando o cliente aprova o orçamento/OS.', 'ativo' => 1, 'canais' => 'email,whatsapp', 'destinatarios' => 'cliente,tecnico', 'blocos' => 'dados,valores', 'anexos' => null, 'template_slug' => 'os'],
            ['evento' => 'os_finalizada', 'nome' => 'OS finalizada', 'grupo' => 'Ordem de Serviço', 'descricao' => 'Quando a OS é concluída.', 'ativo' => 1, 'canais' => 'email', 'destinatarios' => 'cliente', 'blocos' => $blocosOs, 'anexos' => null, 'template_slug' => 'os'],
            ['evento' => 'cobranca_gerada', 'nome' => 'Boleto / cobrança gerada', 'grupo' => 'Cobrança', 'descricao' => 'Ao gerar o boleto/PIX da nota ou cobrança.', 'ativo' => 1, 'canais' => 'email', 'destinatarios' => 'cliente,cliente_secundario', 'blocos' => null, 'anexos' => 'boleto', 'template_slug' => 'cobranca'],
            ['evento' => 'cobranca_enviada', 'nome' => 'Cobrança enviada (manual)', 'grupo' => 'Cobrança', 'descricao' => 'Ao clicar em enviar a cobrança por e-mail.', 'ativo' => 1, 'canais' => 'email', 'destinatarios' => 'cliente,cliente_secundario', 'blocos' => null, 'anexos' => 'boleto', 'template_slug' => 'cobranca'],
            ['evento' => 'pagamento_confirmado', 'nome' => 'Pagamento confirmado', 'grupo' => 'Cobrança', 'descricao' => 'Quando o pagamento do boleto/PIX é identificado.', 'ativo' => 0, 'canais' => 'email,whatsapp', 'destinatarios' => 'cliente', 'blocos' => null, 'anexos' => null, 'template_slug' => null],
            ['evento' => 'nota_emitida', 'nome' => 'Nota fiscal emitida', 'grupo' => 'Fiscal', 'descricao' => 'Ao autorizar uma NF-e / NFS-e.', 'ativo' => 0, 'canais' => 'email', 'destinatarios' => 'cliente,cliente_secundario', 'blocos' => null, 'anexos' => 'nota_fiscal', 'template_slug' => null],
            ['evento' => 'cliente_novo', 'nome' => 'Cliente cadastrado (boas-vindas)', 'grupo' => 'Cliente', 'descricao' => 'Ao cadastrar um novo cliente.', 'ativo' => 0, 'canais' => 'email', 'destinatarios' => 'cliente', 'blocos' => null, 'anexos' => null, 'template_slug' => null],
        ];
    }

    // ---- 20260709000002_add_attachments_email_queue.php (add coluna nao coberta: email_queue.attachments) ----
    private function m20260709000002_49_up()
    {
        // Anexos do e-mail: JSON com URLs (públicas) e/ou caminhos locais.
        if (! $this->db->field_exists('attachments', 'email_queue')) {
            $this->dbforge->add_column('email_queue', [
                'attachments' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'headers',
                ],
            ]);
            log_message('info', 'Coluna email_queue.attachments criada com sucesso');
        }
    }

    private function m20260709000002_49_down()
    {
        if ($this->db->field_exists('attachments', 'email_queue')) {
            $this->dbforge->drop_column('email_queue', 'attachments');
        }
    }

    // ---- 20260709000003_add_automacao_aprovacao.php (add coluna nao coberta: clientes.automacao_aprovacao) ----
    private function m20260709000003_50_up()
    {
        // Flag por cliente: usar a automação de aprovação (NFS-e + boleto).
        if (! $this->db->field_exists('automacao_aprovacao', 'clientes')) {
            $this->dbforge->add_column('clientes', [
                'automacao_aprovacao' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'after' => 'email_secundario',
                ],
            ]);
        }

        // Configurações globais da automação (padrões da NFS-e). Desligada por padrão.
        $this->m20260709000003_50_seedConfig('automacao_aprovacao_ativa', '0');
        $this->m20260709000003_50_seedConfig('automacao_desc_servico', 'Serviços referentes à OS nº {os_numero}');
        $this->m20260709000003_50_seedConfig('automacao_info_complementar', '');
        $this->m20260709000003_50_seedConfig('automacao_ctribnac', '');
        $this->m20260709000003_50_seedConfig('automacao_ctribmun', '');
        $this->m20260709000003_50_seedConfig('automacao_aliquota_iss', '');
        $this->m20260709000003_50_seedConfig('automacao_tp_ret_issqn', '');
    }

    private function m20260709000003_50_down()
    {
        if ($this->db->field_exists('automacao_aprovacao', 'clientes')) {
            $this->dbforge->drop_column('clientes', 'automacao_aprovacao');
        }
        $this->db->where_in('config', [
            'automacao_aprovacao_ativa', 'automacao_desc_servico', 'automacao_info_complementar',
            'automacao_ctribnac', 'automacao_ctribmun', 'automacao_aliquota_iss', 'automacao_tp_ret_issqn',
        ])->delete('configuracoes');
    }

    private function m20260709000003_50_seedConfig($config, $valor)
    {
        if ($this->db->where('config', $config)->count_all_results('configuracoes') == 0) {
            $this->db->insert('configuracoes', ['config' => $config, 'valor' => $valor]);
        }
    }

    // ---- 20260709000004_add_automacao_override_os_e_permissao.php (faz seed (insert/update)) ----
    private function m20260709000004_51_up()
    {
        // Override por OS: NULL = herda do cliente; 1 = forçar ativo; 0 = desativar nesta OS.
        if (! $this->db->field_exists('automacao_override', 'os')) {
            $this->dbforge->add_column('os', [
                'automacao_override' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }

        // Concede a nova permissão cAutomacao a quem já é admin (tem cSistema),
        // para não trancar o próprio dono fora da automação.
        $grupos = $this->db->get('permissoes')->result();
        foreach ($grupos as $g) {
            $perms = @unserialize((string) $g->permissoes);
            if (! is_array($perms)) {
                continue;
            }
            if (! empty($perms['cSistema']) && empty($perms['cAutomacao'])) {
                $perms['cAutomacao'] = 1;
                $this->db->where('idPermissao', $g->idPermissao)
                    ->update('permissoes', ['permissoes' => serialize($perms)]);
            }
        }
    }

    private function m20260709000004_51_down()
    {
        if ($this->db->field_exists('automacao_override', 'os')) {
            $this->dbforge->drop_column('os', 'automacao_override');
        }
    }

    // ---- 20260709000005_add_tp_ret_issqn_cliente.php (add coluna nao coberta: clientes.tp_ret_issqn) ----
    private function m20260709000005_52_up()
    {
        // Retenção de ISS da NFS-e por cliente:
        //   NULL = usa o padrão (automação / config fiscal) | 1 = não retido | 2 = retido
        if (! $this->db->field_exists('tp_ret_issqn', 'clientes')) {
            $this->dbforge->add_column('clientes', [
                'tp_ret_issqn' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => true,
                    'default' => null,
                    'after' => 'automacao_aprovacao',
                ],
            ]);
        }
    }

    private function m20260709000005_52_down()
    {
        if ($this->db->field_exists('tp_ret_issqn', 'clientes')) {
            $this->dbforge->drop_column('clientes', 'tp_ret_issqn');
        }
    }

    // ---- 20260709000006_widen_configuracoes.php (muda tipo/charset/drop) ----
    private function m20260709000006_53_up()
    {
        // A tabela configuracoes foi criada com config/valor em VARCHAR(20),
        // pequeno demais para as chaves e valores novos (automacao_*,
        // notif_intervalo_disparo, layout/CSS de e-mail). Sem alargar, o INSERT
        // dessas configs falha/trunca e nada persiste.
        $this->db->query('ALTER TABLE `configuracoes` MODIFY `config` VARCHAR(60) NOT NULL');
        $this->db->query('ALTER TABLE `configuracoes` MODIFY `valor` TEXT NULL');
    }

    private function m20260709000006_53_down()
    {
        // Não reverte: voltar para VARCHAR(20) truncaria dados existentes.
    }

    // ---- 20260710000001_add_faturamento_agendado.php (cria tabela nova: faturamentos_agendados; faz seed (insert/update)) ----
    private function m20260710000001_54_up()
    {
        // Flag por cliente: segurar a emissão até o dia de faturamento.
        if (! $this->db->field_exists('faturamento_agendado', 'clientes')) {
            $this->dbforge->add_column('clientes', [
                'faturamento_agendado' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'after' => 'automacao_aprovacao',
                ],
            ]);
        }

        // Fila de emissões seguradas até o dia de faturamento.
        if (! $this->db->table_exists('faturamentos_agendados')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'os_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'cliente_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'data_aprovacao' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'data_agendada' => [
                    'type' => 'DATE',
                    'null' => false,
                    'comment' => 'Dia em que a emissão deve ser liberada',
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'aguardando',
                    'comment' => 'aguardando | processado | erro | cancelado',
                ],
                'tentativas' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'nota_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'FK para notas_fiscais.idNota após a emissão',
                ],
                'motivo' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'processed_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('faturamentos_agendados', true);

            $this->db->query('ALTER TABLE `faturamentos_agendados` ADD INDEX `idx_fa_os` (`os_id`)');
            $this->db->query('ALTER TABLE `faturamentos_agendados` ADD INDEX `idx_fa_status_data` (`status`, `data_agendada`)');
        }

        // Dia do mês em que a fila é liberada (padrão: dia 01).
        if ($this->db->where('config', 'automacao_faturamento_dia')->count_all_results('configuracoes') == 0) {
            $this->db->insert('configuracoes', ['config' => 'automacao_faturamento_dia', 'valor' => '1']);
        }
    }

    private function m20260710000001_54_down()
    {
        if ($this->db->table_exists('faturamentos_agendados')) {
            $this->dbforge->drop_table('faturamentos_agendados', true);
        }
        if ($this->db->field_exists('faturamento_agendado', 'clientes')) {
            $this->dbforge->drop_column('clientes', 'faturamento_agendado');
        }
        $this->db->where('config', 'automacao_faturamento_dia')->delete('configuracoes');
    }

    // ---- 20260711000001_consolidado_updates_sessao.php (cria tabela nova: clientes_vinculos,email_envios,whatsapp_envios,whatsapp_templates; faz seed (insert/update); muda tipo/charset/drop) ----
    private function m20260711000001_55_up()
    {
        /* 1) Portal do cliente multi-CNPJ ------------------------------- */
        $this->db->query(
            'CREATE TABLE IF NOT EXISTS `clientes_vinculos` (
                `id`                INT(11)   NOT NULL AUTO_INCREMENT,
                `cliente_master_id` INT(11)   NOT NULL,
                `cliente_id`        INT(11)   NOT NULL,
                `data_cadastro`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_master_cliente` (`cliente_master_id`, `cliente_id`),
                KEY `idx_master` (`cliente_master_id`),
                KEY `idx_cliente` (`cliente_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

        /* 2) Aceite do serviço realizado (os.*) ------------------------- */
        $this->m20260711000001_55_addColuna('os', 'aceite_token', "`aceite_token` VARCHAR(64) NULL DEFAULT NULL");
        $this->m20260711000001_55_addColuna('os', 'aceite_status', "`aceite_status` VARCHAR(20) NULL DEFAULT NULL");
        $this->m20260711000001_55_addColuna('os', 'aceite_expira', "`aceite_expira` DATETIME NULL DEFAULT NULL");
        $this->m20260711000001_55_addColuna('os', 'aceite_data', "`aceite_data` DATETIME NULL DEFAULT NULL");
        $this->m20260711000001_55_addColuna('os', 'aceite_nome', "`aceite_nome` VARCHAR(150) NULL DEFAULT NULL");
        $this->m20260711000001_55_addColuna('os', 'aceite_ip', "`aceite_ip` VARCHAR(45) NULL DEFAULT NULL");
        $this->m20260711000001_55_addColuna('os', 'aceite_obs', "`aceite_obs` TEXT NULL DEFAULT NULL");
        $this->m20260711000001_55_addColuna('os', 'aceite_assinatura_id', "`aceite_assinatura_id` INT(11) NULL DEFAULT NULL");
        $this->m20260711000001_55_addIndice('os', 'idx_os_aceite_token', 'ALTER TABLE `os` ADD INDEX `idx_os_aceite_token` (`aceite_token`)');

        /* 3) Número de notificação (WhatsApp) por cliente --------------- */
        $this->m20260711000001_55_addColuna('clientes', 'whatsapp_notificacao', "`whatsapp_notificacao` VARCHAR(20) NULL DEFAULT NULL");

        /* 4) Gatilhos: grupos e modelo de WhatsApp ---------------------- */
        $this->m20260711000001_55_addColuna('notification_triggers', 'whatsapp_grupos', "`whatsapp_grupos` TEXT NULL DEFAULT NULL");
        $this->m20260711000001_55_addColuna('notification_triggers', 'whatsapp_template', "`whatsapp_template` VARCHAR(40) NULL DEFAULT NULL");

        /* 5) Log de envios de WhatsApp ---------------------------------- */
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `whatsapp_envios` (
                `id`         INT(11)      NOT NULL AUTO_INCREMENT,
                `data_envio` DATETIME     NOT NULL,
                `destino`    VARCHAR(120) NULL,
                `tipo`       VARCHAR(30)  NULL,
                `os_id`      INT(11)      NULL,
                `evento`     VARCHAR(80)  NULL,
                `status`     VARCHAR(20)  NOT NULL DEFAULT 'enviado',
                `erro`       TEXT         NULL,
                `retorno`    VARCHAR(120) NULL,
                `mensagem`   TEXT         NULL,
                PRIMARY KEY (`id`),
                KEY `idx_data` (`data_envio`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        /* 6) Log de envios de e-mail (+ vínculo com a fila) ------------- */
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `email_envios` (
                `id`         INT(11)      NOT NULL AUTO_INCREMENT,
                `data_envio` DATETIME     NOT NULL,
                `destino`    VARCHAR(255) NULL,
                `assunto`    VARCHAR(255) NULL,
                `tipo`       VARCHAR(30)  NULL,
                `status`     VARCHAR(20)  NOT NULL DEFAULT 'enviado',
                `erro`       TEXT         NULL,
                `queue_id`   INT(11)      NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_data` (`data_envio`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
        // Instalações que já tinham a email_envios sem a coluna de vínculo.
        $this->m20260711000001_55_addColuna('email_envios', 'queue_id', "`queue_id` INT(11) NULL DEFAULT NULL");

        /* 7) Modelos de mensagens de WhatsApp + seeds ------------------- */
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `whatsapp_templates` (
                `id`        INT(11)      NOT NULL AUTO_INCREMENT,
                `slug`      VARCHAR(40)  NOT NULL,
                `nome`      VARCHAR(120) NOT NULL,
                `descricao` VARCHAR(255) NULL,
                `tags`      VARCHAR(400) NULL,
                `conteudo`  TEXT         NULL,
                `ativo`     TINYINT(1)   NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
        $this->m20260711000001_55_seedWhatsappTemplates();

        /* 8) Códigos de tributação padrão da NFS-e ---------------------- */
        $this->m20260711000001_55_addColuna('configuracoes_nfe', 'ctribnac_padrao', "`ctribnac_padrao` VARCHAR(6) NOT NULL DEFAULT '010701'");
        $this->m20260711000001_55_addColuna('configuracoes_nfe', 'ctribmun_padrao', "`ctribmun_padrao` VARCHAR(10) NOT NULL DEFAULT '100'");
        if ($this->db->table_exists('configuracoes_nfe')) {
            $this->db->query("UPDATE `configuracoes_nfe` SET `ctribmun_padrao` = '100' WHERE `ctribmun_padrao` IS NULL OR `ctribmun_padrao` = ''");
        }

        /* 9) Descrição do serviço persistida na nota (p/ o boleto) ------ */
        $this->m20260711000001_55_addColuna('notas_fiscais', 'descricao_servico', "`descricao_servico` TEXT NULL DEFAULT NULL");

        /* 10) Paleta de tags do modelo de e-mail de cobrança ------------ */
        if ($this->db->table_exists('email_templates')) {
            $this->db->where('slug', 'cobranca')->update('email_templates', [
                'tags' => 'cliente_nome, cliente_email, empresa_nome, cobranca_numero, cobranca_valor, cobranca_vencimento, cobranca_descricao, cobranca_pagamento_html, cobranca_link, cobranca_pdf, cobranca_barcode, cobranca_pix, os_numero, os_status, os_data_inicial, os_data_final, os_garantia, os_aprovador, os_descricao, os_defeito, os_observacoes, os_laudo, os_produtos_html, os_servicos_html, os_itens_html, os_valor_total, data_atual',
            ]);
        }
    }

    private function m20260711000001_55_down()
    {
        foreach (['clientes_vinculos', 'whatsapp_envios', 'whatsapp_templates', 'email_envios'] as $t) {
            if ($this->db->table_exists($t)) {
                $this->dbforge->drop_table($t, true);
            }
        }

        $colunas = [
            'os' => ['aceite_token', 'aceite_status', 'aceite_expira', 'aceite_data', 'aceite_nome', 'aceite_ip', 'aceite_obs', 'aceite_assinatura_id'],
            'clientes' => ['whatsapp_notificacao'],
            'notification_triggers' => ['whatsapp_grupos', 'whatsapp_template'],
            'configuracoes_nfe' => ['ctribnac_padrao', 'ctribmun_padrao'],
            'notas_fiscais' => ['descricao_servico'],
        ];
        foreach ($colunas as $tabela => $cols) {
            foreach ($cols as $c) {
                if ($this->db->field_exists($c, $tabela)) {
                    $this->dbforge->drop_column($tabela, $c);
                }
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                             */
    /* ------------------------------------------------------------------ */

    /** Adiciona uma coluna só se a tabela existir e a coluna faltar. */
    private function m20260711000001_55_addColuna($tabela, $coluna, $definicao)
    {
        if ($this->db->table_exists($tabela) && ! $this->db->field_exists($coluna, $tabela)) {
            $this->db->query("ALTER TABLE `{$tabela}` ADD COLUMN {$definicao}");
        }
    }

    /** Cria um índice só se a tabela existir e o índice ainda não existir. */
    private function m20260711000001_55_addIndice($tabela, $indice, $sqlCriacao)
    {
        if (! $this->db->table_exists($tabela)) {
            return;
        }
        $existe = $this->db->query(
            'SELECT 1 FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$tabela, $indice]
        )->num_rows();
        if (! $existe) {
            $this->db->query($sqlCriacao);
        }
    }

    /** Semeia os modelos de WhatsApp (só os slugs ainda inexistentes). */
    private function m20260711000001_55_seedWhatsappTemplates()
    {
        if (! $this->db->table_exists('whatsapp_templates')) {
            return;
        }

        // Modelo da OS: herda o texto atual de configuracoes.notifica_whats.
        if ($this->db->where('slug', 'os')->count_all_results('whatsapp_templates') == 0) {
            $row = $this->db->where('config', 'notifica_whats')->limit(1)->get('configuracoes')->row();
            $conteudo = ($row && trim((string) $row->valor) !== '')
                ? $row->valor
                : 'Olá {CLIENTE_NOME}, sua Ordem de Serviço #{NUMERO_OS} está com status: {STATUS_OS}.';
            $this->db->insert('whatsapp_templates', [
                'slug' => 'os',
                'nome' => 'Notificação da OS',
                'descricao' => 'Usado nas notificações de Ordem de Serviço (gatilhos e envio manual).',
                'tags' => '{CLIENTE_NOME},{NUMERO_OS},{STATUS_OS},{VALOR_OS},{DESCRI_PRODUTOS},{EMITENTE},{TELEFONE_EMITENTE},{OBS_OS},{DEFEITO_OS},{LAUDO_OS},{DATA_FINAL},{DATA_INICIAL},{DATA_GARANTIA}',
                'conteudo' => $conteudo,
                'ativo' => 1,
            ]);
        }

        $outros = [
            ['cobranca', 'Cobrança / Link de pagamento', 'Enviado ao mandar o link de pagamento/boleto por WhatsApp.', '{CLIENTE_NOME},{REFERENCIA},{LINK}', "Olá {CLIENTE_NOME}! Segue o link para pagamento da {REFERENCIA}:\n{LINK}"],
            ['aprovacao', 'Link de aprovação', 'Enviado ao mandar o link de aprovação da OS por WhatsApp.', '{CLIENTE_NOME},{NUMERO_OS},{LINK}', "Olá {CLIENTE_NOME}! Para aprovar ou reprovar a OS #{NUMERO_OS}, acesse o link:\n{LINK}"],
            ['aceite', 'Link de aceite do serviço', 'Enviado ao mandar o link de aceite do serviço realizado por WhatsApp.', '{CLIENTE_NOME},{NUMERO_OS},{LINK}', "Olá {CLIENTE_NOME}! Seu serviço (OS #{NUMERO_OS}) foi concluído. Confirme o aceite e assine pelo link:\n{LINK}"],
        ];
        foreach ($outros as $t) {
            if ($this->db->where('slug', $t[0])->count_all_results('whatsapp_templates') == 0) {
                $this->db->insert('whatsapp_templates', [
                    'slug' => $t[0],
                    'nome' => $t[1],
                    'descricao' => $t[2],
                    'tags' => $t[3],
                    'conteudo' => $t[4],
                    'ativo' => 1,
                ]);
            }
        }
    }

    // ---- 20260711000002_add_os_aprovacao.php (add coluna nao parseavel (mantida por seguranca)) ----
    private $colunas = [
        'aprovacao_token' => "`aprovacao_token` VARCHAR(64) NULL DEFAULT NULL",
        'aprovacao_status' => "`aprovacao_status` VARCHAR(20) NULL DEFAULT NULL",
        'aprovacao_expira' => "`aprovacao_expira` DATETIME NULL DEFAULT NULL",
        'aprovacao_data' => "`aprovacao_data` DATETIME NULL DEFAULT NULL",
        'aprovacao_nome' => "`aprovacao_nome` VARCHAR(150) NULL DEFAULT NULL",
        'aprovacao_ip' => "`aprovacao_ip` VARCHAR(45) NULL DEFAULT NULL",
        'aprovacao_obs' => "`aprovacao_obs` TEXT NULL DEFAULT NULL",
    ];

    private function m20260711000002_56_up()
    {
        if (! $this->db->table_exists('os')) {
            return;
        }

        foreach ($this->colunas as $coluna => $definicao) {
            if (! $this->db->field_exists($coluna, 'os')) {
                $this->db->query("ALTER TABLE `os` ADD COLUMN {$definicao}");
            }
        }

        $existeIndice = $this->db->query(
            'SELECT 1 FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            ['os', 'idx_os_aprovacao_token']
        )->num_rows();
        if (! $existeIndice) {
            $this->db->query('ALTER TABLE `os` ADD INDEX `idx_os_aprovacao_token` (`aprovacao_token`)');
        }
    }

    private function m20260711000002_56_down()
    {
        foreach (array_keys($this->colunas) as $coluna) {
            if ($this->db->field_exists($coluna, 'os')) {
                $this->dbforge->drop_column('os', $coluna);
            }
        }
    }

    // ---- 20260711000003_create_rh_estrutura.php (cria tabela nova: rh_colaboradores,rh_jornadas,rh_unidades) ----
    private function m20260711000003_57_up()
    {
        // ------------------------------------------------------------------
        // rh_unidades — locais de trabalho / geofence
        // ------------------------------------------------------------------
        if (! $this->db->table_exists('rh_unidades')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'nome' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ],
                'endereco' => [
                    'type' => 'VARCHAR',
                    'constraint' => 200,
                    'null' => true,
                ],
                'latitude' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,7',
                    'null' => true,
                ],
                'longitude' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,7',
                    'null' => true,
                ],
                'raio_metros' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 150,
                    'comment' => 'Raio do geofence em metros',
                ],
                'situacao' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('rh_unidades', true);
        }

        // ------------------------------------------------------------------
        // rh_jornadas — escalas/jornadas de trabalho
        // ------------------------------------------------------------------
        if (! $this->db->table_exists('rh_jornadas')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'nome' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ],
                'carga_diaria_min' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 480,
                    'comment' => 'Carga diária prevista em minutos (padrão 8h)',
                ],
                'tolerancia_min' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 10,
                    'comment' => 'Tolerância de atraso/antecipação em minutos',
                ],
                'dias_semana' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => '1,2,3,4,5',
                    'comment' => 'Dias trabalhados (0=dom .. 6=sáb)',
                ],
                'hora_entrada' => [
                    'type' => 'TIME',
                    'null' => true,
                ],
                'hora_saida' => [
                    'type' => 'TIME',
                    'null' => true,
                ],
                'intervalo_min' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 60,
                    'comment' => 'Intervalo (almoço) previsto em minutos',
                ],
                'situacao' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('rh_jornadas', true);
        }

        // ------------------------------------------------------------------
        // rh_colaboradores — cadastro de colaboradores
        // ------------------------------------------------------------------
        if (! $this->db->table_exists('rh_colaboradores')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'usuarios_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'Vínculo opcional com usuarios (login no sistema)',
                ],
                'nome' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ],
                'cpf' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                ],
                'rg' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                ],
                'data_nascimento' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'cargo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => true,
                ],
                'departamento' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => true,
                ],
                'tipo_contrato' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'default' => 'CLT',
                    'comment' => 'CLT | PJ | Estagio | Temporario',
                ],
                'admissao' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'demissao' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'unidade_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'jornada_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'salario_base' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                ],
                'valor_hora' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                    'comment' => 'Valor da hora (base para extras); se nulo, derivado do salário',
                ],
                'email' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'celular' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                    'comment' => 'Usado para casar batidas de ponto via WhatsApp',
                ],
                'pix_tipo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                ],
                'pix_chave' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'foto_base64' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                    'comment' => 'Foto de perfil (data URI base64)',
                ],
                'foto_mime' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => true,
                ],
                'observacoes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'situacao' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'comment' => '1=ativo 0=inativo/desligado',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('rh_colaboradores', true);

            $this->db->query('ALTER TABLE `rh_colaboradores` ADD INDEX `idx_rh_colab_usuario` (`usuarios_id`)');
            $this->db->query('ALTER TABLE `rh_colaboradores` ADD INDEX `idx_rh_colab_situacao` (`situacao`)');
        }
    }

    private function m20260711000003_57_down()
    {
        foreach (['rh_colaboradores', 'rh_jornadas', 'rh_unidades'] as $tabela) {
            if ($this->db->table_exists($tabela)) {
                $this->dbforge->drop_table($tabela, true);
            }
        }
    }

    // ---- 20260711000004_create_rh_ponto.php (cria tabela nova: rh_face_biometria,rh_ocorrencias,rh_ponto_registros) ----
    private function m20260711000004_58_up()
    {
        // ------------------------------------------------------------------
        // rh_ponto_registros — batidas de ponto
        // ------------------------------------------------------------------
        if (! $this->db->table_exists('rh_ponto_registros')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'colaborador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'data_hora' => [
                    'type' => 'DATETIME',
                    'comment' => 'Momento da batida',
                ],
                'tipo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'entrada',
                    'comment' => 'entrada | saida | inicio_intervalo | fim_intervalo',
                ],
                'origem' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'browser',
                    'comment' => 'browser | whatsapp | manual',
                ],
                'unidade_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'latitude' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,7',
                    'null' => true,
                ],
                'longitude' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,7',
                    'null' => true,
                ],
                'dentro_geofence' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => true,
                    'comment' => '1=dentro 0=fora null=sem referência',
                ],
                'distancia_metros' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'face_score' => [
                    'type' => 'DECIMAL',
                    'constraint' => '5,4',
                    'null' => true,
                    'comment' => 'Similaridade facial (0..1); quanto maior, melhor',
                ],
                'foto_base64' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                    'comment' => 'Selfie da batida (data URI base64)',
                ],
                'foto_mime' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => true,
                ],
                'ip' => [
                    'type' => 'VARCHAR',
                    'constraint' => 45,
                    'null' => true,
                ],
                'user_agent' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'valido',
                    'comment' => 'valido | ajustado | pendente | rejeitado',
                ],
                'observacao' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'registrado_por' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'usuarios_id de quem lançou (quando origem=manual)',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('rh_ponto_registros', true);

            $this->db->query('ALTER TABLE `rh_ponto_registros` ADD INDEX `idx_rh_ponto_colab_data` (`colaborador_id`, `data_hora`)');
            $this->db->query('ALTER TABLE `rh_ponto_registros` ADD INDEX `idx_rh_ponto_status` (`status`)');
        }

        // ------------------------------------------------------------------
        // rh_face_biometria — descriptor facial de referência
        // ------------------------------------------------------------------
        if (! $this->db->table_exists('rh_face_biometria')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'colaborador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'descriptor' => [
                    'type' => 'TEXT',
                    'comment' => 'Vetor facial (JSON de floats) gerado no navegador',
                ],
                'foto_ref' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                    'comment' => 'Selfie de referência (data URI base64)',
                ],
                'foto_mime' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => true,
                ],
                'modelo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => true,
                    'comment' => 'Identificação do modelo/lib usada',
                ],
                'situacao' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('rh_face_biometria', true);

            $this->db->query('ALTER TABLE `rh_face_biometria` ADD INDEX `idx_rh_face_colab` (`colaborador_id`)');
        }

        // ------------------------------------------------------------------
        // rh_ocorrencias — justificativas / correções (com aprovação)
        // ------------------------------------------------------------------
        if (! $this->db->table_exists('rh_ocorrencias')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'colaborador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'tipo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'comment' => 'correcao_ponto | justificativa_falta | abono',
                ],
                'data_referencia' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'registro_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'Batida relacionada (rh_ponto_registros.id)',
                ],
                'descricao' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'anexo_base64' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                    'comment' => 'Atestado/comprovante (data URI base64)',
                ],
                'anexo_mime' => [
                    'type' => 'VARCHAR',
                    'constraint' => 60,
                    'null' => true,
                ],
                'anexo_nome' => [
                    'type' => 'VARCHAR',
                    'constraint' => 160,
                    'null' => true,
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'pendente',
                    'comment' => 'pendente | aprovado | recusado',
                ],
                'aprovador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'data_analise' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'resposta' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('rh_ocorrencias', true);

            $this->db->query('ALTER TABLE `rh_ocorrencias` ADD INDEX `idx_rh_ocorr_colab` (`colaborador_id`)');
            $this->db->query('ALTER TABLE `rh_ocorrencias` ADD INDEX `idx_rh_ocorr_status` (`status`)');
        }
    }

    private function m20260711000004_58_down()
    {
        foreach (['rh_ocorrencias', 'rh_face_biometria', 'rh_ponto_registros'] as $tabela) {
            if ($this->db->table_exists($tabela)) {
                $this->dbforge->drop_table($tabela, true);
            }
        }
    }

    // ---- 20260711000005_create_rh_extras.php (cria tabela nova: rh_ausencias,rh_horas,rh_lancamentos) ----
    private function m20260711000005_59_up()
    {
        // ------------------------------------------------------------------
        // rh_lancamentos — extras financeiros por competência
        // ------------------------------------------------------------------
        if (! $this->db->table_exists('rh_lancamentos')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'colaborador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'competencia' => [
                    'type' => 'VARCHAR',
                    'constraint' => 7,
                    'comment' => 'Competência no formato YYYY-MM',
                ],
                'tipo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'comment' => 'hora_extra | adicional | comissao | bonus | adiantamento | desconto | falta | vale',
                ],
                'natureza' => [
                    'type' => 'VARCHAR',
                    'constraint' => 10,
                    'default' => 'provento',
                    'comment' => 'provento | desconto',
                ],
                'descricao' => [
                    'type' => 'VARCHAR',
                    'constraint' => 160,
                    'null' => true,
                ],
                'quantidade' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                    'comment' => 'Qtd de horas/itens quando aplicável',
                ],
                'valor' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => 0,
                ],
                'aprovado' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ],
                'aprovador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'origem' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'manual',
                    'comment' => 'manual | automatico (gerado pelo cálculo de horas)',
                ],
                'referencia_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'Origem do lançamento (ex.: rh_horas.id)',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('rh_lancamentos', true);

            $this->db->query('ALTER TABLE `rh_lancamentos` ADD INDEX `idx_rh_lanc_colab_comp` (`colaborador_id`, `competencia`)');
        }

        // ------------------------------------------------------------------
        // rh_horas — consolidação mensal de horas
        // ------------------------------------------------------------------
        if (! $this->db->table_exists('rh_horas')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'colaborador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'competencia' => [
                    'type' => 'VARCHAR',
                    'constraint' => 7,
                    'comment' => 'YYYY-MM',
                ],
                'dias_trabalhados' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'minutos_trabalhados' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'minutos_previstos' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'minutos_extras_50' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'minutos_extras_100' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'minutos_faltas' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'saldo_banco_min' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                    'comment' => 'Saldo de banco de horas em minutos (+/-)',
                ],
                'fechado' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ],
                'data_fechamento' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('rh_horas', true);

            $this->db->query('ALTER TABLE `rh_horas` ADD UNIQUE INDEX `uniq_rh_horas_colab_comp` (`colaborador_id`, `competencia`)');
        }

        // ------------------------------------------------------------------
        // rh_ausencias — férias/folgas/atestados/licenças (com aprovação)
        // ------------------------------------------------------------------
        if (! $this->db->table_exists('rh_ausencias')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'colaborador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'tipo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'comment' => 'ferias | folga | atestado | licenca',
                ],
                'data_inicio' => [
                    'type' => 'DATE',
                ],
                'data_fim' => [
                    'type' => 'DATE',
                ],
                'dias' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'motivo' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'anexo_base64' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'anexo_mime' => [
                    'type' => 'VARCHAR',
                    'constraint' => 60,
                    'null' => true,
                ],
                'anexo_nome' => [
                    'type' => 'VARCHAR',
                    'constraint' => 160,
                    'null' => true,
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'pendente',
                    'comment' => 'pendente | aprovado | recusado',
                ],
                'aprovador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'data_analise' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'resposta' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('rh_ausencias', true);

            $this->db->query('ALTER TABLE `rh_ausencias` ADD INDEX `idx_rh_ausencia_colab` (`colaborador_id`)');
            $this->db->query('ALTER TABLE `rh_ausencias` ADD INDEX `idx_rh_ausencia_status` (`status`)');
        }
    }

    private function m20260711000005_59_down()
    {
        foreach (['rh_ausencias', 'rh_horas', 'rh_lancamentos'] as $tabela) {
            if ($this->db->table_exists($tabela)) {
                $this->dbforge->drop_table($tabela, true);
            }
        }
    }

    // ---- 20260711000006_add_rh_permissoes_e_config.php (faz seed (insert/update); muda tipo/charset/drop) ----
    /** Flags exclusivas do administrador de RH. */
    private $flagsAdmin = ['vRh', 'eRh', 'aprovarRh', 'vRhFinanceiro', 'fecharFolha'];

    /** Flags do colaborador (autoatendimento). */
    private $flagsColaborador = ['vAreaColaborador', 'baterPonto'];

    private function m20260711000006_60_up()
    {
        $todasFlags = array_merge($this->flagsAdmin, $this->flagsColaborador);

        // 1. Mescla as flags novas em cada grupo existente ------------------
        $grupos = $this->db->get('permissoes')->result();
        foreach ($grupos as $grupo) {
            $permissoes = @unserialize($grupo->permissoes);
            if (! is_array($permissoes)) {
                continue; // grupo com blob corrompido: não mexe
            }

            $ehAdmin = ! empty($permissoes['cSistema']) && $permissoes['cSistema'] == 1;

            foreach ($todasFlags as $flag) {
                if (array_key_exists($flag, $permissoes)) {
                    continue; // já tem a flag: preserva o valor atual
                }
                if (in_array($flag, $this->flagsAdmin, true)) {
                    $permissoes[$flag] = $ehAdmin ? 1 : 0;
                } else {
                    $permissoes[$flag] = 0;
                }
            }

            $this->db->where('idPermissao', $grupo->idPermissao)
                     ->update('permissoes', ['permissoes' => serialize($permissoes)]);
        }

        // 2. Cria o grupo "Colaborador" ------------------------------------
        if ($this->db->where('nome', 'Colaborador')->count_all_results('permissoes') == 0) {
            $permissoesColaborador = [
                // acessos administrativos: todos negados
                'aCliente' => 0, 'eCliente' => 0, 'dCliente' => 0, 'vCliente' => 0,
                'aProduto' => 0, 'eProduto' => 0, 'dProduto' => 0, 'vProduto' => 0,
                'aServico' => 0, 'eServico' => 0, 'dServico' => 0, 'vServico' => 0,
                'aOs' => 0, 'eOs' => 0, 'dOs' => 0, 'vOs' => 0,
                'aVenda' => 0, 'eVenda' => 0, 'dVenda' => 0, 'vVenda' => 0,
                'aGarantia' => 0, 'eGarantia' => 0, 'dGarantia' => 0, 'vGarantia' => 0,
                'aArquivo' => 0, 'eArquivo' => 0, 'dArquivo' => 0, 'vArquivo' => 0,
                'aPagamento' => 0, 'ePagamento' => 0, 'dPagamento' => 0, 'vPagamento' => 0,
                'aLancamento' => 0, 'eLancamento' => 0, 'dLancamento' => 0, 'vLancamento' => 0,
                'aCobranca' => 0, 'eCobranca' => 0, 'dCobranca' => 0, 'vCobranca' => 0,
                'cUsuario' => 0, 'cEmitente' => 0, 'cPermissao' => 0, 'cBackup' => 0,
                'cAuditoria' => 0, 'cEmail' => 0, 'cSistema' => 0,
                'rCliente' => 0, 'rProduto' => 0, 'rServico' => 0, 'rOs' => 0,
                'rVenda' => 0, 'rFinanceiro' => 0,
                // RH: só autoatendimento
                'vRh' => 0, 'eRh' => 0, 'aprovarRh' => 0, 'vRhFinanceiro' => 0, 'fecharFolha' => 0,
                'vAreaColaborador' => 1, 'baterPonto' => 1,
            ];

            $this->db->insert('permissoes', [
                'nome' => 'Colaborador',
                'data' => date('Y-m-d'),
                'permissoes' => serialize($permissoesColaborador),
                'situacao' => 1,
            ]);
            log_message('info', 'Grupo de permissao Colaborador criado com sucesso');
        }

        // 3. Configurações do ponto ----------------------------------------
        // Garante que config/valor comportam as chaves do RH (defensivo: em
        // bases sem a widen_configuracoes, config é VARCHAR(20) e truncaria
        // 'rh_geofence_obrigatorio'/'rh_tolerancia_padrao_min'). Ver [[configuracoes-varchar20]].
        $colConfig = $this->db->field_data('configuracoes');
        foreach ($colConfig as $c) {
            if ($c->name === 'config' && (int) $c->max_length < 60) {
                $this->db->query('ALTER TABLE `configuracoes` MODIFY `config` VARCHAR(60) NOT NULL');
            }
        }

        $configs = [
            'rh_geofence_obrigatorio' => '0',  // 1 = bloqueia batida fora do raio
            'rh_face_obrigatorio' => '0',      // 1 = exige reconhecimento facial
            'rh_face_score_minimo' => '0.55',  // limiar de similaridade aceito
            'rh_tolerancia_padrao_min' => '10',
        ];
        foreach ($configs as $chave => $valor) {
            if ($this->db->where('config', $chave)->count_all_results('configuracoes') == 0) {
                $this->db->insert('configuracoes', ['config' => $chave, 'valor' => $valor]);
            }
        }
    }

    private function m20260711000006_60_down()
    {
        // Remove o grupo Colaborador
        $this->db->where('nome', 'Colaborador')->delete('permissoes');

        // Remove as flags de RH dos demais grupos
        $todasFlags = array_merge($this->flagsAdmin, $this->flagsColaborador);
        $grupos = $this->db->get('permissoes')->result();
        foreach ($grupos as $grupo) {
            $permissoes = @unserialize($grupo->permissoes);
            if (! is_array($permissoes)) {
                continue;
            }
            foreach ($todasFlags as $flag) {
                unset($permissoes[$flag]);
            }
            $this->db->where('idPermissao', $grupo->idPermissao)
                     ->update('permissoes', ['permissoes' => serialize($permissoes)]);
        }

        // Remove as configs
        foreach (['rh_geofence_obrigatorio', 'rh_face_obrigatorio', 'rh_face_score_minimo', 'rh_tolerancia_padrao_min'] as $chave) {
            $this->db->where('config', $chave)->delete('configuracoes');
        }
    }

    // ---- 20260712000001_create_rh_holerites.php (cria tabela nova: rh_holerites) ----
    private function m20260712000001_61_up()
    {
        if (! $this->db->table_exists('rh_holerites')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'colaborador_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'competencia' => [
                    'type' => 'VARCHAR',
                    'constraint' => 7,
                    'comment' => 'YYYY-MM',
                ],
                'arquivo_base64' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                    'comment' => 'PDF oficial (data URI base64)',
                ],
                'arquivo_mime' => [
                    'type' => 'VARCHAR',
                    'constraint' => 60,
                    'null' => true,
                ],
                'arquivo_nome' => [
                    'type' => 'VARCHAR',
                    'constraint' => 160,
                    'null' => true,
                ],
                'valor_liquido' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                    'comment' => 'Líquido informado no recibo (opcional)',
                ],
                'observacao' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_by' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('rh_holerites', true);

            $this->db->query('ALTER TABLE `rh_holerites` ADD UNIQUE INDEX `uniq_rh_holerite_colab_comp` (`colaborador_id`, `competencia`)');
        }
    }

    private function m20260712000001_61_down()
    {
        if ($this->db->table_exists('rh_holerites')) {
            $this->dbforge->drop_table('rh_holerites', true);
        }
    }

    // ---- 20260712000002_add_aprovacao_verificacao_token.php (add coluna nao parseavel (mantida por seguranca)) ----
    private $colunasOs = [
        'aprovacao_exige_token' => "`aprovacao_exige_token` TINYINT(1) NOT NULL DEFAULT 0",
        'aprovacao_codigo' => "`aprovacao_codigo` VARCHAR(64) NULL DEFAULT NULL",
        'aprovacao_codigo_expira' => "`aprovacao_codigo_expira` DATETIME NULL DEFAULT NULL",
        'aprovacao_codigo_validado' => "`aprovacao_codigo_validado` TINYINT(1) NOT NULL DEFAULT 0",
        'aprovacao_codigo_tentativas' => "`aprovacao_codigo_tentativas` INT NOT NULL DEFAULT 0",
        'aprovacao_codigo_canal' => "`aprovacao_codigo_canal` VARCHAR(20) NULL DEFAULT NULL",
    ];

    private $colunasClientes = [
        'aprovacao_exige_token' => "`aprovacao_exige_token` TINYINT(1) NOT NULL DEFAULT 0",
    ];

    private function m20260712000002_62_up()
    {
        if ($this->db->table_exists('os')) {
            foreach ($this->colunasOs as $coluna => $definicao) {
                if (! $this->db->field_exists($coluna, 'os')) {
                    $this->db->query("ALTER TABLE `os` ADD COLUMN {$definicao}");
                }
            }
        }

        if ($this->db->table_exists('clientes')) {
            foreach ($this->colunasClientes as $coluna => $definicao) {
                if (! $this->db->field_exists($coluna, 'clientes')) {
                    $this->db->query("ALTER TABLE `clientes` ADD COLUMN {$definicao}");
                }
            }
        }
    }

    private function m20260712000002_62_down()
    {
        foreach (array_keys($this->colunasOs) as $coluna) {
            if ($this->db->field_exists($coluna, 'os')) {
                $this->dbforge->drop_column('os', $coluna);
            }
        }
        foreach (array_keys($this->colunasClientes) as $coluna) {
            if ($this->db->field_exists($coluna, 'clientes')) {
                $this->dbforge->drop_column('clientes', $coluna);
            }
        }
    }

    // ---- 20260712000003_add_aprovacao_token_numeros.php (add coluna nao coberta: os.aprovacao_token_numeros,clientes.aprovacao_token_numeros) ----
    private function m20260712000003_63_up()
    {
        if ($this->db->table_exists('os') && ! $this->db->field_exists('aprovacao_token_numeros', 'os')) {
            $this->db->query("ALTER TABLE `os` ADD COLUMN `aprovacao_token_numeros` TEXT NULL DEFAULT NULL");
        }

        if ($this->db->table_exists('clientes') && ! $this->db->field_exists('aprovacao_token_numeros', 'clientes')) {
            $this->db->query("ALTER TABLE `clientes` ADD COLUMN `aprovacao_token_numeros` TEXT NULL DEFAULT NULL");
        }
    }

    private function m20260712000003_63_down()
    {
        if ($this->db->field_exists('aprovacao_token_numeros', 'os')) {
            $this->dbforge->drop_column('os', 'aprovacao_token_numeros');
        }
        if ($this->db->field_exists('aprovacao_token_numeros', 'clientes')) {
            $this->dbforge->drop_column('clientes', 'aprovacao_token_numeros');
        }
    }

    // ---- 20260712000004_add_ponto_os_vinculo.php (add coluna nao coberta: rh_ponto_registros.os_id,os.latitude,os.longitude) ----
    private function m20260712000004_64_up()
    {
        if ($this->db->table_exists('rh_ponto_registros')
            && ! $this->db->field_exists('os_id', 'rh_ponto_registros')) {
            $this->dbforge->add_column('rh_ponto_registros', [
                'os_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'comment' => 'OS vinculada à batida (atendimento em campo)',
                    'after' => 'unidade_id',
                ],
            ]);
            $this->db->query('ALTER TABLE `rh_ponto_registros` ADD INDEX `idx_rh_ponto_os` (`os_id`)');
        }

        if ($this->db->table_exists('os')) {
            if (! $this->db->field_exists('latitude', 'os')) {
                $this->dbforge->add_column('os', [
                    'latitude' => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
                ]);
            }
            if (! $this->db->field_exists('longitude', 'os')) {
                $this->dbforge->add_column('os', [
                    'longitude' => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
                ]);
            }
        }
    }

    private function m20260712000004_64_down()
    {
        if ($this->db->field_exists('os_id', 'rh_ponto_registros')) {
            $this->dbforge->drop_column('rh_ponto_registros', 'os_id');
        }
        // Mantém os.latitude/longitude (podem ser usados por outros recursos).
    }

    // ---- 20260712000005_add_ocorrencia_correcao.php (add coluna nao coberta: rh_ocorrencias.correcao_tipo,rh_ocorrencias.correcao_data_hora,rh_ocorrencias.correcao_aplicada) ----
    private function m20260712000005_65_up()
    {
        if (! $this->db->table_exists('rh_ocorrencias')) {
            return;
        }
        if (! $this->db->field_exists('correcao_tipo', 'rh_ocorrencias')) {
            $this->dbforge->add_column('rh_ocorrencias', [
                'correcao_tipo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                    'comment' => 'entrada | saida | inicio_intervalo | fim_intervalo',
                    'after' => 'registro_id',
                ],
            ]);
        }
        if (! $this->db->field_exists('correcao_data_hora', 'rh_ocorrencias')) {
            $this->dbforge->add_column('rh_ocorrencias', [
                'correcao_data_hora' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'correcao_tipo',
                ],
            ]);
        }
        if (! $this->db->field_exists('correcao_aplicada', 'rh_ocorrencias')) {
            $this->dbforge->add_column('rh_ocorrencias', [
                'correcao_aplicada' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'after' => 'correcao_data_hora',
                ],
            ]);
        }
    }

    private function m20260712000005_65_down()
    {
        foreach (['correcao_aplicada', 'correcao_data_hora', 'correcao_tipo'] as $col) {
            if ($this->db->field_exists($col, 'rh_ocorrencias')) {
                $this->dbforge->drop_column('rh_ocorrencias', $col);
            }
        }
    }

    // ---- 20260714000001_rh_melhorias_clt.php (faz seed (insert/update); muda tipo/charset/drop) ----
    private function m20260714000001_66_up()
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

    private function m20260714000001_66_down()
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

    // ---- 20260715000001_add_whatsapp_clientes_gatilhos.php (add coluna nao coberta: notification_triggers.whatsapp_clientes) ----
    private function m20260715000001_67_up()
    {
        if (! $this->db->table_exists('notification_triggers')) {
            return;
        }
        if ($this->db->field_exists('whatsapp_clientes', 'notification_triggers')) {
            return;
        }

        $this->db->query(
            'ALTER TABLE `notification_triggers`
             ADD COLUMN `whatsapp_clientes` TEXT NULL DEFAULT NULL
             COMMENT \'IDs de clientes (csv). Vazio = todos. Restringe o envio do modelo aos grupos WhatsApp.\''
        );
    }

    private function m20260715000001_67_down()
    {
        if ($this->db->table_exists('notification_triggers')
            && $this->db->field_exists('whatsapp_clientes', 'notification_triggers')) {
            $this->dbforge->drop_column('notification_triggers', 'whatsapp_clientes');
        }
    }

    // ---- 20260715000002_add_xml_notas_fiscais.php (add coluna nao coberta: notas_fiscais.xml) ----
    private function m20260715000002_68_up()
    {
        if (! $this->db->table_exists('notas_fiscais')) {
            return;
        }
        if ($this->db->field_exists('xml', 'notas_fiscais')) {
            return;
        }

        $this->db->query(
            'ALTER TABLE `notas_fiscais`
             ADD COLUMN `xml` LONGTEXT NULL DEFAULT NULL
             COMMENT \'Conteúdo do XML autorizado (NF-e / NFS-e)\'
             AFTER `xml_path`'
        );
    }

    private function m20260715000002_68_down()
    {
        if ($this->db->table_exists('notas_fiscais')
            && $this->db->field_exists('xml', 'notas_fiscais')) {
            $this->dbforge->drop_column('notas_fiscais', 'xml');
        }
    }

    // ---- 20260716000001_rh_ponto_inicio_e_desconto_flag.php (faz seed (insert/update)) ----
    private function m20260716000001_69_up()
    {
        if ($this->db->table_exists('rh_colaboradores')
            && ! $this->db->field_exists('ponto_inicio', 'rh_colaboradores')) {
            $this->dbforge->add_column('rh_colaboradores', [
                'ponto_inicio' => [
                    'type' => 'DATE',
                    'null' => true,
                    'comment' => 'Início do controle de ponto (faltas/banco). NULL = só conta batidas reais, sem dívida',
                ],
            ]);
        }

        if ($this->db->table_exists('configuracoes')) {
            $exists = $this->db->where('config', 'rh_falta_desconto_automatico')
                ->count_all_results('configuracoes');
            if ((int) $exists === 0) {
                $this->db->insert('configuracoes', [
                    'config' => 'rh_falta_desconto_automatico',
                    'valor' => '0',
                ]);
            }
        }
    }

    private function m20260716000001_69_down()
    {
        if ($this->db->table_exists('rh_colaboradores')
            && $this->db->field_exists('ponto_inicio', 'rh_colaboradores')) {
            $this->dbforge->drop_column('rh_colaboradores', 'ponto_inicio');
        }
        if ($this->db->table_exists('configuracoes')) {
            $this->db->where('config', 'rh_falta_desconto_automatico')->delete('configuracoes');
        }
    }

    // ---- 20260718000001_add_os_data_atribuicao.php (faz seed (insert/update)) ----
    private function m20260718000001_70_up()
    {
        if (! $this->db->table_exists('os')) {
            return;
        }

        // 1) Coluna denormalizada na OS.
        if (! $this->db->field_exists('data_atribuicao', 'os')) {
            $this->db->query("ALTER TABLE `os` ADD COLUMN `data_atribuicao` DATETIME NULL DEFAULT NULL COMMENT 'Data da 1a atribuicao de tecnico (funil do ciclo da OS)'");
        }

        // 2) Índice para filtros por período nos relatórios.
        $existeIndice = $this->db->query(
            'SELECT 1 FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            ['os', 'idx_os_data_atribuicao']
        )->num_rows();
        if (! $existeIndice) {
            $this->db->query('ALTER TABLE `os` ADD INDEX `idx_os_data_atribuicao` (`data_atribuicao`)');
        }

        // 3) Backfill: primeira atribuição registrada no histórico, para as OS
        //    que ainda não têm a data carimbada.
        if ($this->db->table_exists('os_tecnico_atribuicao')) {
            $this->db->query(
                'UPDATE `os` o
                    JOIN (
                        SELECT `os_id`, MIN(`data_atribuicao`) AS primeira
                          FROM `os_tecnico_atribuicao`
                         GROUP BY `os_id`
                    ) a ON a.`os_id` = o.`idOs`
                    SET o.`data_atribuicao` = a.`primeira`
                  WHERE o.`data_atribuicao` IS NULL'
            );
        }
    }

    private function m20260718000001_70_down()
    {
        if ($this->db->field_exists('data_atribuicao', 'os')) {
            $existeIndice = $this->db->query(
                'SELECT 1 FROM information_schema.STATISTICS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
                ['os', 'idx_os_data_atribuicao']
            )->num_rows();
            if ($existeIndice) {
                $this->db->query('ALTER TABLE `os` DROP INDEX `idx_os_data_atribuicao`');
            }
            $this->dbforge->drop_column('os', 'data_atribuicao');
        }
    }

    // ---- 20260718000001_add_os_nao_programada.php (faz seed (insert/update)) ----
    private function m20260718000001_71_up()
    {
        // 1) Coluna de marcação da OS -----------------------------------
        if ($this->db->table_exists('os') && ! $this->db->field_exists('nao_programada', 'os')) {
            $this->db->query(
                "ALTER TABLE `os` ADD COLUMN `nao_programada` TINYINT(1) NOT NULL DEFAULT 0"
            );
        }

        // 2) Permissão para os grupos com acesso à Área do Técnico -------
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

                // Só concede a quem já acessa a Área do Técnico e ainda não tem a permissão.
                $temAreaTecnico = ! empty($perms['vTecnicoDashboard']);
                if ($temAreaTecnico && ! array_key_exists('aTecnicoAtividade', $perms)) {
                    $perms['aTecnicoAtividade'] = 1;
                    $this->db->where('idPermissao', $g->idPermissao);
                    $this->db->update('permissoes', ['permissoes' => serialize($perms)]);
                }
            }
        }
    }

    private function m20260718000001_71_down()
    {
        if ($this->db->table_exists('os') && $this->db->field_exists('nao_programada', 'os')) {
            $this->db->query('ALTER TABLE `os` DROP COLUMN `nao_programada`');
        }

        // Remove a permissão dos grupos (mantém o restante intacto).
        if ($this->db->table_exists('permissoes')) {
            $grupos = $this->db->get('permissoes')->result();
            foreach ($grupos as $g) {
                set_error_handler(static function () {
                    return true;
                });
                $perms = unserialize((string) $g->permissoes);
                restore_error_handler();

                if (is_array($perms) && array_key_exists('aTecnicoAtividade', $perms)) {
                    unset($perms['aTecnicoAtividade']);
                    $this->db->where('idPermissao', $g->idPermissao);
                    $this->db->update('permissoes', ['permissoes' => serialize($perms)]);
                }
            }
        }
    }

    // ---- 20260719000001_restore_permissao_fiscal_admin.php (faz seed (insert/update)) ----
    private function m20260719000001_72_up()
    {
        if (! $this->db->table_exists('permissoes')) {
            return;
        }

        $this->db->where('idPermissao', 1);
        $query = $this->db->get('permissoes');

        if ($query->num_rows() === 0) {
            return;
        }

        $admin = $query->row();
        $permissoes = @unserialize($admin->permissoes);

        if (! is_array($permissoes)) {
            return;
        }

        $permissoes['cNfe'] = 1; // configurar modulo fiscal
        $permissoes['eNfe'] = 1; // emitir notas
        $permissoes['vNfe'] = 1; // visualizar notas
        $permissoes['dNfe'] = 1; // cancelar notas

        $this->db->where('idPermissao', 1);
        $this->db->update('permissoes', ['permissoes' => serialize($permissoes)]);
    }

    private function m20260719000001_72_down()
    {
        // Reparo de dados: sem reversao. A remocao das permissoes fiscais do
        // admin ja e tratada pelo down() da migration do modulo fiscal.
    }
}
