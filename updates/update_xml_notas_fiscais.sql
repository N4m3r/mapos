-- Persiste o XML autorizado da nota fiscal no banco de dados.
-- Se a coluna já existir, ignore o erro de duplicidade.

ALTER TABLE `notas_fiscais`
    ADD COLUMN `xml` LONGTEXT NULL DEFAULT NULL
    COMMENT 'Conteúdo do XML autorizado (NF-e / NFS-e)'
    AFTER `xml_path`;
