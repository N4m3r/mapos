-- ============================================================
-- Mapos - Alarga a tabela configuracoes
-- config/valor eram VARCHAR(20) (pequeno demais para as chaves e valores novos:
-- automacao_*, notif_intervalo_disparo, layout/CSS de e-mail). Sem isso, salvar
-- essas configs falha silenciosamente e nada persiste.
-- Pode rodar mais de uma vez sem problema.
-- ============================================================
ALTER TABLE `configuracoes` MODIFY `config` VARCHAR(60) NOT NULL;
ALTER TABLE `configuracoes` MODIFY `valor` TEXT NULL;
