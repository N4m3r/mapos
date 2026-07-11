-- ============================================================
-- MODELO "Cobrança / Boleto da NF": adiciona tags dos blocos da OS
-- ============================================================
-- Amplia a paleta de tags do modelo de e-mail 'cobranca' com os campos da
-- Ordem de Serviço (descrição, defeito, observações, laudo, produtos, serviços,
-- valores). Só atualiza a coluna `tags` (a paleta de chips do editor); não mexe
-- no assunto nem no corpo que o usuário possa ter customizado. Idempotente.
-- ============================================================

DROP PROCEDURE IF EXISTS `mapos_cobranca_os_tags`;
DELIMITER $$
CREATE PROCEDURE `mapos_cobranca_os_tags`()
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.TABLES
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'email_templates') THEN
        UPDATE `email_templates`
           SET `tags` = 'cliente_nome, cliente_email, empresa_nome, cobranca_numero, cobranca_valor, cobranca_vencimento, cobranca_descricao, cobranca_pagamento_html, cobranca_link, cobranca_pdf, cobranca_barcode, cobranca_pix, os_numero, os_status, os_data_inicial, os_data_final, os_garantia, os_aprovador, os_descricao, os_defeito, os_observacoes, os_laudo, os_produtos_html, os_servicos_html, os_itens_html, os_valor_total, data_atual'
         WHERE `slug` = 'cobranca';
    END IF;
END$$
DELIMITER ;
CALL `mapos_cobranca_os_tags`();
DROP PROCEDURE IF EXISTS `mapos_cobranca_os_tags`;
